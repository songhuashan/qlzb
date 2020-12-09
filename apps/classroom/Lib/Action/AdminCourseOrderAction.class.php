<?php

/**
 * 订单管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');

class AdminCourseOrderAction extends AdministratorAction {

    //课程订单模型对象
    protected $order = null;
    protected $live_order = null;

    /**
     * 初始化，配置页面标题；创建模型对象
     * @return void
     */
    public function _initialize() {
		//实例化模型
		$this->order = D('ZyOrderCourse');
		$this->live_order = D('ZyOrderLive');
	
		

		parent::_initialize();
    }

	protected function titleTab($type){
		if($type == 1){
			$this->pageTab[] = array('title' => '已审', 'tabHash' => 'index', 'url' => U('classroom/AdminVideo/index'));
		} else if ($type == 2) {
			$this->pageTab[] = array('title' => '已审', 'tabHash' => 'index', 'url' => U('live/AdminLive/index'));
		} else if ($type == 3) {
			$this->pageTab[] = array('title' => '已审', 'tabHash' => 'index', 'url' => U('classroom/AdminLineClass/index'));
		} else if ($type == 4) {
			$this->pageTab[] = array('title' => '已审', 'tabHash' => 'index', 'url' => U('classroom/AdminAlbum/index'));
		}
		$this->pageTab[] = array('title' => '课程赠送记录', 'tabHash' => 'index_', 'url' => U('classroom/AdminCourseOrder/index_'));
// 		$this->pageTab[] = array('title' => '直播赠送记录', 'tabHash' => 'indexLive', 'url' => U('classroom/AdminCourseOrder/indexLive'));
// 		$this->pageTab[] = array('title' => '线下课赠送记录', 'tabHash' => 'indexCourseLine', 'url' => U('classroom/AdminCourseOrder/indexCourseLine'));
// 		$this->pageTab[] = array('title' => '班级课赠送记录', 'tabHash' => 'indexAlbum', 'url' => U('classroom/AdminCourseOrder/indexAlbum'));
//		$this->pageTab[] = array('title' => '赠送', 'tabHash' => 'addCourseOrder', 'url' => U('classroom/AdminCourseOrder/addCourseOrder'));
	}

    /**
     * 课程订单列表
     */
    public function index_()
    {
		$_REQUEST['tabHash'] = 'index_';

		$type = t($_GET['type']);

		if($type == 2){
			U('classroom/AdminCourseOrder/indexLive',['type'=>2],true);
		} else if($type == 3){
			U('classroom/AdminCourseOrder/indexCourseLine',['type'=>3],true);
		} else if($type == 4){
			U('classroom/AdminCourseOrder/indexAlbum',['type'=>4],true);
		}

		$this->titleTab(t($_GET['type']));

        //显示字段
        $this->pageKeyList = array(
            'user_group_name', 'video_title', 'ctype', 'addtime'
        );
        //页面按钮
        $this->pageButton[] = array('title' => '搜索记录', 'onclick' => "admin.fold('search_form')");
        //搜索字段
        $this->searchKey = array('uid', 'video_id');

        //where
        $map = array();
        if (!empty($_POST['id'])) {
            $map['id'] = $_POST['id'];
        } else {
            //根据用户查找
            if (!empty($_POST['uid'])) {
                $_POST['uid'] = t($_POST['uid']);
                $map['uid'] = array('in', $_POST['uid']);
            }
            //根据商家查找
            if (!empty($_POST['muid'])) {
                $_POST['muid'] = t($_POST['muid']);
                $map['muid'] = array('in', $_POST['muid']);
            }
            //课程ID
            if (!empty($_POST['video_id'])) {
                $map['video_id'] = $_POST['video_id'];
            }
            //专辑订单ID
            if (!empty($_POST['order_album_id'])) {
                $map['order_album_id'] = $_POST['order_album_id'];
            }
            //开始时间
            if (!empty($_POST['startTime'])) {
                $map['ctime'][] = array('gt', strtotime($_POST['startTime']));
            }
            //结束时间
            if (!empty($_POST['endTime'])) {
                $map['ctime'][] = array('lt', strtotime($_POST['endTime']));
            }
        }
        $map['discount_type'] = 4;
        //取得数据列表
        $listData =M()->query("select c.ctype,c.num,c.user_group_name,c.addtime,d.video_title from ( select a.*,b.user_group_name from el_group_giving a left join el_user_group b on(a.gid=b.user_group_id) ) as c left join el_zy_video as d on(c.kid=d.id)");
        
     
        
        //整理数据列表
        foreach ($listData as $key => $val) {
          
            $listData[$key]['addtime']=date("Y-m-d H:i:s",$val['addtime']);
            if($val['ctype']=='1'){
            $listData[$key]['ctype']='录播课';
                
            }elseif($val['ctype']=='2'){
            $listData[$key]['ctype']='直播课';
                
            }
            
        }
        
        
        
        
        
        
        $listData['data']=$listData;
       // dump($listData);
        
        $this->displayList($listData);
    }

    /**
     * 课程订单列表
     */
    public function indexLive()
    {
		$_REQUEST['tabHash'] = 'indexLive';

		$this->titleTab(t($_GET['type']));

        //显示字段
        $this->pageKeyList = array(
            'id', 'uid', 'video_id', 'old_price', 'discount',
            'discount_type', 'price', 'learn_status', 'ctime', 'DOACTION'
        );
        //页面按钮
        $this->pageButton[] = array('title' => '搜索记录', 'onclick' => "admin.fold('search_form')");
        //搜索字段
        $this->searchKey = array('id', 'uid', 'video_id');

        //where
        $map = array();
        if (!empty($_POST['id'])) {
            $map['id'] = $_POST['id'];
        } else {
            //根据用户查找
            if (!empty($_POST['uid'])) {
                $_POST['uid'] = t($_POST['uid']);
                $map['uid'] = array('in', $_POST['uid']);
            }
            //课程ID
            if (!empty($_POST['video_id'])) {
                $map['live_id'] = $_POST['video_id'];
            }
        }
        $map['discount_type'] = 4;

        //取得数据列表
        $listData = $this->live_order->where($map)->order('ctime DESC,id DESC')->findPage();

        //整理数据列表
        foreach ($listData['data'] as $key => $val) {
			$val['video_id'] = $val['live_id'];
            $val = $this->formatData($val,2);
            $val['DOACTION'] = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>2, 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $val['discount_type'] = '系统赠送';
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }

    /**
     * 课程订单列表
     */
    public function indexCourseLine()
    {
		$_REQUEST['tabHash'] = 'indexCourseLine';

		$this->titleTab(t($_GET['type']));

        //显示字段
        $this->pageKeyList = array(
            'id', 'uid', 'course_name', 'old_price', 'discount',
            'discount_type', 'price', 'learn_status', 'ctime', 'DOACTION'
        );
        //页面按钮
        $this->pageButton[] = array('title' => '搜索记录', 'onclick' => "admin.fold('search_form')");
        //搜索字段
        $this->searchKey = array('id', 'uid', 'video_id');

        //where
        $map = array();
        if (!empty($_POST['id'])) {
            $map['id'] = $_POST['id'];
        } else {
            //根据用户查找
            if (!empty($_POST['uid'])) {
                $_POST['uid'] = t($_POST['uid']);
                $map['uid'] = array('in', $_POST['uid']);
            }
            //课程ID
            if (!empty($_POST['video_id'])) {
                $map['video_id'] = $_POST['video_id'];
            }
            //开始时间
            if (!empty($_POST['startTime'])) {
                $map['ctime'][] = array('gt', strtotime($_POST['startTime']));
            }
            //结束时间
            if (!empty($_POST['endTime'])) {
                $map['ctime'][] = array('lt', strtotime($_POST['endTime']));
            }
        }
        $map['discount_type'] = 4;
        //取得数据列表
        $listData = M('zy_order_teacher')->where($map)->order('ctime DESC,id DESC')->findPage();
        //整理数据列表
        foreach ($listData['data'] as $key => $val) {
			$course_line = M('zy_teacher_course')->where(['course_id'=>$val['video_id']])->field('course_name,course_price')->find();
			$val['course_name'] = $course_line['course_name'];
			$val['old_price'] = $course_line['course_price'];
            $val = $this->formatData($val);
            $val['DOACTION'] = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>3, 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $val['discount_type'] = '系统赠送';
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }

    /**
     * 课程订单列表
     */
    public function indexAlbum()
    {
		$_REQUEST['tabHash'] = 'indexAlbum';

		$this->titleTab(t($_GET['type']));

        //显示字段
        $this->pageKeyList = array(
            'id', 'uid', 'video_id', 'discount',
            'discount_type', 'price', 'learn_status', 'ctime', 'DOACTION'
        );
        //页面按钮
        $this->pageButton[] = array('title' => '搜索记录', 'onclick' => "admin.fold('search_form')");
        //搜索字段
        $this->searchKey = array('id', 'uid', 'video_id');

        //where
        $map = array();
        if (!empty($_POST['id'])) {
            $map['id'] = $_POST['id'];
        } else {
            //根据用户查找
            if (!empty($_POST['uid'])) {
                $_POST['uid'] = t($_POST['uid']);
                $map['uid'] = array('in', $_POST['uid']);
            }
            //课程ID
            if (!empty($_POST['video_id'])) {
				$map['album_id'] = $_POST['video_id'];
			}
        }
        $map['discount_type'] = 4;
        //取得数据列表
        $listData = M('zy_order_album')->where($map)->order('ctime DESC,id DESC')->findPage();
        //整理数据列表
        foreach ($listData['data'] as $key => $val) {
			$val['video_id'] = M('album')->where(['id'=>$val['album_id']])->getField('album_title');
            $val = $this->formatData($val,4);
            $val['DOACTION'] = '<a href="' . U(APP_NAME . '/' . MODULE_NAME . '/viewOrder', array('id' => $val['id'],'type'=>4, 'tabHash' => 'viewOrder')) . '">查看详细</a>';
            $val['discount_type'] = '系统赠送';
            $listData['data'][$key] = $val;
        }
        $this->displayList($listData);
    }

    public function addCourseOrder(){
		$_REQUEST['tabHash'] = 'addCourseOrder';

        $id = t($_GET['id']);
        $type = t($_GET['type']);

		$this->titleTab($type);

		if($type == 4){
			$album_data = D('Album')->where(['id'=>$id])->field('id,status,is_del,album_title')->find();

			if(!$album_data)$this->error("班级课程不存在");
			if ($album_data['status'] == 0) $this->error("班级课程待审核");
			if ($album_data['is_del'] == 1) $this->error("班级课程被禁用");
			$this->assign($album_data);
			$this->assign('video_title',$album_data['album_title']);
		} else if ($type == 3) {
			$line_class = D('ZyLineClass')->where(['course_id'=>$id])->field('course_id,course_name,is_activity,is_del')->find();

			if(!$line_class)$this->error("线下课程不存在");
			if ($line_class['is_activity'] == 0) $this->error("线下课程待审核");
			if ($line_class['is_del'] == 1) $this->error("线下课程被禁用");

			$this->assign('id',$line_class['course_id']);
			$this->assign('video_title',$line_class['course_name']);
		} else {
			$videores = M('zy_video')->where(['id'=>$id])->field('id,video_title,video_binfo,listingtime,uctime,is_activity,type')->find();

			if(!$videores)$this->error("课程不存在");
			if ($videores['is_activity'] == 0) $this->error("课程待审核");
			if ($videores['is_del'] == 1) $this->error("课程被禁用");
			//if ($videores['listingtime'] > time()) $this->error("课程未上架");
			//if ($videores['uctime'] < time()) $this->error("课程已下架");

			$this->assign($videores);
			$type = $videores['type'];
		}

		$this->assign('type',$type);
		$this->assign('pattern',1);
        $this->assign('user_group',model('UserGroup')->getUserGroup());
        $this->display();
    }

    public function seachCourse(){
        $key = $_POST['key'];

        $time  = time();
        $where = "is_del=0 AND is_activity=1 AND uctime>$time AND listingtime<$time AND video_title like  '%$key%'";

        $videolist = D('ZyVideo')->where($where)->field('id,video_title')->select();

        $this->assign("list",$videolist);
        $this->assign("type",1);

        $html = $this->fetch("seachCourse");
        echo json_encode($html);
    }

    public function doCourseOrder(){
        

        $video_id = trim($_POST['video_ids'],',');
        if(!$video_id){
            $this->ajaxReturn('',"请选择课程",0);
        }

		$type = t($_POST['course_type']);

		
	
		if ($type == 3){
			$line_class = D('ZyLineClass')->where(['course_id'=>$video_id])->field('course_id,course_name,course_price,mhm_id,teacher_id,is_activity,is_del')->find();

			if(!$line_class)$this->error("线下课程不存在");
			if ($line_class['is_activity'] == 0) $this->error("线下课程待审核");
			if ($line_class['is_del'] == 1) $this->error("线下课程被禁用");
		} else if ($type == 4){
			$album = D('Album')->where(['id'=>$video_id])->field('id,status,is_del,album_title,price,mhm_id')->find();

			if(!$album)$this->error("班级课程不存在");
			if ($album['status'] == 0) $this->error("班级课程待审核");
			if ($album['is_del'] == 1) $this->error("班级课程被禁用");
		} else {
			//取得课程
			$video = M('zy_video')->where(array('id' => $video_id,))->field("id,uid,video_title,teacher_id,v_price,t_price,
        		vip_level,uctime,listingtime,endtime,starttime,limit_discount,mhm_id,term,type,is_del,is_activity")->find();

			if(!$video){$this->ajaxReturn('',"课程不存在",0);}
			if ($video['is_activity'] == 0) {$this->ajaxReturn('',"课程待审核",0);}
			if ($video['is_del'] == 1) {$this->ajaxReturn('',"课程被禁用",0);}
			//if ($video['listingtime'] > time()) {$this->ajaxReturn('',"课程未上架",0);}
			//if ($video['uctime'] < time()) {$this->ajaxReturn('',"课程已下架",0);}

			$type = $video['type'];
		}

      
            $uids = getSubByKey ( D ( 'user_group_link' )->where ( 'user_group_id=' . intval ( $_POST ['user_group'] ) )->findAll (), 'uid' );
           
            //$live_pay_status = M('zy_order_live')->where(['live_id'=>$video_id])->field('id,uid,pay_status')->findAll();

           
		
		$time = time();

		$is_buy_stause = false;

        
	
		if($type == 1){
		    
		    
			$insert_course_value = '';
			$term = $video['term'] ? : 0;
			$time_limit = $term ? time() + (86400 * floatval($term)) : 0;

			$pay_data = ['pay_status'=>3,'order_album_id'=>0,'rel_id'=>0,'ptime'=>$time,'discount_type'=>4,'price'=>0.00];

			foreach ($uids as $key => $val) {
				$video_pay_status = M('zy_order_course')->where(['uid'=>$val,'video_id'=>$video_id])->field('id,pay_status')->find();

				
				if($video_pay_status['pay_status'] == 3 || $video_pay_status['pay_status'] == 6){
					unset($val);
				} else if ($video_pay_status['pay_status'] == 1 || $video_pay_status['pay_status'] == 2 || $video_pay_status['pay_status'] == 5 ||$video_pay_status['pay_status'] == 7){
					D("ZyOrderCourse",'classroom')->where(array('uid'=>$val, 'id'=>$video_pay_status['id']))->save($pay_data);

					$s['uid'] = $val;
					$s['title'] = "收到系统赠送课程：{$video['video_title']}";
					$s['body'] = "恭喜您收到系统赠送课程：{$video['video_title']},<a href='".U('classroom/Video/view',["id"=>$video_id])."' style='color: #3b5999;'>点我去学习！</a>";
					$s['ctime'] = time();
					model('Notify')->sendMessage($s);
					M('zy_video')->where(array('id' => $video_id))->setInc('video_order_count');
					unset($val);
					$is_buy_stause = true;
				} else {
					$insert_course_value .= "('" . $val . "','" . $video['uid'] . "','" . $video_id . "','" . $video['v_price'] . "','" . $video['v_price'] . "','4','0.00','0','0','3','$time','$time','0','0','$term','$time_limit',{$video['mhm_id']}),";

					$s['uid'] = $val;
					$s['title'] = "收到系统赠送课程：{$video['video_title']}";
					$s['body'] = "恭喜您收到系统赠送课程：{$video['video_title']},<a href='".U('classroom/Video/view',["id"=>$video_id])."' style='color: #3b5999;'>点我去学习！</a>";
					$s['ctime'] = time();
					model('Notify')->sendMessage($s);
					M('zy_video')->where(array('id' => $video_id))->setInc('video_order_count');
					unset($val);
				}
			}

			if($insert_course_value){
				$course_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_course (`uid`,`muid`,`video_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`ctime`,`is_del`,`rel_id`,`term`,`time_limit`,`mhm_id`) VALUE " . trim($insert_course_value, ',');
				$order_res = M('zy_order_course')->execute($course_order_sql)? true : false;
			} else if($is_buy_stause === true) {
				$order_res = true;
			} else {
			    
			
			    //存入el_group_giving 用户表
			    
			    $kid=$_POST['video_ids'];
			    $user_group=$_POST['user_group'];
			    $ctype=$_POST['course_type'];
			    $addtime=time();
			    $num='0';
			    
			    $info=M('group_giving')->where(array('gid'=>$user_group,'kid'=>$kid,'ctype'=>$ctype))->select();
			    
			    if(empty($info[0])){
			        
			        M()->query("INSERT INTO `el_group_giving`(`gid`, `kid`, `ctype`, `num`, `addtime`) VALUES ('$user_group','$kid','$ctype','$num','$addtime')");
			    }
			    
			    
			   
			    

				$this->ajaxReturn('',"赠送的用户已经可以学习此课程了",0);
			}
		} else if($type == 2) {
			$insert_live_value = '';

			$pay_data = ['pay_status'=>3,'order_album_id'=>0,'rel_id'=>0,'ptime'=>$time,'discount_type'=>4,'price'=>0.00];
            // die('476');
            $uids_string = implode(',', $uids);
            //dump($uids_string);
            $map = array();
            $map['uid'] = array('in',$uids_string);
            $map['live_id'] = $video_id;
            //dump($map);
            $live_pay_status = M('zy_order_live')->where($map)->field('id,pay_status')->findAll();
           
            //dump(M('zy_order_live')->getLastSql());
            //dump($live_pay_status);die();
            // dump($uids);
        


            


            $uids_string = 0;
			foreach ($uids as $key => $val) {

				//dump($val);
                    //$live_pay_status = M('zy_order_live')->where(['uid'=>$val,'live_id'=>$video_id])->field('id,uid,pay_status')->find();
				foreach($live_pay_status as $key2 =>$val2){
                    //dump($val2);die;
                    if($val2['pay_status'] == 3 || $val2['pay_status'] == 6){               
                    //unset($val);
                } else if ($val2['pay_status'] == 1 || $val2['pay_status'] == 2 || $val2['pay_status'] == 5 ||$val2['pay_status'] == 7){
                    D("ZyOrderLive",'classroom')->where(array('uid'=>$val, 'id'=>$val2['id']))->save($pay_data);
                    $s['uid'] = $val;
                    $s['title'] = "收到系统赠送直播课程：{$video['video_title']}";
                    $s['body'] = "恭喜您收到系统赠送直播课程：{$video['video_title']},<a href='".U('live/Index/view',["id"=>$video_id])."' style='color: #3b5999;'>点我去学习！</a>";
                    $s['ctime'] = time();
                    model('Notify')->sendMessage($s);
                    
                    M('zy_video')->where(array('id' => $video_id))->setInc('video_order_count');
                   
                    //unset($val);
                    $is_buy_stause = true;
                // } else {
                //     // var_dump('数据为空');
                //         // dump($val);
                //     $insert_live_value .= "('" . $val . "','" . $video_id . "','" . $video['v_price'] . "','" . $video['v_price'] . "','4','0.00','0','0','3','$time','$time','0','0',{$video['mhm_id']}),";
                //     //var_dump($insert_live_value);die();
                //     dump($insert_live_value);
                //     $s['uid'] = $val;
                //     $s['title'] = "收到系统赠送课程：{$video['video_title']}";
                //     $s['body'] = "恭喜您收到系统赠送课程：{$video['video_title']},<a href='".U('classroom/Video/view',["id"=>$video_id])."' style='color: #3b5999;'>点我去学习！</a>";
                //     $s['ctime'] = time();
                //     // $live_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_live (`uid`,`live_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`ctime`,`is_del`,`rel_id`,`mhm_id`) VALUE " . trim($insert_live_value, ',');
                //     // $order_res = M('zy_order_live')->execute($live_order_sql)? true : false;
                //     model('Notify')->sendMessage($s);
                //     $uids_string += 1; 
                //     // M('zy_video')->where(array('id' => $video_id))->setInc('video_order_count');
                //     // dump(M('zy_video')->getLastSql());die();
                //     // unset($val);
                    

                //     // var_dump($insert_live_value);
                    
                //     //$this->ajaxReturn('',"此门课程不可以赠送",0);
                // }
                }
                
                
			}
            

                    //dump($val);
                    
                    $insert_live_value .= "('" . $val . "','" . $video_id . "','" . $video['v_price'] . "','" . $video['v_price'] . "','4','0.00','0','0','3','$time','$time','0','0',{$video['mhm_id']}),";
                   
                    $s['uid'] = $val;
                    $s['title'] = "收到系统赠送课程：{$video['video_title']}";
                    $s['body'] = "恭喜您收到系统赠送课程：{$video['video_title']},<a href='".U('classroom/Video/view',["id"=>$video_id])."' style='color: #3b5999;'>点我去学习！</a>";
                    $s['ctime'] = time();
                    // $live_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_live (`uid`,`live_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`ctime`,`is_del`,`rel_id`,`mhm_id`) VALUE " . trim($insert_live_value, ',');
                    // $order_res = M('zy_order_live')->execute($live_order_sql)? true : false;
                    model('Notify')->sendMessage($s);
                    $uids_string += 1; 
                    // M('zy_video')->where(array('id' => $video_id))->setInc('video_order_count');
                    
                }


            if($uids_string > 0){
                M('zy_video')->where(array('id' => $video_id))->setInc('video_order_count',$uids_string);
            }
            
            
			if($insert_live_value){
                //dump(trim($insert_live_value,','));
                //die('514');
                // var_dump('没有反应');
				$live_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_live (`uid`,`live_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`ctime`,`is_del`,`rel_id`,`mhm_id`) VALUE " . trim($insert_live_value, ',');
                //dump($live_order_sql);
				$order_res = M('zy_order_live')->execute($live_order_sql)? true : false;
               // var_dump($order_res.'515');die;
			} else if($is_buy_stause === true) {
                //die('520');
				$order_res = true;
			} else {
                
			    //存入el_group_giving 用户表
			    // var_dump('存入用户表');
			     
			   
                $kid=$_POST['video_ids'];
                $user_group=$_POST['user_group'];
                $ctype=$_POST['course_type'];
                $addtime=time();
                $num='0';
                 
                $info=M('group_giving')->where(array('gid'=>$user_group,'kid'=>$kid,'ctype'=>$ctype))->select();
              
                if(empty($info[0])){
                     
                    M()->query("INSERT INTO `el_group_giving`(`gid`, `kid`, `ctype`, `num`, `addtime`) VALUES ('$user_group','$kid','$ctype','$num','$addtime')");
                }
				$this->ajaxReturn('',"赠送的用直户可接可以学习此课程了",0);
			}
		} else if($type == 3){
			$insert_course_line_value = '';

			$pay_data = ['pay_status'=>3,'rel_id'=>0,'ptime'=>$time,'discount_type'=>4,'price'=>0.00,'tid'=>$line_class['teacher_id']];

			foreach ($uids as $key => $val) {
				$course_line_pay_status = M('zy_order_teacher')->where(['uid'=>$val,'video_id'=>$video_id])->field('id,pay_status')->find();

				if($course_line_pay_status['pay_status'] == 3 || $course_line_pay_status['pay_status'] == 6){
					unset($val);
				} else if ($course_line_pay_status['pay_status'] == 1 || $course_line_pay_status['pay_status'] == 2 || $course_line_pay_status['pay_status'] == 5 ||$course_line_pay_status['pay_status'] == 7){
					M('zy_order_teacher')->where(['uid'=>$val, 'id'=>$course_line_pay_status['id']])->save($pay_data);

					$s['uid'] = $val;
					$s['title'] = "收到系统赠送线下课程：{$line_class['course_name']}";
					$s['body'] = "恭喜您收到系统赠送线下课程：{$line_class['course_name']},<a href='".U('classroom/LineClass/view',["id"=>$video_id])."' style='color: #3b5999;'>点我去学习！</a>";
					$s['ctime'] = time();
					model('Notify')->sendMessage($s);
					M('zy_teacher_course')->where(array('course_id' => $video_id))->setInc('course_order_count');
					unset($val);
					$is_buy_stause = true;
				} else {
					$insert_course_line_value .= "('" . $val . "','" . $video_id . "','" . $line_class['course_price'] . "','4','0.00','0','3','$time','$time','0','0',{$line_class['mhm_id']},{$line_class['teacher_id']}),";

					$s['uid'] = $val;
					$s['title'] = "收到系统赠送课程：{$line_class['course_name']}";
					$s['body'] = "恭喜您收到系统赠送线下课程：{$line_class['course_name']},<a href='".U('classroom/Video/view',["id"=>$video_id])."' style='color: #3b5999;'>点我去学习！</a>";
					$s['ctime'] = time();
					model('Notify')->sendMessage($s);
					M('zy_teacher_course')->where(array('course_id' => $video_id))->setInc('course_order_count');
					unset($val);
				}
			}

			if($insert_course_line_value){
				$course_line_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_teacher (`uid`,`video_id`,`discount`,`discount_type`,`price`,`learn_status`,`pay_status`,`ptime`,`ctime`,`is_del`,`rel_id`,`mhm_id`,`tid`) VALUE " . trim($insert_course_line_value, ',');
				$order_res = M('zy_order_teacher')->execute($course_line_order_sql)? true : false;
			} else if($is_buy_stause === true) {
				$order_res = true;
			} else {
			    //存入el_group_giving 用户表
			    

			    $kid=$_POST['video_ids'];
			    $user_group=$_POST['user_group'];
			    $ctype=$_POST['course_type'];
			    $addtime=time();
			    $num='0';
			     
			    $info=M('group_giving')->where(array('gid'=>$user_group,'kid'=>$kid,'ctype'=>$ctype))->select();
			   
			    if(empty($info[0])){
			         
			        M()->query("INSERT INTO `el_group_giving`(`gid`, `kid`, `ctype`, `num`, `addtime`) VALUES ('$user_group','$kid','$ctype','$num','$addtime')");
			    }
			    
				$this->ajaxReturn('',"赠送的用户可直接可以学习此课程了",0);
			}
		} else if($type == 4){
			$this_mid = t($_POST['uids']);

			$is_buy = D('ZyOrderAlbum')->isBuyAlbum($this_mid ,$video_id );
			if($is_buy){
				$this->mzError("赠送的用户可直接学习该班级课程");
			}
			//无过期非法信息则生成状态为已支付的订单数据
			$data = array(
				'uid'           => $this_mid,
				'album_id'      => $video_id,
				'old_price'     => $album['price'],
				'discount'      => $album['price'],
				'discount_type' => 4,
				'price'         => 0.00,//$album['now_price'],
				'learn_status'  => 0,
				'ctime'         => time(),
				'is_del'        => 0,
				'pay_status'    => 3,
				'mhm_id'        => $album['mhm_id'],
			);

			$order_id = D('ZyOrderAlbum')->where(array('uid'=>$this_mid,'album_id'=>$video_id))->getField('id');

			if($order_id){
				$id = D('ZyOrderAlbum')->where(array('uid'=>$this_mid,'album_id'=>$video_id))->save($data);
			}else{
				$id = D('ZyOrderAlbum')->add($data);
			}

			if ($id) {
				//批量添加班级下课程订单
				$video_ids      = trim(D("Album",'classroom')->getVideoId($video_id), ',');
				$v_map['id']        = array('in', array($video_ids));
				$v_map["is_del"]    = 0;
				$album_info         = M("zy_video")->where($v_map)->field("id,uid,video_title,mhm_id,teacher_id,
                                          v_price,t_price,discount,vip_level,endtime,starttime,limit_discount,type")
					->select();
				M('album')->where(array('id' => $video_id))->setInc('order_count');
				$a_map['id']      = array('in', array($video_ids));
				M('zy_video')->where($a_map)->setInc('video_order_count');

				$insert_live_value = "";
				$insert_course_value = "";
				$time = time();

				$pay_data = ['pay_status'=>3,'order_album_id'=>0,'rel_id'=>$id,'ptime'=>$time,'discount_type'=>4,'price'=>0.00];

				foreach ($album_info as $key => $video) {
					//如果已经购买 则销毁，已有订单则改为支付
					if($video['type'] == 1) {
						$video_pay_status = D("ZyOrderCourse",'classroom')->where(array('uid'=>$this_mid, 'video_id'=>$video['id']))->field('id,pay_status')->find();
						if($video_pay_status['pay_status'] == 3 || $video_pay_status['pay_status'] == 6){
							unset($video);
						} else if ($video_pay_status['pay_status'] == 1 || $video_pay_status['pay_status'] == 2 || $video_pay_status['pay_status'] == 5 ||$video_pay_status['pay_status'] == 7){
							D("ZyOrderCourse",'classroom')->where(array('uid'=>$this_mid, 'id'=>$video_pay_status['id']))->save($pay_data);
							unset($video);
						}
					}
					if($video['type'] == 2) {
						$video_pay_status = D("ZyOrderLive",'classroom')->where(array('uid'=>$this_mid, 'live_id'=>$video['id']))->field('id,pay_status')->find();
						if($video_pay_status['pay_status'] == 3 || $video_pay_status['pay_status'] == 6){
							unset($video);
						}elseif($video_pay_status['pay_status'] == 1 || $video_pay_status['pay_status'] == 2 || $video_pay_status['pay_status'] == 5 ||$video_pay_status['pay_status'] == 7){
							D("ZyOrderLive",'classroom')->where(array('uid'=>$this_mid, 'id'=>$video_pay_status['id']))->save($pay_data);
							unset($video);
						}
					}

					$album_info[$key] = $video;
				}
				$album_info = array_filter($album_info);

				foreach ($album_info as $key => $video) {
					if($video['type'] == 2){
						$insert_live_value .= "('" . $this_mid . "','" . $video['id'] . "','" . $video['t_price'] . "','0.00','0','" . $video['price']['price'] . "','" . $video_id . "','0','3','". time()."','" .$album['mhm_id']."',". time() . ",'0','".$data['rel_id']."'),";
					}else{
						$insert_course_value .= "('" . $this_mid . "','" . $video['uid'] . "','" . $video['id'] . "','" . $video['v_price'] . "','" . ($video['price']['discount'] / 10) . "','" . $video['price']['dis_type'] . "','" . $video['price']['price'] . "','" . $video_id . "','0','3','". time()."','" .$album['mhm_id']."',". time() . ",'0','".$data['rel_id']."'),";
					}
				}
				if($insert_live_value){
					$live_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_live (`uid`,`live_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`mhm_id`,`ctime`,`is_del`,`rel_id`) VALUE " . trim($insert_live_value, ',');
					M('zy_order_live')->execute($live_order_sql)? true : false;
				}
				if($insert_course_value){
					$course_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_order_course (`uid`,`muid`,`video_id`,`old_price`,`discount`,`discount_type`,`price`,`order_album_id`,`learn_status`,`pay_status`,`ptime`,`mhm_id`,`ctime`,`is_del`,`rel_id`) VALUE " . trim($insert_course_value, ',');
					M('zy_order_course')->execute($course_order_sql)? true : false;
				}

				$order_res = true;
			} else {
				$order_res = false;
			}
		}

        //var_dump($order_res.'690');

        if($order_res){
            
           
        $kid=$_POST['video_ids'];
        $user_group=$_POST['user_group'];
        $ctype=$_POST['course_type'];
        $addtime=time();
        $num='0';
         
        $info=M('group_giving')->where(array('gid'=>$user_group,'kid'=>$kid,'ctype'=>$ctype))->select();
       
        if(empty($info[0])){
             
            M()->query("INSERT INTO `el_group_giving`(`gid`, `kid`, `ctype`, `num`, `addtime`) VALUES ('$user_group','$kid','$ctype','$num','$addtime')");
        }
            $this->ajaxReturn('',"赠送成功",1);
        } else {
            $this->ajaxReturn('',"赠送失败",0);
        }
        exit;
    }

    /**
     * 查看课程订单
     * @return void
     */
    public function viewOrder() {
		$type = t($_GET['type']);
		$this->titleTab($type);

		//不允许更改
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $url = U(APP_NAME . '/' . MODULE_NAME . '/index');
            $this->redirect($url);
            exit;
        }

        $_GET['id'] = intval($_GET['id']);

		if($type == 1){
			$video_title = "课程";
			$table = $this->order;
		} else if($type == 2) {
			$video_title = "直播课程";
			$table = $this->live_order;
		} else if($type == 3) {
			$video_title = "线下课程";
			$table = M('zy_order_teacher');
		} else if($type == 4) {
			$video_title = "班级课程";
			$table = M('zy_order_album');
		}

        $this->pageTab[] = array('title' => "查看{$video_title}订单-ID:" . $_GET['id'], 'tabHash' => 'viewOrder', 'url' => U('classroom/AdminCourseOrder/viewOrder', array('id' => $_GET['id'])));
        //显示字段
        $this->pageKeyList = array(
            'id', 'ctime', 'uid', 'video_id', 'old_price', 'discount',
            'discount_type', 'price');
        //点击按钮返回来源页面
        $this->submitAlias = '返 回';
        $this->onsubmit = 'admin.zyPageBack()';
        $this->pageTitle['viewOrder'] = "{$video_title}订单  - 查看详细";
        $this->savePostUrl = U(APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME);

        $data = $table->find($_GET['id']);

		if($type == 4) {
			$data['video_id'] = M('album')->where(['id'=>$data['album_id']])->getField('album_title');
		} else if ($type == 3){
			$course_line = M('zy_teacher_course')->where(['course_id'=>$data['video_id']])->field('course_name,course_price')->find();
			$data['video_id'] = $course_line['course_name'];
			$data['old_price'] = $course_line['course_price'];
		}

        if (!$data)
            $this->error('没有找到对应的订单记录');

        $data = $this->formatData($data,$type);
        $this->displayConfig($data);
    }

    /**
     * 数据显示格式化
     * @param $val 一个结果集数组
     * @return array
     */
    protected function formatData($val,$type) {
        //学习状态
        $learn_status = array('未开始', '学习中', '已完成');
        //折扣类型
        $discount_type = array('<span style="color:gray">无折扣</span>', '会员折扣', '限时优惠','','系统赠送');
        //取得专辑订单的专辑ID
        if ($val['order_album_id'] > 0) {
            $albumId = $this->orderAlbum->getAlbumIdById($val['order_album_id']);
            $val['album_title'] = getAlbumNameForID($albumId);
        } else {
            $val['album_title'] = ACTION_NAME == 'albumOrderList' ? '<span style=color:gray>单独购买</span>' : '-';
        }
        //购买用户
        $val['uid'] = getUserSpace($val['uid'], null, '_blank');
        //课程卖家|商家
        $val['muid'] = getUserSpace($val['muid'], null, '_blank');
        //课程学习状态
        $val['learn_status'] = $learn_status[$val['learn_status']];

		if($type == 1 || $type == 2){
			//取得课程名称
			$val['video_id'] = '<div style="width:300px;">' . getVideoNameForID($val['video_id']) . '</div>';
		}

		//价格和折扣
        $val['old_price'] = '<span style="text-decoration:line-through;">￥' . $val['old_price'] . '</span>';
        $val['price'] = '<span style="color:red">￥' . $val['price'] . '</span>';
        $val['discount_type'] = $discount_type[$val['discount_type']];
        if ($val['discount_type'] > 0) {
            $val['discount'] = $val['discount'] . '折';
        } else {
            $val['discount'] = '-';
        }

        //购买时间
        $val['ctime'] = friendlyDate($val['ctime']);

        return $val;
    }
}
