<?php

/**
 * Eduline机构首页控制器
 * @author Ashang <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/school/Lib/Action/CommonAction.class.php');
class SchoolAction extends CommonAction
{
    protected $school;
    protected $mhm_id;
    public function _initialize()
    {
        parent::_initialize();
        if(isset($_SERVER['HTTP_X_HOST'])){
            $domain = substr($_SERVER['HTTP_X_HOST'],0,stripos($_SERVER['HTTP_X_HOST'],'.'));
            $mhm_id = model('School')->where(array('doadmin' => t($domain), 'status' => 1, 'is_del' => 0))->getField('id');
        }elseif((intval($_GET['id']))) {
            $mhm_id = model('School')->where(array('id' => $_GET['id'], 'status' => 1, 'is_del' => 0))->getField('id');
        } elseif (t($_GET['doadmin'])) {
            $mhm_id = model('School')->where(array('doadmin' => $_GET['doadmin'], 'status' => 1, 'is_del' => 0))->getField('id');
        }
        if (!$mhm_id) {
            header('HTTP/1.1 404 Not Found');exit;
        } else {
            $this->school = model('School')->getSchoolInfoById($mhm_id);
            $this->mhm_id = $mhm_id;
        }

        $host = '';
        //处理泛域名
        if(isset($_SERVER['HTTP_X_HOST'])){
            // 拼接地址
            $config = model ( 'Xdata' )->get( "school_AdminDomaiName:domainConfig" );
            if(!$config){
                // 默认
                $config = ['openHttps'=>0,'domainConfig'=>1];
            }
            $host =  ($config['openHttps'] ? 'https://' : 'http://').$_SERVER['HTTP_X_HOST'];

        }
        //广告图
        $adList = unserialize($this->school['banner']);
        $this->assign('ad_list', $adList);
        $this->assign('SITE_URL',$host ?:SITE_URL);

    }
    /**
     * Eduline机构首页方法
     * @return void
     */
    public function index()
    {
        $school = $this->school;
        $mhm_id = $this->mhm_id;

        // 获取首页配置
        $template       = $school['template'] ? json_decode($school['template'], true) : [];
        $template_items = $template ? $template['items'] : [];
        $this->assign('theme_items', $template_items);
        // 解析模板
        $tpl = $template ? ($template['tpl'] != 'index' ? "theme/" . $template['tpl'] : 'index') : "theme/theme_x1";

        //设置seo详情
        $this->seo = model('Seo')->installSeo(['_title' => $school['title'], '_keywords' => $school['info']], $this->seo);

        if (!$this->is_pc) {
            //机构评价（课程）
            $schoolmap['mhm_id']      = $school['school_id'];
            $schoolmap['is_del']      = 0;
            $schoolmap['is_activity'] = 1;
            $videoid                  = M('zy_video')->where($schoolmap)->field('id')->select();

            $live_id        = trim(implode(',', array_unique(getSubByKey($videoid, 'id'))), ',');
            $vmap['oid']    = ['in', $live_id];
            $vmap['is_del'] = 0;

            //机构评价（讲师）
            $ostar            = M('zy_review')->where($vmap)->avg('star');
            $tidmap['mhm_id'] = $school['school_id'];
            $tidmap['is_del'] = 0;
            $tids             = M('zy_teacher')->where($tidmap)->field('id')->select();
            $tid              = trim(implode(',', array_unique(getSubByKey($tids, 'id'))), ',');

            $vtmap['tid']    = ['in', $tid];
            $vtmap['is_del'] = 0;

            $tstar                    = M('zy_review')->where($vtmap)->avg('star');
            $star                     = ceil(($tstar + $ostar) / 2 / 20) * 20;
            $school['favorable_rate'] = round($star, 2) . '%' ?: 0;
            $school['star']           = $star;
            $school['review_count']   = M('zy_review')->where($vtmap)->count();
        }
        //机构域名
        $school['domain'] = getDomain($school['doadmin'], $mhm_id);
        $this->assign('school', $school);

        //机构优惠券显示
        if (!$template_items || in_array('coupon', $template_items)) {
            $coupon_model = model('Coupon')->setLoginMid($this->mid);
            $limit              = 7;
            $order              = 'ctime desc';
            $time               = time();
            $map['status']      = 1;
            $map['is_del']      = 0;
            $map['sid']         = $mhm_id;
            $map['end_time']    = array('gt', $time);
            $map['coupon_type'] = 0;
            // 课程卡
            $map['type']  = 5;
            //$video_coupon = model('Coupon')->getList($map, $order, $limit);
            $video_coupon = $coupon_model->getSchoolCardList($map, $order, $limit);
            // 查询课程名称
            foreach ($video_coupon['data'] as $key => $value) {
                $video_coupon['data'][$key]['video_title'] = M('zy_video')->where(array('id' => $value['video_id']))->getField('video_title');
            }
            $this->assign('video_coupon', $video_coupon['data']);

            // 打折卡
            $map['type'] = 2;
            unset($order);
            $order              = 'discount asc';
            //$discount    = model('Coupon')->getList($map, $order, $limit);
            $discount = $coupon_model->getSchoolCardList($map, $order, $limit);

            $this->assign('discount', $discount['data']);

            // 会员卡
            $map['type'] = 3;
            unset($order);
            $order              = 'vip_grade asc';
            //$vip_card    = model('Coupon')->getList($map, $order, $limit);
            $vip_card = $coupon_model->getSchoolCardList($map, $order, $limit);

            foreach ($vip_card['data'] as $k => $v) {
                $vip_card['data'][$k]['vip_grade'] = M('user_vip')->where(array('id' => $v['vip_grade']))->getField('title');
            }
            $this->assign('vip_card', $vip_card['data']);

            // 充值卡
            // $map['type'] = 4;
            // $recharge = model('Coupon')->getList($map,$order,$limit);
            // $this->assign('recharge',$recharge['data']);

            // 优惠券
            $map['type']     = 1;
            unset($order);
            $order              = 'maxprice asc,price desc';
            //$discount_coupon = model('Coupon')->getList($map, $order, $limit);
            $discount_coupon = $coupon_model->getSchoolCardList($map, $order, $limit);
            $this->assign('discount_coupon', $discount_coupon['data']);

        }

        // 机构课程条件组装
        $mount_map['uid']         = $school['uid'];
        $mount_map['mhm_id']      = $mhm_id;
        $mount_map['is_activity'] = 1;
        $mount_map['is_del']      = 0;
        $mount_id                 = M('zy_video_mount')->where($mount_map)->field('vid')->select();
        $mount_ids                = implode(',', getSubByKey($mount_id, 'vid'));
        if ($mount_ids) {
            $mhmWhere['_string'] = " (`mhm_id` = {$mhm_id} ) or (`id` IN ({$mount_ids})) ";
        } else {
            $mhmWhere['mhm_id'] = $mhm_id;
        }
        M('school')->where(array('id' => $mhm_id))->setInc('visit_num');
        $mhmWhere['listingtime'] = array('lt', time());
        $mhmWhere['uctime']      = array('gt', time());
        $mhmWhere['is_activity'] = 1;
        $mhmWhere['is_mount']    = 1;
        $mhmWhere['is_del']      = 0;
        // 点播课程处理
        if (!$template_items || in_array('video', $template_items)) {
            $mhmWhere['type'] = 1;
            $data             = D('ZyVideo', 'classroom')->where($mhmWhere)->field('fullcategorypath,id')->order(
                'video_order_count desc,video_score desc,video_collect_count desc')->limit(1000)->select();
            $count['cate'] = D('ZyVideo', 'classroom')->where($mhmWhere)->count();
            //点播课程列表
            $id   = $data ? getSubByKey($data, 'id') : [];
            $cate = [];
            if ($id) {
                $maps['id'] = array('in', $id);
                $cate       = D('ZyVideo', 'classroom')->where($maps)->limit(8)->select();
                foreach ($cate as $items => $va) {
                    $mount_1and2 = M('zy_video_mount')->where(['vid' => $va['id'], 'mhm_id' => $mhm_id])->getField('vid');
                    if ($mount_1and2) {
                        $cate[$items]['mount_iand'] = 1;
                    }
                    $cate[$items]['mzprice']            = getPrice($va, $this->mid, true, true, $va['type']);
                    $cate[$items]['mzprice']['t_price'] = $cate[$items]['mzprice']['price'];

                    $teacher                  = M('zy_teacher')->where(array('id' => $va['teacher_id']))->find();
                    $cate[$items]['tea_name'] = $teacher['name'];

                    $mhmData = model('School')->where('id=' . $va['mhm_id'])->field('doadmin,title')->find();
                    //机构域名
                    $cate[$items]['domain']    = getDomain($mhmData['doadmin'], $va['mhm_id']);
                    $cate[$items]['mhm_title'] = $mhmData['title'];
                }
            }

            $this->assign('cate', $cate);
        }
        // 直播课程处理
        if (!$template_items || in_array('live', $template_items)) {
            $mhmWhere['type'] = 2;
            if (stripos($tpl, 'theme_x2') !== false) {
                $live_list = $this->live_preview(time(), $mhmWhere, 8);
                $live_cate = array();
                if ($live_list) {
                    foreach ($live_list['live_list'] as $key => $val) {
                        foreach ($val as $v) {
                            $mount_1and2 = M('zy_video_mount')->where(['vid' => $v['id'], 'mhm_id' => $mhm_id])->getField('vid');
                            if ($mount_1and2) {
                                $v['mount_iand'] = 1;
                            }
                            $v['ctime']        = $live_list['live_ctime'][$key];
                            $v['teacher_name'] = M('zy_teacher')->where('id =' . $v['teacher_id'])->getField('name');
                            $live_cate[]       = $v;
                        }
                    }
                }
            } else {
                //直播课程处理
                $liveData = D('ZyVideo', 'classroom')->where($mhmWhere)->field('fullcategorypath,id')->order(
                    'video_order_count desc,video_score desc,video_collect_count desc')->limit(1000)->select();
                $count['live_cate'] = D('ZyVideo', 'classroom')->where($mhmWhere)->count();

                //直播课程列表
                $id         = getSubByKey($liveData, 'id');
                $maps['id'] = array('in', $id);
                $live_cate  = D('ZyVideo', 'classroom')->where($maps)->limit(8)->select();
                foreach ($live_cate as $items => $va) {
                    $mount_1and2 = M('zy_video_mount')->where(['vid' => $va['id'], 'mhm_id' => $mhm_id])->getField('vid');
                    if ($mount_1and2) {
                        $live_cate[$items]['mount_iand'] = 1;
                    }
                    $live_cate[$items]['mzprice']            = getPrice($va, $this->mid, true, true, $va['type']);
                    $live_cate[$items]['mzprice']['t_price'] = $live_cate[$items]['mzprice']['price'];

                    $teacher                       = M('zy_teacher')->where(array('id' => $teacher_id))->getField('name');
                    $live_cate[$items]['tea_name'] = $teacher;
                }
            }
        }
        // 精品推荐
        if (stripos($tpl, 'theme_x3') !== false) {
            // 
            $mhmWhere['is_best'] = 1;
            unset($mhmWhere['type']);
            $best_data = D('ZyVideo', 'classroom')->where($mhmWhere)->limit(7)->select();
            foreach ($best_data as $items => $va) {
                $mount_1and2 = M('zy_video_mount')->where(['vid' => $va['id'], 'mhm_id' => $mhm_id])->getField('vid');
                if ($mount_1and2) {
                    $best_data[$items]['mount_iand'] = 1;
                }
                $best_data[$items]['mzprice']            = getPrice($va, $this->mid, true, true, $va['type']);
                $best_data[$items]['mzprice']['t_price'] = $best_data[$items]['mzprice']['price'];

                $teacher                       = M('zy_teacher')->where(array('id' => $teacher_id))->getField('name');
                $best_data[$items]['tea_name'] = $teacher;
            }
            $this->assign('best_data', $best_data);
            unset($mhmWhere['is_best']);
        }

        if (!$template_items || in_array('teacher', $template_items)) {
            //教师团队
            $teacherWhere = array();
            $teacherWhere['mhm_id'] = $mhm_id;
            $teacherWhere['is_del'] = 0;
            $teacher = M('zy_teacher')->where($teacherWhere)->order('course_count desc,reservation_count desc,review_count desc,views desc')->limit(6)->select();
            $count['teacher'] = M('zy_teacher')->where($teacherWhere)->count();
        }

        if (!$template_items || in_array('album', $template_items)) {
            //班级列表
            $album_mount_map['uid']         = $school['uid'];
            $album_mount_map['mhm_id']      = $school['id'];
            $video_mount_map['is_activity'] = 1;
            $video_mount_map['is_del']      = 0;
            $album_mount_id                 = M('zy_video_mount')->where($album_mount_map)->field('aid')->select();
            $album_mount_ids                = implode(',', array_filter(getSubByKey($album_mount_id, 'aid')));
            if ($album_mount_ids) {
                $albumWhere['_string'] = " (`mhm_id` = {$mhm_id} ) or (`id` IN ({$album_mount_ids})) ";
            } else {
                $albumWhere['mhm_id'] = $mhm_id;
            }
            $albumWhere['status'] = 1;
            $albumWhere['is_del'] = 0;
            $albumWhere['price']  = ['neq', 0];
            $album                = M('album')->where($albumWhere)->order('order_count desc,comment_count desc,collect_count desc')->limit(6)->select();
            $count['album']       = M('album')->where($albumWhere)->count();
            foreach ($album as $item => $value) {
               // $mount_1and2 = M( 'zy_video_mount')->where ( ['aid'=>$value['id'],'mhm_id'=>$mhm_id] )->getField('aid');
               //             if($mount_1and2){
               //                 $album[$item]['mount_iand'] = 1;
               //             }
                $album_category            = array_filter(explode(',', $value['album_category']));
                $cate_name                 = M('zy_package_category')->where(array('zy_package_category_id' => $album_category['1']))->find();
                $album[$item]['cate_name'] = $cate_name['title'];

                $album[$item]['video_count'] = M('album_video_link')->where(array('album_id' => $value['id']))->count();

                $all_price                = getAlbumPrice($value['id'], $this->mid);
                $album[$item]['price']    = $all_price['price'];
                $album[$item]['oPrice']   = $all_price['oriPrice'];
                $album[$item]['disPrice'] = $all_price['disPrice'];
            }
        }

        // 访问量+1
        M('school')->where(array('id' => $mhm_id))->setInc('visit_num');

        // 挂载课程随机字符串
        $chars          = 'JMRZaNTU1bNOXcABIdFVWX2eSA9YhxKhxMmDEG3InYZfDEhxCFG5oPQjOP9QkKhxR9SsGIJtTU5giVqBCJrW29pEhx0MuFKvPTUVwQRSxCDNOyBWXzAYZ';
        $mount_url_str  = '';
        $mount_url_str2 = '';
        for ($i = 0; $i < 4; $i++) {
            $mount_url_str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        for ($i = 0; $i < 4; $i++) {
            $mount_url_str2 .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        // 机构学员数
        $student   = model('Follow')->where(array('fid' => $school['uid']))->count();

        $user  = model('User')->where('mhm_id=' . $mhm_id)->field('uid')->findALL();
        $video = M('zy_order_course')->where('mhm_id=' . $mhm_id)->field('uid')->findALL();
        foreach ($video as $v) {
            $v      = implode(',', $v);
            $list[] = $v;
        }
        foreach ($user as $v) {
            $v     = implode(',', $v);
            $new[] = $v;
        }
        $user_count = array_merge($list, $new);
        $user_count = count(array_unique($user_count)) ?: 1;
        $user_count = $user_count + $student;
        //机构课程数
        $video_count = M('zy_video')->where('mhm_id=' . $mhm_id)->count();
        //机构讲师数
        $teacher_count = M('zy_teacher')->where('mhm_id=' . $mhm_id)->count();

        if (!$template_items || in_array('topic', $template_items)) {
            // 机构资讯
            $topic = model('Topics')->getTopic(1, 0, ['mhm_id' => $mhm_id]);
            $count['topicList'] = $topic['count'];
            $this->assign('topicList', $topic['data']);
        }
        $this->assign('mid', $this->mid);
        $this->assign('mhm_id', $mhm_id);
        $this->assign('album_mount_url_str', "H" . $mount_url_str);
        $this->assign('this_mhm_id', $mhm_id);
        $this->assign('mount_url_str', "L" . $mount_url_str);
        $this->assign('mount_url_str2', "V" . $mount_url_str);
        $this->assign('this_mhm_id', $mhm_id);
        
        $this->assign('live_cate', $live_cate);
        $this->assign('teacher', $teacher);
        $this->assign('album', $album);
        $this->assign('user_count', $user_count);
        $this->assign('video_count', $video_count);
        $this->assign('teacher_count', $teacher_count);
        $this->assign('count', $count);

        // 传递模板版本号
        $this->assign('theme_version', ($tpl == 'index') ? 0 : substr(strstr($tpl, '_x'), 2));
        $this->display($tpl);
    }
    /**
     * 获取直播预告
     * @param $time
     * @param $map
     * @return array
     * @author wolfHua <197572207.qq.com>
     */
    private function live_preview($time, $map, $num = 0)
    {
        if (!$time || !$map) {
            return [];
        }

        if ($num) {
            $live = model('Live')->getLiveListByTime($time, $map, $num);
        } else {
            $live = model('Live')->getLiveListByTime($time, $map);
        }
        if ($live) {
            $live_ctime = [];
            foreach ($live as $key => $val) {
                foreach ($val as $v) {
                    $live_ctime[$key] = $v;
                }
            }
            $nowTime = strtotime(date("Y-m-d"), time());

            $live_list = [];
            foreach ($live as $key => $value) {
                foreach ($value as $k => $val) {
                    $map['id']                                = $k;
                    $live_list[$key][$k]                      = D('ZyVideo')->where($map)->field('id,uid,video_title,video_binfo,cover,teacher_id,t_price,video_order_count,mhm_id,is_charge,live_type')->find();
                    $live_list[$key][$k]['video_order_count'] = M('zy_order_live')->where(array('live_id' => $map['id'], 'is_del' => 0, 'pay_status' => 3))->count();

                    $live_list[$key][$k]['school'] = model('School')->where('id = ' . $live_list[$key][$k]['mhm_id'])->field('title,doadmin')->find();
                    //机构域名
                    if ($live_list[$key][$k]['school']['doadmin']) {
                        $live_list[$key][$k]['school']['domain'] = getDomain($live_list[$key][$k]['school']['doadmin']);
                    } else {
                        $live_list[$key][$k]['school']['domain'] = U('school/School/index', array('id' => $live_list[$key][$k]['mhm_id']));
                    }

                    //购买直播实际价格
                    $live_list[$key][$k]['t_price'] = getPrice($live_list[$key][$k], $this->mid);
                }
            }
            return array('live_ctime' => $live_ctime, 'live_list' => $live_list, 'nowTime' => $nowTime);
        }
        return [];
    }
    /*
     * 关于我们
     * */
    public function about_us()
    {
        $school = $this->school;

        //机构域名
        $school['domain'] = getDomain($school['doadmin'], $school['school_id']);
        /*if($school['doadmin'] && $school['doadmin'] != 'www'){
        $school['domain'] = getDomain($school['doadmin']);
        }else{
        $school['domain'] = U('school/School/index',array('id'=>$school['school_id']));
        }*/
        $this->assign('content',$school['about_us']);
        $this->display('about_us');
    }

    protected function parseCateList($cate){
        // 获取所以父级
        //$list = model('VideoCategory')->getTreeById($cate);
        //$catelist = array_reverse($list);
        $selectCate = model('CategoryTree')->setTable('zy_currency_category')->getSelectData($cate);
        $this->assign('catelist',$selectCate);
    }

    /*
     * 机构课程分类详情
     */
    public function video_list($mhm_id, $type,$order = '')
    {
        $limit = 12;
        $orders = [
            'default'=>'video_order_count desc,video_score desc,video_collect_count desc',
            'new' => 'ctime desc',
            'score' => 'video_score desc',
            'price' => 't_price ASC'
        ];
        $order || $order = $_GET['order'];
        (!$order || !isset($orders[$order])) && $order = $_GET['order'] = 'default';
        $order = $orders[$order];
        $school = $this->school;
        //机构域名
        $school['domain'] = getDomain($school['doadmin'], $school['school_id']);

        $cate_id = intval($_GET['cate']);
        $this->parseCateList($cate_id);
        $video_mount_map['uid']         = $school['uid'];
        $video_mount_map['mhm_id']      = $mhm_id;
        $video_mount_map['is_activity'] = 1;
        $video_mount_map['is_del']      = 0;
        $video_mount_id                 = M('zy_video_mount')->where($video_mount_map)->field('vid')->select();
        $video_mount_ids                = implode(',', array_filter(getSubByKey($video_mount_id, 'vid')));
        if ($video_mount_ids) {
            $map['_string'] = " (`mhm_id` = {$mhm_id} ) or (`id` IN ({$video_mount_ids})) ";
        } else {
            $map['mhm_id'] = $mhm_id;
        }

        $map['type']        = $type;
        $map['is_activity'] = 1;
        $map['is_del']      = 0;
        $map['listingtime'] = array('lt', time());
        $map['uctime']      = array('gt', time());
        $cate_id && $map['fullcategorypath'] =array('like','%,' . $cate_id . ',%');

        $data = M('zy_video')->where($map)->order($order)->field('id,video_title,uid,is_activity,ctime,type,teacher_id,cover,is_charge,t_price,v_price,mhm_id')->findPage($limit);
        foreach ($data['data'] as $key => $val) {
            $mount_1and2 = M('zy_video_mount')->where(['vid' => $val['id'], 'mhm_id' => $mhm_id])->getField('vid');
            if ($mount_1and2) {
                $data['data'][$key]['mount_iand'] = 1;
            }
            $school_info                        = model('School')->where('id=' . $val['mhm_id'])->field('id,title,doadmin')->find();
            $data['data'][$key]['school_title'] = $school_info['title'];
            //机构域名
            $data['data'][$key]['domain'] = getDomain($school_info['doadmin'], $school_info['id']);

            $teacher                        = M('zy_teacher')->where(array('id' => $val['teacher_id']))->find();
            $data['data'][$key]['tea_name'] = $teacher['name'];
            if ($type == 1) {
                $data['data'][$key]['order_count'] = M('zy_order_course')->where('video_id=' . $val['id'])->count();
            } else {
                $data['data'][$key]['order_count'] = M('zy_order_live')->where('live_id=' . $val['id'])->count();
            }
            $data['data'][$key]['mzprice']            = getPrice($val, $this->mid, true, true, $val['type']);
            $data['data'][$key]['mzprice']['t_price'] = $data['data'][$key]['mzprice']['price'];
        }

        $chars         = 'JMRZaNTU1bNOXcABIdFVWX2eSA9YhxKhxMmDEG3InYZfDEhxCFG5oPQjOP9QkKhxR9SsGIJtTU5giVqBCJrW29pEhx0MuFKvPTUVwQRSxCDNOyBWXzAYZ';
        $mount_url_str = '';
        for ($i = 0; $i < 4; $i++) {
            $mount_url_str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        $this->assign('mount_url_str', "L" . $mount_url_str);
        $this->assign('listData', $data);
        $this->assign('school', $school);
        $this->assign('mhm_id', $mhm_id);

    }

    /*
     * 机构班级详情
     */
    public function album_list($mhm_id)
    {
        $limit  = 4;
        $school = $this->school;

        //班级
        $album_mount_map['uid']         = $school['uid'];
        $album_mount_map['mhm_id']      = $mhm_id;
        $video_mount_map['is_activity'] = 1;
        $video_mount_map['is_del']      = 0;
        $album_mount_id                 = M('zy_video_mount')->where($album_mount_map)->field('aid')->select();
        $album_mount_ids                = implode(',', array_filter(getSubByKey($album_mount_id, 'aid')));
        if ($album_mount_ids) {
            $albumWhere['_string'] = " (`mhm_id` = {$mhm_id} ) or (`id` IN ({$album_mount_ids})) ";
        } else {
            $albumWhere['mhm_id'] = $mhm_id;
        }
        $albumWhere['status'] = 1;
        $albumWhere['is_del'] = 0;
        $albumWhere['price']  = ['neq', 0];
        $order                = 'order_count desc,comment_count desc,collect_count desc';
//        $album = M('album')->where($albumWhere)->order('order_count desc,comment_count desc,collect_count desc')->findPage(6);
        $album          = D('Album', 'classroom')->getList($albumWhere, $order, $limit);
        $count['album'] = M('album')->where($albumWhere)->count();
        foreach ($album['data'] as $item => $value) {
//            $mount_1and2 = M( 'zy_video_mount')->where ( ['aid'=>$value['id'],'mhm_id'=>$mhm_id] )->getField('aid');
            //            if($mount_1and2){
            //                $album[$item]['mount_iand'] = 1;
            //            }
            $album_category                      = array_filter(explode(',', $value['album_category']));
            $cate_name                           = M('zy_package_category')->where(array('zy_package_category_id' => $album_category['1']))->find();
            $album['data'][$item]['cate_name']   = $cate_name['title'];
            $album['data'][$item]['video_count'] = M('album_video_link')->where(array('album_id' => $value['id']))->count();

            $all_price                        = getAlbumPrice($value['id'], $this->mid);
            $album['data'][$item]['price']    = $all_price['price'];
            $album['data'][$item]['oPrice']   = $all_price['oriPrice'];
            $album['data'][$item]['disPrice'] = $all_price['disPrice'];
        }
        /*$album = M('album')->where($albumWhere)->order('order_count desc,comment_count desc,collect_count desc')->findPage($limit);
        foreach ($album as $item=>$value){
        $album_category = array_filter(explode(',',$value['album_category']));
        $cate_name = M('zy_package_category')->where(array('zy_package_category_id'=>$album_category['1']))->find();
        $album[$item]['cate_name'] = $cate_name['title'];

        $album[$item]['video_count'] = M('album_video_link')->where(array('album_id'=>$value['id']))->count();;
        }*/
        M('school')->where(array('id' => $mhm_id))->setInc('visit_num');

        //机构域名
        $school['domain'] = getDomain($school['doadmin'], $school['school_id']);

        $this->assign('data', $album);
        $this->assign('listData', $album['data']);
        $this->assign('school', $school);
        $this->assign('mhm_id', $mhm_id);
    }

    /*
     * 机构讲师详情
     */
    public function teacher_list($mhm_id)
    {
        $limit = 6;
        $order = 'reservation_count desc';
        $school = $this->school;

        $teachermap['mhm_id']    = $mhm_id;
        $teachermap['is_del']    = 0;
        $teachermap['is_reject'] = 0;
        $data                    = M('zy_teacher')->where($teachermap)->order($order)->findPage($limit);
        foreach ($data['data'] as $key => &$value) {
            $value["video"]                  = M('zy_video')->where('is_del=0 and teacher_id=' . $value['id'])->order('video_order_count desc')->field('id,video_title,type,t_price,video_order_count')->find();
            $value['teach_areas']            = explode(",", $value['teach_areas']);
            $teacher_title                   = M('zy_teacher_title_category')->where('zy_teacher_title_category_id=' . $value['title'])->find();
            $value['teacher_title_category'] = $teacher_title['title'] ?: '普通讲师';
            if ($teacher_title['cover']) {
                $value['teacher_title_cover'] = getCover($teacher_title['cover'], 19, 19);
            }
            $user                   = model('User')->getUserInfo($value['uid']);
            $value['Teacher_areas'] = $user['location'];
            //是否已经收藏讲师
            $isExist = D('ZyCollection')->where(array('source_id' => $value['id'], 'uid' => $this->mid, 'source_table_name' => 'zy_teacher'))->count();

            if ($isExist) {
                $value['collection'] = 1;
            } else {
                $value['collection'] = 0;
            }

            $star                  = M('zy_review')->where('tid=' . $value['id'])->avg('star');
            $value['review_count'] = M('zy_review')->where('tid=' . $value['id'])->count();
            $value['star']         = round($star / 20);
            //关注
            $follow_count = model('Follow')->getFollowCount($value['uid']);
            foreach ($follow_count as $k => &$v) {
                $value['follow_count'] = $v['follower'];
            }
            if (!$value['follow_count']) {
                $value['follow_count'] = '0';
            }
            //讲师课程
            $value['video_count'] = D('ZyVideo')->where('teacher_id=' . $value['id'])->count();
            //讲师标签
            $value['label'] = array_filter(explode(",", $value['label']));
        }

        //机构域名
        $school['domain'] = getDomain($school['doadmin'], $school['school_id']);

        //模版
        $school_template = model('School')->where(array('id' => $mhm_id))->getField('template');
        if (!$school_template) {
            $template = 0;
        } else {
            $template = array_flip(explode(",", $school_template));
        }
        //机构评价（课程）
        $schoolmap['mhm_id']      = $school['school_id'];
        $schoolmap['is_del']      = 0;
        $schoolmap['is_activity'] = 1;
        $videoid                  = M('zy_video')->where($schoolmap)->field('id')->select();

        $live_id        = trim(implode(',', array_unique(getSubByKey($videoid, 'id'))), ',');
        $vmap['oid']    = ['in', $live_id];
        $vmap['is_del'] = 0;

        //机构评价（讲师）
        $ostar            = M('zy_review')->where($vmap)->avg('star');
        $tidmap['mhm_id'] = $school['school_id'];
        $tidmap['is_del'] = 0;
        $tids             = M('zy_teacher')->where($tidmap)->field('id')->select();
        $tid              = trim(implode(',', array_unique(getSubByKey($tids, 'id'))), ',');

        $vtmap['tid']    = ['in', $tid];
        $vtmap['is_del'] = 0;

        $tstar                    = M('zy_review')->where($vtmap)->avg('star');
        $star                     = ceil(($tstar + $ostar) / 2 / 20) * 20;
        $school['favorable_rate'] = round($star, 2) . '%' ?: 0;
        $school['star']           = $star;
        //机构学员数
        $user  = model('User')->where('mhm_id=' . $mhm_id)->field('uid')->findALL();
        $video = M('zy_order_course')->where('mhm_id=' . $mhm_id)->field('uid')->findALL();
        foreach ($video as $v) {
            $v      = implode(',', $v);
            $list[] = $v;
        }
        foreach ($user as $v) {
            $v     = implode(',', $v);
            $new[] = $v;
        }
        $user_count = array_merge($list, $new);
        $user_count = count(array_unique($user_count)) ?: 1;

        $this->assign('data', $data);
        $this->assign('listData', $data['data']);
        $this->assign('template', $template);
        $this->assign('school', $school);
        $this->assign('mhm_id', $mhm_id);
        $this->assign('listData', $data['data']);
        $this->assign('user_count', $user_count);
    }

    /*
     *机构讲师详情--加载更多
     */
    public function getTeacherList()
    {
        $limit = 10;
        $order = 'reservation_count desc';

        $map['mhm_id']    = intval($_GET['id']);
        $map['is_del']    = 0;
        $map['is_reject'] = 0;
        $data             = M('zy_teacher')->where($map)->order($order)->findPage($limit);
        foreach ($data['data'] as $key => &$value) {
            $value['teach_areas']   = explode(",", $value['teach_areas']);
            $user                   = model('User')->getUserInfo($value['uid']);
            $value['Teacher_areas'] = $user['location'];

            $star                  = M('zy_review')->where('tid=' . $value['id'])->avg('star');
            $value['review_count'] = M('zy_review')->where('tid=' . $value['id'])->count();
            $value['star']         = round($star / 20);

            //讲师标签
            $value['label'] = array_filter(explode(",", $value['label']));
        }

        $this->assign('data', $data);
        $this->assign('listData', $data['data']);

        $html         = $this->fetch('ajax_teacher');
        $data['data'] = $html;
        exit(json_encode($data));
    }

    /**
     * 机构分类分页数据
     * @return json
     */
    public function getPage()
    {
        $cate_id                 = intval($_POST['cate_id']);
        $mhm_id                  = intval($_POST['mhm_id']);
        $cate_ids                = model('VideoCategory')->getVideoChildCategory($cate_id);
        $where                   = array();
        $where['video_category'] = array('in', $cate_ids);
        $where['is_del']         = 0;
        $where['is_activity']    = 1;
        $where['mhm_id']         = $mhm_id;
        $data                    = D('ZyVideo', 'classroom')->where($where)->findPage(10);
        if ($data['data']) {
            foreach ($data['data'] as $items => $va) {
                if (ceil($va['v_price']) > 0) {
                    $data['data'][$items]['is_free'] = 0;
                } else {
                    $data['data'][$items]['is_free'] = 1;
                }
                $teacher                          = M('zy_teacher')->where(array('id' => $va['teacher_id']))->find();
                $data['data'][$items]['tea_name'] = $teacher['name'];
                $data['data']['state']            = 1;
                $data['data']['msg']              = '获取成功';
            }
        } else {
            $data['data']          = array();
            $data['data']['state'] = 0;
            $data['data']['msg']   = '没有更多数据';
        }
        echo json_encode($data['data']);
        exit;
    }
    /**
     *卡券领取
     *@return void
     */
    public function saveUSerCoupon()
    {
        $coupon_id = t($_POST['coupon_id']);
        if(!$coupon_id){
            echo json_encode(['status'=>0,'info'=>'请选择要领取的卡券']);
            exit;
        }
        $coupon = model('Coupon')->saveUSerCoupon($coupon_id,$this->mid);

        if($coupon === 1){
            echo json_encode(['status'=>0,'info'=>model('Coupon')->getError()]);
            exit;
        } else if($coupon) {
            echo json_encode(['status'=>1,'info'=>'领取成功，请及时使用']);
            exit;
        }
        echo json_encode(['status'=>0,'info'=>'领取失败']);
        exit;
    }

    //课程列表
    public function course()
    {
        $mhm_id = $this->mhm_id;
        $this->video_list($mhm_id, 1);
        $this->display('course');
    }
    //班级列表
    public function album()
    {
        $mhm_id = $this->mhm_id;
        $this->album_list($mhm_id);
        $this->display();
    }
    //直播列表
    public function live()
    {
        $mhm_id = $this->mhm_id;
        $this->video_list($mhm_id, 2);
        $this->display('course');
    }
    //讲师列表
    public function teacher_index()
    {
        $mhm_id = $this->mhm_id;
        $this->teacher_list($mhm_id);
        $this->display('teacher_index');
    }
    /**
     * 机构资讯
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2018-01-02
     * @return   [type]                         [description]
     */
    public function news()
    {
        $school = $this->school;
        // 资讯分类
        $cate = model('Topics')->getCate(0);
        $this->assign('topicCate',$cate);
        $data = model('Topics')->getTopic(1,$_GET['cate'],['mhm_id'=>$school['school_id']]);
        $this->assign('topic_data',$data);
        $this->assign('school', $school);
        $this->display();
    }
}
