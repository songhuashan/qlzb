<?php
/**
 * 机构列表详情
 * @author wangjun@chuyouyun.com
 * @version chuyouyun1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminSchoolDivideIntoConfigAction extends AdministratorAction
{
    /**
     * 机构管理
     * @return void
     */
    public function _initialize()
    {
        if(is_admin($this->mid)) {
            $this->pageTab = [
                array('title' => '点播分成比例配置', 'tabHash' => 'divideIntoCourseConfig', 'url' => U('school/AdminSchoolDivideIntoConfig/divideIntoCourseAdminConfig')),
                array('title' => '直播分成比例配置', 'tabHash' => 'divideIntoLiveConfig', 'url' => U('school/AdminSchoolDivideIntoConfig/divideIntoLiveAdminConfig')),
                array('title' => '班级分成比例配置', 'tabHash' => 'divideIntoAlbumConfig', 'url' => U('school/AdminSchoolDivideIntoConfig/divideIntoAlbumAdminConfig')),
                array('title' => '线下课分成比例配置', 'tabHash' => 'divideIntoCourseLineConfig', 'url' => U('school/AdminSchoolDivideIntoConfig/divideIntoCourseLineAdminConfig')),
            ];
        }else{
            $this->pageTab = [
                array('title' => '点播分成比例配置', 'tabHash' => 'divideIntoCourseConfig', 'url' => U('school/AdminSchoolDivideIntoConfig/divideIntoCourseConfig')),
                array('title' => '直播分成比例配置', 'tabHash' => 'divideIntoLiveConfig', 'url' => U('school/AdminSchoolDivideIntoConfig/divideIntoLiveConfig')),
                array('title' => '班级分成比例配置', 'tabHash' => 'divideIntoAlbumConfig', 'url' => U('school/AdminSchoolDivideIntoConfig/divideIntoAlbumConfig')),
                array('title' => '线下课分成比例配置', 'tabHash' => 'divideIntoCourseLineConfig', 'url' => U('school/AdminSchoolDivideIntoConfig/divideIntoCourseLineConfig')),
            ];
        }

        parent::_initialize();
    }

    /**
     * 点播分成比例配置
     */
    public function divideIntoCourseAdminConfig(){
        if(is_admin($this->mid)){
            $_REQUEST['tabHash'] = 'divideIntoCourseConfig';

            $this->pageKeyList = array('id','uid','mhm_id','course_platform_and_school','course_platform_and_school_action',
                'course_school_and_mschool','course_school_and_teacher','course_teacher_and_share','course_teacher_and_share_action',
                'status','ctime','DOACTION');
            $this->pageButton[] = array('title' => "待审", 'onclick' => "admin.jumpDivideInto('Course')");

            $list = $this->data_list('course',$fun = $_GET['fun']);

            $this->displayList($list);
        }
    }

    /**
     * 点播分成比例配置
     */
    public function divideIntoCourseConfig(){
        if(is_school($this->mid)){
            if( isset($_POST) ) {
                $platform_and_school_action = array_filter(explode(':', t($_POST['course_platform_and_school_action'])));
                $school_and_mschool = array_filter(explode(':', t($_POST['course_school_and_mschool'])));
                $school_and_teacher = array_filter(explode(':', t($_POST['course_school_and_teacher'])));
                $teacher_and_share_action = array_filter(explode(':', t($_POST['course_teacher_and_share_action'])));

                if($platform_and_school_action[0] + $platform_and_school_action[1] != 100){
                    $this->error("平台与机构待审比例之和需为100");
                }
                if($school_and_mschool[0] + $school_and_mschool[1] != 100){
                    $this->error("机构与销课机构比例之和需为100");
                }
                if($school_and_teacher[0] + $school_and_teacher[1] != 100){
                    $this->error("机构与讲师比例之和需为100");
                }
                if($teacher_and_share_action[0] + $teacher_and_share_action[1] != 100){
                    $this->error("讲师与分享者待审比例之和需为100");
                }

                $data['course_platform_and_school_action']  = t($_POST['course_platform_and_school_action']);
                $data['course_school_and_mschool']          = t($_POST['course_school_and_mschool']);
                $data['course_school_and_teacher']          = t($_POST['course_school_and_teacher']);
                $data['course_teacher_and_share_action']    = t($_POST['course_teacher_and_share_action']);
                $data['uid']    = $this->mid;
                $data['mhm_id'] = is_school($this->mid);
                $data['ctime']  = time();
                $data['course_status'] = 0;

                $id = M('school_divideinto')->where(['mhm_id'=>is_school($this->mid)])->getField('id');
                if($id){
                    $data['id'] = $id;
                    $res = M('school_divideinto')->save($data);
                }else{
                    $res = M('school_divideinto')->add($data);
                }

                if(!$res){
                    $this->error("操作失败");
                }

                $this->assign('jumpUrl',U('school/AdminSchoolDivideIntoConfig/divideIntoCourseConfig'));
                $this->success("操作成功");
            } else {
                $_REQUEST['tabHash'] = 'divideIntoCourseConfig';

                //$this->onsubmit = 'admin.checkLive(this)';

                $this->pageKeyList = ['course_platform_and_school','course_platform_and_school_action','course_school_and_mschool',
                    'course_school_and_teacher','course_teacher_and_share','course_teacher_and_share_action','tips'];
                $this->notEmpty = ['course_platform_and_school_action','course_school_and_mschool','course_school_and_teacher','course_teacher_and_share_action'];

                $data = M('school_divideinto')->where(['mhm_id'=>is_school($this->mid)])->find();
                if($data['course_status'] == 2){
                    $info = "您所配置的比例不合适，已被平台驳回。";
                }
                $data['tips'] = "<span style='color: #ba0000'>{$info}以上比例未配置采用平台默认比例</span>";

                $this->savePostUrl = U('school/AdminSchoolDivideIntoConfig/divideIntoCourseConfig');
                $this->displayConfig($data);
            }
        }
    }

    /**
     * 直播分成比例配置
     */
    public function divideIntoLiveAdminConfig(){
        if(is_admin($this->mid)){
            $_REQUEST['tabHash'] = 'divideIntoLiveConfig';

            $this->pageKeyList = array('id','uid','mhm_id','live_platform_and_school','live_platform_and_school_action',
                'live_school_and_mschool','live_school_and_teacher','live_teacher_and_share','live_teacher_and_share_action',
                'status','ctime','DOACTION');

            $this->pageButton[] = array('title' => "待审", 'onclick' => "admin.jumpDivideInto('Live')");

            $list = $this->data_list('live',$fun = $_GET['fun']);

            $this->displayList($list);
        }
    }

    /**
     * 直播分成比例配置
     */
    public function divideIntoLiveConfig(){
        if(is_school($this->mid)){
            if( isset($_POST) ) {

                $platform_and_school_action = array_filter(explode(':', t($_POST['live_platform_and_school_action'])));
                $school_and_mschool = array_filter(explode(':', t($_POST['live_school_and_mschool'])));
                $school_and_teacher = array_filter(explode(':', t($_POST['live_school_and_teacher'])));
                $teacher_and_share_action = array_filter(explode(':', t($_POST['live_teacher_and_share_action'])));

                if($platform_and_school_action[0] + $platform_and_school_action[1] != 100){
                    $this->error("平台与机构待审比例之和需为100");
                }
                if($school_and_mschool[0] + $school_and_mschool[1] != 100){
                    $this->error("机构与销课机构比例之和需为100");
                }
                if($school_and_teacher[0] + $school_and_teacher[1] != 100){
                    $this->error("机构与讲师比例之和需为100");
                }
                if($teacher_and_share_action[0] + $teacher_and_share_action[1] != 100){
                    $this->error("讲师与分享者待审比例之和需为100");
                }

                $data['live_platform_and_school_action']  = t($_POST['live_platform_and_school_action']);
                $data['live_school_and_mschool']          = t($_POST['live_school_and_mschool']);
                $data['live_school_and_teacher']          = t($_POST['live_school_and_teacher']);
                $data['live_teacher_and_share_action']    = t($_POST['live_teacher_and_share_action']);
                $data['uid']    = $this->mid;
                $data['mhm_id'] = is_school($this->mid);
                $data['ctime']  = time();
                $data['live_status'] = 0;

                $id = M('school_divideinto')->where(['mhm_id'=>is_school($this->mid)])->getField('id');
                if($id){
                    $data['id'] = $id;
                    $res = M('school_divideinto')->save($data);
                }else{
                    $res = M('school_divideinto')->add($data);
                }

                if(!$res){
                    $this->error("操作失败");
                }

                $this->assign('jumpUrl',U('school/AdminSchoolDivideIntoConfig/divideIntoLiveConfig'));
                $this->success("操作成功");
            } else {
                $_REQUEST['tabHash'] = 'divideIntoLiveConfig';

                //$this->onsubmit = 'admin.checkLive(this)';

                $this->pageKeyList = ['live_platform_and_school','live_platform_and_school_action','live_school_and_mschool',
                    'live_school_and_teacher','live_teacher_and_share','live_teacher_and_share_action','tips'];
                $this->notEmpty = ['live_platform_and_school_action','live_school_and_mschool','live_school_and_teacher','live_teacher_and_share_action'];

                $data = M('school_divideinto')->where(['mhm_id'=>is_school($this->mid)])->find();
                if($data['live_status'] == 2){
                    $info = "您所配置的比例不合适，已被平台驳回。";
                }
                $data['tips'] = "<span style='color: #ba0000'>{$info}以上比例未配置采用平台默认比例</span>";

                $this->savePostUrl = U('school/AdminSchoolDivideIntoConfig/divideIntoLiveConfig');
                $this->displayConfig($data);
            }
        }
    }

    /**
     * 班级分成比例配置
     */
    public function divideIntoAlbumAdminConfig(){

        if(is_admin($this->mid)){
            $_REQUEST['tabHash'] = 'divideIntoAlbumConfig';

            $this->pageKeyList = array('id','uid','mhm_id','album_platform_and_school','album_platform_and_school_action',
                'album_school_and_share','album_school_and_share_action','status','ctime','DOACTION');

            $this->pageButton[] = array('title' => "待审", 'onclick' => "admin.jumpDivideInto('Album')");

            $list = $this->data_list('album',$fun = $_GET['fun']);

            $this->displayList($list);
        }
    }

    /**
     * 班级分成比例配置
     */
    public function divideIntoAlbumConfig(){

        if(is_school($this->mid)){
            if( isset($_POST) ) {
                $platform_and_school_action = array_filter(explode(':', t($_POST['album_platform_and_school_action'])));
                //$school_and_share = array_filter(explode(':', t($_POST['album_school_and_share_action'])));

                if($platform_and_school_action[0] + $platform_and_school_action[1] != 100){
                    $this->error("平台与机构待审比例之和需为100");
                }
                /*if($school_and_share[0] + $school_and_share[1] != 100){
                    $this->error("机构与分享者待审比例之和需为100");
                }*/

                $data['album_platform_and_school_action'] = t($_POST['album_platform_and_school_action']);
                $data['album_school_and_share_action']  = t($_POST['album_school_and_share_action']);
                $data['uid']    = $this->mid;
                $data['mhm_id'] = is_school($this->mid);
                $data['ctime']  = time();
                $data['album_status'] = 0;

                $id = M('school_divideinto')->where(['mhm_id'=>is_school($this->mid)])->getField('id');
                if($id){
                    $data['id'] = $id;
                    $res = M('school_divideinto')->save($data);
                }else{
                    $res = M('school_divideinto')->add($data);
                }

                if(!$res){
                    $this->error("操作失败");
                }

                $this->assign('jumpUrl',U('school/AdminSchoolDivideIntoConfig/divideIntoAlbumConfig'));
                $this->success("操作成功");
            } else {
                $_REQUEST['tabHash'] = 'divideIntoAlbumConfig';

                //$this->onsubmit = 'admin.checkLive(this)';

                $this->pageKeyList = ['album_platform_and_school','album_platform_and_school_action',
                    'album_school_and_share','album_school_and_share_action','tips'];
                $this->notEmpty = ['album_platform_and_school_action','album_school_and_share_action'];

                $data = M('school_divideinto')->where(['mhm_id'=>is_school($this->mid)])->find();
                if($data['album_status'] == 2){
                    $info = "您所配置的比例不合适，已被平台驳回。";
                }
                $data['tips'] = "<span style='color: #ba0000'>{$info}以上比例未配置采用平台默认比例</span>";

                $this->savePostUrl = U('school/AdminSchoolDivideIntoConfig/divideIntoAlbumConfig');
                $this->displayConfig($data);
            }
        }
    }

    /**
     * 线下课分成比例配置
     */
    public function divideIntoCourseLineAdminConfig(){
        if(is_admin($this->mid)){
            $_REQUEST['tabHash'] = 'divideIntoCourseLineConfig';

            $this->pageKeyList = array('id','uid','mhm_id','course_line_platform_and_school','course_line_platform_and_school_action',
                'course_line_school_and_teacher','course_line_teacher_and_share','course_line_teacher_and_share_action','status','ctime','DOACTION');

            $this->pageButton[] = array('title' => "待审", 'onclick' => "admin.jumpDivideInto('CourseLine')");

            $list = $this->data_list('course_line',$fun = $_GET['fun']);

            $this->displayList($list);
        }
    }

    /**
     * 线下课分成比例配置
     */
    public function divideIntoCourseLineConfig(){
        if(is_school($this->mid)){
            if( isset($_POST) ) {

                $platform_and_school_action = array_filter(explode(':', t($_POST['course_line_platform_and_school_action'])));
                $school_and_teacher = array_filter(explode(':', t($_POST['course_line_school_and_teacher'])));
                $teacher_and_share_action = array_filter(explode(':', t($_POST['course_line_teacher_and_share_action'])));

                if($platform_and_school_action[0] + $platform_and_school_action[1] != 100){
                    $this->error("平台与机构待审比例之和需为100");
                }
                if($school_and_teacher[0] + $school_and_teacher[1] != 100){
                    $this->error("机构与讲师比例之和需为100");
                }
                if($teacher_and_share_action[0] + $teacher_and_share_action[1] != 100){
                    $this->error("讲师与分享者待审核比例之和需为100");
                }

                $data['course_line_platform_and_school_action'] = t($_POST['course_line_platform_and_school_action']);
                $data['course_line_school_and_teacher']  = t($_POST['course_line_school_and_teacher']);
                $data['course_line_teacher_and_share_action']  = t($_POST['course_line_teacher_and_share_action']);
                $data['uid']    = $this->mid;
                $data['mhm_id'] = is_school($this->mid);
                $data['ctime']  = time();
                $data['course_line_status'] = 0;

                $id = M('school_divideinto')->where(['mhm_id'=>is_school($this->mid)])->getField('id');
                if($id){
                    $data['id'] = $id;
                    $res = M('school_divideinto')->save($data);
                }else{
                    $res = M('school_divideinto')->add($data);
                }

                if(!$res){
                    $this->error("操作失败");
                }

                $this->assign('jumpUrl',U('school/AdminSchoolDivideIntoConfig/divideIntoCourseLineConfig'));
                $this->success("操作成功");
            } else {
                $_REQUEST['tabHash'] = 'divideIntoCourseLineConfig';

                //$this->onsubmit = 'admin.checkLive(this)';

                $this->pageKeyList = ['course_line_platform_and_school','course_line_platform_and_school_action',
                    'course_line_school_and_teacher','course_line_teacher_and_share','course_line_teacher_and_share_action','tips'];
                $this->notEmpty = ['course_line_platform_and_school_action','course_line_school_and_teacher','course_line_teacher_and_share_action'];

                $data = M('school_divideinto')->where(['mhm_id'=>is_school($this->mid)])->find();
                if($data['course_line_status'] == 2){
                    $info = "您所配置的比例不合适，已被平台驳回。";
                }
                $data['tips'] = "<span style='color: #ba0000'>{$info}以上比例未配置采用平台默认比例</span>";

                $this->savePostUrl = U('school/AdminSchoolDivideIntoConfig/divideIntoCourseLineConfig');
                $this->displayConfig($data);
            }
        }
    }

    private function data_list($type,$fun){
        if($fun){
            if($type == 'course' || $type == 'live' ){
                $map = "{$type}_status=0 and (({$type}_platform_and_school_action!={$type}_platform_and_school AND {$type}_platform_and_school_action != '') or ({$type}_teacher_and_share_action!={$type}_teacher_and_share AND {$type}_teacher_and_share_action != ''))";
            }elseif($type == 'album'){
                $map = "{$type}_status=0 and (({$type}_platform_and_school_action!={$type}_platform_and_school AND {$type}_platform_and_school_action != '') or ({$type}_school_and_share_action!={$type}_school_and_share AND {$type}_school_and_share_action != ''))";
            }elseif($type == 'course_line'){
                $map = "{$type}_status=0 and (({$type}_platform_and_school_action!={$type}_platform_and_school AND {$type}_platform_and_school_action != '') or ({$type}_teacher_and_share_action!={$type}_teacher_and_share AND {$type}_teacher_and_share_action != ''))";
            }
        } else {
            $map = "{$type}_status=1";
        }

        $list = M('school_divideinto')->where($map)->findPage();

        foreach($list['data'] as &$val){
            $val['uid'] = getUserSpace($val['uid'], null, '_blank');
            $val['ctime'] = date('Y-m-d H:i:s', $val["ctime"]);
            $school_info = model('School')->where(['id'=>$val['mhm_id']])->field('id,doadmin,title')->find();
            if(!$school_info['doadmin']){
                $url = U('school/School/index', array('id' => $school_info['id']));
            }else{
                $url = getDomain($school_info['doadmin']);
            }
            $val['mhm_id'] = getQuickLink($url,$school_info['title'],"未知机构");

            if($val["{$type}_status"] == 0){
                $val["status"] = '<span style="color: #7f7f7c">提交未审核</span>';

                $val['DOACTION'] =  '<a href="javascript:;" onclick="admin.doaction('.$val['id'].',\'adopt\',\''.$type.'\')">通过</a>';
                $val['DOACTION'] .=  ' | <a href="javascript:;" onclick="admin.doaction('.$val['id'].',\'reject\',\''.$type.'\')">驳回</a>';

            }elseif($val["{$type}_status"] == 1){
                $val["status"] = '<span style="color: green">已审核</span>';
            }else{
                $val["status"] = '<span style="color: #ba0000">审核未通过</span>';
            }
        }

        return $list;
    }

    public function doaction(){
        $id = intval($_POST['id']);
        $status = t($_POST['status']);
        $type = t($_POST['type']);

        if(!$id || !$status || !$type){
            $this->ajaxReturn(null,'参数错误',0);
        }
        $data = M('school_divideinto')->where(['id'=>$id])->find();

        $save['id'] = $data['id'];

        if($status == 'adopt'){
            $save["{$type}_status"] = 1;
            if($data["{$type}_platform_and_school_action"]){
                $save["{$type}_platform_and_school"] = $data["{$type}_platform_and_school_action"];
                $save["{$type}_platform_and_school_action"] = "";
            }

            if($type == 'course' || $type == 'live' || $type == 'course_line'){
                if($data["{$type}_teacher_and_share_action"]){
                    $save["{$type}_teacher_and_share"] = $data["{$type}_teacher_and_share_action"];
                    $save["{$type}_teacher_and_share_action"] = "";
                }
            }elseif($type == 'album'){
                if($data["{$type}_school_and_share_action"]){
                    $save["{$type}_school_and_share"] = $data["{$type}_school_and_share_action"];
                    $save["{$type}_school_and_share_action"] = "";
                }
            }

            $res =  M('school_divideinto')->save($save);
        }else{//reject
            $save["{$type}_status"] = 2;
            $res =  M('school_divideinto')->save($save);
        }
        if($res){
            if($type == 'course_line'){
                $type = 'courseLine';
            }
            $type = ucfirst($type);
            $this->assign('jumpUrl',U("school/AdminSchoolDivideIntoConfig/divideInto{$type}Config"));
            $this->ajaxReturn(null,'操作成功',1);
        } else {
            $this->ajaxReturn(null,'操作失败',0);
        }
    }

}