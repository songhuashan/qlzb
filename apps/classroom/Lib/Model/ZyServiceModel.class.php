<?php

/**
 * 云课堂服务层
 * 实现一些公共服务，充值，提现，购买等等
 * @author xiewei <master@xiew.net>
 * @version 1.0
 */
class ZyServiceModel {

    /**
     * 申请提现
     * @param integer $uid 提现用户UID
     * @param integer $wnum 提现数量/金额
     * @param integer $bcard_id 提现银行卡ID
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:申请提现的学币不是系统指定的倍数，或小于0
     * 2:没有找到用户对应的提现银行卡/账户
     * 3:有未完成的提现记录，需要等待完成
     * 4:余额转冻结失败：可能是余额不足
     * 5:提现记录添加失败
     */
    public function applyWithdraw($uid, $wnum, $bcard_id,$wtype = 1) {

        //检查提现金额是否按照系统规定的倍数
        $wb = intval(getAppConfig('withdraw_basenum'));
        if ($wnum < 0 || $wnum < $wb || $wnum % $wb != 0) {
            return 1;
        }

        //检查用户是否拥有银行卡
        if (!D('ZyBcard')->hasBcard($bcard_id, $uid)) {
            return 2;
        }

        //检查是否已经有未完成的提现记录
        if (D('ZyWithdraw','classroom')->hasUnfinished($uid)) {
            return 3;
        }

        //model
        $zyLearnc = D('ZyLearnc','classroom');

        //余额转冻结
        if (!$zyLearnc->freeze($uid, $wnum)) {
            return 4;
        }

        //申请提现
        $id = D('ZyWithdraw','classroom')->apply($uid, $wnum, $bcard_id,$wtype);
        if (!$id)
            return 5;

        //添加流水记录
        $zyLearnc->addFlow($uid, 2, $wnum, '申请提现', $id, 'zy_withdraw');

        return true;
    }

    /**
     * 申请提现
     * @param integer $uid 提现用户UID
     * @param integer $wnum 提现数量/金额
     * @param integer $bcard_id 提现银行卡ID
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:申请提现的收入余额不是系统指定的倍数，或小于0
     * 2:没有找到用户对应的提现银行卡/账户
     * 3:有未完成的提现记录，需要等待完成
     * 4:余额转冻结失败：可能是余额不足
     * 5:提现记录添加失败
     */
    public function applySpiltWithdraw($uid, $wnum, $bcard_id,$wtype = 2) {

        //检查提现金额是否按照系统规定的倍数
        $wb = intval(getAppConfig('withdraw_basenum'));
        if ($wnum < 0 || $wnum < $wb || $wnum % $wb != 0) {
            return 1;
        }

        //检查用户是否拥有银行卡
        if (!D('ZyBcard')->hasBcard($bcard_id, $uid)) {
            return 2;
        }

        //检查是否已经有未完成的提现记录
        if (D('ZyWithdraw','classroom')->hasUnfinished($uid)) {
            return 3;
        }

        //model
        $ZySplit = D('ZySplit','classroom');

        //余额转冻结
        if (!$ZySplit->freeze($uid, $wnum)) {
            return 4;
        }

        //申请提现
        $id = D('ZyWithdraw','classroom')->apply($uid, $wnum, $bcard_id,$wtype);
        if (!$id)
            return 5;

        //添加流水记录
        $ZySplit->addFlow($uid, 2, $wnum, "申请提现：$wnum", $id, 'zy_withdraw');

        return true;
    }

    /**
     * 设置提现状态
     * @param integer $id 需要设置的提现记录ID
     * @param integer $uid 该条提现记录对应的UID，后台操作设置为false
     * @param integer $status 要设置的状态 0:提交成功,1:正在处理,2:处理成功,3:处理失败,4:用户取消
     * @param string $reason 如果是失败或取消，那么输入原因
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:设置的状态不存在
     * 2:没有找到对应的提现记录
     * 3:收入余额冻结扣除失败
     * 4:学币解冻失败
     * 5:提现记录状态改变失败
     * 6:提现已完成或已经关闭
     */
    public function setWithdrawStatus($id, $uid, $status, $reason = '') {
        $ZyWithdraw = D('ZyWithdraw');
        //防止状态码溢出
        if (!$ZyWithdraw->statusExists($status))
            return 1;

        //查找数据记录，如果$uid为false，则不检查uid；
        //ps:uid可以防止前台用户操作非自己的提现记录
        $map['id'] = $id;
        if (false !== $uid)
            $map['uid'] = $uid;
        $rs = $ZyWithdraw->where($map)->find();
        if (!$rs)
            return 2;

        //当状态小于2时才能进行操作
        if ($rs['status'] < 2) {
            if($rs['wtype'] == 1){
                //学币及流水操作流程
                $zyLearnc = D('ZyLearnc');
                //提现成功则扣除冻结
                if ($status == 2) {
                    $func = 'rmfreeze';
                } elseif ($status == 3 || $status == 4) {
                    //如果是失败或用户自动取消，则将冻结转为余额
                    $func = 'unfreeze';
                }
                //执行对应的操作
                if (isset($func) && !$zyLearnc->$func($rs['uid'], $rs['wnum'])) {
                    return $status == 2 ? 3 : 4;
                }
                //保存记录状态
                $result = $ZyWithdraw->save(array(
                    'id' => $id,
                    'status' => $status,
                    'reason' => ( $status == 2 ? '' : $reason ),
                    'rtime' => ( $status < 2 ? 0 : time() ),
                ));
                if (false === $result)
                    return 5;
                //添加流水记录
                if ($status == 2) {
                    $type = 4;
                    $note = '提现成功';
                } elseif ($status == 3) {
                    $type = 3;
                    $note = '提现失败';
                } elseif ($status == 4) {
                    $type = 3;
                    $note = '用户取消提现';
                }
                if (isset($type)) {
                    $zyLearnc->addFlow(
                        $rs['uid'], $type, $rs['wnum'], $note, $rs['id'], 'zy_withdraw'
                    );
                }
                return true;
            }else if($rs['wtype'] == 2){
                //学币及流水操作流程
                $zySplit = D('ZySplit');
                //提现成功则扣除冻结
                if ($status == 2) {
                    $func = 'rmfreeze';
                } elseif ($status == 3 || $status == 4) {
                    //如果是失败或用户自动取消，则将冻结转为余额
                    $func = 'unfreeze';
                }
                //执行对应的操作
                if (isset($func) && !$zySplit->$func($rs['uid'], $rs['wnum'])) {
                    return $status == 2 ? 3 : 4;
                }
                //保存记录状态
                $result = $ZyWithdraw->save(array(
                    'id' => $id,
                    'status' => $status,
                    'reason' => ( $status == 2 ? '' : $reason ),
                    'rtime' => ( $status < 2 ? 0 : time() ),
                ));
                if (false === $result)
                    return 5;
                //添加流水记录
                if ($status == 2) {
                    $type = 4;
                    $note = '提现成功';
                } elseif ($status == 3) {
                    $type = 3;
                    $note = '提现失败';
                } elseif ($status == 4) {
                    $type = 3;
                    $note = '用户取消提现';
                }
                if (isset($type)) {
                    $zySplit->addFlow(
                        $rs['uid'], $type, $rs['wnum'], $note, $rs['id'], 'zy_withdraw'
                    );
                }
                return true;
            }
        } else {
            return 6;
        }
    }

    /**
     * 购买单个课程
     * @param $uid
     * @param $video_id
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:可以直接观看，可能的原因是用户自己发布的，用户为管理员，价格为0，已经购买过了
     * 2:找不到课程
     * 3:余额扣除失败，可能原因是余额不足
     * 4:购买记录/订单，添加失败
     */
    public function buyVideo($uid, $video_id) {
        if ($this->checkVideoAccess($uid, $video_id)) {
            return 1;
        }
        $time = time();
        //取得课程
        $video = D('ZyVideo')->where(array(
            'id' => $video_id,
            'is_del' => 0,
            'is_activity' => 1,
            'listingtime' => array('lt', $time),
        ))->find();
        //找不到课程
        if (!$video)
            return 2;
        //取得价格
        $prices = getPrice($video, $uid, false, true);

        $learnc = D('ZyLearnc');
        if (!$learnc->consume($uid, $prices['price'])) {
            return 3; //余额扣除失败，可能原因是余额不足
        }
        //订单数据
        $order = D('ZyOrder');
        $data = array(
            'uid' => $uid,
            'video_id' => $video['id'],
            'old_price' => $prices['oriPrice'],
            'discount' => $prices['discount'],
            'discount_type' => $prices['dis_type'],
            'price' => $prices['price'],
            'order_album_id' => 0,
            'learn_status' => 0,
            'ctime' => $time,
            'pay_status' => 0,
        );
        $id = $order->add($data);
        if (!$id)
            return 4; //购买记录/订单，添加失败

        //更新订单数量
        D('ZyVideo')->where(array('id' => $video['id']))->save(
            array('video_order_count' => $order->where(
                array('video_id' => $video['id']))->count()));

        //添加流水记录
        $learnc->addFlow($uid, 0, $prices['price'], '购买课程<'.$video['video_title'].'>', $id, 'zy_order');
        return true;
    }

