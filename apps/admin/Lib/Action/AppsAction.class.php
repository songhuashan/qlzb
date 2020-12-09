<?php

tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
/**
 * 后台应用管理
 * 
 * @author jason
 */
class AppsAction extends AdministratorAction
{
	public $pageTitle = array(
							'index'			=> '已安装应用列表',
							'install'		=> '待安装应用列表',
			                'onLineApp'		=> '在线应用',
							'setCreditNode' => '积分节点设置',
							'setPermNode'	=> '权限节点设置',
							'setFeedNode'	=> '微博模板设置',
							);
	
	private $appStatus = array('0'=>'关闭','1'=>'开启');	//应用状态
	private $host_type_alias = array(0=>'本地应用',1=>'远程应用');	//托管状态
	private $RemoteAppURL = '';
	public function _initialize(){
		$this->pageTitle['index'] = L('PUBLIC_INSTALLED_APPLIST');
		$this->pageTitle['install'] = L('PUBLIC_UNINSTALLED_APPLIST');
		$this->pageTitle['setCreditNode'] = L('PUBLIC_POINTS_SETTING');
		$this->pageTitle['setPermNode'] = L('PUBLIC_AUTHORITY_SETTING');
		$this->pageTitle['setFeedNode'] = L('PUBLIC_WEIBO_TEMPLATE_SETTING');
		$this->appStatus[0] = L('PUBLIC_CLOSE');
		$this->appStatus[1] = L('PUBLIC_OPEN');
		$this->host_type_alias[0] = L('PUBLIC_LOCAL_APP');
		$this->host_type_alias[1] = L('PUBLIC_REMOTE_APP');
		$this->RemoteAppURL = C('TS_UPDATE_SITE');
		parent::_initialize();
	}
	
	//已安装的应用
	public function index(){
		$this->pageTab[] = array('title'=>'已安装应用','tabHash'=>'index','url'=>U('admin/Apps/index'));
		//列表key值 DOACTION表示操作
		$this->pageKeyList = array('app_id','app_name','app_alias','status','DOACTION');
		
		$listData = model('App')->findPage(20);	

		$inNav = $this->navList;
		

		foreach($listData['data'] as &$v){

			// $v['icon_url'] = empty($v['icon_url']) ? '<img src="'.APPS_URL.'/'.$v['app_name'].'/Appinfo/icon_app.png" >' : "<img src='{$v['icon_url']}'>";
			$v['icon_url'] = '<img src="'.APPS_URL.'/'.$v['app_name'].'/Appinfo/icon_app.png" >';
			!empty($v['author_homepage_url']) && $v['author_name'] = "<a href='{$v['author_homepage_url']}'>{$v['author_name']}</a>";
			$v['DOACTION'] = "<a href='".U('admin/Apps/preinstall',array('app_name'=>$v['app_name']))."'>".L('PUBLIC_EDIT')."</a>&nbsp;&nbsp;";
			$v['DOACTION'] .= $v['status'] == 0 
							? "<a href='javascript:admin.setAppStatus({$v['app_id']},1)'>".L('PUBLIC_OPEN')."</a>&nbsp;&nbsp;"
							: "<a href='javascript:admin.setAppStatus({$v['app_id']},0)'>".L('PUBLIC_CLOSE')."</a>&nbsp;&nbsp;";
			$v['DOACTION'] .= "<a href='".U('admin/Apps/uninstall',array('app_id'=>$v['app_id']))."'>".L('PUBLIC_SYSTEM_APP_UNLODING')."</a>";
			$v['status'] = $this->appStatus[$v['status']];	//语义化

		}

		$this->pageButton[] = array('title'=>L('PUBLIC_OPEN'),'onclick'=>"admin.setAppStatus('', 1)");
		$this->pageButton[] = array('title'=>L('PUBLIC_CLOSE'),'onclick'=>"admin.setAppStatus('', 0)");
		
		$this->_listpk = 'app_id';
		$this->displayList($listData);
	}
	
