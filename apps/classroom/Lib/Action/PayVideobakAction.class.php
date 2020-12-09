<?php
tsload(APPS_PATH.'/classroom/Lib/Action/CommonAction.class.php');
class PayVideoAction extends CommonAction{

    public function index(){
        $str = $_GET['vst'];
        $data_arr = explode(',', $str);

        $cid = $data_arr[4];
        $pay_video_id = intval($data_arr[0]);

        $pay_video_type = t($data_arr[1]);
        $pay_video_type_arr = explode('_',$pay_video_type);

//        $pay_video_mount = t($data_arr[2]);
//        $pay_video_mount_school = explode('H', $pay_video_mount)[0];
        $pay_video_mount_school = t($data_arr[2]);

        $pay_gspay = t($data_arr[3]);

        if($pay_video_type_arr[1] == 'video' || $pay_video_type_arr[1] == 'album' || $pay_video_type_arr[1] == 'live'){
            $vtype = $pay_video_type_arr[1];
        }else{
            $vtype = false;
        }
        $this->assign('jumpUrl', U('classroom/Index/index'));
        if(!is_numeric($pay_video_id) || $pay_video_type_arr[0] != 'zy' || !$vtype){
            $this->error("错误的购买参数");
        }

        if($pay_video_type == 'zy_video'){
            $video_map ['id']          = $pay_video_id;
            $video_map ['is_activity'] = 1;
            $video_map ['is_del']      = 0;
            $video_map ['uctime']      = array('gt',time());
            $video_map ['listingtime'] = array('lt',time());
            $data = D ( 'ZyVideo' )->where ( $video_map )->field("id,uid,video_title,mhm_id,teacher_id,cover,fullcategorypath,v_price,t_price,vip_level,
                    listingtime,uctime,limit_discount,term,type")->find ();
            if(!$data){
                $this->error("课程不存在");
            }
            $data['term_num']  = round(($data['uctime'] - $data['listingtime'])/86400);
            $data['video_section_num']  = M('zy_video_section')->where(array('vid'=>$data['id'],'pid'=>array('neq',0)))->field('id')->count();
            $data['moner_data'] = getPrice($data, $this->mid, false, true);

        }else if($pay_video_type == 'zy_live'){
            $live_map['id'] = $pay_video_id;
            $live_map['is_del'] = 0;
            $live_map['is_activity'] = 1;
            $live_map['type'] = 2;
            $live_map['listingtime'] = array('lt', time());
            $live_map['uctime'] = array('gt', time());
            $data = D('ZyVideo')->where($live_map)->field("id,uid,video_title,mhm_id,teacher_id,cover,fullcategorypath,v_price,t_price,vip_level,
                    listingtime,uctime,limit_discount,live_type,type")->find ();
            if(!$data){
                $this->error("直播课程不存在");
            }
            $data['moner_data']['oriPrice'] =  $data['v_price'] ;
            $data['moner_data']['price'] =  $data['t_price'] ;
            if($data['live_type'] == 1){
                $data['video_section_num']  = M('zy_live_zshd')->where(array('live_id'=>$data['id'],'is_active'=>1,'is_del'=>0))->field('id')->count();
            }elseif($data['live_type'] == 3){
                $data['video_section_num']  = M('zy_live_gh')->where(array('live_id'=>$data['id'],'is_active'=>1,'is_del'=>0))->field('id')->count();
            }
        }else if($pay_video_type == 'zy_album'){
            $map['id'] = array('eq', $pay_video_id);
            $map['status'] = 1;
            $data = D('Album')->where($map)->find();
            if (!$data) {
                $this->assign('isAdmin', 1);
                $this->error('班级不存在!');
            }

            $album_video = D('Album')->getVideoId($pay_video_id);
            $oPrice = 0.00;
            $videoData = array();
            if($album_video){
                $tMap['id'] = array('IN',trim( $album_video ,','));
                $tMap['is_del'] = array('eq',0);
                $tMap['teacher_id'] = array('NEQ',0);
                $videoData = D('ZyVideo')->where($tMap)->field('id,teacher_id,t_price,video_title,cover,video_intro')->select();
            }
            foreach($videoData as $key=>$val){
                $tch_ids[$key] = $val['teacher_id'];
                $oPrice += $val['t_price'];
            }

            //获取班级价格
            $data['moner_data']['oriPrice'] = ($oPrice - $data['price']) > 0 ? $oPrice : $data['price'];
            $data['moner_data']['price'] = $data['price'];
            $data['video_title'] = $data['album_title'];
            $data['fullcategorypath'] = $data['album_category'];
            $data['video_section_num'] = count($videoData);
        }
        $ceta_data = array_filter(explode(',', $data['fullcategorypath']));
        foreach ($ceta_data as $key => $val){
            $cetas_data[$key]['cate_title'] = M('zy_currency_category')->where(array('zy_currency_category_id'=>$val))->getField('title');
            $pid = M('zy_currency_category')->where('zy_currency_category_id = '.$val)->getField('pid');
            $cetas_data[$key]['cate_id'] = $val;
            if($pid){
                $cetas_data[$key]['cate_id'] = $pid.','.$val;
                $pid2 = M('zy_currency_category')->where('zy_currency_category_id = '.$pid)->getField('pid');
                if($pid2){
                    $cetas_data[$key]['cate_id'] = $pid2.','.$pid.','.$val;
                }
            }
        }
        $school_info = M('school')->where(array('id'=>$data['mhm_id']))->field('title,doadmin')->find();
        $data['mhm_info'] = $school_info;
        $data['cate_info'] = $cetas_data;

        //查找此课程可用的优惠券、打折卡
        $coupon_id = M('coupon_user')->where(array('uid'=>$this->mid,'id'=>$cid,'status'=>2,'is_del'=>0,'etime'=>['gt',time()]))->getField('cid');
        $new_coupon = model('Coupon')->where(array('id'=>$coupon_id,'is_del'=>0))->find();
        if(!$new_coupon){
            if($pay_video_type == 'zy_video' || $pay_video_type == 'zy_live' || $pay_video_type == 'zy_album'){
                $vc_map = array('uid'=>$this->mid,'type'=>1,'sid'=>$data['mhm_id']);
                $videoCoupon = model('Coupon')->getUserCouponList($vc_map,0);
                foreach ($videoCoupon['data'] as $key => $val) {
                    $videoCoupon['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
                    $videoCoupon['data'][$key]['price'] = floor($val['price']);
                    $videoCoupon['data'][$key]['maxprice'] = floor($val['maxprice']);
                    $videoCoupon['data'][$key]['stime'] = date("Y.m.d",$val['stime']);
                    $videoCoupon['data'][$key]['etime'] = date("Y.m.d",$val['etime']);
                }
                $vd_map = array('uid'=>$this->mid,'type'=>2,'sid'=>$data['mhm_id']);
                $discount = model('Coupon')->getUserCouponList($vd_map);
                foreach ($discount['data'] as $key => $val) {
                    $discount['data'][$key]['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
                    $time = time();
                    if($val['status'] == 0 && $val['etime'] < $time){
                        $discount['data'][$key]['status'] = 2;
                    }
                }
                $this->assign('discount', $discount['data']);
                $this->assign('videoCoupon', $videoCoupon['data']);
            }
        }else{
            $new_coupon['school_title'] = model('School')->where(array('id'=>$new_coupon['sid']))->getField('title');
            $new_coupon['stime'] = M('coupon_user')->where(array('uid'=>$this->mid,'id'=>$cid))->getField('stime');
            $new_coupon['etime'] = M('coupon_user')->where(array('uid'=>$this->mid,'id'=>$cid))->getField('etime');
            $this->assign('coupon',$new_coupon);
        }

        $this->assign($data);
        $this->assign('pay_gspay',$pay_gspay);
        $this->assign('pay_video_mount_school',$pay_video_mount_school);
        $this->assign('pay_video_type',$pay_video_type);
        $this->disPlay();
    }
    /*
     * @name 获取卡券信息
     */
    public function getCouponInfo(){
        $id = intval($_POST['id']);
        $coupon = model('Coupon')->getCouponInfoById($id);
        if($coupon){
            $coupon['cuid'] = M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon['coupon_id']))->getField('id');
            echo json_encode($coupon);exit;
        }else{
            return false;
        }
    }

    /*
     * @name 获取优惠券信息
     */
    public function getExchangeCard(){
        $code = $_POST['code'];
        $sid = $_POST['mhm_id'];
        $map['code'] = $code;
        $map['sid'] = $sid;
        $map['coupon_type'] = 1 ;
        $map['video_id'] = intval($_POST['vid']);
        $coupon = model('Coupon')->where($map)->find();
        if($coupon){
            $couponUserId = M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon['id'],'status'=>0,'is_del'=>0,'etime'=>['gt',time()]))->getField('id');
            if(!$couponUserId){
                $res = model('Coupon')->grantCouponByCode($code);
            }else{
                $res = 'true';
            }
            if($res == 'true'){
                $coupon['coupon_id'] = M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon['id']))->getField('id');
                $coupon['status'] = 1;
                echo json_encode($coupon);exit;
            }else{
                $this->mzError('使用优惠券失败,请重新尝试');
            }
        }else{
            $this->mzError('该实体卡无法使用');
        }
    }
    /*
     * @name 取消使用优惠券
     */
    public function cancelExchangeCard(){
        $map['code'] = $_POST['code'];
        $map['sid'] = $_POST['mhm_id'];
        $data['status'] = 1;
        $result = model('Coupon')->where($map)->save($data);
        if($result){
            $coupon_id = model('Coupon')->where($map)->getField('id');
            $res = M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon_id))->delete();
        }
        if($res){
            $this->mzSuccess('取消使用成功');
        }else{
            $this->mzError('取消使用失败,请重新尝试');
        }
    }

    /**
     * @name 检查使用优惠券
     */
    public function checkCoupon(){
        $coupon_id = intval($_POST['coupon_id']);
        $price = floatval($_POST['price']);
        if(session('get_preferential_operation') >= time()){
            $this->mzError('请勿频繁操作');
        }

        if($coupon_id && $price){
            //检测优惠券是否可以使用
            $uCoupon = M('coupon_user')->where(array('id'=>$coupon_id,'is_del'=>0,'etime'=>['gt',time()]))->find();
            if($uCoupon['status'] == 2){
                $coupon = model('Coupon')->getCouponInfoById($uCoupon['cid']);
            }else{
                $coupon = model('Coupon')->canUse($coupon_id,$this->mid);
            }

            if(!$coupon){
                $this->mzError('该优惠券已经无法使用');
            }
            $this->coupon = $coupon;
            //优惠券类型是否符合
            if(!in_array($coupon['type'],[1,2])){
                $this->mzError('该优惠券不能用于购买课程');
            }
            switch($coupon['type']){
                case "1":
                    //价格低于门槛价 || 至少支付0.01
                    if($coupon['maxprice'] != '0.00' && $price <= $coupon['maxprice']){
                        $this->mzError('该优惠券需要满'.$coupon['maxprice'].'元才能使用');
                    }
                    if($price < $coupon['price']){
                        $this->mzError('所支付的金额不满足使用优惠券条件');
                    }
                    $after_preferential = round($price-$coupon['price'],2);
                    $minus_price = $coupon['price'];
                    break;
                case "2":
                    $after_preferential = $price * $coupon['discount']/10;
                    $minus_price = $price - $after_preferential;
                default:
                    break;
            }
            $new_price['minus_price'] = $minus_price;
            $new_price['after_price'] = $after_preferential;

            //使用优惠券
            //if(M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1)){
            session('get_preferential_operation', time() + 3);
            $this->ajaxReturn($new_price,'获取优惠券成功',1);
            //}
        }else{
            $this->mzError('使用优惠券失败,请重新尝试');
        }
    }


    /**
     * 检查课程是否需要购买等
     */
    public function checkPay(){
        if(!$this->mid){
            $this->mzError('请先登录');
        }

        $check_type = t($_POST['check_type']);

        $vid = intval($_POST['vid']);

        $type = t($_POST['type']);

        if($check_type == 'zy_video') {
            if(session('purchase_pay_video_operation') >= time()){
                $this->mzError('请勿频繁操作');
            }
            $type_title = "课程";
        } else if ($check_type == 'zy_album') {
            if(session('purchase_pay_album_operation') >= time()){
                $this->mzError('请勿频繁操作');
            }
            $type_title = "班级";
        } else if ($check_type == 'zy_live') {
            if(session('purchase_pay_live_operation') >= time()){
                $this->mzError('请勿频繁操作');
            }
            $type_title = "直播";
        }
        if(!$vid){
            $this->mzError("请选择要购买的$type_title");
        }
        $uid = $this->mid;
        $money = floatval($_POST['money']);

        //检查是否可以购买
        if($check_type == 'zy_video') {
            $data = D('ZyVideo')->where(array('id' => $vid))->find();
            $old_price = getPrice($data, $this->mid, true, true);
            if (bccomp($money, $old_price['price'])) {
                $this->mzError("请勿篡改金额");
            }

            $is_buy = D('ZyService')->checkVideoAccess($uid, $vid);
            session('purchase_pay_video_operation', time() + 10);
        } else if ($check_type == 'zy_album') {
            $price = D("Album")->getAlbumOneInfoById($vid,'price');
            if (bccomp($money, $price)) {
                $this->mzError("请勿篡改金额");
            }
            session('purchase_pay_album_operation', time() + 10);
        } else if ($check_type == 'zy_live') {
            $price = D('ZyVideo')->where(array('id' => $vid))->getField('t_price');
            if (bccomp($money, $price)) {
                $this->mzError("请勿篡改金额");
            }
            session('purchase_pay_live_operation', time() + 10);
        }
        if($is_buy){
            $this->mzError("该{$type_title}不需要您购买！");
        }
        //检查是否加入订单
        if(!$type){
            if($check_type == 'zy_video') {
                $pay_status = M('zy_order_course')->where(array('uid' => intval($this->mid), 'video_id' => $vid,'is_del'=>0))->getField('pay_status');
            } else if ($check_type == 'zy_album') {
                $pay_status = M('zy_order_album')->where(array('uid' => intval($this->mid), 'album_id' => $vid,'is_del'=>0))->getField('pay_status');
            } else if ($check_type == 'zy_live') {
                $pay_status = M('zy_order_live')->where(array('uid' => intval($this->mid), 'live_id' => $vid,'is_del'=>0))->getField('pay_status');
            }
        }
        if ($pay_status == 1) {
            $this->mzError("课程订单未支付，请先到个人中心—订单操作！");
        }else{
            $data['vid'] = $vid;
            $data['type'] = $check_type;
            $this->mzSuccess('');
        }
    }

    public function test(){
        $service = D('ZyService')->buyOnlineVideo(1009,1021,[],146);
//        $service = D('ZyService')->buyOnlineLive(3,915);
//        $service = D('ZyService')->buyOnlineAlbum(679,34);
//        dump($service);
//        $map['uid'] = 3;//购买用户ID
//        $map['status'] = 1;
//        $map['aid']  = 1;
//
//        D('ZySplit')->addVideoFlows($map, 5, 'zy_album_order');

//        $ree = M('zy_split_balance')->field('uid')->findAll();
//        $recipe_id  = getSubByKey($ree , 'uid');
//        $ids = "1,2,3,4,9,13,14,15,16,17,18,19,20,21,24,26,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,71,73,74,75,76,77,78,79,80,81,86,87,88,89,90,91,92,93,94,95,97,99,121,122,123,124,125,126,132,133,134,135,136,137,138,139,140,141,142,143,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,176,177,178,179,180,181,182,183,184,185,186,187,188,189,190,191,192,193,194,195,196,197,198,199,200,201,202,204,211,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,230,231,232,233,236,237,238,239,240,241,242,243,244,245,246,247,248,249,250,251,252,253,254,255,256,257,258,259,260,261,262,263,264,265,266,267,268,269,270,271,272,273,274,275,276,277,278,279,280,281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,301,302,303,304,305,306,307,308,309,310,311,312,313,314,315,316,317,318,319,320,321,322,323,324,325,326,327,328,329,330,331,332,333,334,335,336,337,338,339,340,341,342,343,344,345,346,347,348,349,350,351,352,353,354,355,356,357,358,359,360,361,362,363,364,365,366,367,368,371,372,373,374,375,376,377,378,379,380,381,382,383,384,385,386,387,388,389,390,391,392,393,394,395,396,397,398,399,400,401,402,403,404,405,406,407,408,409,410,411,412,413,414,415,416,417,418,419,420,421,422,423,424,425,426,427,428,429,430,431,432,433,434,435,436,437,438,439,440,441,442,443,445,447,448,449,450,451,453,457,458,460,461,462,463,464,465,466,467,469,470,471,472,473,474,475,476,477,478,479,480,481,482,483,484,485,486,487,488,489,490,491,498,499,500,501,502,503,504,505,506,507,508,509,510,511,512,513,514,515,516,517,518,519,520,521,523,524,525,526,527,528,529,530,531,532,533,535,536,537,538,539,540,541,542,543,544,545,546,547,548,549,550,551,552,555,557,558,560,561,562,563,564,565,566,567,568,569,570,571,572,574,575,576,582,585,588,589,590,592,593,594,595,596,597,598,599,600,601,609,610,611,612,613,614";
//        $rees = M('zy_split_balance')->where(array('uid'=>array('in',$ids)))->save(array('balance'=>0));
//        dump($rees);
//        $video_address = 'http://chuyou.qiniudn.com/{$this->site['site_keyword']}81484121736';
//        $avinfo = json_decode(file_get_contents($video_address.'?avinfo') , true);
//        dump($avinfo['format']['duration']);
//        dump(number_format($avinfo['format']['duration']/60, 2, ':', ''));
//        dump(number_format(0.186122/60, 2, ':', ''));

//        $i = D('ZyService')->buyOnlineLive(intval($this->mid), 557,$ext_data);
//        $i = D('ZyService')->buyOnlineVideo(intval($this->mid), 633,$ext_data);
//        $i = D('ZyService')->buyOnlineAlbum(intval($this->mid), 1);
//
//        dump($i);

//        $map['uid'] = intval($this->mid);//购买用户ID
//        $map['status'] = 1;
//        $map['vid']  = intval(633);
//
//        D('ZySplit')->addVideoFlows($map, 5, 'zy_video_order');

//        $result = D('ZyRecharge')->setNormalPaySuccess2(1,11189449947788);
//        dump($result);


    }
    /**
     * 检查课程购买
     */
    public function checkPayOperat(){
        if($_SERVER['REQUEST_METHOD']!='POST') exit;

        //使用后台提示模版
        $this->assign('isAdmin', 1);

        $check_type = t($_POST['check_type']);

        //必须要先登录才能进行操作
        if(!$this->mid){
            $this->mzError("请先登录再进行购买");
        }

        if($check_type == 'zy_video') {
            if(session('purchase_course_operation') >= time()){
                $this->mzError('请勿频繁操作');
            }
        }else if($check_type == 'zy_album') {
            if( session('purchase_album_operation') >= time()){
                $this->mzError('请勿频繁操作');
            }
        }else if($check_type == 'zy_live') {
            if( session('purchase_live_operation') >= time()){
                $this->mzError('请勿频繁操作');
            }
        }

        $vid = intval($_POST['vid']);
        if(!$vid){
            $this->mzError("参数错误");
        }

        $pay_list = array('alipay','unionpay','wxpay');
        if(!in_array($_POST['pay'],$pay_list)){
            $this->mzError("支付方式错误");
        }

        $money = floatval($_POST['money']);
        if($money <= 0){
            $this->mzError("请选择或填写购买金额");
        }
        $rechange_base = getAppConfig('rechange_basenum');
        /*if($rechange_base > 0 && $money % $rechange_base != 0){
            if($rechange_base == 1){
                $this->mzError("购买金额必须为整数");
            }else{
                $this->mzError("购买金额必须为{$rechange_base}的倍数");
            }
        }*/

        $coupon_id = intval($_POST['coupon_id']);
        $dis_type = intval($_POST['discount_type']);

        $pay_video_mount_school = explode('H',t($_POST['pay_video_mount_school']))[0];

        if($check_type == 'zy_video') {
            $data = D('ZyVideo')->where(array('id' => $vid))->find();
            if($coupon_id && $dis_type){
                $ext_data = [
                    'coupon_id' => $coupon_id,
                    'dis_type' => $dis_type,
                    'price' => $money,
                ];
            }else{
                $old_price = getPrice($data, $this->mid, true, true);
                if (bccomp($money, $old_price['price'])) {
                    $this->mzError("请勿篡改金额");
                }
            }
            $pay_status = M('zy_order_course')->where(array('uid' => intval($this->mid), 'video_id' => $vid,'is_del'=>0))->getField('pay_status');

            /* if ($pay_status == 1) {
                 $this->mzError("课程订单未支付，请先到个人中心—订单操作！");
             }else*/
            if ($pay_status == 3) {
                $this->mzError("已购买此课程！");
            } else if ($pay_status == 1 || $pay_status == 2 || !$pay_status) {
                $i = D('ZyService')->buyOnlineVideo(intval($this->mid), $vid,$ext_data,$pay_video_mount_school);
            }
            if ($i === true) {
                // 记录购买的课程的ID
                session('purchase_course_operation', time() + 15);
                $this->mzSuccess('成功');
            } else if ($i === 1) {
                $this->mzError('该课程你不需要购买');
            } else if ($i === 2) {
                $this->mzError('找不到课程');
            } else if ($i === 4) {
                $this->ajaxReturn(null, "创建订单失败", 9);
            } else if ($i === 5) {
                $this->mzError('平台分成比例不存在');
            } else if ($i === 6) {
                $this->mzError('课程不属于机构');
            } else if ($i === 7) {
                $this->mzError('该课程机构不存在');
//            } else if ($i === 8) {
//                $this->mzError('课程下讲师不存在');
//            } else if ($i === 9) {
//                $this->mzError('机构与教师分成比例不存在');
            } else if ($i === 10) {
                $this->mzError('机构分成比例不存在');//机构与挂载分成比例不存在
            } else if ($i === 11) {
                $this->mzError('您所属的相应机构不存在');//购买用户的相应机构管理员不存在
            } else if ($i === 12) {
                $this->mzError('课程有效期不存在');//课程订单有效期不存在
            } else if ($i === 13) {
                $this->ajaxReturn(null, "创建订单失败!", 9);
            }
        } else if ($check_type == 'zy_album') {
            if($coupon_id && $dis_type){
                $ext_data = [
                    'coupon_id' => $coupon_id,
                    'dis_type' => $dis_type,
                    'price' => $money,
                ];
            }else {
                $price = D("Album")->getAlbumOneInfoById($vid,'price');
                if (bccomp($money, $price)) {
                    $this->mzError("请勿篡改金额");
                }
            }
            $pay_status = M('zy_order_album')->where(array('uid' => intval($this->mid), 'album_id' => $vid))->getField('pay_status');

            /*if ($pay_status == 1) {
                $this->mzError("班级订单未支付，请先到个人中心—订单操作！");
            } else*/
            if ($pay_status == 3) {
                $this->mzError("已购买此班级！");
            } else if ($pay_status == 1 || $pay_status == 2 || !$pay_status) {
                $i = D('ZyService')->buyOnlineAlbum(intval($this->mid), $vid, $ext_data);
            }

            if ($i === true) {
                session('purchase_album_operation', time() + 15);
                $this->mzSuccess('成功');
            } else
//            if ($i === 1) {
//                $this->mzError('该班级你不需要购买');
//            } else
                if ($i === 2) {
                    $this->mzError('找不到班级');
                } else if ($i === 3) {
                    $this->mzError('班级下没有课程');
                } else if ($i === 4) {
                    $this->ajaxReturn(null, "创建订单失败", 9);
                } else if ($i === 5) {
                    $this->mzError('平台分成比例不存在');//平台与机构分成比例不存在
                } else if ($i === 6) {
                    $this->mzError('班级不属于机构');
                } else if ($i === 7) {
                    $this->mzError('该班级机构不存在');//班级所绑定的机构管理员不存在
                } else if ($i === 8) {
                    $this->mzError('班级中包含有 没有相关讲师用户的课程');
                } else if ($i === 9) {
                    $this->mzError('班级中包含有与班级的机构不一致的课程');
//            } else if ($i === 10) {
//                $this->mzError('机构与教师分成不存在');
                } else if ($i === 11) {
                    $this->mzError('您所属的相应机构不存在');//购买用户的相应机构管理员不存在
                } else if ($i === 12) {
                    $this->ajaxReturn(null, "创建订单失败!", 9);
                }
        } else if ($check_type == 'zy_live') {
            if($coupon_id && $dis_type){
                $ext_data = [
                    'coupon_id' => $coupon_id,
                    'dis_type' => $dis_type,
                    'price' => $money,
                ];
            }else {
                $price = D('ZyVideo')->where(array('id' => $vid))->getField('t_price');
                if (bccomp($money, $price)) {
                    $this->mzError("请勿篡改金额");
                }
            }

            $pay_status = M('zy_order_live')->where(array('uid' => intval($this->mid), 'live_id' => $vid,'is_del'=>0))->getField('pay_status');

            /*if ($pay_status == 1) {
                $this->mzError("直播课程订单未支付，请先到个人中心—订单操作！！");
            } else*/
            if ($pay_status == 3) {
                $this->mzError("已购买此直播课程！");
            } else if ($pay_status == 1 || $pay_status == 2 || !$pay_status) {
                $i = D('ZyService')->buyOnlineLive(intval($this->mid), $vid,$ext_data,$pay_video_mount_school);
            }

            if ($i === true) {
                session('purchase_live_operation', time() + 15);
                $this->mzSuccess('成功');
            } else
//            if ($i === 1) {
//                $this->mzError('该直播课堂你不需要购买');
//            } else
                if ($i === 2) {
                    $this->mzError('找不到直播课堂');
                }
                else if ($i === 3) {
                    $this->mzError('直播课程下没有课时');
                }
                else if ($i === 4) {
                    $this->ajaxReturn(null, "创建订单失败", 9);
                } else if ($i === 5) {
                    $this->mzError('平台分成自营比例不存在');//平台与机构分成自营比例不存在
                } else if ($i === 6) {
                    $this->mzError('直播课程不属于机构');
                } else if ($i === 7) {
                    $this->mzError('该直播课程机构不存在');//直播课程所绑定的机构管理员不存在
                } else if ($i === 8) {
                    $this->mzError('直播课程中包含有 没有相关讲师用户的课程');
//                } else if ($i === 9) {
//                    $this->mzError('机构与教师分成不存在');
                } else if ($i === 10) {
                    $this->mzError('机构分成比例不存在');//机构数据里的 机构与挂载机构分成不存在
                } else if ($i === 11) {
                    $this->mzError('您所属的相应机构不存在');//购买用户的相应机构管理员不存在
                } else if ($i === 12) {
                    $this->ajaxReturn(null, "创建订单失败!", 9);
                }
        } else {
            $this->mzError('请勿篡改购买类型，我们会发现哦');
        }
    }

    /**
     * 加入看单 （订单）
     */
    public function add_order()
    {

        $video_id = intval($_POST['vid']);
        $vtype = t($_POST['vtype']);
        $coupon_id = intval($_POST['coupon_id']);
        if (!$this->mid) {
            $this->mzError("请先登录");
        }
        if ($vtype == 'zy_video') {
            //取得课程
            $video = M('zy_video')->where(array(
                'id' => $video_id,
                'is_del' => 0,
                'is_activity' => 1,
                'type' => 1,
                'listingtime' => array('lt', time()),
            ))->field("id,uid,video_title,mhm_id,teacher_id,v_price,t_price,vip_level,is_charge,
            endtime,starttime,limit_discount,term")->find();
            //找不到课程
            if (!$video) {
                $this->mzError("找不到该课程");
            }

            $is_buy = D('ZyOrderCourse','classroom')->isBuyVideo($this->mid ,$video_id );
            if($is_buy){
                $this->mzError("您可以直接学习该课程");
            }

            //查询教师用户uid
            $teacher_uid = M('zy_teacher')->where(array('id' => $video['teacher_id']))->getField('uid');
            $teacher_uid = M('user')->where(array('uid' => $teacher_uid))->getField('uid');


            //取得价格
            $prices = getPrice($video, $this->mid, false, true);
            $prices['price'] = floatval($prices['price']);
            $mid = $this -> mid;
            $tid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
            if($mid == intval($video['uid']) || $tid == $video['teacher_id'] || $video['is_charge'] == 1)
            {
                $prices['price'] = 0;
            }

            if(!$coupon_id){
                if ($prices['price'] != 0  && $video['is_charge'] != 1) {
                    $this->mzError("该课程为收费课程");
                }
                $prices['now_price'] = $prices['price'];
            }else{
                $prices['now_price'] = 0;
            }

            $data = array(
                'uid' => $this->mid,
                'muid' => $teacher_uid,
                'video_id' => $video['id'],
                'old_price' => $prices['price'],//10
                'discount' => round($prices['oriPrice'] - $prices['price'], 2),
                'discount_type' => 3,
                'price' => $prices['now_price'],
                'coupon_id' => $coupon_id,
                'order_album_id' => 0,
                'learn_status' => 0,
                'ctime' => time(),
                'order_type' => 0,
                'is_del' => 0,
                'pay_status' => 3,
                'term' => $video['term'],
                'time_limit' => time() + 129600 * floatval($video['term']),
                'mhm_id' => $video['mhm_id'],
//                'coupon_id' => isset($ext_data['coupon_id']) ? intval($ext_data['coupon_id']) : 0,
            );
            $id = M('zy_order_course')->add($data);
            if ($id) {
                if($coupon_id){
                    $data['status'] = 1;
                    M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon_id))->save($data);
                }
                $this->mzSuccess("加入成功");
            } else {
                $this->mzError("加入失败");
            }
        } else if ($vtype == 'zy_live') {
            //取得直播课程
            $live_info = D('ZyVideo')->where(array(
                'id'          => $video_id,
                'is_del'      => 0,
                'is_activity' => 1,
                'type'        => 2,
                'listingtime' => array('lt', time()),
                'uctime' => array('gt', time()),
            ))->field("id,video_title,mhm_id,t_price,v_price,is_charge,teacher_id,uid,
            listingtime,uctime,live_type")->find();

            //找不到直播课程
            if (!$live_info){
                $this->mzError("找不到该直播课程");
            }

            $is_buy = D('ZyOrderLive','classroom')->isBuyLive($this->mid ,$video_id );
            if($is_buy){
                $this->mzError("您可以直接学习该直播课程");
            }

            //如果为管理员/机构管理员自己机构的课程 则免费
            if(is_admin($this->mid) || $live_info['is_charge'] == 1) {
                $live_info['t_price'] = 0;
            }
            if(is_school($this->mid) == $live_info['mhm_id']){
                $live_info['t_price'] = 0;
            }

            //如果是讲师自己的课程 则免费
            $mid = $this -> mid;
            $tid =  M('zy_teacher')->where('uid ='.$mid)->getField('id');
            if($mid == intval($live_info['uid']) || $tid == $live_info['teacher_id'])
            {
                $live_info['t_price'] = 0;
            }

            if($live_info['live_type']  ==1)
            {
                $livetall =M('zy_live_zshd') -> where(array('live_id'=>$live_info['id'],'is_del'=> 0,'is_active'=>1))-> field('speaker_id') ->select();
            }
            if($live_info['live_type']  ==3)
            {
                $livetall =M('zy_live_gh') -> where(array('live_id'=>$live_info['id'],'is_del'=> 0,'is_active'=>1))-> field('speaker_id') ->select();
            }
            if($live_info['live_type']  ==4)
            {
                $livetall =M('zy_live_cc') -> where(array('live_id'=>$live_info['id'],'is_del'=> 0,'is_active'=>1))-> field('speaker_id') ->select();
            }
            if($tid) {
                $tids = trim(implode(',', array_unique(getSubByKey($livetall, 'speaker_id'))), ',');
                $tids = "," . $tids . ',';

                $chtid = ',' . $tid . ',';

                if (strstr($tids, $chtid)) {
                    $live_info['t_price'] = 0;
                }
            }

            if(!$coupon_id) {
                if ($live_info['t_price'] != 0) {
                    $this->mzError("该直播课程为收费课程");
                }
                $live_info['now_price'] = $live_info['t_price'];
            }else{
                $live_info['now_price'] = 0;
            }

            //无过期非法信息则生成状态为已支付的订单数据
            $data = array(
                'uid'           => $this->mid,
                'live_id'       => $video_id,
                'old_price'     => $live_info['t_price'],
                'discount'      => round($live_info['v_price'] - $live_info['t_price'],2),
                'discount_type' => 3,
                'price'         => $live_info['now_price'],
                'order_album_id'=> 0,
                'learn_status'  => 0,
                'ctime'         => time(),
                'is_del'        => 0,
                'pay_status'    => 3,
                'mhm_id'        => $live_info['mhm_id'],
                'coupon_id'     => $coupon_id,
                'rel_id'        => 0,
            );
            $id = D('ZyOrderLive')->add($data);
            if ($id) {
                if($coupon_id){
                    $data['status'] = 1;
                    M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon_id))->save($data);
                }
                $this->mzSuccess("加入成功");
            } else {
                $this->mzError("加入失败");
            }
        }else if($vtype = 'zy_album'){
            $album = D("Album")->getAlbumOneInfoById($video_id,'id,price,mhm_id,album_title');
            //找不到直播课程
            if (!$album){
                $this->mzError("找不到该班级课程");
            }

            $is_buy = D('ZyOrderAlbum')->isBuyAlbum($this->mid ,$video_id );
            if($is_buy){
                $this->mzError("您可以直接学习该直播课程");
            }
            if(!$coupon_id){
                if($album['price'] != 0){
                    $this->mzError("该班级课程为收费课程");
                }
                $album['now_price'] = $album['price'];
            }else{
                $album['now_price'] = 0;
            }
            $oPrice = 0.00;
            foreach ($album as $key => $video) {
                $oPrice += $video['t_price'];
            }

            //无过期非法信息则生成状态为已支付的订单数据
            $data = array(
                'uid'           => $this->mid,
                'album_id'      => $video_id,
                'old_price'     => $album['price'],
                'discount'      => round($oPrice-$album['price'],2),
                'discount_type' => 3,
                'price'         => $album['now_price'],
                'learn_status'  => 0,
                'ctime'         => time(),
                'is_del'        => 0,
                'pay_status'    => 3,
                'mhm_id'        => $album['mhm_id'],
            );
            $id = D('ZyOrderAlbum')->add($data);
            if ($id) {
                if($coupon_id){
                    $data['status'] = 1;
                    M('coupon_user')->where(array('uid'=>$this->mid,'cid'=>$coupon_id))->save($data);
                }
                $this->mzSuccess("加入成功");
            } else {
                $this->mzError("加入失败");
            }
        }
    }

    /**
     * 购买课程（点播、直播、班级）
     */
    public function payLibrary(){
        if($_SERVER['REQUEST_METHOD']!='POST') exit;

        //使用后台提示模版
        $this->assign('isAdmin', 1);
        $check_type = t($_POST['check_type']);

        //必须要先登录才能进行操作
        if(!$this->mid){
            $this->error('请先登录再进行购买');
        }

        $vid = intval($_POST['vid']);
        if(!$vid){
            $this->error("参数错误");
        }

        $pay_list = array('alipay','unionpay','wxpay');
        if(!in_array($_POST['pay'],$pay_list)){
            $this->error('支付方式错误');
        }

        $money = floatval($_POST['money']);
        $title = t($_POST['title']);
        $coupon_id = intval($_POST['coupon_id']);

        if($money <= 0){
            $this->error('请选择或填写购买金额');
        }
        $rechange_base = getAppConfig('rechange_basenum');
        /*if($rechange_base>0 && $money%$rechange_base != 0){
            if($rechange_base == 1){
                $this->error('购买金额必须为整数');
            }else{
                $this->error("购买金额必须为{$rechange_base}的倍数");
            }
        }*/

        if($check_type == 'zy_video'){
            if(!$coupon_id){
                $data = D('ZyVideo')->where(array('id' => $vid))->find();

                $old_price = getPrice($data, $this->mid, true, true);
                if (bccomp($money, $old_price['price'])) {
                    $this->mzError("请勿篡改金额");
                }
            }
        } else if ($check_type == 'zy_album') {
            $price = D("Album")->getAlbumOneInfoById($vid, 'price');
            if (bccomp($money, $price)) {
                $this->mzError("请勿篡改金额");
            }
        } else if ($check_type == 'zy_live') {
            if (!$coupon_id) {
                $price = D('ZyVideo')->where(array('id' => $vid))->getField('t_price');
                if (bccomp($money, $price)) {
                    $this->mzError("请勿篡改金额");
                }
            }
        }

      //  $money = 0.01;

        $re = D('ZyRecharge');
        $id = $re->addRechange(array(
            'uid'      => $this->mid,
            'type'     => 1,
            'money'    => $money,
            'note'     => "{$this->site['site_keyword']}在线教育-购买{$title}",
            'pay_type' => $_POST['pay'],
        ));
        if(!$id) $this->error("操作异常");
        if($_POST['pay'] == 'alipay'){
            $this->alipay(array(
                'vid'          => intval($_POST['vid']),
                'vtype'        => $check_type,
                'coupon_id'    => $coupon_id ? : 0,
                'out_trade_no' => $id,
                'total_fee'    => $money,
                'subject'      => "{$this->site['site_keyword']}在线教育-购买{$title}",
            ));
        }elseif($_POST['pay'] == 'unionpay'){
            $this->unionpay(array(
                'vid'       => intval($_POST['vid']),
                'vtype'     => $check_type,
                'coupon_id' => $coupon_id ? : 0,
                'id'        => $id,
                'money'     => $money,
                'subject'   => "{$this->site['site_keyword']}在线教育-购买{$title}",
            ));
        }elseif($_POST['pay'] == 'wxpay'){
            $res = $this->wxpay(array(
                'vid'           => intval($_POST['vid']),
                'vtype'         => $check_type,
                'coupon_id'     => $coupon_id ? : 0,
                'out_trade_no'  => $id,
                'total_fee'     => $money * 100,//单位：分
                'subject'       => "{$this->site['site_keyword']}在线教育-购买{$title}",
            ));
            if($res){
                $this->assign('url',$res);
                $html = $this->fetch('wxpay');
                $data = array('status'=>1,'data'=>['html'=>$html,'trade_no'=>$id]);
                echo json_encode($data);
                exit;
            }
        }
    }

    /**
     * @name 阿里支付
     * @packages protected
     */
    protected function alipay($args){
		
		
		echo $this->is_wap;
		exit();
		
      
    }

    /**
     * @name 阿里支付回调 服务器异步通知页面路径
     * @packages public
     */
    public function alinu(){
        file_put_contents('alipayre.txt',json_encode($_POST));
        tsload(implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v3','AopClient.php')));
        $aop = new AopClient;
        $aop->alipayrsaPublicKey = model('Xdata')->get('admin_Config:alipay')['public_key'];
        //此处验签方式必须与下单时的签名方式一致
        $_POST = json_decode(file_get_contents('alipayre.txt'),true);
        $verify_result = $aop->rsaCheckV1($_POST, NULL, "RSA");
        if(!$verify_result) exit('fail');
        file_put_contents('alipayre_success.txt',json_encode($_POST));

        //商户订单号
        $out_trade_no = stristr($_POST['out_trade_no'],'h',true);
        //支付宝交易号
        $trade_no = $_POST['trade_no'];
        //交易状态
        $trade_status = $_POST['trade_status'];

        //自定义数据
        $extra_common_param = json_decode(urldecode($_POST['passback_params']),true);

        $vid = intval($extra_common_param['vid']);
        $vtype = $extra_common_param['vtype'];
        $coupon_id = $extra_common_param['coupon_id'];

        $re = D('ZyRecharge');
        if ($trade_status == 'TRADE_FINISHED') {
            $result = $re->setNormalPaySuccess2($out_trade_no,$trade_no);

            if($result){
                $this_uid = $re->where('id = '.$out_trade_no)->getField('uid');
                //查询订单支付类型
                if($vtype == 'zy_video'){
                    $pay_status = M('zy_order_course')->where(array('uid'=>$this_uid,'video_id'=>$vid))->getField('pay_status');
                }elseif($vtype == 'zy_album'){
                    $pay_status = M('zy_order_album')->where(array('uid'=>$this_uid,'album_id'=>$vid))->getField('pay_status');
                }elseif($vtype == 'zy_live'){
                    $pay_status = M('zy_order_live')->where(array('uid'=>$this_uid,'live_id'=>$vid))->getField('pay_status');
                }

                if($pay_status == 3){
                    if($coupon_id){
                        M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                    }
                    echo '购买成功';
                }else{
                    $order_info = $this->buyOperating($vid,$out_trade_no,$extra_common_param['vtype']);
                    if($order_info == 1){
                        if($coupon_id){
                            M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                        }
                        echo '购买成功';
                    }else{
                        echo '购买失败';
                    }
                }
            }else{
                echo '购买失败!';
            }
        }elseif($trade_status == 'TRADE_SUCCESS'){
            $result = $re->setNormalPaySuccess2($out_trade_no,$trade_no);

            if($result){
                $this_uid = $re->where('pay_order = '.$trade_no)->getField('uid');
                //查询订单支付类型
                if($vtype == 'zy_video'){
                    $pay_status = M('zy_order_course')->where(array('uid'=>$this_uid,'video_id'=>$vid))->getField('pay_status');
                    $credit = M('credit_setting')->where(array('id'=>2,'is_open'=>1))->field('id,name,score,count')->find();
                    if($credit['score'] > 0){
                        $type = 6;
                        $note = '购买课程获得的积分';
                    }
                }elseif($vtype == 'zy_album'){
                    $pay_status = M('zy_order_album')->where(array('uid'=>$this_uid,'album_id'=>$vid))->getField('pay_status');
                    $credit = M('credit_setting')->where(array('id'=>16,'is_open'=>1))->field('id,name,score,count')->find();
                    if($credit['score'] > 0){
                        $type = 6;
                        $note = '购买班级获得的积分';
                    }
                }elseif($vtype == 'zy_live'){
                    $pay_status = M('zy_order_live')->where(array('uid'=>$this_uid,'live_id'=>$vid))->getField('pay_status');
                    $credit = M('credit_setting')->where(array('id'=>10,'is_open'=>1))->field('id,name,score,count')->find();
                    if($credit['score'] > 0){
                        $type = 6;
                        $note = '购买直播获得的积分';
                    }
                }

                if($pay_status == 3){
                    if($coupon_id){
                        M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                    }
                    model('Credit')->addUserCreditRule($this_uid,$type,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
                    echo '购买成功';
                }else{
                    $order_info = $this->buyOperating($vid,$out_trade_no,$extra_common_param['vtype']);
                    if($order_info == 1){
                        if($coupon_id){
                            M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                        }
                        model('Credit')->addUserCreditRule($this_uid,$type,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
                        echo '购买成功';
                    }else{
                        echo '购买失败';
                    }
                }
            }else{
                echo '购买失败!';
            }
        }
        echo 'success';
        exit;

        file_put_contents('alipayre.txt',json_encode($_POST));
        $alipay_config = $this->getAlipayConfig();
        //引入类
        tsload(join(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v2','AlipayNotify.php')));
        //初始化
        $alipayNotify = new \AlipayNotify($alipay_config);
        //验证结果
        $verify_result = $alipayNotify->verifyNotify();
        if(!$verify_result) exit('fail');
        file_put_contents('alipay_success.txt',json_encode($_POST));
        //商户订单号
        $out_trade_no = stristr($_POST['out_trade_no'],'h',true);
        //支付宝交易号
        $trade_no = $_POST['trade_no'];
        //交易状态
        $trade_status = $_POST['trade_status'];

        //自定义数据
        $extra_common_param = json_decode(urldecode($_POST['extra_common_param']),true);
    }

    /**
     * @name 阿里支付回调 页面跳转同步通知页面路径
     * @packages public
     */
    public function aliru()
    {
        unset($_GET['app'], $_GET['mod'], $_GET['act']);
        $alipay_config = $this->getAlipayConfig();
        //引入类
        tsload(join(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v2','AlipayNotify.php')));
        //初始化
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();
        $this->assign('isAdmin', 1);

        //自定义数据
        if($this->is_wap){
            $extra_common_param = json_decode(urldecode($_GET['extra_common_param_w3g']),true);
        }else{
            $extra_common_param = json_decode(urldecode($_GET['extra_common_param']),true);
        }

        $vid = intval($extra_common_param['vid']);
        $vtype = $extra_common_param['vtype'];
        $coupon_id = $extra_common_param['coupon_id'];

        if(!$this->is_wap){
            if($vtype == 'zy_video'){
                $this->assign('jumpUrl', U('classroom/Video/view',array('id'=>$vid)));
            }elseif($vtype == 'zy_album'){
                $this->assign('jumpUrl', U('classroom/Album/view',array('id'=>$vid)));
            }elseif($vtype == 'zy_live'){
                $this->assign('jumpUrl', U('live/Index/view',array('id'=>$vid)));
            }
        }

//        if(!$verify_result) $this->error('操作异常');
        //商户订单号
        $out_trade_no = stristr($_GET['out_trade_no'],'h',true);
        //支付宝交易号
        $trade_no = $_GET['trade_no'];
        //交易状态
        $trade_status = $_GET['trade_status'];
        if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
            $result = D('ZyRecharge')->setNormalPaySuccess2($out_trade_no,$trade_no);

            if($result){
                //查询订单支付类型
                if($vtype == 'zy_video'){
                    $pay_status = M('zy_order_course')->where(array('uid'=>intval($this->mid),'video_id'=>$vid))->getField('pay_status');
                    $credit = M('credit_setting')->where(array('id'=>2,'is_open'=>1))->field('id,name,score,count')->find();
                    if($credit['score'] > 0){
                        $type = 6;
                        $note = '购买课程获得的积分';
                    }
                }elseif($vtype == 'zy_album'){
                    $pay_status = M('zy_order_album')->where(array('uid'=>intval($this->mid),'album_id'=>$vid))->getField('pay_status');
                    $credit = M('credit_setting')->where(array('id'=>16,'is_open'=>1))->field('id,name,score,count')->find();
                    if($credit['score'] > 0){
                        $type = 6;
                        $note = '购买班级获得的积分';
                    }
                }elseif($vtype == 'zy_live'){
                    $pay_status = M('zy_order_live')->where(array('uid'=>intval($this->mid),'live_id'=>$vid))->getField('pay_status');
                    $credit = M('credit_setting')->where(array('id'=>10,'is_open'=>1))->field('id,name,score,count')->find();
                    if($credit['score'] > 0){
                        $type = 6;
                        $note = '购买直播获得的积分';
                    }
                }

                if($pay_status == 3){
                    if($coupon_id){
                        M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                    }
                    if($this->is_wap){
                        if($vtype == 'zy_video'){
                            header("Location: ".U('classroom/Video/view',array('id'=>$vid)));
                        }elseif($vtype == 'zy_album'){
                            header("Location: ".U('classroom/Album/view',array('id'=>$vid)));
                        }elseif($vtype == 'zy_live'){
                            header("Location: ".U('live/Index/view',array('id'=>$vid)));
                        }
                    }
                    model('Credit')->addUserCreditRule($this->mid,$type,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
                    $this->success('购买成功');
                }else{
                    $order_info = $this->buyOperating($vid,$out_trade_no,$vtype);
                    if($order_info == 1){
                        if($coupon_id){
                            M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                        }
                        if($this->is_wap){
                            if($vtype == 'zy_video'){
                                header("Location: ".U('classroom/Video/view',array('id'=>$vid)));
                            }elseif($vtype == 'zy_album'){
                                header("Location: ".U('classroom/Album/view',array('id'=>$vid)));
                            }elseif($vtype == 'zy_live'){
                                header("Location: ".U('live/Index/view',array('id'=>$vid)));
                            }
                        }
                        model('Credit')->addUserCreditRule($this->mid,$type,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
                        $this->success('购买成功');
                    }else{
                        if($this->is_wap){
                            if($vtype == 'zy_video'){
                                header("Location: ".U('classroom/Video/view',array('id'=>$vid)));
                            }elseif($vtype == 'zy_album'){
                                header("Location: ".U('classroom/Album/view',array('id'=>$vid)));
                            }elseif($vtype == 'zy_live'){
                                header("Location: ".U('live/Index/view',array('id'=>$vid)));
                            }
                        }
                        $this->success('购买失败');
                    }
                }
            }else{
                if($this->is_wap){
                    if($vtype == 'zy_video'){
                        header("Location: ".U('classroom/Video/view',array('id'=>$vid)));
                    }elseif($vtype == 'zy_album'){
                        header("Location: ".U('classroom/Album/view',array('id'=>$vid)));
                    }elseif($vtype == 'zy_live'){
                        header("Location: ".U('live/Index/view',array('id'=>$vid)));
                    }
                }
                $this->error('购买失败!');
            }
        }
    }

    /**
     * 购买课程成功 修改购买支付状态以及生成分成明细、每个人分成
     */
    public function buyOperating($vid,$out_trade_no,$vtype){
        $data['ptime']      = time();
        $data['pay_status'] = 3;
        $data['rel_id']     = $out_trade_no ? $out_trade_no : 0;
        $this_mid = D('ZyRecharge')->where('id = '.$out_trade_no)->getField('uid');

        //修改订单支付类型并更新订单数量
        if($vtype == 'zy_video') {
            M('zy_order_course')->where(array('uid'=>intval($this_mid),'video_id'=>$vid))->save($data);
            $pay_status = M('zy_order_course')->where(array('uid'=>intval($this_mid),'video_id'=>$vid))->getField('pay_status');

            M('zy_video')->where(array('id' => $vid))->setInc('video_order_count');
        }elseif($vtype == 'zy_album'){
            M('zy_order_album')->where(array('uid'=>intval($this_mid),'album_id'=>$vid))->save($data);
            $pay_status = M('zy_order_album')->where(array('uid'=>intval($this_mid),'album_id'=>$vid))->getField('pay_status');

            M('album')->where(array('id' => $vid))->setInc('order_count');
            $video_ids      = trim(D("Album")->getVideoId($vid), ',');
            $a_map['id']      = array('in', array($video_ids));
            M('zy_video')->where($a_map)->setInc('video_order_count');
        }elseif($vtype == 'zy_live') {
            M('zy_order_live')->where(array('uid'=>intval($this_mid),'live_id'=>$vid))->save($data);
            $pay_status = M('zy_order_live')->where(array('uid'=>intval($this_mid),'live_id'=>$vid))->getField('pay_status');

            M('zy_video')->where(array('id' => $vid))->setInc('video_order_count');
        }

//        $vid = 21;
//        $vtype = 'zy_album';
//        $pay_status = 3;
        if($pay_status == 3){

            $map['uid'] = intval($this_mid);//购买用户ID

            $v_data['status'] = 1;//分成流水订单状态
            $v_data['ltime']  = time();
            if($vtype == 'zy_video'){
                $map['vid']  = intval($vid);
                $video_order_info = M('zy_split_course')->where($map)->field('term,time_limit')->find();
                $split_video = M('zy_split_course')->where($map) ->save($v_data);
            }elseif($vtype == 'zy_album'){
                $map['aid']  = intval($vid);
                $split_video = M('zy_split_album')->where($map) ->save($v_data);
            }elseif($vtype == 'zy_live'){
                $map['lid']  = intval($vid);
                $split_video = M('zy_split_live')->where($map) ->save($v_data);
            }
            $map['status'] = 1;

//            $split_video = true;
            if($split_video){
                $s['uid']=$this_mid;
                $split = D('ZySplit');

                if($vtype == 'zy_video'){
                    //添加多条流水记录 并给分成用户加钱 通知购买用户
                    $split->addVideoFlows($map, 5, 'zy_video_order');

                    if($video_order_info['term']) {
                        $video_order_info['time_limit'] = date('Y-m-d H:i:s',$video_order_info['time_limit']);
                        $ses_info = "，该课程的有效天数为{$video_order_info['term']}天,有效期截至：{$video_order_info['time_limit']}，请您务必在有效期内尽快学习";
                    }
                    $video_info = M('zy_video')->where(array('id' => $vid))->field('video_title,teacher_id')->find();
                    $s['title'] = "恭喜您购买课程成功";
                    $s['body'] = "恭喜您成功购买课程：{$video_info['video_title']}".$ses_info;
                    $s['ctime'] = time();
                    model('Notify')->sendMessage($s);
                    return 1;//购买成功
                }elseif($vtype == 'zy_album'){
                    //添加多条流水记录 并给分成用户加钱 通知购买用户
                    $album = D("Album")->getAlbumOneInfoById($vid,'id,price,mhm_id,album_title');
                    $video_ids      = trim(D("Album")->getVideoId($vid), ',');
                    $v_map['id']        = array('in', array($video_ids));
                    $v_map["is_del"]    = 0;
                    $album_info         = M("zy_video")->where($v_map)->field("id,uid,video_title,mhm_id,teacher_id,
                                          v_price,t_price,discount,vip_level,endtime,starttime,limit_discount,type")
                        ->select();

                    $insert_live_value = "";
                    $insert_course_value = "";
                    foreach ($album_info as $key => $video) {
                        if($video['type'] == 1) {
                            $video['price'] = getPrice($video, $this_mid, true, true);
                            $is_buy = D("ZyOrderCourse")->isBuyVideo($this_mid, $video['id']);
                            if($is_buy){
                                unset($video);
                            }
                        }
                        if($video['type'] == 2) {
                            $is_buy = D("ZyOrderLive")->isBuyLive($this_mid ,$video['id'] );
                            if($is_buy){
                                unset($video);
                            }
                        }
                        $album_info[$key] = $video;
                    }
                    $album_info = array_filter($album_info);
                    $order_mhm_id = model('User')->where('uid='.$this_mid)->getField('mhm_id');
                    foreach ($album_info as $key => $video) {
                        if($video['type'] == 2){
                            $insert_live_value .= "('" . $this_mid . "','" . $video['id'] . "','" . $video['t_price'] . "','0.00','0','" . $video['t_price'] . "','" . $vid . "','0','3','". time()."','" .$album['mhm_id']."',". time() . ",'0','".$data['rel_id']."','" .$order_mhm_id."'),";
                        }else{
                            $insert_course_value .= "('" . $this_mid . "','" . $video['uid'] . "','" . $video['id'] . "','" . $video['v_price'] . "','" . ($video['price']['discount'] / 10) . "','" . $video['price']['dis_type'] . "','" . $video['price']['price'] . "','" . $vid . "','0','3','". time()."','" .$album['mhm_id']."',". time() . ",'0','".$data['rel_id']."','".$order_mhm_id."'),";
                        }
                    }
                    if($insert_live_value){
                        $live_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_live (`uid`,`live_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`mhm_id`,`ctime`,`is_del`,`rel_id`,`order_mhm_id`) VALUE " . trim($insert_live_value, ',');
                        M('zy_order_live')->execute($live_order_sql)? true : false;
                    }
                    if($insert_course_value){
                        $course_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_course (`uid`,`muid`,`video_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`mhm_id`,`ctime`,`is_del`,`rel_id`,`order_mhm_id`) VALUE " . trim($insert_course_value, ',');
                        M('zy_order_course')->execute($course_order_sql)? true : false;
                    }
                    $split->addVideoFlows($map, 5, 'zy_album_order');

                    $album_title = M('album')->where(array('id' => $vid))->getField('album_title');
                    $s['title'] = "恭喜您购买班级成功";
                    $s['body'] = "恭喜您成功购买班级：{$album_title}";
                    $s['ctime'] = time();
                    model('Notify')->sendMessage($s);
                    return 1;//购买成功
                }elseif($vtype == 'zy_live'){
                    //添加多条流水记录 并给分成用户加钱 通知购买用户
                    $split->addVideoFlows($map, 5, 'zy_live_order');
                    $video_info = M('zy_video')->where(array('id' => $vid))->field('video_title,teacher_id')->find();
                    $s['title'] = "恭喜您购买直播课堂成功";
                    $s['body'] = "恭喜您成功购买直播课堂：{$video_info['video_title']}";
                    $s['ctime'] = time();
                    model('Notify')->sendMessage($s);
                    return 1;//购买成功
                }
            }else{
                return 0;//购买失败
            }
        }else{
            return 0;//购买失败
        }
    }

    /**
     * @name 银联支付
     * @packages protected
     */
    protected function unionpay($args){
        include SITE_PATH.'/api/pay/unionpay/quickpay_service.php';
        $param['transType']     = quickpay_conf::CONSUME;  //交易类型，CONSUME or PRE_AUTH
        $param['commodityName'] = $args['subject'];
        $param['orderAmount']   = $args['money']*100;        //交易金额
        $param['orderNumber']   = $args['id']+10000000; //订单号，必须唯一
        $param['orderTime']     = date('YmdHis');   //交易时间, YYYYmmhhddHHMMSS
        $param['orderCurrency'] = quickpay_conf::CURRENCY_CNY;  //交易币种，CURRENCY_CNY=>人民币
        $param['customerIp']    = get_client_ip();//客户端的IP地址
        //$param['frontEndUrl']   = SITE_URL.'/classroom/Pay/unionru';    //前台回调URL
        //$param['backEndUrl']    = SITE_URL.'/classroom/Pay/unionnu';    //后台回调URL
        $param['frontEndUrl']   = U('classroom/Pay/unionru');    //前台回调URL
        $param['backEndUrl']    = U('classroom/Pay/unionnu');    //后台回调URL
        //print_r($param);exit;
        $pay_service = new quickpay_service($param, quickpay_conf::FRONT_PAY);
        $html = $pay_service->create_html();
        header("Content-Type: text/html; charset=" . quickpay_conf::$pay_params['charset']);
        echo $html; //自动post表单
    }

    /**
     * @name 银联支付回调
     * @packages public
     */
    public function unionru(){
        include SITE_PATH.'/api/pay/unionpay/quickpay_service.php';
        $this->assign('isAdmin', 1);
        $this->assign('jumpUrl', U('classroom/User/recharge'));
        try {
            $response = new quickpay_service($_POST, quickpay_conf::RESPONSE);
            if ($response->get('respCode') != quickpay_service::RESP_SUCCESS) {
                $err = sprintf("Error: %d => %s", $response->get('respCode'), $response->get('respMsg'));
                throw new Exception($err);
            }
            $arr_ret = $response->get_args();
            $id = $arr_ret['orderNumber']-10000000;
            $qid = $arr_ret['qid'];
            $re = D('ZyRecharge');
            $result = $re->setSuccess($id, $qid);
            if($result){
                $this->success('充值成功！');
            }else{
                $this->error('充值失败！');
            }
        }catch(Exception $exp) {
            $this->error('操作异常！');
            //$str .= var_export($exp, true);
            //die("error happend: " . $str);
        }
    }

    /**
     * @name 微信支付
     * @packages protected
     */
    protected function wxpay($data){
        $url = '';
        if($data){
            require_once SITE_PATH.'/api/pay/wxpay/WxPay.php';
            $input = new WxPayUnifiedOrder();
            $attr  = json_encode(array('vid'=>$data['vid'],'vtype'=>$data['vtype'],'coupon_id'=>$data['coupon_id']));
            $body  = isset($data['subject']) ? $data['subject'] :"{$this->site['site_keyword']}-购买";
            $out_trade_no = $data['out_trade_no'].'h'.date('YmdHis',time()).mt_rand(1000,9999);//stristr
            $input->SetBody($body);
            $input->SetAttach($attr);//自定义数据
            $input->SetOut_trade_no($out_trade_no);
            $input->SetTotal_fee($data['total_fee']);
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
            $input->SetNotify_url('http://'.$_SERVER['HTTP_HOST'].'/api/pay/wxpay/notify.php');
            $input->SetTrade_type("NATIVE");
            $input->SetProduct_id($data['out_trade_no']);
            $notify = new NativePay();
            $result = $notify->GetPayUrl($input);
            $url = $result["code_url"];
        }
        return $url;
    }

    /**
     * @name 微信回调
     */
    public function wxpay_success(){
        if($_GET['openid']){
            $re = D('ZyRecharge');
            $re->setWxPaySuccess($_GET['out_trade_no'], $_GET['transaction_id'],$_GET['attach']);
//            $da['vip_length']=$_GET['out_trade_no'];
//            $da['note']=$_GET['transaction_id'];
//            $da['pay_order']=$_GET['openid'];
//            $da['pay_type']=$_GET['attach'];
//            D('ZyRecharge')->add($da);
        }
    }

    /**
     * @name 微信查询支付状态
     */
    public function getPayStatus(){
        $id = $_POST['order'];
        $data = M('zy_recharge')->where(['id'=>$id])->find();
        if($data['status'] == 1){
            $attach = json_decode($data['note_wxpay'],true);
            $coupon_id = $attach['coupon_id'];

            $this_uid = M('zy_recharge')->where('id = '.$data['id'])->getField('uid');

            if($attach['vtype'] == 'zy_video'){
                $pay_status = M('zy_order_course')->where(array('uid'=>$this_uid,'video_id'=>intval($attach['vid'])))->getField('pay_status');
                $credit = M('credit_setting')->where(array('id'=>2,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $type = 6;
                    $note = '购买课程获得的积分';
                }
            }elseif($attach['vtype'] == 'zy_album'){
                $pay_status = M('zy_order_album')->where(array('uid'=>$this_uid,'album_id'=>intval($attach['vid'])))->getField('pay_status');
                $credit = M('credit_setting')->where(array('id'=>16,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $type = 6;
                    $note = '购买班级获得的积分';
                }
            }elseif($attach['vtype'] == 'zy_live'){
                $pay_status = M('zy_order_live')->where(array('uid'=>$this_uid,'live_id'=>intval($attach['vid'])))->getField('pay_status');
                $credit = M('credit_setting')->where(array('id'=>10,'is_open'=>1))->field('id,name,score,count')->find();
                if($credit['score'] > 0){
                    $type = 6;
                    $note = '购买直播获得的积分';
                }
            }
            if($pay_status == 3){

                model('Credit')->addUserCreditRule($this_uid,$type,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
                $info = '购买成功';
            }else{
                $order_info = $this->buyOperating(intval($attach['vid']),$data['id'],$attach['vtype']);
                if($order_info == 1){
                    if($coupon_id){
                        M('coupon_user')->where(['id'=>$coupon_id])->setField('status',1);
                    }
                    model('Credit')->addUserCreditRule($this_uid,$type,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);
                    $info = '购买成功';
                }else{
                    $info = '购买失败';
                }
            }
            $vid = intval($attach['vid']);
            $vtype = $attach['vtype'];
            if($vtype == 'zy_video'){
                $url = U('classroom/Video/view',array('id'=>$vid));
            }elseif($vtype == 'zy_album'){
                $url = U('classroom/Album/view',array('id'=>$vid));
            }elseif($vtype == 'zy_live'){
                $url = U('live/Index/view',array('id'=>$vid));
            }

            echo json_encode(['status'=>1,'info'=>$info,'data'=>$url]);exit;
        }else{
            echo json_encode(['status'=>0]);exit;
        }
    }

    /**
     * 获取阿里支付配置
     */
    protected function getAlipayConfig(){
        $config = array(
            'cacert' => join(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v2','cacert.pem')),
            'input_charset'    => strtolower('utf-8'),
            'sign_type' =>  strtoupper('RSA'),
        );
        $conf = unserialize(M('system_data')->where("`list`='admin_Config' AND `key`='alipay'")->getField('value'));
        if(is_array($conf)){
            $config = array_merge($config, array(
                'partner'   =>$conf['alipay_partner'],
                'key'       =>$conf['alipay_key'],
                'seller_email'=> $conf['seller_email'],
                'private_key_path' => $conf['private_key'],
                'ali_public_key_path'    => $conf['public_key']
            ));
        }
        return $config;
    }

}