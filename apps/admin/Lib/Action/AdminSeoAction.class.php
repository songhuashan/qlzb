<?php
/**
 * seo管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun2.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminSeoAction extends AdministratorAction
{

    /**
     * 初始化，
     */
    public function _initialize() {

        $this->pageTitle['index']   = '列表';
        $this->pageTitle['addSeo']  = '添加';
        $this->pageTitle['editSeo'] = '编辑';

        $this->pageTab[] = array('title'=>'列表','tabHash'=>'index','url'=>U('admin/AdminSeo/index'));
        $this->pageTab[] = array('title'=>'添加','tabHash'=>'addSeo','url'=>U('admin/AdminSeo/addSeo'));

        parent::_initialize();
    }

    /**
     * 直播课堂列表
     */
    public function index(){
        $_REQUEST['tabHash'] = 'index';

        $this->pageKeyList = array('id','uid','name','title','uri','keywords','description','ctime','DOACTION');
        //搜索字段
        $this->searchKey = array('id', 'name', 'title','uri','keywords');
        $this->pageButton[] = array('title' => "搜索", 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => "禁用", 'onclick' => "admin.doaction('','ColseLive',9)");

        // 数据的格式化
        //$map['is_del'] = ['neq',2];
        $list = $this->_getSeoList('index',20,'id desc',$map);

        $this->displayList($list);
    }

    /**
     * 创建课堂直播课堂
     */
    public function addSeo(){
        if( isset($_POST) ) {
            if(empty($_POST['name'])){$this->error("页面名称不能为空");}
            if(empty($_POST['title'])){$this->error("标题不能为空");}
            if(empty($_POST['uri'])){$this->error("路径规则不能为空");}
            if(empty($_POST['keywords'])){$this->error("搜索关键描述不能为空");}

            $data['uid']         = $this->mid;
            $data['name']        = t($_POST['name']);
            $data['title']       = t($_POST['title']);
            $data['uri']         = t($_POST['uri']);
            $data['keywords']    = t($_POST['keywords']);
            $data['description'] = t($_POST['description']);
            $data['ctime']       = time();

            $res = M('seo')->add($data);

            $this->assign('jumpUrl',U('admin/AdminSeo/index'));

            if(!$res){
                $this->error("添加失败");
            }

            $this->success("添加成功");
        } else {
            $_REQUEST['tabHash'] = 'addSeo';

            //$this->onsubmit = 'admin.checkLive(this)';

            $this->pageKeyList = array('name','title','uri','keywords','description');

            $this->notEmpty = array('name','title','uri','keywords');

            $this->savePostUrl = U('admin/AdminSeo/addSeo');
            $this->displayConfig();
        }
    }
    /**
     * 编辑课堂直播课堂
     */
    public function editSeo(){
        if( isset($_POST) ) {
            if(empty($_POST['name'])){$this->error("页面名称不能为空");}
            if(empty($_POST['title'])){$this->error("标题不能为空");}
            if(empty($_POST['uri'])){$this->error("路径规则不能为空");}
            if(empty($_POST['keywords'])){$this->error("搜索关键描述不能为空");}

            $data['id']          = intval($_GET['id']);
            $data['uid']         = $this->mid;
            $data['name']        = t($_POST['name']);
            $data['title']       = t($_POST['title']);
            $data['uri']         = t($_POST['uri']);
            $data['keywords']    = t($_POST['keywords']);
            $data['description'] = t($_POST['description']);
            $data['ctime']       = time();

            $res = M('seo')->save($data);

            $this->assign('jumpUrl',U('admin/AdminSeo/index'));

            if(!$res){
                $this->error("编辑失败");
            }

            $this->success("编辑成功");
        } else {
            $_REQUEST['tabHash'] = 'editSeo';
            //$this->onsubmit = 'admin.checkLive(this)';

            $this->pageKeyList = array('name','title','uri','keywords','description');
            $this->notEmpty = array('name','title','uri','keywords');

            $seo_list = M('seo')->find(intval($_GET['id']));

            $this->savePostUrl = U('admin/AdminSeo/editSeo',['id'=>$_GET['id']]);
            $this->displayConfig($seo_list);
        }
    }

    /**
     * 解析直播课堂列表数据
     * @param integer $limit 结果集数目，默认为20
     * @param $order 排序
     * @return array 解析后的直播列表数据
     */
    private function _getSeoList($type,$limit,$order,$map) {
        if(isset($_POST)){

            $_POST['id'] && $map['id']          = intval($_POST['id']);
            $_POST['name'] && $map['name'] = array('like', '%' . t($_POST['name']) . '%');
            $_POST['title'] && $map['title'] = array('like', '%' . t($_POST['title']) . '%');
            $_POST['uri'] && $map['uri'] = array('like', '%' . t($_POST['uri']) . '%');
            $_POST['keywords'] && $map['keywords'] = array('like', '%' . t($_POST['keywords']) . '%');
        }

        $seo_list = M('seo')->where($map)->order($order)->findPage($limit);
        foreach($seo_list['data'] as $key => $val){
            $seo_list['data'][$key]['keywords'] = msubstr($val['keywords'],0,20);
            $seo_list['data'][$key]['description'] = msubstr($val['description'],0,20);
            $seo_list['data'][$key]['uid']      = getUserSpace($val['uid'], null, '_blank');
            $seo_list['data'][$key]['ctime']    = date('Y-m-d H:i:s',$val["ctime"]);

            //if($val['is_best'] == 0){
            //    $liveInfo['data'][$key]['is_best'] = "<p style='color: red;'>否</p>";
            //}else if($val['is_best'] == 1){
            //    $liveInfo['data'][$key]['is_best'] = "<p style='color: green;'>是</p>";
            //}

            $seo_list['data'][$key]['DOACTION'] .= '<a href="'.U('admin/AdminSeo/editSeo',array('id'=>$val['id'])).'">编辑</a> | ';
            $seo_list['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.doaction('.$val['id'].',\'DelSeo\''.',9)">彻底删除</a>';
        }
        return $seo_list;
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
        $map['type'] = $type;
        if(intval($_POST['status']) == 1){
            $data['is_del']=2;
        }else{
            $data['is_del']=1;
        }
        $result = M('zy_live_thirdparty')->where($map)->save($data);
        
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
        $map['type'] = $type;

        $result = M('zy_live_thirdparty') ->where($map)-> save(['is_del'=>0]);

        if(!$result){
            $this->ajaxReturn(null,'启用失败',0);
            return;
        }

        $this->ajaxReturn(null,'启用成功',1);
    }

    /**
     * 彻底删除直播间
     */
    public function doactionDelSeo(){
        $id = intval($_POST['id']);
        if(!$id){
            $this->ajaxReturn(null,'参数错误',0);
        }
        $result = M('seo')->where(['id'=>$id])->delete();

        if($result){
            $this->ajaxReturn(null,'彻底关闭成功',1);
        } else {
            $this->ajaxReturn(null,'彻底关闭失败',0);
        }
    }

}