	//待安装的应用
	public function install(){
		$this->pageTab[] = array('title'=>'未安装应用','tabHash'=>'install','url'=>U('admin/Apps/install'));
		$this->pageKeyList = array('app_name','app_alias','description','host_type_alias','company_name','DOACTION');
		
		$listData['data']= model('App')->getUninstallList();
		
		foreach($listData['data'] as &$v){
			$v['host_type_alias'] = $this->host_type_alias[$v['host_type']];
			!empty($v['author_homepage_url']) && $v['author_name'] = "<a href='{$v['author_homepage_url']}'>{$v['author_name']}</a>";  
			$v['icon_url'] = empty($v['icon_url']) ? '<img src="'.APPS_URL.'/'.$v['app_name'].'/Appinfo/icon_app.png" >' : "<img src='{$v['icon_url']}'>";
			$v['DOACTION'] =  "<a href='".U('admin/Apps/preinstall',array('app_name'=>$v['app_name'],'install'=>1))."'>".L('PUBLIC_SYSTEM_APP_INSTALL')."</a>";	
		}
		$this->allSelected = false;
		$this->displayList($listData);
	}
	
	//安装 编辑 应用
	public function preinstall($app_name='', $install=''){
		if(empty($app_name)){
			$app_name = t($_GET['app_name']);
		}
		if(empty($install)){
			$install = t($_GET['install']);
		}
		if(empty($app_name))
			$this->error(L('PUBLIC_SYSTEM_APP_SELECTINSTALL'));

		$this->pageKeyList = array('app_id','app_name','app_alias','app_entry','description','status','host_type','icon_url','large_icon_url',
								'admin_entry','statistics_entry','company_name','display_order','version','api_key','secure_key','add_front_top','add_front_applist'
								);

		if(!empty($install)){
			$this->submitAlias = L('PUBLIC_SYSTEM_APP_INSTALL');
			$info = model('App')->__getAppInfo($app_name);
			$this->assign('pageTitle',L('PUBLIC_SYSTEM_APP_INSTALL'));
			!empty($info['admin_entry']) && $this->pageKeyList[] = 'add_tonav';
		}else{
			$map['app_name']  = $app_name;
			$info = model('App')->where($map)->find();
			$this->assign('pageTitle',L('PUBLIC_SYSTEM_APP_EDIT'));
		}

		$this->savePostUrl = U('admin/Apps/saveApp');
		$this->opt['status'] = $this->appStatus;
		$this->opt['host_type'] = $this->host_type_alias;
		$this->opt['add_tonav'] = array(0=>L('PUBLIC_SYSTEMD_FALSE'),1=>L('PUBLIC_SYSTEMD_TRUE'));
		$this->opt['add_front_top'] = array(0=>L('PUBLIC_SYSTEMD_FALSE'),1=>L('PUBLIC_SYSTEMD_TRUE'));
		$this->opt['add_front_applist'] = array(0=>L('PUBLIC_SYSTEMD_FALSE'),1=>L('PUBLIC_SYSTEMD_TRUE'));
		$this->notEmpty = array('app_name','app_alias','app_entry');
		$this->onsubmit = 'admin.checkAppInfo(this)';
		$this->displayConfig($info);
	}
	
	
	//安装保存应用
	public function saveApp(){
		//app_id 为空为安装 ，否则为修改
		if(empty($_POST['app_name']) || empty($_POST['app_alias']) || empty($_POST['app_entry'])){
			$this->error(L('PUBLIC_SYSTEM_APP_INSTALLERROR'));
		}
		
		
		$status = model('App')->saveApp($_POST);

		if($status === true){
			$log['app_name']  = $_POST['app_name'];
			if(!empty($_POST['app_id'])){
				$log['app_id'] 	  = $_POST['app_id'];
			}
			$log['app_alias'] = $_POST['app_alias'];
			$log['k']		  = L('PUBLIC_SYSTEM_APP_FILEDS');
			LogRecord('admin_extends', 'appManage', $log,true);
			
			if(!empty($_POST['admin_entry']) && $_POST['add_tonav'] == 1){
				$this->navList[$_POST['app_name']] = $_POST['admin_entry'];
				model('Xdata')->put('admin_nav:top',$this->navList);
			}
			if($_POST['app_name']=='tipoff') model('UserGroup')->cleanCache();
			$this->assign('jumpUrl',!empty($_POST['app_id']) ? U('admin/Apps/index'): U('admin/Apps/install'));
			$this->success(); 
		}else{
			$this->error($status);
		}
	}
	
