<?php
/**
 * 后台，用户管理控制器
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
// 加载后台控制器
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminUserAction extends AdministratorAction
{

    public $pageTitle = array();

    /**
     * 初始化，初始化页面表头信息，用于双语
     */
    public function _initialize()
    {
        $this->pageTitle['index'] = L('PUBLIC_USER_MANAGEMENT');
        $this->pageTitle['pending'] = L('PUBLIC_PENDING_LIST');
        $this->pageTitle['profile'] = L('PUBLIC_PROFILE_SETTING');
        $this->pageTitle['profileCategory'] = L('PUBLIC_PROFILE_SETTING');
        $this->pageTitle['dellist'] = L('PUBLIC_DISABLE_LIST');
        $this->pageTitle['online'] = '在线用户列表';
        $this->pageTitle['addUser'] = L('PUBLIC_ADD_USER_INFO');
        $this->pageTitle['editUser'] = L('PUBLIC_EDIT_USER');
        $this->pageTitle['addProfileField'] = L('PUBLIC_ADD_FIELD');
        $this->pageTitle['editProfileField'] = L('PUBLIC_EDIT_FIELD');
        $this->pageTitle['addProfileCategory'] = L('PUBLIC_ADD_FIELD_CLASSIFICATION');
        $this->pageTitle['editProfileCategory'] = L('PUBLIC_EDITCATEOGRY');
        $this->pageTitle['verify'] = '待认证用户';

        $this->pageTitle['verified'] = '已认证用户';

        $this->pageTitle['addVerify'] = '添加认证';
        $this->pageTitle['category'] = '推荐标签';
        $this->pageTitle['verifyCategory'] = '认证分类';
        $this->pageTitle['verifyConfig'] = '认证配置';
        $this->pageTitle['official'] = '官方用户配置';
        $this->pageTitle['officialCategory'] = '官方用户分类';
        $this->pageTitle['officialList'] = '官方用户列表';
        $this->pageTitle['officialAddUser'] = '添加官方用户';
        $this->pageTitle['findPeopleConfig'] = '全局配置';
        $this->user = model("UserGroupLink")->where("uid=" . $this->mid)->find();
        parent::_initialize();
    }

    /**
     * 用户管理 - 用户列表
     */
    public function index()
    {
        $_REQUEST['tabHash'] = 'index';
        // 初始化用户列表管理菜单
        $this->_initUserListAdminMenu('index');
        $this->pageTitle['index']       = '列表';
        // 数据的格式化与listKey保持一致

        $listData = $this->_getUserList('20', [], 'index');
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => L('搜索'), 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => L('导出'), 'onclick' => "admin.UserexportUser()");

        $this->displayList($listData);
    }

    /**
     * 用户管理 - 待审列表
     */
    public function pending()
    {
        $_REQUEST['tabHash'] = 'pending';
        // 初始化审核列表管理菜单
        $this->_initUserListAdminMenu('pending');
        // 数据的格式化与listKey保持一致
        $listData = $this->_getUserList(20, array('is_audit' => 0, 'is_del' => '0'), 'pending');
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_USER'), 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => L('PUBLIC_AUDIT_USER_SUCCESS'), 'onclick' => "admin.auditUser('',1)");

        $this->displayList($listData);
    }

    function importUser()
    {
        $_REQUEST['tabHash'] = 'importUser';
        // 初始化禁用列表管理菜单
        $this->_initUserListAdminMenu('importUser');
        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_USER'), 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => L('PUBLIC_AUDIT_USER_SUCCESS'), 'onclick' => "admin.auditUser('',1)");
        $this->display();
    }

    function doImportUser()
    {
        $insert_time = date('Y-m-d H:i:s', time());
        $dest_folder = "data/upload/";
        if (!file_exists($dest_folder)) {
            mkdir($dest_folder);
        }
        $tmp_name = $_FILES["file"]["tmp_name"];
        $name = $_FILES["file"]["name"];
        $newTime = date('Ymd_His', time());
        $img = strtolower($name);
        $fileType = substr($img, strripos($img, '.') + 1);
        $uploadfile = $dest_folder . $newTime . '.' . $fileType;
        move_uploaded_file($tmp_name, $uploadfile);
        $filename = $uploadfile;
        require_once 'excel/reader.php';
        $data = new Spreadsheet_Excel_Reader();
        $data->setOutputEncoding('UTF-8');
        $data->read($filename);
        error_reporting(E_ALL ^ E_NOTICE);
        //定义试题总条数，错误类型条数
        $sum = 0;
        $error = 0;
        $right = 0;
        $error_question_type = 0;
        //循环获取excel中的值
        // 审核与激活修改
        for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
            if ($i > 2 && $data->sheets[0]['cells'][$i]) {
                $sum++;
                $uname = $data->sheets[0]['cells'][$i][1];
                $email = $data->sheets[0]['cells'][$i][2];
                $phone = $data->sheets[0]['cells'][$i][3];
                $pwd = $data->sheets[0]['cells'][$i][4];
                $sex = $data->sheets[0]['cells'][$i][5] == "男" ? "男" : "女";
                if (!$res = model("User")->where("email=" . $email . " or phone=" . $phone)->find()) {
                    $salt = rand(11111, 99999);
                    $mhm_id = $this->school_id;
                    $map = array(
                        'is_active' => 1,
                        'is_audit' => 1,
                        'is_init' => 1,
                        'uname' => $uname,
                        'email' => $email,
                        'phone' => $phone,
                        'sex' => $sex,
                        'first_letter' => getFirstLetter($uname),
                        'login_salt' => $salt,
                        'password' => md5(md5($pwd) . $salt),
                        'login' => $email ? $email : $phone,
                        'ctime' => time(),
                        'reg_ip' => get_client_ip(),
                        'mhm_id' => $mhm_id,
                    );
                    $result = model("User")->add($map);

                    if ($result) {
                        $right++;
                    } else {
                        $error++;
                    }
                } else {
                    $error_question_type++;
                }
            }
        }

        $this->assign('jumpUrl', U('school/AdminUser/index'));
        $this->success("导入成功,一共有{$sum}条数据，其中邮箱或电话号码存在的数据有{$error_question_type}条，导入成功的数据有{$right}条，导入失败的数据有{$error}条!");
    }

    /**
     * 用户管理 - 禁用列表
     */
    public function dellist()
    {
        $_REQUEST['tabHash'] = 'dellist';
        // 初始化禁用列表管理菜单
        $this->_initUserListAdminMenu('dellist');
        // 数据的格式化与listKey保持一致
        $listData = $this->_getUserList(20, array('is_del' => '1'), 'dellist');
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_USER'), 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => L('PUBLIC_RECOVER_ACCOUNT'), 'onclick' => "admin.rebackUser()");

        $this->displayList($listData);
    }

    /**
     * 用户管理 - 在线用户列表
     */
    public function online()
    {
        $_REQUEST['tabHash'] = 'online';
        // tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_USER_LIST'), 'tabHash' => 'index', 'url' => U('school/AdminUser/index'));
        $this->pageTab[] = array('title' => L('PUBLIC_PENDING_LIST'), 'tabHash' => 'pending', 'url' => U('school/AdminUser/pending'));
        $this->pageTab[] = array('title' => L('PUBLIC_DISABLE_LIST'), 'tabHash' => 'dellist', 'url' => U('school/AdminUser/dellist'));
        // $this->pageTab[] = array('title'=>'在线用户列表','tabHash'=>'online','url'=>U('admin/User/online'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_USER_INFO'), 'tabHash' => 'addUser', 'url' => U('school/AdminUser/addUser'));
        // 搜索选项的key值
        $this->searchKey = array('uid', 'uname', 'email','phone', 'sex', 'user_group', array('ctime', 'ctime1'));
        // 针对搜索的特殊选项
        $this->opt['sex'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));
        $this->opt['identity'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_PERSONAL'), '2' => L('PUBLIC_ORGANIZATION'));
        $this->opt['user_group'] = array_merge(array('0' => L('PUBLIC_SYSTEMD_NOACCEPT')), model('UserGroup')->getHashUsergroup());
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_USER'), 'onclick' => "admin.fold('search_form')");

        $this->opt['user_group'] = array_merge(array('0' => L('PUBLIC_SYSTEMD_NOACCEPT')), model('UserGroup')->getHashUsergroup());

        $this->pageKeyList = array('uid', 'uname', 'user_group', 'location', 'ctime', 'last_operating_ip');

        $listData = $this->_getUserOnlineList(20, $map);

        $this->displayList($listData);
    }

    /**
     * 用户管理 - 查看IP列表
     */
    public function viewIP()
    {
        $_REQUEST['tabHash'] = 'viewIP';
        $uid = intval($_REQUEST['uid']);
        $userInfo = model('User')->getUserInfo($uid);
        $this->pageTitle['viewIP'] = '查看IP - 用户：' . $userInfo['uname'] . '（' . $userInfo['email'] . '）';
        // tab选项
        $this->pageTab[] = array('title' => '查看IP', 'tabHash' => 'viewIP', 'url' => U('school/AdminUser/viewIP', array('tabHash' => 'viewIP', 'uid' => $uid)));
        $this->pageTab[] = array('title' => '登录日志', 'tabHash' => 'loginLog', 'url' => U('school/AdminUser/loginLog', array('tabHash' => 'loginLog', 'uid' => $uid)));
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('id', 'day', 'action', 'ip', 'DOACTION');
        // 获取相关数据
        $listData = model('Online')->getUserOperatingList($uid);
        foreach ($listData['data'] as $k => $v) {
            // $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0);" onclick="admin.disableIP(\''.$v['ip'].'\')">禁用IP</a>';
        }

        $this->displayList($listData);
    }

    /**
     * 用户管理 - 一键试用
     */
    public function tryout()
    {
        $_REQUEST['tabHash'] = 'tryout';
        $this->pageTitle['tryout'] = '一键试用用户';
        $this->pageTab[] = array('title' => '试用用户', 'tabHash' => 'tryout', 'url' => U('school/AdminUser/tryout'));
        $this->pageButton[] = array('title' => '删除用户', 'onclick' => "javascript:;");
        $this->pageKeyList = array('id', 'phone', 'tname', 'company', 'hangye', 'ctime');
        // 初始化用户列表管理菜单
        $listData = M('zy_tryout')->order('ctime desc')->findPage(20);
        foreach ($listData['data'] as &$val) {
            $val['ctime'] = date('Y-m-d H:i', $val['ctime']);
        }
        $this->displayList($listData);
    }

    /**
     * 用户管理 - 登录日志
     */
    public function loginLog()
    {
        $_REQUEST['tabHash'] = 'loginLog';
        $uid = intval($_REQUEST['uid']);
        $userInfo = model('User')->getUserInfo($uid);
        $this->pageTitle['loginLog'] = '登录日志 - 用户：' . $userInfo['uname'] . '（' . $userInfo['email'] . '）';
        // tab选项
        $this->pageTab[] = array('title' => '查看IP', 'tabHash' => 'viewIP', 'url' => U('school/AdminUser/viewIP', array('tabHash' => 'viewIP', 'uid' => $uid)));
        $this->pageTab[] = array('title' => '登录日志', 'tabHash' => 'loginLog', 'url' => U('school/AdminUser/loginLog', array('tabHash' => 'loginLog', 'uid' => $uid)));
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('login_logs_id', 'ip', 'ctime', 'DOACTION');
        // 获取相关数据
        $map['uid'] = $uid;
        $listData = D('login_logs')->where($map)->findPage(20);
        foreach ($listData['data'] as $k => $v) {
            $listData['data'][$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            // $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0);" onclick="admin.disableIP(\''.$v['ip'].'\')">禁用IP</a>';
        }

        $this->displayList($listData);
    }

    /**
     * 用户管理 - 选择课程
     */
    public function changeUserVideo()
    {
        $ids = $_GET["ids"];
        $user_list = explode(",", $ids);
        $user_info = array();
        foreach ($user_list as $key => $value) {
            $user_info[] = M("user")->where("uid=" . $value)->field("uid,uname")->find();
        }
        $this->assign("user_info", $user_info);
        $this->assign("ids", $ids);
        $this->display();
    }

    /**
     * 用户管理 - 选择课程
     */
    public function dochangeUserVideo()
    {
        $video_ids = substr($_POST["video_ids"], 0, strlen($_POST["video_ids"]) - 1);
        $ids = $_POST["ids"];
        $user_list = explode(",", $ids);
        $video_ids = explode(",", $video_ids);
        foreach ($user_list as $key => $value) {
            foreach ($video_ids as $k => $v) {
                $data = array(
                    'user_id' => $value,
                    'video_id' => $v,
                    'admin_id' => $this->mid,
                    'time' => time(),
                );
                $res = M("user_video")->where('user_id=' . $value . " and video_id=" . $v . " and is_del=0")->find();
                if (!$res) {
                    M("user_video")->add($data);
                }
            }
        }
        $this->assign('jumpUrl', U('school/AdminUser/index'));
        $this->success('操作成功');
    }

    /**
     * 获取在线用户列表数据
     */
    private function _getUserOnlineList($limit, $map)
    {
        // 设置列表主键
        $this->_listpk = 'uid';
        // 取用户列表
        $listData = model('User')->getUserList($limit, $map);
        $uids = getSubByKey($listData['data'], 'uid');
        $ipData = D('Online')->getLastOnlineInfo($uids);
        $ipKey = array_keys($ipData);
        // 数据格式化
        foreach ($listData['data'] as $k => $v) {
            $listData['data'][$k]['uname'] = '<a href="' . U('school/AdminUser/editUser', array('tabHash' => 'editUser', 'uid' => $v['uid'])) . '">' . $v['uname'] . '</a> (' . $v['email'] . ')';
            $listData['data'][$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            // 用户组数据
            if (!empty($v['user_group'])) {
                $group = array();
                foreach ($v['user_group'] as $gid) {
                    $group[] = $this->opt['user_group'][$gid];
                }
                $listData['data'][$k]['user_group'] = implode('<br/>', $group);
            } else {
                $listData['data'][$k]['user_group'] = '';
            }
            $this->opt['user_group'][$v['user_group_id']];
            // 最后操作IP
            $listData['data'][$k]['last_operating_ip'] = empty($ipData) ? $v['reg_ip'] : (in_array($v['uid'], $ipKey) ? $ipData[$v['uid']] : $v['reg_ip']);
        }

        return $listData;
    }

    /**
     * 初始化用户列表管理菜单
     * @param string $type 列表类型，index、pending、dellist
     */
    private function _initUserListAdminMenu($type)
    {
        // tab选项
        $this->pageTab[] = array('title' => '列表', 'tabHash' => 'index', 'url' => U('school/AdminUser/index'));
//        $this->pageTab[] = array('title' => L('PUBLIC_PENDING_LIST'), 'tabHash' => 'pending', 'url' => U('school/AdminUser/pending'));
//        $this->pageTab[] = array('title' => L('PUBLIC_DISABLE_LIST'), 'tabHash' => 'dellist', 'url' => U('school/AdminUser/dellist'));
        // $this->pageTab[] = array('title'=>'在线用户列表','tabHash'=>'online','url'=>U('admin/User/online'));
        if(!is_admin($this->mid)) {
            $this->pageTab[] = array('title' => '添加', 'tabHash' => 'addUser', 'url' => U('school/AdminUser/addUser'));
        }
//        $this->pageTab[] = array('title' => L('批量添加用户'), 'tabHash' => 'importUser', 'url' => U('school/AdminUser/importUser'));

        // 搜索选项的key值
        // $this->searchKey = array('uid','uname','email','sex','department','user_group',array('ctime','ctime1'));
        $this->searchKey = array('id', 'uid', 'email', 'sex', 'user_group', 'user_category', array('ctime', 'ctime1'));

        // 针对搜索的特殊选项
        $this->opt['sex'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));
        $this->opt['identity'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_PERSONAL'), '2' => L('PUBLIC_ORGANIZATION'));
        //$this->opt['user_group'] = array_merge(array('0'=>L('PUBLIC_SYSTEMD_NOACCEPT')),model('UserGroup')->getHashUsergroup());
        $this->opt['user_group'] = model('UserGroup')->getHashUsergroup('user_group_id','user_group_name',1);
        $this->opt['user_group'][0] = L('PUBLIC_SYSTEMD_NOACCEPT');

        $map['pid'] = array('NEQ', 0);
        $categoryList = model('UserCategory')->getAllHash($map);
        $categoryList[0] = L('PUBLIC_SYSTEMD_NOACCEPT');
        ksort($categoryList);

        $this->opt['user_category'] = $categoryList;
        //$this->opt['department_id'] = model('Department')->getHashDepartment();

        // 列表key值 DOACTION表示操作
        switch (strtolower($type)) {
            case 'index':
            case 'dellist':
                if(is_admin($this->mid)) {
                    $this->pageKeyList = array('uid','uname','phone','user_group','location','mhm_id','is_audit','is_active','is_init','browser_and_ver','os','place','ctime','reg_ip','DOACTION');
                }else{
                    $this->pageKeyList = array('uid', 'uname', 'phone', 'user_group', 'location', 'is_audit', 'is_active', 'is_init', 'ctime', 'DOACTION');
                }
                break;
            case 'pending':
                $this->pageKeyList = array('uid', 'uname', 'location', 'ctime', 'reg_ip', 'DOACTION');
                break;
        }

        /*		if(!empty($_POST['_parent_dept_id'])) {
			$this->onload[] = "admin.departDefault('".implode(',', $_POST['_parent_dept_id'])."','form_user_department')";
		}*/
    }

    public function exportUser()
    {
        $uids       = explode(",", $_GET["uid"]);
        $map["uid"] = array("in", $uids);
        $listData   = model('User')->where($map)->field("uid,uname,email,phone,sex,reg_ip,ctime")->select();
		if(!$listData){
			$this->error("暂无用户可导出");
		}
		$xlsCell = [
			['uid', '用户ID'],
			['uname', '用户昵称'],
			['email', '邮箱'],
			['phone', '电话号码'],
			['sex', '性别'],
			['reg_ip', '注册IP'],
			['ctime', '注册时间']
		];
		if ($listData) {
			foreach ($listData as &$v) {
				$v['sex'] = $v['sex'] == 1 ? "男" : "女";
				$v['ctime'] = date("Y-m-d H:i:s", $v['ctime']);
			}
			unset($v);
		}
		model('Excel')->export('用户信息导出', $xlsCell, $listData);
    }

    /**
     * 解析用户列表数据
     * @param integer $limit 结果集数目，默认为20
     * @param array $map 查询条件
     * @param string $type 格式化数据类型，index、pending、dellist
     * @return array 解析后的用户列表数据
     */
    private function _getUserList($limit = 20, $map = array(), $type = 'index')
    {
        // 设置列表主键
        $this->_listpk = 'uid';
        if ($_POST['id']) {
            $_POST['uid'] = $_POST['id'];
        }

        if(!is_admin($this->mid) && is_school($this->mid)){
            if($this->mid == 1){
                $map['_string'] = 'mhm_id ='.is_school($this->mid).' or mhm_id = ""';
            }else{
                $map['mhm_id'] = is_school($this->mid);
            }
        }
        $listData = model('User')->getUserList($limit, $map);
        // 数据格式化
        foreach ($listData['data'] as $k => $v) {
            $school_info = M('school')->where('id = '.$v['mhm_id'])->field('title,doadmin')->find();
            $listData['data'][$k]['mhm_id'] = getQuickLink(getDomain($school_info['doadmin']),$school_info['title'],'未知机构');
            $listData['data'][$k]['browser_and_ver']  = $v['browser']." ".$v['browser_ver'];
            $listData['data'][$k]['place']  = trim($v['place']) ? : '<span style="color: red;">未知地区</span>';
            if ($v["parent_id"] > 0) {
                $parent = M("user")->where("uid=" . $v["parent_id"])->find();
                $listData['data'][$k]['parent'] = $parent["uname"];
            } else {
                $listData['data'][$k]['parent'] = "没有父级";
            }
            // 获取用户身份信息
            $userTag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($v['uid']);
            $userTagString = '';
            $userTagArray = array();
            if (!empty($userTag)) {
                $userTagString .= '<p>';
                foreach ($userTag as $value) {
                    $userTagArray[] = '<span style="color:blue;cursor:auto;">' . $value . '</span>';
                }
                $userTagString .= implode('&nbsp;', $userTagArray) . '</p>';
            }
            //获取用户组信息
            $userGroupInfo = model('UserGroupLink')->getUserGroupData($v['uid']);
            foreach ($userGroupInfo[$v['uid']] as $val) {
                $userGroupIcon[$v['uid']] .= '<img style="width:auto;height:auto;display:inline;cursor:pointer;vertical-align:-2px;" src="' . $val['user_group_icon_url'] . '" title="' . $val['user_group_name'] . '" />&nbsp';
            }
            $listData['data'][$k]['uname'] = '<a href="' . U('school/AdminUser/editUser', array('tabHash' => 'editUser', 'uid' => $v['uid'])) . '">' . $v['uname'] . '</a>' . $userGroupIcon[$v['uid']] . ' <br/>' . $this ->hideStar($v['email']) . ' ' . $userTagString;
            $listData['data'][$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            // 屏蔽部门信息，若要开启将下面的注释打开
            /*			$department = model('Department')->getUserDepart($v['uid']);
			$listData['data'][$k]['department'] = str_replace('|', ' - ',trim($department[$v['uid']],'|'));*/
            $listData['data'][$k]['identity'] = ($v['identity'] == 1) ? L('PUBLIC_PERSONAL') : L('PUBLIC_ORGANIZATION');
            switch (strtolower($type)) {
                case 'index':
                case 'dellist':
                    // 列表数据
                    $listData['data'][$k]['is_active'] = ($v['is_active'] == 1) ? '<span style="color:blue;cursor:auto;">' . L('SSC_ALREADY_ACTIVATED') . '</span>' : '<a href="javascript:void(0)" onclick="admin.activeUser(\'' . $v['uid'] . '\',1)" style="color:red">' . L('PUBLIC_NOT_ACTIVATED') . '</a>';
                    $listData['data'][$k]['is_audit'] = ($v['is_audit'] == 1) ? '<span style="color:blue;cursor:auto;">' . L('PUBLIC_AUDIT_USER_SUCCESS') . '</span>' : '<a href="javascript:void(0)" onclick="admin.auditUser(\'' . $v['uid'] . '\',1)" style="color:red">' . L('PUBLIC_AUDIT_USER_ERROR') . '</a>';
                    $listData['data'][$k]['is_init'] = ($v['is_init'] == 1) ? '<span style="cursor:auto;">' . L('PUBLIC_SYSTEMD_TRUE') . '</span>' : '<span style="cursor:auto;">' . L('PUBLIC_SYSTEMD_FALSE') . '</span>';
                    if ($v['phone'] != null) {
                        $listData['data'][$k]['phone'] =  $this ->hideStar($v['phone']);
                    }
                    // 用户组数据
                    if (!empty($v['user_group'])) {
                        $group = array();
                        foreach ($v['user_group'] as $gid) {
                            $group[] = $this->opt['user_group'][$gid];
                        }
                        $listData['data'][$k]['user_group'] = implode('<br/>', $group);
                    } else {
                        $listData['data'][$k]['user_group'] = '';
                    }
                    $this->opt['user_group'][$v['user_group_id']];
                    // 操作数据
//                    $listData['data'][$k]['DOACTION'] = '<a href="' . U('school/AdminUser/User/editUser', array('tabHash' => 'editUser', 'uid' => $v['uid'])) . '">' . L('PUBLIC_EDIT') . '</a> - ';
//                    $listData['data'][$k]['DOACTION'] .= $v['is_del'] == 1 ? '<a href="javascript:void(0)" onclick="admin.rebackUser(\'' . $v['uid'] . '\')">' . L('PUBLIC_RECOVER') . '</a> - ' : '<a href="javascript:void(0)" onclick="admin.delUser(\'' . $v['uid'] . '\')">' . L('PUBLIC_SYSTEM_NOUSE') . '</a> - ';
                  
                    $listData['data'][$k]['DOACTION'] .= ' <a onclick="ui.sendmessage(\'' . $v['uid'] . '\', 0)" href="javascript:void(0)" >发私信</a>	 ';

                $is_mount_teacher = model('UserGroupLink')->where('uid='.$v['uid'])->field('user_group_id')->select();
                $is_mount_teacher  = array_unique(getSubByKey($is_mount_teacher , 'user_group_id'));

//                if (!in_array("5", $is_mount_teacher)) {
//                    $listData['data'][$k]['DOACTION'] .= '| <a onclick="admin.editMountTeacher(\'' . $v['uid'] . '\', 1)" href="javascript:void(0)" >设置为销课班主任</a>';
//                }else{
//                    $listData['data'][$k]['DOACTION'] .= '| <a onclick="admin.editMountTeacher(\'' . $v['uid'] . '\', 2)" href="javascript:void(0)" >取消销课班主任</a>';
//                }
                    // $listData['data'][$k]['DOACTION'] .= '<a href="'.U('admin/User/viewIP',array('tabHash'=>'viewIP','uid'=>$v['uid'])).'">查看IP</a>';
                    break;
                case 'pending':
                    // 操作数据
                    $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.auditUser(\'' . $v['uid'] . '\', 1)">' . L('PUBLIC_AUDIT_USER_SUCCESS') . '</a> -';
                    break;

            }
        }
        return $listData;
    }

    public function editMountTeacher(){
        $map['id'] = is_array($_POST['id']) ? array('IN',$_POST['id']) : intval($_POST['id']);

        if(intval($_POST['type']) == 2){
            $result = M('user_group_link')->where(['user_group_id'=>5,'uid'=>$map['id']])->delete();
        }else{
            $data['uid'] = $map['id'];
            $data['user_group_id'] = 5;

            $result = M('user_group_link')->where($map)->add($data);
        }

        if($result){
            $this->mzSuccess('操作成功');
        } else {
            $this->mzError('系统繁忙，稍后再试');
        }
    }

    /**
     * 用户管理 - 添加用户
     */
    public function addUser()
    {
        // 初始化用户列表管理菜单
        $this->_initUserListAdminMenu();
        $this->pageTitle['addUser']       = '添加';
        //注册配置(添加用户页隐藏审核按钮)
        $regInfo = model('Xdata')->get('admin_Config:register');
        $this->pageKeyList = array('email', 'uname', 'password', 'sex');
        if ($regInfo['register_audit'] == 1) {
            $this->pageKeyList = array_merge($this->pageKeyList, array('is_audit'));
            $this->opt['is_audit'] = array('1' => '是', '2' => '否');
        }
        if ($regInfo['need_active'] == 1) {
            $this->pageKeyList = array_merge($this->pageKeyList, array('is_active'));
            $this->opt['is_active'] = array('1' => '是', '2' => '否');
        }
        $this->pageKeyList = array_merge($this->pageKeyList);

        $this->opt['type'] = array('2' => L('PUBLIC_SYSTEM_FIELD'));
        // 字段选项配置
        $this->opt['sex'] = array('1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));

        $map['pid'] = array('NEQ', 0);
        $this->opt['user_category'] = model('UserCategory')->getAllHash($map);
        // 表单URL设置
        $this->savePostUrl = U('school/AdminUser/doAddUser');
        $this->notEmpty = array('email', 'uname', 'password');
//        $this->onsubmit = 'admin.addUserSubmitCheck(this)';

        $this->displayConfig();
    }

    /**
     * 添加新用户操作
     */
    public function doAddUser()
    {
        $user = model('User');
        $map = $user->create();
        // 审核与激活修改
        $map['is_active'] = ($map['is_active'] == 2) ? 0 : 1;
        $map['is_audit'] = ($map['is_audit'] == 2) ? 0 : 1;
        $map['is_init'] = ($map['is_init'] == 2) ? 0 : 1;
        $map['user_group'] = 2;
        //获取默认机构
        $this_mhm_id = model('School')->getDefaultSchol('id');
        $map['mhm_id'] = is_school($this->mid) ?: $this_mhm_id;
        //检查map返回值，有表单验证
        $result = $user->addUser($map);
        if ($result) {
            $this->assign('jumpUrl', U('school/AdminUser/index'));
            $this->success(L('PUBLIC_ADD_SUCCESS'));
        } else {
            $this->error($user->getLastError());
        }
    }

    /**
     * 编辑用户页面
     */
    public function editUser()
    {
        // 初始化用户列表管理菜单
        $this->_initUserListAdminMenu();
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('uid', 'email', 'uname', 'phone', 'password', 'sex');
        $this->opt['type'] = array('2' => L('PUBLIC_SYSTEM_FIELD'));
        // 字段选项配置
        $this->opt['sex'] = array('1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));
        //$this->opt['identity'] = array('1'=>L('PUBLIC_PERSONAL'),'2'=>L('PUBLIC_ORGANIZATION'));
        // $user_department = model('Department')->getAllHash(0);
        $usergroupHash = model('UserGroup')->getHashUsergroupNoncertified();
        $this->opt['user_group'] = $usergroupHash;

        $this->opt['is_active'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));

        //获取用户资料
        $uid = intval($_REQUEST['uid']);
        $userInfo = model('User')->getUserInfo($uid);

        unset($userInfo['password']);
        //获取用户组信息
        $userInfo['user_group'] = model('UserGroupLink')->getUserGroup($uid);
        $userInfo['user_group'] = $userInfo['user_group'][$uid];
        $map['pid'] = array('neq', 0);
        $this->opt['user_category'] = model('UserCategory')->getAllHash($map);
        $userInfo['user_category'] = getSubByKey(model('UserCategory')->getRelatedUserInfo($uid), 'user_category_id');

        if (!$userInfo) {
            $this->error(L('PUBLIC_GET_INFORMATION_FAIL'));
        }

        //用户部门
        /*		$depart = model('Department')->getUserDepart($uid);
		$userInfo['department_show'] = str_replace('|', ' - ',trim($depart[$uid],'|'));
		$userInfo['department_id']	 = 0;*/

        $this->assign('pageTitle', L('PUBLIC_EDIT_USER'));
        $this->savePostUrl = U('school/AdminUser/doUpdateUser');

        // $this->notEmpty = array('email','uname','department_id');
        $this->notEmpty = array('email', 'uname', 'user_group');
        $this->onsubmit = 'admin.checkUser(this)';

        $this->displayConfig($userInfo);
    }

    /**
     * 更新用户信息
     */
    public function doUpdateUser()
    {
        $uid = intval($_POST['uid']);
        $user = model('User');
        // 验证用户名称是否重复
        $oldUname = $user->where('uid=' . $uid)->getField('uname');
        $vmap['uname'] = t($_POST['uname']);
        if ($oldUname != $vmap['uname']) {
            $isExist = $user->where($vmap)->count();
            if ($isExist > 0) {
                $this->error('用户昵称已存在，请使用其他昵称');
                return false;
            }
        }

        $map = $user->create();
        $map['login'] = t($_POST['email']);
        unset($map['password']);
        // 生成新密码
        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $map['login_salt'] = rand(11111, 99999);
            $map['password'] = md5(md5($_POST['password']) . $map['login_salt']);
        }
        $map['first_letter'] = getFirstLetter($map['uname']);
        // 如果包含中文将中文翻译成拼音
        if (preg_match('/[\x7f-\xff]+/', $map['uname'])) {
            // 昵称和呢称拼音保存到搜索字段
            $map['search_key'] = $map['uname'] . ' ' . model('PinYin')->Pinyin($map['uname']);
        } else {
            $map['search_key'] = $map['uname'];
        }
        $map['user_group'] = 2;

        // 检查map返回值，有表单验证
        $result = $user->where("uid=" . $uid)->save($map);

        if (count($_POST['user_group']) == 0) {
            $this->error('用户组不能为空');
        }
        $r = model('UserGroupLink')->domoveUsergroup($uid, implode(',', $_POST['user_group']));

        // 更改部门
        /*
		if(!empty($_POST['department_id'])){
			model('Department')->updateUserDepartById($uid,intval($_POST['department_id']));
		}*/
        //更改职业信息
        //model('UserCategory')->updateRelateUser($uid, $_POST['user_category']);

        if ($result || $r) {
            model('User')->cleanCache($uid);
            // 清除权限缓存
            model('Cache')->rm('perm_user_' . $uid);
            // 保存用户组的信息
            $this->assign('jumpUrl', U('school/AdminUser/editUser', array('uid' => $uid, 'tabHash' => 'editUser')));
            $this->success(L('PUBLIC_SYSTEM_MODIFY_SUCCESS'));
        } else {
            $this->error(L('PUBLIC_ADMIN_OPRETING_ERROR'));
        }
    }

    /*
	 * 新增资料字段/分类
	 * @access public
	 *
	 */
    public function doActiveUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        //设置激活状态id可以是多个，类型只能是0或1
        $result = model('User')->activeUsers($_POST['id'], $_POST['type']);
        if (!$result) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        } else {
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        }
        echo json_encode($return);
        exit();
    }

    public function doAuditUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        //设置激活状态id可以是多个，类型只能是0或1
        $result = model('Register')->audit($_POST['id'], $_POST['type']);
        if (!$result) {
            $return['status'] = 0;
            $return['data'] = model('Register')->getLastError();
        } else {
            $return['status'] = 1;
            $return['data'] = model('Register')->getLastError();
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 用户帐号禁用操作
     * @return json 操作后的JSON数据
     */
    public function doDeleteUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            exit(json_encode($return));
        }

        $result = model('User')->deleteUsers(intval($_POST['id']));
        if (!$result) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');                // 操作失败
        } else {
            // 关联删除用户其他信息，执行删除用户插件.
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');            // 操作成功
        }
        exit(json_encode($return));
    }

