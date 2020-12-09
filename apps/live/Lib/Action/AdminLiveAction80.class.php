<?php
/**
 * 后台直播管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun2.0
 */
use GuzzleHttp\Client;
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminLiveAction extends AdministratorAction
{

    protected $base_config = array();
    protected $gh_config = array();
    protected $zshd_config = array();
    protected $cc_config = array();
    protected $wh_config = array();
    protected $eeo_xbkConfig = array();
    protected $tk_config = array();

    /**
     * 初始化，
     */
    public function _initialize() {
        $this->base_config =  model('Xdata')->get('live_AdminConfig:baseConfig');
        $this->gh_config   =  model('Xdata')->get('live_AdminConfig:ghConfig');
        $this->zshd_config =  model('Xdata')->get('live_AdminConfig:zshdConfig');
        $this->cc_config   =  model('Xdata')->get('live_AdminConfig:ccConfig');
        $this->wh_config   =  model('Xdata')->get('live_AdminConfig:whConfig');
        $this->eeo_xbkConfig =  model('Xdata')->get('live_AdminConfig:eeo_xbkConfig');
        $this->tk_config   =  model('Xdata')->get('live_AdminConfig:tkConfig');

        $this->pageTitle['index']   = '已审';
        $this->pageTitle['activity']   = '待审';
        $this->pageTitle['closeLive']   = '回收站';
        $this->pageTitle['addLive'] = '添加';

        $this->pageTab[] = array('title'=>'已审','tabHash'=>'index','url'=>U('live/AdminLive/index'));
        $this->pageTab[] = array('title'=>'待审','tabHash'=>'activity','url'=>U('live/AdminLive/index',array('activity'=> '1')));
        $this->pageTab[] = array('title'=>'回收站','tabHash'=>'closeLive','url'=>U('live/AdminLive/closeLive'));
        $this->pageTab[] = array('title'=>'添加','tabHash'=>'addLive','url'=>U('live/AdminLive/addLive'));

        parent::_initialize();
    }

    /**
     * 直播课堂列表
     */
    public function index(){
        $_REQUEST['tabHash'] = 'index';
        
        $this->pageKeyList = array('id','uid','mhm_title','video_title','speaker_name','v_price','t_price','video_score','video_order_count','video_order_count_mark','cover','listingtime',
            'uctime','maxmannums','is_best','is_charge','is_activity','is_open','live_type','DOACTION');
        //搜索字段
        $this->searchKey = array('id', 'uid', 'mhm_id','teacher_id','video_title','v_price','t_price','video_score','is_charge','is_best','is_best_like','is_cete_floor','is_re_free',array('listingtime','listingtime2'),'quanzhong');
        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "禁用", 'onclick' => "admin.doaction('','ColseLive',9)");
        dump("6556");die;
        $this->opt['video_score']     = array('0'=>'不限','1'=>'★ （一星）','2'=>'★★ （二星）','3'=>'★★★ （三星）','4'=>'★★★★ （四星）','5'=>'★★★★★ （五星）');
        $this->opt['is_best']   = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_charge'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_best_like'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_cete_floor'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_re_free'] = array('0'=>'不限','1'=>'否','2'=>'是');

        $this->opt['quanzhong'] = array('best_sort asc'=>'精选推荐');//,'best_like_sort asc'=>'喜欢推荐','cete_floor_sort asc'=>'分类楼层推荐','re_free_sort asc'=>'天天特价推荐'
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['mhm_id'] = $school ? $school + [0=>'请选择'] : [0=>'请选择'];
        $all_speaker = M('zy_teacher')->where('is_del=0')->getField('id,name');
        $this->opt['teacher_id'] = $all_speaker ? array('0'=>'请选择')+$all_speaker : array('0'=>'请选择');

        // 数据的格式化
        $order = 'id desc';
        $activity = 0;
        if(t($_GET['activity'] == 1)) {
            $_REQUEST['tabHash'] = 'activity';
            $this->pageTitle['index']   = '待审';
            $activity = t($_GET['activity']);
            $this->searchPostUrl = U('live/AdminLive/index',['activity'=>1,'tabHash'=>'activity']);
        }
        $map['is_del'] = ['neq',2];

        $list = $this->_getLiveList('index',20,$order,$map,$activity,0);

        $this->displayList($list);
    }


     /**
     * 直播课堂列表
     */
    public function type(){
 

        /** 获取全部分类 */


        // // 数据的格式化
        // $order = 'id desc';
        // $map['is_del'] = ['neq',2];
        // $list = $this->_getTypeList(20,$order,$map);

        // $this->assign('list',$list);
        // $this->assign('lists',$list['data']);
        $type=M('zy_currency_category')->where('pid = 0')->select();
        foreach ($type as $k => $v) {
            $ss[$v['zy_currency_category_id']]=$v;
        }
        $this->assign('type',$type);
        $this->assign('ss',$ss);
        $type=isset($_REQUEST['type'])?$_REQUEST['type']:$type[0]['zy_currency_category_id'];

        $map['video_category'] = $type;
        $order = 'id desc';
        $map['is_del'] = ['neq',2];
        $list = $this->_getLiveList('index',20,$order,$map,0,1);
        
        $this->assign('list',$list);
        $this->assign('lists',$list['data']);
        $this->display();
    }

    /**
     * 直播课堂列表
     */
    public function closeLive(){
        $_REQUEST['tabHash'] = 'closeLive';

        $this->pageKeyList = array('id','uid','mhm_title','video_title','speaker_name','v_price','t_price','video_score','cover','listingtime',
            'uctime','is_best','is_charge','is_open','live_type','DOACTION');

        //搜索字段
        $this->searchKey = array('id', 'uid', 'mhm_id','teacher_id','video_title','v_price','t_price','video_score','is_charge','is_best','is_best_like','is_cete_floor','is_re_free',array('listingtime','listingtime2'),'quanzhong');
        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "删除", 'onclick' => "admin.doaction('','ColseLive',9,1)");

        $this->opt['video_score']     = array('0'=>'不限','1'=>'★ （一星）','2'=>'★★ （二星）','3'=>'★★★ （三星）','4'=>'★★★★ （四星）','5'=>'★★★★★ （五星）');
        $this->opt['is_best']   = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_charge'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_best_like'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_cete_floor'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_re_free'] = array('0'=>'不限','1'=>'否','2'=>'是');

        $this->opt['quanzhong'] = array('best_sort asc'=>'精选推荐','best_like_sort asc'=>'喜欢推荐','cete_floor_sort asc'=>'分类楼层推荐','re_free_sort asc'=>'天天特价推荐');
        $school = model('School')->getAllSchol('','id,title');
        $this->opt['mhm_id'] = $school ? $school + [0=>'请选择'] : [0=>'请选择'];
        $all_speaker = M('zy_teacher')->where('is_del=0')->getField('id,name');
        $this->opt['teacher_id'] = $all_speaker ? array('0'=>'请选择')+$all_speaker : array('0'=>'请选择');

        // 数据的格式化
        $order = 'id desc';
        $map['is_del'] = 1;
        $list = $this->_getLiveList('closeLive',20,$order,$map,0,0);

        $this->displayList($list);
    }

    /**
     * 创建课堂直播课堂
     */
    public function addLive(){
        if( isset($_POST) ) {
            if(empty($_POST['video_title'])){$this->error("名称不能为空");}
            if(empty($_POST['mzLevelSelect2'])){$this->error("请选择分类");}
            if(empty($_POST['school'])){$this->error("机构ID不能为空");}
            /*$t_mhm_id = M('zy_teacher')->where(['id'=>$_POST['teacher_id']])->getField('mhm_id');
            if($_POST['mhm_id'] != $t_mhm_id){
                $this->error("讲师所属机构和课程所属机构需保持一致");
            }*/
            if(empty($_POST['cover_ids'])){$this->error("请上传直播课堂封面");}
            //if(empty($_POST['video_binfo'])){$this->error("直播课堂简介信息不能为空");}
            if(empty($_POST['video_intro'])){$this->error("直播课堂简介信息不能为空");}
            if($_POST['v_price'] == ''){$this->error("市场价格不能为空");}
            if(!is_numeric($_POST['v_price'])){$this->error('市场价格必须为数字');}
            if($_POST['t_price'] == ''){$this->error("销售价格不能为空");}
            if(!is_numeric($_POST['t_price'])){$this->error('销售必须为数字');}
            if(empty($_POST['listingtime'])){$this->error("上架时间不能为空");}
            if(empty($_POST['uctime'])){$this->error("下架时间不能为空");}
            if(strtotime($_POST['uctime']) < strtotime($_POST['listingtime'])){$this->error("下架时间不能小于上架时间");}
            if(empty($_POST['maxmannums'])){$this->error("最大并发量不能为空");}
            if(!is_numeric($_POST['maxmannums']) || preg_match("/^-[0-9]+(\.[0-9]+)?$/",$_POST['maxmannums'])){$this->error('最大并发量格式错误');}
            if (intval($post['video_order_count_mark']) >  intval($post['view_nums_mark'])) {
                $this->error("学习人数不能大于浏览人数");
            }

            $myAdminLevelhidden         = getCsvInt(t($_POST['mzLevelSelect2']),0,true,true,',');  //处理分类全路径
            $fullcategorypath             = explode(',',$_POST['mzLevelSelect2']);
            $category                     = array_pop($fullcategorypath);
            $category                    = $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
            $data['fullcategorypath']    = $myAdminLevelhidden; //分类全路径
            $data['video_category']        = $category == '0' ? array_pop($fullcategorypath) : $category;

            $time = time();
            if($this->base_config['live_opt'] == 1){
                //展示互动
                $data['live_type'] = 1;
            }else if($this->base_config['live_opt'] == 2){
                //三芒
                $data['live_type'] = 2;
            }else if($this->base_config['live_opt'] == 3){
                //光慧
                $data['live_type'] = 3;
            }else if($this->base_config['live_opt'] == 4){
                //CC
                $data['live_type'] = 4;
            }else if($this->base_config['live_opt'] == 5){
                //微吼
                $data['live_type'] = 5;
            }else if($this->base_config['live_opt'] == 6){
                //cc小班课
                $data['live_type'] = 6;
            }else if($this->base_config['live_opt'] == 7){
                //classin
                $data['live_type'] = 7;

                $time = time();
                //eeo注册用户
                $user_url = $this->eeo_xbkConfig['api_url']."register";

                //讲师信息
                $speaker = M('zy_teacher')->where("id=".intval(t($_POST['teacher_id'])))->field('id,uid,name,inro')->find();
                $user_info = M('user')->where(['uid'=>$speaker['uid']])->field('phone,password')->find();
                $speaker_info = M('user_verified')->where("uid=".intval($speaker['uid']))->field('id,uid,phone,realname,idcard')->find();

                if(!$user_info['phone']){// && !$speaker_info['phone']
                    $this->error("该用户未绑定手机号");
                }

                $query_public_data['SID']       = $this->eeo_xbkConfig['api_key'];
                $query_public_data['safeKey']   = md5($this->eeo_xbkConfig['api_secret'].$time);
                $query_public_data['timeStamp'] = $time;
                $query_user_data['telephone']   = $user_info['phone'];//? : $speaker_info['phone']
                $query_user_data['nickname']    = $speaker['name'];
                $query_user_data['md5pass']     = $user_info['password'];

                $live_user_res   = getDataByPostUrl($user_url,array_merge($query_public_data,$query_user_data));

                if($live_user_res->error_info->errno == 1 || $live_user_res->error_info->errno == 135){
                    unset($query_user_data['md5pass']);

                    //如果已注册就修改用户信息
                    if($live_user_res->error_info->errno == 135){
                        $user_url = $this->eeo_xbkConfig['api_url']."editUserInfo";

                        getDataByPostUrl($user_url,array_merge($query_public_data,$query_user_data));
                    }

                    $teacher_url = $this->eeo_xbkConfig['api_url']."addTeacher";

                    $query_teacher_data['teacherAccount'] = $user_info['phone'] ? : $speaker_info['phone'] ;
                    $query_teacher_data['teacherName']    = $speaker['name'];

                    $live_teacher_res   = getDataByPostUrl($teacher_url,array_merge($query_public_data,$query_teacher_data));

                    if($live_teacher_res->error_info->errno != 1 && $live_teacher_res->error_info->errno != 133){
                        $this->error("eeo讲师添加失败");
                    }

                } else {
                    $this->error("eeo用户注册失败");
                }

                $url = $this->eeo_xbkConfig['api_url']."addCourse";

                $query_live_data['SID']       = $this->eeo_xbkConfig['api_key'];
                $query_live_data['safeKey']   = md5($this->eeo_xbkConfig['api_secret'].$time);
                $query_live_data['timeStamp'] = $time;
                $query_live_data['courseName'] = t($_POST['video_title']);
                $query_live_data['expiryTime'] = strtotime($_POST['uctime']);
                $query_live_data['mainTeacherAccount'] = $speaker_info['phone'];
                $query_live_data['courseIntroduce'] = t($_POST['video_binfo']);

                $live_res   = getDataByPostUrl($url,$query_live_data);

                if($live_res->error_info->errno != 1){
                    $this->error($live_res->error_info->error);
                }
            }else if($this->base_config['live_opt'] == 8){
                //拓课云
                $data['live_type'] = 8;
            }

            $data['uid']            = $this->mid;
            $data['live_course_id'] = $live_res->data;
            $data['ctime']          = $time;
            $data['type']           = 2;
            $data['is_activity']    = 1;
            $data['video_title']    = t($_POST['video_title']);
            $data['cover']          = intval($_POST['cover_ids']);
            $data['video_binfo']    = '';
            $data['video_intro']    = $_POST['video_intro'];

            $data['mhm_id']         = intval(t($_POST['school']));
            $data['teacher_id']     = $_POST['teacher_id'];
            $data['maxmannums']     = intval($_POST['maxmannums']);
            $data['is_charge']      = intval($_POST['is_charge']);
            $data['is_mount']       = intval($_POST['is_mount']);
            $is_mount = M('zy_video')->where('id = '.$_POST['id'])->getField('is_mount');
            if($_POST['is_mount'] != 0 && !$is_mount){
                $data['atime']      = time();
            }
            $data['is_best']         = intval($_POST['is_best']);
            $data['best_sort']       = intval($_POST['best_sort']);
            $data['is_best_like']    = intval($_POST['is_best_like']);
            $data['best_like_sort']  = intval($_POST['best_like_sort']);
            $data['is_cete_floor']   = intval($_POST['is_cete_floor']);
            $data['cete_floor_sort'] = intval($_POST['cete_floor_sort']);
            $data['is_re_free']      = intval($_POST['is_re_free']);
            $data['re_free_sort']    = intval($_POST['re_free_sort']);
            $data['v_price']         = floatval($_POST['v_price']);
            $data['t_price']         = floatval($_POST['t_price']);
            $data['listingtime']     = strtotime($_POST['listingtime']);
            $data['uctime']          = strtotime($_POST['uctime']);
            $data['video_score']     = intval($_POST['video_score'])*20;
            $data['vip_level']       = intval($_POST['vip_level']); //vip等级
            $data['video_order_count_mark'] = intval($_POST['video_order_count_mark']); //学习人数
			 $data['videofile_ids']    = isset($_POST['attach'][0]) ? intval($_POST['attach'][0]) : 0; //课件id
            $data['view_nums_mark']  = intval($post['view_nums_mark']);

            if(isset($_POST['crow_id']) && !empty($_POST['crow_id'])){
                $data['crow_id'] = intval($_POST['crow_id']); //众筹id
            }

			
			
			
			
			
			
			
            $res = model('Live')->add($data);
         /*    dump(M()->getLastSql());
 
         echo "<pre>";
			
			print_r($data);
			
			echo "</pre>";
			
			exit();
			 */
            $this->assign('jumpUrl',U('live/AdminLive/index'));

//            if(isset($data['crow_id']) && !empty($data['crow_id']) && $res){
//                $this->error("添加失败");
//            }
            if(!$res){
                $this->error("添加失败");
            }

            $this->success("添加成功");
        } else {
            
			
		if (t($_GET['id'])) {
			
			
			echo "909090";
			
			exit();
			
            $data = D('ZyVideo', 'classroom')->getVideoById(intval($_GET['id']));

            $this->assign($data);
            $this->assign('data', $data);
            //查询讲师列表
            $trlist = $this->teacherList($data['mhm_id']);
            $this->assign('trlist', $trlist);
        } else {
            $this->assign("listingtime", time());
            $this->assign("uctime", time() + 604800);
            $this->assign("video_intro", "");
            $this->assign("is_mount", 1);
    }

        //获取会员等级
        $vip_levels = M('user_vip')->where('is_del=0')->order('sort desc')->getField('id,title');
        $school     = model('School')->where(array('status' => 1, 'is_del' => 0))->field('id,title')->findALL();
        $this->assign('vip_levels', $vip_levels);
        //$this->assign('album_list', $album_list);
        $this->assign('school', $school);

        $this->display();
        }
    }
    /**
     * 编辑课堂直播课堂
     */
    public function editLive(){
		
		
		
        if( isset($_POST) ) {
			
			
	
            if(empty($_POST['video_title'])){$this->error("名称不能为空");}
            if(empty($_POST['mzLevelSelect2'])){$this->error("请选择分类");}
           /*  $t_mhm_id = M('zy_teacher')->where(['id'=>$_POST['teacher_id']])->getField('mhm_id');
            if($_POST['mhm_id'] != $t_mhm_id){
                $this->error("讲师所属机构和课程所属机构需保持一致");
            } */
            if(empty($_POST['cover_ids'])){$this->error("请上传直播课堂封面");}
            if(empty($_POST['video_intro'])){$this->error("直播简介不能为空");}
            if(empty($_POST['maxmannums'])){$this->error("最大并发量不能为空");}
            if(!is_numeric($_POST['maxmannums']) || preg_match("/^-[0-9]+(\.[0-9]+)?$/",$_POST['maxmannums'])){$this->error('最大并发量格式错误');}
            if($_POST['v_price'] == ''){$this->error("市场价格不能为空");}
            if(!is_numeric($_POST['v_price'])){$this->error('市场价格必须为数字');}
            if($_POST['t_price'] == ''){$this->error("销售价格不能为空");}
            if(!is_numeric($_POST['t_price'])){$this->error('销售必须为数字');}
            if(empty($_POST['listingtime'])){$this->error("上架时间不能为空");}
            if(empty($_POST['uctime'])){$this->error("下架时间不能为空");}
            if(strtotime($_POST['uctime']) < strtotime($_POST['listingtime'])){$this->error("下架时间不能小于上架时间");}
            if (intval($_POST['video_order_count_mark']) >  intval($_POST['view_nums_mark'])) {
                $this->error("学习人数不能大于浏览人数");
            }

            $myAdminLevelhidden         = getCsvInt(t($_POST['mzLevelSelect2']),0,true,true,',');  //处理分类全路径
            $fullcategorypath           = explode(',',$_POST['mzLevelSelect2']);
            $category                   = array_pop($fullcategorypath);
            $category                   = $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
            $data['fullcategorypath']   = $myAdminLevelhidden; //分类全路径
            $data['video_category']     = $category == '0' ? array_pop($fullcategorypath) : $category;

            $video_info = M('zy_video')->where('id ='.intval($_POST['id'])) ->field('teacher_id,live_course_id,live_type') ->find();
            //classin
            if($video_info['live_type'] == 7){
                $time = time();

                //讲师信息
                $speaker = M('zy_teacher')->where("id=".intval(t($_POST['teacher_id'])))->field('id,uid,name,inro')->find();
                $user_info = M('user')->where(['uid'=>$speaker['uid']])->field('phone,password')->find();
                $speaker_info = M('user_verified')->where("uid=".intval($speaker['uid']))->field('id,uid,phone,realname,idcard')->find();

                if(!$user_info['phone'] ){//&& !$speaker_info['phone']
                    $this->error("该用户未绑定手机号");
                }

                if($video_info['teacher_id'] != $_POST['teacher_id']){

                    //eeo注册用户
                    $user_url = $this->eeo_xbkConfig['api_url']."register";

                    $query_public_data['SID']       = $this->eeo_xbkConfig['api_key'];
                    $query_public_data['safeKey']   = md5($this->eeo_xbkConfig['api_secret'].$time);
                    $query_public_data['timeStamp'] = $time;
                    $query_user_data['telephone']   = $user_info['phone'] ;// ? : $speaker_info['phone']
                    $query_user_data['nickname']    = $speaker['name'];
                    $query_user_data['md5pass']     = $user_info['password'];

                    $live_user_res   = getDataByPostUrl($user_url,array_merge($query_public_data,$query_user_data));

                    if($live_user_res->error_info->errno == 1 || $live_user_res->error_info->errno == 135){
                        unset($query_user_data['md5pass']);

                        //如果已注册就修改用户信息
                        if($live_user_res->error_info->errno == 135){
                            $user_url = $this->eeo_xbkConfig['api_url']."editUserInfo";

                            getDataByPostUrl($user_url,array_merge($query_public_data,$query_user_data));
                        }

                        $teacher_url = $this->eeo_xbkConfig['api_url']."addTeacher";

                        $query_teacher_data['teacherAccount'] = $user_info['phone'] ? : $speaker_info['phone'] ;
                        $query_teacher_data['teacherName']    = $speaker['name'];

                        $live_teacher_res   = getDataByPostUrl($teacher_url,array_merge($query_public_data,$query_teacher_data));

                        if($live_teacher_res->error_info->errno != 1 && $live_teacher_res->error_info->errno != 133){
                            $this->error("eeo讲师添加失败");
                        }

                    } else {
                        $this->error("eeo用户注册失败");
                    }

                    $change_teacher_url = $this->eeo_xbkConfig['api_url']."modifyCourseTeacher";

                    $querychange__teacher_data['courseId']       = $video_info['live_course_id'];
                    $querychange__teacher_data['teacherAccount'] = $user_info['phone'] ? : $speaker_info['phone'] ;

                    $live_change_teacher_res   = getDataByPostUrl($change_teacher_url,array_merge($query_public_data,$querychange__teacher_data));

                    if($live_change_teacher_res->error_info->errno != 1 && $live_change_teacher_res->error_info->errno != 133){
                        $this->error("eeo讲师操作失败");
                    }
                }

                $url = $this->eeo_xbkConfig['api_url']."editCourse";

                $query_live_data['SID']       = $this->eeo_xbkConfig['api_key'];
                $query_live_data['safeKey']   = md5($this->eeo_xbkConfig['api_secret'].$time);
                $query_live_data['timeStamp'] = $time;
                $query_live_data['courseId']  = $video_info['live_course_id'];
                $query_live_data['courseName'] = t($_POST['video_title']);
                $query_live_data['expiryTime'] = strtotime($_POST['uctime']);
                $query_live_data['mainTeacherAccount'] = $user_info['phone'] ? : $speaker_info['phone'] ;
                $query_live_data['courseIntroduce'] = t($_POST['video_binfo']);

                $live_res   = getDataByPostUrl($url,$query_live_data);

                if($live_res->error_info->errno != 1) {
                    $this->error($live_res->error_info->error);
                }
            }

            if(isset($_POST['crow_id']) && !empty($_POST['crow_id'])){
                $data['crow_id'] = intval($_POST['crow_id']); //众筹id
            }

            $data['uid']            = $this->mid;
            $data['utime']          = time();
            $data['video_title']    = $video_title = t($_POST['video_title']);
            $data['cover']          = intval($_POST['cover_ids']);
             $data['video_binfo']    = '';
            $data['video_intro']    = $_POST['video_intro'];
            
            $data['mhm_id']         = $mhm_id = intval($_POST['school']);
            $data['teacher_id']     = $_POST['teacher_id'];
            $data['video_score']    = intval($_POST['video_score'])*20;
            $data['maxmannums']     = $maxmannums = intval($_POST['maxmannums']);
            $data['is_charge']      = intval($_POST['is_charge']);
            $data['is_mount']       = intval($_POST['is_mount']);
            $is_mount = M('zy_video')->where('id = '.$_POST['id'])->getField('is_mount');
            if($_POST['is_mount'] != 0 && !$is_mount){
                $data['atime']      = time();
            }
            $data['is_best']         = intval($_POST['is_best']);
            $data['best_sort']       = intval($_POST['best_sort']);
            $data['is_best_like']    = intval($_POST['is_best_like']);
            $data['best_like_sort']  = intval($_POST['best_like_sort']);
            $data['is_cete_floor']   = intval($_POST['is_cete_floor']);
            $data['cete_floor_sort'] = intval($_POST['cete_floor_sort']);
            $data['is_re_free']      = intval($_POST['is_re_free']);
            $data['re_free_sort']    = intval($_POST['re_free_sort']);
            $data['v_price']         = floatval($_POST['v_price']);
            $data['t_price']         = floatval($_POST['t_price']);
            $data['listingtime']     = $start = strtotime($_POST['listingtime']);
            $data['uctime']          = $uctime = strtotime($_POST['uctime']);
            $data['vip_level']       = intval($_POST['vip_level']); //vip等级
            $data['video_order_count_mark'] = intval($_POST['video_order_count_mark']); //学习人数
            $data['view_nums_mark']  = intval($_POST['view_nums_mark']);
            $data['videofile_ids']    = isset($_POST['attach'][0]) ? intval($_POST['attach'][0]) : 0; //课件id

            $map['id']               = $course_id = intval($_POST['id']);
            $is_del = M('zy_video')->where('id ='.$map['id'])->getField('is_del');
            $res = model('Live')->updateLiveInfo($map,$data);

            if(isset($data['crow_id']) && !empty($data['crow_id']) && $res){
                $crowData = array();
                $crowData['vstime'] = $data['listingtime'];//课程开始时间
                $crowData['vetime'] = $data['uctime'];//课程结束时间
                $crowRst = M('Crowdfunding')->where(array('id'=>$data['crow_id']))->save($crowData);
                if($crowRst === false){
                    $this->error("添加失败");
                }
            }
            if($res){
                $this->assign('jumpUrl',U('live/AdminLive/index'));
                $this->success("编辑成功");
            }else{
                $this->error("编辑失败");
            }
        } else {
            if (t($_GET['id'])) {
                $data = D('ZyVideo', 'classroom')->getVideoById(intval($_GET['id']));
                
                $teacher=explode(',',$data['teacher_id']);
                $this->assign('teacher', $teacher);
                $this->assign($data);
                $this->assign('data', $data);
                //查询讲师列表
                $trlist = $this->teacherList($data['mhm_id']);
                $this->assign('trlist', $trlist);
            } else {
                $this->assign("listingtime", time());
                $this->assign("uctime", time() + 604800);
                $this->assign("video_intro", "");
                $this->assign("is_mount", 1);
            }

        //获取会员等级
        $vip_levels = M('user_vip')->where('is_del=0')->order('sort desc')->getField('id,title');
        $school     = model('School')->where(array('status' => 1, 'is_del' => 0))->field('id,title')->findALL();
        $this->assign('vip_levels', $vip_levels);
        //$this->assign('album_list', $album_list);
        $this->assign('school', $school);
        $this->display();
        }
    }

	
	   //讲师列表
    private function teacherList($mhm_id)
    {
        $map = array(
            'is_del'          => 0,
            'is_reject'       => 0,
            'verified_status' => 1,
        );
        if ($mhm_id) {
            $map['mhm_id'] = $mhm_id;
        }
        $teacherlist = D('ZyTeacher')->where($map)->order("ctime DESC")->select();
        return $teacherlist;
    }
    /*
     * 第三方直播间-展示互动
     */
    public function zshdLiveRoom(){
        $_REQUEST['tabHash'] = 'zshdLiveRoom';

        $live_id = intval($_GET['id']);
        $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
        if(!$liveInfo){
            $this->error("参数错误");
        }
        $this->pageTitle['zshdLiveRoom'] = $liveInfo['video_title'].' 直播课堂—直播间列表';


        $this->pageKeyList = array('id','uname', 'subject', 'teacherToken','assistantToken', 'studentClientToken',
            'studentToken','maxAttendees', 'startDate', 'invalidDate', 'clientJoin', 'webJoin', 'is_active',
            'is_open', 'DOACTION');
        //搜索字段
        $this->searchKey = array('id','uname', 'subject',  ['startDate','startDate1'], 'clientJoin', 'webJoin', 'is_active','is_del');

        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "新建", 'onclick' => "admin.jump('Zshd',{$live_id})");

        $this->opt['clientJoin'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['webJoin'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_active'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_del'] = array('0'=>'不限','1'=>'否','2'=>'是');

        // 数据的格式化
        $order = 'id desc';
        $map['live_id'] = $live_id;

        $this->searchPostUrl = U('live/AdminLive/zshdLiveRoom',['id'=>$live_id]);

        $list = $this->_getZshdLiveList('zshdLiveRoom',20,$order,$map);

        $this->displayList($list);
    }

    /*
     * 第三方直播间-光慧
     */
    public function ghLiveRoom(){
        $_REQUEST['tabHash'] = 'ghLiveRoom';

        $live_id = intval($_GET['id']);
        $liveInfo = model('Live')->where(array('id'=>$live_id))->find();
        if(!$liveInfo){
            $this->error("参数错误");
        }
        $this->pageTitle['ghLiveRoom'] = $liveInfo['video_title'].' 直播课堂—直播间列表';

        $this->pageKeyList = array('id','uname','title','account','passwd','maxAttendees','startDate','invalidDate',
            'supportMobile','is_active', 'is_open','DOACTION');
        $this->searchKey = array('id','uname', 'subject',  ['startDate','invalidDate'], 'clientJoin', 'webJoin', 'is_active','is_del');

        //搜索字段
        $this->searchKey = array('id','uname', 'subject',  ['startDate','invalidDate'], 'clientJoin', 'is_active','is_del');
        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "新建", 'onclick' => "admin.jump('Gh',{$live_id})");

        $this->opt['clientJoin'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['webJoin'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_active'] = array('0'=>'不限','1'=>'否','2'=>'是');
        $this->opt['is_del'] = array('0'=>'不限','1'=>'否','2'=>'是');

        $order = 'id desc';
        $map['live_id'] = $live_id;

        $this->searchPostUrl = U('live/AdminLive/ghLiveRoom',['id'=>$live_id]);
        $list = $this->_getGhLiveList('ghLiveRoom',20,$order,$map);

        $this->displayList($list);
    }

    /*
     * 第三方直播间-CC
     */
    public function ccLiveRoom(){
        $_REQUEST['tabHash'] = 'ccLiveRoom';

        $live_id = intval($_GET['id']);
        $this->assign('live_id',$live_id);

        $category=$this->MakeTree(0,0,0,$live_id);
        $this->assign('category',$category);

        $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
        if(!$liveInfo){
            $this->error("参数错误");
        }
        $this->pageTitle['ccLiveRoom'] = $liveInfo['video_title'].' 直播课堂—直播间列表';


        $this->pageKeyList = array('id','uname', 'subject', 'teacherToken','assistantToken', 'studentClientToken',
            'maxAttendees','startDate', 'invalidDate', 'clientJoin', 'webJoin', 'is_active','is_open', 'DOACTION');
        //搜索字段
        $this->searchKey = array('id','uname', 'subject',  ['startDate','invalidDate'], 'clientJoin', 'webJoin', 'is_active','is_del');

        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "新建", 'onclick' => "admin.jump('Cc',{$live_id})");
        $this->pageButton[] = array('title' => "关联课程", 'onclick' => "admin.associate('associate',{$live_id})");
        

        $this->opt['clientJoin'] = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['webJoin']    = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['is_active']  = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['is_del']     = ['0'=>'不限','1'=>'否','2'=>'是'];

        // 数据的格式化
        $order = 'id desc'; //修改修改 20190425
		$order = 'startDate asc';
		
        $map['live_id'] = $live_id;

        $this->searchPostUrl = U('live/AdminLive/ccLiveRoom',['id'=>$live_id]);

        $list = $this->_getCcLiveList('ccLiveRoom',20,$order,$map,0);

        // echo '<pre>';
        // print_r($list['data']);die;
        $this->assign('listdata',$list['data']);
        $this->assign('category',$category);
        // $this->display();
        $this->displayList($list);
    }


    //新增课程
    public function addCourse(){
        $post = $_POST;
        if(!empty($_POST)){
            $categoryid = $post['categoryid'];
            $courseid = $post['courseid'];
            $courseInfo = M("zy_live_thirdparty")->where("id=".$courseid)->find();
            $categoryInfo = M("zy_live_thirdparty")->where("categoryid=".$categoryid)->find();
            unset($courseInfo['id']);
            unset($courseInfo['type']);
            unset($courseInfo['type']);
            $courseInfo['type'] = $categoryInfo['type'];
            $courseInfo['categoryid'] = $categoryid;
            $courseInfo['live_id'] = $categoryInfo['live_id'];
            $insertLiveThtirdparty = M("zy_live_thirdparty")->add($courseInfo); 
            if(!empty($insertLiveThtirdparty)){
                echo  json_encode(['msg'=>1]); 
            }else{
                echo json_encode(['msg'=>3]);
            }
        }else{
            echo json_encode(['msg'=>2]);
        }


    }





    //转移直播
    public function associate(){
        $post = $_POST;

        $thirdparty = M('zy_live_thirdparty');
        $shift_live = $thirdparty->where("id=".$post['shift_live'])->find();
        if(empty($shift_live)){
            echo json_encode(['msg'=>"课程不存在"]);exit();
        }
        $type = $thirdparty->where("live_id=".$post['now_live_id'])->find();
        unset($shift_live['id']);
        unset($shift_live['type']);
        
        foreach ($shift_live as $key => $value) {
            $shift_live['live_id'] = $post['now_live_id'];
            $shift_live['type'] = $type['type'];
            $shift_live[$key]= $value;
        }
        $res = $thirdparty->add($shift_live);
        if($res){
            echo json_encode(['msg'=>"复制课程成功"]);
        }else{
            echo json_encode(['msg'=>"复制课程失败"]);
        }
                  





    }

    public function ccLiveRoom2(){
        header('Access-Control-Allow-Origin:*'); 
        $_REQUEST['tabHash'] = 'ccLiveRoom';

        $live_id = intval($_GET['id']);
        $this->assign('live_id',$live_id);

        $category=$this->MakeTree(0,0,0,$live_id);
        $this->assign('category',$category);

        $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
        if(!$liveInfo){
            $this->error("参数错误");
        }
        $this->pageTitle['ccLiveRoom'] = $liveInfo['video_title'].' 直播课堂—直播间列表';


        $this->pageKeyList = array('id','uname', 'subject', 'teacherToken','assistantToken', 'studentClientToken',
            'maxAttendees','startDate', 'invalidDate', 'clientJoin', 'webJoin', 'is_active','is_open', 'DOACTION');
        //搜索字段
        $this->searchKey = array('id','uname', 'subject',  ['startDate','invalidDate'], 'clientJoin', 'webJoin', 'is_active','is_del');

        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "新建", 'onclick' => "admin.jump('Cc',{$live_id})");
        

        $this->opt['clientJoin'] = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['webJoin']    = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['is_active']  = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['is_del']     = ['0'=>'不限','1'=>'否','2'=>'是'];

        // 数据的格式化
        $order = 'id desc'; //修改修改 20190425
        $order = 'startDate asc';
        
        $map['live_id'] = $live_id;

        $this->searchPostUrl = U('live/AdminLive/ccLiveRoom',['id'=>$live_id]);

        $list = $this->_getCcLiveList('ccLiveRoom2',1000,$order,$map,0);
        
        // echo '<pre>';
        // print_r($list['data']);die;
        $this->assign('listdata',$list['data']);
        $this->assign('listdata2',$list['data']);
        $this->assign('category',$category);
        $this->display('ccLiveRoom');
        // $this->displayList($list);
    }


    //克隆课程
    public function addCourse(){
        $post = $_POST;

        if(!empty($_POST)){
            $id = $post['categoryid'];
            $courseid = $post['courseid'];
            $live_category = M("zy_live_category");
            $courseInfo = $live_category->where("id=".$id)->find();
            $categoryid = $courseInfo['videoid'];
            unset($courseInfo['id']);
            $courseInfo['videoid'] = $courseid;
            $getAddId = $live_category->add($courseInfo);
            $live_thirdparty = M("zy_live_thirdparty");
            $categoryInfo = $live_thirdparty->where("categoryid=".$id)->select();
            // dump($categoryInfo);
            foreach($categoryInfo as $key => $value){
                unset($categoryInfo[$key]['id']);
                $categoryInfo[$key]['live_id'] = $courseid;
                $categoryInfo[$key]['categoryid'] = $getAddId;
                $insertLiveThtirdparty = M("zy_live_thirdparty")->add($categoryInfo[$key]); 
            }
            // dump($categoryInfo);
            if(!empty($insertLiveThtirdparty)){
                echo  json_encode(['msg'=>1]); 
            }else{
                echo json_encode(['msg'=>3]);
            }
        }else{
            echo json_encode(['msg'=>2]);
        }


    }





    /*
     * 第三方直播间-微吼
     */
    public function whLiveRoom(){
        $_REQUEST['tabHash'] = 'whLiveRoom';

        $live_id = intval($_GET['id']);
        $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
        if(!$liveInfo){
            $this->error("参数错误");
        }
        $this->pageTitle['whLiveRoom'] = $liveInfo['video_title'].' 直播课堂—直播间列表';

    //        'teacherToken','assistantToken', 'studentClientToken',
        $this->pageKeyList = array('id','uname', 'subject', 'roomid','startDate', 'invalidDate', 'clientJoin', 'is_active','is_open', 'DOACTION');
        //搜索字段
        $this->searchKey = array('id','uname', 'subject',  ['startDate','invalidDate'], 'clientJoin', 'is_active','is_del');

        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "新建", 'onclick' => "admin.jump('Wh',{$live_id})");

        $this->opt['clientJoin'] = ['0'=>'不限','1'=>'非公开','2'=>'公开'];