	//卸载应用
	public function uninstall(){
		$app_id = intval($_GET['app_id']);
		if(empty($app_id)){
			$this->error(L('PUBLIC_ID_NOEXIST'));
		}
		$appInfo = model('App')->getAppById($app_id);
		$status = model('App')->uninstall($app_id);
		if($status === true){
			unset($this->navList[$appInfo['app_name']]);
			model('Xdata')->put('admin_nav:top',$this->navList);
			LogRecord('admin_extends', 'appUninstall', array('app_id'=>$app_id,'k1'=>L('PUBLIC_SYSTEM_APP_UNLODING')),true);
			$this->success();
		}else{
			$this->error($status);
		}
	}
    /**
     * 递归形成树形结构
     * @param integer $pid 父菜单ID
     * @param integer $level 等级
     * @return array 树形结构
     */
    private function _PermNodeMakeTree($pid = 0, $level = 0)
    {
        $result = M('permission_node')->where('pid='.$pid)->order('id ASC')->findAll();
        $list = [];
        if($result) {
            foreach($result as $key => $value) {
                $id = $value['id'];
                $list[$id]['id'] = $value['id'];
                $list[$id]['pid'] = $value['pid'];
                if($value['is_autocephaly'] == 1){
                    $list[$id]['is_autocephaly'] = 1;
                }
                $list[$id]['appname'] = $value['appname'];
                $list[$id]['title'] = $value['rulename'];
                $list[$id]['rule'] = $value['rule'];
                $list[$id]['note'] = $value['note'];
                $list[$id]['level'] = $level;
                $child = $this->_PermNodeMakeTree($value['id'], $level + 1)?:[];
                $child && $list[$id]['child'] = $child;
            }
        }

        return $list;
    }
	//权限节点设置
	public function setPermNode(){
        $tree =  $this->_PermNodeMakeTree();
        $this->assign('tree', $tree);
        $this->assign('level', 0);
        $this->assign('extra', '');
        $this->assign('limit', 10);
        $this->display('admin_perm_node');
	}

    /**
     * 添加权限菜单窗口API
     * @return void
     */
    public function addPermTreeCategory()
    {
        $pid = intval($_GET['id']);
        $limit = intval($_GET['limit']);
        $this->assign('pid', $pid);
        $this->assign('limit', $limit);
        $this->display('categoryBox');
    }

    /**
     * 编辑权限菜单窗口API
     * @return void
     */
    public function upPermTreeCategory()
    {
        $id = intval($_GET['id']);
        $limit = intval($_GET['limit']);

        $category = M('permission_node')->where(['id'=>$id])->find();

        $this->assign('pid', $id);
        $this->assign('limit', $limit);
        $this->assign('category', $category);
        $this->display('categoryBox');
    }

