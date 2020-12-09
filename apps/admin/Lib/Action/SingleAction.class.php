<?php
/**
 * 单页面管理
 */
// 加载后台控制器
tsload ( APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php' );
class SingleAction extends AdministratorAction {	

	/**
	 * 初始化，配置内容标题
	 * @return void
	 */
	public function _initialize(){
		parent::_initialize();
		$this->pageTab[] = array( 'title' =>'列表', 'tabHash' => 'index', 'url' => U('admin/single/index') );
		$this->pageTab[] = array( 'title' =>'添加', 'tabHash' => 'add', 'url' => U('admin/single/add') );
		//$this->pageTab[] = array( 'title' =>'分类', 'tabHash' => 'cate', 'url' => U('admin/single/cate') );
	}
	
	/**
	 * 单页列表
	 */
	public function index(){
		 $this->pageTitle ['index'] = '列表';
		 $this->pageKeyList = array ('id','title','key' ,'action');
		 $this->pageButton [] = array ( 'title' =>'删除', 'onclick' => "admin.delSingles()" );
		 $list = M('single')->where('is_del=0')->findPage(10);
         //获取单页分类
         //$catelist = model('Single')->getCate();
		 foreach($list['data'] as $k => &$v){
			 //$v['cate']   = $catelist[$v['cate_id']];
			 $v['title'] = '<a target="_blank" href="'.U('public/Single/info',array('id'=>$v['id'])).'">'.$v['title'].'</a>';
			 $v['action'] = '<a href="'.U('admin/Single/edit',array('tabHash'=>'edit','id'=>$v['id'])).'">编辑</a> - ';
			 $v['action'] .= '<a href="javascript:void(0)" onclick="admin.delSingle(\''.$v['id'].'\')">删除</a>';
				
	    }
		$this->displayList($list);
	}
	
	/**
	 * 添加单页
	 */
	public function add(){
		$this->pageTitle ['add'] = '添加';
		$this->pageKeyList = array ('title','key','text');
		if($_SERVER['REQUEST_METHOD'] == 'POST'){//如果是post表单提交
            $data['title']       = t( $_POST['title'] );
            $data['key']         = t( $_POST['key'] );
            $data['text']        = $_POST['text'];

            if( empty($data['title']) || empty($data['key'])) {
            	$this->error('请输入标题和唯一标识');
            }
            if( M('single')->where( array('key'=>$data['key']) )->find() ) {
            	$this->error('唯一标识已经存在');
            }
            if( $rst = M('single')->add($data)){
                //LogRecord('admin_content', 'addPage', array('ids'=>$rst) , true);
                $this->assign('jumpUrl' , U('admin/Single/index'));
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
            exit;
        }
        //$this->opt['cate_id'] = model('Single')->getCate();
        $this->savePostUrl = U('admin/Single/add');
        $this->notEmpty    = array('title','key');
       // $this->onsubmit = 'admin.addPageSubmitCheck(this)';
        $this->displayConfig();
	}
	
	/**
	 * 修改单页
	 */
	public function edit(){
	    $_REQUEST ['tabHash'] = 'edit';
	    $this->_top();
	    $this->pageTitle ['edit'] = '编辑单页';
	    $this->pageKeyList = array ('title','key','text');
	    $id = intval( $_GET['id'] );
        if($id && $_SERVER['REQUEST_METHOD'] == 'POST'){//如果是post表单提交
            $data['title']       = t( $_POST['title'] );
            $data['key']         = t( $_POST['key'] );
            $data['text']        = $_POST['text'];

            if( M('single')->where( array('key'=>$data['key']) )->find() ) {
            	$this->error('唯一标识已经存在');
            }
            if (M('single')->where('id=' . $id)->save($data) ){
                //LogRecord('admin_content', 'editPage', array('ids'=>$id) , true);
                $this->assign('jumpUrl',U('admin/Single/index'));
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }
        //$this->opt['cate_id'] = model('Single')->getCate();
        $this->savePostUrl = U('admin/Single/edit' , array( 'id'=>$id ));
        $this->notEmpty    = array('title','key');
        //$this->onsubmit = 'admin.addPageSubmitCheck(this)';
        $res = M('single')->where('id=' . $id)->find();
        $this->displayConfig ( $res );
	}
	
	/**
	 * 删除单页
	 */
	public function del(){
		$id = $_POST['ids'];
		if( is_numeric($id) ) {
			$map['id'] = intval($id);
		} else {
			$map['id'] = array('in' , $id);
		}
		if(M('single')->where($map)->setField('is_del' , 1)){
			//LogRecord('admin_content', 'delPage', array('ids'=>$id) , true);
			$return['status'] = 1;
			$return['data'] = '删除成功';
		}else{
			$return['status'] = 0;
			$return['data'] = '删除失败';
		}
		echo json_encode($return);exit();
	}
	
	private function _top(){
	  	
	}
	
}