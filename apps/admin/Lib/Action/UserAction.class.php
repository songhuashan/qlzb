<?php
/**
 * 后台，用户管理控制器
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
// 加载后台控制器
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');
tsload ( APPS_PATH . '/live/Lib/Action/IndexAction.class.php' );
class UserAction extends AdministratorAction
{

    public $pageTitle = array();

    /**
     * 初始化，初始化页面表头信息，用于双语
     */
    public function _initialize()
    {
        $this->pageTitle['index']               = L('PUBLIC_USER_MANAGEMENT');
        //$this->pageTitle['pending']             = L('PUBLIC_PENDING_LIST');
        $this->pageTitle['profile']             = L('PUBLIC_PROFILE_SETTING');
        $this->pageTitle['profileCategory']     = L('PUBLIC_PROFILE_SETTING');
        $this->pageTitle['dellist']             = L('PUBLIC_DISABLE_LIST');
        $this->pageTitle['online']              = '在线用户列表';
        $this->pageTitle['addUser']             = L('PUBLIC_ADD_USER_INFO');
        $this->pageTitle['editUser']            = L('PUBLIC_EDIT_USER');
        $this->pageTitle['addProfileField']     = L('PUBLIC_ADD_FIELD');
        $this->pageTitle['editProfileField']    = L('PUBLIC_EDIT_FIELD');
        $this->pageTitle['addProfileCategory']  = L('PUBLIC_ADD_FIELD_CLASSIFICATION');
        $this->pageTitle['editProfileCategory'] = L('PUBLIC_EDITCATEOGRY');
        $this->pageTitle['verify']              = '待认证用户';

        $this->pageTitle['verified'] = '已认证用户';

//        $this->pageTitle['schoolVerify'] = '待机构认证用户';

        $this->pageTitle['addVerify']        = '添加认证';
        $this->pageTitle['category']         = '推荐标签';
        $this->pageTitle['verifyCategory']   = '认证分类';
        $this->pageTitle['verifyConfig']     = '认证配置';
        $this->pageTitle['official']         = '官方用户配置';
        $this->pageTitle['officialCategory'] = '官方用户分类';
        $this->pageTitle['officialList']     = '官方用户列表';
        $this->pageTitle['officialAddUser']  = '添加官方用户';
        $this->pageTitle['findPeopleConfig'] = '全局配置';
        $this->user                          = model("UserGroupLink")->where("uid=" . $this->mid)->find();
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
        $this->pageTitle['index'] = '列表';
        // 数据的格式化与listKey保持一致

        $listData = $this->_getUserList('100', [], 'index');
        
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => L('搜索'), 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => L('PUBLIC_TRANSFER_USER_GROUP'), 'onclick' => "admin.changeUserGroup()");
        $this->pageButton[] = array('title' => L('导出'), 'onclick' => "admin.exportUser()");
        $this->pageButton[] = array('title' => L('禁用'), 'onclick' => "admin.delUser()");
        $this->pageButton[] = array('title' => L('批量删除'), 'onclick' => "admin.trueDelUser()");
        // 转移用户部门，如果需要请将下面的注释打开
        //$this->pageButton[] = array('title'=>L('PUBLIC_TRANSFER_DEPARTMENT'),'onclick'=>"admin.changeUserDepartment()");
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
        $this->pageButton[] = array('title' => L('搜索'), 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => L('通过'), 'onclick' => "admin.auditUser('',1)");

        $this->displayList($listData);
    }

    public function importUser()
    {
        $this->pageTitle['importUser'] = '导入';
        $this->_initUserListAdminMenu();
        $this->pageKeyList = array('file');
        // 表单URL设置
        $this->savePostUrl = U('admin/User/doImportUser');

        $this->displayConfig();

    }
    

    
    public function doImportUser()
    {
        
        
        
        
        
        
        $attach_id = trim($_POST['file_ids'], '|') ?: 0;
        if ($attach_id) {
            $attach = model('Attach')->getAttachById($attach_id);
            if (!in_array($attach['extension'], ['xls', 'xlsx'])) {
                $this->error('请重新上传导入附件');
            } else {
                //检测文件是否存在
                
                
            
                $file_path = implode(DIRECTORY_SEPARATOR, array(SITE_PATH, 'data', 'upload', $attach['save_path'] . $attach['save_name']));
                $excel = model('Excel')->import($file_path,false);
                $sheet = $excel->getActiveSheet(0);
                $data  = $sheet->toArray();
      
                $field = array('uname', 'password', 'email', 'phone', 'sex', 'intro');
                //循环获取excel中的值
                $failtel="";
                $add_count   = 0;
                $total_count = 0;
                if (!empty($data)) {
                    $mod             = model('User');
                    $_register_model = model('Register');
                    
                    
                
                    foreach ($data as $key => $value) {
                        if ($key > 0 && $value[0]) {
                            $total_count++;
                            $password = $value[1] ?: '123456';
                            $add      = array(
                                'uname' => t($value[0]),
                                'sex'   => $value[4] == '男' ? 1 : 2,
                                'intro' => $value[5] ?: '',
                                'group' => $value[6] ?: '',
                            );
                          
                            $map['uname'] = $add['uname'];
                          
                            // 手机
                            if ($value[3] != '' && M('user')->where(array('phone' => $value[3]))->count() < 1) {
                                $add['phone'] = $add['login'] = $value[3];
                                $map['phone'] = $add['phone'];
                            }
                            if (M('user')->where(array('phone' => $value[3]))->count()>0) {
                               $failtel.=$value[3].",";
                            }
                            
                            
                           
                            
                            
                            if (!$add['login']) {
                                continue;
                            }

                          
                            $login_salt = rand(11111, 99999);
                            //获取默认机构
                            $default_school = model('School')->getDefaultSchol('id');
                            $ext_data   = array(
                                'update_time' => time(),
                                'password'    => md5(md5($password) . $login_salt),
                                'reg_ip'      => get_client_ip(),
                                'ctime'       => time(),
                                'is_audit'    => 1,
                                'is_active'   => 1,
                                'is_init'     => 1,
                                'login_salt'  => $login_salt,
                                'mhm_id'      => $default_school,
                            );
                            $ext_data['first_letter'] = getFirstLetter($add['uname']);
                            //如果包含中文将中文翻译成拼音
                            if (preg_match('/[\x7f-\xff]+/', $add['uname'])) {
                                //昵称和呢称拼音保存到搜索字段
                                $ext_data['search_key'] = $add['uname'] . ' ' . model('PinYin')->Pinyin($add['uname']);
                            } else {
                                $ext_data['search_key'] = $add['uname'];
                            }
                            $add = array_merge($add, $ext_data);
                     
               
                         if ($uid = $mod->add($add)) {

                                $add_count++;
                                // 添加积分
                                model('Credit')->setUserCredit($uid, 'init_default');
                                // 添加至默认的用户组
                                
                            
                                $userGroup = model('Xdata')->get('admin_Config:register');
                                if(empty($add['group'])){
                                $userGroupid=$userGroup['default_user_group'];
                                }else{
                                $userGroupid['0']=$add['group'];    
                                    
                                }
                            
                                model('UserGroupLink')->domoveUsergroup($uid,implode(',', $userGroupid) );
                             
                                
                                $auth['oauth_token']        = getOAuthToken($uid); //添加app认证
                                $auth['oauth_token_secret'] = getOAuthTokenSecret();
                                $auth['type']               = t($this->data['type_oauth']);
                                $auth['type_uid']           = t($this->data['type_uid']);
                                $auth['uid']                = $uid;
                                M('login')->add($auth);
                            }
                        }
                    }
                }
                if ($add_count > 0) {
                    
                    if(empty($failtel)){
                    $this->jumpUrl = U('admin/User/index');
                    $this->success('共计' . $total_count . '个用户,本次成功导入' . $add_count . '个用户');
                    }else{
                    echo    '共计' . $total_count . '个用户,本次成功导入' . $add_count . '个用户，重复数号码为'.$failtel; 
                    exit();
                    }
                
                } else {
                    $this->error('导入失败,请检查数据格式或用户是否重复导入');
                }
            }

        }
        $this->error('请重新上传导入附件');
    }
    /**
     * 用户管理 - 禁用列表
     */
    public function dellist()
    {
        $_REQUEST['tabHash'] = 'dellist';
        // 初始化禁用列表管理菜单
        $this->_initUserListAdminMenu('dellist');
        $this->pageTitle['dellist'] = '禁用';
        // 数据的格式化与listKey保持一致
        $listData = $this->_getUserList(20, array('is_del' => '1'), 'dellist');
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => L('搜索'), 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => L('恢复'), 'onclick' => "admin.rebackUser()");

        $this->displayList($listData);
    }

    /**
     * 用户管理 - 在线用户列表
     */
    public function online()
    {
        $_REQUEST['tabHash'] = 'online';
        // tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_USER_LIST'), 'tabHash' => 'index', 'url' => U('admin/User/index'));
        $this->pageTab[] = array('title' => L('PUBLIC_PENDING_LIST'), 'tabHash' => 'pending', 'url' => U('admin/User/pending'));
        $this->pageTab[] = array('title' => L('PUBLIC_DISABLE_LIST'), 'tabHash' => 'dellist', 'url' => U('admin/User/dellist'));
        // $this->pageTab[] = array('title'=>'在线用户列表','tabHash'=>'online','url'=>U('admin/User/online'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_USER_INFO'), 'tabHash' => 'addUser', 'url' => U('admin/User/addUser'));
        // 搜索选项的key值
        $this->searchKey = array('uid', 'uname', 'email', 'sex', 'user_group', array('ctime', 'ctime1'));
        // 针对搜索的特殊选项
        $this->opt['sex']        = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));
        $this->opt['identity']   = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_PERSONAL'), '2' => L('PUBLIC_ORGANIZATION'));
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
        $_REQUEST['tabHash']       = 'viewIP';
        $uid                       = intval($_REQUEST['uid']);
        $userInfo                  = model('User')->getUserInfo($uid);
        $this->pageTitle['viewIP'] = '查看IP - 用户：' . $userInfo['uname'] . '（' . $userInfo['email'] . '）';
        // tab选项
        $this->pageTab[] = array('title' => '查看IP', 'tabHash' => 'viewIP', 'url' => U('admin/User/viewIP', array('tabHash' => 'viewIP', 'uid' => $uid)));
        $this->pageTab[] = array('title' => '登录日志', 'tabHash' => 'loginLog', 'url' => U('admin/User/loginLog', array('tabHash' => 'loginLog', 'uid' => $uid)));
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
        $_REQUEST['tabHash']       = 'tryout';
        $this->pageTitle['tryout'] = '一键试用用户';
        $this->pageTab[]           = array('title' => '试用用户', 'tabHash' => 'tryout', 'url' => U('admin/User/tryout'));
        //$this->pageButton[] = array('title'=>'删除用户','onclick'=>"javascript:;");
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
        $_REQUEST['tabHash']         = 'loginLog';
        $uid                         = intval($_REQUEST['uid']);
        $userInfo                    = model('User')->getUserInfo($uid);
        $this->pageTitle['loginLog'] = '登录日志 - 用户：' . $userInfo['uname'] . '（' . $userInfo['email'] . '）';
        // tab选项
        $this->pageTab[] = array('title' => '查看IP', 'tabHash' => 'viewIP', 'url' => U('admin/User/viewIP', array('tabHash' => 'viewIP', 'uid' => $uid)));
        $this->pageTab[] = array('title' => '登录日志', 'tabHash' => 'loginLog', 'url' => U('admin/User/loginLog', array('tabHash' => 'loginLog', 'uid' => $uid)));
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('login_logs_id', 'ip', 'ctime', 'DOACTION');
        // 获取相关数据
        $map['uid'] = $uid;
        $listData   = D('login_logs')->where($map)->findPage(20);
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
        $ids       = $_GET["ids"];
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
        $ids       = $_POST["ids"];
        $user_list = explode(",", $ids);
        $video_ids = explode(",", $video_ids);
        foreach ($user_list as $key => $value) {
            foreach ($video_ids as $k => $v) {
                $data = array(
                    'user_id'  => $value,
                    'video_id' => $v,
                    'admin_id' => $this->mid,
                    'time'     => time(),
                );
                $res = M("user_video")->where('user_id=' . $value . " and video_id=" . $v . " and is_del=0")->find();
                if (!$res) {
                    M("user_video")->add($data);
                }
            }
        }
        $this->assign('jumpUrl', U('admin/User/index'));
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
        $uids     = getSubByKey($listData['data'], 'uid');
        $ipData   = D('Online')->getLastOnlineInfo($uids);
        $ipKey    = array_keys($ipData);
        // 数据格式化
        foreach ($listData['data'] as $k => $v) {
            $listData['data'][$k]['uname'] = '<a href="' . U('admin/User/editUser', array('tabHash' => 'editUser', 'uid' => $v['uid'])) . '">' . $v['uname'] . '</a> (' . $v['email'] . ')';
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
        $this->pageTab[] = array('title' => L('列表'), 'tabHash' => 'index', 'url' => U('admin/User/index'));
        //$this->pageTab[] = array('title' => L('PUBLIC_PENDING_LIST'), 'tabHash' => 'pending', 'url' => U('admin/User/pending'));
        $this->pageTab[] = array('title' => L('禁用'), 'tabHash' => 'dellist', 'url' => U('admin/User/dellist'));
        // $this->pageTab[] = array('title'=>'在线用户列表','tabHash'=>'online','url'=>U('admin/User/online'));
        $this->pageTab[] = array('title' => L('添加'), 'tabHash' => 'addUser', 'url' => U('admin/User/addUser'));
        $this->pageTab[] = array('title' => L('导入'), 'tabHash' => 'importUser', 'url' => U('admin/User/importUser'));

        // 搜索选项的key值
        // $this->searchKey = array('uid','uname','email','sex','department','user_group',array('ctime','ctime1'));
        //$this->searchKey = array('id', 'uid', 'email', 'phone', 'sex', 'user_group', 'user_category', array('ctime', 'ctime1'));
        
        //2019.2.27去掉姓名搜索只保留手机号
        
        $this->searchKey = array('uname','phone', 'sex', 'user_group', 'user_category', array('ctime', 'ctime1'));

        // 针对搜索的特殊选项
        $this->opt['sex']      = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));
        $this->opt['identity'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_PERSONAL'), '2' => L('PUBLIC_ORGANIZATION'));
        //$this->opt['user_group'] = array_merge(array('0'=>L('PUBLIC_SYSTEMD_NOACCEPT')),model('UserGroup')->getHashUsergroup());
        $this->opt['user_group']    = model('UserGroup')->getHashUsergroup();
        $this->opt['user_group'][0] = L('PUBLIC_SYSTEMD_NOACCEPT');

        $map['pid']      = array('NEQ', 0);
        $categoryList    = model('UserCategory')->getAllHash($map);
        $categoryList[0] = L('PUBLIC_SYSTEMD_NOACCEPT');
        ksort($categoryList);

        $this->opt['user_category'] = $categoryList;
        //$this->opt['department_id'] = model('Department')->getHashDepartment();

        // 列表key值 DOACTION表示操作
        switch (strtolower($type)) {
            case 'index':
            case 'dellist':
                $this->pageKeyList = array('uid', 'uname', 'phone', 'user_group', 'location', 'mhm_id', 'is_audit', 'is_active', 'is_init', 'browser_and_ver', 'os', 'place', 'ctime', 'reg_ip', 'DOACTION');
                break;
            case 'pending':
                $this->pageKeyList = array('uid', 'uname', 'location', 'mhm_id', 'browser_and_ver', 'os', 'place', 'ctime', 'reg_ip', 'DOACTION');
                break;
        }

/*        if(!empty($_POST['_parent_dept_id'])) {
$this->onload[] = "admin.departDefault('".implode(',', $_POST['_parent_dept_id'])."','form_user_department')";
}*/
    }

    public function exportUser()
    {
        $uids       = explode(",", $_GET["uid"]);
        $map["uid"] = array("in", $uids);
        $listData   = model('User')->where($map)->field("uid,uname,email,phone,sex,mhm_id,reg_ip,ctime")->order('uid desc')->select();
        if(!$listData){
            $this->error("暂无用户可导出");
        }
        $xlsCell = [
            ['uid', '用户ID'],
            ['uname', '用户昵称'],
            ['email', '邮箱'],
            ['phone', '电话号码'],
            ['sex', '性别'],
            ['mhm_id', '所属机构'],
            ['reg_ip', '注册IP'],
            ['ctime', '注册时间']
        ];
        if ($listData) {
            foreach ($listData as &$v) {
                $v['sex'] = $v['sex'] == 1 ? "男" : "女";
                $v['mhm_id'] = model('School')->getSchooldStrByMap(['id'=>$v['mhm_id']],'title');
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

        $listData = model('User')->getUserList($limit, $map);

        // 数据格式化
        foreach ($listData['data'] as $k => $v) {
            $school_info                             = M('school')->where('id = ' . $v['mhm_id'])->field('title,doadmin')->find();
            $listData['data'][$k]['mhm_id']          = getQuickLink(getDomain($school_info['doadmin']), $school_info['title'], '未知机构');
            $listData['data'][$k]['browser_and_ver'] = $v['browser'] . " " . $v['browser_ver'];
            $listData['data'][$k]['place']           = trim($v['place']) ?: '<span style="color: red;">未知地区</span>';
            if ($v["parent_id"] > 0) {
                $parent                         = M("user")->where("uid=" . $v["parent_id"])->find();
                $listData['data'][$k]['parent'] = $parent["uname"];
            } else {
                $listData['data'][$k]['parent'] = "没有父级";
            }
            // 获取用户身份信息
            $userTag       = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($v['uid']);
            $userTagString = '';
            $userTagArray  = array();
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
            $listData['data'][$k]['uname'] = '<a href="' . U('admin/User/editUser', array('tabHash' => 'editUser', 'uid' => $v['uid'])) . '">' . $v['uname'] . '</a>' . $userGroupIcon[$v['uid']] . ' <br/>' . $v['email'] . ' ' . $userTagString;
            $listData['data'][$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            // 屏蔽部门信息，若要开启将下面的注释打开
            /*            $department = model('Department')->getUserDepart($v['uid']);
            $listData['data'][$k]['department'] = str_replace('|', ' - ',trim($department[$v['uid']],'|'));*/
            $listData['data'][$k]['identity'] = ($v['identity'] == 1) ? L('PUBLIC_PERSONAL') : L('PUBLIC_ORGANIZATION');
            $status = M('contract')->field('status')->where(['uid'=>$v['uid']])->find();
            $status = $status['status'];
            switch (strtolower($type)) {
                case 'index':
                case 'dellist':
                    // 列表数据
                    $listData['data'][$k]['is_active'] = ($v['is_active'] == 1) ? '<span style="color:blue;cursor:auto;">' . L('SSC_ALREADY_ACTIVATED') . '</span>' : '<a href="javascript:void(0)" onclick="admin.activeUser(\'' . $v['uid'] . '\',1)" style="color:red">' . L('PUBLIC_NOT_ACTIVATED') . '</a>';
                    $listData['data'][$k]['is_audit']  = ($v['is_audit'] == 1) ? '<span style="color:blue;cursor:auto;">' . L('PUBLIC_AUDIT_USER_SUCCESS') . '</span>' : '<a href="javascript:void(0)" onclick="admin.auditUser(\'' . $v['uid'] . '\',1)" style="color:red">' . L('PUBLIC_AUDIT_USER_ERROR') . '</a>';
                    $listData['data'][$k]['is_init']   = ($v['is_init'] == 1) ? '<span style="cursor:auto;">' . L('PUBLIC_SYSTEMD_TRUE') . '</span>' : '<span style="cursor:auto;">' . L('PUBLIC_SYSTEMD_FALSE') . '</span>';
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

                    $listData['data'][$k]['DOACTION'] = '<a href="' . U('admin/User/editUser', array('tabHash' => 'editUser', 'uid' => $v['uid'])) . '">' . L('PUBLIC_EDIT') . '</a> - ';
                    $listData['data'][$k]['DOACTION'] .= $v['is_del'] == 1 ? '<a href="javascript:void(0)" onclick="admin.rebackUser(\'' . $v['uid'] . '\')">' . L('PUBLIC_RECOVER') . '</a> - ' : '<a href="javascript:void(0)" onclick="admin.delUser(\'' . $v['uid'] . '\')">' . L('PUBLIC_SYSTEM_NOUSE') . '</a> - ';
                    $listData['data'][$k]['DOACTION'] .= '<a target="_blank" href=" ' . U('admin/User/learn', array('uid' => $v['uid'])) . ' ">学习记录</a> - ';
                    $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.trueDelUser(\''.$v['uid'].'\')">'.L('PUBLIC_REMOVE_COMPLETELY').'</a> - ';
                    $listData['data'][$k]['DOACTION'] .= '<a onclick="ui.sendmessage(\'' . $v['uid'] . '\', 0)" href="javascript:void(0)"  event-node="postMsg" class="sx">发私信</a> - ';
                    // $listData['data'][$k]['DOACTION'] .= '<a href="'.U('admin/User/viewIP',array('tabHash'=>'viewIP','uid'=>$v['uid'])).'">签合同</a>';
                    if($status == 0){
                        $statu = '签合同';
                        $click = '';
                    }else if ($status == 1) {
                        $statu = '已发送';
                        $click = '';
                    }else if ($status == 2) {
                        $statu = '已签名';
                        $click = '';
                    }
                    $listData['data'][$k]['DOACTION'] .= '<a data-status="'.$status.'" href="javascript:void(0)" data-id="'.$v['uid'].'" >
                    '.$statu.'</a><div style="display:none;"><ul><li  onclick="contract('.$v['uid'].',1)">学历专用</li><li onclick="contract('.$v['uid'].',2)">职业资格专用</li></ul></div> ';
                    // $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" data-id = '.$v['uid'].'>签合同</a> ';
                    // $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.auditUser(\'' . $v['uid'] . '\', 1)">签合同</a>';

                    break;
                case 'pending':
                    // 操作数据
                    $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.auditUser(\'' . $v['uid'] . '\', 1)">' . L('PUBLIC_AUDIT_USER_SUCCESS') . '</a>';
                    break;
            }
        }
        return $listData;
    }

    //签合同
    public function  contract()
    {
        
        $uid  = $_REQUEST['uid'];
        $type = $_REQUEST['type'];
        $data = $_POST;
       
        $id = $_GET['id'];
        
        if(!empty($id)){
            $res = M('contract')->where(['uid'=>$id])->find();
            $res = array_filter($res);
            

            foreach($res as $k => $v){
                $gettype = strpos($v,',');
                if(gettype($gettype) != 'boolean'){
                    $v = explode(",", $v);
                    $res[$k] = $v;
                }
                
            }
            $this->assign("res",$res);
        }

        if(!empty($data)){
            if(empty($_POST['student_name'])){
                $this->error('请填写学员信息！');
            }
            foreach($data as $k => $v){
                if(is_array($v)){
                    $v = array_filter($v);
                    $v = implode($v, ',');
                    $data[$k] = $v;
                }
                if(empty($v)){
                    unset($data[$k]);
                } 
            }
            $data['uid'] = $uid;
            $data['contract_type'] = $type;
            $data['status'] = 1;
            
            $res = M('contract')->add($data);
            
            if($res){
                $data_array = array();
                $data_array['msg'] = 1;
                    $this->assign('jumpUrl', U('admin/User/index'));
                    $this->success('提交成功');
                } else {
                    $this->error($user->getLastError());
                }
          
        }else{

            
            $this->assign("type",$type);
            $this->display();
        }
        

        

    }


    //学习记录
    public function learn($limit = 20)
    {
        $this->_initUserListAdminMenu();
        $_REQUEST['tabHash'] = 'learn';
        $uid                 = intval($_GET['uid']);
        // $this->pageButton[]  = array('title' => '获取roomId', 'onclick' => "admin.userLiveRecord('$uid')");
        // $this->pageButton[]  = array('title' => '获取观看记录', 'onclick' => "admin.userWatchList('$uid')");

        $this->pageButton[]  = array('title' => '①获取roomId', 'onclick' => "admin.userLiveRecord('$uid')");
        $this->pageButton[]  = array('title' => '②获取观看记录', 'onclick' => "admin.userWatchList('$uid')");
        $this->pageButton[]  = array('title' => '重新获取请删除当前记录', 'onclick' => "admin.delLearnAll('$uid')");
        // $this->pageButton[]  = array('title' => '批量删除', 'onclick' => "admin.delLearnAll()");
        //$this->pageKeyList   = array('id', 'user_title', 'video_title', 'sid', 'ctime','name' ,'city','enterTime','leaveTime','watchTime','DOACTION');
        $this->pageKeyList   = array('id', 'roomName', 'enterTime','leaveTime','watchTime','recordType','recordId','roomId','liveId','ip','createTime');

        $this->searchKey     = array('id', 'uid', 'video_title', 'sid');
//        $video_title = M('zy_video')->where('id='.$id)->getField("video_title");
        
        $userName = M("user")->where("uid=".$uid)->field("login,uname")->find();
        
        $encryptionPhone = M("live_record")->where("uid=$uid")->select();
        if(empty($encryptionPhone)){
            echo "<script>alert('请先按照步骤获取数据！');</script>";
        }
        $index = new IndexAction();
        // $encryptionPhone = $index->hidtel($userName['login']);
        $phone = substr($userName['login'],-4);
        $userNamePhone = $userName['uname']."@".$phone;
        // $userNamePhone = "杨金华@";
        // dump($userNamePhone);
        $where = [];
        $where['userName'] = $userNamePhone;
        $learn = M('learn_record')->order("enterTime asc ,leaveTime asc")->findPage("",false,$where,"record");
        // dump($learn);
        $this->_listpk = 'id';
        $this->assign('pageTitle', '用户：[ ' . M('user')->where('uid=' . $uid)->getField('uname') . ' ] 学习记录');
        $this->displayList($learn);
    }
    //获取当前用户观看直播和回放的记录

    public function userWatchList()
    {
        
        ini_set('max_execution_time',0);

        $uid = $_GET['uid'];
        
        $roomIdList = M("live_record")->where(["uid"=>$uid])->select();
        
        $live_search =  new IndexAction();
        foreach($roomIdList as $key => $val){
            $result_list = json_decode($live_search->liveSearch('liveid',$val['liveId'],'http://api.csslcloud.net/api/statis/live/useraction?'));
            if("OK" == $result_list->result && 0 < $result_list->count){
                foreach($result_list->userEnterLeaveActions as $k => $v){
                    M()->query("insert into el_learn_record(userName,roomName,roomId,liveId,recordId,ip,city,enterTime,leaveTime,watchTime,createTime,recordType) values('$v->viewerName','$val[roomName]','$val[roomId]','$val[liveId]','$val[recordId]','$v->viewerIp','$v->city','$v->enterTime','$v->leaveTime','$v->watchTime',now(),'1')");
                }
            }
        }

        $beforeYearTime = date("Y-m-d H:i:s", strtotime("-1 year"));
        
        
        $roomIdMaxMin = M("live_record")->field("MAX(startTime) as max_time, min(startTime) as min_time")->where(["uid"=>$uid])->find();
        // dump($roomIdMaxMin);
        if($roomIdMaxMin['min_time'] < $beforeYearTime){
            $ob_start = $beforeYearTime;
        }else{
            $ob_start = $roomIdMaxMin['min_time'];
        }
        $now = date("Y-m-d H:i:s",time());
        if($roomIdMaxMin['max_time'] > $now){
            $ob_end = $now;
        }else{
            $ob_end = $roomIdMaxMin['max_time'];
        }
        
        
        $sevenDaysCount  =  $this->getChaBetweenTwoDate(explode(" ",$ob_start)[0],explode(" ", $ob_end)[0]);
        $this->enterLive($ob_start,$ob_end,$uid,$sevenDaysCount);
        

        $this->leaveLive($ob_start,$ob_end,$uid,$sevenDaysCount);

        echo json_encode(['message'=>1]);exit();

    }

    //获取进入直播间的访问人数
    public function enterLive($ob_start,$ob_end,$uid,$sevenDaysCount){
        
        // ini_set('max_execution_time',0);
        
        // $ii = 0;
        // do{ 
        //     $start = date("Y-m-d H:i:s",strtotime("$ob_start+".($ii*7)."day"));
        //     $end = date('Y-m-d H:i:s',strtotime("$start+7day"));
        //     if($end > $ob_end){
        //         $end = $ob_end;
        //     }
        //     $data = [];
        //     $data['uid'] = $uid;
        //     $data['startTime'] = array("BETWEEN","'$start','$end'");
        //     $result_record = M("live_record")->where($data)->select();
        //     $live_search =  new IndexAction();
        //     if(!empty($result_record)){
        //         foreach ($result_record as $kk => $vv) {
        //             $values = [];
        //             $values['recordid'] = $vv['recordId'];
        //             $values['starttime'] = $start; 
        //             $values['endtime'] = $end; 
        //             $values['action'] = 0 ;
        //             $result_list = json_decode($live_search->liveSearch('recordId',$values,'http://api.csslcloud.net/api/statis/record/useraction?'));
        //             if("OK" == $result_list->result && 0 < $result_list->count){
        //                 foreach($result_list->userActions as $k => $v){
        //                     M()->query("insert into el_learn_record(userName,roomName,roomId,liveId,recordId,ip,enterTime,createTime,action,recordType) values('$v->userName','$vv[roomName]','$vv[roomId]','$vv[liveId]','$vv[recordId]',,'$v->userIp','$v->time',now(),'$result_list->action','2')");
        //                 }
        //             }

        //         }
        //     }

        //     $ii++;
        // }while($ii <= $sevenDaysCount-1);
        ini_set('max_execution_time',0);
        $i = 0;
        do{ 
            $start = date("Y-m-d H:i:s",strtotime("$ob_start+".($i*7)."day"));
            $end = date('Y-m-d H:i:s',strtotime("$start+7day"));
            if($end > $ob_end){
                $end = $ob_end;
            }
            $data = [];
            $data['uid'] = $uid;
            $data['startTime'] = array("BETWEEN","'$start','$end'");
            $result_record = M("live_record")->where($data)->select();
            $live_search =  new IndexAction();
            if(!empty($result_record)){
                foreach ($result_record as $kk => $vv) {
                    $values = [];
                    $values['recordid'] = $vv['recordId'];
                    $values['starttime'] = $start; 
                    $values['endtime'] = $end; 
                    $values['action'] = 0 ;
                    $result_list = json_decode($live_search->liveSearch('recordId',$values,'http://api.csslcloud.net/api/statis/record/useraction?'));
                    if("OK" == $result_list->result && 0 < $result_list->count){
                        foreach($result_list->userActions as $k => $v){
                            M()->query("insert into el_learn_record(userName,roomName,roomId,liveId,recordId,ip,enterTime,createTime,action,recordType) values('$v->userName','$vv[roomName]','$vv[roomId]','$vv[liveId]','$vv[recordId]','$v->userIp','$v->time',now(),'$result_list->action','2')");
                        }
                    }

                }
            }
            $i++;
        }while($i <= $sevenDaysCount-1);
    }

    //离开直播间的人数
    public function leaveLive($ob_start,$ob_end,$uid,$sevenDaysCount){
        
        ini_set('max_execution_time',0);
        $i = 0;
        do{ 
            $start = date("Y-m-d H:i:s",strtotime("$ob_start+".($i*7)."day"));
            $end = date('Y-m-d H:i:s',strtotime("$start+7day"));
            if($end > $ob_end){
                $end = $ob_end;
            }
            $data = [];
            $data['uid'] = $uid;
            $data['startTime'] = array("BETWEEN","'$start','$end'");
            $result_record = M("live_record")->where($data)->select();
            $live_search =  new IndexAction();
            if(!empty($result_record)){
                foreach ($result_record as $kk => $vv) {
                    $values = [];
                    $values['recordid'] = $vv['recordId'];
                    $values['starttime'] = $start; 
                    $values['endtime'] = $end; 
                    $values['action'] = 1 ;
                    $result_list = json_decode($live_search->liveSearch('recordId',$values,'http://api.csslcloud.net/api/statis/record/useraction?'));
                    if("OK" == $result_list->result && 0 < $result_list->count){
                        foreach($result_list->userActions as $k => $v){
                            M()->query("insert into el_learn_record(userName,roomName,roomId,liveId,recordId,ip,leaveTime,createTime,action,recordType) values('$v->userName','$vv[roomName]','$vv[roomId]','$vv[liveId]','$vv[recordId]','$v->userIp','$v->time',now(),'$result_list->action','2')");
                        }
                    }

                }
            }
            $i++;
        }while($i <= $sevenDaysCount-1);
    }

    //日期间隔
    function getChaBetweenTwoDate($date1,$date2){
        $Date_List_a1=explode("-",$date1);
        $Date_List_a2=explode("-",$date2);
        $d1=mktime(0,0,0,$Date_List_a1[1],$Date_List_a1[2],$Date_List_a1[0]);
        $d2=mktime(0,0,0,$Date_List_a2[1],$Date_List_a2[2],$Date_List_a2[0]);
        $Days=ceil(round(($d2-$d1)/3600/24)/7);
        return $Days;
    }



    //获取当前用户的直播间观看id和回放记录id
    public function userLiveRecord()
    {
        ini_set('max_execution_time',0);
        $uid = $_GET['uid'];
        $res_array = M()->query('select live_id from el_zy_order_live where uid ='.$uid);
        $live_id = '';
        foreach ($res_array as $key => $value) {
            $live_id .= $value['live_id'].",";
        }
        $live_id_string = trim($live_id,",");
        
        
        $roomId_array = M("zy_live_thirdparty")->field("roomid,subject")->where(["live_id"=>["in",$live_id_string]])->select();
        $live_search =  new IndexAction();
        foreach($roomId_array as $key => $val){
            
            $result_list = json_decode($live_search->liveSearch('roomid',$val['roomid'],'http://api.csslcloud.net/api/v2/record/info?'));
            $subject = $val['subject'];
            $roomId = $val['roomid'];
            if("OK" == $result_list->result && 0 < $result_list->count){
                foreach ($result_list->records as $k => $v) {
                    M()->query("insert into el_live_record(uid,roomName,roomId,liveId,recordId,startTime,stopTime,saveTime) values(\"$uid\",\"$subject\",\"$roomId\",\"$v->liveId\",\"$v->id\",\"$v->startTime\",\"$v->stopTime\",now())");
                }
            }
        }


        echo json_encode(['message'=>1]);exit;
    }

    //删除单个会员的操作记录
    public function delLearnAll(){
        $uid = $_GET['uid'];
        $live_record = M("live_record")->where("uid=$uid");
        $stringIdArray = $live_record->field("liveId")->findAll();
        if(empty($stringIdArray)){
            echo json_encode(['message'=>2]);exit();
        }
        foreach ($stringIdArray as $key => $value) {
            $learn_record = M("learn_record")->where("liveId='".$value['liveId']."'")->delete();
        }
        
        $live_result =  M("live_record")->where("uid=$uid")->delete();
        if($live_result > 0 && $learn_record > 0){
            echo json_encode(['message'=>1]);
        }else{
            echo json_encode(['message'=>0]);
        }


        exit();



    }



    /**
    * 删除学习记录
    */
    
    public function delLearnRecord()
    {
        
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data']   = '';
            exit(json_encode($return));
        }


       $result = M('learn_record')->where('id = '.$_POST['id'])->delete();
        if (!$result) {
            $return['status'] = 0;
            $return['data']   = L('PUBLIC_REMOVE_COMPLETELY_FAIL'); // 操作失败
        } else {
            // 关联删除用户其他信息，执行删除用户插件.
            $return['status'] = 1;
            $return['data']   = '删除成功'; // 操作成功
        }
        exit(json_encode($return));
        
        
        
        
        
    
    }
    /**
     * 用户管理 - 添加用户
     */
    public function addUser()
    {
        // 初始化用户列表管理菜单
        $this->_initUserListAdminMenu();
        $this->pageTitle['addUser'] = '添加';
        //注册配置(添加用户页隐藏审核按钮)
        $regInfo           = model('Xdata')->get('admin_Config:register');
        $this->pageKeyList = array('uname', 'email', 'phone', 'password', 'sex', 'school');
        if ($regInfo['register_audit'] == 1) {
            $this->pageKeyList     = array_merge($this->pageKeyList, array('is_audit'));
            $this->opt['is_audit'] = array('1' => '是', '2' => '否');
        }
        if ($regInfo['need_active'] == 1) {
            $this->pageKeyList      = array_merge($this->pageKeyList, array('is_active'));
            $this->opt['is_active'] = array('1' => '是', '2' => '否');
        }
        $this->pageKeyList = array_merge($this->pageKeyList, array('user_group'));
        // 列表key值 DOACTION表示操作
        //$this->pageKeyList = array('email','uname','password','sex','is_audit','is_active','identity','user_group','user_category');  //身份字段预留
        // $this->pageKeyList = array('email','uname','password','sex','is_audit','is_active','user_group','user_category');
        $this->opt['type'] = array('2' => L('PUBLIC_SYSTEM_FIELD'));
        // 字段选项配置
        $this->opt['sex'] = array('1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));
        //$this->opt['identity'] = array('1'=>L('PUBLIC_PERSONAL'),'2'=>L('PUBLIC_ORGANIZATION'));
        // $group = D('user_group')->where('is_authenticate=0')->findAll();
        // foreach($group as $k=>$v){
        //     $unverifyGroup[$v['user_group_id']] = $v['user_group_name'];
        // }
        // $this->opt['user_group'] = $unverifyGroup;
        $this->opt['school'] = model('School')->getAllSchol('', 'id,title');
        $group_ids           = M('user_group')->where(['pid' => 4])->field('user_group_id')->findAll();
        $list                = [];
        foreach ($group_ids as $val) {
            array_push($list, $this->getAllUserGroupId($val['user_group_id']));
        }
        $new_list = [];
        foreach ($list as $val) {
            foreach ($val as $v) {
                $pid = model('UserGroup')->where(['user_group_id' => $v])->getField('pid');
                if ($pid != 4) {
                    array_push($new_list, $v);
                }
            }
        }
        $all_ids = $this->getAllUserGroupId();
        foreach ($all_ids as $k => $v) {
            if (in_array($v, $new_list)) {
                unset($all_ids[$k]);
            }
        }

        $this->opt['user_group']    = model('UserGroup')->where(['user_group_id' => ['in', $all_ids]])->getField('user_group_id,user_group_name');
        $map['pid']                 = array('NEQ', 0);
        $this->opt['user_category'] = model('UserCategory')->getAllHash($map);
        // 表单URL设置
        $this->savePostUrl = U('admin/User/doAddUser');
        $this->notEmpty    = array('uname', 'password', 'user_group');
        $this->onsubmit    = 'admin.addUserSubmitCheck(this)';

        $this->displayConfig();
    }

    /**
     * 根据group_id 获取所有用户组的id
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-05-22
     * @param  integer $group_id 用户组ID
     * @return array
     */
    public function getAllUserGroupId($group_id = 0)
    {
        $list      = [$group_id];
        $group_ids = M('user_group')->where(['pid'=>$group_id,'user_group_id'=>['neq',3]])->field('user_group_id')->select();
        if ($group_ids) {
            foreach ($group_ids as $group_id) {
                $list[] = $group_id['user_group_id'];
                $list   = array_merge($list, $this->getAllUserGroupId($group_id['user_group_id']));
            }
        }
        return array_filter(array_unique($list));
    }

    /**
     * 添加新用户操作
     */
    public function doAddUser()
    {
        $user = model('User');
        $map  = $user->create();
        // 审核与激活修改
        $map['is_active'] = ($map['is_active'] == 2) ? 0 : 1;
        $map['is_audit']  = ($map['is_audit'] == 2) ? 0 : 1;
        $map['is_init']   = ($map['is_init'] == 2) ? 0 : 1;
        $map['mhm_id']    = intval($_POST['school']);

        //检查map返回值，有表单验证
        $result = $user->addUser($map);
        if ($result) {
            $this->assign('jumpUrl', U('admin/User/index'));
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
        $this->pageTitle['editUser'] = '编辑';
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('uid', 'uname', 'email', 'phone', 'password', 'sex', 'user_group');
        $this->opt['type'] = array('2' => L('PUBLIC_SYSTEM_FIELD'));
        // 字段选项配置
        $this->opt['sex'] = array('1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));
        //$this->opt['identity'] = array('1'=>L('PUBLIC_PERSONAL'),'2'=>L('PUBLIC_ORGANIZATION'));
        // $user_department = model('Department')->getAllHash(0);
        $group_ids = M('user_group')->where(['pid' => 4])->field('user_group_id')->findAll();
        $list      = [];
        foreach ($group_ids as $val) {
            array_push($list, $this->getAllUserGroupId($val['user_group_id']));
        }
        $new_list = [];
        foreach ($list as $val) {
            foreach ($val as $v) {
                $pid = model('UserGroup')->where(['user_group_id' => $v])->getField('pid');
                if ($pid != 4) {
                    array_push($new_list, $v);
                }
            }
        }
        $all_ids = $this->getAllUserGroupId();
        foreach ($all_ids as $k => $v) {
            if (in_array($v, $new_list)) {
                unset($all_ids[$k]);
            }
        }

        $this->opt['user_group'] = model('UserGroup')->where(['user_group_id' => ['in', $all_ids]])->getField('user_group_id,user_group_name');
        //所有的
        //        $usergroupHash = model('UserGroup')->getHashUsergroupNoncertified();
        //        $this->opt['user_group'] = $usergroupHash;
        $this->opt['is_active'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));

        //获取用户资料
        $uid      = intval($_REQUEST['uid']);
        $userInfo = model('User')->getUserInfo($uid);

        unset($userInfo['password']);
        //获取用户组信息
        $userInfo['user_group']     = model('UserGroupLink')->getUserGroup($uid);
        $userInfo['user_group']     = $userInfo['user_group'][$uid];
        $map['pid']                 = array('neq', 0);
        $this->opt['user_category'] = model('UserCategory')->getAllHash($map);
        $userInfo['user_category']  = getSubByKey(model('UserCategory')->getRelatedUserInfo($uid), 'user_category_id');
        // dump($userInfo);
        if (!$userInfo) {
            $this->error(L('PUBLIC_GET_INFORMATION_FAIL'));
        }
        
        //用户部门
        /*        $depart = model('Department')->getUserDepart($uid);
        $userInfo['department_show'] = str_replace('|', ' - ',trim($depart[$uid],'|'));
        $userInfo['department_id']     = 0;*/

        $this->assign('pageTitle', L('PUBLIC_EDIT_USER'));
        $this->savePostUrl = U('admin/User/doUpdateUser');

        // $this->notEmpty = array('email','uname','department_id');
        $this->notEmpty = array('uname', 'user_group');
        $this->onsubmit = 'admin.checkUser(this)';

        $this->displayConfig($userInfo);
    }
    /**
     * 更新用户信息
     */
    public function doUpdateUser()
    {
        
        $uid  = intval($_POST['uid']);
        $user = model('User');
        // 验证用户名称是否重复
        $oldUname      = $user->where('uid=' . $uid)->getField('uname');
        
        $vmap['uname'] = t($_POST['uname']);


        if (trim($oldUname) != trim($vmap['uname'])) {


            $isExist = $user->where($vmap)->count();
            if ($isExist > 0) {
                $this->error('用户昵称已存在，请使用其他昵称');
                return false;
            }
        }

        $map          = $user->create();
        $map['login'] = t($_POST['email']);
        unset($map['password']);
        // 生成新密码
        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $map['login_salt'] = rand(11111, 99999);
            $map['password']   = md5(md5($_POST['password']) . $map['login_salt']);
        }
        $map['first_letter'] = getFirstLetter($map['uname']);
        // 如果包含中文将中文翻译成拼音
        if (preg_match('/[\x7f-\xff]+/', $map['uname'])) {
            // 昵称和呢称拼音保存到搜索字段
            $map['search_key'] = $map['uname'] . ' ' . model('PinYin')->Pinyin($map['uname']);
        } else {
            $map['search_key'] = $map['uname'];
        }

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
            $this->assign('jumpUrl', U('admin/User/editUser', array('uid' => $uid, 'tabHash' => 'editUser')));
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
            $return['data']   = '';
            echo json_encode($return);exit();
        }
        //设置激活状态id可以是多个，类型只能是0或1
        $result = model('User')->activeUsers($_POST['id'], $_POST['type']);
        if (!$result) {
            $return['status'] = 0;
            $return['data']   = L('PUBLIC_ADMIN_OPRETING_ERROR');
        } else {
            $return['status'] = 1;
            $return['data']   = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        }
        echo json_encode($return);exit();
    }
    public function doAuditUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data']   = '';
            echo json_encode($return);exit();
        }
        //设置激活状态id可以是多个，类型只能是0或1
        $result = model('Register')->audit($_POST['id'], $_POST['type']);
        if (!$result) {
            $return['status'] = 0;
            $return['data']   = model('Register')->getLastError();
        } else {
            $return['status'] = 1;
            $return['data']   = model('Register')->getLastError();
        }
        echo json_encode($return);exit();
    }

    /**
     * 用户帐号禁用操作
     * @return json 操作后的JSON数据
     */
    public function doDeleteUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data']   = '';
            exit(json_encode($return));
        }

        $result = model('User')->deleteUsers($_POST['id']);
        if (!$result) {
            $return['status'] = 0;
            $return['data']   = L('PUBLIC_ADMIN_OPRETING_ERROR'); // 操作失败
        } else {
            // 关联删除用户其他信息，执行删除用户插件.
            $return['status'] = 1;
            $return['data']   = L('PUBLIC_ADMIN_OPRETING_SUCCESS'); // 操作成功
        }
        exit(json_encode($return));
    }
    /**
     * 彻底删除用户帐号操作
     * @return json 操作后的JSON数据
     */
    public function doTrueDeleteUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data']   = '';
            exit(json_encode($return));
        }


       $result = model('User')->trueDeleteUsers($_POST['id']);
        if (!$result) {
            $return['status'] = 0;
            $return['data']   = L('PUBLIC_REMOVE_COMPLETELY_FAIL'); // 操作失败
        } else {
            // 关联删除用户其他信息，执行删除用户插件.
            $return['status'] = 1;
            $return['data']   = L('PUBLIC_REMOVE_COMPLETELY_SUCCESS'); // 操作成功
        }
        exit(json_encode($return));
    }
    /**
     * 用户帐号恢复操作
     * @return json 操作后的JSON数据
     */
    public function doRebackUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data']   = '';
            exit(json_encode($return));
        }

        $result = model('User')->rebackUsers($_POST['id']);
        if (!$result) {
            $return['status'] = 0;
            $return['data']   = L('PUBLIC_ADMIN_OPRETING_ERROR'); // 操作失败
        } else {
            //关联删除用户其他信息，执行删除用户插件.
            $return['status'] = 1;
            $return['data']   = L('PUBLIC_ADMIN_OPRETING_SUCCESS'); // 操作成功
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
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

        //字段列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'field_key', 'field_name', 'field_type', 'visiable', 'editable', 'required', 'DOACTION');

        //列表批量操作按钮ed
        $this->pageButton[] = array('title' => L('PUBLIC_ADD_FIELD'), 'onclick' => "location.href='" . U('admin/User/addProfileField', array('tabHash' => 'addField')) . "'");

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
                $type[$v['field_id']]         = $v;
                $listData['data'][$k]['type'] = '<b>' . L('PUBLIC_SYSTEM_CATEGORY') . '</b>';
            } else {
                $listData['data'][$k]['field_type'] = $type[$v['field_type']]['field_name'];
                $listData['data'][$k]['type']       = L('PUBLIC_SYSTEM_FIELD');
            }
            $listData['data'][$k]['visiable'] = $listData['data'][$k]['visiable'] == 1 ? L('PUBLIC_SYSTEMD_TRUE') : L('PUBLIC_SYSTEMD_FALSE');
            $listData['data'][$k]['editable'] = $listData['data'][$k]['editable'] == 1 ? L('PUBLIC_SYSTEMD_TRUE') : L('PUBLIC_SYSTEMD_FALSE');
            $listData['data'][$k]['required'] = $listData['data'][$k]['required'] == 1 ? L('PUBLIC_SYSTEMD_TRUE') : L('PUBLIC_SYSTEMD_FALSE');
            //操作按钮
            $listData['data'][$k]['DOACTION'] = '<a href="' . U('admin/User/editProfileField', array('tabHash' => 'editField', 'id' => $v['field_id'])) . '">' . L('PUBLIC_EDIT') . '</a> '
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
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

        //分类列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'field_key', 'field_name', 'DOACTION');

        //列表批量操作按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'onclick' => "location.href='" . U('admin/User/addProfileCategory', array('tabHash' => 'addCateogry')) . "'");
        //$this->pageButton[] = array('title'=>'删除选中','onclick'=>"admin.delProfileField()");

        $map         = array();
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
                $type[$v['field_id']]         = $v;
                $listData['data'][$k]['type'] = '<b>' . L('PUBLIC_SYSTEM_CATEGORY') . '</b>';
            } else {
                $listData['data'][$k]['field_type'] = $type[$v['field_type']]['field_name'];
                $listData['data'][$k]['type']       = L('PUBLIC_SYSTEM_FIELD');
            }

            //操作按钮

            $listData['data'][$k]['DOACTION'] = '<a href="' . U('admin/User/editProfileCategory', array('tabHash' => 'addProfileCategory', 'id' => $v['field_id'])) . '">' . L('PUBLIC_EDIT') . '</a> '
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
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'type', 'field_key', 'field_name', 'field_type');
        $this->opt['type'] = array('1' => L('PUBLIC_SYSTEM_CATEGORY'));

        //获取配置信息
        $id      = intval($_REQUEST['id']);
        $setting = D('UserProfileSetting')->where("type=1")->find($id);
        if (!$setting) {
            $this->error(L('PUBLIC_INFO_GET_FAIL'));
        }

        $this->savePostUrl = U('admin/User/doSaveProfileField');

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
        $this->pageTab[]          = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[]          = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[]          = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $edit && $this->pageTab[] = array('title' => L('PUBLIC_EDIT_FIELD'), 'tabHash' => 'editField', 'url' => U('admin/User/editProfileField', array('id' => $_REQUEST['id'])));
        $this->pageTab[]          = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

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
        $this->opt['visiable']   = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['editable']   = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['required']   = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['privacy']    = array('0' => L('PUBLIC_WEIBO_COMMENT_ALL'), '1' => L('PUBLIC_SYSTEM_PARENT_SEE'), '2' => L('PUBLIC_SYSTEM_FOLLOWING_SEE'), '3' => L('PUBLIC_SYSTEM_FOLLW_SEE'));
        $this->opt['form_type']  = model('UserProfile')->getUserProfileInputType();

        $detail            = !empty($_GET['id']) ? D('UserProfileSetting')->where("field_id='{$_GET['id']}'")->find() : array();
        $this->savePostUrl = !empty($detail) ? U('admin/User/doSaveProfileField') : U('admin/User/doAddProfileField');

        $this->notEmpty = array('field_key', 'field_name', 'field_type');
        $this->onsubmit = 'admin.checkProfile(this)';
        $this->displayConfig($detail);
    }

    public function editProfileField()
    {
        //tab选项
        $this->pageTab[]          = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[]          = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[]          = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $edit && $this->pageTab[] = array('title' => L('PUBLIC_EDIT_FIELD'), 'tabHash' => 'editField', 'url' => U('admin/User/editProfileField', array('id' => $_REQUEST['id'])));
        $this->pageTab[]          = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

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
        $this->opt['visiable']   = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['editable']   = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['required']   = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['privacy']    = array('0' => L('PUBLIC_WEIBO_COMMENT_ALL'), '1' => L('PUBLIC_SYSTEM_PARENT_SEE'), '2' => L('PUBLIC_SYSTEM_FOLLOWING_SEE'), '3' => L('PUBLIC_SYSTEM_FOLLW_SEE'));
        $this->opt['form_type']  = model('UserProfile')->getUserProfileInputType();

        $detail            = !empty($_GET['id']) ? D('UserProfileSetting')->where("field_id='{$_GET['id']}'")->find() : array();
        $this->savePostUrl = !empty($detail) ? U('admin/User/doSaveProfileField') : U('admin/User/doAddProfileField');

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
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('type', 'field_key', 'field_name', 'field_type');
        $this->opt['type'] = array('1' => L('PUBLIC_SYSTEM_CATEGORY'));

        //字段选项配置
        $this->opt['field_type'] = array('0' => L('PUBLIC_SYSTEM_PCATEGORY'));
        $this->opt['visiable']   = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['editable']   = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['required']   = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['privacy']    = array('0' => L('PUBLIC_WEIBO_COMMENT_ALL'), '1' => L('PUBLIC_SYSTEM_PARENT_SEE'), '2' => L('PUBLIC_SYSTEM_FOLLOWING_SEE'), '3' => L('PUBLIC_SYSTEM_FOLLW_SEE'));
        $this->opt['form_type']  = model('UserProfile')->getUserProfileInputType();

        $this->savePostUrl = U('admin/User/doAddProfileField');

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
        $map     = $profile->create();
        //检查map返回值.有表单验证.
        $result = $profile->add($map);
        if ($result) {
            $jumpUrl = $_POST['type'] == 1 ? U('admin/User/profileCategory', array('tabHash' => 'category')) : U('admin/User/profile');
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
        $profile  = D('UserProfileSetting');
        $map      = $profile->create();
        $field_id = intval($_POST['field_id']);

        $jumpUrl = $_POST['type'] == 1 ? U('admin/User/profileCategory', array('tabHash' => 'category')) : U('admin/User/profile');
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
            $return['data']   = '';
            echo json_encode($return);exit();
        }
        if (D('UserProfileSetting')->where('field_type=' . intval($_POST['id']))->find()) {
            $return['status'] = 0;
            $return['data']   = '删除失败，该分类下字段不为空！';
        } else {
            $result = model('UserProfile')->deleteProfileSet($_POST['id']);
            if (!$result) {
                $return['status'] = 0;
                $return['data']   = L('PUBLIC_DELETE_FAIL');
            } else {
                //关联删除用户其他信息.执行删除用户插件.
                $return['status'] = 1;
                $return['data']   = L('PUBLIC_DELETE_SUCCESS');
            }
        }
        echo json_encode($return);exit();
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
        echo json_encode($return);exit();
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
        echo json_encode($return);exit();
    }

    /**
     * 初始化用户认证菜单
     */
    public function _initVerifyAdminMenu()
    {
        // tab选项
        //$this->pageTab[] = array('title'=>'认证分类', 'tabHash'=>'verifyCategory', 'url'=>U('admin/User/verifyCategory'));
        $this->pageTab[] = array('title' => '已认证', 'tabHash' => 'verified', 'url' => U('admin/User/verified'));
        $this->pageTab[] = array('title' => '待认证', 'tabHash' => 'verify', 'url' => U('admin/User/verify'));
        $this->pageTab[] = array('title' => '添加', 'tabHash' => 'addverify', 'url' => U('admin/User/addVerify'));
//        $this->pageTab[] = array('title'=>'待机构认证用户','tabHash'=>'schoolVerify','url'=>U('admin/User/schoolVerify'));

    }

    /**
     * 获取待认证用户列表
     * @return void
     */
    public function verify()
    {
        $this->_initVerifyAdminMenu();
        $this->pageTitle['verify'] = '待认证';
        $this->pageButton[] = array('title' => '驳回', 'onclick' => "admin.verify('',0)");

        $this->pageKeyList = array('uname', 'name', 'category', 'phone', 'reason', 'certification', 'Teach_areas', 'DOACTION');
        //获取默认机构
        $this_mhm_id = model('School')->getDefaultSchol('id');
        $listData          = D('ZyTeacher', 'classroom')->where(array('verified_status' => 2, 'mhm_id' => $this_mhm_id))->order('id DESC')->findpage(20);

        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo                      = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname'] = $userinfo['uname'];
            //$listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id='.$v['usergroup_id'])->getField('user_group_name');
            if ($listData['data'][$k]['identity_id']) {
                $a                                  = explode('|', $listData['data'][$k]['identity_id']);
                $listData['data'][$k]['attachment'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= msubstr($attachInfo['save_name'], 0, 25, "UTF-8", ture) . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path']) . $attachInfo['save_name'] . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            if ($listData['data'][$k]['attach_id']) {
                $a                                     = explode('|', $listData['data'][$k]['attach_id']);
                $listData['data'][$k]['certification'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['certification'] .= msubstr($attachInfo['save_name'], 0, 25, "UTF-8", ture) . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path']) . $attachInfo['save_name'] . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }

            //$listData['data'][$k]['category'] = $categoryHash[$v['user_verified_category_id']];
            $cate = M('zy_teacher_category')->where(['zy_teacher_category_id'=>['in',trim($v['fullcategorypath'],',')]])->field('title')->findAll();
            $cate = getSubByKey($cate,'title');
            $listData['data'][$k]['category'] = implode('/',$cate);
            $listData['data'][$k]['reason'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
//            if($v['is_school'] == 1){
            //                $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.verify('.$v['id'].',1,0)">通过</a> - ';
            //            }else{
            //                $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.verify('.$v['id'].',2,0)">通过</a> - ';
            //            }

            //$dotitle = $listData['data'][$k]['usergroup_id'];

            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.verify(' . $v['id'] . ',1)">通过</a> - ';

            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.getVerifyBox(' . $v['id'] . ',' . $v['uid'] . ',' . "'{$dotitle}'" . ')">驳回</a>';

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
        $this->pageButton[] = array('title' => '驳回认证', 'onclick' => "admin.verify('',0,6)");

        $this->pageKeyList = array('uname', 'usergroup_id', 'category', 'company', 'realname', 'idcard', 'phone', 'reason', 'info', 'attachment', 'DOACTION');
        $listData          = D('user_verified')->where('verified=0 and usergroup_id=6')->findpage(20);
        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo                             = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname']        = $userinfo['uname'];
            $listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id=' . $v['usergroup_id'])->getField('user_group_name');
            if ($listData['data'][$k]['attach_id']) {
                $a                                  = explode('|', $listData['data'][$k]['attach_id']);
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
            $listData['data'][$k]['reason']   = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
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
        $this->pageTitle['verified'] = '已认证';
        $this->pageButton[] = array('title' => '驳回', 'onclick' => "admin.verify('',0)");

        $this->pageKeyList = array('uname', 'name', 'category', 'phone', 'school', 'reason', 'certification', 'Teach_areas', 'DOACTION');
        $listData          = D('ZyTeacher', 'classroom')->where(array('verified_status' => 1))->order('id DESC')->findpage(20);
        // 获取认证分类的Hash数组
        //$categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo                      = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname'] = $userinfo['uname'];
            //$listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id='.$v['usergroup_id'])->getField('user_group_name');
            $school = model('School')->getSchoolFindStrByMap(array('id'=>$v['mhm_id']),'doadmin,title');
            $listData['data'][$k]['school'] = getQuickLink(getDomain($school['doadmin'],$v['mhm_id']),$school['title'],"未知机构");
            if ($listData['data'][$k]['identity_id']) {
                $a                                  = explode('|', $listData['data'][$k]['identity_id']);
                $listData['data'][$k]['attachment'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= msubstr($attachInfo['name'], 0, 25, "UTF-8", ture) . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path']) . $attachInfo['save_name'] . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            if ($listData['data'][$k]['attach_id']) {
                $a                                     = explode('|', $listData['data'][$k]['attach_id']);
                $listData['data'][$k]['certification'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['certification'] .= msubstr($attachInfo['name'], 0, 25, "UTF-8", ture) . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path']) . $attachInfo['save_name'] . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            //$listData['data'][$k]['category'] = $categoryHash[$v['user_verified_category_id']];
            $cate = M('zy_teacher_category')->where(['zy_teacher_category_id'=>['in',trim($v['fullcategorypath'],',')]])->field('title')->findAll();
            $cate = getSubByKey($cate,'title');
            $listData['data'][$k]['category'] = implode('/',$cate);
            $listData['data'][$k]['reason'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
            //$listData['data'][$k]['info'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['info']));
            //$listData['data'][$k]['DOACTION'] = '<a href="'.U('admin/User/editVerify',array('tabHash'=>'verified','id'=>$v['id'])).'">编辑</a> - ';
            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.getVerifyBox(' . $v['id'] . ')">驳回</a>';
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
        $listData          = D('user_verified')->where('verified=1 and usergroup_id=6')->order('id DESC')->findpage(20);
        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo                             = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname']        = $userinfo['uname'];
            $listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id=' . $v['usergroup_id'])->getField('user_group_name');
            if ($listData['data'][$k]['attach_id']) {
                $a                                  = explode('|', $listData['data'][$k]['attach_id']);
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
            $listData['data'][$k]['reason']   = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
            $listData['data'][$k]['info']     = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['info']));
            $listData['data'][$k]['DOACTION'] = '<a href="' . U('admin/User/editVerify', array('tabHash' => 'verifiedGroup', 'id' => $v['id'])) . '">编辑</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.getVerifyBox(' . $v['id'] . ')">驳回</a>';
        }
        $this->displayList($listData);
    }

    /**
     * 获取待机构认证用户列表
     * @return void
     */
    public function schoolVerify()
    {
        $this->_initVerifyAdminMenu();

        $this->pageKeyList = array('uname', 'usergroup_id', 'category', 'realname', 'idcard', 'phone', 'reason', 'info', 'mhm_title', 'school', 'specialty', 'address', 's_address', 'attachment', 'certification', 'other_data');
        $listData          = D('user_verified')->where('verified=2')->findpage(20);
        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo                             = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname']        = $userinfo['uname'];
            $listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id=' . $v['usergroup_id'])->getField('user_group_name');
            $school                               = model('School')->getSchoolFindStrByMap(array('id' => $v['mhm_id']), 'doadmin,title');
            $listData['data'][$k]['mhm_title']    = getQuickLink(getDomain($school['doadmin']), $school['title'], "未知机构");
            if ($listData['data'][$k]['identity_id']) {
                $a                                  = explode('|', $listData['data'][$k]['identity_id']);
                $listData['data'][$k]['attachment'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= msubstr($attachInfo['name'], 0, 25, "UTF-8", ture) . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path']) . $attachInfo['save_name'] . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            if ($listData['data'][$k]['attach_id']) {
                $a                                     = explode('|', $listData['data'][$k]['attach_id']);
                $listData['data'][$k]['certification'] = "";
                foreach ($a as $key => $val) {
                    if ($val !== "") {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['certification'] .= msubstr($attachInfo['name'], 0, 25, "UTF-8", ture) . '&nbsp;<a href="' . getImageUrl($attachInfo['save_path']) . $attachInfo['save_name'] . '" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            $listData['data'][$k]['category'] = $categoryHash[$v['user_verified_category_id']];
            $listData['data'][$k]['reason']   = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));

        }
        $this->displayList($listData);
    }
    /**
     * 驳回理由窗口
     * @return void
     */
    public function getVerifyBox()
    {
        $id     = intval($_GET['id']);
        $douid  = intval($_GET['uid']);
        $dotite = t($_GET['dotite']);

        $this->assign('id', $id);
        $this->assign('douid', $douid);
        $this->assign('dotite', $dotite);

        $this->display('verifyBox');
    }
    /**
     * 修改认证状态 信息
     * @return json 返回操作后的JSON信息数据
     */
    public function saveVerify()
    {
        $id = $_POST['id'];
        if ($_POST['info']) {
            $data['info'] = t($_POST['info']);
        }
        if ($_POST['school'] > 0) {
            $data['mhm_id']   = t($_POST['school']);
            $data['verified'] = intval($_POST['status']);
        } else {
            $data['verified'] = 1;
            $this->doVerify();
        }
        D('user_verified')->where('id=' . $id)->save($data);
        echo json_encode(array('status' => 1, 'data' => "认证成功"));exit();
    }
    /**
     * 执行认证
     * @return json 返回操作后的JSON信息数据
     */
    public function doVerify()
    {
        $id = $_POST['id'];
        //if($_POST['status'] > 2){
        //$status = 1;
        //}else{
        $status = intval($_POST['status']);
        //}
        if (is_array($id)) {
            $map['id'] = array('in', $id);
        } else {
            $map['id'] = $id;
        }
        $datas['verified_status'] = $status;
        if ($_POST['info']) {
            $datas['info'] = t($_POST['info']);
        }
        //if($_POST['school']){
        //    $datas['mhm_id'] = t($_POST['school']);
        //}else{
        //    $datas['mhm_id'] = 1;//D('user_verified')->where($map)->getField('mhm_id') ? : '0';
        //}
        $res = D('ZyTeacher', 'classroom')->where($map)->save($datas);
        if ($res) {
            $return['status'] = 1;
            if ($status == 1) {
                $user_id = D('ZyTeacher', 'classroom')->where('id=' . $id)->getField('uid');
                //添加成为老师
                //if($user_group["user_verified_category_id"]==6){
                //                    $data["uid"]   = $user_group['uid'];
                //                    $data["Teach_areas"] = $user_group['address'];
                //                    $data["name"]  = $user_group['realname'];
                //                    $data["mhm_id"] = $user_group['mhm_id'];
                //                    $data["ctime"] = time();
                //                    $data['identification'] = date(md).mt_rand(1000,9999).$data['uid'];
                //
                //                    $res = M("zy_teacher")->where('uid='.$user_group['uid'])->find();
                //                    if($res){
                //                        $data['is_reject'] = 0;
                //                        M("zy_teacher")->where('uid='.$user_group['uid'])->save($data);
                //                    }else{
                //                        M("zy_teacher")->add($data);
                //                    }
                //                }
                //结束
                $maps['uid']           = $user_id;
                $maps['user_group_id'] = 3; //$user_group['usergroup_id'];
                $exist                 = D('user_group_link')->where($maps)->find();
                if (!$exist) {
                    D('user_group_link')->add($maps);
                    // 清除用户组缓存
                    model('Cache')->rm('user_group_' . $user_id);
                    // 清除权限缓存
                    model('Cache')->rm('perm_user_' . $user_id);
                    // 删除微博信息
                    $feed_ids = model('Feed')->where('uid=' . $user_id)->limit(1000)->getAsFieldArray('feed_id');
                    model('Feed')->cleanCache($feed_ids);

                    model('Notify')->sendNotify($user_id, 'admin_user_doverify_ok');
                }
                $credit = M('credit_setting')->where(array('id' => 39, 'is_open' => 1))->field('id,name,score,count')->find();
                if ($credit['score'] > 0) {
                    $vtype = 6;
                    $note  = '申请成为讲师获得的积分';
                }
                model('Credit')->addUserCreditRule($user_id, $vtype, $credit['id'], $credit['name'], $credit['score'], $credit['count'], $note);

                $return['data'] = "认证成功";
            }
            if ($status == 0) {
                $return['data'] = "驳回成功";
                $rejectInfo     = array('reason' => t($_POST['reason']));
                //$data['act'] = '驳回';
                if (is_array($id)) {
                    foreach ($id as $k => $v) {
                        $user_id = D('ZyTeacher', 'classroom')->where('id=' . $v)->getField('uid');
                        $teacher = M('zy_teacher')->where('uid=' . $user_id)->count();
                        if ($teacher) {
                            $data['is_reject'] = 1;
                            M('zy_teacher')->where('uid=' . $user_id)->save($data);
                        }
                        $maps['uid']           = $user_id;
                        $maps['user_group_id'] = 3; //$user_group['usergroup_id'];
                        D('user_group_link')->where($maps)->delete();
                        // 清除用户组缓存
                        model('Cache')->rm('user_group_' . $user_id);
                        // 清除权限缓存
                        model('Cache')->rm('perm_user_' . $user_id);
                        // 删除微博信息
                        $feed_ids = model('Feed')->where('uid=' . $user_id)->limit(1000)->getAsFieldArray('feed_id');
                        model('Feed')->cleanCache($feed_ids);

                        //if($user_group['is_school'] == 1){
                        //$uid = model('School')->where('id='.$user_group['mhm_id'])->getField('uid');
                        //model('Notify')->sendNotify($uid,'admin_user_doverify_reject', $rejectInfo);
                        //}else{
                        model('Notify')->sendNotify($user_id, 'admin_user_doverify_reject', $rejectInfo);
                        //}
                        unset($user_group);
                        unset($maps);
                    }
                } else {
                    $user_id = D('ZyTeacher', 'classroom')->where('id=' . $id)->getField('uid');
                    $teacher = M('zy_teacher')->where('uid=' . $user_id)->count();
                    if ($teacher) {
                        $data['is_reject'] = 1;
                        M('zy_teacher')->where('uid=' . $user_id)->save($data);
                    }
                    $maps['uid']           = $user_id;
                    $maps['user_group_id'] = 3; //$user_group['usergroup_id'];
                    D('user_group_link')->where($maps)->delete();
                    // 清除用户组缓存
                    model('Cache')->rm('user_group_' . $user_id);
                    // 清除权限缓存
                    model('Cache')->rm('perm_user_' . $user_id);
                    // 删除微博信息
                    $feed_ids = model('Feed')->where('uid=' . $user_id)->limit(1000)->getAsFieldArray('feed_id');
                    model('Feed')->cleanCache($feed_ids);
                    model('Notify')->sendNotify($user_id, 'admin_user_doverify_reject', $rejectInfo);
                }
            }

        } else {
            $return['status'] = 0;
            $return['data']   = "认证失败";
        }
        echo json_encode($return);exit();
    }

    /**
     * 添加认证用户或认证企业
     * @return void
     */
    public function addVerify()
    {
        $this->pageTitle['addVerify']   = '添加';

        $this->_initVerifyAdminMenu();
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array(
            'uname',
            'realname',
            'teacher_category',
            'phone',
            'address',
            'reason',
            'school',
            'attach',
            //'identity',
        );
        // 字段选项配置
        //$auType = model('UserGroup')->where('is_authenticate=1')->select();
        //foreach($auType as $k=>$v){
        //    $this->opt['usergroup_id'][$v['user_group_id']] = $v['user_group_name'];
        //}
        // 认证分类配置
        //$categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        //foreach($categoryHash as $key => $value) {
        //    $this->opt['user_verified_category_id'][$key] = $value;
        //}
        // 表单URL设置
        $this->savePostUrl = U('admin/User/doAddVerify');
        $this->notEmpty    = array(
            'uname',
            'realname',
            'teacher_category',
            'phone',
            'reason',
            //'identity',
            'attach',
        );
        $this->onload[] = "admin.addVerifyConfig(5)";
        $this->opt['school'] = model('School')->getAllSchol(array('status'=>1,'is_del'=>0),'id,title');
        //$this->onsubmit = 'admin.addVerifySubmitCheck(this)';
        ob_start();
        echo W('CategoryLevel',array('table'=>'zy_teacher_category','id'=>'teacher_category','default'=>''));
        $output = ob_get_contents();
        ob_end_clean();
        $dateult['teacher_category'] = $output;
        $this->displayConfig($dateult);
    }

    /**
     * 执行添加认证
     * @return void
     */
    public function doAddVerify()
    {
        $data['uid'] = $_POST['uname'];
        $result      = D('ZyTeacher','classroom')->where('uid=' . $data['uid'])->find();
        if ($result) {
            if ($result['verified_status'] == 1) {
                $this->error('该用户已通过认证');
            } else {
                D('ZyTeacher','classroom')->where('uid=' . $data['uid'])->delete();
            }
        }

        //$data['usergroup_id'] = intval($_POST['usergroup_id']);
        //if ($_POST['company']) {
        //    $data['company'] = t($_POST['company']);
        //}
        $data['name']  = t($_POST['realname']);
        $data['phone']     = t($_POST['phone']);
        $data['reason']    = t($_POST['reason']);
        $data['mhm_id']    = t($_POST['school']);
        //$data['specialty'] = t($_POST['specialty']);
        $data['Teach_areas']   = t($_POST['address']);
        $data['attach_id']                 = t($_POST['attach_ids']);
        $myAdminLevelhidden         = getCsvInt(t($_POST['teacher_categoryhidden']),0,true,true,',');  //处理分类全路径
        $fullcategorypath           = explode(',',$_POST['teacher_categoryhidden']);
        $category                   = array_pop($fullcategorypath);
        $category                   = $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况

        $data['teacher_category'] = '0' ? array_pop($fullcategorypath) : $category;
        $data['fullcategorypath'] = $myAdminLevelhidden;//分类全路径
        $Regx1                             = '/^[0-9]*$/';
        //$Regx2                             = '/^[A-Za-z0-9]*$/';
        $Regx3                             = '/^[A-Za-z|\x{4e00}-\x{9fa5}]+$/u';
        if (strlen($data['name']) == 0) {
            $this->error('真实姓名不能为空');
        }
        if (strlen($data['phone']) == 0) {
            $this->error('手机号码不能为空');
        }
        if (strlen($data['reason']) == 0) {
            $this->error('认证理由不能为空');
        }
        if (preg_match($Regx3, $data['name']) == 0 || strlen($data['name']) > 30) {
            $this->error('请输入正确的姓名格式');
        }
        // if (preg_match($Regx2, $data['idcard']) == 0 || preg_match($Regx1, substr($data['idcard'], 0, 17)) == 0 || strlen($data['idcard']) !== 18) {
        //     $this->error('请输入正确的身份证号码');
        // }
        if (strlen($data['phone']) !== 11 || preg_match($Regx1, $data['phone']) == 0) {
            $this->error('请输入正确的手机号码格式');
        }
        $data['verified_status']     = 1;
        $data['ctime']     = time();
        $res                  = D('ZyTeacher','classroom')->add($data);
        $map['uid']           = $_POST['uname'];
        $map['user_group_id'] = 3;//intval($_POST['usergroup_id']);
        $exist                 = D('user_group_link')->where($map)->find();
        if (!$exist) {
            D('user_group_link')->add($map);
        }
        // 清除用户组缓存
        model('Cache')->rm('user_group_' . $map['uid']);
        // 清除权限缓存
        model('Cache')->rm('perm_user_' . $map['uid']);
        if ($res){
            $this->success('认证成功');
        }else {
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
        $verifyInfo = D('ZyTeacher', 'classroom')->where('id=' . intval($_GET['id']))->find();
        $school     = model('School')->field('id,title')->findALL();
        $school     = array_column($school, 'title', 'id');
        $this->assign('info', format($verifyInfo['reason']));
        $this->assign('usergroupId', 3);
        $this->assign('school', $school);
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
            'attach',
        );

        $id                  = intval($_REQUEST['id']);
        $verifyInfo          = D('user_verified')->where('id=' . $id)->find();
        $userinfo            = model('user')->getUserInfo($verifyInfo['uid']);
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

        $this->savePostUrl = U('admin/User/doEditVerify');
        $this->onsubmit    = 'admin.editVerifySubmitCheck(this)';
        $this->notEmpty    = array(
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
        $uid                  = intval($_POST['uid']);
        $old_group_id         = D('user_verified')->where('uid=' . $uid)->getField('usergroup_id');
        $data['usergroup_id'] = intval($_POST['usergroup_id']);
        if ($data['usergroup_id'] == 6) {
            $data['company'] = t($_POST['company']);
        }
        $data['realname']  = t($_POST['realname']);
        $data['idcard']    = t($_POST['idcard']);
        $data['phone']     = t($_POST['phone']);
        $data['reason']    = t($_POST['reason']);
        $data['info']      = t($_POST['info']);
        $data['school']    = t($_POST['school']);
        $data['specialty'] = t($_POST['specialty']);
        $data['Teach_areas']   = t($_POST['address']);
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
            //     $option[$v['pid']] .= 'selected';
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
        $treeData    = model('CategoryTree')->setTable('user_category')->getNetworkList();
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
        $this->pageButton[] = array('title' => L('添加'), 'onclick' => "admin.addVerifyCategory()");

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
        $cateInfo                  = D('user_verified_category')->where('user_verified_category_id=' . $user_verified_category_id)->find();
        $this->assign('cateInfo', $cateInfo);
        $this->display('editVerifyCategory');
    }

    /**
     * 执行添加认证分类
     */
    public function doAddVerifyCategory()
    {
        $data['pid']   = intval($_POST['pid']);
        $data['title'] = t($_POST['title']);
        if (D('user_verified_category')->where($data)->find()) {
            $return['status'] = 0;
            $return['data']   = '此分类已存在';
        } else {
            if (D('user_verified_category')->add($data)) {
                $return['status'] = 1;
                $return['data']   = '添加成功';
            } else {
                $return['status'] = 0;
                $return['data']   = '添加失败';
            }
        }
        echo json_encode($return);exit();
    }

    /**
     * 执行编辑认证分类
     */
    public function doEditVerifyCategory()
    {
        $data['pid']               = intval($_POST['pid']);
        $data['title']             = t($_POST['title']);
        $user_verified_category_id = intval($_POST['user_verified_category_id']);
        if (D('user_verified_category')->where($data)->find()) {
            $return['status'] = 0;
            $return['data']   = '此分类已存在';
        } else {
            $old_pid = D('user_verified_category')->where('user_verified_category_id=' . $user_verified_category_id)->getField('pid');
            if (D('user_verified_category')->where('user_verified_category_id=' . $user_verified_category_id)->save($data) !== false) {
                if ($old_pid != $data['pid']) {
                    D('user_verified')->where('user_verified_category_id=' . $user_verified_category_id)->setField('usergroup_id', $data['pid']);
                    $datas['uid']           = array('in', getSubByKey(D('user_verified')->where('user_verified_category_id=' . $user_verified_category_id)->field('uid')->findAll(), 'uid'));
                    $datas['user_group_id'] = $old_pid;
                    D('user_group_link')->where($datas)->setField('user_group_id', $data['pid']);
                }
                $return['status'] = 1;
                $return['data']   = '编辑成功';
            } else {
                $return['status'] = 0;
                $return['data']   = '编辑失败';
            }
        }
        echo json_encode($return);exit();
    }

    /**
     * 删除认证分类
     */
    public function delVerifyCategory()
    {
        $user_verified_category_id = intval($_POST['user_verified_category_id']);
        if (D('user_verified_category')->where('user_verified_category_id=' . $user_verified_category_id)->delete()) {
            $return['status'] = 1;
            $return['data']   = '删除成功';
        } else {
            $return['status'] = 0;
            $return['data']   = '删除失败';
        }
        echo json_encode($return);exit();
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
        $this->pageTab[] = array('title' => '找人配置', 'tabHash' => 'findPeopleConfig', 'url' => U('admin/User/findPeopleConfig'));
        // 配置用户存储基本字段
        $this->pageKeyList       = array('findPeople');
        $findtype['tag']         = '按标签';
        $findtype['area']        = '按地区';
        $findtype['verify']      = '认证用户';
        $findtype['official']    = '官方推荐';
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
        $treeData    = model('CategoryTree')->setTable('user_official_category')->getNetworkList();
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
            $user_category     = model('CategoryTree')->setTable('user_official_category')->getCategoryById($value['user_official_category_id']);
            $value['title']    = $user_category['title'];
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
        $this->savePostUrl = U('admin/User/doOfficialAddUser');
        $this->notEmpty    = array('uids', 'category');

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
        $uids   = t($_REQUEST['uids']);
        $cid    = intval($_REQUEST['category']);
        $info   = t($_REQUEST['info']);
        $result = model('UserOfficial')->addOfficialUser($uids, $cid, $info);
        // 添加后跳转
        if ($result) {
            $this->assign('jumpUrl', U('admin/User/officialAddUser'));
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
            $res['data']   = '请选择用户';
        } else {
            // 删除操作
            $result = model('UserOfficial')->removeUserOfficial($ids);
            // 返回结果集
            if ($result) {
                $res['status'] = 1;
                $res['data']   = '操作成功';
            } else {
                $res['status'] = 0;
                $res['data']   = '操作失败';
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
        $this->pageTab[] = array('title' => '推荐分类', 'tabHash' => 'officialCategory', 'url' => U('admin/User/officialCategory'));
        $this->pageTab[] = array('title' => '置顶用户', 'tabHash' => 'official', 'url' => U('admin/User/official'));
        $this->pageTab[] = array('title' => '添加推荐用户', 'tabHash' => 'officialAddUser', 'url' => U('admin/User/officialAddUser'));
        $this->pageTab[] = array('title' => '已推荐用户', 'tabHash' => 'officialList', 'url' => U('admin/User/officialList'));
    }

}