	public function editPermNode(){
		if(!empty($_GET['id'])){
			$info = D('permission_node')->find(intval($_GET['id']));
			$this->assign('pageTitle','编辑权限节点');
		}else{
			$this->submitAlias = L('PUBLIC_ADD');
			$this->assign('pageTitle',L('PUBLIC_SYSTEM_ADMINJUR_ADD'));
		}
		
		$this->pageKeyList = array('id','appname','appinfo','module','rule','ruleinfo');

		$this->opt['module'] = array('normal'=>L('PUBLIC_SYSTEM_NORMAL_USER'),'admin'=>L('PUBLIC_SYSTEM_ADMIN_USER'));

		$this->savePostUrl = U('admin/Apps/savePermNode');
		
		$this->notEmpty = array('appname','module','rule');
		$this->onsubmit = 'admin.checkPermNode(this)';

		$this->displayConfig($info);
	}

	public function savePermNode(){
		if($_POST){
            $data['rulename']       = t($_POST['rulename']);
            $data['appname']        = t($_POST['appname']);
			$data['rule']           = t($_POST['rule']);
			$data['note']	        = t($_POST['note']);
			$data['is_autocephaly'] = t($_POST['is_autocephaly']);

			if(!$data['appname'] || !$data['rulename'] || !$data['rule']  ){
                $this->mzError('应用名称、菜单名称、权限规则不能有空值');
			}

            if($_POST['id']){
				$map['id']      = intval($_POST['id']);
				$act            ='editPermNode';
                $info           = "编辑";

                $res = D('permission_node')->where($map)->save($data);
			}else{
				$act            = 'addPermNode';
                $info           = "添加";
                $data['pid']    = t($_POST['pid']);

                $res = D('permission_node')->add($data);
			}

            if($res !== false){
                $d['log'] = var_export([$data],true);
                $d['k']   = $info."权限节点";
                LogRecord('admin_extends',$act,$d,true);
                $this->mzSuccess("{$info}成功");
            }else{
                $this->mzError("{$info}失败");
            }
		}
	}

	public function delPermNode(){
        $map['id'] = intval($_POST['id']);

        $data = D('permission_node')->where($map)->findAll();

        $res = D('permission_node')->where($map)->delete();
        if($res){
            $d['log'] = var_export($data,true);
            $d['k']   = L('PUBLIC_SYSTEM_ADMINJUR_DELETE');
            LogRecord('admin_extends','delPermNode',$d,true);
            $this->mzSuccess("删除成功");
        }else{
            $this->mzError("删除失败");
        }

	}
	
	//动态节点设置
	public function setAppStatus(){
		$app_id = $_POST['app_id'];
		$map['app_id'] = is_array($app_id) ? array('in',$app_id):intval($app_id);
		$data['status'] = intval($_POST['status']);
		if($data = D('App')->where($map)->save($data)){
			
			// 设置缓存
			$appname = D('App')->where($map)->getField('app_name');
			model ( 'Cache' )->set ( 'Appinfo_' . $appname, null );
			model('App')->cleanCache();
			$return['status'] = 1;
			$return['data']   = '设置成功！';
			echo json_encode($return);exit();
		}else{
			$return['status'] = 0;
			$return['data']   = '设置失败！';
			echo json_encode($return);exit();
		}
	}