    /**
     * 购买一个班级
     */
    public function buyAlbum($uid, $album_id, $total_price) {
        //获取$uid的学币数量
        if (!D('ZyLearnc',"classroom")->isSufficient($uid, $total_price, 'balance')) {
            return array('status' => '3', 'info' => '可支配的学币不足');
            exit;
        }
        if (!D("ZyLearnc","classroom")->consume($uid, $total_price)) {
            return array('status' => '2', 'info' => '合并付款失败，请稍后再试');
            exit;
        }
        $cuid = M("album")->where('id=' . $album_id)->getField('uid');
        $data['uid'] = $uid;
        $data['cuid'] = $cuid;
        $data['album_id'] = $album_id;
        $data['price'] = $total_price;
        $data['ctime'] = time();
        $data['is_del'] = 0;
        $result = M("zy_order_album")->data($data)->add();
        if ($result) {
            return array('status' => '1', 'info' => '添加购买班级记录成功！', 'rid' => $result);
        }else{
            return array('status' => '0', 'info' => '付款失败，请稍后再试');
        }
    }

    /**
     * @param $id 点播、套餐、直播 id
     * @param $type 0点播1套餐2直播3线下课
     * @param $mid 用户id
     * @return bool|string 分享的url
     */
    public function addCourseOfShare($id,$type,$mid,$code){
        if($type == 1){
            $map ['id']          = $id;
            $map ['status'] = 1;
            $video_info = M('album')->where($map)->field('id,uid,mhm_id')->find();

            $true = $mid && !is_admin($mid) && ($mid != $video_info['uid']) && ($mid != is_school($video_info['mhm_id']));
        } else if($type == 3){
            $map ['id']     = $id;
            $map ['is_activity'] = 1;
            $map ['is_del'] = 0;
            $video_info = D('ZyLineClass')->getLineclassById($id);

            $true = $mid && !is_admin($mid) && ($mid != $video_info['course_uid']) && ($mid != is_teacher($video_info['teacher_id'])) && ($mid != is_school($video_info['mhm_id']));
        } else {
            $type == 0 ? $vtype = 1 : '';
            $map ['id']          = $id;
            $map ['is_activity'] = 1;
            $map ['is_del']      = 0;
            $map ['type']        = $vtype;

            $video_info = D('ZyVideo')->where ($map)->field('id,uid,teacher_id,mhm_id')->find();

            $true = $mid && !is_admin($mid) && ($mid != $video_info['uid']) && ($mid != is_teacher($video_info['teacher_id'])) && ($mid != is_school($video_info['mhm_id']));
        }

        if($true){
            if($code){
                $id = $code;
            }
            $share_data['type'] = $type;
            $share_data['vid']  = $id;
            $share_data['uid']  = $mid;
            $share_str = urlencode(sunjiami(json_encode($share_data),'link'));

            if($type == 0) {
                $share_url = U('classroom/Video/view', array('id' => $id, 'code' => $share_str));
            } else if($type == 1){
                $share_url = U('classroom/Album/view', array('id' => $id, 'code' => $share_str));
            } else if($type == 2){
                $share_url = U('live/Index/view', array('id' => $id, 'code' => $share_str));
            } else if($type == 3){
                $share_url = U('classroom/LineClass/view', array('id' => $id, 'code' => $share_str));
            }
        } else {
            if($code){
                $id = $code;
            }
            if($type == 0) {
                $share_url = U('classroom/Video/view', array('id' => $id));
            } else if($type == 1){
                $share_url = U('classroom/Album/view', array('id' => $id));
            } else if($type == 2){
                $share_url = U('live/Index/view', array('id' => $id));
            } else if($type == 3){
                $share_url = U('classroom/LineClass/view', array('id' => $id));
            }
        }

        return $share_url;
    }

    public function addCourseOfUserShare($code,$mid){
        $jiemi_code = json_decode(sunjiemi(urldecode($code),'link'));

        $data['uid']        = $jiemi_code->uid;
        $data['video_id']   = $jiemi_code->vid;
        $data['type']       = $jiemi_code->type;
        $data['ctime']      = time();
        $data['tmp_id']     = $code;
        $data['share_url']  = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        $share_id = M('zy_video_share')->where(array('uid'=>$jiemi_code->uid,'video_id'=>$jiemi_code->vid,'tmp_id'=>$code))->getField('id');
        if($share_id){
            M('zy_video_share')->where('id='.$share_id)->save($data);
        }else{
            M('zy_video_share')->add($data);
        }

        $video_share = M('zy_video_share')->where(array('tmp_id' => $code))->field('uid,video_id,type,share_url')->find();

        if($mid && $video_share && ($video_share['uid'] != $mid)) {
            $data['type']       = $map['type'] = $video_share['type'];
            $data['uid']        = $map['uid'] = $video_share['uid'];
            $data['use_uid']    = $mid;
            $data['video_id']   = $map['video_id'] = $video_share['video_id'];
            $data['ctime']      = time();
            $data['share_url'] = $video_share['share_url'];
            $data['tmp_id'] = $map['tmp_id'] = session_id();

            $result = M('zy_video_share_user')->where($map)->find();
            if ($result) {
                $res = M('zy_video_share_user')->where($map)->save($data);
            } else {
                $res = M('zy_video_share_user')->add($data);
            }
        }

        return $res ? : false;
    }