//        $this->opt['webJoin']    = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['is_active']  = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['is_del']     = ['0'=>'不限','1'=>'否','2'=>'是'];

        // 数据的格式化
        $order = 'id desc';
        $map['live_id'] = $live_id;
        $this->searchPostUrl = U('live/AdminLive/whLiveRoom',['id'=>$live_id]);

        $list = $this->_getWhLiveList('whLiveRoom',20,$order,$map);

        $this->displayList($list);
    }

    /*
     * 第三方直播间-CC小班课
     */
    public function ccXbkLiveRoom(){
        $_REQUEST['tabHash'] = 'ccXbkLiveRoom';

        $live_id = intval($_GET['id']);
        $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
        if(!$liveInfo){
            $this->error("参数错误");
        }
        $this->pageTitle['ccXbkLiveRoom'] = $liveInfo['video_title'].' 直播课堂—直播间列表';


        $this->pageKeyList = array('id','uname', 'subject', 'teacherToken','assistantToken', 'studentClientToken',
            'maxAttendees','startDate', 'invalidDate', 'clientJoin', 'webJoin', 'is_active','is_open', 'DOACTION');
        //搜索字段
        $this->searchKey = array('id','uname', 'subject',  ['startDate','invalidDate'], 'is_active','is_del');

        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "新建", 'onclick' => "admin.jump('CcXbk',{$live_id})");

        $this->opt['clientJoin'] = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['webJoin']    = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['is_active']  = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['is_del']     = ['0'=>'不限','1'=>'否','2'=>'是'];

        // 数据的格式化
        $order = 'id desc';
        $map['live_id'] = $live_id;

        $this->searchPostUrl = U('live/AdminLive/ccXbkLiveRoom',['id'=>$live_id]);
        $list = $this->_getCcXbkLiveList('ccXbkLiveRoom',20,$order,$map);

        $this->displayList($list);
    }

    /*
     * 第三方直播间-eeo小班课
     */
    public function eeoXbkLiveRoom(){
        $_REQUEST['tabHash'] = 'eeoXbkLiveRoom';

        $live_id = intval($_GET['id']);
        $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
        if(!$liveInfo){
            $this->error("参数错误");
        }
        $this->pageTitle['eeoXbkLiveRoom'] = $liveInfo['video_title'].' 直播课堂—直播间列表';


        $this->pageKeyList = array('id','uname', 'subject', 'maxAttendees','startDate', 'invalidDate', 'is_active','is_open', 'DOACTION');
        //搜索字段
        $this->searchKey = array('id','uname', 'subject',  ['startDate','invalidDate'], 'is_active','is_del');

        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "新建", 'onclick' => "admin.jump('EeoXbk',{$live_id})");

        $this->opt['is_active']  = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['is_del']     = ['0'=>'不限','1'=>'否','2'=>'是'];

        // 数据的格式化
        $order = 'id desc';
        $map['live_id'] = $live_id;

        $this->searchPostUrl = U('live/AdminLive/eeoXbkLiveRoom',['id'=>$live_id]);
        $list = $this->_getEeoXbkLiveList('eeoXbkLiveRoom',20,$order,$map);

        $this->displayList($list);
    }

    /*
     * 第三方直播间-拓课云
     */
    public function tkLiveRoom(){
        $_REQUEST['tabHash'] = 'tkLiveRoom';

        $live_id = intval($_GET['id']);
        $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
        if(!$liveInfo){
            $this->error("参数错误");
        }
        $this->pageTitle['tkLiveRoom'] = $liveInfo['video_title'].' 直播课堂—直播间列表';

        $this->pageKeyList   = array('id','uname','subject','startDate','invalidDate','scene','maxAttendees',
            'teacherToken','assistantToken','studentClientToken', 'is_active','is_open', 'DOACTION' );

        //搜索字段
        $this->searchKey = array('id','uname', 'subject',  ['startDate','invalidDate'], 'is_active','is_del');

        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "新建", 'onclick' => "admin.jump('Tk',{$live_id})");

        $this->opt['scene'] = ['0'=>'一对一','3'=>'一对多'];
        $this->opt['is_active']  = ['0'=>'不限','1'=>'否','2'=>'是'];
        $this->opt['is_del']     = ['0'=>'不限','1'=>'否','2'=>'是'];

        // 数据的格式化
        $order = 'id desc';
        $map['live_id'] = $live_id;

        $this->searchPostUrl = U('live/AdminLive/tkLiveRoom',['id'=>$live_id]);
        $list = $this->_getTkLiveList('tkLiveRoom',20,$order,$map);

        $this->displayList($list);
    }

    /*
     * 新建直播间-展示互动
     */
    public function addZshdLiveRoom(){
        $live_id = intval($_GET['id']);
        $this->assign('live_id',$live_id);
        if(isset($_POST)){

            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);
            $live_id=intval($_REQUEST['live_id']);
            $newTime = time();
            $map['subject'] = trim(t($_POST['subject']));
            $field = 'subject';
            $status = $_POST['status'];
            $thirdpartyid =$_POST['thirdpartyid'];
            $liveSubject = model('Live')->getZshdLiveRoomInfo($map,$field);

            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            // if($_POST['subject'] == $liveSubject['subject']){$this->error('已有此直播课时名称,请勿重复添加');}
            if($status==0)
            {
                if(empty($startDate)){$this->error('开始时间不能为空');}

                if($startDate < $newTime ){$this->error('开始时间必须大于当前时间');}
                if(empty($invalidDate)){$this->error('结束时间不能为空');}
                if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}
            }
            
            $live_time = model('Live')->liveRoom->where(['live_id'=>$live_id])->order('startDate asc')->field('startDate,invalidDate')->findAll();

            $live_time_res = false;
            foreach($live_time as $key => $val){
                if(($val['startDate'] < $startDate && $startDate < $val['invalidDate']) or ($val['startDate'] < $invalidDate && $invalidDate < $val['invalidDate'])){
                    $live_time_res = true;
                    break;
                }
            }
  
            // if($live_time_res) $this->error('当前课堂该时段已有直播');
            if($status==0)
            {
                if($_POST['clientJoin'] == 0){$clientJoin = 'false';}else{$clientJoin = 'true';}
                if($_POST['webJoin'] == 0){$webJoin = 'false';}else{$webJoin = 'true';}
                if($clientJoin == 'false' && $webJoin == 'false'){$this->error('Web端学生加入或客户端开启学生加入必须开启其一');}
                if(empty($_POST['teacherToken'])){$this->error('老师口令不能为空');}
                if(!is_numeric($_POST['teacherToken'])){$this->error('老师口令必须为数字');}
                if(strlen($_POST['teacherToken'])< 6 || strlen($_POST['teacherToken']) >15 ){$this->error('老师口令只能为6-15位数字');}
                if(empty($_POST['assistantToken'])){$this->error('助教口令不能为空');}
                if(!is_numeric($_POST['assistantToken'])){$this->error('助教口令必须为数字');}
                if(strlen($_POST['assistantToken'])< 6 || strlen($_POST['assistantToken']) >15 ){$this->error('助教口令只能为6-15位数字');}
                if(empty($_POST['studentToken'])){$this->error('学生WEB端口令不能为空');}
                if(!is_numeric($_POST['studentToken'])){$this->error('学生WEB端口令必须为数字');}
                if(strlen($_POST['studentToken'])< 6 || strlen($_POST['studentToken']) >15 ){$this->error('学生WEB端口令只能为6-15位数字');}
                if($_POST['teacherToken'] == $_POST['assistantToken'] || $_POST['teacherToken'] == $_POST['studentClientToken'] || $_POST['teacherToken'] == $_POST['studentToken'] || $_POST['assistantToken'] == $_POST['studentClientToken']
                    || $_POST['assistantToken'] == $_POST['studentToken'] || $_POST['studentClientToken'] == $_POST['studentToken']){
                    $this->error('四个口令的值不能相同');
                }
                
            }
            if(empty($_POST['description'])){$this->error('直播课时信息不能为空');}
            if(empty($_POST['scheduleInfo'])){$this->error('直播课时安排信息不能为空');}

            $video_time = M('zy_video')->where('id ='.$live_id) ->field('listingtime,uctime,maxmannums') ->find();
            if(t($_POST['maxAttendees']) > $video_time['maxmannums'])
            {
                $this->error('超过直播人数上限');
            }
            if($status==1)
            {
                $live_room_data = model('Live')->liveRoom->where('id='.$thirdpartyid )->find();
                if(empty($live_room_data))
                {
                    $this->error('没有获取到关联直播间');
                }
            }

            $speaker = M('zy_teacher')->where("id=".model('Live')->where(['id'=>$live_id])->getField('teacher_id'))->field('id,name,inro')->find();
            $url   = $this->zshd_config['api_url'].'/room/created?';
            $param = 'subject='.urlencode(t($_POST['subject'])).'&startDate='.t($startDate*1000).
                '&invalidDate='.t($invalidDate*1000).'&teacherToken='.t($_POST['teacherToken']).
                '&assistantToken='.t($_POST['assistantToken']).'&studentClientToken='.t($_POST['studentClientToken']).
                '&studentToken='.t($_POST['studentToken']).'&scheduleInfo='.urlencode(t($_POST['scheduleInfo'])).
                '&description='.urlencode(t($_POST['description'])).'&clientJoin='.$clientJoin.'&webJoin='.$webJoin.
                '&scene='.intval($_POST['scene']).'&uiMode='.intval($_POST['uiMode']).'&speakerInfo='.urlencode(t($speaker['inro']));
            $hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
            $url   = $url.$hash;
            if($status==0){

            $addLive = getDataByUrl($url);
            if($addLive['code'] == 0) {
                if(empty($addLive["number"])){$this->error('服务器创建失败');}
                //查此次插入数据库的课堂名称
                $url   = $this->zshd_config['api_url'].'/room/info?';
                $param = 'roomId='.$addLive["id"];
                $hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
                $url   = $url.$hash;
                $live = getDataByUrl($url);
                if(empty($live["number"])){$this->error('服务器查询失败');}

                if($addLive["clientJoin"]){$liveClientJoin = 1;}else{$liveClientJoin = 0;}
                if($addLive["webJoin"]){$liveWebJoin = 1;}else{$liveWebJoin = 0;}

                $data["uid"]            = $this->mid;
                $data["number"]         = $addLive["number"];
                $data["subject"]        = $live['subject'];
                $data["startDate"]      = $addLive["startDate"]/1000;
                $data["invalidDate"]    = $addLive["invalidDate"]/1000;
                $data["teacherJoinUrl"] = $addLive["teacherJoinUrl"];
                $data["studentJoinUrl"] = $addLive["studentJoinUrl"];
                $data["teacherToken"]   = $addLive["teacherToken"];
                $data["assistantToken"] = $addLive["assistantToken"];
                $data["studentClientToken"] = $addLive["studentClientToken"];
                $data["studentToken"]   = $addLive["studentToken"];
                $data["maxAttendees"]   = t($_POST['maxAttendees']);
                $data['scene']          = intval($_POST['scene']);
                $data['uiMode']         = intval($_POST['uiMode']);
                $data["clientJoin"]     = $liveClientJoin;
                $data["webJoin"]        = $liveWebJoin;
                $data["roomid"]         = $addLive["id"];
                $data["is_active"]      = 1;
                $data["live_id"]        = $live_id;
                $data["type"]           = 1;
                $data['attach_id']     = t($_POST['attach_ids']);

                $result = model('Live')->liveRoom->add($data);
                $this->assign( 'jumpUrl',U('live/AdminLive/zshdLiveRoom',array('id'=>$live_id)));
                if(!$result){$this->error('创建失败!');}
                $this->success('创建成功');
            } else {
                $this->error('服务器出错啦');
            }
        }
        if($status==1) {
            $data['uid']            = $this->mid;
            $data['roomid']         = $live_room_data['roomid'];
            $data['subject']        = $_POST['subject'];
            $data['startDate']      = $live_room_data['startDate'];
            $data['invalidDate']    = $live_room_data['invalidDate'];
            $data['maxAttendees']   = intval($live_room_data['maxAttendees']);
            $data['uiMode']         = $live_room_data['uiMode'];
            $data['clientJoin']     = $live_room_data['clientJoin'];
            $data['webJoin']        = $live_room_data['webJoin'];
            $data['teacherToken']   = $live_room_data['teacherToken'];
            $data['assistantToken'] = $live_room_data['assistantToken'];
            $data['studentClientToken'] = $live_room_data['studentClientToken'];
            $data['studentToken']   = $live_room_data['studentToken'];
            $data["scheduleInfo"]   = t($_POST['scheduleInfo']);
            $data["description"]    = t($_POST['description']);
            $data['teacherJoinUrl'] = $live_room_data['teacherJoinUrl'];
            $data['assistantJoinUrl'] = $live_room_data['assistantJoinUrl'];;
            $data['studentJoinUrl'] = $live_room_data['studentJoinUrl'];
            $data['is_del']         = 0;
            $data['is_active']      = 1;
            $data['live_id']        = $live_id;
            $data['type']           = 1;
            $data['status']         = 1;
            $data['types']          = $status;
            $data['categoryid']     = end($categoryid);
            $data['thirdpartyid']   = $_POST['thirdpartyid'];
            $data['attach_id']     = t($_POST['attach_ids']);
            $result = M('zy_live_thirdparty')->add($data);        
            
            if(!$result){$this->error('创建失败!');}
            $this->success('创建成功');
        }
            if($status==2) {
                    $data['uid']            = $this->mid;
                    $data['roomid']         = 0;
                    $data['subject']        = $_POST['subject'];
                    $data['startDate']      = $startDate;
                    $data['invalidDate']    = $invalidDate;
                    $data['maxAttendees']   = 200;
                    $data['uiMode']         = 0;
                    $data['clientJoin']     = 1;
                    $data['webJoin']        = 1;
                    $data['teacherToken']   = $_REQUEST['teacherToken'];
                    $data['assistantToken'] = $_REQUEST['assistantToken'];
                    $data['studentClientToken'] = $_REQUEST['studentClientToken'];
                    $data['studentToken']   = $_REQUEST['studentToken'];
                    $data['scheduleInfo']   =  $_REQUEST['scheduleInfo'];
                    $data['description']    =  $_REQUEST['description'];
                    $data['teacherJoinUrl'] =  $_REQUEST['teacherurl'];
                    $data['assistantJoinUrl'] =  $_REQUEST['teacherurl'];
                    $data['studentJoinUrl'] =  $_REQUEST['teacherurl'];
                    $data['playback_url']   =  $_REQUEST['studentJoinUrl'];
                    
                    $data['is_del']         = 0;
                    $data['is_active']      = 1;
                    $data['live_id']        = $live_id;
                    $data['type']           = 1;
                    $data['types']          = $status;

                    $data['categoryid']     = end($categoryid);
                    $data['thirdpartyid']   = $_POST['thirdpartyid'];
                    $data['attach_id']     = t($_POST['attach_ids']);
                    $result = M('zy_live_thirdparty')->add($data);
                    if(!$result){$this->error('创建失败!');}
                    $this->success('创建成功');
            }
        }else{
            $_REQUEST['tabHash'] = 'addZshdLiveRoom';

            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type,maxmannums');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }
            $this->pageTitle['addZshdLiveRoom'] = $liveInfo['video_title'].' 直播课堂—新建直播课时';

            $this->pageKeyList   = array('subject','startDate','invalidDate','scene','maxAttendees','uiMode','clientJoin','webJoin',
                'teacherToken','assistantToken','studentClientToken','studentToken','description' ,'scheduleInfo');
            $this->notEmpty = array('subject','startDate','invalidDate','maxAttendees','uiMode','clientJoin','webJoin',
                'teacherToken','assistantToken','studentClientToken','studentToken','description' ,'scheduleInfo');

            $this->opt['scene']         = array('0'=>'大讲堂','1'=>'小班课');
            $this->opt['uiMode']        = array('1'=>'模板一 视频直播+聊天互动+直播问答 三分屏','2'=>'模板二 视频为主 文档为小窗','3'=>'模板三 视频直播+直播文档 两分屏','4'=>'模板四 视频直播+聊天互动+直播文档+直播问答');
            $this->opt['clientJoin']    = array('1'=>'开启','0'=>'不开启');
            $this->opt['webJoin']       = array('1'=>'开启','0'=>'不开启');
            $this->savePostUrl = U('live/AdminLive/addZshdLiveRoom',['id'=>$live_id]);
            $this->assign('maxmannums',$liveInfo['maxmannums']);
            // $this->displayConfig(['maxAttendees'=>$liveInfo['maxmannums']]);
            $this->display('addZshdLiveRoom');
        }
    }

    /**
     *编辑直播课时-展示互动
     */
    public function editZshdLiveRoom(){
        $live_id = $_REQUEST['live_id'];
        $id = $_REQUEST['id'];
        $this->assign('id',$id);
        $this->assign('live_id',$live_id);
        if( isset($_POST) ) {
            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);
            $status = $_POST['status'];

            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            if(empty($startDate)){$this->error('开始时间不能为空');}
            if(empty($invalidDate)){$this->error('结束时间不能为空');}
            if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}

            $live_time = model('Live')->liveRoom->where(['live_id'=>$live_id])->order('startDate asc')->field('startDate,invalidDate')->findAll();

            $live_time_res = false;
            foreach($live_time as $key => $val){
                if(($val['startDate'] < $startDate && $startDate < $val['invalidDate']) or ($val['startDate'] < $invalidDate && $invalidDate < $val['invalidDate'])){
                    $live_time_res = true;
                    break;
                }
            }

            // if($live_time_res) $this->error('当前课堂该时段已有直播');
            if($status==0)
            {
                if(empty($_POST['maxAttendees'])){$this->error('最大并发不能为空');}
                if(!is_numeric($_POST['maxAttendees'])){$this->error('最大并发必须为数字');}
                if($_POST['clientJoin'] == 0){$clientJoin = 'false';}else{$clientJoin = 'true';}
                if($_POST['webJoin'] == 0){$webJoin = 'false';}else{$webJoin = 'true';}
                if($clientJoin == 'false' && $webJoin == 'false'){$this->error('Web端学生加入或客户端开启学生加入必须开启其一');}
                if(empty($_POST['teacherToken'])){$this->error('老师口令不能为空');}
                if(!is_numeric($_POST['teacherToken'])){$this->error('老师口令必须为数字');}
                if(strlen($_POST['teacherToken'])< 6 || strlen($_POST['teacherToken']) >15 ){$this->error('老师口令只能为6-15位数字');}
                if(empty($_POST['assistantToken'])){$this->error('助教口令不能为空');}
                if(!is_numeric($_POST['assistantToken'])){$this->error('助教口令必须为数字');}
                if(strlen($_POST['assistantToken'])< 6 || strlen($_POST['assistantToken']) >15 ){$this->error('助教口令只能为6-15位数字');}
                if(empty($_POST['studentToken'])){$this->error('学生WEB端口令不能为空');}
                if(!is_numeric($_POST['studentToken'])){$this->error('学生WEB端口令必须为数字');}
                if(strlen($_POST['studentToken'])< 6 || strlen($_POST['studentToken']) >15 ){$this->error('学生WEB端口令只能为6-15位数字');}
            }
            if($_POST['teacherToken'] == $_POST['assistantToken'] || $_POST['teacherToken'] == $_POST['studentClientToken'] || $_POST['teacherToken'] == $_POST['studentToken'] || $_POST['assistantToken'] == $_POST['studentClientToken']
                || $_POST['assistantToken'] == $_POST['studentToken'] || $_POST['studentClientToken'] == $_POST['studentToken']){
                $this->error('四个口令的值不能相同');
            }
            if(empty($_POST['scheduleInfo'])){$this->error('直播课时安排信息不能为空');}
            if(empty($_POST['description'])){$this->error('直播课时信息不能为空');}

            $video_time = M('zy_video')->where('id ='.$live_id) ->field('listingtime,uctime,maxmannums') ->find();
            if(t($_POST['maxAttendees']) > $video_time['maxmannums'])
            {
                $this->error('超过直播人数上限');
            }
            if($status==1)
            {
                $live_room_data = model('Live')->liveRoom->where('id='.$thirdpartyid )->find();
                if(empty($live_room_data))
                {
                    $this->error('没有获取到关联直播间');
                }
            }

            $speaker = M('zy_teacher')->where("id=".model('Live')->where(['id'=>$live_id])->getField('teacher_id'))->field('id,name,inro')->find();
            $url   = $this->zshd_config['api_url'].'/room/modify?';
            $param = 'id='.t($_POST['roomid']).'&subject='.urlencode(t($_POST['subject'])).'&startDate='.t($startDate*1000).
                '&invalidDate='.t($invalidDate*1000).'&teacherToken='.t($_POST['teacherToken']).
                '&assistantToken='.t($_POST['assistantToken']).'&studentClientToken='.t($_POST['studentClientToken']).
                '&studentToken='.t($_POST['studentToken']).'&scheduleInfo='.urlencode(t($_POST['scheduleInfo'])).
                '&description='.urlencode(t($_POST['description'])).'&clientJoin='.$clientJoin.'&webJoin='.$webJoin.
                '&scene='.intval($_POST['scene']).'&uiMode='.intval($_POST['uiMode']).'&speakerInfo='.urlencode(t($speaker['inro']));

            $hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
            $url   = $url.$hash;
            if($status==0)
            {
                $upLive = getDataByUrl($url);
         
                if($upLive['code'] == 0){
                    //查此次插入数据库的课堂名称
                    $url   = $this->zshd_config['api_url'].'/room/info?';
                    $param = 'roomId='.$_POST['roomid'];
                    $hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
                    $url   = $url.$hash;
                    $live = getDataByUrl($url);
                    if(empty($live["number"])){$this->error('服务器查询失败');}

                    if($live["clientJoin"]){$liveClientJoin = 1;}else{$liveClientJoin = 0;}
                    if($live["webJoin"]){$liveWebJoin = 1;}else{$liveWebJoin = 0;}

                    $data["subject"]        = $live['subject'];
                    $data["startDate"]      = $live['startDate']/1000;
                    $data["invalidDate"]    = $live['invalidDate']/1000;
                    $data["teacherToken"]   = $live['teacherToken'];
                    $data["assistantToken"] = $live['assistantToken'];
                    $data["studentClientToken"] = $live['studentClientToken'];
                    $data["studentToken"]   = $live['studentToken'];
                    $data["scheduleInfo"]   = t($_POST['scheduleInfo']);
                    $data["maxAttendees"]   = t($_POST['maxAttendees']);
                    $data["description"]    = t($_POST['description']);
                    $data["maxAttendees"]   = intval($_POST['maxAttendees']);
                    $data['uiMode']         = intval($_POST['uiMode']);
                    $data['scene']          = intval($_POST['scene']);
                    $data["clientJoin"]     = $liveClientJoin;
                    $data["webJoin"]        = $liveWebJoin;
                    $data['scheduleInfo'] =  $_REQUEST['scheduleInfo'];
                    $data['attach_id']     = t($_POST['attach_ids']);
                    $map = array('roomid'=>t($_POST['roomid']));

                    $result = model('Live')->updateZshdLiveInfo($map,$data);

                    if( $result !== false) {
                        // $this->assign( 'jumpUrl', U('live/AdminLive/zshdLiveRoom',array('id'=>intval($_GET['live_id']))) );
                        $this->success('修改成功');
                    } else {
                        $this->error('修改失败!');
                    }
                }else {
                    $this->error('服务器出错啦');
                }
            }
            if($status==1)
            {
                $data['subject']        = $_POST['subject'];
                $data['startDate']      = $live_room_data['startDate'];
                $data['invalidDate']    = $live_room_data['invalidDate'];
                $data['maxAttendees']   = intval($live_room_data['maxAttendees']);
                $data['uiMode']         = $live_room_data['uiMode'];
                $data['clientJoin']     = $live_room_data['clientJoin'];
                $data['webJoin']        = $live_room_data['webJoin'];
                $data['teacherToken']   = $live_room_data['teacherToken'];
                $data['assistantToken'] = $live_room_data['assistantToken'];
                $data['studentClientToken'] = $live_room_data['studentClientToken'];
                $data['studentToken']   = $live_room_data['studentToken'];
                $data["scheduleInfo"]   = t($_POST['scheduleInfo']);
                $data["description"]    = t($_POST['description']);
                $data['teacherJoinUrl'] = $live_room_data['teacherJoinUrl'];
                $data['assistantJoinUrl'] = $live_room_data['assistantJoinUrl'];;
                $data['studentJoinUrl'] = $live_room_data['studentJoinUrl'];
                $data['scheduleInfo'] =  $_REQUEST['scheduleInfo'];
                $data['live_id']        = $live_id;
                $data['categoryid']     = end($categoryid);
                $data['thirdpartyid']   = $_POST['thirdpartyid'];
                $data['attach_id']     = t($_POST['attach_ids']);
                $map = array('roomid'=>$live_room_data['roomid']);
                $result = model('Live')->updateZshdLiveInfo($map,$data);
                if( $result !== false) {
                        // $this->assign( 'jumpUrl', U('live/AdminLive/zshdLiveRoom',array('id'=>intval($_GET['live_id']))) );
                    $this->success('修改成功');
                } else {
                    $this->error('修改失败!');
                }
            }
            if($status==2)
            {
                
                    $data['subject']        = $_POST['subject'];
                    $data['startDate']      = $startDate;
                    $data['invalidDate']    = $invalidDate;
                    $data['teacherToken']   = $_REQUEST['teacherToken'];
                    $data['assistantToken'] = $_REQUEST['assistantToken'];
                    $data['studentClientToken'] = $_REQUEST['studentClientToken'];
                    $data['studentToken']   = $_REQUEST['studentToken'];
                    $data['description']    =  $_REQUEST['description'];
                    $data['teacherJoinUrl'] =  $_REQUEST['teacherurl'];
                    $data['assistantJoinUrl'] =  $_REQUEST['teacherurl'];
                    $data['studentJoinUrl'] =  $_REQUEST['teacherurl'];
                    $data['scheduleInfo'] =  $_REQUEST['scheduleInfo'];
                    $data['playback_url']   =  $_REQUEST['playback_url'];
                    $data['live_id']        = $live_id;
                    $data['types']          = $status;
                    $data['categoryid']     = end($categoryid);
                    $data['thirdpartyid']   = $_POST['thirdpartyid'];
                    $data['attach_id']     = t($_POST['attach_ids']);
                    $map = array('id'=>$_REQUEST['id']);
                    $result = model('Live')->updateZshdLiveInfo($map,$data);
                    if( $result !== false) {
                            // $this->assign( 'jumpUrl', U('live/AdminLive/zshdLiveRoom',array('id'=>intval($_GET['live_id']))) );
                        $this->success('修改成功');
                    } else {
                        $this->error('修改失败!');
                    }
            }
        }else{
            $_REQUEST['tabHash'] = 'editZshdLiveRoom';

            // 数据的格式化
            $data = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->find();
  // echo '<pre>';
  //           print_r($data);die;
            $live_info = model('Live')->findLiveInfo(array('id'=>$data['live_id']));
    
            $this->pageTitle['editZshdLiveRoom'] = $live_info['video_title'].' 直播课堂—修改直播课时:'.$data['subject'];

            $data['startDate'] = date('Y-m-d H:i:s',$data["startDate"]);
            $data['invalidDate'] = date('Y-m-d H:i:s',$data["invalidDate"]);
            if($data['webJoin'] == 1 && $data['clientJoin'] == 1){
                $this->pageKeyList   = array('roomid','subject','startDate','invalidDate','maxAttendees','scene','uiMode','clientJoin','webJoin','teacherToken','assistantToken','studentToken','studentClientToken','description','scheduleInfo');
            }else if($data['webJoin'] == 1){
                $this->pageKeyList   = array('roomid','subject','startDate','invalidDate','maxAttendees','scene','uiMode','clientJoin','webJoin','teacherToken','assistantToken','studentToken','description','scheduleInfo');
            }else if($data['clientJoin'] == 1){
                $this->pageKeyList   = array('roomid','subject','startDate','invalidDate','maxAttendees','scene','uiMode','clientJoin','webJoin','teacherToken','assistantToken','studentToken','studentClientToken','description','scheduleInfo');
            }
            $this->notEmpty = array('subject','startDate','invalidDate','maxAttendees','uiMode','clientJoin','webJoin','teacherToken','assistantToken','studentToken','studentClientToken','description','scheduleInfo');

            $this->opt['scene']         = array('0'=>'大讲堂','1'=>'小班课');
            $this->opt['uiMode']        = array('1'=>'模板一 视频直播+聊天互动+直播问答 三分屏','2'=>'模板二 视频为主 文档为小窗','3'=>'模板三 视频直播+直播文档 两分屏','4'=>'模板四 视频直播+聊天互动+直播文档+直播问答');
            $this->opt['clientJoin'] = array('1'=>'开启','0'=>'不开启');
            $this->opt['webJoin']      = array('1'=>'开启','0'=>'不开启');

            $ss=explode('|',$data['attach_id']);
          
            $data['attach_id']=$ss[1];
            $this->savePostUrl = U('live/AdminLive/editZshdLiveRoom',['id'=>$_REQUEST['id'],'live_id'=>$live_id]);
            // $this->displayConfig($data);
            $this->assign('live',$data);
            $this->display('editZshdLiveRoom');
        }
    }

    /**
     * 新建直播间-光慧
     */
    public function addGhLiveRoom(){
        $live_id = $_GET['id'];
        if( isset($_POST) ) {
            $url  = $this->gh_config['api_url'].'/openApi/createLiveRoom';
            $data = $_POST;
            unset($data['systemdata_list']);
            unset($data['systemdata_key']);
            unset($data['pageTitle']);
            $data['beginTime'] = strtotime($data['startDate']) * 1000;
            $data['endTime']   = strtotime($data['invalidDate']) * 1000;
            $data['uid']       = $this->mid;
            $data['is_active']  = 1;
            $data['live_id']  = $live_id;

            $video_time = M('zy_video')->where('id ='.$live_id) ->field('listingtime,uctime,maxmannums') ->find();
            if(strtotime($_POST['startDate']) <  intval($video_time['listingtime']))
            {
                $this->error('直播开始时间不能小于课程上架时间');
            }
            if(strtotime( $_POST['invalidDate']) > intval($video_time['uctime']))
            {
                $this->error('直播结束时间不能大于课程下架时间');
            }
            if(strtotime($_POST['startDate']) > strtotime($_POST['invalidDate']) )
            {
                $this->error('直播开始时间不能大于直播结束时间');
            }
            if(t($_POST['maxAttendees']) > $video_time['maxmannums'])
            {
                $this->error('超过直播人数上限');
            }


            $livegh = M('zy_live_gh');
            $data['startDate'] = $data['beginTime'];
            $data['invalidDate'] = $data['endTime'];
            $data['maxAttendees'] = $_POST['maxAttendees'];
            $roomid = $livegh->add($data);

            if( $roomid ) {
                $data['id'] = $roomid;
                $gh_data = M('zy_live_gh')->where('id='.intval($data['id']) )->find();
                $teacher_info = t(M('zy_teacher')->where('id='.intval($gh_data['speakert_id']))->getField('inro'));
                $data['teachers']  = json_encode( array( array('account'=>$data['account'] , 'passwd'=>base64_encode( md5($data['passwd'] , true) ) , 'info'=>$teacher_info ) ) );
                $data = array_merge($data , _ghdata() );
                $res = json_decode( request_post($url , $data) , true);


                if($res['code'] == 0) {
                    $data['room_id'] = $res['liveRoomId'];
                    $troom = $livegh->where('id='.$roomid)->save( $data );
                    if(!$troom)
                    {
                        $livegh ->rollback();
                        $this->error('创建失败!');
                    }

                    $this->assign( 'jumpUrl',U('live/AdminLive/ghLiveRoom',array('id'=>$live_id)));
                    $this->success('创建成功');
                } else {
                    //删除本地数据
                    M('zy_live_gh')->where('id='.$roomid)->delete();
                    $this->error('创建失败!');
                }
            } else {
                $livegh ->rollback();
                $this->error('创建失败');
            }
        } else {
            $_REQUEST['tabHash'] = 'addGhLiveRoom';

            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }
            $this->pageTitle['addGhLiveRoom'] = $liveInfo['video_title'].' 直播课堂—新建直播课时';

            $this->pageKeyList   = array('title','startDate','invalidDate','maxAttendees','supportMobile','liveMode','account','passwd','introduce');
            $this->notEmpty = array('title','startDate','invalidDate','maxAttendees','supportMobile','liveMode','account','passwd','introduce');

            $this->opt['supportMobile'] = array('0'=>'不支持','1'=>'支持');
            $this->opt['liveMode']      = array('1'=>'通用','2'=>'大视频','3'=>'1对1');

            $this->savePostUrl = U('live/AdminLive/addGhLiveRoom',['id'=>$live_id]);
            $this->displayConfig();
        }
    }

    /**
     * @return 修改光慧直播间
     */
    public function editGhLiveRoom(){
        if( isset($_POST) ) {
            $url = $this->gh_config['api_url'].'/openApi/updateLiveRoom';
            $data = $_POST;
            unset($data['systemdata_list']);
            unset($data['systemdata_key']);
            unset($data['pageTitle']);
            $gh_data = M('zy_live_gh')->where('id='.intval($data['id']) )->find();
            $teacher_info = t(M('zy_teacher')->where('id='.intval($gh_data['speakert_id']))->getField('inro'));
            $data['beginTime'] = strtotime($data['startDate']) * 1000;
            $data['endTime']   = strtotime($data['invalidDate']) * 1000;
            $data['teachers']  = json_encode( array( array('account'=>$data['account'] , 'passwd'=>base64_encode( md5($data['passwd'] , true) ) , 'info'=>$teacher_info ) ) );
            $data = array_merge($data , _ghdata());
            $res  = json_decode( request_post($url , $data) , true);

            $video_time = M('zy_video')->where('id ='.$gh_data['live_id']) ->field('listingtime,uctime,maxmannums') ->find();

            if(strtotime($_POST['startDate']) <  intval($video_time['listingtime']))
            {
                $this->error('直播开始时间不能小于课程上架时间');
            }

            if(strtotime($_POST['invalidDate']) > intval($video_time['uctime']))
            {
                $this->error('直播结束时间不能大于课程下架时间');
            }
            if(t($_POST['maxAttendees']) > $video_time['maxmannums'])
            {
                $this->error('超过直播人数上限');
            }

            $livegh = M('zy_live_gh');


            $data['is_active'] = 1;
            $data['maxAttendees'] = $_POST['maxAttendees'];
            if($res['code'] == 0) {
                unset($data['teachers']);
                $data['uid'] = $this->mid;
                $data['startDate'] = $data['beginTime'];
                $data['invalidDate'] = $data['endTime'];
                $res = $livegh ->where('id='.$data['id'])->save( $data );
                if( $res == false ) {
                    $livegh ->rollback();
                    $this->error('修改失败!');
                }
                $live_id = $livegh ->where('id='.intval($data['id']) )->getField('live_id');
                $this->assign( 'jumpUrl', U('live/AdminLive/ghLiveRoom',array('id'=>$live_id)) );
                $this->success('修改成功');

            } else {
                $livegh ->rollback();
                $this->error('修改失败');
            }
        } else {
            $_REQUEST['tabHash'] = 'editGhLiveRoom';
            $this->pageKeyList   = array('id','title','startDate','invalidDate','maxAttendees','supportMobile','liveMode','account','passwd','introduce');
            $this->notEmpty = array('title','startDate','invalidDate','maxAttendees','supportMobile','liveMode','account','passwd','introduce');

            $data = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->find();
            $live_info = model('Live')->findLiveInfo(array('id'=>$data['live_id']));
            $this->pageTitle['editGhLiveRoom'] = $live_info['video_title'].' 直播课堂—修改直播课时:'.$data['subject'];

            $this->opt['supportMobile'] = array('0'=>'不支持','1'=>'支持');
            $this->opt['liveMode']      = array('1'=>'通用','2'=>'大视频','3'=>'1对1');
            $data['startDate'] = date('Y-m-d H:i:s' , $data['startDate'] / 1000);
            $data['invalidDate']   = date('Y-m-d H:i:s' , $data['invalidDate'] / 1000);

            $this->savePostUrl = U('live/AdminLive/editGhLiveRoom');
            $this->displayConfig($data);
        }
    }

    /**
     * 新建直播间-CcLive
     */
    public function addCcLiveRoom(){
        $live_id = $_REQUEST['id'];
        $this->assign('live_id',$live_id);

        $sssy = $_REQUEST['sssy'];
        $this->assign('sssy',$sssy);
        $categoryid=intval($_REQUEST['categoryid']);

        $this->assign('categoryid',$categoryid);

        if( isset($_POST) ) {

            // if ($_FILES["file"]["error"] > 0)
            // {
            //     echo "Error: " . $_FILES["file"]["error"] . "<br />";
            // }
            // else
            // {
            //     echo "Upload: " . $_FILES["file"]["name"] . "<br />";
            //     echo "Type: " . $_FILES["file"]["type"] . "<br />";
            //     echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
            //     echo "Stored in: " . $_FILES["file"]["tmp_name"];
            // }

            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);
            $status = $_POST['status'];
            $thirdpartyid = $_POST['thirdpartyid'];
            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            if($status==0)
            {
                if(empty($startDate)){$this->error('开始时间不能为空');}
                if(empty($invalidDate)){$this->error('结束时间不能为空');}
                if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}
            }
            $categoryid=$_REQUEST['categoryid'];
            $list=M('zy_live_category')->where('pid=0 and videoid='.$live_id)->select();
            if(!empty($list)&&!empty($categoryid))
            {
                foreach ($categoryid as $k => $v) {
                    if($v==0)
                    {
                        $this->error('请选择直播标题');
                    }
                }
            }
        
            $live_time = model('Live')->liveRoom->where(['live_id'=>$live_id])->order('startDate asc')->field('startDate,invalidDate')->findAll();

          /*   $live_time_res = false;
            foreach($live_time as $key => $val){
                if(($val['startDate'] < $startDate && $startDate < $val['invalidDate']) or ($val['startDate'] < $invalidDate && $invalidDate < $val['invalidDate'])){
                    $live_time_res = true;
                    break;
                }
            }
           
            if($live_time_res) $this->error('当前课堂该时段已有直播'); */
            if(empty($_POST['teacherToken'])){$this->error('老师口令不能为空');}
            if(empty($_POST['maxAttendees'])){$this->error('最大并发不能为空');}
            if(!is_numeric($_POST['maxAttendees'])){$this->error('最大并发必须为数字');}
            if(empty($_POST['uiMode'])){$this->error('直播模版不能为空');}
            if($status==1)
            {
                $live_room_data = model('Live')->liveRoom->where('id='.$thirdpartyid )->find();
                if(empty($live_room_data))
                {
                    $this->error('没有获取到关联直播间');
                }
            }
            if($status==2)
            {
                if(empty($_POST['teacherurl'])){$this->error('链接地址不能为空');}
            }
            if(empty($_POST['teacherToken'])){$this->error('老师口令不能为空');}
            if(!is_numeric($_POST['teacherToken'])){$this->error('老师口令必须为数字');}
            if(strlen($_POST['teacherToken'])< 6 || strlen($_POST['teacherToken']) >15 ){$this->error('老师口令只能为6-15位数字');}
            if(empty($_POST['assistantToken'])){$this->error('助教口令不能为空');}
            if(!is_numeric($_POST['assistantToken'])){$this->error('助教口令必须为数字');}
            if(strlen($_POST['assistantToken'])< 6 || strlen($_POST['assistantToken']) >15 ){$this->error('助教口令只能为6-15位数字');}
            if(empty($_POST['studentClientToken'])){$this->error('学生口令不能为空');}
            if(!is_numeric($_POST['studentClientToken'])){$this->error('学生口令必须为数字');}
            if(strlen($_POST['studentClientToken'])< 6 || strlen($_POST['studentClientToken']) >15 ){$this->error('学生口令只能为6-15位数字');}
            if(empty($_POST['description'])){$this->error('直播课时信息不能为空');}
            if($status==0)
            {
                $video_time = M('zy_video')->where('id ='.$live_id) ->field('listingtime,uctime,maxmannums') ->find();
                if(t($_POST['maxAttendees']) > $video_time['maxmannums'])
                {
                    $this->error('超过直播人数上限');
                }
            }
            $url  = $this->cc_config['api_url'].'/room/create?';
            
            $query_map['name']              = urlencode(t($_POST['subject']));
            $query_map['desc']              = urlencode(t($_POST['description']));
            $query_map['templatetype']      = urlencode(t($_POST['uiMode']));
            $query_map['authtype']          = urlencode(1);
            $query_map['publisherpass']     = urlencode(t($_POST['teacherToken']));
            $query_map['assistantpass']     = urlencode(t($_POST['assistantToken']));
            $query_map['playpass']          = urlencode(t($_POST['studentClientToken']));
            $query_map['barrage']           = urlencode(t($_POST['clientJoin']));
            $query_map['livestarttime']         = urlencode(t($_POST['startDate']));
            if(intval($_POST['webJoin'])){
                $query_map['foreignpublish']    = urlencode(t($_POST['webJoin']));
            }
            $query_map['userid']            = urlencode($this->cc_config['user_id']);
            // $query_map['openlowdelaymode']  = urlencode();

            $url    = $url.createHashedQueryString($query_map)[1].'&time='.time().'&hash='.createHashedQueryString($query_map)[0];

            if($status==0){
				$res   = getDataByUrl($url);

                if($res['result'] == 'OK'){
                    if(empty($res['room']['id'])){$this->error('服务器创建失败');}

                    $get_live_info_url  = $this->cc_config['api_url'].'/room/search?';
                    $get_live_uri_info_url  = $this->cc_config['api_url'].'/room/code?';

                    $info_map['userid'] = urlencode($this->cc_config['user_id']);
                    $info_map['roomid'] = $res['room']['id'];

                    //查询服务器
                    $live_info_url = $get_live_info_url.createHashedQueryString($info_map)[1].'&time='.time().'&hash='.createHashedQueryString($info_map)[0];
                    $live_url_info_url = $get_live_uri_info_url.createHashedQueryString($info_map)[1].'&time='.time().'&hash='.createHashedQueryString($info_map)[0];

                    $live_info_res   = getDataByUrl($live_info_url);
                    $live_url_info_res   = getDataByUrl($live_url_info_url);
         
                    if($live_info_res['result'] != 'OK' || $live_url_info_res['result'] != 'OK'){$this->error('服务器查询失败');}

                    $live_info_res          = $live_info_res['room'];
                    $data['uid']            = $this->mid;
                    $data['roomid']         = $live_info_res['id'];
                    $data['subject']        = $live_info_res['name'];
                    $data['startDate']      = $startDate;
                    $data['invalidDate']    = $invalidDate;
                    $data['maxAttendees']   = intval($_POST['maxAttendees']);
                    $data['uiMode']         = $live_info_res['templateType'];
                    $data['clientJoin']     = $live_info_res['barrage'];
                    $data['webJoin']        = $live_info_res['foreignPublish'];
                    $data['teacherToken']   = $live_info_res['publisherPass'];
                    $data['assistantToken'] = $live_info_res['assistantPass'];
                    $data['studentClientToken'] = $live_info_res['playPass'];
                    $data['description']    = $live_info_res['desc'];
                    $data['teacherJoinUrl'] = $live_url_info_res['clientLoginUrl'];
                    $data['assistantJoinUrl'] = explode('?',$live_url_info_res['assistantLoginUrl'])[0].'/login?'.explode('?',$live_url_info_res['assistantLoginUrl'])[1];
                    $data['studentJoinUrl'] = $live_url_info_res['viewUrl'];
                    $data['is_del']         = 0;
                    $data['is_active']      = 1;
                    $data['live_id']        = $live_id;
                    $data['type']           = 4;
                     $data['types']          = $status;
                    $data['categoryid']     = end($categoryid);
                    $data['attach_id']     = t($_POST['attach_ids']);
                    $result = M('zy_live_thirdparty')->add($data);

                    if(!$result){$this->error('创建失败!');}
                    if($sssy==1)
                    {
                        $this->assign( 'jumpUrl', U('live/AdminLive/ccLiveRoom2',array('id'=>$live_id)) );
                    }else{
                        $this->assign( 'jumpUrl', U('live/AdminLive/ccLiveRoom',array('id'=>$live_id)) );
                    }
                    
                    $this->success('创建成功');
                }else{
                    $this->error('服务器出错啦');
                }
            }
            if($status==1) {
                    $data['uid']            = $this->mid;
                    $data['roomid']         = $live_room_data['roomid'];
                    $data['subject']        = $_POST['subject'];
                    $data['startDate']      = $live_room_data['startDate'];
                    $data['invalidDate']    = $live_room_data['invalidDate'];
                    $data['maxAttendees']   = intval($live_room_data['maxAttendees']);
                    $data['uiMode']         = $live_room_data['uiMode'];
                    $data['clientJoin']     = $live_room_data['clientJoin'];
                    $data['webJoin']        = $live_room_data['webJoin'];
                    $data['teacherToken']   = $live_room_data['teacherToken'];
                    $data['assistantToken'] = $live_room_data['assistantToken'];
                    $data['studentClientToken'] = $live_room_data['studentClientToken'];
                    $data['description']    = $live_room_data['desc'];
                    $data['teacherJoinUrl'] = $live_room_data['teacherJoinUrl'];
                    $data['assistantJoinUrl'] = $live_room_data['assistantJoinUrl'];;
                    $data['studentJoinUrl'] = $live_room_data['studentJoinUrl'];
                    $data['is_del']         = 0;
                    $data['is_active']      = 1;
                    $data['live_id']        = $live_id;
                    $data['type']           = 4;
                    $data['status']         = 1;
                    $data['types']          = $status;
                    $data['categoryid']     = end($categoryid);
                    $data['thirdpartyid']   = $_POST['thirdpartyid'];
                    $data['attach_id']     = t($_POST['attach_ids']);
                    $result = M('zy_live_thirdparty')->add($data);
                    
           
                    if(!$result){$this->error('创建失败!');}
                    if($sssy==1)
                    {
                        $this->assign( 'jumpUrl', U('live/AdminLive/ccLiveRoom2',array('id'=>$live_id)) );
                    }else{
                        $this->assign( 'jumpUrl', U('live/AdminLive/ccLiveRoom',array('id'=>$live_id)) );
                    }
                    $this->success('创建成功');
            }
            if($status==2) {
                    $data['uid']            = $this->mid;
                    $data['roomid']         = 0;
                    $data['subject']        = $_POST['subject'];
                    $data['startDate']      = $startDate;
                    $data['invalidDate']    = $invalidDate;
                    $data['maxAttendees']   = 200;
                    $data['uiMode']         = 0;
                    $data['clientJoin']     = 1;
                    $data['webJoin']        = 0;
                    $data['teacherToken']   = $_REQUEST['teacherToken'];
                    $data['assistantToken'] = $_REQUEST['assistantToken'];
                    $data['studentClientToken'] = $_REQUEST['studentClientToken'];
                    $data['description']    =  $_REQUEST['description'];
                    $data['teacherJoinUrl'] =  $_REQUEST['teacherurl'];
                    $data['assistantJoinUrl'] =  $_REQUEST['teacherurl'];
                    $data['studentJoinUrl'] =  $_REQUEST['teacherurl'];
                    $data['is_del']         = 0;
                    $data['is_active']      = 1;
                    $data['live_id']        = $live_id;
                    $data['type']           = 4;
                    $data['types']          = $status;

                    $data['categoryid']     = end($categoryid);
                    $data['thirdpartyid']   = $_POST['thirdpartyid'];
                    $data['attach_id']     = t($_POST['attach_ids']);
                    $result = M('zy_live_thirdparty')->add($data);
                    
           
                    if(!$result){$this->error('创建失败!');}
                    if($sssy==1)
                    {
                        $this->assign( 'jumpUrl', U('live/AdminLive/ccLiveRoom2',array('id'=>$live_id)) );
                    }else{
                        $this->assign( 'jumpUrl', U('live/AdminLive/ccLiveRoom',array('id'=>$live_id)) );
                    }
                    $this->success('创建成功');
            }

            
        } else {
            $_REQUEST['tabHash'] = 'addCcLiveRoom';
//            $this->onsubmit = 'admin.checkAddCc(this)';
//            $this->onload[] = 'admin.checkLoadCC()';

            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type,maxmannums');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }

            $this->pageTitle['addCcLiveRoom'] = $liveInfo['video_title'].' 直播课堂—新建直播课时';

            $this->pageKeyList   = array('subject','startDate','invalidDate','maxAttendees','uiMode','clientJoin',
                'webJoin','teacherToken','assistantToken','studentClientToken' ,'description');
            $this->notEmpty = array('subject','startDate','invalidDate','maxAttendees','uiMode','clientJoin',
                'webJoin','teacherToken','assistantToken','studentClientToken' ,'description');

            $this->opt['uiMode'] = array('1'=>'模板一 视频直播','2'=>'模板二 视频直播+聊天互动+直播问答','3'=>'模板三 视频直播+聊天互动',
                '4'=>'模板四 视频直播+聊天互动+直播文档','5'=>'模板五 视频直播+聊天互动+直播文档+直播问答','6'=>'模板六 视频直播+直播问答');
            $this->opt['clientJoin'] = array('0'=>'否','1'=>'是');
            $this->opt['webJoin']    = array('0'=>'否','1'=>'是');

            $this->savePostUrl = U('live/AdminLive/addCcLiveRoom',['id'=>$live_id]);
            /** 获取我的第一层 */
        
            $plevel = M('zy_live_category')->where('pid = 0 and videoid='.$live_id)->order('`sort` DESC')->select();

            $this->assign('plevel',$plevel);
            $this->display('addCcLiveRoom');

            // $this->displayConfig(['maxAttendees'=>$liveInfo['maxmannums']]);
        }

    }

    public function ajaxlive(){
        $ceng=$_REQUEST['ceng'];
        $where['pid']=$_REQUEST['lv'];
        $ceng=$ceng+1;
        $plevel = M('zy_live_category')->where($where)->order('`sort` DESC')->select();
        $op='';
        $data['msg']=0;
        if(!empty($plevel))
        {
            $op.="<select name='categoryid[] lastid' id='ceng".$ceng."'  onchange='haizi(".$ceng.")'>";
            $op.="<option value='0' >请选择</option>";
            foreach ($plevel as $k => $v) {
               
                $op.="<option value='".$v['id']."'>".$v['title']."</option>";
                
            }
            $op.="</select>";
            $data['con']=$op;
            $data['msg']=1;
        }
       
        exit(json_encode($data));

    }


    /**
     * 编辑直播间-CcLive
     */
    public function editCcLiveRoom(){
        $live_id = $_REQUEST['live_id'];
        $id = $_REQUEST['id'];
        $this->assign('id',$id);
        $this->assign('live_id',$live_id);

        if( isset($_POST) ) {
            //dump($_POST);
            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);
            $status = $_POST['status'];
            $thirdpartyid =$_POST['thirdpartyid'];
            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            if(empty($startDate)){$this->error('开始时间不能为空');}
            if(empty($invalidDate)){$this->error('结束时间不能为空');}
            if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}
            
          /*   $live_time = model('Live')->liveRoom->where(['live_id'=>$live_id])->order('startDate asc')->field('startDate,invalidDate')->findAll();

            $live_time_res = false;
            foreach($live_time as $key => $val){
                if(($val['startDate'] < $startDate && $startDate < $val['invalidDate']) or ($val['startDate'] < $invalidDate && $invalidDate < $val['invalidDate'])){
                    $live_time_res = true;
                    break;
                }
            }
            

            if($live_time_res) $this->error('当前课堂该时段已有直播'); */
            if($status==0)
            {
                if(empty($_POST['maxAttendees'])){$this->error('最大并发不能为空');}
                if(!is_numeric($_POST['maxAttendees'])){$this->error('最大并发必须为数字');}
                if(empty($_POST['teacherToken'])){$this->error('老师口令不能为空');}
                if(!is_numeric($_POST['teacherToken'])){$this->error('老师口令必须为数字');}
                if(strlen($_POST['teacherToken'])< 6 || strlen($_POST['teacherToken']) >15 ){$this->error('老师口令只能为6-15位数字');}
                if(empty($_POST['assistantToken'])){$this->error('助教口令不能为空');}
                if(!is_numeric($_POST['assistantToken'])){$this->error('助教口令必须为数字');}
                if(strlen($_POST['assistantToken'])< 6 || strlen($_POST['assistantToken']) >15 ){$this->error('助教口令只能为6-15位数字');}
                if(empty($_POST['studentClientToken'])){$this->error('学生口令不能为空');}
                if(!is_numeric($_POST['studentClientToken'])){$this->error('学生口令必须为数字');}
                if(strlen($_POST['studentClientToken'])< 6 || strlen($_POST['studentClientToken']) >15 ){$this->error('学生口令只能为6-15位数字');}
            }
            $categoryid=$_REQUEST['categoryid'];
            $list=M('zy_live_category')->where('pid=0 and videoid='.$live_id)->select();
            
            if(!empty($list))
            {   
                if(!empty($categoryid))
                {
                    foreach ($categoryid as $k => $v) {
                        if($v==0)
                        {
                            $this->error('请选择直播标题');break;
                        }
                    }
                }else{
                    $this->error('请选择直播标题');
                }
                
            }
            if($status==1)
            {
                $live_room_data = model('Live')->liveRoom->where('id='.$thirdpartyid )->find();
                
                if(empty($live_room_data))
                {
                    $this->error('没有获取到关联直播间');
                }
            }
       
            if(empty($_POST['description'])){$this->error('直播课时信息不能为空');}
                // $live = M('zy_live_thirdparty');
                $data['id']             = intval($_REQUEST['id']);

                $data['uid']            = $this->mid;
                $data['subject']        = $_POST['subject'];
               // if($status==0)
                //{
                    $data['thirdpartyid']   = $_POST['thirdpartyid'];
                    //$data['roomid']         = $live_room_data['roomid'];
                    //$data['startDate']      = $live_room_data['startDate'];
                    //$data['invalidDate']    = $live_room_data['invalidDate'];
                   // $data['maxAttendees']   = intval($live_room_data['maxAttendees']);
                    $data['uiMode']         = $live_room_data['uiMode'];
                    //$data['clientJoin']     = $live_room_data['clientJoin'];
                    $data['clientJoin']     = $_REQUEST['clientJoin'];
                    $data['webJoin']        = $live_room_data['webJoin'];
                    //$data['teacherToken']   = $live_room_data['teacherToken'];
                    //$data['assistantToken'] = $live_room_data['assistantToken'];
                    //$data['studentClientToken'] = $live_room_data['studentClientToken'];
                    //$data['teacherJoinUrl'] = $live_room_data['teacherJoinUrl'];
                    //$data['assistantJoinUrl'] = $live_room_data['assistantJoinUrl'];;
                   // $data['studentJoinUrl'] = $live_room_data['studentJoinUrl'];
                //}else{
                    $data['maxAttendees']   = $_REQUEST['maxAttendees'];
                    $data['startDate']      = $startDate;
                    $data['invalidDate']    = $invalidDate;
                    $data['teacherToken']   = $_REQUEST['teacherToken'];
                    $data['assistantToken'] = $_REQUEST['assistantToken'];
                    $data['studentClientToken'] = $_REQUEST['studentClientToken'];
               // }

                $data['description']    = $_REQUEST['description'];
                $data['is_del']         = 0;
                $data['is_active']      = 1;
                $data['live_id']        = $live_id;
                $data['type']           = 4;
                $data['status']         = $status;
                $data['attach_id']     = t($_POST['attach_ids']);
                $data['categoryid']     = implode(',',$categoryid);
                // $result = $live->data($data)->save();
                $result = model('Live')->liveRoom->save($data);
                
                //die;
                // if(false === $result){
                //     echo model('Live')->getDbError();die;
                // }
                if(!$result){$this->error('编辑失败!');}
                if("ccLiveRoom" == $_REQUEST['url']){
                    $url = 'live/AdminLive/ccLiveRoom';
                }else{
                    $url = 'live/AdminLive/ccLiveRoom2';
                }
                $this->assign( 'jumpUrl', U($url,array('id'=>intval($_REQUEST['live_id']))) );
                $this->success('编辑成功');

        } else {
            $_REQUEST['tabHash'] = 'editCcLiveRoom';
//            $this->onsubmit = 'admin.checkAddCc(this)';

            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }
        
            // 数据的格式化
            $live_room_data = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->find();
  
            $this->pageTitle['editCcLiveRoom'] = $liveInfo['video_title'].' 直播课堂—修改直播课时:'.$live_room_data['subject'];

            $live_room_data['startDate'] = date('Y-m-d H:i:s',$live_room_data["startDate"]);
            $live_room_data['invalidDate'] = date('Y-m-d H:i:s',$live_room_data["invalidDate"]);

            $this->savePostUrl = U('live/AdminLive/editCcLiveRoom',['id'=>$_GET['id'],'live_id'=>$live_id]);
            $plevel = M('zy_live_category')->where('pid = 0 and videoid='.$live_id)->order('`sort` DESC')->select();

            $this->assign('plevel',$plevel);
            $this->assign('liveInfo',$liveInfo);
            $ss=explode('|',$live_room_data['attach_id']);
            // $live_room_data['attach_id']=$ss[1];
            $trim = array_values(array_filter($ss));
            $live_room_data['attach_id'] = $trim;
            $this->assign('live',$live_room_data);
            /** 获取直播标题 */
            $where['id']=array('in',$live_room_data['categoryid']);
            $list=M('zy_live_category')->where($where)->select();
            foreach ($list as $k => $v) {
                $ll[]=$v['title'];
            }
            $ql=implode(' > ',$ll);

            $this->assign('catelist',$list);
            $this->assign('ql',$ql);
            $this->display();
        }
    }

    /**
     * 新建直播间-微吼
     */
    public function addWhLiveRoom(){
        $live_id = $_GET['id'];

        if( isset($_POST) ) {
            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);

            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            if(strlen(t($_POST['subject'])) > 50){$this->error('直播课时名称不能超过50个字符/25个汉字');}
            if(empty($startDate)){$this->error('开始时间不能为空');}
            if(empty($invalidDate)){$this->error('结束时间不能为空');}
            if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}

            $live_time = model('Live')->liveRoom->where(['live_id'=>$live_id])->order('startDate asc')->field('startDate,invalidDate')->findAll();

            $live_time_res = false;
            foreach($live_time as $key => $val){
                if(($val['startDate'] < $startDate && $startDate < $val['invalidDate']) or ($val['startDate'] < $invalidDate && $invalidDate < $val['invalidDate'])){
                    $live_time_res = true;
                    break;
                }
            }

            if($live_time_res) $this->error('当前课堂该时段已有直播');

            if(empty($_POST['uiMode'])){$this->error('直播模版不能为空');}
            if(empty($_POST['description'])){$this->error('直播课时信息不能为空');}

            $url  = $this->wh_config['api_url'].'/api/vhallapi/v2/webinar/create';
            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'teacher_id');
            $speaker = M('zy_teacher')->where("id={$liveInfo['teacher_id']}")->field('id,name,inro')->find();

            $query_data['subject']           = t($_POST['subject']);
            $query_data['start_time']        = strtotime(t($_POST['startDate']));
            $query_data['layout']            = intval($_POST['uiMode']);
            $query_data['type']              = intval($_POST['clientJoin']);
            $query_data['auto_record']       = 1;
            $query_data['introduction']      = t($_POST['description']);
            $query_data['host']              = t($speaker['name']);
            $query_data['auth_type'] = $find_data['auth_type'] = 2;
            $query_data['app_key']   = $find_data['app_key']   = t($this->wh_config['api_key']);
            $query_data['signed_at'] = $find_data['signed_at'] = time();
            $query_data['sign']              = createSignQueryString($query_data);

            $live_res   = getDataByPostUrl($url,$query_data);

            if($live_res->code == 200){
                if(empty($live_res->data)){$this->error('服务器创建失败');}
//
                $get_live_info_url       = $this->wh_config['api_url'].'/api/vhallapi/v2/webinar/fetch';
                $find_data['webinar_id']  = $live_res->data;
                $find_data['fields']      = "id,subject,introduction,layout,is_open";
                $find_data['sign']        = createSignQueryString($find_data);
                $live_info_res           = getDataByPostUrl($get_live_info_url,$find_data);
                unset($find_data['fields']);
                unset($find_data['sign']);
                $find_data['is_sec_auth'] = 0;
                $find_data['sign']        = createSignQueryString($find_data);
                $get_teacher_url         = $this->wh_config['api_url'].'/api/vhallapi/v2/webinar/start';
                $get_other_url           = $this->wh_config['api_url'].'/api/vhallapi/v2/guest/url';
                $teacher_join_url        = getDataByPostUrl($get_teacher_url,$find_data);
                unset($find_data['sign']);
                $find_data['email']       = "eduline@eduline.net";
                $find_data['name']        = "Eduline";
                $find_data['type']        = "2";
                $find_data['sign']        = createSignQueryString($find_data);
                $assistant_join_url      = getDataByPostUrl($get_other_url,$find_data);
                //以下注释的是特邀嘉宾的api  勿删
                //unset($find_data['type']);
                //unset($find_data['sign']);
                //$find_data['type']        = "1";
                //$find_data['sign']        = createSignQueryString($find_data);
                //$other_join_url          = getDataByPostUrl($get_other_url,$find_data);
                //dump($other_join_url);

                //查询服务器
                if($live_info_res->code != 200 || $teacher_join_url->code != 200){$this->error('服务器查询失败');}
                $live_info_res          = $live_info_res->data;
                $data['uid']            = $this->mid;
                $data['roomid']         = $live_res->data;
                $data['subject']        = $live_info_res->subject;
                $data['startDate']      = $startDate;
                $data['invalidDate']    = $invalidDate;
                $data['uiMode']         = $live_info_res->layout;
                $data['clientJoin']     = $live_info_res->is_open;
                //$data['webJoin']        = $live_info_res['foreignPublish'];
                //$data['teacherToken']   = $live_info_res['publisherPass'];
                //$data['assistantToken'] = $live_info_res['assistantPass'];
                //$data['studentClientToken'] = $live_info_res['playPass'];
                $data['description']    = $live_info_res->introduction;
                $data['teacherJoinUrl'] = $teacher_join_url->data;
                $data['assistantJoinUrl'] = $assistant_join_url->data;
                $data['studentJoinUrl'] = "{$this->wh_config['api_url']}/webinar/inituser/{$live_res->data}";
                $data['is_del']         = 0;
                $data['is_active']      = 1;
                $data['live_id']        = $live_id;
                $data['type']           = 5;

                $result = model('Live')->liveRoom->add($data);
                if(!$result){$this->error('创建失败!');}
                $this->assign( 'jumpUrl', U('live/AdminLive/whLiveRoom',array('id'=>$live_id)) );
                $this->success('创建成功');
            }else{
                $this->error('服务器出错啦');
            }
        } else {
            $_REQUEST['tabHash'] = 'addWhLiveRoom';
//            $this->onsubmit = 'admin.addCcLiveRoom(this)';['maxAttendees'=>$liveInfo['maxmannums']]
//            $this->onload[] = 'admin.checkLoadCC()';
            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type,maxmannums,teacher_id');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }
            $this->pageTitle['addWhLiveRoom'] = $liveInfo['video_title'].' 直播课堂—新建直播课时';

            $this->pageKeyList   = array('subject','startDate','invalidDate','clientJoin','uiMode','description');
            $this->notEmpty = array('subject','startDate','invalidDate','uiMode','webJoin','description');

            $this->opt['uiMode'] = array('1'=>'模板一 视频直播+聊天互动','2'=>'模板二 文档直播+聊天互动','3'=>'模板三 文档+视频+聊天互动');
            $this->opt['clientJoin'] = array('1'=>'是','0'=>'否');
            $this->savePostUrl = U('live/AdminLive/addWhLiveRoom',['id'=>$live_id]);
            $this->displayConfig(['maxAttendees'=>$liveInfo['maxmannums']]);
        }
    }

    /**
     * 编辑直播间-微吼
     */
    public function editWhLiveRoom(){
        $live_id = intval($_GET['live_id']);

        if( isset($_POST) ) {
            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);

            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            if(strlen(t($_POST['subject'])) > 50){$this->error('直播课时名称不能超过50个字符/25个汉字');}
            if(empty($startDate)){$this->error('开始时间不能为空');}
            if(empty($invalidDate)){$this->error('结束时间不能为空');}
            if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}
            if(empty($_POST['uiMode'])){$this->error('直播模版不能为空');}
            if(empty($_POST['description'])){$this->error('直播课时信息不能为空');}

            $roomid = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->getField('roomid');
            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'teacher_id');
            $speaker = M('zy_teacher')->where("id={$liveInfo['teacher_id']}")->field('id,name,inro')->find();

            $url  = $this->wh_config['api_url'].'/api/vhallapi/v2/webinar/update';

            $up_data['webinar_id'] = $find_data['webinar_id'] = $roomid;
            $up_data['subject']           = t($_POST['subject']);
            $up_data['start_time']        = strtotime(t($_POST['startDate']));
            $up_data['layout']            = intval($_POST['uiMode']);
            $up_data['is_open']           = intval($_POST['clientJoin']);
            $up_data['introduction']      = t($_POST['description']);
            $up_data['host']              = t($speaker['name']);
            $up_data['auth_type'] = $find_data['auth_type'] = 2;
            $up_data['app_key']   = $find_data['app_key']   = t($this->wh_config['api_key']);
            $up_data['signed_at'] = $find_data['signed_at'] = time();
            $up_data['sign']              = createSignQueryString($up_data);

            $up_res   = getDataByPostUrl($url,$up_data);

            if($up_res->code == 200){
                //查询服务器
                $get_live_info_url       = $this->wh_config['api_url'].'/api/vhallapi/v2/webinar/fetch';
                $find_data['fields']      = "id,subject,introduction,layout,is_open";
                $find_data['sign']        = createSignQueryString($find_data);
                $live_info_res           = getDataByPostUrl($get_live_info_url,$find_data);

                if($live_info_res->code != 200){$this->error('服务器查询失败');}

                $live_info_res          = $live_info_res->data;
                $data['id']             = intval($_GET['id']);
                $data['uid']            = $this->mid;
                $data['subject']        = $live_info_res->subject;
                $data['startDate']      = $startDate;
                $data['invalidDate']    = $invalidDate;
                $data['uiMode']         = $live_info_res->layout;
                $data['clientJoin']     = $live_info_res->is_open;
                //$data['webJoin']        = $live_info_res['foreignPublish'];
                //$data['teacherToken']   = $live_info_res['publisherPass'];
                //$data['assistantToken'] = $live_info_res['assistantPass'];
                //$data['studentClientToken'] = $live_info_res['playPass'];
                $data['description']    = $live_info_res->introduction;

                $result = model('Live')->liveRoom->save($data);

                if(!$result){$this->error('编辑失败!');}
                $this->assign( 'jumpUrl', U('live/AdminLive/whLiveRoom',array('id'=>intval($_GET['live_id']))) );
                $this->success('编辑成功');
            }else{
                $this->error('服务器出错啦');
            }
        } else {
            $_REQUEST['tabHash'] = 'editWhLiveRoom';
            //$this->onsubmit = 'admin.addWhLiveRoom(this)';

            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }
            // 数据的格式化
            $live_room_data = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->find();

            $this->pageTitle['editWhLiveRoom'] = $liveInfo['video_title'].' 直播课堂—修改直播课时:'.$live_room_data['subject'];

            $live_room_data['startDate'] = date('Y-m-d H:i:s',$live_room_data["startDate"]);
            $live_room_data['invalidDate'] = date('Y-m-d H:i:s',$live_room_data["invalidDate"]);


            $this->pageKeyList   = array('subject','startDate','invalidDate','clientJoin','uiMode','description');
            $this->notEmpty = array('subject','startDate','invalidDate','clientJoin','uiMode','webJoin','description');

            $this->opt['uiMode'] = array('1'=>'模板一 视频直播+聊天互动','2'=>'模板二 文档直播+聊天互动','3'=>'模板三 文档+视频+聊天互动');
            $this->opt['clientJoin'] = array('1'=>'是','0'=>'否');

            $this->savePostUrl = U('live/AdminLive/editWhLiveRoom',['id'=>$_GET['id'],'live_id'=>$live_id]);
            $this->displayConfig($live_room_data);
        }
    }

    /**
     * 新建直播间-CcLive-小班课
     */
    public function addCcXbkLiveRoom(){
        $live_id = $_GET['id'];

        if( isset($_POST) ) {
            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);

            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            if(empty($startDate)){$this->error('开始时间不能为空');}
            if(empty($invalidDate)){$this->error('结束时间不能为空');}
            if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}

            $live_time = model('Live')->liveRoom->where(['live_id'=>$live_id])->order('startDate asc')->field('startDate,invalidDate')->findAll();

            $live_time_res = false;
            foreach($live_time as $key => $val){
                if(($val['startDate'] < $startDate && $startDate < $val['invalidDate']) or ($val['startDate'] < $invalidDate && $invalidDate < $val['invalidDate'])){
                    $live_time_res = true;
                    break;
                }
            }

            if($live_time_res) $this->error('当前课堂该时段已有直播');

            if(empty($_POST['maxAttendees'])){$this->error('最大并发不能为空');}
            if(!is_numeric($_POST['maxAttendees'])){$this->error('最大并发必须为数字');}
            if(empty($_POST['uiMode'])){$this->error('直播模版不能为空');}
            if(empty($_POST['teacherToken'])){$this->error('老师口令不能为空');}
            if(!is_numeric($_POST['teacherToken'])){$this->error('老师口令必须为数字');}
            if(strlen($_POST['teacherToken'])< 6 || strlen($_POST['teacherToken']) >15 ){$this->error('老师口令只能为6-15位数字');}
            if(empty($_POST['assistantToken'])){$this->error('助教口令不能为空');}
            if(!is_numeric($_POST['assistantToken'])){$this->error('助教口令必须为数字');}
            if(strlen($_POST['assistantToken'])< 6 || strlen($_POST['assistantToken']) >15 ){$this->error('助教口令只能为6-15位数字');}
            if(empty($_POST['studentClientToken'])){$this->error('学生口令不能为空');}
            if(!is_numeric($_POST['studentClientToken'])){$this->error('学生口令必须为数字');}
            if(strlen($_POST['studentClientToken'])< 6 || strlen($_POST['studentClientToken']) >15 ){$this->error('学生口令只能为6-15位数字');}
            if(empty($_POST['description'])){$this->error('直播课时信息不能为空');}

            $video_time = M('zy_video')->where('id ='.$live_id) ->field('listingtime,uctime,maxmannums') ->find();
            if(t($_POST['maxAttendees']) > $video_time['maxmannums'])
            {
                $this->error('超过直播人数上限');
            }
            $url  = $this->cc_config['xbk_api_url'].'/room/create?';

            $query_map['name']              = urlencode(t($_POST['subject']));
            $query_map['desc']              = urlencode(t($_POST['description']));
            $query_map['templatetype']      = intval($_POST['uiMode']);//模版
            $query_map['room_type']         = 2;//互动场景 intval($_POST['scene'])
            $query_map['max_users']         = intval($_POST['maxAttendees']);//最大支持人数，不能超过开通人数上限，默认为账户允许上限
            $query_map['max_streams']       = intval($_POST['maxAttendees']);//互动人数上限

            $query_map['talker_authtype']   = intval(1);//互动学员认证方式
            $query_map['audience_authtype'] = intval(1);//旁听认证方式：（小班课房间开启旁听必填）
            $query_map['publisherpass']     = urlencode(t($_POST['teacherToken']));//讲师密码
            $query_map['audience_pass']     = urlencode(t($_POST['assistantToken']));//旁听密码
            $query_map['talker_pass']       = urlencode(t($_POST['studentClientToken']));//互动学员认证密码

            $query_map['video_mode']        = intval($_POST['clientJoin']);//
            $query_map['classtype']         = intval(t($_POST['webJoin']));//连麦模式
            $query_map['userid'] = $info_map['userid'] = urlencode($this->cc_config['user_id']);
            $query_map['livestarttime']      = urlencode(t($_POST['startDate']));
            
            $url = $url.createHashedQueryString($query_map)[1].'&time='.time().'&hash='.createHashedQueryString($query_map)[0];
            $res = getDataByUrl($url);

            if($res['result'] == 'OK'){
                if(empty($res['data']['roomid'])){$this->error('服务器创建失败');}

                $get_live_info_url  = $this->cc_config['xbk_api_url'].'/room/list?';
                //$get_live_uri_info_url  = $this->cc_config['xbk_api_url'].'/room/code?';

                $info_map['roomid'] = $res['data']['roomid'];

                //查询服务器
                $live_info_url = $get_live_info_url.createHashedQueryString($info_map)[1].'&time='.time().'&hash='.createHashedQueryString($info_map)[0];
                //$live_url_info_url = $get_live_uri_info_url.createHashedQueryString($info_map)[1].'&time='.time().'&hash='.createHashedQueryString($info_map)[0];
                $live_info_res   = getDataByUrl($live_info_url);
                //$live_url_info_res   = getDataByUrl($live_url_info_url);

                if($live_info_res['result'] != 'OK'){$this->error('服务器查询失败');}// || $live_url_info_res['result'] != 'OK'

                $live_info_res          = $live_info_res['rooms'][0];

                $data['uid']            = $this->mid;
                $data['roomid']         = $live_info_res['roomid'];
                $data['subject']        = $live_info_res['name'];
                $data['startDate']      = $startDate;
                $data['invalidDate']    = $invalidDate;
                $data['maxAttendees']   = $live_info_res['max_streams'];
                $data['uiMode']         = $live_info_res['templatetype'];
                $data['clientJoin']     = intval($_POST['clientJoin']);
                $data['webJoin']        = $live_info_res['classtype'];
                $data['scene']          = $live_info_res['room_type'];
                $data['teacherToken']   = $live_info_res['publisherpass'];
                $data['assistantToken'] = $live_info_res['audience_pass'];
                $data['studentClientToken'] = $live_info_res['talker_pass'];
                $data['description']    = $live_info_res['desc'];
                $data['teacherJoinUrl'] = "https://class.csslcloud.net/index/presenter/?roomid={$live_info_res['roomid']}&userid={$info_map['userid']}";
                $data['assistantJoinUrl'] = "https://view.csslcloud.net/api/view/index?roomid={$live_info_res['roomid']}&userid={$info_map['userid']}";
                $data['studentJoinUrl'] = "https://class.csslcloud.net/index/talker/?roomid={$live_info_res['roomid']}&userid={$info_map['userid']}";
                $data['is_del']         = 0;
                $data['is_active']      = 1;
                $data['live_id']        = $live_id;
                $data['type']           = 6;

                $result = model('Live')->liveRoom->add($data);

                if(!$result){$this->error('创建失败!');}
                $this->assign( 'jumpUrl', U('live/AdminLive/ccXbkLiveRoom',array('id'=>$live_id)) );
                $this->success('创建成功');
            }else{
                $this->error($res['errorMsg']);
            }
        } else {
            $_REQUEST['tabHash'] = 'addCcXbkLiveRoom';
            //$this->onsubmit = 'admin.checkAddCc(this)';

            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type,maxmannums');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }

            $this->pageTitle['addCcXbkLiveRoom'] = $liveInfo['video_title'].' 直播课堂—新建直播课时';

            $this->pageKeyList   = array('subject','startDate','invalidDate','maxAttendees','uiMode','clientJoin',
                'webJoin','teacherToken','assistantToken','studentClientToken' ,'description');//,'scene'
            $this->notEmpty = array('subject','startDate','invalidDate','maxAttendees','uiMode','clientJoin',
                'webJoin','teacherToken','assistantToken','studentClientToken' ,'description');

            $this->opt['scene']      = array('1'=>'视频群聊','2'=>'小班课');
            $this->opt['clientJoin'] = array('1'=>'音视频','2'=>'仅音频');
            $this->opt['webJoin']    = array('1'=>'点名','2'=>'自由');
            $this->opt['uiMode']     = array('1'=>'模板一 讲课模式','2'=>'模板二 主视角模式','4'=>'模板四 平铺模式');

            $this->savePostUrl = U('live/AdminLive/addCcXbkLiveRoom',['id'=>$live_id]);
            $this->displayConfig(['maxAttendees'=>$liveInfo['maxmannums']]);
        }
    }

    /**
     * 编辑直播间-CcLive-小班课
     */
    public function editCcXbkLiveRoom(){
        $live_id = $_GET['live_id'];

        if( isset($_POST) ) {
            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);

            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            if(empty($startDate)){$this->error('开始时间不能为空');}
            if(empty($invalidDate)){$this->error('结束时间不能为空');}
            if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}

            $live_time = model('Live')->liveRoom->where(['live_id'=>$live_id])->order('startDate asc')->field('startDate,invalidDate')->findAll();

            $live_time_res = false;
            foreach($live_time as $key => $val){
                if(($val['startDate'] < $startDate && $startDate < $val['invalidDate']) or ($val['startDate'] < $invalidDate && $invalidDate < $val['invalidDate'])){
                    $live_time_res = true;
                    break;
                }
            }

            if($live_time_res) $this->error('当前课堂该时段已有直播');

            if(empty($_POST['maxAttendees'])){$this->error('最大并发不能为空');}
            if(intval($_POST['maxAttendees']) > $this->cc_config['xbk_max_users']){$this->error('最大支持人数不能超过开通人数上限');}

            if(!is_numeric($_POST['maxAttendees'])){$this->error('最大并发必须为数字');}
            if(empty($_POST['uiMode'])){$this->error('直播模版不能为空');}
            if(empty($_POST['teacherToken'])){$this->error('老师口令不能为空');}
            if(!is_numeric($_POST['teacherToken'])){$this->error('老师口令必须为数字');}
            if(strlen($_POST['teacherToken'])< 6 || strlen($_POST['teacherToken']) >15 ){$this->error('老师口令只能为6-15位数字');}
            if(empty($_POST['assistantToken'])){$this->error('助教口令不能为空');}
            if(!is_numeric($_POST['assistantToken'])){$this->error('助教口令必须为数字');}
            if(strlen($_POST['assistantToken'])< 6 || strlen($_POST['assistantToken']) >15 ){$this->error('助教口令只能为6-15位数字');}
            if(empty($_POST['studentClientToken'])){$this->error('学生口令不能为空');}
            if(!is_numeric($_POST['studentClientToken'])){$this->error('学生口令必须为数字');}
            if(strlen($_POST['studentClientToken'])< 6 || strlen($_POST['studentClientToken']) >15 ){$this->error('学生口令只能为6-15位数字');}
            if(empty($_POST['description'])){$this->error('直播课时信息不能为空');}

            $video_time = M('zy_video')->where('id ='.$live_id) ->field('listingtime,uctime,maxmannums') ->find();
            $roomid = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->getField('roomid');

            if(t($_POST['maxAttendees']) > $video_time['maxmannums'])
            {
                $this->error('超过直播人数上限');
            }
            $url  = $this->cc_config['xbk_api_url'].'/room/update?';

            $query_map['live_roomid']       = urlencode($roomid);
            $query_map['name']              = urlencode(t($_POST['subject']));
            $query_map['desc']              = urlencode(t($_POST['description']));
            $query_map['templatetype']      = intval($_POST['uiMode']);//模版
            //$query_map['room_type']         = 2;//互动场景 intval($_POST['scene'])
            $query_map['max_users']         = intval($_POST['maxAttendees']);//最大支持人数
            $query_map['max_streams']       = intval($_POST['maxAttendees']);//互动人数上限

            $query_map['talker_authtype']   = intval(1);//互动学员认证方式
            $query_map['audience_authtype'] = intval(1);//旁听认证方式：（小班课房间开启旁听必填）
            $query_map['publisherpass']     = urlencode(t($_POST['teacherToken']));//讲师密码
            $query_map['audience_pass']     = urlencode(t($_POST['assistantToken']));//旁听密码
            $query_map['talker_pass']       = urlencode(t($_POST['studentClientToken']));//互动学员认证密码
            $query_map['livestarttime']      = urlencode(t($_POST['startDate']));

            $query_map['video_mode']        = intval($_POST['clientJoin']);//
            $query_map['classtype']         = intval(t($_POST['webJoin']));//连麦模式
            $query_map['userid'] = $info_map['userid'] = urlencode($this->cc_config['user_id']);

            $url    = $url.createHashedQueryString($query_map)[1].'&time='.time().'&hash='.createHashedQueryString($query_map)[0];
            $res   = getDataByUrl($url);

            if($res['result'] == 'OK'){
                $get_live_info_url  = $this->cc_config['xbk_api_url'].'/room/list?';
                //$get_live_uri_info_url  = $this->cc_config['xbk_api_url'].'/room/code?';

                $info_map['roomid'] = $roomid;

                //查询服务器
                $live_info_url = $get_live_info_url.createHashedQueryString($info_map)[1].'&time='.time().'&hash='.createHashedQueryString($info_map)[0];
                //$live_url_info_url = $get_live_uri_info_url.createHashedQueryString($info_map)[1].'&time='.time().'&hash='.createHashedQueryString($info_map)[0];
                $live_info_res   = getDataByUrl($live_info_url);
                //$live_url_info_res   = getDataByUrl($live_url_info_url);

                if($live_info_res['result'] != 'OK'){$this->error('服务器查询失败');}// || $live_url_info_res['result'] != 'OK'

                $live_info_res          = $live_info_res['rooms'][0];

                $data['id']             = intval($_GET['id']);
                $data['uid']            = $this->mid;
                $data['roomid']         = $live_info_res['roomid'];
                $data['subject']        = $live_info_res['name'];
                $data['startDate']      = $startDate;
                $data['invalidDate']    = $invalidDate;
                $data['maxAttendees']   = $live_info_res['max_streams'];
                $data['uiMode']         = $live_info_res['templatetype'];
                $data['clientJoin']     = $live_info_res['video_mode'];
                $data['webJoin']        = $live_info_res['classtype'];
                $data['scene']          = $live_info_res['room_type'];
                $data['teacherToken']   = $live_info_res['publisherpass'];
                $data['assistantToken'] = $live_info_res['audience_pass'];
                $data['studentClientToken'] = $live_info_res['talker_pass'];
                $data['description']    = $live_info_res['desc'];
                $data['teacherJoinUrl'] = "https://class.csslcloud.net/index/presenter/?roomid={$live_info_res['roomid']}&userid={$info_map['userid']}";
                $data['assistantJoinUrl'] = "https://view.csslcloud.net/api/view/index?roomid={$live_info_res['roomid']}&userid={$info_map['userid']}";
                $data['studentJoinUrl'] = "https://class.csslcloud.net/index/talker/?roomid={$live_info_res['roomid']}&userid={$info_map['userid']}";

                $result = model('Live')->liveRoom->save($data);

                if(!$result){$this->error('编辑失败');}
                $this->assign( 'jumpUrl', U('live/AdminLive/ccXbkLiveRoom',array('id'=>intval($_GET['live_id']))) );
                $this->success('编辑成功');
            }else{
                $this->error($res['errorMsg']);
            }
        } else {
            $_REQUEST['tabHash'] = 'editCcXbkLiveRoom';
            //$this->onsubmit = 'admin.checkAddCc(this)';

            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }
            // 数据的格式化
            $live_room_data = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->find();

            $this->pageTitle['editCcXbkLiveRoom'] = $liveInfo['video_title'].' 直播课堂—修改直播课时:'.$live_room_data['subject'];

            $live_room_data['startDate'] = date('Y-m-d H:i:s',$live_room_data["startDate"]);
            $live_room_data['invalidDate'] = date('Y-m-d H:i:s',$live_room_data["invalidDate"]);


            $this->pageKeyList   = array('subject','startDate','invalidDate','maxAttendees','uiMode','clientJoin',
                'webJoin','teacherToken','assistantToken','studentClientToken' ,'description');//,'scene'
            $this->notEmpty = array('subject','startDate','invalidDate','scene','maxAttendees','uiMode','clientJoin',
                'webJoin','teacherToken','assistantToken','studentClientToken' ,'description');

            $this->opt['clientJoin'] = array('0'=>'否','1'=>'是');

            $this->opt['scene']      = array('1'=>'视频群聊','2'=>'小班课');
            $this->opt['clientJoin'] = array('1'=>'音视频','2'=>'仅音频');
            $this->opt['webJoin']    = array('1'=>'点名','2'=>'自由');
            $this->opt['uiMode']     = array('1'=>'模板一 讲课模式','2'=>'模板二 主视角模式','4'=>'模板四 平铺模式');

            $this->savePostUrl = U('live/AdminLive/editCcXbkLiveRoom',['id'=>$_GET['id'],'live_id'=>$live_id]);
            $this->displayConfig($live_room_data);
        }
    }

    /**
     * 新建直播间-eeo-小班课
     */
    public function addEeoXbkLiveRoom(){
        $live_id = $_GET['id'];

        if( isset($_POST) ) {
            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);

            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            if(empty($startDate)){$this->error('开始时间不能为空');}
            if(empty($invalidDate)){$this->error('结束时间不能为空');}
            if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}

            $live_time = model('Live')->liveRoom->where(['live_id'=>$live_id])->order('startDate asc')->field('startDate,invalidDate')->findAll();

            $live_time_res = false;
            foreach($live_time as $key => $val){
                if(($val['startDate'] < $startDate && $startDate < $val['invalidDate']) or ($val['startDate'] < $invalidDate && $invalidDate < $val['invalidDate'])){
                    $live_time_res = true;
                    break;
                }
            }

            if($live_time_res) $this->error('当前课堂该时段已有直播');

            if(empty($_POST['maxAttendees'])){$this->error('最大并发不能为空');}
            if(!is_numeric($_POST['maxAttendees'])){$this->error('最大并发必须为数字');}
            //if(empty($_POST['description'])){$this->error('直播课时信息不能为空');}

            $video_info = M('zy_video')->where('id ='.$live_id) ->field('listingtime,uctime,maxmannums,teacher_id,live_course_id') ->find();
            if(t($_POST['maxAttendees']) > $video_info['maxmannums']){
                $this->error('超过直播课程直播人数上限');
            }

            $time = time();
            //eeo注册用户
            $user_url = $this->eeo_xbkConfig['api_url']."register";

            //讲师信息
            $speaker = M('zy_teacher')->where("id=".intval($video_info['teacher_id']))->field('id,uid,name,inro')->find();
            $user_info = M('user')->where(['uid'=>$speaker['uid']])->field('phone,password')->find();
            $speaker_info = M('user_verified')->where("uid=".intval($speaker['uid']))->field('id,uid,phone,realname,idcard')->find();

            if(!$user_info['phone']){// && !$speaker_info['phone']
                $this->error("该用户未绑定手机号");
            }

            $query_public_data['SID']       = $this->eeo_xbkConfig['api_key'];
            $query_public_data['safeKey']   = md5($this->eeo_xbkConfig['api_secret'].$time);
            $query_public_data['timeStamp'] = $time;
            $query_user_data['telephone']   = $user_info['phone'];// ? : $speaker_info['phone']
            $query_user_data['nickname']    = $speaker['name'];
            $query_user_data['md5pass']     = $user_info['password'];

            $live_user_res   = getDataByPostUrl($user_url,array_merge($query_public_data,$query_user_data));

            if($live_user_res->error_info->errno == 1 || $live_user_res->error_info->errno == 135){
                unset($query_user_data['md5pass']);

                //如果已注册就修改用户信息
                if($live_user_res->error_info->errno == 135){
                    $user_url = $this->eeo_xbkConfig['api_url']."editUserInfo";

                    getDataByPostUrl($user_url,array_merge($query_public_data,$query_user_data));
                }

                $teacher_url = $this->eeo_xbkConfig['api_url']."addTeacher";

                $query_teacher_data['teacherAccount'] = $user_info['phone'] ? : $speaker_info['phone'] ;
                $query_teacher_data['teacherName']    = $speaker['name'];

                $live_teacher_res   = getDataByPostUrl($teacher_url,array_merge($query_public_data,$query_teacher_data));

                if($live_teacher_res->error_info->errno != 1 && $live_teacher_res->error_info->errno != 133){
                    $this->error("eeo讲师添加失败");
                }

            } else {
                $this->error("eeo用户注册失败");
            }

            $live_url = $this->eeo_xbkConfig['api_url']."addCourseClass";

            $query_public_data['SID']               = $this->eeo_xbkConfig['api_key'];
            $query_public_data['safeKey']           = md5($this->eeo_xbkConfig['api_secret'].$time);
            $query_public_data['timeStamp']         = $time;
            $query_live_course_data['courseId']     = $video_info['live_course_id'];
            $query_live_course_data['className']    = t($_POST['subject']);
            $query_live_course_data['beginTime']    = $startDate;
            $query_live_course_data['endTime']      = $invalidDate;
            $query_live_course_data['teacherAccount'] = $user_info['phone'] ? : $speaker_info['phone'] ;
            $query_live_course_data['teacherName']    = $speaker['name'];
            $query_live_course_data['seatNum']        = intval($_POST['maxAttendees']);
            $query_live_course_data['record']         = 1;
            $query_live_course_data['live']           = 1;
            $query_live_course_data['replay']         = 1;

            $live_course_res   = getDataByPostUrl($live_url,array_merge($query_public_data,$query_live_course_data));

            if($live_course_res->error_info->errno == 1 || $live_course_res->error_info->errno == 135){

                if(empty($live_course_res->data)){$this->error('服务器创建失败');}

                $get_live_info_url = $this->eeo_xbkConfig['api_url']."getLoginLinked";

                $info_map['courseId'] = $video_info['live_course_id'];
                $info_map['classId'] = $live_course_res->data;
                $info_map['telephone'] = $user_info['phone'] ? : $speaker_info['phone'] ;

                //查询服务器
                $live_course_info_res   = getDataByPostUrl($get_live_info_url,array_merge($query_public_data,$info_map));

                if($live_course_info_res->error_info->errno != 1){$this->error('服务器查询失败');}

                $data['uid']            = $this->mid;
                $data['roomid']         = $live_course_res->data;
                $data['subject']        = $_POST['subject'];
                $data['startDate']      = $startDate;
                $data['invalidDate']    = $invalidDate;
                $data['maxAttendees']   = intval($_POST['maxAttendees']);
                $data['teacherJoinUrl'] = "https://www.eeo.cn/partner/invoke/classin.html?".$live_course_info_res->data;
                $data['is_del']         = 0;
                $data['is_active']      = 1;
                $data['live_id']        = $live_id;
                $data['type']           = 7;

                $result = model('Live')->liveRoom->add($data);

                if(!$result){$this->error('创建失败!');}
                $this->assign( 'jumpUrl', U('live/AdminLive/eeoXbkLiveRoom',array('id'=>$live_id)) );
                $this->success('创建成功');
            }else{
                $this->error($live_course_res->error_info->error);
            }
        } else {
            $_REQUEST['tabHash'] = 'addEeoXbkLiveRoom';
            //$this->onsubmit = 'admin.checkAddCc(this)';

            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type,maxmannums');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }

            $this->pageTitle['addEeoXbkLiveRoom'] = $liveInfo['video_title'].' 直播课堂—新建直播课时';

            $this->pageKeyList   = array('subject','startDate','invalidDate','maxAttendees');
            $this->notEmpty = array('subject','startDate','invalidDate','maxAttendees');

            $this->savePostUrl = U('live/AdminLive/addEeoXbkLiveRoom',['id'=>$live_id]);
            $this->displayConfig(['maxAttendees'=>$liveInfo['maxmannums']]);
        }
    }

    /**
     * 编辑直播间-eeo-小班课
     */
    public function editEeoXbkLiveRoom(){
        $live_id = $_GET['live_id'];

        if( isset($_POST) ) {
            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);

            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            if(empty($startDate)){$this->error('开始时间不能为空');}
            if(empty($invalidDate)){$this->error('结束时间不能为空');}
            if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}

            $live_time = model('Live')->liveRoom->where(['live_id'=>$live_id])->order('startDate asc')->field('startDate,invalidDate')->findAll();

            $live_time_res = false;
            foreach($live_time as $key => $val){
                if(($val['startDate'] < $startDate && $startDate < $val['invalidDate']) or ($val['startDate'] < $invalidDate && $invalidDate < $val['invalidDate'])){
                    $live_time_res = true;
                    break;
                }
            }

            if($live_time_res) $this->error('当前课堂该时段已有直播');
            //if(empty($_POST['description'])){$this->error('直播课时信息不能为空');}

            $video_info = M('zy_video')->where('id ='.$live_id) ->field('listingtime,uctime,maxmannums,teacher_id,live_course_id') ->find();
            if(t($_POST['maxAttendees']) > $video_info['maxmannums'])
            {
                $this->error('超过直播人数上限');
            }
            $roomid = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->getField('roomid');

            $time = time();
            //eeo注册用户
            $user_url = $this->eeo_xbkConfig['api_url']."register";

            //讲师信息
            $speaker = M('zy_teacher')->where("id=".intval($video_info['teacher_id']))->field('id,uid,name,inro')->find();
            $user_info = M('user')->where(['uid'=>$speaker['uid']])->field('phone,password')->find();
            $speaker_info = M('user_verified')->where("uid=".intval($speaker['uid']))->field('id,uid,phone,realname,idcard')->find();

            if(!$user_info['phone']){// && !$speaker_info['phone']
                $this->error("该用户未绑定手机号");
            }

            $query_public_data['SID']       = $this->eeo_xbkConfig['api_key'];
            $query_public_data['safeKey']   = md5($this->eeo_xbkConfig['api_secret'].$time);
            $query_public_data['timeStamp'] = $time;
            $query_user_data['telephone']   = $user_info['phone'];// ? : $speaker_info['phone']
            $query_user_data['nickname']    = $speaker['name'];
            $query_user_data['md5pass']     = $user_info['password'];

            $live_user_res   = getDataByPostUrl($user_url,array_merge($query_public_data,$query_user_data));

            if($live_user_res->error_info->errno == 1 || $live_user_res->error_info->errno == 135){
                unset($query_user_data['md5pass']);

                //如果已注册就修改用户信息
                if($live_user_res->error_info->errno == 135){
                    $user_url = $this->eeo_xbkConfig['api_url']."editUserInfo";

                    getDataByPostUrl($user_url,array_merge($query_public_data,$query_user_data));
                }

                $teacher_url = $this->eeo_xbkConfig['api_url']."addTeacher";

                $query_teacher_data['teacherAccount'] = $user_info['phone'] ? : $speaker_info['phone'] ;
                $query_teacher_data['teacherName']    = $speaker['name'];

                $live_teacher_res   = getDataByPostUrl($teacher_url,array_merge($query_public_data,$query_teacher_data));

                if($live_teacher_res->error_info->errno != 1 && $live_teacher_res->error_info->errno != 133){
                    $this->error("eeo讲师添加失败");
                }

            } else {
                $this->error("eeo用户注册失败");
            }

            $live_url = $this->eeo_xbkConfig['api_url']."editCourseClass";

            $query_public_data['SID']               = $this->eeo_xbkConfig['api_key'];
            $query_public_data['safeKey']           = md5($this->eeo_xbkConfig['api_secret'].$time);
            $query_public_data['timeStamp']         = $time;
            $query_live_course_data['courseId']     = $video_info['live_course_id'];
            $query_live_course_data['classId']      = $roomid;
            $query_live_course_data['className']    = t($_POST['subject']);
            $query_live_course_data['beginTime']    = $startDate;
            $query_live_course_data['endTime']      = $invalidDate;
            $query_live_course_data['teacherAccount'] = $speaker_info['phone'];
            $query_live_course_data['teacherName']    = $speaker['name'];
            $query_live_course_data['seatNum']        = intval($_POST['maxAttendees']);
            $query_live_course_data['record']         = 1;
            $query_live_course_data['live']           = 1;
            $query_live_course_data['replay']         = 1;

            $live_course_res   = getDataByPostUrl($live_url,array_merge($query_public_data,$query_live_course_data));

            if($live_course_res->error_info->errno == 1 || $live_course_res->error_info->errno == 135){

                $get_live_info_url = $this->eeo_xbkConfig['api_url']."getLoginLinked";

                $info_map['courseId']  = $video_info['live_course_id'];
                $info_map['classId']   = $roomid;
                $info_map['telephone'] = $speaker_info['phone'];

                //查询服务器
                $live_course_info_res   = getDataByPostUrl($get_live_info_url,array_merge($query_public_data,$info_map));

                if($live_course_info_res->error_info->errno != 1){$this->error('服务器查询失败');}

                $data['id']             = intval($_GET['id']);
                $data['uid']            = $this->mid;
                $data['subject']        = $_POST['subject'];
                $data['startDate']      = $startDate;
                $data['invalidDate']    = $invalidDate;
                //$data['maxAttendees']   = intval($_POST['maxAttendees']);
                $data['teacherJoinUrl'] = "https://www.eeo.cn/partner/invoke/classin.html?".$live_course_info_res->data;

                $result = model('Live')->liveRoom->save($data);

                if(!$result){$this->error('编辑失败');}
                $this->assign( 'jumpUrl', U('live/AdminLive/eeoXbkLiveRoom',array('id'=>intval($_GET['live_id']))) );
                $this->success('编辑成功');
            }else{
                $this->error($live_course_res->error_info->error);
            }
        } else {
            $_REQUEST['tabHash'] = 'editEeoXbkLiveRoom';
            //$this->onsubmit = 'admin.checkAddEeo(this)';

            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }
            // 数据的格式化
            $live_room_data = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->find();

            $this->pageTitle['editEeoXbkLiveRoom'] = $liveInfo['video_title'].' 直播课堂—修改直播课时:'.$live_room_data['subject'];

            $live_room_data['startDate'] = date('Y-m-d H:i:s',$live_room_data["startDate"]);
            $live_room_data['invalidDate'] = date('Y-m-d H:i:s',$live_room_data["invalidDate"]);


            $this->pageKeyList   = array('subject','startDate','invalidDate','maxAttendees');
            $this->notEmpty = array('subject','startDate','invalidDate','maxAttendees');

            $this->savePostUrl = U('live/AdminLive/editEeoXbkLiveRoom',['id'=>$_GET['id'],'live_id'=>$live_id]);
            $this->displayConfig($live_room_data);
        }
    }

    /**
     * 新建直播间-拓课云
     */
    public function addTkLiveRoom(){
        $live_id = $_GET['id'];

        if( isset($_POST) ) {
            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);

            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            if(empty($startDate)){$this->error('开始时间不能为空');}
            if(empty($invalidDate)){$this->error('结束时间不能为空');}
            if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}

            $live_time = model('Live')->liveRoom->where(['live_id'=>$live_id])->order('startDate asc')->field('startDate,invalidDate')->findAll();

            $live_time_res = false;
            foreach($live_time as $key => $val){
                if(($val['startDate'] < $startDate && $startDate < $val['invalidDate']) or ($val['startDate'] < $invalidDate && $invalidDate < $val['invalidDate'])){
                    $live_time_res = true;
                    break;
                }
            }

            if($live_time_res) $this->error('当前课堂该时段已有直播');

            if(empty($_POST['maxAttendees'])){$this->error('最大并发不能为空');}
            if(!is_numeric($_POST['maxAttendees'])){$this->error('最大并发必须为数字');}
            if(!is_numeric($_POST['scene'])){$this->error('直播类型不能为空');}
            if(empty($_POST['teacherToken'])){$this->error('老师口令不能为空');}
            if(!is_numeric($_POST['teacherToken'])){$this->error('老师口令必须为数字');}
            if(strlen($_POST['teacherToken'])< 6 || strlen($_POST['teacherToken']) >15 ){$this->error('老师口令只能为6-15位数字');}
            if(empty($_POST['assistantToken'])){$this->error('助教口令不能为空');}
            if(!is_numeric($_POST['assistantToken'])){$this->error('助教口令必须为数字');}
            if(strlen($_POST['assistantToken'])< 6 || strlen($_POST['assistantToken']) >15 ){$this->error('助教口令只能为6-15位数字');}
            if(empty($_POST['studentClientToken'])){$this->error('学生口令不能为空');}
            if(!is_numeric($_POST['studentClientToken'])){$this->error('学生口令必须为数字');}
            if(strlen($_POST['studentClientToken'])< 6 || strlen($_POST['studentClientToken']) >15 ){$this->error('学生口令只能为6-15位数字');}

            $video_time = M('zy_video')->where('id ='.$live_id) ->field('listingtime,uctime,maxmannums') ->find();
            if(t($_POST['maxAttendees']) > $video_time['maxmannums'])
            {
                $this->error('超过直播人数上限');
            }
            $client = new Client(['base_uri'=>$this->tk_config['api_url']]);
            $url  = '/WebAPI/roomcreate?';

            $query_map['key']       = $this->tk_config['api_key'];
            $query_map['roomname']  = t($_POST['subject']);
            $query_map['roomtype']  = intval($_POST['scene']);//互动场景
            $query_map['starttime'] = intval($startDate);
            $query_map['endtime']   = intval($invalidDate);
            $query_map['chairmanpwd']   = t($_POST['teacherToken']);
            $query_map['assistantpwd']  = t($_POST['assistantToken']);
            $query_map['patrolpwd']     = t($_POST['studentToken']);
            $query_map['passwordrequired']   = 1;
            $query_map['confuserpwd']   = t($_POST['studentClientToken']);
            $query_map['autoopenav']    = 1;

            $res = json_decode($client->get($url,['query'=>$query_map])->getBody()->getContents());

            if($res->result == 4001){
                $this->error('企业信息已过期，请联系直播服务商');
            } else if($res->result == 0){
                if(empty($res->serial)){$this->error('服务器创建失败');}

                $get_live_info_url  = "/WebAPI/getroom?key={$query_map['key']}&serial={$res->serial}";

                //查询服务器
                $live_info_res = json_decode($client->get($get_live_info_url)->getBody()->getContents());

                if($live_info_res->result != 0){$this->error('服务器查询失败');}

                //讲师信息
                $video_info = M('zy_video')->where('id ='.$live_id) ->field('teacher_id') ->find();
                $speaker = M('zy_teacher')->where("id=".intval($video_info['teacher_id']))->field('id,uid,name,inro')->find();

                $teacher_join_url = model('Live')->getTkLiveUri(0,$query_map['chairmanpwd'],$live_info_res->serial,$speaker['name'],$live_id);
                $assistant_join_url = model('Live')->getTkLiveUri(1,$query_map['assistantpwd'],$live_info_res->serial,getUserName($this->mid),$live_id);
                $student_join_url = model('Live')->getTkLiveUri(2,$query_map['confuserpwd'],$live_info_res->serial,getUserName($this->mid),$live_id);
                $patrolpwd_join_url = model('Live')->getTkLiveUri(4,$query_map['patrolpwd'],$live_info_res->serial,getUserName($this->mid),$live_id);

                $data['uid']            = $this->mid;
                $data['roomid']         = $live_info_res->serial;
                $data['subject']        = $live_info_res->roomname;
                $data['startDate']      = $startDate;
                $data['invalidDate']    = $invalidDate;
                $data['maxAttendees']   = intval($_POST['maxAttendees']);
                $data['scene']          = intval($_POST['scene']);
                $data['teacherToken']   = $live_info_res->chairmanpwd;
                $data['assistantToken'] = $live_info_res->assistantpwd;
                $data['studentClientToken'] = $live_info_res->confuserpwd;
                $data['studentToken']   = $query_map['patrolpwd'];
                $data['teacherJoinUrl'] = $teacher_join_url;
                $data['assistantJoinUrl'] = $assistant_join_url;
                $data['studentJoinUrl'] = $student_join_url;
                $data['is_del']         = 0;
                $data['is_active']      = 1;
                $data['live_id']        = $live_id;
                $data['type']           = 8;

                $result = model('Live')->liveRoom->add($data);

                if(!$result){$this->error('创建失败!');}
                $this->assign( 'jumpUrl', U('live/AdminLive/tkLiveRoom',array('id'=>$live_id)) );
                $this->success('创建成功');
            }else{
                $this->error($res['errorMsg']);
            }
        } else {
            $_REQUEST['tabHash'] = 'addTkLiveRoom';
            //$this->onsubmit = 'admin.checkAddCc(this)';

            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type,maxmannums');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }

            $this->pageTitle['addTkLiveRoom'] = $liveInfo['video_title'].' 直播课堂—新建直播课时';

            $this->pageKeyList   = array('subject','startDate','invalidDate','scene','maxAttendees',
                'teacherToken','assistantToken','studentToken','studentClientToken' );
            $this->notEmpty = array('subject','startDate','invalidDate','maxAttendees','scene',
                'teacherToken','assistantToken','studentToken','studentClientToken' ,'description');

            $this->opt['scene']         = array('0'=>'一对一','3'=>'一对多');

            $this->savePostUrl = U('live/AdminLive/addTkLiveRoom',['id'=>$live_id]);
            $this->displayConfig(['maxAttendees'=>$liveInfo['maxmannums']]);
        }
    }

    /**
     * 编辑直播间-拓课云
     */
    public function editTkLiveRoom(){
        $live_id = $_GET['live_id'];

        if( isset($_POST) ) {
            $startDate = strtotime($_POST['startDate']);
            $invalidDate = strtotime($_POST['invalidDate']);

            if(empty($_POST['subject'])){$this->error('直播课时名称不能为空');}
            if(empty($startDate)){$this->error('开始时间不能为空');}
            if(empty($invalidDate)){$this->error('结束时间不能为空');}
            if($invalidDate < $startDate){$this->error('结束时间不能小于开始时间');}

            $live_time = model('Live')->liveRoom->where(['live_id'=>$live_id])->order('startDate asc')->field('startDate,invalidDate')->findAll();

            $live_time_res = false;
            foreach($live_time as $key => $val){
                if(($val['startDate'] < $startDate && $startDate < $val['invalidDate']) or ($val['startDate'] < $invalidDate && $invalidDate < $val['invalidDate'])){
                    $live_time_res = true;
                    break;
                }
            }

            if($live_time_res) $this->error('当前课堂该时段已有直播');

            if(empty($_POST['maxAttendees'])){$this->error('最大并发不能为空');}
            if(!is_numeric($_POST['maxAttendees'])){$this->error('最大并发必须为数字');}
            if(!is_numeric($_POST['scene'])){$this->error('直播类型不能为空');}
            if(empty($_POST['teacherToken'])){$this->error('老师口令不能为空');}
            if(!is_numeric($_POST['teacherToken'])){$this->error('老师口令必须为数字');}
            if(strlen($_POST['teacherToken'])< 6 || strlen($_POST['teacherToken']) >15 ){$this->error('老师口令只能为6-15位数字');}
            if(empty($_POST['assistantToken'])){$this->error('助教口令不能为空');}
            if(!is_numeric($_POST['assistantToken'])){$this->error('助教口令必须为数字');}
            if(strlen($_POST['assistantToken'])< 6 || strlen($_POST['assistantToken']) >15 ){$this->error('助教口令只能为6-15位数字');}
            if(empty($_POST['studentClientToken'])){$this->error('学生口令不能为空');}
            if(!is_numeric($_POST['studentClientToken'])){$this->error('学生口令必须为数字');}
            if(strlen($_POST['studentClientToken'])< 6 || strlen($_POST['studentClientToken']) >15 ){$this->error('学生口令只能为6-15位数字');}

            $roomid = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->getField('roomid');

            $video_time = model('Live')->where('id ='.$live_id) ->field('listingtime,uctime,maxmannums') ->find();
            if(t($_POST['maxAttendees']) > $video_time['maxmannums'])
            {
                $this->error('超过直播人数上限');
            }

            $client = new Client(['base_uri'=>$this->tk_config['api_url']]);
            $url  = '/WebAPI/roommodify?';

            $query_map['key']       = $this->tk_config['api_key'];
            $query_map['serial']    = $roomid;
            $query_map['roomname']  = t($_POST['subject']);
            $query_map['roomtype']  = intval($_POST['scene']);//互动场景
            $query_map['starttime'] = intval($startDate);
            $query_map['endtime']   = intval($invalidDate);
            $query_map['chairmanpwd']   = t($_POST['teacherToken']);
            $query_map['assistantpwd']  = t($_POST['assistantToken']);
            $query_map['patrolpwd']     = t($_POST['studentToken']);
            $query_map['passwordrequired']   = 1;
            $query_map['confuserpwd']   = t($_POST['studentClientToken']);
            $query_map['autoopenav']    = 1;

            $res = json_decode($client->get($url,['query'=>$query_map])->getBody()->getContents());

            if($res->result == 4001){
                $this->error('企业信息已过期，请联系直播服务商');
            } else if($res->result == 0){
                if(empty($res->serial)){$this->error('服务器创建失败');}

                $get_live_info_url  = "/WebAPI/getroom?key={$query_map['key']}&serial={$res->serial}";

                //查询服务器
                $live_info_res = json_decode($client->get($get_live_info_url)->getBody()->getContents());

                if($live_info_res->result != 0){$this->error('服务器查询失败');}

                //讲师信息
                $video_info = M('zy_video')->where('id ='.$live_id) ->field('teacher_id') ->find();
                $speaker = M('zy_teacher')->where("id=".intval($video_info['teacher_id']))->field('id,uid,name,inro')->find();

                $teacher_join_url = model('Live')->getTkLiveUri(0,$query_map['chairmanpwd'],$live_info_res->serial,$speaker['name'],$live_id);
                $assistant_join_url = model('Live')->getTkLiveUri(1,$query_map['assistantpwd'],$live_info_res->serial,getUserName($this->mid),$live_id);
                $student_join_url = model('Live')->getTkLiveUri(2,$query_map['confuserpwd'],$live_info_res->serial,getUserName($this->mid),$live_id);
                $patrolpwd_join_url = model('Live')->getTkLiveUri(4,$query_map['patrolpwd'],$live_info_res->serial,getUserName($this->mid),$live_id);

                $data['id']             = $_GET['id'];
                $data['uid']            = $this->mid;
                $data['roomid']         = $live_info_res->serial;
                $data['subject']        = $live_info_res->roomname;
                $data['startDate']      = $startDate;
                $data['invalidDate']    = $invalidDate;
                $data['maxAttendees']   = intval($_POST['maxAttendees']);
                $data['scene']          = intval($_POST['scene']);
                $data['teacherToken']   = $live_info_res->chairmanpwd;
                $data['assistantToken'] = $live_info_res->assistantpwd;
                $data['studentClientToken'] = $live_info_res->confuserpwd;
                $data['studentToken']   = $query_map['patrolpwd'];
                $data['teacherJoinUrl'] = $teacher_join_url;
                $data['assistantJoinUrl'] = $assistant_join_url;
                $data['studentJoinUrl'] = $student_join_url;

                $result = model('Live')->liveRoom->save($data);

                if(!$result){$this->error('编辑失败!');}
                $this->assign( 'jumpUrl', U('live/AdminLive/tkLiveRoom',array('id'=>intval($_GET['live_id']))) );
                $this->success('编辑成功');
            }else{
                $this->error('服务器出错啦');
            }
        } else {
            $_REQUEST['tabHash'] = 'editTkLiveRoom';
            //$this->onsubmit = 'admin.checkAddCc(this)';

            $liveInfo = model('Live')->findLiveInfo(array('id'=>$live_id),'id,video_title,live_type');
            if(!$liveInfo){
                $this->error("直播课堂未通过审核或已下架");
            }
            // 数据的格式化
            $live_room_data = model('Live')->liveRoom->where('id='.intval($_GET['id']) )->find();

            $this->pageTitle['editTkLiveRoom'] = $liveInfo['video_title'].' 直播课堂—修改直播课时:'.$live_room_data['subject'];

            $live_room_data['startDate'] = date('Y-m-d H:i:s',$live_room_data["startDate"]);
            $live_room_data['invalidDate'] = date('Y-m-d H:i:s',$live_room_data["invalidDate"]);

            $this->pageKeyList   = array('subject','startDate','invalidDate','scene','maxAttendees',
                'teacherToken','assistantToken','studentToken','studentClientToken' );
            $this->notEmpty = array('subject','startDate','invalidDate','maxAttendees','scene',
                'teacherToken','assistantToken','studentToken','studentClientToken' ,'description');

            $this->opt['scene']         = array('0'=>'一对一','3'=>'一对多');

            $this->savePostUrl = U('live/AdminLive/editTkLiveRoom',['id'=>$_GET['id'],'live_id'=>$live_id]);
            $this->displayConfig($live_room_data);
        }
    }

    /**
     * 解析直播课堂列表数据
     * @param integer $limit 结果集数目，默认为20
     * @param $order 排序
     * @return array 解析后的直播列表数据
     */
    private function _getLiveList($type,$limit,$order,$map,$activity) {
        if(isset($_POST)){
            $_POST['id'] && $map['id']          = intval($_POST['id']);
            $_POST ['uid'] && $map ['uid']      = array('in', (string)$_POST ['uid']);
            $_POST['mhm_id'] && $map['mhm_id']  = intval($_POST['mhm_id']);
            $_POST['teacher_id'] && $map['teacher_id']  = intval($_POST['teacher_id']);
//            $_POST['mhm_title'] && $map['mhm_id']  = model('School')->where(array('title'=>['like','%' . t($_POST['mhm_title']) . '%']))->getField('id');
            $_POST['video_title'] && $map['video_title'] = array('like', '%' . t($_POST['video_title']) . '%');
            $_POST['v_price'] && $map['v_price']    = floatval($_POST['v_price']);
            $_POST['t_price'] && $map['t_price']    = floatval($_POST['t_price']);
            if($_POST ['video_score'] != 0){
                $_POST ['video_score'] && $map ['video_score'] = intval($_POST['video_score']);
            }
            if($_POST ['is_best'] == 1){
                $map ['is_best'] = 0;
            }else if($_POST ['is_best'] == 2){
                $map ['is_best'] = 1;
            }
            if($_POST ['is_charge'] == 1){
                $map ['is_charge'] = 0;
            }else if($_POST ['is_charge'] == 2){
                $map ['is_charge'] = 1;
            }
            if($_POST ['is_best_like'] == 1){
                $map ['is_best_like'] = 0;
            }else if($_POST ['is_best_like'] == 2){
                $map ['is_best_like'] = 1;
            }
            if($_POST ['is_cete_floor'] == 1){
                $map ['is_cete_floor'] = 0;
            }else if($_POST ['is_cete_floor'] == 2){
                $map ['is_cete_floor'] = 1;
            }
            if($_POST ['is_re_free'] == 1){
                $map ['is_re_free'] = 0;
            }else if($_POST ['is_re_free'] == 2){
                $map ['is_re_free'] = 1;
            }
            if($_POST ['quanzhong'] ){
                $order = '';
                foreach ($_POST ['quanzhong'] as $val)
                {
                    $order .= $val.",";
                }
                $order = substr($order,0,strlen($order)-1);
            }
            if (! empty ( $_POST ['listingtime'] [0] )) {
                $map ['listingtime'] = array ('egt',strtotime ( $_POST ['listingtime'] [0] ));
            }
            if (! empty(strtotime($_POST ['listingtime'] [1]))) {
                $map ['uctime'] = array ('elt',strtotime($_POST ['listingtime'] [1]));
            }
        }
        $map['type'] = 2;
        $map['school_switch'] = 0;

        if( $activity == 1)
        {
            $map['is_activity'] = 3;
        }else{
            $map['is_activity'] = 1;
        }

        $liveInfo = model('Live')->getAllLiveInfo($limit,$order,$map);
        foreach($liveInfo['data'] as $key => $val){
            $liveInfo['data'][$key]['video_title']    = msubstr($val['video_title'],0,20);
            $url = U('live/Index/view', array('id' => $val['id']));
            $liveInfo['data'][$key]['video_title'] = getQuickLink($url ,$liveInfo['data'][$key]['video_title'] ,"未知直播");
            $s_map['id'] = $val['mhm_id'];
            $school_info = M('school')->where($s_map)->field('title,doadmin')->find();
            $liveInfo['data'][$key]['mhm_title'] = getQuickLink(getDomain($school_info['doadmin']),$school_info['title'],'未知机构');
            $teacher_info = M('zy_teacher')->where('id ='.$val['teacher_id'])->field('id,name')->find();
            $sql="select id,name from el_zy_teacher where id in (".$val['teacher_id'].")";

            $teacher_list=M('zy_teacher')->query($sql);
            $str='';
            foreach ($teacher_list as $k => $v) {
                $str.=getQuickLink(U('classroom/Teacher/view',['id'=>$v['id']]),$v['name'],'未知讲师').",";
            }
            $liveInfo['data'][$key]['speaker_name'] = $str;

            $liveInfo['data'][$key]['uid']       = getUserSpace($val['uid'], null, '_blank');
            $liveInfo['data'][$key]['listingtime'] = date('Y-m-d H:i:s',$val["listingtime"]);
            $liveInfo['data'][$key]['uctime'] = date('Y-m-d H:i:s',$val["uctime"]);
            $liveInfo['data'][$key]['cover'] = "<img src=".getCover($val['cover'] , 80 ,40)." width='80' height='40'>";
            if($val['video_score'] == 0){
                $liveInfo['data'][$key]['video_score'] = '';
            }else if($val['video_score'] == 20){
                $liveInfo['data'][$key]['video_score'] = 1;
            }else if($val['video_score'] == 40){
                $liveInfo['data'][$key]['video_score'] = 2;
            }else if($val['video_score'] == 60){
                $liveInfo['data'][$key]['video_score'] = 3;
            }else if($val['video_score'] == 80){
                $liveInfo['data'][$key]['video_score'] = 4;
            }else if($val['video_score'] == 100){
                $liveInfo['data'][$key]['video_score'] = 5;
            }

            if($val['is_best'] == 0){
                $liveInfo['data'][$key]['is_best'] = "<p style='color: red;'>否</p>";
            }else if($val['is_best'] == 1){
                $liveInfo['data'][$key]['is_best'] = "<p style='color: green;'>是</p>";
            }
            if($val['is_charge'] == 0){
                $liveInfo['data'][$key]['is_charge'] = "<p style='color: red;'>否</p>";
            }else if($val['is_charge'] == 1){
                $liveInfo['data'][$key]['is_charge'] = "<p style='color: green;'>是</p>";
            }
            if($val['is_del'] == 0){
                $liveInfo['data'][$key]['is_open'] = "<p style='color: green;'>否</p>";
            }else if($val['is_del'] == 1){
                $liveInfo['data'][$key]['is_open'] = "<p style='color: red;'>是</p>";
            }
            if($val['is_activity'] == 3){
                //$liveInfo['data'][$key]['is_activity'] = '<span style="color: red;" onclick="admin.doaction('.$val['id'].',\'OpenActivity\''.',9)" >否</span>';
                $liveInfo['data'][$key]['is_activity'] = '<p style="color: red;" >否</p>';
            }else if($val['is_activity'] == 1){
                $liveInfo['data'][$key]['is_activity'] = "<p style='color: green;'>是</p>";
            }
            if($val['live_type'] == 1){
                $liveInfo['data'][$key]['live_type'] = "展示互动";
            }else if($val['live_type'] == 2){
                $liveInfo['data'][$key]['live_type'] = "三芒";
            }else if($val['live_type'] == 3){
                $liveInfo['data'][$key]['live_type'] = "光慧";
            }else if($val['live_type'] == 4){
                $liveInfo['data'][$key]['live_type'] = "CCLive";
            }else if($val['live_type'] == 5){
                $liveInfo['data'][$key]['live_type'] = "微吼";
            }else if($val['live_type'] == 6){
                $liveInfo['data'][$key]['live_type'] = "CC小班课";
            }else if($val['live_type'] == 7){
                $liveInfo['data'][$key]['live_type'] = "eeo小班课";
            }else if($val['live_type'] == 8){
                $liveInfo['data'][$key]['live_type'] = "拓课云";
            }

            if ($val['clientJoin'] == 0) {$liveInfo['data'][$key]['clientJoin'] = '<span style="color: red;">关闭</span>';}else{$liveInfo['data'][$key]['clientJoin'] = '开启';}

            if( $val['is_activity']== 3){
                $videotitle =t($val['video_title']);
                $tuid = M('zy_teacher')->where(array('id'=>$val['teacher_id']))->getField('uid');
                $liveInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void();" onclick="admin.crossVideo('.$val['id'].',true,' ."'{$videotitle}'".',' ."'{$tuid}'".')">通过审核</a> | ';
                $liveInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void();" onclick="admin.crossVideo('.$val['id'].',false,' ."'{$videotitle}'".',' ."'{$tuid}'".')">驳回</a>';
            }else{
                if($val['live_type'] == 1){
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/addZshdLiveRoom',array('id'=>$val['id'])).'">新建直播课时</a> | ';
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/zshdLiveRoom',array('id'=>$val['id'])).'" title="查看此直播课堂下直播课时">直播课时</a> | ';
                }else if($val['live_type'] == 3){
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/addGhLiveRoom',array('id'=>$val['id'])).'">新建直播课时</a> | ';
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/ghLiveRoom',array('id'=>$val['id'])).'" title="查看此直播课堂下直播课时">直播课时</a> | ';
                }else if($val['live_type'] == 4){
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/ccLiveRoom2',array('id'=>$val['id'])).'" title="查看此直播课堂下直播课时">直播目录</a> | ';
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/addCcLiveRoom',array('id'=>$val['id'],'sssy'=>1)).'">新建直播课时</a> | ';
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/ccLiveRoom',array('id'=>$val['id'])).'" title="查看此直播课堂下直播课时">直播课时</a> | ';
                }else if($val['live_type'] == 5){
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/addWhLiveRoom',array('id'=>$val['id'])).'">新建直播课时</a> | ';
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/whLiveRoom',array('id'=>$val['id'])).'" title="查看此直播课堂下直播课时">直播课时</a> | ';
                }else if($val['live_type'] == 6){
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/addCcXbkLiveRoom',array('id'=>$val['id'])).'">新建直播课时</a> | ';
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/ccXbkLiveRoom',array('id'=>$val['id'])).'" title="查看此直播课堂下直播课时">直播课时</a> | ';
                }else if($val['live_type'] == 7){
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/addEeoXbkLiveRoom',array('id'=>$val['id'])).'">新建直播课时</a> | ';
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/eeoXbkLiveRoom',array('id'=>$val['id'])).'" title="查看此直播课堂下直播课时">直播课时</a> | ';
                }else if($val['live_type'] == 8){
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/addTkLiveRoom',array('id'=>$val['id'])).'">新建直播课时</a> | ';
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/tkLiveRoom',array('id'=>$val['id'])).'" title="查看此直播课堂下直播课时">直播课时</a> | ';
                }

                $liveInfo['data'][$key]['DOACTION'] .= '<a href="'.U('live/AdminLive/editLive',array('id'=>$val['id'])).'">编辑</a> | ';
				$liveInfo['data'][$key]['DOACTION'] .= '<a href="' . U('classroom/AdminCourseOrder/addCourseOrder', array('id' => $val['id'], 'tabHash' => 'editVideo')) . '">赠送</a> | ';

                if ($val['is_del'] == 0) {
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'ColseLive\''.',9)">禁用</a> | ';
                } else {
                    $liveInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'OpenLive\''.',9)">启用</a> | ';
                }
                if($val['is_mount'] == 1) {
                    $liveInfo['data'][$key]['DOACTION'] .=   '<a onclick="admin.closeMount('.$val['id'].','.$val['is_mount'].');" href="javascript:void(0)">取消挂载</a> ';
                }else if($val['is_mount'] == 0) {
                    $liveInfo['data'][$key]['DOACTION'] .=   '<a onclick="admin.openMount('.$val['id'].','.$val['is_mount'].');" href="javascript:void(0)">允许挂载</a> ';
                }else if($val['is_mount'] == 2) {
                    $liveInfo['data'][$key]['DOACTION'] .=   '<a  href="javascript:void(0)">已提交挂载待审核</a> ';
                }
            }
        }
        return $liveInfo;
    }


    /**
     * 解析直播课堂列表数据
     * @param integer $limit 结果集数目，默认为20
     * @param $order 排序
     * @return array 解析后的直播列表数据
     */
    private function _gettypeList($limit,$order,$map,$activity) {
        


        $liveInfo = model('Live')->getAllTypeInfo($limit,$order,$map);

        foreach($liveInfo['data'] as $key => $val){
                $liveInfo['data'][$key]['DOACTION'] .= '<a href="javascript:admin.upTreeCategory('.$val['id'].');" >编辑</a> | ';
                $liveInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'ColseLive\''.',9)">删除</a>  ';
        }
        return $liveInfo;
    }

    /**
     * 解析展示互动直播间列表数据
     * @param integer $limit 结果集数目，默认为20
     * @param $order 排序
     * @return array 解析后的直播列表数据
     */
    private function _getZshdLiveList($type,$limit,$order,$map) {
        if(isset($_POST)){
            $_POST['id'] && $map['id']              = intval($_POST['id']);
            $_POST ['uname'] && $map ['uid']        = array('in', (string)$_POST ['uname']);
            $_POST['subject'] && $map['subject']    = array('like', '%' . t($_POST['subject']) . '%');
            $clientJoin = $_POST['clientJoin'] - 1;
            if($clientJoin != -1){
                $map['clientJoin']    = intval($clientJoin);
            }
            $webJoin = $_POST['webJoin'] - 1;
            if($webJoin != -1){
                $map['webJoin']    = intval($webJoin);
            }
            $is_active = $_POST['is_active'] - 1;
            if($is_active != -1){
                $map['is_active']    = intval($is_active);
            }
            $is_del = $_POST['is_del'] - 1;
            if($is_del != -1){
                $map['is_del']    = intval($is_del);
            }
            if (! empty ( $_POST ['startDate'] [0] )) {
                $map ['startDate'] = array ('egt',strtotime ( $_POST ['startDate'] [0] ));
            }
            if (! empty(strtotime($_POST ['startDate'] [1]))) {
                $map ['invalidDate'] = array ('elt',strtotime($_POST ['startDate'] [1]));
            }
        }
        $liveInfo = model('Live')->getZshdLiveInfo($order,$limit,$map);

        $nickname = M('user')->where("uid={$this->mid}")->getField('uname');
        $teacher_info = M('zy_teacher')->where('id ='.M('Live')->where(['id'=>$map['live_id']])->getField('teacher_id'))->field('id,name')->find();

        foreach($liveInfo['data'] as $key => $val) {
            $liveInfo['data'][$key]['subject']  = mb_substr($val['subject'],0,7,'utf-8')."...";
            $liveInfo['data'][$key]['uname'] = getUserSpace($val['uid'], null, '_blank');
            $liveInfo['data'][$key]['startDate'] = date('Y-m-d H:i:s', $val["startDate"]);
            $liveInfo['data'][$key]['invalidDate'] = date('Y-m-d H:i:s', $val["invalidDate"]);

            if ($val['clientJoin'] == 0) {
                $liveInfo['data'][$key]['clientJoin'] = '<p style="color: red;">不支持</p>';
            } else {
                $liveInfo['data'][$key]['clientJoin'] = "<p style='color: green;'>支持</p>";
            }
            if ($val['webJoin'] == 0) {
                $liveInfo['data'][$key]['webJoin'] = '<p style="color: red;">不支持</p>';
            } else {
                $liveInfo['data'][$key]['webJoin'] = "<p style='color: green;'>支持</p>";
            }
            if ($val['is_del'] == 0) {
                $liveInfo['data'][$key]['is_open'] = "<p style='color: green;'>开启</p>";
            } else {
                $liveInfo['data'][$key]['is_open'] = '<p style="color: red;">关闭</p>';
            }
            if ($val['is_active'] == 1) {
                $liveInfo['data'][$key]['is_active'] = "<p style='color: green;'>已审核</p>";
            } else {
                $liveInfo['data'][$key]['is_active'] = '<span title="审核此直播间" href="javascript:;" onclick="admin.doaction('.$val['id'].',\'ActiveLiveRoom\''.',1)" style="color: red;">未审核</span> ';
            }
//            $liveInfo['data'][$key]['DOACTION'] = '<a title="查看直播课堂详细信息" href="' . U('live/Admin/checkLive', array('tabHash' => 'checkLive', 'id' => $val["id"])) . '">查看</a> | ';
            $liveInfo['data'][$key]['DOACTION'] .= '<a href="' . U('live/AdminLive/editZshdLiveRoom', array('id' => $val["id"],'live_id'=>intval($_GET['id']))) . '">编辑</a> | ';
            if ($val['is_del'] == 0) {
                $liveInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'ColseLiveRoom\''.',1)">禁用</a> | ';
            } else {
                $liveInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'OpenLiveRoom\''.',1)">启用</a> | ';
            }

            $liveInfo['data'][$key]['DOACTION'] .= "<a target='_blank' href='{$val['studentJoinUrl']}?nickname={$nickname}&token={$val['studentToken']}'>学生观看</a> | ";
            $liveInfo['data'][$key]['DOACTION'] .= "<a target='_blank' href='{$val['teacherJoinUrl']}?nickname={$teacher_info['name']}&token={$val['teacherToken']}'>老师讲课</a> | ";
            $liveInfo['data'][$key]['DOACTION'] .= "<a target='_blank' href='{$val['teacherJoinUrl']}?nickname={$nickname}&token={$val['assistantToken']}'>助教讲课 | </a>";
            $liveInfo['data'][$key]['DOACTION'] .= "<a target='_blank' href='".U('live/Index/getLivePlayback',['id'=>$val['id'],'type'=>1])."'>回放观看</a>";

            if ($val['is_del'] == 1){
                $liveInfo['data'][$key]['DOACTION'] .= ' | <a title="此操作会彻底删除数据" href="javascript:void(0)"  onclick="admin.doaction('.$val['id'].',\'DelLiveRoom\''.',1)">彻底关闭</a>  ';
            }
            switch(strtolower($type)) {
                case 'index':
                    break;
            }
        }
        return $liveInfo;
    }

    /**
     * 解析光慧直播间列表数据
     * @param integer $limit 结果集数目，默认为20
     * @param $order 排序
     * @return array 解析后的直播列表数据
     */
    private function _getGhLiveList($type,$limit,$order,$map) {
        if(isset($_POST)){
            $_POST['id'] && $map['id']              = intval($_POST['id']);
            $_POST ['uname'] && $map ['uid']        = array('in', (string)$_POST ['uname']);
            $_POST['subject'] && $map['subject']    = array('like', '%' . t($_POST['subject']) . '%');
            $clientJoin = $_POST['clientJoin'] - 1;
            if($clientJoin != -1){
                $map['supportMobile']    = intval($clientJoin);
            }
            $is_active = $_POST['is_active'] - 1;
            if($is_active != -1){
                $map['is_active']    = intval($is_active);
            }
            $is_del = $_POST['is_del'] - 1;
            if($is_del != -1){
                $map['is_del']    = intval($is_del);
            }
            if (! empty ( $_POST ['startDate'] [0] )) {
                $map ['startDate'] = array ('egt',strtotime ( $_POST ['startDate'] [0] )*1000);
            }
            if (! empty(strtotime($_POST ['startDate'] [1]))) {
                $map ['invalidDate'] = array ('elt',strtotime($_POST ['startDate'] [1])*1000);
            }
        }

        $list = model('Live')->getGhLiveInfo($order,$map,$limit);
        $val['uname'] = getUserSpace($val['uid'], null, '_blank');

        foreach($list['data'] as &$val){
            if ($val['is_del'] == 0) {
                $val['is_open'] = "<p style='color: green;'>开启</p>";
            } else {
                $val['is_open'] = '<p style="color: red;">关闭</p>';
            }
            if ($val['is_active'] == 1) {
                $val['is_active'] = "<p style='color: green;'>已审核</p>";
            } else {
                $val['is_active'] = '<span title="审核此直播间" href="javascript:;" onclick="admin.doaction('.$val['id'].',\'ActiveLiveRoom\''.',3)" style="color: red;">未审核</span> ';
            }

            $val['supportMobile'] = $val['supportMobile'] ? '<p style="color: green;">支持</p>' : '<p style="color: red;">不支持</p>';
            $val['startDate'] = date('Y-m-d H:i' , $val['startDate'] / 1000);
            $val['invalidDate']   = date('Y-m-d H:i' , $val['invalidDate'] / 1000);
            $val['DOACTION']  = '<a href="'.U('live/AdminLive/editGhLiveRoom',array('tabHash'=>'editGhLiveRoom','id'=>$val['id'])).'">编辑</a> | ';
            if ($val['is_del'] == 0) {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'ColseLiveRoom\''.',3)">禁用</a> | ';
            } else {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'OpenLiveRoom\''.',3)">启用</a> | ';
            }
            $val['DOACTION'] .= '<a target="_blank" href="'.$this->gh_config['video_url'].'/student/index.html?liveClassroomId='.$val['room_id'].'&customer='.$this->gh_config['customer'].'&customerType=taobao&sp=0">学生观看</a> | ';
            $val['DOACTION'] .= '<a target="_blank" href="'.$this->gh_config['video_url'].'/teacher/index.html?liveClassroomId='.$val['room_id'].'&customer='.$this->gh_config['customer'].'&customerType=taobao&sp=0">老师讲课</a> | ';
            $val['DOACTION'] .= '<a target="_blank" href="'.$this->gh_config['video_url'].'/playback/index.html?liveClassroomId='.$val['room_id'].'&customer='.$this->gh_config['customer'].'&customerType=taobao&sp=0">回放观看</a> | ';

            if ($val['is_del'] == 1){
                $val['DOACTION'] .= ' | <a title="此操作会彻底删除数据" href="javascript:void(0)"  onclick="admin.doaction('.$val['id'].',\'DelLiveRoom\''.',3)">彻底关闭</a>  ';
            }
            switch(strtolower($type)) {
                case 'index':
                    break;
            }
        }
        return $list;
    }

    /**
     * 解析CC直播间列表数据
     * @param integer $limit 结果集数目，默认为20
     * @param $order 排序
     * @return array 解析后的直播列表数据
     */
    private function _getCcLiveList($type,$limit,$order,$map) {
        if(isset($_POST)){
            $_POST['id'] && $map['id']              = intval($_POST['id']);
            $_POST ['uname'] && $map ['uid']        = array('in', (string)$_POST ['uname']);
            $_POST['subject'] && $map['subject']    = array('like', '%' . t($_POST['subject']) . '%');
            $clientJoin = $_POST['clientJoin'] - 1;
            if($clientJoin != -1){
                $map['clientJoin']    = intval($clientJoin);
            }
            $webJoin = $_POST['webJoin'] - 1;
            if($webJoin != -1){
                $map['webJoin']    = intval($webJoin);
            }
            $is_active = $_POST['is_active'] - 1;
            if($is_active != -1){
                $map['is_active']    = intval($is_active);
            }
            $is_del = $_POST['is_del'] - 1;
            if($is_del != -1){
                $map['is_del']    = intval($is_del);
            }
            if (! empty ( $_POST ['startDate'] [0] )) {
                $map ['startDate'] = array ('egt',strtotime ( $_POST ['startDate'] [0] ));
            }
            if (! empty(strtotime($_POST ['startDate'] [1]))) {
                $map ['invalidDate'] = array ('elt',strtotime($_POST ['startDate'] [1]));
            }
        }

        $list = model('Live')->getCcLiveInfo($order,$map,$limit);

        $nickname = M('user')->where("uid={$this->mid}")->getField('uname');
        $l=M('Live')->where(['id'=>$map['live_id']])->getField('teacher_id');
        $larr=explode(',',$l);
        $teacher_info = M('zy_teacher')->where('id ='.$larr[0])->field('id,name')->find();

        foreach($list['data'] as &$val){

        
            if ($val['is_del'] == 0) {
                $val['is_open'] = "<p style='color: green;'>开启</p>";
            } else {
                $val['is_open'] = '<p style="color: red;">关闭</p>';
            }
            if ($val['is_active'] == 1) {
                $val['is_active'] = "<p style='color: green;'>已审核</p>";
            } else {
                $val['is_active'] = '<span title="审核此直播间" href="javascript:;" onclick="admin.doaction('.$val['id'].',\'ActiveLiveRoom\''.',4)" style="color: red;">未审核</span> ';
            }
            $val['type']           = ($val['categoryid']!='')?'√':'×';
            $val['uname']           = getUserSpace($val['uid'], null, '_blank');
            $val['clientJoin']      = $val['clientJoin'] ? '<p style="color: green;">开启</p>' : '<p style="color: red;">关闭</p>';
            $val['webJoin']         = $val['webJoin'] ? '<p style="color: green;">开启</p>' : '<p style="color: red;">关闭</p>';
            $val['startDate']       = date('Y-m-d H:i' , $val['startDate']);
            $val['invalidDate']     = date('Y-m-d H:i' , $val['invalidDate']);

            $val['DOACTION']        = '<a href="'.U('live/AdminLive/editCcLiveRoom',array('tabHash'=>'editCcLiveRoom','id'=>$val['id'],'live_id'=>intval($_GET['id']),"type"=>$type)).'">编辑</a> | ';
            if ($val['is_del'] == 0) {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'ColseLive\''.',4)">禁用</a> | ';
            } else {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'OpenLive\''.',4)">启用</a> | ';
            }
            if($val['types']==2)
            {
                $val['DOACTION'] .= "<a target='_blank' href='{$val['studentJoinUrl']}' >学生观看</a> | ";
                $val['DOACTION'] .= "<a target='_blank' href='{$val['teacherJoinUrl']}' >老师讲课</a> | ";
                $val['DOACTION'] .= "<a target='_blank' href='{$val['assistantJoinUrl']}' >助教讲课</a> | ";//
  
            }else{
                $val['DOACTION'] .= "<a target='_blank' href='{$val['studentJoinUrl']}&autoLogin=true&viewername={$nickname}&viewertoken={$val['studentClientToken']}' >学生观看</a> | ";
                $val['DOACTION'] .= "<a target='_blank' href='{$val['teacherJoinUrl']}&publishname={$teacher_info['name']}&publishpassword={$val['teacherToken']}' >老师讲课</a> | ";
                $val['DOACTION'] .= "<a target='_blank' href='{$val['assistantJoinUrl']}&viewername={$nickname}&viewertoken={$val['assistantToken']}' >助教讲课</a> | ";//
             
            }
               $val['DOACTION'] .= "<a target='_blank' href='".U('live/Index/getLivePlayback',['id'=>$val['id'],'type'=>4])."'>回放观看</a>";

            if ($val['is_del'] == 1){
                $val['DOACTION'] .= ' | <a title="此操作会彻底删除数据" href="javascript:void(0)"  onclick="admin.doaction('.$val['id'].',\'DelLiveRoom\''.',4)">彻底关闭</a>  ';
            }
            switch(strtolower($type)) {
                case 'index':
                    break;
            }
        }
        return $list;
    }

    /**
     * 解析微吼直播间列表数据
     * @param integer $limit 结果集数目，默认为20
     * @param $order 排序
     * @return array 解析后的直播列表数据
     */
    private function _getWhLiveList($type,$limit,$order,$map) {
        if(isset($_POST)){
            $_POST['id'] && $map['id']              = intval($_POST['id']);
            $_POST ['uname'] && $map ['uid']        = array('in', (string)$_POST ['uname']);
            $_POST['subject'] && $map['subject']    = array('like', '%' . t($_POST['subject']) . '%');
            $clientJoin = $_POST['clientJoin'] - 1;
            if($clientJoin != -1){
                $map['clientJoin']    = intval($clientJoin);
            }
            $webJoin = $_POST['webJoin'] - 1;
            if($webJoin != -1){
                $map['webJoin']    = intval($webJoin);
            }
            $is_active = $_POST['is_active'] - 1;
            if($is_active != -1){
                $map['is_active']    = intval($is_active);
            }
            $is_del = $_POST['is_del'] - 1;
            if($is_del != -1){
                $map['is_del']    = intval($is_del);
            }
            if (! empty ( $_POST ['startDate'] [0] )) {
                $map ['startDate'] = array ('egt',strtotime ( $_POST ['startDate'] [0] ));
            }
            if (! empty(strtotime($_POST ['startDate'] [1]))) {
                $map ['invalidDate'] = array ('elt',strtotime($_POST ['startDate'] [1]));
            }
        }

        $list = model('Live')->getWhLiveInfo($order,$map,$limit);
        foreach($list['data'] as &$val){
            if ($val['is_del'] == 0) {
                $val['is_open'] = "<p style='color: green;'>开启</p>";
            } else {
                $val['is_open'] = '<p style="color: red;">关闭</p>';
            }
            if ($val['is_active'] == 1) {
                $val['is_active'] = "<p style='color: green;'>已审核</p>";
            } else {
                $val['is_active'] = '<span title="审核此直播间" href="javascript:;" onclick="admin.doaction('.$val['id'].',\'ActiveLiveRoom\''.',4)" style="color: red;">未审核</span> ';
            }

            $val['uname']           = getUserSpace($val['uid'], null, '_blank');
            $val['clientJoin']      = $val['clientJoin'] ? '<p style="color: green;">公开</p>' : '<p style="color: red;">非公开</p>';
//            $val['webJoin']         = $val['webJoin'] ? '<p style="color: green;">开启</p>' : '<p style="color: red;">关闭</p>';
            $val['startDate']       = date('Y-m-d H:i' , $val['startDate']);
            $val['invalidDate']     = date('Y-m-d H:i' , $val['invalidDate']);

            $val['DOACTION']        = '<a href="'.U('live/AdminLive/editWhLiveRoom',array('tabHash'=>'editWhLiveRoom','id'=>$val['id'],'live_id'=>intval($_GET['id']))).'">编辑</a> | ';
            if ($val['is_del'] == 0) {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'ColseLiveRoom\''.',5)">禁用</a> | ';
            } else {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'OpenLiveRoom\''.',5)">启用</a> | ';
            }

            $user_info = M('user')->where("uid={$this->mid}")->field('uname')->find();
            $user_info['email'] ? : $user_info['email'] = "eduline@eduline.com";
            $val['DOACTION'] .= "<a target='_blank' href='{$this->wh_config['api_url']}/webinar/inituser/{$val['roomid']}?email={$user_info['email']}&name={$user_info['uname']}' >学生观看</a> | ";
            $val['DOACTION'] .= "<a target='_blank' href='{$val['teacherJoinUrl']}' >老师讲课</a> | ";
            $val['DOACTION'] .= "<a target='_blank' href='{$val['assistantJoinUrl']}' >助教讲课</a> | ";//
            $val['DOACTION'] .= "<a target='_blank' href='".U('live/Index/getLivePlayback',['id'=>$val['id'],'type'=>5])."'>回放观看</a>";

            if ($val['is_del'] == 1){
                $val['DOACTION'] .= ' | <a title="此操作会彻底删除数据" href="javascript:void(0)"  onclick="admin.doaction('.$val['id'].',\'DelLiveRoom\''.',5)">彻底关闭</a>  ';
            }

            switch(strtolower($type)) {
                case 'index':
                    break;
            }
        }
        return $list;
    }

    /**
     * 解析CC小班课直播间列表数据
     * @param integer $limit 结果集数目，默认为20
     * @param $order 排序
     * @return array 解析后的直播列表数据
     */
    private function _getCcXbkLiveList($type,$limit,$order,$map) {
        if(isset($_POST)){
            $_POST['id'] && $map['id']              = intval($_POST['id']);
            $_POST ['uname'] && $map ['uid']        = array('in', (string)$_POST ['uname']);
            $_POST['subject'] && $map['subject']    = array('like', '%' . t($_POST['subject']) . '%');
            $clientJoin = $_POST['clientJoin'] - 1;
            if($clientJoin != -1){
                $map['clientJoin']    = intval($clientJoin);
            }
            $webJoin = $_POST['webJoin'] - 1;
            if($webJoin != -1){
                $map['webJoin']    = intval($webJoin);
            }
            $is_active = $_POST['is_active'] - 1;
            if($is_active != -1){
                $map['is_active']    = intval($is_active);
            }
            $is_del = $_POST['is_del'] - 1;
            if($is_del != -1){
                $map['is_del']    = intval($is_del);
            }
            if (! empty ( $_POST ['startDate'] [0] )) {
                $map ['startDate'] = array ('egt',strtotime ( $_POST ['startDate'] [0] ));
            }
            if (! empty(strtotime($_POST ['startDate'] [1]))) {
                $map ['invalidDate'] = array ('elt',strtotime($_POST ['startDate'] [1]));
            }
        }

        $list = model('Live')->getCcXbkLiveInfo($order,$map,$limit);

        $nickname = M('user')->where("uid={$this->mid}")->getField('uname');

        $teacher_info = M('zy_teacher')->where('id ='.M('Live')->where(['id'=>$map['live_id']])->getField('teacher_id'))->field('id,name')->find();

        foreach($list['data'] as &$val){
            if ($val['is_del'] == 0) {
                $val['is_open'] = "<p style='color: green;'>开启</p>";
            } else {
                $val['is_open'] = '<p style="color: red;">关闭</p>';
            }
            if ($val['is_active'] == 1) {
                $val['is_active'] = "<p style='color: green;'>已审核</p>";
            } else {
                $val['is_active'] = '<span title="审核此直播间" href="javascript:;" onclick="admin.doaction('.$val['id'].',\'ActiveLiveRoom\''.',4)" style="color: red;">未审核</span> ';
            }

            $val['uname']           = getUserSpace($val['uid'], null, '_blank');
            $val['clientJoin']      = $val['clientJoin'] == 1 ? '<p style="color: blue;">音视频</p>' : '<p style="color: blue;">仅音频 </p>';
            $val['webJoin']         = $val['webJoin'] == 1 ? '<p style="color: blue;">点名</p>' : '<p style="color: blue;">自由</p>';
            $val['startDate']       = date('Y-m-d H:i' , $val['startDate']);
            $val['invalidDate']     = date('Y-m-d H:i' , $val['invalidDate']);

            $val['DOACTION']        = '<a href="'.U('live/AdminLive/editCcXbkLiveRoom',array('tabHash'=>'editCcXbkLiveRoom','id'=>$val['id'],'live_id'=>intval($_GET['id']))).'">编辑</a> | ';
            if ($val['is_del'] == 0) {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'ColseLiveRoom\''.',6)">禁用</a> | ';
            } else {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'OpenLiveRoom\''.',6)">启用</a> | ';
            }

            $val['DOACTION'] .= "<a target='_blank' href='{$val['studentJoinUrl']}&autoLogin=true&username={$nickname}&password={$val['studentClientToken']}' >学生观看</a> | ";
            $val['DOACTION'] .= "<a target='_blank' href='{$val['teacherJoinUrl']}&autoLogin=true&username={$teacher_info['name']}&password={$val['teacherToken']}' >老师讲课</a> | ";
            $val['DOACTION'] .= "<a target='_blank' href='{$val['assistantJoinUrl']}&autoLogin=true&viewername={$nickname}&viewertoken={$val['assistantToken']}' >助教讲课</a> | ";//
            $val['DOACTION'] .= "<a target='_blank' href='".U('live/Index/getLivePlayback',['id'=>$val['id'],'type'=>6])."'>回放观看</a>";

            if ($val['is_del'] == 1){
                $val['DOACTION'] .= ' | <a title="此操作会彻底删除数据" href="javascript:void(0)"  onclick="admin.doaction('.$val['id'].',\'DelLiveRoom\''.',6)">彻底关闭</a>  ';
            }

            switch(strtolower($type)) {
                case 'index':
                    break;
            }
        }
        return $list;
    }

    /**
     * 解析eeo小班课直播间列表数据
     * @param integer $limit 结果集数目，默认为20
     * @param $order 排序
     * @return array 解析后的直播列表数据
     */
    private function _getEeoXbkLiveList($type,$limit,$order,$map) {
        if(isset($_POST)){
            $_POST['id'] && $map['id']              = intval($_POST['id']);
            $_POST ['uname'] && $map ['uid']        = array('in', (string)$_POST ['uname']);
            $_POST['subject'] && $map['subject']    = array('like', '%' . t($_POST['subject']) . '%');
            $clientJoin = $_POST['clientJoin'] - 1;
            if($clientJoin != -1){
                $map['clientJoin']    = intval($clientJoin);
            }
            $is_active = $_POST['is_active'] - 1;
            if($is_active != -1){
                $map['is_active']    = intval($is_active);
            }
            $is_del = $_POST['is_del'] - 1;
            if($is_del != -1){
                $map['is_del']    = intval($is_del);
            }
            if (! empty ( $_POST ['startDate'] [0] )) {
                $map ['startDate'] = array ('egt',strtotime ( $_POST ['startDate'] [0] ));
            }
            if (! empty(strtotime($_POST ['startDate'] [1]))) {
                $map ['invalidDate'] = array ('elt',strtotime($_POST ['startDate'] [1]));
            }
        }

        $list = model('Live')->getEeoXbkLiveInfo($order,$map,$limit);

        $nickname = M('user')->where("uid={$this->mid}")->getField('uname');
        $teacher_info = M('zy_teacher')->where('id ='.M('Live')->where(['id'=>$map['live_id']])->getField('teacher_id'))->field('id,name')->find();

        foreach($list['data'] as &$val){
            if ($val['is_del'] == 0) {
                $val['is_open'] = "<p style='color: green;'>开启</p>";
            } else {
                $val['is_open'] = '<p style="color: red;">关闭</p>';
            }
            if ($val['is_active'] == 1) {
                $val['is_active'] = "<p style='color: green;'>已审核</p>";
            } else {
                $val['is_active'] = '<span title="审核此直播间" href="javascript:;" onclick="admin.doaction('.$val['id'].',\'ActiveLiveRoom\''.',4)" style="color: red;">未审核</span> ';
            }

            $val['uname']           = getUserSpace($val['uid'], null, '_blank');
            //$val['clientJoin']      = $val['clientJoin'] == 1 ? '<p style="color: blue;">音视频</p>' : '<p style="color: blue;">仅音频 </p>';
            $val['startDate']       = date('Y-m-d H:i' , $val['startDate']);
            $val['invalidDate']     = date('Y-m-d H:i' , $val['invalidDate']);

            $val['DOACTION']        = '<a href="'.U('live/AdminLive/editEeoXbkLiveRoom',array('tabHash'=>'editEeoXbkLiveRoom','id'=>$val['id'],'live_id'=>intval($_GET['id']))).'">编辑</a> | ';
            if ($val['is_del'] == 0) {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'ColseLiveRoom\''.',7)">禁用</a> | ';
            } else {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'OpenLiveRoom\''.',7)">启用</a> | ';
            }

            //$val['DOACTION'] .= "<a target='_blank' href='{$val['studentJoinUrl']}&autoLogin=true&username={$nickname}&password={$val['studentClientToken']}' >学生观看</a> | ";
            $val['DOACTION'] .= "<a target='_blank' href='{$val['teacherJoinUrl']}' >老师讲课</a> | ";
            //$val['DOACTION'] .= "<a target='_blank' href='{$val['assistantJoinUrl']}&autoLogin=true&viewername={$nickname}&viewertoken={$val['assistantToken']}' >助教讲课</a> | ";//
            $val['DOACTION'] .= "<a target='_blank' href='".U('live/Index/getLivePlayback',['id'=>$val['id'],'type'=>7])."'>回放观看</a>";

            if ($val['is_del'] == 1){
                $val['DOACTION'] .= ' | <a title="此操作会彻底删除数据" href="javascript:void(0)"  onclick="admin.doaction('.$val['id'].',\'DelLiveRoom\''.',7)">彻底关闭</a>  ';
            }

            switch(strtolower($type)) {
                case 'index':
                    break;
            }
        }
        return $list;
    }

    /**
     * 解析拓课云小班课直播间列表数据
     * @param integer $limit 结果集数目，默认为20
     * @param $order 排序
     * @return array 解析后的直播列表数据
     */
    private function _getTkLiveList($type,$limit,$order,$map) {
        if(isset($_POST)){
            $_POST['id'] && $map['id']              = intval($_POST['id']);
            $_POST ['uname'] && $map ['uid']        = array('in', (string)$_POST ['uname']);
            $_POST['subject'] && $map['subject']    = array('like', '%' . t($_POST['subject']) . '%');
            $is_active = $_POST['is_active'] - 1;
            if($is_active != -1){
                $map['is_active']    = intval($is_active);
            }
            $is_del = $_POST['is_del'] - 1;
            if($is_del != -1){
                $map['is_del']    = intval($is_del);
            }
            if (! empty ( $_POST ['startDate'] [0] )) {
                $map ['startDate'] = array ('egt',strtotime ( $_POST ['startDate'] [0] ));
            }
            if (! empty(strtotime($_POST ['startDate'] [1]))) {
                $map ['invalidDate'] = array ('elt',strtotime($_POST ['startDate'] [1]));
            }
        }

        $list = model('Live')->getTkLiveInfo($order,$map,$limit);

        $nickname = M('user')->where("uid={$this->mid}")->getField('uname');
        $teacher_info = M('zy_teacher')->where('id ='.M('Live')->where(['id'=>$map['live_id']])->getField('teacher_id'))->field('id,name')->find();

        foreach($list['data'] as &$val){
            if ($val['is_del'] == 0) {
                $val['is_open'] = "<p style='color: green;'>开启</p>";
            } else {
                $val['is_open'] = '<p style="color: red;">关闭</p>';
            }
            if ($val['is_active'] == 1) {
                $val['is_active'] = "<p style='color: green;'>已审核</p>";
            } else {
                $val['is_active'] = '<span title="审核此直播间" href="javascript:;" onclick="admin.doaction('.$val['id'].',\'ActiveLiveRoom\''.',4)" style="color: red;">未审核</span> ';
            }

            $val['uname']           = getUserSpace($val['uid'], null, '_blank');
            $val['scene']      = $val['scene'] == 0 ? '一对一' : '一对多';
            $val['startDate']       = date('Y-m-d H:i' , $val['startDate']);
            $val['invalidDate']     = date('Y-m-d H:i' , $val['invalidDate']);

            $val['DOACTION']        = '<a href="'.U('live/AdminLive/editTkLiveRoom',array('tabHash'=>'editTkLiveRoom','id'=>$val['id'],'live_id'=>intval($_GET['id']))).'">编辑</a> | ';
            if ($val['is_del'] == 0) {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'ColseLiveRoom\''.',8)">禁用</a> | ';
            } else {
                $val['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'OpenLiveRoom\''.',8)">启用</a> | ';
            }

            $val['DOACTION'] .= "<a target='_blank' href='{$val['studentJoinUrl']}' >学生观看</a> | ";
            $val['DOACTION'] .= "<a target='_blank' href='{$val['teacherJoinUrl']}' >老师讲课</a> | ";
            $val['DOACTION'] .= "<a target='_blank' href='{$val['assistantJoinUrl']}' >助教讲课</a> | ";//
            $val['DOACTION'] .= "<a target='_blank' href='".U('live/Index/getLivePlayback',['id'=>$val['id'],'type'=>8])."'>回放观看</a>";

            if ($val['is_del'] == 1){
                $val['DOACTION'] .= ' | <a title="此操作会彻底删除数据" href="javascript:void(0)"  onclick="admin.doaction('.$val['id'].',\'DelLiveRoom\''.',8)">彻底关闭</a>  ';
            }

            switch(strtolower($type)) {
                case 'index':
                    break;
            }
        }
        return $list;
    }

    /**
     * 激活直播间
     */
    public function doactionActiveLiveRoom(){
        $id = intval($_POST['id']);
        $type = intval($_POST['type']);
        if(!$id || !$type){
            $this->ajaxReturn(null,'参数错误',0);
        }
        $map = array('id' => $id,);

        $res = model('Live')->liveRoom->where($map)->save(['is_active'=>1]);
        if(!$res) {
            $this->ajaxReturn(null,'审核失败',0);
        }

        $this->ajaxReturn(null,'审核成功',1);
    }

    /**
     * 禁用直播间
     */
    public function doactionColseLive(){
        $id = $_POST['id'];
        $type = intval($_POST['type']);
        if(!$id || !$type){
            $this->ajaxReturn(null,'参数错误',0);
        }
        $map['id'] = ['in',$id];
        $map['type'] = 2;
        if(intval($_POST['status']) == 1){
            $data['is_del']=2;
        }else{
            $data['is_del']=1;
        }
        $result = model('Live') ->where($map)-> save($data);

        if(!$result){
            $this->ajaxReturn(null,'操作失败',0);
            return;
        }
        $this->ajaxReturn(null,'操作成功',1);
    }
    /**
     * 启用直播间
     */
    public function doactionOpenLive()
    {
        $id = intval($_POST['id']);
        $type = intval($_POST['type']);
        if(!$id || !$type){
            $this->ajaxReturn(null,'参数错误',0);
        }
        $map['id'] = $id;
        $map['type'] = 2;

        $result = model('Live') ->where($map)-> save(['is_del'=>0]);

        if(!$result){
            $this->ajaxReturn(null,'启用失败',0);
            return;
        }

        $this->ajaxReturn(null,'启用成功',1);
    }
    /**
     * 禁用直播间
     */
    public function doactionColseLiveRoom()
    {
        $id = intval($_POST['id']);
        $type = intval($_POST['type']);
        if (!$id || !$type) {
            $this->ajaxReturn(null, '参数错误', 0);
        }
        $map = array('id' => $id,);
        $map['type'] = $type;

        $res = model('Live')->liveRoom->where($map)->save(['is_del'=>1]);
        echo M()->getLastSql();
        exit();
        if (!$res) {
            $this->ajaxReturn(null, '禁用失败', 0);
        }

        $this->ajaxReturn(null, '禁用成功', 1);
        exit;
    }

    /**
     * 审核直播间
     */
    public function doactionOpenActivity(){

        $id = intval($_POST['id']);
        $type = intval($_POST['type']);
        if(!$id || !$type){
            $this->ajaxReturn(null,'参数错误',0);
        }
        $map = array('id' => $id,'type' => 2);

        $result = model('Live') ->where($map)-> save(['is_activity'=>1]);
        if(!$result){
            $this->ajaxReturn(null,'审核失败！',0);
            return;
        }
        $this->ajaxReturn(null,'审核成功',1);
    }

    /**
     * 启用直播间
     */
    public function doactionOpenLiveRoom(){

        $id = intval($_POST['id']);
        $type = intval($_POST['type']);
        if(!$id || !$type){
            $this->ajaxReturn(null,'参数错误',0);
        }
        $map = array('id' => $id,);
        $map['type'] = $type;

        $res = model('Live')->liveRoom->where($map)->save(['is_del'=>0]);
        if (!$res) {
            $this->ajaxReturn(null, '启用失败', 0);
        }

        $this->ajaxReturn(null, '启用成功', 1);
    }

    /**
     * 彻底删除直播间
     */
    public function doactionDelLiveRoom(){
        $id = intval($_POST['id']);
        $type = intval($_POST['type']);
        if(!$id || !$type){
            $this->ajaxReturn(null,'参数错误',0);
        }

        $map['type'] = $type;

        $table = model('Live')->liveRoom;
        $map = array('id' => $id);

        $info = $table->where($map)->find();
        
        if($type == 1){
            if($info['types']==0||$info['types']==1)
            {


                $url   = $this->zshd_config['api_url'].'/room/deleted?';
                $roomid = $table->where($map)->getField('roomid');
                $param = 'roomId='.$roomid;

                $hash  = $param.'&loginName='.$this->zshd_config['api_key'].'&password='.md5($this->zshd_config['api_pwd']).'&sec=true';
                $url   = $url.$hash;
                $delzshdLive = getDataByUrl($url);
                if($delzshdLive['code'] == 0) {
                $result = $table->where($map)->delete();
                if($result){
                    $this->ajaxReturn(null,'彻底关闭成功',1);
                } else {
                    $this->ajaxReturn(null,'彻底关闭失败',0);
                }
                }else{
                    $this->ajaxReturn(null,'彻底关闭失败',0);
                }
            }else{
                $result = $table->where($map)->delete();
                if($result){
                    $this->ajaxReturn(null,'彻底关闭成功',1);
                } else {
                    $this->ajaxReturn(null,'彻底关闭失败',0);
                }

            }
            
        }else if($type == 3){
            if($info['types']==0||$info['types']==1)
            {
                $data['id'] = intval($_POST['id']);
                $data = array_merge($data , _ghdata() );
                $url  = $this->gh_config['api_url'].'/openApi/deleteLiveRoom';
                $res = json_decode( request_post($url , $data) , true);

                if($res['code'] == 0) {
                    $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
                }else{
                    $this->ajaxReturn(null,'彻底关闭成功',1);
                }
            }else{
                $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
            }
        }else if($type == 4){
            if($info['types']==0||$info['types']==1)
            {
                $roomid = $table->where($map)->getField('roomid');

                $delete_live_url  = $this->cc_config['api_url'].'/room/close?';

                $info_map['userid'] = urlencode($this->cc_config['user_id']);
                $info_map['roomid'] = $roomid;

                $delete_live_info_url = $delete_live_url.createHashedQueryString($info_map)[1].'&time='.time().'&hash='.createHashedQueryString($info_map)[0];
                $res   = getDataByUrl($delete_live_info_url);

                if($res['result'] == 'OK') {
                    $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
                }else{
                    $this->ajaxReturn(null,'彻底关闭失败',0);
                }
            }else{
                $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
            }
        }else if($type == 5){
            if($info['types']==0||$info['types']==1)
            {
                $roomid = $table->where($map)->getField('roomid');

                $delete_live_url  = $this->wh_config['api_url'].'/api/vhallapi/v2/webinar/delete';

                $info_map['webinar_id'] = $roomid;
                $info_map['auth_type'] = 2;
                $info_map['app_key']   = t($this->wh_config['api_key']);
                $info_map['signed_at'] = time();
                $info_map['sign']      = createSignQueryString($info_map);

                $res   = getDataByPostUrl($delete_live_url,$info_map);

                if($res->code == 200) {
                    $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
                }else{
                    $this->ajaxReturn(null,'彻底关闭失败',0);
                }
            }else{
                $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
            }
        }else if($type == 6){
            if($info['types']==0||$info['types']==1)
            {
                $roomid = $table->where($map)->getField('roomid');

                $delete_live_url  = $this->cc_config['xbk_api_url'].'/room/close?';

                $info_map['userid'] = urlencode($this->cc_config['user_id']);
                $info_map['roomid'] = $roomid;

                $delete_live_info_url = $delete_live_url.createHashedQueryString($info_map)[1].'&time='.time().'&hash='.createHashedQueryString($info_map)[0];
                $res   = getDataByUrl($delete_live_info_url);

                if($res['result'] == 'OK') {
                    $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
                }else{
                    $this->ajaxReturn(null,'彻底关闭失败',0);
                }
            }else{
                $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
            }
        }else if($type == 7){
            if($info['types']==0||$info['types']==1)
            {
                $live_course_info = $table->where($map)->field('roomid,live_id')->find();
                $live_course_id = model('Live')->where(['id'=>$live_course_info['live_id']])->getField('live_course_id');

                //eeo注册用户
                $delete_live_url = $this->eeo_xbkConfig['api_url']."delCourseClass";

                $query_public_data['SID']       = $this->eeo_xbkConfig['api_key'];
                $query_public_data['safeKey']   = md5($this->eeo_xbkConfig['api_secret'].time());
                $query_public_data['timeStamp'] = time();
                $query_live_course_data['courseId']   = $live_course_id;
                $query_live_course_data['classId']    = $live_course_info['roomid'];

                $live_course_res   = getDataByPostUrl($delete_live_url,array_merge($query_public_data,$query_live_course_data));

                if($live_course_res->error_info->errno == 1){
                    $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
                }else{
                    $this->ajaxReturn(null,'彻底关闭失败',0);
                }
            }else{
                $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
            }
        }else if($type == 8){
            if($info['types']==0||$info['types']==1)
            {
                $live_course_info = $table->where($map)->field('roomid,live_id')->find();

                $delete_live_url = "/WebAPI/roomdelete";

                $client = new Client(['base_uri'=>$this->tk_config['api_url']]);

                $query_map['key']    = $this->tk_config['api_key'];
                $query_map['serial'] = $live_course_info['roomid'];

                $live_course_res = json_decode($client->get($delete_live_url,['query'=>$query_map])->getBody()->getContents());

                if($live_course_res->result == 0){
                    $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
                }else{
                    $this->ajaxReturn(null,'彻底关闭失败',0);
                }
            }else{
                $result = $table->where($map)->delete();
                    if($result){
                        $this->ajaxReturn(null,'彻底关闭成功',1);
                    } else {
                        $this->ajaxReturn(null,'彻底关闭失败',0);
                    }
            }
        }
    }

    public function MakeTree($pid, $level = 0, $isApp,$vid)
    {
        $result = M('zy_live_category')->where('pid='.$pid.' and videoid = '.$vid)->order('sort ASC')->findAll();
        $list   = [];
        if ($result) {
            foreach ($result as $key => $value) {
                if($isApp == 1){
                    $id                                         = $key;
                }else{
                    $id                                         = $value['id'];
                }
                $list[$id]['id']                                = $value['id'];
                $list[$id]['pid']                               = $value['pid'];
                $list[$id]['videoid']                           = $value['videoid'];
                isset($value['is_del']) && $list[$id]['is_del'] = $value['is_del'];
                $list[$id]['title']                             = $value['title'];
                $list[$id]['is_enable']                         = $value['is_enable'];
                $list[$id]['level']                             = $level;
                $child                                          = $this->MakeTree($value['id'], $level + 1 ,$isApp,$vid) ?: [];
                $child && $list[$id]['child']                   = $child;
            }
        }

        return $list;
    }

}