	//在线应用
/*	function onLineApp(){
		$url = $this->RemoteAppURL.'/index.php?app=public&mod=Tool&act=getAppsOnLineInfo';
		$list = file_get_contents($url);
		$list = json_decode($list, true);

		$loadlist = Model('App')->field('app_name,version')->findAll();
		foreach ($loadlist as $v){
			$appVersion[$v['app_name']] = $v['version'];
			$appName[$v['app_name']] = $v['app_name'];
		}
		unset($loadlist);
		
		foreach ($list as $k=>&$v){
			if(in_array($v['app_name'], $appName)){
				if($v['version']==$appVersion[$v['app_name']]){
					unset($list[$k]);  //已安装并且版本号一样不再显示
				}else{
					$v['isUpdate'] = true; //安装过的应用有更新版本
				}
			}else{
				$v['isUpdate'] = false; //未安装过的应用
			}
		}
		
		$dirIsWritable = is_writable(APPS_PATH);
		$type = array('1'=>'模板','2'=>'插件','3'=>'应用'); //插件类型,1:模板皮肤;2:插件;3:应用
		
		foreach ($list as &$vo){
			$vo['title'] = '<a href="'.$this->RemoteAppURL.'/index.php?app=develop&mod=Index&act=detail&id='.$vo['develop_id'].'" target="_blank">'.$vo['title'].'</a>';
			$vo['type'] = $type[$vo['type']];
			$vo['user'] = '<a href="'.$this->RemoteAppURL.'/index.php?app=public&mod=Profile&act=index&uid='.$vo['uid'].'" target="_blank">'.$vo['user'].'</a>';
			unset($vo['uid']);
			$vo['option'] = '<a href="'.$this->RemoteAppURL.'/index.php?app=develop&mod=Index&act=download&id='.$vo['develop_id'].'">下载</a> ';
			
			if($dirIsWritable){
				if($vo['isUpdate']){
					$vo['option'] .= '<a href="'.U('admin/Apps/downloadAndInstall', array('develop_id'=>$vo['develop_id'])).'">一键更新</a>';
				}else{
					$vo['option'] .= '<a href="'.U('admin/Apps/downloadAndInstall', array('develop_id'=>$vo['develop_id'])).'">一键安装</a>';
				}
			}
			
		}
		//dump($list);
		
		$this->pageKeyList = array('title','type','download_count','user','option');
		
		$listData['data']= $list;
		
		$this->allSelected = false;
		$this->displayList($listData);
	}*/
	
	/**
	 * 在线应用
	 * @return void
	 */
	public function onLineApp () {
		// 获取站点域名
		$host = str_replace('http://', '', SITE_URL);
		// 验证站点是否登录
		$iframeUrl = $this->RemoteAppURL.'/index.php?app=develop&mod=Public&act=getAppStoreHome&h='.base64_encode($host);
		$this->assign('iframeUrl', $iframeUrl);
		$this->display();
	}
	
	/**
	 * 一键安装接口
	 * @return void
	 */
	public function downloadAndInstall () {
		header("content-Type: text/html; charset=utf-8");
		// 获取下载地址
		$develop_id = intval($_GET['develop_id']);
		$url = $this->RemoteAppURL.'/index.php?app=public&mod=Tool&act=downloadApp&develop_id='.$develop_id;
		$info = file_get_contents($url);
		$info = json_decode($info, true);
		// 载入下载类
		tsload(ADDON_PATH.'/library/Update.class.php');
		$updateClass = new Update();
		//从服务器端下载应用到本地
		$res = $updateClass->downloadFile($info['packageURL']);
		if ($res != 1) {
			$this->error('下载应用失败，请确认网络是否正常');
			exit;
		}
		// 压缩
		$package = explode('/', $info['file']['filename']);
		$packageName = array_pop($package);
		$targetDir = $updateClass->downloadPath.'unzip';
		// 创建目录unzip
		if (!is_dir($targetDir)) {
			@mkdir($targetDir, 0777);
		}
		$res = $updateClass->unzipPackage($packageName, $targetDir);
		if ($res != 1) {
			$this->error('下载应用解压失败');
			exit;
		}
		// 覆盖代码
		switch ($info['type']) {
			case 3:
				 // 应用
				$res = $updateClass->overWrittenFile(APPS_PATH);
				break;
			case 2:
				// 插件
				$res = $updateClass->overWrittenFile(ADDON_PATH.'/plugin');
				break;
			case 1:
				// 皮肤
				$res = $updateClass->overWrittenFile(ADDON_PATH.'/theme');
				break;
		}
		// 安装
		switch ($info['type']) {
			case 3:
				// 应用
				U('admin/Apps/preinstall', array('app_name'=>$info['app_name'],'install'=>1), true);
				break;
			case 2:
				// 插件
				U('admin/Addons/index', array('install'=>1), true);
				break;
			case 1:
				U('admin/Apps/onLineApp', true);
				break;
		}		
	}
}