    /**
     * 在线直接购买单个课程
     * @param $uid
     * @param $video_id
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:可以直接观看，可能的原因是用户自己发布的，用户为管理员，价格为0，已经购买过了
     * 2:找不到课程
     * 4:购买记录/订单，添加失败
     */
    public function buyOnlineVideo($uid, $video_id,$ext_data = array(),$pay_video_mount_school) {
        if ($this->checkVideoAccess($uid, $video_id)) {
            return 1;
        }
        $time = time();
        //取得课程
        $video = M('zy_video')->where(array(
            'id' => $video_id,
            'is_del' => 0,
            'is_activity' => ['in','1,5,6,7'],
            'type'        => 1,
            'listingtime' => array('lt', $time),
        ))->field("id,uid,video_title,mhm_id,teacher_id,v_price,t_price,vip_level,
            endtime,starttime,limit_discount,term")->find();
        //找不到课程
        if (!$video){
            return 2;
        }
        //取得分成比例
        $proportion = $this->getAllProportion('course',$video['mhm_id']);

        //平台与机构分成与分享者分成比例为空
        if($proportion == 1 || $proportion == 2 || $proportion == 3 || $proportion == 4){
            return 5;
        }

        //课程不属于机构
        if(!$video['mhm_id']) {
            return 6;
        }

        //课程属于机构
        $school_info = model('School')->where(array('id'=>$video['mhm_id']))->field('uid')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');

        //机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        //查询教师用户uid
        $teacher_uid = M('zy_teacher')->where(array('id'=>$video['teacher_id']))->getField('uid');
        $teacher_uid = M('user')->where(array('uid'=>$teacher_uid))->getField('uid');

        if(!$teacher_uid){
            return 8;
        }

        if($pay_video_mount_school){
            $mount_school_info = model('School')->where(array('id'=>$pay_video_mount_school))->field('uid')->find();
            $mount_school_uid = M('user')->where(array('uid'=>$mount_school_info['uid']))->getField('uid');

            //挂载直播课程所绑定的机构管理员不存在
            if(!$mount_school_uid){
                return 9;
            }
        }

        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');
        ////购买用户的相应机构管理员不存在
        //if(!$oschool_uid){
        //    return 11;
        //}


        //取得价格
        $prices = getPrice($video, $uid, false, true);
        $prices['price'] = floatval($prices['price']);

        //生成状态为未支付的订单数据
        $order = M('zy_order_course');
        $data = array(
            'uid'           => $uid,
            'muid'          => $teacher_uid,
            'video_id'      => $video['id'],
            'old_price'     => $prices['price'],//10
            'discount'      => $ext_data['price'] ? round($prices['price'] - $ext_data['price'],2) : 0,
            'discount_type' => in_array(intval($ext_data['dis_type']),[0,1,2]) ? intval($ext_data['dis_type']) : 0,
            'price'         => $ext_data['price'] ? : $prices['price'],
            'order_album_id'=> 0,
            'learn_status'  => 0,
            'ctime'         => $time,
            'order_type'    => 0,
            'is_del'        => 0,
            'pay_status'    => 1,
            'term'          => $video['term'],
            'time_limit'    => $video['term'] ? time() + (86400 * floatval($video['term'])) : 0,
            'mhm_id'        => $video['mhm_id'],//课程机构id
            'order_mhm_id'  => intval($oschool_id),//购买的用户机构id
            'coupon_id'     => isset($ext_data['coupon_id']) ? intval($ext_data['coupon_id']) : 0,
        );

        $map['uid']         = $uid;
        $map['video_id']    = $video['id'];
        $order_id = $order->where($map)->getField('id');
        if($order_id){
            $id = $order->where($map)->save($data);
            if($id){
                $id = $order_id;
            }else{
                $id = 0;
            }
        }else{
            $id = $order->add($data);
        }

        //购买记录/订单，添加失败
        if (!$id){
            return 4;
        }

        $video_spilt_status = $this->addVideoSplit($id,$uid,$video,$ext_data['price'] ? : $prices['price'],$pay_video_mount_school);

        if(!$video_spilt_status){
            //创建订单明细流水失败 并删除此订单
            M('zy_order_course')->where(array('id' => $id))->delete();
            return $video_spilt_status;
        }else{
            $data['coupon_id'] && M('coupon_user')->where(['id'=>$data['coupon_id']])->setField('status',2);
            return true;
        }
    }

    /**
     * 取得平台/机构配置的所有分成比例
     * @param $type 比例类型
     * @param $school_id 机构id
     * @return array 所有分成比例
     */
    private function getAllProportion($type,$school_id){
        $school_proportion = M('school_divideinto')->where(['mhm_id'=>$school_id,"{$type}_status"=>1])->field('course_platform_and_school,
        course_school_and_mschool,course_school_and_teacher,course_teacher_and_share,live_platform_and_school,
        live_school_and_mschool,live_school_and_teacher,live_teacher_and_share,album_platform_and_school,
        album_school_and_share,course_line_platform_and_school,course_line_school_and_teacher,course_line_teacher_and_share
        ')->find();
        if($school_proportion){
            $old_proportion = $school_proportion;
        } else {
            if($type == 'course_line'){
                $otype = 'CourseLine';
                $old_proportion = model('Xdata')->get("admin_Config:divideInto".ucwords($otype)."Config");
            } else{
                $old_proportion = model('Xdata')->get("admin_Config:divideInto".ucwords($type)."Config");
            }
        }

        //平台与机构分成比例
        $platform_and_school = array_filter(explode(':', $old_proportion["{$type}_platform_and_school"]));
        if (empty($platform_and_school)) {
            return 1;
        }

        if($type == 'course' || $type == 'live'){
            //机构与销课机构
            $school_and_mschool = array_filter(explode(':', $old_proportion["{$type}_school_and_mschool"]));
            if (empty($school_and_mschool)) {
                return 2;
            }
        }


        if($type == 'album') {
            //机构与分享者 班级涉及到多个讲师 以及点播、直播 不参与机构与讲师的分成
            $school_and_share = array_filter(explode(':', $old_proportion["{$type}_school_and_share"]));
            if (empty($school_and_share)) {
                return 4;
            }
        }else{
            //机构与讲师
            $school_and_teacher = array_filter(explode(':', $old_proportion["{$type}_school_and_teacher"]));
            if (empty($school_and_teacher)) {
                return 3;
            }

            //讲师与分享者
            $teacher_and_share = array_filter(explode(':', $old_proportion["{$type}_teacher_and_share"]));
            if (empty($teacher_and_share)) {
                return 4;
            }
        }

        //platform_and_school
        $proportion['pas_platform'] = floatval($platform_and_school[0]/100);//平台与机构-平台
        $proportion['pas_school'] = floatval($platform_and_school[1]/100);//平台与机构-机构
        if($type == 'course' || $type == 'live') {
            //机构与销课机构
            $proportion['sam_school'] = floatval($school_and_mschool[0]/100);//机构与销课机构-机构
            $proportion['sam_mschool'] = floatval($school_and_mschool[1]/100);//机构与销课机构-销课机构
        }
        if($type == 'album') {
            //机构与分享者 班级涉及到多个讲师 以及点播、直播 不参与机构与讲师的分成
            $proportion['sas_school'] = floatval($school_and_share[0]/100);//机构与分享者-机构
            $proportion['sas_share'] = floatval($school_and_share[1]/100);//机构与分享者-分享者
        }else {
            //机构与讲师
            $proportion['sat_school'] = floatval($school_and_teacher[0]/100);//机构与讲师-机构
            $proportion['sat_teacher'] = floatval($school_and_teacher[1]/100);//机构与讲师-讲师

            //讲师与分享者
            $proportion['ats_teacher'] = floatval($teacher_and_share[0]/100);//讲师与分享者-讲师
            $proportion['ats_share'] = floatval($teacher_and_share[1]/100);//讲师与分享者-分享者
        }

        return $proportion;
    }

    /**
     * 在线直接购买单个班级
     * @param $uid
     * @param $album_id
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:可以直接观看，可能的原因是用户自己发布的，用户为管理员，价格为0，已经购买过了
     * 2:找不到班级
     * 3:班级下没有课程
     * 4:购买记录/订单，添加失败
     */
    public function buyOnlineAlbum($uid, $album_id,$ext_data = array()) {
        $album = D("Album")->getAlbumOneInfoById($album_id,'id,price,mhm_id,album_title');
        //找不到班级
        if (!$album){
            return 2;
        }

        //取得分成比例
        $proportion = $this->getAllProportion('album',$album['mhm_id']);

        //平台与机构分成与分享者分成比例为空
        if($proportion == 1 || $proportion == 4){
            return 5;
        }

        //班级不属于机构
        if(!$album['mhm_id']) {
            return 6;
        }

        //班级属于机构 先平台和机构分成 再机构与教师分成
        $school_info = model('School')->where(array('id'=>$album['mhm_id']))->field('uid')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');
        //班级所绑定的机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        $albumId        = intval($album['id']);

        //获取班级下所有的课程ID
        $video_ids        = trim(D("Album",'classroom')->getVideoId($albumId), ',');
        $v_map['id']      = array('in', array($video_ids));
        $v_map["is_del"]  = 0;
        $album_info       = M("zy_video")->where($v_map)->field("id,video_title,mhm_id,teacher_id,
                              v_price,t_price,vip_level,endtime,starttime,limit_discount")
            ->select();
        //班级下所有的课程数量 班级下没有课程
        $album['video_count'] = count($album_info);
//        if($album['video_count'] <= 0){
//            return 3;
//        }

        //判断班级中有没有正在退款的课程
        $is_refund = M('zy_order_course')->where(array('uid' =>$uid,'video_id'=>['in',[$video_ids]],'pay_status'=>4,'is_del'=>0))->getField('id');
        if($is_refund){
            return 8;
        }
        $is_refund = M('zy_order_live')->where(array('uid'=>$uid,'live_id' =>['in',[$video_ids]],'pay_status'=>4,'is_del'=>0))->getField('id');
        if($is_refund){
            return 8;
        }

        $illegal_count  = 0;
        $total_price    = 0;

        //通过课程取得专辑价格
        $video_id   = '';
        $tuid       = '';
        $oPrice = 0.00;
//        foreach ($album_info as $key => $video) {
//            $oPrice += $video['t_price'];
//            if($video['mhm_id'] != $album['mhm_id']){
//                $video_id       .= $video['id'].',';//课程和班级的机构id不一致
//            }
//            $album_info[$key]['price'] = getPrice($video, $this->mid, true, true);
//            //价格为0的 限时免费的  不加入购物记录
//            if($album_info[$key]['price']['oriPrice'] == 0){
//                unset($album_info[$key]);
//                continue;
//            }
//            $album_info[$key]['is_buy'] = D("ZyOrder",'classroom')->isBuyVideo($this->mid, $video['id']);
//            $total_price                += ($album_info[$key]['is_buy'] || $video['uid'] == $this->mid) ? 0 : round($album_info[$key]['price']['price'], 2);
//            //判断是否有课程过期
//            if ($video['uctime'] < time()) {
//                $illegal_count  += 1;
//                $video_gid       = $video['id'];//过期id
//            }
//            //判断班级下没有相关讲师用户不存在的课程id
//            $teacher_info = M('zy_teacher')->where(array('id'=>$video['teacher_id']))->field('uid,name')->find();
//            $teacher_uinfo = M('user')->where(array('uid'=>$teacher_info['uid']))->field('uid,uname')->find();
//            if(!$teacher_uinfo['uid']){
//                $tuid       .= $video['id'].',';//班级下没有相关讲师用户不存在的课程id
//            }
//            $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
//            //拼接班级下所有讲师信息以便线下分成
//            $album_info['tuid_str'] .= $teacher_uinfo['uid'].",";
//        }

//        //班级中包含有 没有相关讲师用户不存在的课程id
//        if ($tuid) {
//            return 8;
//        }

        //班级中包含有与班级的机构不一致的课程 $video_id为返回的课程id
        if ($video_id) {
            return 9;
        }

        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');

        //购买用户的相应机构管理员不存在
        if(!$oschool_uid){
            return 11;
        }
        //无过期非法信息则生成状态为未支付的订单数据
        $order = D('ZyOrderAlbum');
        $data = array(
            'uid'           => $uid,
            'album_id'      => $albumId,
            'old_price'     => $album['price'],
            'price'         => $ext_data['price'] ? : $album['price'],
            'learn_status'  => 0,
            'ctime'         => time(),
            'order_type'    => 1,
            'is_del'        => 0,
            'pay_status'    => 1,
            'mhm_id'        => $album['mhm_id'],//课程机构id
            'order_mhm_id'  => intval($oschool_id),//购买的用户机构id
            'discount'      => $ext_data['price'] ? round($album['price'] - $ext_data['price'],2) : 0,
            'discount_type' => in_array(intval($ext_data['dis_type']),[0,1,2]) ? intval($ext_data['dis_type']) : 0,
            'coupon_id'     => isset($ext_data['coupon_id']) ? intval($ext_data['coupon_id']) : 0,
        );
        $map['uid']      = $uid;
        $map['album_id'] = $albumId;
        $order_id = $order->where($map)->getField('id');
        if($order_id){
            $id = $order->where($map)->save($data);
            if($id){
                $id = $order_id;
            }else{
                $id = 0;
            }
        }else{
            $id = $order->add($data);
        }

        //购买记录/订单，添加失败
        if (!$id){
            return 4;
        }
        if($ext_data['price']){
            $album['price'] = $ext_data['price'];
        }
        $video_spilt_status = $this->addAlbumSplit($id,$uid,$album);

        if(!$video_spilt_status){
            //创建订单明细流水失败 并删除此订单
            M('ZyOrderAlbum')->where(array('id' => $id))->delete();
            return $video_spilt_status;
        }else{
            $data['coupon_id'] && M('coupon_user')->where(['id'=>$data['coupon_id']])->setField('status',2);
            return true;
        }
    }

    /**
     * 在线直接购买单个直播课堂
     * @param $uid
     * @param $album_id
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:可以直接观看，可能的原因是用户自己发布的，用户为管理员，价格为0，已经购买过了
     * 2:找不到直播课堂
     * 3:直播课程下所有的课程数量 直播课程下没有课程
     * 4:购买记录/订单，添加失败
     */
    public function buyOnlineLive($uid, $live_id,$ext_data,$pay_video_mount_school) {
        //取得直播课程
        $live_info = D('ZyVideo','classroom')->where(array(
            'id'          => $live_id,
            'is_del'      => 0,
            'is_activity' => 1,
            'type'        => 2,
            'listingtime' => array('lt', time()),
            'uctime' => array('gt', time()),
        ))->field("id,video_title,mhm_id,teacher_id,t_price,v_price,
            listingtime,uctime,live_type")->find();

        if ($this->checkVideoAccess($uid, $live_id)) {
            return 1;
        }

        //找不到直播课程
        if (!$live_info){
            return 2;
        }
        $live_id = $live_info['id'];

        // //取得分成比例
        // $proportion = $this->getAllProportion('live',$live_info['mhm_id'])
        // //平台与机构分成与分享者分成比例为空
        // if($proportion == 1 || $proportion == 2 || $proportion == 3 || $proportion == 4){
        //     return 5;
        // }

        //直播课程不属于机构
        if(!$live_info['mhm_id']){
            return 6;
        }

        $school_info = model('School')->where(array('id'=>$live_info['mhm_id']))->field('uid')->find();

        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');
        //直播课程所绑定的机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        //查询教师用户uid
        $teacher_uid = M('zy_teacher')->where(array('id'=>$live_info['teacher_id']))->getField('uid');
        $teacher_uid = M('user')->where(array('uid'=>$teacher_uid))->getField('uid');
        if(!$teacher_uid){
            return 8;
        }

        if($pay_video_mount_school){
            $mount_school_info = model('School')->where(array('id'=>$pay_video_mount_school))->field('uid')->find();
            $mount_school_uid = M('user')->where(array('uid'=>$mount_school_info['uid']))->getField('uid');

            //挂载直播课程所绑定的机构管理员不存在
            if(!$mount_school_uid){
                return 9;
            }
        }

        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');

        //购买用户的相应机构管理员不存在
//        if(!$oschool_uid){
//            return 11;
//        }

        //取得价格
        $prices          = getPrice($live_info, $uid, false, true);
        $prices['price'] = floatval($prices['price']);
        //无过期非法信息则生成状态为未支付的订单数据
        $order = D('ZyOrderLive','classroom');
        $data = array(
            'uid'           => $uid,
            'live_id'      => $live_id,
            'old_price'     => $prices['price'],
            'discount'      => $ext_data['price'] ? round($prices['price'] - $ext_data['price'],2) : 0,
            'discount_type' => in_array(intval($ext_data['dis_type']),[0,1,2]) ? intval($ext_data['dis_type']) : 0,
            'price'         => $ext_data['price'] ? : $prices['price'],
            'order_album_id'=> 0,
            'learn_status'  => 0,
            'ctime'         => time(),
            //'order_type'    => 2,
            'is_del'        => 0,
            'pay_status'    => 1,
            'mhm_id'        => $live_info['mhm_id'],//课程机构id
            'order_mhm_id'  => intval($oschool_id),//购买的用户机构id
            'coupon_id'     => isset($ext_data['coupon_id']) ? intval($ext_data['coupon_id']) : 0,
        );

        $map['uid']        = $uid;
        $map['live_id']    = $live_id;

        $order_id = $order->where($map)->getField('id');
        if($order_id){
            $id = $order->where($map)->save($data);
            if($id){
                $id = $order_id;
            }else{
                $id = 0;
            }
        }else{
            $id = $order->add($data);
        }
        //购买记录/订单，添加失败
        if (!$id){
            return 4;
        }

        $video_spilt_status = $this->addLiveSplit($id,$uid,$live_info,$ext_data['price'] ? : $prices,$pay_video_mount_school);

        if(!$video_spilt_status){
            //创建订单明细流水失败 并删除此订单
            M('zy_order_live')->where(array('id' => $id))->delete();
            return $video_spilt_status;
        }else{
            $data['coupon_id'] && M('coupon_user')->where(['id'=>$data['coupon_id']])->setField('status',2);
            return true;
        }
    }

    /**
     * 直播课堂 分成明细生成
     * @param $order_id 订单id
     * @param $uid 购买用户id
     * @param $video 购买课程详细
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 5:平台与机构分成自营与分享者分成比例为空
     * 6:直播课程不属于机构
     * 7:直播课程所绑定的机构管理员不存在
     * 8:直播课堂中包含有 没有相关讲师用户不存在的课程id
     * 9:机构数据里的 机构与教师分成不存在
     * 10:创建订单明细流水失败 并删除此订单
     */
    public function addLiveSplit($order_id,$uid,$live_info = array(),$prices,$pay_video_mount_school){
        //找不到直播课程
        if (!$live_info){
            return 2;
        }
        $live_id = $live_info['id'];

        //取得分成比例
        $proportion = $this->getAllProportion('live',$live_info['mhm_id']);

        //平台与机构分成与分享者分成比例为空
        if($proportion == 1 || $proportion == 2 || $proportion == 3 || $proportion == 4){
            return 5;
        }

        //直播课程不属于机构
        if(!$live_info['mhm_id']){
            return 6;
        }

        $school_info = model('School')->where(array('id'=>$live_info['mhm_id']))->field('uid')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');
        //直播课程所绑定的机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        //查询教师用户uid
        $teacher_uid = M('zy_teacher')->where(array('id'=>$live_info['teacher_id']))->getField('uid');
        $teacher_uid = M('user')->where(array('uid'=>$teacher_uid))->getField('uid');
        if(!$teacher_uid){
            return 8;
        }

        if($pay_video_mount_school){
            $mount_school_info = model('School')->where(array('id'=>$pay_video_mount_school))->field('uid')->find();
            $mount_school_uid = M('user')->where(array('uid'=>$mount_school_info['uid']))->getField('uid');

            //挂载直播课程所绑定的机构管理员不存在
            if(!$mount_school_uid){
                return 9;
            }
        }

        //购买用户相应机构
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');

        //生成订单分成详细 取得价格
        $prices = floatval($prices);
        $data['status']       = 0;
        $data['order_id']     = $order_id;
        $data['uid']          = intval($uid);//购买用户ID
        $data['mhm_id']       = intval($live_info['mhm_id']);//课程所属机构id
        $data['order_mhm_id'] = intval($oschool_uid);//购买的用户机构id
        $data['lid']          = intval($live_info['id']);//所购买直播课程的id
        $data['note']         = t("购买直播课堂：{$live_info['video_title']}。");
        $data['sum']          = $prices;//购买金额

        //平台手续费
        $data['pid']          = 1;//获得平台分成的管理员用户id
        $data['platform_sum'] = round($prices * $proportion['pas_platform'],2);//平台手续费

        //扣掉平台手续费后的总金额
        $school_num = round($prices * $proportion['pas_school'],2);//机构挂载机构讲师及分享者的总金额

        //如果有销售机构 销售机构拿去自己的一部分钱
        if($mount_school_uid){
            $data['mount_school_id'] = $mount_school_uid;
            $data['mount_school_sum'] = round($school_num * $proportion['sam_mschool'],2);

            //扣掉销售机构后的总金额
            $sam_school = round($school_num * $proportion['sam_school'],2);//机构挂载机构讲师及分享者的总金额
        }else{
            $sam_school = $school_num;
        }

        //课程机构
        $data['sid'] = $school_uid;
        $data['school_sum'] = round($sam_school * $proportion['sat_school'],2);

        //扣掉课程机构后讲师与分享者的总金额
        $sat_teacher_num = round($sam_school * $proportion['sat_teacher'],2);

        //取得分享者信息
        $vsu_map['tmp_id']   = session_id();
        $vsu_map['type']     = 2;
        $vsu_map['video_id'] = $live_id;
        $share_uid = M('zy_video_share_user')->where($vsu_map)->getField('uid');

        //如果讲师没有分成，即讲师设置的比例为0
        if($proportion['ats_teacher'] == 0){
            //如果分享者有且不为平台管理员、课程所属机构管理员和教师 挂载机构管理员   //购买用户相应机构管理员、&& $share_uid != $oschool_uid
            //则参与分享者分成(从课程所属机构方扣除) 否则直接加到课程所属机构管理员账户
            if ($share_uid && $share_uid != 1 && $share_uid != $school_uid && $share_uid != $teacher_uid && $share_uid != $mount_school_uid) {
                //分享者
                $data['share_id'] = $share_uid;//获得平台分享者用户id//hxb_
                $data['share_sum'] = $sat_teacher_num;//平台分享者的金额
            } else {
                //课程机构 没有分享者机构得所有
                $data['sid'] = $school_uid;
                $data['school_sum'] = $sam_school;
            }
        } else {
            //如果分享者有且不为平台管理员、课程所属机构管理员和教师 挂载机构管理员   //购买用户相应机构管理员、&& $share_uid != $oschool_uid
            //则参与分享者分成(从课程所属机构方扣除) 否则直接加到课程所属机构管理员账户
            if ($share_uid && $share_uid != 1 && $share_uid != $school_uid && $share_uid != $teacher_uid && $share_uid != $mount_school_uid) {
                //讲师
                $data['st_id'] = $teacher_uid;//hxb
                $data['school_teacher_sum'] = round($sat_teacher_num * $proportion['ats_teacher'],2);
                //分享者
                $data['share_id'] = $share_uid;//获得平台分享者用户id
                $data['share_sum'] = round($sat_teacher_num * $proportion['ats_share'], 2);//平台分享者的金额
            }else{
                //讲师
                $data['st_id'] = $teacher_uid;
                $data['school_teacher_sum'] = $sat_teacher_num;
            }
        }



        //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $school_uid) {
            $school_sum_add = $data['school_sum'];////机构获得的金额
            $platform_sum_new = round($data['platform_sum'] + $school_sum_add, 2);
            $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$school_sum_add}元之和{$platform_sum_new}元；";
            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['sid']);        //课程所属机构分成的管理员用户id
            unset($data['school_sum']); //课程所属机构获得的金额
        }

        //如果课程机构管理员和课程所属讲师相同 销毁课程所属讲师的金额 直接加到课程机构管理员账户上
        if($school_uid == $teacher_uid){
            $teacher_sum_add = $data['school_teacher_sum'];//机构获得的金额
            $school_sum_new = round($data['school_sum'] + $teacher_sum_add, 2);
            $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['school_sum']}元
                                     加上{$teacher_sum_add}元之和{$school_sum_new}元；";
            $data['school_sum'] = $school_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['st_id']);        //课程所属讲师用户id
            unset($data['school_teacher_sum']); //课程所属讲师获得的金额
        }

