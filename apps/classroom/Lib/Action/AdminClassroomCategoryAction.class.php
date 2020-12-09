<?php
/**
 * 云课堂后台配置
 * 分类管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminClassroomCategoryAction extends AdministratorAction
{
	/**
	 * 初始化，配置内容标题
	 * @return void
	 */
	public function _initialize()
	{
		// 管理标题项目
		$this->pageTitle['index'] 			    = '（点播、直播、线下课）分类';
		// $this->pageTitle['liveCategory'] 	= '课时标题';
		$this->pageTitle['packageCategory'] 	= '班级分类';
		$this->pageTitle['libraryCategory']     = '文库分类';
		$this->pageTitle['teacherCategory']     = '讲师分类';
		$this->pageTitle['schoolCategory']      = '机构分类';

		// 管理分页项目
		$this->pageTab[] = array('title'=>'（点播、直播、线下课）分类','tabHash'=>'index','url'=>U('classroom/AdminClassroomCategory/index'));
		// $this->pageTab[] = array('title'=>'课时标题','tabHash'=>'liveCategory','url'=>U('classroom/AdminClassroomCategory/liveCategory'));
		$this->pageTab[] = array('title'=>'班级分类','tabHash'=>'packageCategory','url'=>U('classroom/AdminClassroomCategory/packageCategory'));
		$this->pageTab[] = array('title'=>'文库分类','tabHash'=>'libraryCategory','url'=>U('classroom/AdminClassroomCategory/libraryCategory'));
		$this->pageTab[] = array('title'=>'讲师分类','tabHash'=>'teacherCategory','url'=>U('classroom/AdminClassroomCategory/teacherCategory'));
		$this->pageTab[] = array('title'=>'机构分类','tabHash'=>'schoolCategory','url'=>U('classroom/AdminClassroomCategory/schoolCategory'));

		parent::_initialize();
	}
	
	//课程(直播、点播)分类列表
	public function index(){
        $treeData = model ( 'CategoryTree' )->setTable ( 'zy_currency_category' )->getNetworkList ();

        $this->displayCoverTree ( $treeData, 'zy_currency_category',3);
	}
	//班级分类列表
	public function packageCategory(){
        $treeData = model ( 'CategoryTree' )->setTable ( 'zy_package_category' )->getNetworkList ();
        $this->displayTree ( $treeData, 'zy_package_category',1);
	}
	//文库分类列表
	public function libraryCategory(){
		$treeData = model ( 'CategoryTree' )->setTable ( 'doc_category' )->getNetworkList ();
		$this->displayTree ( $treeData, 'doc_category',3);
	}

	//讲师分类列表
	public function teacherCategory(){
        $treeData = model ( 'CategoryTree' )->setTable ( 'zy_teacher_category' )->getNetworkList ();
      
        $this->displayTree ( $treeData, 'zy_teacher_category',3);
	}

	//机构分类列表
	public function schoolCategory(){
		$treeData = model ( 'CategoryTree' )->setTable ( 'school_category' )->getNetworkList ();

		$this->displayTree ( $treeData, 'school_category',3);
	}

	public function liveCategory(){

		// $list=M('zy_live_cetegory')->where('pid = 0')->order('id ASC')->findAll();
		$list=$this->MakeTree(0,1,0);
	
		$this->assign('list',$list);
		$this->display();
	}

	public function MakeTree($pid, $level = 0, $isApp)
    {
        $result = M('zy_live_category')->where('pid='.$pid)->order('sort ASC')->findAll();
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
                isset($value['is_del']) && $list[$id]['is_del'] = $value['is_del'];
                $list[$id]['title']                             = $value['title'];
                $list[$id]['level']                             = $level;
                $child                                          = $this->MakeTree($value['id'], $level + 1 ,$isApp) ?: [];
                $child && $list[$id]['child']                   = $child;
            }
        }

        return $list;
    }

}