//    /**
//     * 彻底删除用户帐号操作
//     * @return json 操作后的JSON数据
//     */
//    public function doTrueDeleteUser()
//    {
//        if (empty($_POST['id'])) {
//            $return['status'] = 0;
//            $return['data'] = '';
//            exit(json_encode($return));
//        }
//
//        $result = model('User')->trueDeleteUsers(intval($_POST['id']));
//        if (!$result) {
//            $return['status'] = 0;
//            $return['data'] = L('PUBLIC_REMOVE_COMPLETELY_FAIL');                // 操作失败
//        } else {
//            // 关联删除用户其他信息，执行删除用户插件.
//            $return['status'] = 1;
//            $return['data'] = L('PUBLIC_REMOVE_COMPLETELY_SUCCESS');            // 操作成功
//        }
//        exit(json_encode($return));
//    }

    /**
     * 用户帐号恢复操作
     * @return json 操作后的JSON数据
     */
    public function doRebackUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            exit(json_encode($return));
        }

        $result = model('User')->rebackUsers($_POST['id']);
        if (!$result) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');                // 操作失败
        } else {
            //关联删除用户其他信息，执行删除用户插件.
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');            // 操作成功
        }
        exit(json_encode($return));
    }

    /*
	 * 用户资料配置
	 * @access public
	 */
    public function profile()
    {

        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('school/AdminUser/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('school/AdminUser/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('school/AdminUser/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('school/AdminUser/addProfileCategory'));


        //字段列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'field_key', 'field_name', 'field_type', 'visiable', 'editable', 'required', 'DOACTION');

        //列表批量操作按钮ed
        $this->pageButton[] = array('title' => L('PUBLIC_ADD_FIELD'), 'onclick' => "location.href='" . U('school/AdminUser/addProfileField', array('tabHash' => 'addField')) . "'");

        $map = array();

        /*数据的格式化 与listKey保持一致 */

        //取用户列表
        $listData = D('UserProfile')->table(C('DB_PREFIX') . 'user_profile_setting')
            ->where($map)
            ->order('type,field_type,display_order asc')
            ->findPage(100);
        //dump($listData);exit;
        //数据格式化
        foreach ($listData['data'] as $k => $v) {
            if ($v['type'] == 1) {
                $type[$v['field_id']] = $v;
                $listData['data'][$k]['type'] = '<b>' . L('PUBLIC_SYSTEM_CATEGORY') . '</b>';
            } else {
                $listData['data'][$k]['field_type'] = $type[$v['field_type']]['field_name'];
                $listData['data'][$k]['type'] = L('PUBLIC_SYSTEM_FIELD');
            }
            $listData['data'][$k]['visiable'] = $listData['data'][$k]['visiable'] == 1 ? L('PUBLIC_SYSTEMD_TRUE') : L('PUBLIC_SYSTEMD_FALSE');
            $listData['data'][$k]['editable'] = $listData['data'][$k]['editable'] == 1 ? L('PUBLIC_SYSTEMD_TRUE') : L('PUBLIC_SYSTEMD_FALSE');
            $listData['data'][$k]['required'] = $listData['data'][$k]['required'] == 1 ? L('PUBLIC_SYSTEMD_TRUE') : L('PUBLIC_SYSTEMD_FALSE');
            //操作按钮
            $listData['data'][$k]['DOACTION'] = '<a href="' . U('school/AdminUser/editProfileField', array('tabHash' => 'editField', 'id' => $v['field_id'])) . '">' . L('PUBLIC_EDIT') . '</a> '
                . ($v['is_system'] == 1 ? '' : ' -  <a href="javascript:void(0)" onclick="admin.delProfileField(\'' . $v['field_id'] . '\',1)">' . L('PUBLIC_STREAM_DELETE') . '</a>');

            //如果只显示字段.删除数据
            if ($field_type != 1 && $v['type'] == 1) {
                unset($listData['data'][$k]);
            }
        }

        //$this->_listpk = 'field_id';
        $this->allSelected = false;
        $this->displayList($listData);
    }

    /*
	 * 用户资料分类配置
	 * @access public
	 */
    public function profileCategory()
    {

        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('school/AdminUser/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('school/AdminUser/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('school/AdminUser/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('school/AdminUser/addProfileCategory'));

        //分类列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'field_key', 'field_name', 'DOACTION');

        //列表批量操作按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'onclick' => "location.href='" . U('school/AdminUser/addProfileCategory', array('tabHash' => 'addCateogry')) . "'");
        //$this->pageButton[] = array('title'=>'删除选中','onclick'=>"admin.delProfileField()");

        $map = array();
        $map['type'] = 1;

        /*数据的格式化 与listKey保持一致 */

        //取用户列表
        $listData = D('UserProfile')->table(C('DB_PREFIX') . 'user_profile_setting')
            ->where($map)
            ->order('type,field_type,display_order asc')
            ->findPage(100);

        //数据格式化
        foreach ($listData['data'] as $k => $v) {
            if ($v['type'] == 1) {
                $type[$v['field_id']] = $v;
                $listData['data'][$k]['type'] = '<b>' . L('PUBLIC_SYSTEM_CATEGORY') . '</b>';
            } else {
                $listData['data'][$k]['field_type'] = $type[$v['field_type']]['field_name'];
                $listData['data'][$k]['type'] = L('PUBLIC_SYSTEM_FIELD');
            }

            //操作按钮

            $listData['data'][$k]['DOACTION'] = '<a href="' . U('school/AdminUser/editProfileCategory', array('tabHash' => 'addProfileCategory', 'id' => $v['field_id'])) . '">' . L('PUBLIC_EDIT') . '</a> '
                . ($v['is_system'] == 1 ? ' ' : ' - <a href="javascript:void(0)" onclick="admin.delProfileField(\'' . $v['field_id'] . '\',0)">' . L('PUBLIC_STREAM_DELETE') . '</a>');
        }

        //$this->_listpk = 'field_id';
        $this->allSelected = false;
        $this->displayList($listData);
    }

    /*
	 * 新增资料字段/分类
	 * @access public
	 *
	 */
    public function editProfileCategory()
    {

        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('school/AdminUser/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('school/AdminUser/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('school/AdminUser/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('school/AdminUser/addProfileCategory'));


        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'type', 'field_key', 'field_name', 'field_type');
        $this->opt['type'] = array('1' => L('PUBLIC_SYSTEM_CATEGORY'));

        //获取配置信息
        $id = intval($_REQUEST['id']);
        $setting = D('UserProfileSetting')->where("type=1")->find($id);
        if (!$setting) {
            $this->error(L('PUBLIC_INFO_GET_FAIL'));
        }

        $this->savePostUrl = U('school/AdminUser/doSaveProfileField');

        $this->notEmpty = array('field_key', 'field_name');
        $this->onsubmit = 'admin.checkProfile(this)';

        $this->displayConfig($setting);
    }

    /*
	 * 新增资料字段/分类
	 * @access public
	 *
	 */
    public function addProfileField($edit = false)
    {
        $_GET['id'] = intval($_GET['id']);
        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('school/AdminUser/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('school/AdminUser/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('school/AdminUser/addProfileField'));
        $edit && $this->pageTab[] = array('title' => L('PUBLIC_EDIT_FIELD'), 'tabHash' => 'editField', 'url' => U('school/AdminUser/editProfileField', array('id' => $_REQUEST['id'])));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('school/AdminUser/addProfileCategory'));

        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'type', 'field_key', 'field_name', 'field_type', 'visiable', 'editable', 'required', 'privacy', 'form_type', 'form_default_value', 'validation', 'tips');
        $this->opt['type'] = array('2' => L('PUBLIC_SYSTEM_FIELD'));

        //获取字段分类列表
        $category = D('UserProfileSetting')->where("type=1")->findAll();
        foreach ($category as $c) {
            $cate_array[$c['field_id']] = $c['field_name'];
        }

        //字段选项配置
        $this->opt['field_type'] = $cate_array;
        $this->opt['visiable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['editable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['required'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['privacy'] = array('0' => L('PUBLIC_WEIBO_COMMENT_ALL'), '1' => L('PUBLIC_SYSTEM_PARENT_SEE'), '2' => L('PUBLIC_SYSTEM_FOLLOWING_SEE'), '3' => L('PUBLIC_SYSTEM_FOLLW_SEE'));
        $this->opt['form_type'] = model('UserProfile')->getUserProfileInputType();

        $detail = !empty($_GET['id']) ? D('UserProfileSetting')->where("field_id='{$_GET['id']}'")->find() : array();
        $this->savePostUrl = !empty($detail) ? U('school/AdminUser/doSaveProfileField') : U('school/AdminUser/doAddProfileField');

        $this->notEmpty = array('field_key', 'field_name', 'field_type');
        $this->onsubmit = 'admin.checkProfile(this)';
        $this->displayConfig($detail);
    }

    public function editProfileField()
    {
        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('school/AdminUser/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('school/AdminUser/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('school/AdminUser/addProfileField'));
        $edit && $this->pageTab[] = array('title' => L('PUBLIC_EDIT_FIELD'), 'tabHash' => 'editField', 'url' => U('school/AdminUser/editProfileField', array('id' => $_REQUEST['id'])));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('school/AdminUser/addProfileCategory'));

        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'type', 'field_key', 'field_name', 'field_type', 'visiable', 'editable', 'required', 'privacy', 'form_type', 'form_default_value', 'validation', 'tips');
        $this->opt['type'] = array('2' => L('PUBLIC_SYSTEM_FIELD'));

        //获取字段分类列表
        $category = D('UserProfileSetting')->where("type=1")->findAll();
        foreach ($category as $c) {
            $cate_array[$c['field_id']] = $c['field_name'];
        }

        //字段选项配置
        $this->opt['field_type'] = $cate_array;
        $this->opt['visiable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['editable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['required'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['privacy'] = array('0' => L('PUBLIC_WEIBO_COMMENT_ALL'), '1' => L('PUBLIC_SYSTEM_PARENT_SEE'), '2' => L('PUBLIC_SYSTEM_FOLLOWING_SEE'), '3' => L('PUBLIC_SYSTEM_FOLLW_SEE'));
        $this->opt['form_type'] = model('UserProfile')->getUserProfileInputType();

        $detail = !empty($_GET['id']) ? D('UserProfileSetting')->where("field_id='{$_GET['id']}'")->find() : array();
        $this->savePostUrl = !empty($detail) ? U('school/AdminUser/doSaveProfileField') : U('school/AdminUser/doAddProfileField');

        $this->notEmpty = array('field_key', 'field_name', 'field_type');
        $this->onsubmit = 'admin.checkProfile(this)';
        $this->displayConfig($detail);
        // $this->addProfileField(true);
    }

    /*
	 * 新增资料字段/分类
	 * @access public
	 *
	 */
    public function addProfileCategory()
    {

        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('school/AdminUser/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('school/AdminUser/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('school/AdminUser/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('school/AdminUser/addProfileCategory'));


        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('type', 'field_key', 'field_name', 'field_type');
        $this->opt['type'] = array('1' => L('PUBLIC_SYSTEM_CATEGORY'));

        //字段选项配置
        $this->opt['field_type'] = array('0' => L('PUBLIC_SYSTEM_PCATEGORY'));
        $this->opt['visiable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['editable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['required'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['privacy'] = array('0' => L('PUBLIC_WEIBO_COMMENT_ALL'), '1' => L('PUBLIC_SYSTEM_PARENT_SEE'), '2' => L('PUBLIC_SYSTEM_FOLLOWING_SEE'), '3' => L('PUBLIC_SYSTEM_FOLLW_SEE'));
        $this->opt['form_type'] = model('UserProfile')->getUserProfileInputType();

        $this->savePostUrl = U('school/AdminUser/doAddProfileField');

        $detail = !empty($_GET['id']) ? D('UserProfileSetting')->where("field_id='{$_GET['id']}'")->find() : array();

        $this->notEmpty = array('field_key', 'field_name');
        $this->onsubmit = 'admin.checkProfile(this)';

        $this->displayConfig($detail);
    }

    /*
	 * 添加资料字段/分类
	 * @access public
	 *
	 */
    public function doAddProfileField()
    {
        //dump($_REQUEST);exit;
        $profile = D('UserProfileSetting');
        $map = $profile->create();
        //检查map返回值.有表单验证.
        $result = $profile->add($map);

        if ($result) {
            $jumpUrl = $_POST['type'] == 1 ? U('school/AdminUser/profileCategory', array('tabHash' => 'category')) : U('school/AdminUser/profile');
            $this->assign('jumpUrl', $jumpUrl);
            $this->success(L('PUBLIC_ADD_SUCCESS'));
        } else {
            $this->error(L('PUBLIC_ADD_FAIL'));
        }
    }

    /*
	 * 保存资料字段/分类
	 * @access public
	 *
	 */
    public function doSaveProfileField()
    {
        $profile = D('UserProfileSetting');
        $map = $profile->create();
        $field_id = intval($_POST['field_id']);

        $jumpUrl = $_POST['type'] == 1 ? U('school/AdminUser/profileCategory', array('tabHash' => 'category')) : U('school/AdminUser/profile');
        //检查map返回值.有表单验证.
        $result = $profile->where("field_id=" . $field_id)->save($map);
        if ($result) {

            $this->assign('jumpUrl', $jumpUrl);
            $this->success(L('PUBLIC_SYSTEM_MODIFY_SUCCESS'));
        } else {
            $this->error(L('PUBLIC_ADMIN_OPRETING_ERROR'));
        }
    }

    /*
	 * 删除资料字段/分类
	 * @access public
	 *
	 */
    public function doDeleteProfileField()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        if (D('UserProfileSetting')->where('field_type=' . intval($_POST['id']))->find()) {
            $return['status'] = 0;
            $return['data'] = '删除失败，该分类下字段不为空！';
        } else {
            $result = model('UserProfile')->deleteProfileSet($_POST['id']);
            if (!$result) {
                $return['status'] = 0;
                $return['data'] = L('PUBLIC_DELETE_FAIL');
            } else {
                //关联删除用户其他信息.执行删除用户插件.
                $return['status'] = 1;
                $return['data'] = L('PUBLIC_DELETE_SUCCESS');
            }
        }
        echo json_encode($return);
        exit();
    }

    /*
	 * 资料配置预览
	 * @access public
	 *
	 */


    /**
     * 转移用户组
     * Enter description here ...
     */
    public function moveDepartment()
    {
        $this->display();
    }

    public function domoveDepart()
    {
        $return = array('status' => '0', 'data' => L('PUBLIC_ADMIN_OPRETING_ERROR'));
        if (!empty($_POST['uid']) && !empty($_POST['topid'])) {
            if ($res = model('User')->domoveDepart($_POST['uid'], $_POST['topid'])) {
                $return = array('status' => 1, 'data' => L('PUBLIC_ADMIN_OPRETING_SUCCESS'));
                //TODO 记录日志
            } else {
                $return['data'] = model('User')->getError();
            }
        }
        echo json_encode($return);
        exit();
    }

    public function moveGroup()
    {
        $usergroupHash = model('UserGroup')->getHashUsergroupNoncertified();
        $this->assign('user_group', $usergroupHash);
        $this->display();
    }

    public function domoveUsergroup()
    {
        $return = array('status' => '0', 'data' => L('PUBLIC_ADMIN_OPRETING_ERROR'));
        if (!empty($_POST['uid']) && !empty($_POST['user_group_id'])) {
            if ($res = model('UserGroupLink')->domoveUsergroup($_POST['uid'], $_POST['user_group_id'])) {
                $return = array('status' => 1, 'data' => L('PUBLIC_ADMIN_OPRETING_SUCCESS'));
                //TODO 记录日志
            } else {
                $return['data'] = model('UserGroup')->getError();
            }
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 初始化用户认证菜单
     */
    public function _initVerifyAdminMenu()
    {
        // tab选项
        $this->pageTab[] = array('title' => '认证分类', 'tabHash' => 'verifyCategory', 'url' => U('school/AdminUser/verifyCategory'));

        $this->pageTab[] = array('title' => '添加认证用户', 'tabHash' => 'addverify', 'url' => U('school/AdminUser/addVerify'));
        $this->pageTab[] = array('title' => '待认证用户', 'tabHash' => 'verify', 'url' => U('school/AdminUser/verify'));

        $this->pageTab[] = array('title' => '已认证用户', 'tabHash' => 'verified', 'url' => U('school/AdminUser/verified'));

    }

    /**
     * 获取待认证用户列表
     * @return void
     */
    public function verify()
    {
        $this->_initVerifyAdminMenu();
        $this->pageButton[] = array('title' => '驳回认证', 'onclick' => "admin.verify('',-1)");

        $this->pageKeyList = array('uname', 'usergroup_id', 'category', 'realname', 'idcard', 'phone', 'reason', 'info', 'school', 'specialty', 'address', 's_address', 'attachment', 'other_data', 'DOACTION');
        $listData = D('user_verified')->where('verified=0')->findpage(20);
        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname'] = $userinfo['uname'];
            $listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id=' . $v['usergroup_id'])->getField('user_group_name');
            if ($listData['data'][$k]['attach_id']) {
                $a = explode('|', $listData['data'][$k]['attach_id']);
                $listData['data'][$k]['attachment'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= $attachInfo['name'] . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path']) . $attachInfo['save_name'] . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            if ($listData['data'][$k]['other_data']) {
                $a = explode('|', $listData['data'][$k]['other_data']);
                $listData['data'][$k]['other_data'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['other_data'] .= $attachInfo['name'] . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path']) . $attachInfo['save_name'] . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            $listData['data'][$k]['category'] = $categoryHash[$v['user_verified_category_id']];
            $listData['data'][$k]['reason'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.verify(' . $v['id'] . ',1,0)">通过</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.getVerifyBox(' . $v['id'] . ')">驳回</a>';

        }
        $this->displayList($listData);
    }

    /**
     * 获取待认证企业列表
     * @return void
     */
    public function verifyGroup()
    {
        $this->_initVerifyAdminMenu();
        $this->pageButton[] = array('title' => '驳回认证', 'onclick' => "admin.verify('',-1,6)");

        $this->pageKeyList = array('uname', 'usergroup_id', 'category', 'company', 'realname', 'idcard', 'phone', 'reason', 'info', 'attachment', 'DOACTION');
        $listData = D('user_verified')->where('verified=0 and usergroup_id=6')->findpage(20);
        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname'] = $userinfo['uname'];
            $listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id=' . $v['usergroup_id'])->getField('user_group_name');
            if ($listData['data'][$k]['attach_id']) {
                $a = explode('|', $listData['data'][$k]['attach_id']);
                $listData['data'][$k]['attachment'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= $attachInfo['name'] . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path'] . $attachInfo['save_name']) . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            $listData['data'][$k]['category'] = $categoryHash[$v['user_verified_category_id']];
            $listData['data'][$k]['reason'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.verify(' . $v['id'] . ',1,0)">通过</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.getVerifyBox(' . $v['id'] . ')">驳回</a>';
        }
        $this->displayList($listData);
    }

    /**
     * 获取已认证用户列表
     * @return void
     */
    public function verified()
    {
        $this->_initVerifyAdminMenu();
        $this->pageButton[] = array('title' => '驳回认证', 'onclick' => "admin.verify('',-1)");

        $this->pageKeyList = array('uname', 'usergroup_id', 'category', 'realname', 'idcard', 'phone', 'reason', 'info', 'school', 'specialty', 'address', 's_address', 'attachment', 'other_data', 'DOACTION');
        $listData = D('user_verified')->where('verified=1')->order('id DESC')->findpage(20);
        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname'] = $userinfo['uname'];
            $listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id=' . $v['usergroup_id'])->getField('user_group_name');
            if ($listData['data'][$k]['attach_id']) {
                $a = explode('|', $listData['data'][$k]['attach_id']);
                $listData['data'][$k]['attachment'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= $attachInfo['name'] . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path'] . $attachInfo['save_name']) . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            if ($listData['data'][$k]['other_data']) {
                $a = explode('|', $listData['data'][$k]['other_data']);
                $listData['data'][$k]['other_data'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['other_data'] .= $attachInfo['name'] . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path']) . $attachInfo['save_name'] . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            $listData['data'][$k]['category'] = $categoryHash[$v['user_verified_category_id']];
            $listData['data'][$k]['reason'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
            $listData['data'][$k]['info'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['info']));
            $listData['data'][$k]['DOACTION'] = '<a href="' . U('school/AdminUser/editVerify', array('tabHash' => 'verified', 'id' => $v['id'])) . '">编辑</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.getVerifyBox(' . $v['id'] . ')">驳回</a>';
        }
        $this->displayList($listData);
    }

    /**
     * 获取已认证企业列表
     * @return void
     */
    public function verifiedGroup()
    {
        $this->_initVerifyAdminMenu();
        $this->pageButton[] = array('title' => '驳回认证', 'onclick' => "admin.verify('',-1,6)");

        $this->pageKeyList = array('uname', 'usergroup_id', 'category', 'company', 'realname', 'idcard', 'phone', 'reason', 'info', 'attachment', 'DOACTION');
        $listData = D('user_verified')->where('verified=1 and usergroup_id=6')->order('id DESC')->findpage(20);
        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname'] = $userinfo['uname'];
            $listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id=' . $v['usergroup_id'])->getField('user_group_name');
            if ($listData['data'][$k]['attach_id']) {
                $a = explode('|', $listData['data'][$k]['attach_id']);
                $listData['data'][$k]['attachment'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= $attachInfo['name'] . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path'] . $attachInfo['save_name']) . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            $listData['data'][$k]['category'] = $categoryHash[$v['user_verified_category_id']];
            $listData['data'][$k]['reason'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
            $listData['data'][$k]['info'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['info']));
            $listData['data'][$k]['DOACTION'] = '<a href="' . U('school/AdminUser/editVerify', array('tabHash' => 'verifiedGroup', 'id' => $v['id'])) . '">编辑</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.getVerifyBox(' . $v['id'] . ')">驳回</a>';
        }
        $this->displayList($listData);
    }

    /**
     * 驳回理由窗口
     * @return void
     */
    public function getVerifyBox()
    {
        $id = intval($_GET['id']);
        $this->assign('id', $id);

        $this->display('verifyBox');
    }

    /**
     * 执行认证
     * @return json 返回操作后的JSON信息数据
     */
    public function doVerify()
    {
        $status = intval($_POST['status']);
        $id = $_POST['id'];
        if (is_array($id)) {
            $map['id'] = array('in', $id);
        } else {
            $map['id'] = $id;
        }
        $datas['verified'] = $status;
        if ($_POST['info']) {
            $datas['info'] = t($_POST['info']);
        }
        $res = D('user_verified')->where($map)->save($datas);
        if ($res) {
            $return['status'] = 1;
            if ($status == 1) {
                if (is_array($id)) {
                    foreach ($id as $k => $v) {
                        $user_group = D('user_verified')->where('id=' . $v)->find();
                        //添加成为老师
                        if ($user_group["user_verified_category_id"] == 6) {
                            $data["uid"] = $user_group['uid'];
                            $data["Teach_areas"] = $user_group['address'];
                            $data["name"] = $user_group['realname'];
                            $data["ctime"] = time();
                            $data['identification'] = date(md) . mt_rand(1000, 9999) . $data['uid'];
                            M("zy_teacher")->add($data);
                        }
                        //结束
                        $maps['uid'] = $user_group['uid'];
                        $maps['user_group_id'] = $user_group['usergroup_id'];
                        $exist = D('user_group_link')->where($maps)->find();
                        if ($exist) {
                            continue;
                        }
                        D('user_group_link')->add($maps);
                        // 清除用户组缓存
                        model('Cache')->rm('user_group_' . $user_group['uid']);
                        // 清除权限缓存
                        model('Cache')->rm('perm_user_' . $user_group['uid']);
                        // 删除微博信息
                        $feed_ids = model('Feed')->where('uid=' . $user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                        model('Feed')->cleanCache($feed_ids);

                        model('Notify')->sendNotify($user_group['uid'], 'admin_user_doverify_ok');
                        unset($user_group);
                        unset($maps);
                    }
                } else {
                    $user_group = D('user_verified')->where('id=' . $id)->find();
                    //添加成为老师
                    if ($user_group["user_verified_category_id"] == 6) {
                        $data["uid"] = $user_group['uid'];
                        $data["name"] = $user_group['realname'];
                        $data["ctime"] = time();
                        $data['identification'] = date(md) . mt_rand(1000, 9999) . $data['uid'];
                        M("zy_teacher")->add($data);
                    }
                    //结束
                    $maps['uid'] = $user_group['uid'];
                    $maps['user_group_id'] = $user_group['usergroup_id'];
                    $exist = D('user_group_link')->where($maps)->find();
                    if (!$exist) {
                        D('user_group_link')->add($maps);
                        // 清除用户组缓存
                        model('Cache')->rm('user_group_' . $user_group['uid']);
                        // 清除权限缓存
                        model('Cache')->rm('perm_user_' . $user_group['uid']);
                        // 删除微博信息
                        $feed_ids = model('Feed')->where('uid=' . $user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                        model('Feed')->cleanCache($feed_ids);

                        model('Notify')->sendNotify($user_group['uid'], 'admin_user_doverify_ok');
                    }
                }

                $return['data'] = "认证成功";
            }
            if ($status == -1) {
                $return['data'] = "驳回成功";
                $rejectInfo = array('reason' => t($_POST['reason']));
                //$data['act'] = '驳回';
                if (is_array($id)) {
                    foreach ($id as $k => $v) {
                        $user_group = D('user_verified')->where('id=' . $v)->find();
                        $maps['uid'] = $user_group['uid'];
                        $maps['user_group_id'] = $user_group['usergroup_id'];
                        D('user_group_link')->where($maps)->delete();
                        // 清除用户组缓存
                        model('Cache')->rm('user_group_' . $user_group['uid']);
                        // 清除权限缓存
                        model('Cache')->rm('perm_user_' . $user_group['uid']);
                        // 删除微博信息
                        $feed_ids = model('Feed')->where('uid=' . $user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                        model('Feed')->cleanCache($feed_ids);

                        model('Notify')->sendNotify($user_group['uid'], 'admin_user_doverify_reject', $rejectInfo);
                        unset($user_group);
                        unset($maps);
                    }
                } else {
                    $user_group = D('user_verified')->where('id=' . $id)->find();
                    $maps['uid'] = $user_group['uid'];
                    $maps['user_group_id'] = $user_group['usergroup_id'];
                    D('user_group_link')->where($maps)->delete();
                    // 清除用户组缓存
                    model('Cache')->rm('user_group_' . $user_group['uid']);
                    // 清除权限缓存
                    model('Cache')->rm('perm_user_' . $user_group['uid']);
                    // 删除微博信息
                    $feed_ids = model('Feed')->where('uid=' . $user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                    model('Feed')->cleanCache($feed_ids);

                    model('Notify')->sendNotify($user_group['uid'], 'admin_user_doverify_reject', $rejectInfo);
                }
            }

        } else {
            $return['status'] = 0;
            $return['data'] = "认证失败";
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 添加认证用户或认证企业
     * @return void
     */
    public function addVerify()
    {
        $this->_initVerifyAdminMenu();
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array(
            'uname',
            'usergroup_id',
            'user_verified_category_id',
            'company',
            'realname',
            'idcard',
            'phone',
            'address',
            'reason',
            'attach',
            'info',
            //'other_data',
        );
        // 字段选项配置
        $auType = model('UserGroup')->where('is_authenticate=1')->select();
        foreach ($auType as $k => $v) {
            $this->opt['usergroup_id'][$v['user_group_id']] = $v['user_group_name'];
        }
        // 认证分类配置
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($categoryHash as $key => $value) {
            $this->opt['user_verified_category_id'][$key] = $value;
        }
        // 表单URL设置
        $this->savePostUrl = U('school/AdminUser/doAddVerify');
        $this->notEmpty = array(
            'uname',
            'usergroup_id',
            'company',
            'realname',
            'idcard',
            'phone',
            'reason',
            'info',
            'attach');
        $this->onload[] = "admin.addVerifyConfig(5)";
        //$this->onsubmit = 'admin.addVerifySubmitCheck(this)';

        $this->displayConfig();
    }

    /**
     * 执行添加认证
     * @return void
     */
    public function doAddVerify()
    {
        $data['uid'] = $_POST['uname'];
        $result = D('user_verified')->where('uid=' . $data['uid'])->find();
        if ($result) {
            if ($result['verified'] == 1) {
                $this->error('该用户已通过认证');
            } else {
                D('user_verified')->where('uid=' . $data['uid'])->delete();
            }
        }

        $data['usergroup_id'] = intval($_POST['usergroup_id']);
        if ($_POST['company']) {
            $data['company'] = t($_POST['company']);
        }
        $data['realname'] = t($_POST['realname']);
        $data['idcard'] = t($_POST['idcard']);
        $data['phone'] = t($_POST['phone']);
        $data['reason'] = t($_POST['reason']);
        $data['info'] = t($_POST['info']);
        $data['school'] = t($_POST['school']);
        $data['specialty'] = t($_POST['specialty']);
        $data['address'] = t($_POST['address']);
        $data['s_address'] = t($_POST['s_address']);
        //	$data['attachment'] = t($_POST['attach']);
        $data['attach_id'] = t($_POST['attach_ids']);
        $data['user_verified_category_id'] = intval($_POST['user_verified_category_id']);
        $Regx1 = '/^[0-9]*$/';
        $Regx2 = '/^[A-Za-z0-9]*$/';
        $Regx3 = '/^[A-Za-z|\x{4e00}-\x{9fa5}]+$/u';
        if ($data['usergroup_id'] == 6) {
            if (strlen($data['company']) == 0) {
                $this->error('企业名称不能为空');
            }
            if (strlen($data['realname']) == 0) {
                $this->error('法人姓名不能为空');
            }
            if (strlen($data['idcard']) == 0) {
                $this->error('营业执照号不能为空');
            }
            if (strlen($data['phone']) == 0) {
                $this->error('联系方式不能为空');
            }
            if (strlen($data['reason']) == 0) {
                $this->error('认证理由不能为空');
            }
            if (strlen($data['info']) == 0) {
                $this->error('认证资料不能为空');
            }
            if (preg_match($Regx2, $data['idcard']) == 0) {
                $this->error('请输入正确的营业执照号');
            }

        } else {
            if (strlen($data['realname']) == 0) {
                $this->error('真实姓名不能为空');
            }
            if (strlen($data['idcard']) == 0) {
                $this->error('身份证号码不能为空');
            }
            if (strlen($data['phone']) == 0) {
                $this->error('手机号码不能为空');
            }
            if (strlen($data['reason']) == 0) {
                $this->error('认证理由不能为空');
            }
            if (strlen($data['info']) == 0) {
                $this->error('认证资料不能为空');
            }
            if (preg_match($Regx3, $data['realname']) == 0 || strlen($data['realname']) > 30) {
                $this->error('请输入正确的姓名格式');
            }
            if (preg_match($Regx2, $data['idcard']) == 0 || preg_match($Regx1, substr($data['idcard'], 0, 17)) == 0 || strlen($data['idcard']) !== 18) {
                $this->error('请输入正确的身份证号码');
            }
            if (strlen($data['phone']) !== 11 || preg_match($Regx1, $data['phone']) == 0) {
                $this->error('请输入正确的手机号码格式');
            }
        }
        // preg_match_all('/./us', $data['reason'], $matchs);   //一个汉字也为一个字符
        // if(count($matchs[0])>140){
        // 	$this->error('认证理由不能超过140个字符');
        // }
        // preg_match_all('/./us', $data['info'], $match);   //一个汉字也为一个字符
        // if(count($match[0])>140){
        // 	$this->error('认证资料不能超过140个字符');
        // }
        $data['verified'] = 1;
        $res = D('user_verified')->add($data);
        $map['uid'] = $_POST['uname'];
        $map['user_group_id'] = intval($_POST['usergroup_id']);
        $res2 = D('user_group_link')->add($map);
        // 清除用户组缓存
        model('Cache')->rm('user_group_' . $map['uid']);
        // 清除权限缓存
        model('Cache')->rm('perm_user_' . $map['uid']);
        if ($res && $res2) {
            if ($_POST["user_verified_category_id"] == 6 && $_POST["usergroup_id"] == 5) {
                $map["uid"] = $_POST['uname'];
                $map["ctime"] = time();
                M("zy_teacher")->add($map);
            }
            $this->success('认证成功');
        } else {
            $this->error('认证失败');
        }
    }

    /**
     * 通过时编辑认证资料
     * @return  void
     */
    public function editVerifyInfo()
    {
        $this->assign('id', intval($_GET['id']));
        $this->assign('status', intval($_GET['status']));
        $verifyInfo = D('user_verified')->where('id=' . intval($_GET['id']))->find();
        $this->assign('info', format($verifyInfo['reason']));
        $this->display();
    }

    /**
     * 编辑认证资料
     * @return void
     */
    public function editVerify()
    {
        $this->_initVerifyAdminMenu();

        $this->pageKeyList = array(
            'uid',
            'uname',
            'usergroup_id',
            'user_verified_category_id',
            'company',
            'realname',
            'idcard',
            'phone',
            'school',
            'specialty',
            'address',
            's_address',
            'reason',
            'info',
            'attach'
        );

        $id = intval($_REQUEST['id']);
        $verifyInfo = D('user_verified')->where('id=' . $id)->find();
        $userinfo = model('user')->getUserInfo($verifyInfo['uid']);
        $verifyInfo['uname'] = $userinfo['uname'];
        // 认证分类配置
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($categoryHash as $key => $value) {
            $this->opt['user_verified_category_id'][$key] = $value;
        }
        // 认证组
        $auType = model('UserGroup')->where('is_authenticate=1')->select();
        foreach ($auType as $k => $v) {
            $this->opt['usergroup_id'][$v['user_group_id']] = $v['user_group_name'];
        }

        $verifyInfo['attach'] = str_replace('|', ',', substr($verifyInfo['attach_id'], 1, strlen($verifyInfo['attach_id']) - 2));

        $this->savePostUrl = U('school/AdminUser/doEditVerify');
        $this->onsubmit = 'admin.editVerifySubmitCheck(this)';
        $this->notEmpty = array(
            'uname',
            'usergroup_id',
            'company',
            'realname',
            'idcard',
            'phone',
            'reason',
            'info',
            'school',
            'specialty',
            'attach');
        $this->onload[] = "admin.addVerifyConfig({$verifyInfo['usergroup_id']})";
        $this->displayConfig($verifyInfo);
    }

    /**
     * 执行编辑认证资料
     * @return void
     */
    public function doEditVerify()
    {
        $uid = intval($_POST['uid']);
        $old_group_id = D('user_verified')->where('uid=' . $uid)->getField('usergroup_id');
        $data['usergroup_id'] = intval($_POST['usergroup_id']);
        if ($data['usergroup_id'] == 6) {
            $data['company'] = t($_POST['company']);
        }
        $data['realname'] = t($_POST['realname']);
        $data['idcard'] = t($_POST['idcard']);
        $data['phone'] = t($_POST['phone']);
        $data['reason'] = t($_POST['reason']);
        $data['info'] = t($_POST['info']);
        $data['school'] = t($_POST['school']);
        $data['specialty'] = t($_POST['specialty']);
        $data['address'] = t($_POST['address']);
        $data['s_address'] = t($_POST['s_address']);
        if (t($_POST['attach_ids'])) {
            $data['attach_id'] = t($_POST['attach_ids']);
        }
        $data['user_verified_category_id'] = intval($_POST['user_verified_category_id']);
        //dump($data);exit;
        $res = D('user_verified')->where('uid=' . $uid)->save($data);
        if ($old_group_id != $data['usergroup_id']) {
            D('user_group_link')->where('uid=' . $uid . ' and user_group_id=' . $old_group_id)->setField('user_group_id', $data['usergroup_id']);
        }
        // 清除用户组缓存
        model('Cache')->rm('user_group_' . $uid);
        // 清除权限缓存
        model('Cache')->rm('perm_user_' . $uid);
        if ($res) {
            $this->success('编辑成功');
        } else {
            $this->error('编辑失败');
        }
    }

    public function getVerifyCategory()
    {
        $category = D('user_verified_category')->where('pid=' . intval($_POST['value']))->findAll();
        foreach ($category as $k => $v) {
            $option .= '<option ';
            // if(intval($_POST['category_id'])==$v['user_verified_category_id']){
            // 	$option[$v['pid']] .= 'selected';
            // }
            $option .= ' value="' . $v['user_verified_category_id'] . '">' . $v['title'] . '</option>';
        }
        echo $option;
    }

    /**
     * 推荐标签 - 列表显示
     */
    public function category()
    {
        $_GET['pid'] = intval($_GET['pid']);
        $treeData = model('CategoryTree')->setTable('user_category')->getNetworkList();
        // 配置删除关联信息
        $this->displayTree($treeData, 'user_category', 2, '', '', 10);
    }

    /**
     * 认证分类展示页面
     * @return void
     */
    public function verifyCategory()
    {
        // 初始化Tab信息
        $this->_initVerifyAdminMenu();
        // 分类相关数据
        //$_GET['pid'] = intval($_GET['pid']);
        //$treeData = model('CategoryTree')->setTable('user_verified_category')->getNetworkList();

        //$this->displayTree($treeData, 'user_verified_category');

        //分类列表key值 DOACTION表示操作
        $this->pageKeyList = array('user_verified_category_id', 'title', 'pCategory', 'DOACTION');

        //列表批量操作按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'onclick' => "admin.addVerifyCategory()");

        //取用户列表
        $listData = D('user_verified_category')->findpage(20);
        //数据格式化
        foreach ($listData['data'] as $k => $v) {
            $listData['data'][$k]['pCategory'] = model('UserGroup')->where('is_authenticate=1 AND user_group_id=' . $v['pid'])->getField('user_group_name');

            //操作按钮

            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0);" onclick="admin.editVerifyCategory(' . $v['user_verified_category_id'] . ')">' . L('PUBLIC_EDIT') . '</a> '
                . ($v['user_verified_category_id'] == 6 ? ' ' : ' - <a href="javascript:void(0)" onclick="admin.delVerifyCategory(' . $v['user_verified_category_id'] . ')">' . L('PUBLIC_STREAM_DELETE') . '</a>');
        }

        //$this->_listpk = 'field_id';
        $this->allSelected = false;
        $this->displayList($listData);
    }

    /**
     * 添加认证分类
     * @return void
     */
    public function addVerifyCategory()
    {
        $vType = model('UserGroup')->where('is_authenticate=1')->findAll();
        $this->assign('vType', $vType);
        $this->display('editVerifyCategory');
    }

    /**
     * 编辑认证分类
     * @return void
     */
    public function editVerifyCategory()
    {
        $vType = model('UserGroup')->where('is_authenticate=1')->findAll();
        $this->assign('vType', $vType);
        $user_verified_category_id = intval($_GET['user_verified_category_id']);
        $cateInfo = D('user_verified_category')->where('user_verified_category_id=' . $user_verified_category_id)->find();
        $this->assign('cateInfo', $cateInfo);
        $this->display('editVerifyCategory');
    }

    /**
     * 执行添加认证分类
     */
    public function doAddVerifyCategory()
    {
        $data['pid'] = intval($_POST['pid']);
        $data['title'] = t($_POST['title']);
        if (D('user_verified_category')->where($data)->find()) {
            $return['status'] = 0;
            $return['data'] = '此分类已存在';
        } else {
            if (D('user_verified_category')->add($data)) {
                $return['status'] = 1;
                $return['data'] = '添加成功';
            } else {
                $return['status'] = 0;
                $return['data'] = '添加失败';
            }
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 执行编辑认证分类
     */
    public function doEditVerifyCategory()
    {
        $data['pid'] = intval($_POST['pid']);
        $data['title'] = t($_POST['title']);
        $user_verified_category_id = intval($_POST['user_verified_category_id']);
        if (D('user_verified_category')->where($data)->find()) {
            $return['status'] = 0;
            $return['data'] = '此分类已存在';
        } else {
            $old_pid = D('user_verified_category')->where('user_verified_category_id=' . $user_verified_category_id)->getField('pid');
            if (D('user_verified_category')->where('user_verified_category_id=' . $user_verified_category_id)->save($data) !== false) {
                if ($old_pid != $data['pid']) {
                    D('user_verified')->where('user_verified_category_id=' . $user_verified_category_id)->setField('usergroup_id', $data['pid']);
                    $datas['uid'] = array('in', getSubByKey(D('user_verified')->where('user_verified_category_id=' . $user_verified_category_id)->field('uid')->findAll(), 'uid'));
                    $datas['user_group_id'] = $old_pid;
                    D('user_group_link')->where($datas)->setField('user_group_id', $data['pid']);
                }
                $return['status'] = 1;
                $return['data'] = '编辑成功';
            } else {
                $return['status'] = 0;
                $return['data'] = '编辑失败';
            }
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 删除认证分类
     */
    public function delVerifyCategory()
    {
        $user_verified_category_id = intval($_POST['user_verified_category_id']);
        if (D('user_verified_category')->where('user_verified_category_id=' . $user_verified_category_id)->delete()) {
            $return['status'] = 1;
            $return['data'] = '删除成功';
        } else {
            $return['status'] = 0;
            $return['data'] = '删除失败';
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 认证用户基本配置
     * @return void
     */
    public function verifyConfig()
    {
        // 配置用户基本信息
        $this->_initVerifyAdminMenu();
        // 配置用户存储基本字段
        $this->pageKeyList = array('top_user');
        // 显示配置列表
        $this->displayConfig();
    }

    /**
     * 找人全局
     */
    public function findPeopleConfig()
    {
        // tab选项
        $this->pageTab[] = array('title' => '找人配置', 'tabHash' => 'findPeopleConfig', 'url' => U('school/AdminUser/findPeopleConfig'));
        // 配置用户存储基本字段
        $this->pageKeyList = array('findPeople');
        $findtype['tag'] = '按标签';
        $findtype['area'] = '按地区';
        $findtype['verify'] = '认证用户';
        $findtype['official'] = '官方推荐';
        $this->opt['findPeople'] = $findtype;
        // 显示配置列表
        $this->displayConfig();
    }

    /**
     * 官方用户配置
     * @return void
     */
    public function official()
    {
        // 初始化
        $this->_officialInit();
        // 配置用户存储基本字段
        $this->pageKeyList = array('top_user');
        // 显示配置列表
        $this->displayConfig();
    }

    /*** 官方用户 ***/

    /**
     * 官方用户分类
     * @return void
     */
    public function officialCategory()
    {
        // 初始化
        $this->_officialInit();
        // 获取分类信息
        $_GET['pid'] = intval($_GET['pid']);
        $treeData = model('CategoryTree')->setTable('user_official_category')->getNetworkList();
        // 删除分类关联信息
        $delParam['module'] = 'UserOfficial';
        $delParam['method'] = 'deleteAssociatedData';
        $this->displayTree($treeData, 'user_official_category', 1, $delParam);
    }

    /**
     * 官方用户列表
     */
    public function officialList()
    {
        // 设置列表主键
        $this->_listpk = 'official_id';
        // 初始化
        $this->_officialInit();
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => '移除', 'onclick' => "admin.removeOfficialUser()");
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('official_id', 'uid', 'uname', 'title', 'info', 'DOACTION');
        // 获取用户列表
        $listData = model('UserOfficial')->getUserOfficialList();
        // 组装数据
        foreach ($listData['data'] as &$value) {
            $user_category = model('CategoryTree')->setTable('user_official_category')->getCategoryById($value['user_official_category_id']);
            $value['title'] = $user_category['title'];
            $value['DOACTION'] = '<a href="javascript:;" onclick="admin.removeOfficialUser(' . $value['official_id'] . ')">移除</a>';
        }

        $this->displayList($listData);
    }

    /**
     * 添加官方用户界面
     * @return void
     */
    public function officialAddUser()
    {
        $_REQUEST['tabHash'] = 'officialAddUser';
        // 初始化
        $this->_officialInit();
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('uids', 'category', 'info');
        // 字段选项配置
        $this->opt['category'] = model('CategoryTree')->setTable('user_official_category')->getCategoryHash();
        // 表单URL设置
        $this->savePostUrl = U('school/AdminUser/doOfficialAddUser');
        $this->notEmpty = array('uids', 'category');

        $this->displayConfig();
    }

    /**
     * 添加官方用户操作
     * @return void
     */
    public function doOfficialAddUser()
    {
        //dump($_REQUEST);exit;
        if (empty($_REQUEST['uids']) || empty($_REQUEST['category'])) {
            $this->error('请添加用户');
            return false;
        }
        $uids = t($_REQUEST['uids']);
        $cid = intval($_REQUEST['category']);
        $info = t($_REQUEST['info']);
        $result = model('UserOfficial')->addOfficialUser($uids, $cid, $info);
        // 添加后跳转
        if ($result) {
            $this->assign('jumpUrl', U('school/AdminUser/officialAddUser'));
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 移除官方用户操作
     * @return json 操作后返回的JSON数据
     */
    public function doRemoveOfficialUser()
    {
        $ids = t($_POST['id']);
        $res = array();
        if (empty($ids)) {
            $res['status'] = 0;
            $res['data'] = '请选择用户';
        } else {
            // 删除操作
            $result = model('UserOfficial')->removeUserOfficial($ids);
            // 返回结果集
            if ($result) {
                $res['status'] = 1;
                $res['data'] = '操作成功';
            } else {
                $res['status'] = 0;
                $res['data'] = '操作失败';
            }
        }
        exit(json_encode($res));
    }

    /**
     * 初始化官方用户Tab标签选项
     * @return void
     */
    private function _officialInit()
    {
        $this->pageTab[] = array('title' => '推荐分类', 'tabHash' => 'officialCategory', 'url' => U('school/AdminUser/officialCategory'));
        $this->pageTab[] = array('title' => '置顶用户', 'tabHash' => 'official', 'url' => U('school/AdminUser/official'));
        $this->pageTab[] = array('title' => '添加推荐用户', 'tabHash' => 'officialAddUser', 'url' => U('school/AdminUser/officialAddUser'));
        $this->pageTab[] = array('title' => '已推荐用户', 'tabHash' => 'officialList', 'url' => U('school/AdminUser/officialList'));
    }


    public function hideStar($str)
    { //用户名、邮箱、手机账号中间字符串以*隐藏
        if (strpos($str, '@')) {
            $email_array = explode("@", $str);
            $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($str, 0, 3); //邮箱前缀
            $count = 0;
            $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $str, -1, $count);
            $rs = $prevfix . $str;
        } else {
            $pattern = '/(1[3458]{1}[0-9])[0-9]{4}([0-9]{4})/i';
            if (preg_match($pattern, $str)) {
                $rs = preg_replace($pattern, '$1****$2', $str); // substr_replace($name,'****',3,4);
            } else {
                $rs = substr($str, 0, 3) . "***" . substr($str, -1);
            }
        }
        return $rs;
    }
}