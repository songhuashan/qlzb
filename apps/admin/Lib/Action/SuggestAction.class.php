<?php
/**
 * 反馈管理
 */
// 加载后台控制器
tsload ( APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php' );
class SuggestAction extends AdministratorAction {

	/**
	 * 初始化，配置内容标题
	 * @return void
	 */
	public function _initialize(){
        parent::_initialize();
    }

    /**
     * 意见反馈列表
     * @return void
     */
    public function index(){
        $this->pageTab[]    = array('title'=>'列表','tabHash'=>'index','url'=>U('admin/Suggest/index'));
        //页面配置
        $this->pageKeyList = array('id','uid','content','ctime','DOACTION');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delSuggest()");

        $this->searchKey = array('id','uid','content');

        $map = array();
        if(!empty($_POST['id'])){
            $map['id'] = t($_POST['id']);
        }
        if(!empty($_POST['uid'])){
            $_POST['uid'] = t($_POST['uid']);
            $map['uid']   = array('in', $_POST['uid']);
        }
        if(!empty($_POST['content'])){
            $_POST['content'] = htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8');
            $map['content'] = array('like', "%{$_POST['content']}%");
        }
        $_POST ['uid'] && $map ['uid'] = array('in', (string)$_POST ['uid']);
        //数据列表
        $map['type'] = 0;
        $listData = M('suggest')->where($map)->order('ctime DESC,id DESC')->findPage(20);
        foreach($listData['data'] as $key=>$val){
            $val['ctime']    = friendlyDate($val['ctime']);
            $val['uid']      = $val['uid']?getUserSpace($val['uid'], null, '_blank'):'游客';
            $val['DOACTION'] = '<a href="javascript:;" onclick="admin.delSuggest('.$val['id'].');">彻底删除</a>';
            $val['content']  = '<div style="width:500px">'.$val['content'].'</div>';
            $listData['data'][$key] = $val;
        }

        $this->displayList($listData);
    }

    /**
     * 删除意见反馈
     * @return void
     */
    public function delSuggest(){
        if(is_array($_POST['id'])){
            $_POST['id'] = implode(',', $_POST['id']);
        }
        $id = "'".str_replace(array("'", ','), array('', "','"), $_POST['id'])."'";
        $res = M('suggest')->where("id IN($id)")->delete();
        if($res){
            $this->ajaxReturn(null,"删除成功",1);
        }else{
            $this->ajaxReturn(null,"删除失败",0);
        }
    }
}