        $data['ctime']              = time();
        $map['uid'] = intval($uid);//购买用户ID
        $map['lid'] = intval($live_info['id']);//所购买课程的id(包括点播、直播、班级)
        $split_video = M('zy_split_live')->where($map)->getField('id');

        if ($split_video) {
            $res = M('zy_split_live')->where($map)->save($data);
        } else {
            $res = M('zy_split_live')->add($data);
        }
        if ($res){
            return true;
        } else {
            //创建订单明细流水失败 并删除此订单
            M('zy_order_live')->where(array('id' => $order_id))->delete();
            return 12;
        }
        exit;//新版结束
    }

    /**
     * 点播 分成明细生成
     * @param $order_id 订单id
     * @param $uid 购买用户id
     * @param $video 购买课程详细
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 5:平台与机构分成比例不存在
     * 6:课程不属于机构
     * 7:机构管理员不存在
     * 8:课程下讲师不存在
     * 9:机构数据里的 机构与教师分成比例不存在
     * 10机构数据里的 机构与挂载机构分成不存在
     * 11购买用户的相应机构管理员不存在
     * 12:课程订单有效期不存在
     * 13:创建订单明细流水失败
     */
    public function addVideoSplit($order_id,$uid,$video = array(),$prices,$pay_video_mount_school){
        //找不到课程
        if (!$video){
            return 2;
        }
        //取得分成比例
        $proportion = $this->getAllProportion('course',$video['mhm_id']);

        //平台与机构分成与分享者分成比例为空
        if($proportion == 1 || $proportion == 2 || $proportion == 3 || $proportion == 4){
            return 5;
        }

        //课程不属于机构
        if(!$video['mhm_id']) {
            return 6;
        }

        //课程属于机构
        $school_info = model('School')->where(array('id'=>$video['mhm_id']))->field('uid')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');

        //机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        //查询教师用户uid
        $teacher_uid = M('zy_teacher')->where(array('id'=>$video['teacher_id']))->getField('uid');
        $teacher_uid = M('user')->where(array('uid'=>$teacher_uid))->getField('uid');
        //课程下讲师不存在
        if(!$teacher_uid){
            return 8;
        }

        if($pay_video_mount_school){
            $mount_school_info = model('School')->where(array('id'=>$pay_video_mount_school))->field('uid')->find();
            $mount_school_uid = M('user')->where(array('uid'=>$mount_school_info['uid']))->getField('uid');

            //挂载直播课程所绑定的机构管理员不存在
            if(!$mount_school_uid){
                return 9;
            }
        }

        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');

//        //课程订单有效期不存在
//        if(!$video['term']){
//            return 12;
//        }
        //生成订单分成详细 取得价格
        $prices = floatval($prices);
        $data['status']       = 0;
        $data['order_id']     = $order_id;
        $data['uid']          = intval($uid);//购买用户ID
        $data['mhm_id']       = intval($video['mhm_id']);//课程所属机构id
        $data['order_mhm_id'] = intval($oschool_id);//购买用户的机构id
        $data['vid']          = intval($video['id']);//所购买点播课程的id
        $data['note']         = t("购买课程：{$video['video_title']}。");
        $data['sum']          = $prices;//购买金额

        //平台手续费
        $data['pid']          = 1;//获得平台分成的管理员用户id
        $data['platform_sum'] = round($prices * $proportion['pas_platform'],2);//平台手续费

        //扣掉平台手续费后的总金额
        $school_num = round($prices * $proportion['pas_school'],2);//机构挂载机构讲师及分享者的总金额

        //如果有销售机构 销售机构拿去自己的一部分钱
        if($mount_school_uid){
            $data['mount_school_id'] = $mount_school_uid;
            $data['mount_school_sum'] = round($school_num * $proportion['sam_mschool'],2);

            //扣掉销售机构后的总金额
            $sam_school = round($school_num * $proportion['sam_school'],2);//机构挂载机构讲师及分享者的总金额
        }else{
            $sam_school = $school_num;
        }

        //课程机构
        $data['sid'] = $school_uid;
        $data['school_sum'] = round($sam_school * $proportion['sat_school'],2);

        //扣掉课程机构后讲师与分享者的总金额
        $sat_teacher_num = round($sam_school * $proportion['sat_teacher'],2);

        $vsu_map['tmp_id']   = session_id();
        $vsu_map['type']     = 0;
        $vsu_map['video_id'] = $video['id'];
        $share_uid = M('zy_video_share_user')->where($vsu_map)->getField('uid');

        //如果讲师没有分成，即讲师设置的比例为0
        if($proportion['ats_teacher'] == 0){
            //如果分享者有且不为平台管理员、课程所属机构管理员和教师 挂载机构管理员   //购买用户相应机构管理员、&& $share_uid != $oschool_uid
            //则参与分享者分成(从课程所属机构方扣除) 否则直接加到课程所属机构管理员账户
            if ($share_uid && $share_uid != 1 && $share_uid != $school_uid && $share_uid != $teacher_uid && $share_uid != $mount_school_uid) {
                //分享者
                $data['share_id'] = $share_uid;//获得平台分享者用户id//hxb_
                $data['share_sum'] = $sat_teacher_num;//平台分享者的金额
            } else {
                //课程机构 没有分享者机构得所有
                $data['sid'] = $school_uid;
                $data['school_sum'] = round($sam_school,2);
            }
        } else {
            //如果分享者有且不为平台管理员、课程所属机构管理员和教师 挂载机构管理员   //购买用户相应机构管理员、&& $share_uid != $oschool_uid
            //则参与分享者分成(从课程所属机构方扣除) 否则直接加到课程所属机构管理员账户
            if ($share_uid && $share_uid != 1 && $share_uid != $school_uid && $share_uid != $teacher_uid && $share_uid != $mount_school_uid) {
                //讲师
                $data['st_id'] = $teacher_uid;
                $data['school_teacher_sum'] = round($sat_teacher_num * $proportion['ats_teacher'],2);
                //分享者
                $data['share_id'] = $share_uid;//获得平台分享者用户id//hxb_
                $data['share_sum'] = round($sat_teacher_num * $proportion['ats_share'], 2);//平台分享者的金额
            }else{
                //讲师
                $data['st_id'] = $teacher_uid;
                $data['school_teacher_sum'] = $sat_teacher_num;
            }
        }

        //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $school_uid) {
            $school_sum_add = $data['school_sum'];////机构获得的金额
            $platform_sum_new = round($data['platform_sum'] + $school_sum_add, 2);
            $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$school_sum_add}元之和{$platform_sum_new}元；";
            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['sid']);        //课程所属机构分成的管理员用户id
            unset($data['school_sum']); //课程所属机构获得的金额
        }

        //如果课程机构管理员和课程所属讲师相同 销毁课程所属讲师的金额 直接加到课程机构管理员账户上
        if($school_uid == $teacher_uid){
            $teacher_sum_add = $data['school_teacher_sum'];//机构获得的金额
            $school_sum_new = round($data['school_sum'] + $teacher_sum_add, 2);
            $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['school_sum']}元
                                     加上{$teacher_sum_add}元之和{$school_sum_new}元；";
            $data['school_sum'] = $school_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['st_id']);        //课程所属讲师用户id
            unset($data['school_teacher_sum']); //课程所属讲师获得的金额
        }

        $data['ctime'] = time();

        $map['uid'] = intval($uid);//购买用户ID
        $map['vid'] = $vm_map['vid'] = intval($video['id']);//所购买课程的id(包括点播、直播、班级)

        $split_video = M('zy_split_course')->where($map)->getField('id');

        if ($split_video) {
            $res = M('zy_split_course')->where($map)->save($data);
        } else {
            $res = M('zy_split_course')->add($data);
        }
        if ($res){
            return true;
        } else {
            //创建订单明细流水失败 并删除此订单
            M('zy_order_course')->where(array('id' => $order_id))->delete();
            return 13;
        }
        exit;//新版结束
    }

    /**
     * 班级分成明细生成
     * @param $order_id 订单id
     * @param $uid 购买用户id
     * @param $album 购买班级详细
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 5:平台与机构分成比例不存在
     * 6:班级不属于机构
     * 7:班级所绑定的机构管理员不存在
     * 8：班级中包含有 没有相关讲师用户不存在的课程id
     * 9：班级中包含有与班级的机构不一致的课程 $video_id为返回的课程id
     * 10:机构数据里的 机构与教师分成不存在
     * 11:创建订单明细流水失败 并删除此订单
     */
    public function addAlbumSplit($order_id,$uid,$album = array())
    {
        //找不到班级
        if (!$album) {
            return 2;
        }

        //取得分成比例
        $proportion = $this->getAllProportion('album',$album['mhm_id']);

        //平台与机构分成与分享者分成比例为空
        if($proportion == 1 || $proportion == 4){
            return 5;
        }

        //班级不属于机构
        if (!$album['mhm_id']) {
            return 6;
        }

        //班级属于机构 先平台和机构分成 再机构与教师分成
        $school_info = model('School')->where(array('id' => $album['mhm_id']))->field('uid')->find();
        $school_uid = M('user')->where(array('uid' => $school_info['uid']))->getField('uid');
        //班级所绑定的机构管理员不存在
        if (!$school_uid) {
            return 7;
        }

        $albumId = intval($album['id']);

        //获取班级下所有的课程ID
        $video_ids = trim(D("Album")->getVideoId($albumId), ',');
        $v_map['id'] = array('in', array($video_ids));
        $v_map["is_del"] = 0;
        $album_info = M("zy_video")->where($v_map)->field("id,video_title,mhm_id,teacher_id,
                              v_price,t_price,vip_level,endtime,starttime,limit_discount,type,live_type")
            ->select();
        //班级下所有的课程数量 班级下没有课程
        $album['video_count'] = count($album_info);
//        if ($album['video_count'] <= 0) {
//            return 3;
//        }

        $illegal_count = 0;
        $total_price = 0;

        //取得分享者信息
        $vsu_map['tmp_id'] = session_id();
        $vsu_map['type'] = 1;
        $vsu_map['video_id'] = $albumId;
        $share_uid = M('zy_video_share_user')->where($vsu_map)->getField('uid');

        //通过课程取得专辑价格
        $video_id = '';
        $tuid = '';
        $share_str = '';
        $teacher_user_info = "此班级下课程所有讲师信息分别为  ";
        foreach ($album_info as $key => $video) {

            if ($video['mhm_id'] != $album['mhm_id']) {
                $video_id .= $video['id'] . ',';//课程和班级的机构id不一致
            }

            //判断是否有课程过期
            if ($video['uctime'] < time()) {
                $illegal_count += 1;
                $video_gid = $video['id'];//过期id
            }
            //判断班级下没有相关讲师用户不存在的课程id
            $teacher_info = M('zy_teacher')->where(array('id' => $video['teacher_id']))->field('id,uid,name')->find();
            $teacher_uinfo = M('user')->where(array('uid' => $teacher_info['uid']))->field('uid,uname')->find();
            if ($video['type'] != 2) {
                if (!$teacher_uinfo['uid']) {
                    $tuid .= $video['id'] . ',';//班级下没有相关讲师用户不存在的课程id
                }
            }

            $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
            //拼接班级下所有讲师信息以便线下分成
            $key = $key + 1;
            if ($video['type'] != 2) {
                $teacher_user_info .= "【{$key}】 讲师id为: " . $teacher_info['id'] . " ,讲师名字为：" . $teacher_info['name'] .
                    " ;其用户id为： " . $teacher_uinfo['uid'] . " ,用户名为： " . $teacher_uinfo['uname'] . " 。
                    ";
            }
            if ($share_uid == $teacher_uinfo['uid']) {
                $share_str .= $video['id'] . ',';//分享者为当前班级下讲师
            }

            /*if ($video['type'] == 2) {
                if ($video['live_type'] == 1) {
                    $live_zshd_hour = M('zy_live_zshd')->where('live_id=' . $video['id'])->field('id,speaker_id')->findAll();
                    //直播课程下所有的课程数量 直播课程下没有课程
                    $video_count = count($live_zshd_hour);
//                    if ($video_count <= 0) {
//                        return 3;
//                    }

                    $tuid = '';
                    foreach ($live_zshd_hour as $k => $v) {
                        //判断直播课堂下没有相关讲师用户不存在的课程
                        $teacher_live_info = M('zy_teacher')->where(array('id' => $v['speaker_id']))->field('id,uid,name')->find();
                        $teacher_live_uinfo = M('user')->where(array('uid' => $teacher_live_info['uid']))->field('uid,uname')->find();
                        if (!$teacher_live_uinfo['uid']) {
                            $tuid .= $v['id'] . ',';//直播课堂下没有相关讲师用户不存在的课程id
                        }
                        $album_info[$key]['teacher_uid'] = $teacher_uinfo['uid'];
                        //拼接直播课堂下所有讲师信息以便线下分成
                        $teacher_user_info .= "【{$key}】 讲师id为: " . $teacher_live_info['id'] . " ,讲师名字为：" . $teacher_live_info['name'] .
                            " ;其用户id为： " . $teacher_live_uinfo['uid'] . " ,用户名为： " . $teacher_live_uinfo['uname'] . " 。
                            ";
                        if ($share_uid == $teacher_live_uinfo['uid']) {
                            $share_str .= $v['id'] . ',';//分享者为当前班级下讲师
                        }
                    }
                } elseif ($video['live_type'] == 3) {
                    $live_gh_hour = M('zy_live_gh')->where('live_id=' . $video['id'])->field('id,speaker_id')->findAll();
                    foreach ($live_gh_hour as $k => $v) {
                        //判断直播课堂下没有相关讲师用户不存在的课程
                        $teacher_live_info = M('zy_teacher')->where(array('id' => $v['speaker_id']))->field('id,uid,name')->find();
                        $teacher_live_uinfo = M('user')->where(array('uid' => $teacher_live_info['uid']))->field('uid,uname')->find();
                        if (!$teacher_live_uinfo['uid']) {
                            $tuid .= $v['id'] . ',';//直播课堂下没有相关讲师用户不存在的课程id
                        }
                        //拼接直播课堂下所有讲师信息以便线下分成
                        $teacher_user_info .= "【{$key}】 讲师id为: " . $teacher_live_info['id'] . " ,讲师名字为：" . $teacher_live_info['name'] .
                            " ;其用户id为： " . $teacher_live_uinfo['uid'] . " ,用户名为： " . $teacher_live_uinfo['uname'] . " 。
                                        ";
                    }
                    if ($share_uid == $teacher_live_uinfo['uid']) {
                        $share_str .= $v['id'] . ',';//分享者为当前班级下讲师
                    }
                }
            }*/
            $album_info['tuid_str'] .= $teacher_uinfo['uid'] . ",";
        }

//        //班级中包含有 没有相关讲师用户不存在的课程id
//        if ($tuid) {
//            return 8;
//        }

        //班级中包含有与班级的机构不一致的课程 $video_id为返回的课程id
        if ($video_id) {
            return 9;
        }

        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');

        //生成订单分成详细 取得价格
        $prices = floatval($album['price']);
        $data['status'] = 0;
        $data['order_id'] = $order_id;
        $data['uid'] = intval($uid);//购买用户ID
        $data['mhm_id']       = intval($album['mhm_id']);//课程所属机构id
        $data['order_mhm_id'] = intval($oschool_id);//购买的用户机构id
        $data['aid'] = $albumId;//所购买课程的id
        $data['note'] .= t("购买班级：{$album['album_title']}。");
        $data['sum'] = $prices;//购买金额

        //平台手续费
        $data['pid']          = 1;//获得平台分成的管理员用户id
        $data['platform_sum'] = round($prices * $proportion['pas_platform'],2);//平台手续费

        //扣掉平台手续费后的总金额
        $school_num = $prices * $proportion['pas_school'];//机构及分析者与教师分成的总金额

        //如果分享者有且不为平台管理员、购买用户相应机构管理员、课程所属机构管理员和教师，
        //则参与分享者分成(从购买机构方扣除，原为课程所属方) 否则直接加到课程所属机构管理员账户
        if ($share_uid && $share_uid != 1 && $share_uid != $oschool_uid && $share_uid != $school_uid && $share_uid != $teacher_uid) {
            $data['sid'] = $oschool_uid;//购买机构分成的管理员用户id
            $data['school_sum'] = round($school_num * $proportion['sas_school'], 2);//购买机构获得的金额

            $data['share_id'] = $share_uid;//获得平台分享者用户id
            $data['share_sum'] = round($school_num * $proportion['sas_share'], 2);//平台分享者的金额
        } else {
            $data['sid'] = $school_uid;//课程所属机构分成的管理员用户id
            $data['school_sum'] = $school_num;//课程所属机构获得的金额
        }

        //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $school_uid) {
            $school_sum_add = $data['school_sum'];////机构获得的金额
            $platform_sum_new = round($data['platform_sum'] + $school_sum_add, 2);
            $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$school_sum_add}元之和{$platform_sum_new}元；";
            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['sid']);        //课程所属机构分成的管理员用户id
            unset($data['school_sum']); //课程所属机构获得的金额
        }

        $data['ctime'] = time();

        $map['uid'] = intval($uid);//购买用户ID
        $map['aid'] = $albumId;//所购买班级的id

        $split_video = M('zy_split_album')->where($map)->getField('id');

        if ($split_video) {
            $res = M('zy_split_album')->where($map)->save($data);
        } else {
            $res = M('zy_split_album')->add($data);
        }
        if ($res) {
            return true;
        } else {
            //创建订单明细流水失败 并删除此订单
            M('ZyOrderAlbum')->where(array('id' => $order_id))->delete();
            return 12;
        }
        exit;
    }


    /**
     * 检查一个用户是否可以直接观看某个课程
     * @param integer $uid 用户ID
     * @param integer $video_id 课程ID
     * @return boolean 可以直接观看返回true，否则返回false
     */
    public function checkVideoAccess($uid, $video_id) {
        $time = time();
        //取得课程
        $video = D('ZyVideo','classroom')->where(array(
            'id' => $video_id,
            'is_del' => 0,
            'is_activity' => 1,
            //'uctime' => array('gt', $time),
            //'listingtime' => array('lt', $time),
        ))->field("id,uid,video_title,mhm_id,teacher_id,v_price,t_price,
                    vip_level,listingtime,uctime,limit_discount,uid,teacher_id,type")->find();

        //找不到课程
        if (!$video){
            return false;
        }
        //管理员
        if (model('UserGroup')->isAdmin($uid)) {
            return true;
        }
        //如果是机构管理员 该机构的课程
        if( $video['mhm_id'] && (is_school($uid) == $video['mhm_id'])){
            return true;
        }
        $teacher_id = D('ZyTeacher','classroom')->getTeacherStrByMap((array('uid'=>$uid)),'id');
        //自己的课程
        if($uid || $video['uid']){
            if ($video['uid'] == $uid ){
                return true;
            }
        }
        if($video['teacher_id'] || $teacher_id) {
            if ($video['teacher_id'] == $teacher_id) {
                return true;
            }
        }
        $order = D('ZyOrderCourse','classroom');
        //检查是否已经购买过了
        if ($order->isBuyVideo($uid, $video_id)){
            return true;
        }

        $order = D('ZyOrderLive','classroom');
        if ($order->isBuyLive($uid, $video_id)){
            return true;
        }

        //取得价格
        $prices = getPrice($video, $uid, true, true,$video['type']);
        //限时免费和价格为0的  不需购买
        if ($prices['price'] <= 0 || $video['is_charge'] == 1){
            return true;
        }

        return false;
    }

    /**
     * 在线直接购买单个课程
     * @param $uid
     * @param $video_id
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 1:可以直接观看，可能的原因是用户自己发布的，用户为管理员，价格为0，已经购买过了
     * 2:找不到课程
     * 4:购买记录/订单，添加失败
     */
    public function buyOnlineTeacher($uid, $video_id,$ext_data = array()) {
        if ($this->checkVideoAccess($uid, $video_id)) {
            return 1;
        }
        $time = time();
        //取得课程
        $video = M('zy_teacher_course')->where(array(
            'course_id' => $video_id,
            'is_del' => 0,
            'is_activity' => 1,
            'uctime' => array('gt', $time),
        ))->find();
        //找不到课程
        if (!$video){
            return 2;
        }

        //取得分成比例
        $proportion = $this->getAllProportion('course_line',$video['mhm_id']);

        //平台与机构分成与分享者分成比例为空
        if($proportion == 1 || $proportion == 3 || $proportion == 4){
            return 5;
        }

        //课程不属于机构
        if(!$video['mhm_id']) {
            return 6;
        }

        //课程属于机构
        $school_info = model('School')->where(array('id'=>$video['mhm_id']))->field('uid')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');

        //机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        //查询教师用户uid
        $teacher_uid = M('zy_teacher')->where(array('id'=>$video['teacher_id']))->getField('uid');
        $teacher_uid = M('user')->where(array('uid'=>$teacher_uid))->getField('uid');

        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');
        //购买用户的相应机构管理员不存在
        if(!$oschool_uid){
            return 11;
        }

//        //课程订单有效期不存在
//        if(!$video['term']){
//            return 12;
//        }

        //生成状态为未支付的订单数据
        $order = M('zy_order_teacher');
        $data = array(
            'uid'           => $uid,
            'video_id'      => $video['course_id'],
            'price'         => $ext_data['price'],
            'ctime'         => $time,
            'is_del'        => 0,
            'pay_status'    => 1,
            'time_limit'    => time() + 129600 * floatval($video['term']),
            'mhm_id'        => $video['mhm_id'],//课程机构id
            'order_mhm_id'  => intval($oschool_id),//购买的用户机构id
            'learn_status'  => 0,
            'tid'           => $video['teacher_id'],
        );

        $map['uid']         = $uid;
        $map['video_id']    = $video['course_id'];

        $order_id = $order->where($map)->getField('id');
        if($order_id){
            $id = $order->where($map)->save($data);
            if($id){
                $id = $order_id;
            }else{
                $id = 0;
            }
        }else{
            $id = $order->add($data);
        }

        //购买记录/订单，添加失败
        if (!$id){
            return 4;
        }

        $teacher_spilt_status = $this->addTeacherSplit($id,$uid,$video,$ext_data['price']);
        if(!$teacher_spilt_status){
            //创建订单明细流水失败 并删除此订单
            M('zy_order_teacher')->where(array('id' => $id))->delete();
            return $teacher_spilt_status;
        }else{
            return true;
        }
    }

    /**
     * 点播 分成明细生成
     * @param $order_id 订单id
     * @param $uid 购买用户id
     * @param $video 购买课程详细
     * @return mixed 成功后返回true，失败返回错误状态码
     * 错误状态一览：
     * 5:平台与机构分成比例不存在
     * 6:课程不属于机构
     * 7:机构管理员不存在
     * 8:课程下讲师不存在
     * 9:机构数据里的 机构与教师分成比例不存在
     * 10机构数据里的 机构与挂载机构分成不存在
     * 11购买用户的相应机构管理员不存在
     * 12:课程订单有效期不存在
     * 13:创建订单明细流水失败
     */
    public function addTeacherSplit($order_id,$uid,$video = array(),$prices){
        //找不到课程
        if (!$video){
            return 2;
        }

        //取得分成比例
        $proportion = $this->getAllProportion('course_line',$video['mhm_id']);

        //平台与机构分成与分享者分成比例为空
        if($proportion == 1 || $proportion == 3 || $proportion == 5){
            return 5;
        }

        //课程不属于机构
        if(!$video['mhm_id']) {
            return 6;
        }

        //课程所属机构
        $school_info = model('School')->where(array('id'=>$video['mhm_id']))->field('uid')->find();
        $school_uid = M('user')->where(array('uid'=>$school_info['uid']))->getField('uid');

        //机构管理员不存在
        if(!$school_uid){
            return 7;
        }

        //查询教师用户uid
        $teacher_uid = M('zy_teacher')->where(array('id'=>$video['teacher_id']))->getField('uid');
        $teacher_uid = M('user')->where(array('uid'=>$teacher_uid))->getField('uid');
//        //课程下讲师不存在
//        if(!$teacher_uid){
//            return 8;
//        }

        //购买用户相应机构抽成
        $mhuid = M('user')->where('uid = '.$uid)->getField('mhm_id');
        $oschool_uid = model('School')->where(array('id'=>$mhuid))->getField('uid');
        $oschool_id = model('School')->where(array('id'=>$mhuid))->getField('id');

//        //课程订单有效期不存在
//        if(!$video['term']){
//            return 12;
//        }
        //生成订单分成详细 取得价格
        $prices = floatval($prices);
        $data['status']       = 0;
        $data['order_id']     = $order_id;
        $data['uid']          = intval($uid);//购买用户ID
        $data['mhm_id']       = intval($video['mhm_id']);//课程所属机构id
        $data['order_mhm_id'] = intval($oschool_id);//购买用户的机构id
        $data['vid']          = intval($video['course_id']);//所购买线下课程的id
        $data['note']         = t("购买线下课程：{$video['course_name']}。");
        $data['sum']          = $prices;//购买金额

        //平台手续费
        $data['pid']          = 1;//获得平台分成的管理员用户id
        $data['platform_sum'] = round($prices * $proportion['pas_platform'],2);//平台手续费

        //扣掉平台手续费后的总金额
        $school_num = $prices * $proportion['pas_school'];//机构及分享者的总金额

        //课程机构
        $data['sid'] = $school_uid;
        $data['school_sum'] = round($school_num * $proportion['sat_school'],2);

        //扣掉课程机构后讲师与分享者的总金额
        $sat_teacher_num = round($school_num * $proportion['sat_teacher'],2);

        $vsu_map['tmp_id']   = session_id();
        $vsu_map['type']     = 3;
        $vsu_map['video_id'] = $video['course_id'];
        $share_uid = M('zy_video_share_user')->where($vsu_map)->getField('uid');

        //如果讲师没有分成，即讲师设置的比例为0
        if($proportion['ats_teacher'] == 0){
            //如果分享者有且不为平台管理员、课程所属机构管理员和教师 挂载机构管理员   //购买用户相应机构管理员、&& $share_uid != $oschool_uid
            //则参与分享者分成(从课程所属机构方扣除) 否则直接加到课程所属机构管理员账户
            if ($share_uid && $share_uid != 1 && $share_uid != $school_uid && $share_uid != $teacher_uid && $share_uid != $mount_school_uid) {
                //分享者
                $data['share_id'] = $share_uid;//获得平台分享者用户id//hxb_
                $data['share_sum'] = $sat_teacher_num;//平台分享者的金额
            } else {
                //课程机构 没有分享者机构得所有
                $data['sid'] = $school_uid;
                $data['school_sum'] = $school_num;
            }
        } else {
            //如果分享者有且不为平台管理员、课程所属机构管理员和教师 挂载机构管理员   //购买用户相应机构管理员、&& $share_uid != $oschool_uid
            //则参与分享者分成(从课程所属机构方扣除) 否则直接加到课程所属机构管理员账户
            if ($share_uid && $share_uid != 1 && $share_uid != $school_uid && $share_uid != $teacher_uid) {
                //讲师
                $data['st_id'] = $teacher_uid;
                $data['school_teacher_sum'] = round($sat_teacher_num * $proportion['ats_teacher'],2);
                //分享者
                $data['share_id'] = $share_uid;//获得平台分享者用户id
                $data['share_sum'] = round($sat_teacher_num * $proportion['ats_share'], 2);//平台分享者的金额
            }else{
                //讲师
                $data['st_id'] = $teacher_uid;
                $data['school_teacher_sum'] = $sat_teacher_num;
            }
        }

        //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $school_uid) {
            $school_sum_add = $data['school_sum'];////机构获得的金额
            $platform_sum_new = round($data['platform_sum'] + $school_sum_add, 2);
            $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$school_sum_add}元之和{$platform_sum_new}元；";
            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['sid']);        //课程所属机构分成的管理员用户id
            unset($data['school_sum']); //课程所属机构获得的金额
        }

        //如果课程机构管理员和课程所属讲师相同 销毁课程所属讲师的金额 直接加到课程机构管理员账户上
        if($school_uid == $teacher_uid){
            $teacher_sum_add = $data['school_teacher_sum'];//机构获得的金额
            $school_sum_new = round($data['school_sum'] + $teacher_sum_add, 2);
            $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['school_sum']}元
                                     加上{$teacher_sum_add}元之和{$school_sum_new}元；";
            $data['school_sum'] = $school_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['st_id']);        //课程所属讲师用户id
            unset($data['school_teacher_sum']); //课程所属讲师获得的金额
        }

        //如果平台管理员和课程所属机构管理员相同 销毁课程所属机构管理员的金额 直接加到平台管理员账户上
        if ($data['pid'] == $school_uid) {
            $school_sum_add = $data['school_sum'];//机构获得的金额
            $platform_sum_new = round($data['platform_sum'] + $school_sum_add, 2);
            $data['note'] .= "平台管理员和课程所属机构管理员相同，分成金额为两者的分成金额{$data['platform_sum']}元
                                     加上{$school_sum_add}元之和{$platform_sum_new}元；";
            $data['platform_sum'] = $platform_sum_new;//平台分成的金额 + 机构获得的金额
            unset($data['sid']);        //课程所属机构分成的管理员用户id
            unset($data['school_sum']); //课程所属机构获得的金额
        }

        $data['ctime'] = time();

        $map['uid'] = intval($uid);//购买用户ID
        $map['vid'] = $vm_map['vid'] = intval($video['course_id']);//所购买课程的id(包括点播、直播、班级)

        $split_video = M('zy_split_teacher')->where($map)->getField('id');

        if ($split_video) {
            $res = M('zy_split_teacher')->where($map)->save($data);
        } else {
            $res = M('zy_split_teacher')->add($data);
        }
        if ($res){
            return true;
        } else {
            //创建订单明细流水失败 并删除此订单
            M('zy_order_teacher')->where(array('id' => $order_id))->delete();
            return 13;
        }
        exit;//新版结束
    }
}
