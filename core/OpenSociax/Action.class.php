<?php
/**
 * ThinkSNS Action控制器基类
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
abstract class Action
{
//类定义开始

    // 当前Action名称
    private $name           = '';
    protected $tVar         = array();
    protected $trace        = array();
    protected $templateFile = '';
    protected $appCssList   = array();
    protected $langJsList   = array();

    protected $site         = array();
    protected $user         = array();
    protected $app          = array();
    protected $mid          = 0;
    protected $uid          = 0;
    protected $is_wap       = false;
    protected $is_pc        = true;
    protected $seo          = array();
    protected $youtu_status = 0;

    /**
     * 架构函数 取得模板对
     * @access public
     */
    public function __construct()
    {

        header('Content-type:text/html;charset=UTF-8;');
        $this->initSite();
        $this->initUser();
        $this->initApp();
        Addons::hook('core_filter_init_action');
        //控制器初始化
        if (method_exists($this, '_initialize')) {
            $this->_initialize();
        }

    }

    /**
     * 站点信息初始化
     * @access private
     * @return void
     */
    private function initSite()
    {
        $path_uri = APP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME;
        // 人脸识别配置
        $youtuConf          = model("Xdata")->get("admin_Config:youtu");
        // 营销数据开关配置
        $marketConf          = model("Xdata")->get("admin_Config:marketConfig");
        $this->youtu_status = (!$youtuConf || $youtuConf['is_open'] != 1) ? 0 : 1;
        // 超级管理员和机构管理员登陆不验证
        if ($_SESSION['mid'] && $this->youtu_status && !is_admin($_SESSION['mid']) && !is_school($_SESSION['mid'])) {
            // 人脸验证
            $youtuscene   = model('Xdata')->get('admin_Config:youtuscene');
            $is_open      = $youtuscene && isset($youtuscene['login_force_verify']) && $youtuscene['login_force_verify'] == 1;
            $notVerify    = ['classroom/user/face', 'public/passport/loginfaceverify','public/passport/logout'];

            if ($is_open && !session('face_check_face_verify') && !in_array(strtolower($path_uri), $notVerify)) {
                // 如果没有验证
                $redirect_params = $_GET;
                unset($redirect_params['app'], $redirect_params['mod'], $redirect_params['act']);
                // 是否设置了回调地址
                if(isset($redirect_params['redirect_url'])){
                    $redirect_url = $redirect_params['redirect_url'];
                    unset($redirect_params['redirect_url']);
                }else{
                    $redirect_url = U($path_uri, $redirect_params);
                }
                redirect(U('public/Passport/loginFaceVerify', ['verified_module' => 'check_face', 'redirect_url' => urlencode($redirect_url)]));
            }
        }

        //载入站点配置全局变量
        $this->site = model('Xdata')->get('admin_Config:site');

        //设置站点默认seo的title、meta
        $seo_uri = $path_uri;
        $seo     = M('seo')->where(['uri' => $seo_uri])->field('id,title,keywords,description')->find();
        if ($seo) {
            $this->seo['_title']       = $seo['keywords'] ? $seo['title'] . " — " . $seo['keywords'] : $seo['title'] . " — " . $this->site['site_name'];
            $this->seo['_bak_title']   = $seo['title'] ? " — " . $seo['title'] : " — " . $this->site['site_name'];
            $this->seo['_keywords']    = $seo['keywords'] ?: $this->site['site_header_keywords'];
            $this->seo['_description'] = $seo['description'] ?: $this->site['site_header_description'];
        } else {
            $this->seo['_title']       = $this->site['site_name'] . " — " . $this->site['site_slogan'];
            $this->seo['_bak_title']   = " — " . $this->site['site_name'];
            $this->seo['_keywords']    = $this->site['site_header_keywords'];
            $this->seo['_description'] = $this->site['site_header_description'];
        }

        //获取注册方式
        $register_type = model('Xdata')->get('admin_Config:register');

        if ($this->site['site_closed'] == 0 && APP_NAME != 'admin') {
            //TODO  跳转到站点关闭页面
            $this->page404($this->site['site_closed_reason']);exit();
        }

        //检查是否启用rewrite
        if (isset($this->site['site_rewrite_on'])) {
            C('URL_ROUTER_ON', ($this->site['site_rewrite_on'] == 1));
        }

        //初始化语言包
        $cacheFile = C('F_CACHE_PATH') . '/initSiteLang.lock.php';
        if (!file_exists($cacheFile)) {
            model('Lang')->initSiteLang();
        }

        //LOGO处理
        //处理泛域名
        if (IS_CHILDS_HOST) {
            // 拼接地址
            $config = model('Xdata')->get("school_AdminDomaiName:domainConfig");
            if (!$config) {
                // 默认
                $config = ['openHttps' => 0, 'domainConfig' => 1];
            }
            $url                        = ($config['openHttps'] ? 'https://' : 'http://') . $_SERVER['HTTP_X_HOST'];
            $domain                     = substr($_SERVER['HTTP_X_HOST'], 0, stripos($_SERVER['HTTP_X_HOST'], '.'));
            $info                       = model('School')->where(array('doadmin' => t($domain), 'status' => 1, 'is_del' => 0))->field(['id','logo', 'title'])->find();
            $this->site['logo_head']    = getCover($info['logo'], 150, 52);
            $this->site['logo_head']    = getCover($info['logo'], 150, 52);
            $this->site['home_url']     = $this->site['site_url']     = $url;
            $this->site['site_keyword'] = $info['title'];
            $this->mhm_id               = $info['id'];
        } else {
            $this->site['logo_head']     = getSiteLogo($this->site['site_logo_head']);
            $this->site['logo_head_w3g'] = getSiteLogo($this->site['site_logo_head_w3g']);
        }

        //默认登录后首页
        if (intval($this->site['home_page'])) {
            $appInfo                = model('App')->where('app_id=' . intval($this->site['home_page']))->find();
            $this->site['home_url'] = U($appInfo['app_name'] . '/' . $appInfo['app_entry']);
        } else {
            $this->site['home_url'] = U('classroom/Index/index');
        }

        //网站底部的单页面导航
        $pageCategory = D('Single', 'admin')->getList();
        $this->assign('pageCategory', $pageCategory);

        //赋值给全局变量
        $GLOBALS['ts']['site'] = $this->site;

        //网站导航
        $GLOBALS['ts']['site_top_nav']          = model('Navi')->getTopNav();
        $GLOBALS['ts']['site_bottom_nav']       = model('Navi')->getBottomNav();
        $GLOBALS['ts']['site_bottom_child_nav'] = model('Navi')->getBottomChildNav($GLOBALS['ts']['site_bottom_nav']);
        if (!$_SESSION['mid']) {
            //游客导航
            $GLOBALS['ts']['site_guest_nav'] = model('Navi')->getGuestNav();
            $this->assign('site_guest_nav', $GLOBALS['ts']['site_guest_nav']);
        }
        //获取可搜索的内容列表
        if (false === ($searchSelect = S('SearchSelect'))) {
            $searchSelect = D('SearchSelect')->findAll();
            S('SearchSelect', $searchSelect);
        }

        //网站所有的应用
        $GLOBALS['ts']['site_nav_apps'] = model('App')->getAppList(array('status' => 1, 'add_front_top' => 1), 9);

        //网站全局变量过滤插件
        Addons::hook('core_filter_init_site');

        //体验手机版地址
        $scheme                  = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : http;
        $this->site['phone_url'] = $scheme . '://' . $_SERVER['HTTP_HOST'];

        //app下载地址
        $this->site['download_url'] = model('Xdata')->get('admin_Config:appConfig')['download_url'];

        //tp验证码
        include_once 'Verify.class.php';

        //$_REQUEST['wap_to_normal'] = 1;//暂不访问手机版
        // 使用手持设备时, 对用户的访问默认跳转至移动版, 除非用户指定访问普通版 iPad上浏览pc的内容尺寸较小，默认也为3g版
        //        if (is_mobile()) {
        //            $this->is_pc = false;
        //            $this->is_wap = true;
        //            $this->assign('is_wap',$this->is_wap);
        //        } else {
        //            $this->is_pc = true;
        //            $this->is_wap = false;
        //            $this->assign('is_pc',$this->is_pc);
        //        }
        //$_REQUEST['wap_to_normal'] = 1;//暂不访问手机版
        // 使用手持设备时, 对用户的访问默认跳转至移动版, 除非用户指定访问普通版 iPad上浏览pc的内容尺寸较小，默认也为3g版
        if ($_SESSION['wap_to_normal'] != '1' && cookie('wap_to_normal') != '1' && $_REQUEST['wap_to_normal'] != '1') {
            // 根据各应用的配置来判断是否存在手机版访问配置文件
            $publicAccess = array('message', 'register', 'feed');

            if ((APP_NAME == 'classroom' || APP_NAME == 'exams' || APP_NAME == 'group' || APP_NAME == 'home' || APP_NAME == 'live'
                || APP_NAME == 'mall' || APP_NAME == 'wenda' || APP_NAME == 'public' || APP_NAME == 'school') && !in_array(strtolower(MODULE_NAME), $publicAccess) &&
                strtolower(ACTION_NAME) != 'message' && isMobile() || isiPad() && in_array('wap', C('DEFAULT_APPS'))) {
                $this->is_pc  = false;
                $this->is_wap = true;
                $this->assign('is_wap', $this->is_wap);
            } else {
                $this->is_pc  = true;
                $this->is_wap = false;
                $this->assign('is_pc', $this->is_pc);
            }
        }

        //导航栏分类导航
        $config = model('Xdata')->get('admin_Config:index_item');

        if (!$_SESSION['mid']) {
            $_SESSION['mid'] = model('Passport')->getCookieUid();
        }
        //是否为机构管理员
        if (is_school($_SESSION['mid'])) {
            $school_info = M('school')->where(array('uid' => $_SESSION['mid'], 'is_del' => 0, 'status' => 1))->field('id,doadmin')->find();
//            || !$school_info['doadmin']
            if (!$school_info['id']) {
                $this->assign("school_info", 0);
            } else {
                $this->assign("school_info", $school_info);
            }
            $this->assign("is_school", is_school($_SESSION['mid']));
        }

        //用户身份 如有多个以最高的那个
        $user_ground_info = M('user_group_link')->where('uid = ' . $_SESSION['mid'])->join('el_user_group On el_user_group_link.user_group_id = el_user_group.user_group_id')->order('ctime asc')->field('el_user_group.user_group_id,user_group_name')->find();
        if ($user_ground_info['user_group_id'] == 2) {
            $user_ground_info['user_group_name'] = '';
        }

        //搜索关键字
        $search_keywords = M('search_keywords')->where('is_del = 0')->order('sort asc')->field('id,sk_name,sk_url,sk_text,is_color')->limit(10)->select();

        $this->assign('site', $this->site);
        $this->assign('register_type', $register_type);
        $this->assign('site_top_nav', $GLOBALS['ts']['site_top_nav']);
        $this->assign('site_bottom_nav', $GLOBALS['ts']['site_bottom_nav']);
        $this->assign('site_bottom_child_nav', $GLOBALS['ts']['site_bottom_child_nav']);
        $this->assign('site_nav_apps', $GLOBALS['ts']['site_nav_apps']);
        $this->assign('user_ground_name', $user_ground_info['user_group_name']);
        $this->assign('search_keywords', $search_keywords);
        $this->assign('menuList', $searchSelect);
        $this->assign('my_uname', getUserName($_SESSION['mid']));
        $this->assign('marketConf', $marketConf);
        //获取地区
        /* $area_info = M('area')->where(array('area_id'=>$this->getVisitCity() ?:'110100'))->field('title,pid')->find();
        if($area_info['title'] == '市辖区' || $area_info['title'] == '市' || $area_info['title'] == '县' || $area_info['title'] == '区'){
        $visitRegion = M('area')->where(array('area_id'=>$area_info['pid']))->getField('title');
        }
        $this->assign('visitRegion',$visitRegion ? $visitRegion : $area_info['title']);
        $this->assign('temp_id',$this->getVisitCity());*/

        $this->assign('youtu_status', $this->youtu_status);
        if ($this->youtu_status === 1) {
            // 人脸登录
            $youtuscene = model('Xdata')->get('admin_Config:youtuscene');
            if ($youtuscene && in_array('login', $youtuscene['scene'])) {
                $this->assign('face_login', 1);
            }
        }
        return true;
    }

    /**
     * 应用信息初始化
     *
     * @access private
     * @return void
     */
    private function initApp()
    {
        //是否为核心的应用
        if (in_array(APP_NAME, C('DEFAULT_APPS'))) {
            return true;
        }

        //加载后台已安装应用列表
        $GLOBALS['ts']['app'] = $this->app = model('App')->getAppByName(APP_NAME);

        if (empty($this->app) || !$this->app) {
            $this->error('此应用不存在');
            return false;
        }
        if ($this->app['status'] == 0) {
            $this->error('此应用已经关闭');
            return false;
        }
        Addons::hook('core_filter_init_app');
        return true;
    }

    /**
     * 用户信息初始化
     * @access private
     * @return void
     */
    private function initUser()
    {
        // 邀请跳转
        if (isset($_GET['invite']) && APP_NAME . '/' . MODULE_NAME != 'classroom/Public') {
            //redirect(U('public/Register/index', array('invite'=>t($_GET['invite']))));exit();
            redirect(U('classroom/Public/step2', array('invite' => t($_GET['invite']))));exit();
        }
        // 验证登录
        if (model('Passport')->needLogin()) {
            if (defined('LOGIN_URL')) {
                redirect(LOGIN_URL);
            } else {
                if (APP_NAME == 'admin') {
                    if (MODULE_NAME != "Public" && !model('Passport')->checkAdminLogin()) {
                        redirect(U('admin/Public/login'));exit();
                    }
                } else {
                    redirect(U('public/Passport/login'));exit();
                }
            }
        }

        //判断登录有效期
        /*
        $activeTime  = cookie('ST_ACTIVE_TIME');
        if($activeTime < time() && APP_NAME != 'admin' && ACTION_NAME !='login'){
        unset($_SESSION['mid']);
        cookie('TSV3_LOGGED_USER',null);
        $this->assign('jumpUrl',U('public/Passport/login'));
        $this->error(L('PUBLIC_TIME_OUT'));exit();
        }else{
        cookie('TSV3_ACTIVE_TIME',time()+60*60*24);
        }*/
        $activeTime = cookie('TSV3_ACTIVE_TIME');
        $login      = model('Passport')->needLogin();
        if ($activeTime < time() && APP_NAME != 'admin' && $login == true) {
            unset($_SESSION['mid']);
            cookie('TSV3_LOGGED_USER', null);
            $this->assign('jumpUrl', U('public/Passport/login'));
            $this->error(L('PUBLIC_TIME_OUT'));exit();
        } else {
            cookie('TSV3_ACTIVE_TIME', time() + 60 * 30);
        }

        //当前登录者uid
        $GLOBALS['ts']['mid'] = $this->mid = intval($_SESSION['mid']) ? intval($_SESSION['mid']) : intval(model('Passport')->getCookieUid());

        //当前访问对象的uid
        $GLOBALS['ts']['uid'] = $this->uid = intval($_REQUEST['uid'] == 0 ? $this->mid : $_REQUEST['uid']);

        // 验证站点访问权限
        // 验证应用访问权限

        // 获取用户基本资料
        $GLOBALS['ts']['user'] = !empty($this->mid) ? $this->user = model('User')->getUserInfo($this->mid) : array();

        if ($this->mid != $this->uid) {
            $GLOBALS['ts']['_user'] = !empty($this->uid) ? model('User')->getUserInfo($this->uid) : array();
        } else {
            $GLOBALS['ts']['_user'] = $GLOBALS['ts']['user'];
        }
        $res = M("zy_teacher")->where("uid=" . $this->mid)->find();
//        $res = model( 'UserGroupLink' )->where('uid='.$this->mid.' and user_group_id=3')->getField('uid');
        $GLOBALS['ts']['teacher'] = $res ? 1 : 0;
        // 未初始化
        $module_arr = array('Register' => 1, 'Passport' => 1, 'Account' => 1);
        if (0 < $this->mid && 0 == $this->user['is_init'] && APP_NAME != 'admin' && !isset($module_arr[MODULE_NAME])) {
            // 注册完成后就开启此功能
            if ($this->user['is_active'] == '0') {
                U('public/Register/waitForActivation', 'uid=' . $this->mid, true);
            } else {
                $init_config = model('Xdata')->get('admin_Config:register');
                if ($init_config['photo_open']) {
                    U('public/Register/step2', '', true);
                }
                if (false && $init_config['tag_open']) {
                    U('public/Register/step3', '', true);
                }
                if ($init_config['interester_open']) {
                    U('public/Register/step4', '', true);
                }
                model('Register')->overUserInit($GLOBALS['ts']['mid']);
                U('public/Register/index', '', true);
            }
        }

        //应用权限判断
        if (!empty($this->app) && $this->app['status'] == 0) {
            $this->error('此应用已经关闭');
        }
        if ($this->uid > 0) {
            //当前用户的所有已添加的应用
            // $GLOBALS['ts']['_userApp']  = $userApp =  model('UserApp')->getUserApp($this->uid);
            //当前用户的统计数据
            $GLOBALS['ts']['_userData'] = $userData = model('UserData')->getUserData($this->uid);
            //$userCredit                 = model('Credit')->getUserCredit($this->uid);
            $this->assign('userCredit', $userCredit);
            $this->assign('_userData', $userData);
            $this->assign('_userApp', $userApp);
        }

        // 获取当前Js语言包
        $this->langJsList = setLangJavsScript();
        //获取未读私信消息数量
        $unreadnum = model('Message')->getUnreadMessageCount($this->mid);
        //获取未读的系统消息数量
        $systemnum = D('notify_message')->where('uid=' . $this->mid . ' and is_read=0')->count();
        //获取评论未读数
        $commentnum = D('ZyComment')->where(array('fid' => $this->mid, 'is_del' => 0, 'is_read' => 0))->count();

        $this->assign("unreadnum", $unreadnum);
        $this->assign("systemnum", $systemnum);
        $this->assign("commentnum", $commentnum);
        $this->assign('mid', $this->mid); //登录者
        $this->assign('uid', $this->uid); //访问对象
        $this->assign('user', $this->user); //当前登录的人

        $this->assign('initNums', model('Xdata')->getConfig('weibo_nums', 'feed'));
        Addons::hook('core_filter_init_user');
        return true;
    }

    /**
     * 重设访问对象的用户信息 主要用于重写等地方
     * @return void
     */
    public function reinitUser($uid = '')
    {
        if (empty($uid) || $this->mid == $uid) {
            return true;
        }

        $GLOBALS['ts']['uid']   = $_REQUEST['uid']   = $this->uid   = $uid;
        $GLOBALS['ts']['_user'] = model('User')->getUserInfo($this->uid);
        //当前用户的所有已添加的应用
        $GLOBALS['ts']['_userApp'] = $userApp = model('UserApp')->getUserApp($this->uid);
        //当前用户的统计数据
        $GLOBALS['ts']['_userData'] = $userData = model('UserData')->getUserData($this->uid);
        //$userCredit                 = model('Credit')->getUserCredit($this->uid);

        $this->assign('uid', $this->uid); //访问对象
        $this->assign('_userData', $userData);
        $this->assign('_userApp', $userApp);
        $this->assign('userCredit', $userCredit);
    }

    /**
     * 魔术方法 有不存在的操作的时候
     * @access public
     * @param string $method 方法名
     * @param array $parms
     * @return mix
     */
    public function __call($method, $parms)
    {
        if (0 === strcasecmp($method, ACTION_NAME)) {
            // 检查扩展操作方法
            $_action = C('_actions_');
            if ($_action) {
                // 'module:action'=>'callback'
                if (isset($_action[MODULE_NAME . ':' . ACTION_NAME])) {
                    $action = $_action[MODULE_NAME . ':' . ACTION_NAME];
                } elseif (isset($_action[ACTION_NAME])) {
                    // 'action'=>'callback'
                    $action = $_action[ACTION_NAME];
                }
                if (!empty($action)) {
                    call_user_func($action);
                    return;
                }
            }
            // 如果定义了_empty操作 则调用
            if (method_exists($this, '_empty')) {
                $this->_empty($method, $parms);
            } else {
                // 检查是否存在默认模版 如果有直接输出模版
                $this->display();
            }
        } elseif (in_array(strtolower($method), array('ispost', 'isget', 'ishead', 'isdelete', 'isput'))) {
            return strtolower($_SERVER['REQUEST_METHOD']) == strtolower(substr($method, 2));
        } else {
            throw_exception(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
        }
    }

    /**
     * 模板Title
     * @access public
     * @param mixed $input 要
     * @return
     */
    public function setTitle($title = '')
    {
        Addons::hook('core_filter_set_title', $title);
        $this->assign('_title', $title);
    }

    /**
     * 模板keywords
     * @access public
     * @param mixed $input 要
     * @return
     */
    public function setKeywords($keywords = '')
    {
        $this->assign('_keywords', $keywords);
    }

    /**
     * 模板description
     * @access public
     * @param mixed $input 要
     * @return
     */
    public function setDescription($description = '')
    {
        $this->assign('_description', $description);
    }

    /**
     * 模板变量赋
     * @access protected
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的
     * @return void
     */
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->tVar = array_merge($this->tVar, $name);
        } elseif (is_object($name)) {
            foreach ($name as $key => $val) {
                $this->tVar[$key] = $val;
            }

        } else {
            $this->tVar[$name] = $value;
        }
    }

    /**
     * 魔术方法：注册模版变量
     * @access protected
     * @param string $name 模版变量
     * @param mix $value 变量值
     * @return mixed
     */
    public function __set($name, $value)
    {
        $this->assign($name, $value);
    }

    /**
     * 取得模板显示变量的值
     * @access protected
     * @param string $name 模板显示变量
     * @return mixed
     */
    protected function get($name)
    {
        if (isset($this->tVar[$name])) {
            return $this->tVar[$name];
        } else {
            return false;
        }

    }

    /**
     * Trace变量赋值
     * @access protected
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     * @return void
     */
    protected function trace($name, $value = '')
    {
        if (is_array($name)) {
            $this->trace = array_merge($this->trace, $name);
        } else {
            $this->trace[$name] = $value;
        }

    }

    /**
     * 模板显示
     * 调用内置的模板引擎显示方法
     * @access protected
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类
     * @return voi
     */
    protected function display($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        $this->is_pc ? $templateFile : $templateFile = ($templateFile ?: ACTION_NAME) . "_w3g";
        echo $this->fetch($templateFile, $charset, $contentType, true);
    }

    /**
     *  获取输出页面内容
     * 调用内置的模板引擎fetch方法
     * @access protected
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类
     * @return strin
     */
    protected function fetch($templateFile = '', $charset = 'utf-8', $contentType = 'text/html', $display = false)
    {
        $this->assign('seo', $this->seo);
        $this->assign('appCssList', $this->appCssList);
        $this->assign('langJsList', $this->langJsList);
        Addons::hook('core_display_tpl', array('tpl' => $templateFile, 'vars' => $this->tVar, 'charset' => $charset, 'contentType' => $contentType, 'display' => $display));
        return fetch($templateFile, $this->tVar, $charset, $contentType, $display);
    }

    /**
     * 操作错误跳转的快捷方
     * @access protected
     * @param string $message 错误信息
     * @param Boolean $ajax 是否为Ajax方
     * @return voi
     */
    protected function error($message, $ajax = false)
    {
        Addons::hook('core_filter_error_message', $message);
        $this->_dispatch_jump($message, 0, $ajax);
    }

    protected function page404($message)
    {
        $this->assign('site_closed', $this->site['site_closed']);
        $this->assign('message', $message);
        $this->display(THEME_PATH . '/page404.html');
    }
    /**
     * 操作成功跳转的快捷方
     * @access protected
     * @param string $message 提示信息
     * @param Boolean $ajax 是否为Ajax方
     * @return voi
     */
    protected function success($message, $ajax = false)
    {
        Addons::hook('core_filter_success_message', $message);
        $this->_dispatch_jump($message, 1, $ajax);
    }

    /**
     * Ajax方式返回数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param String $info 提示信息
     * @param boolean $status 返回状态
     * @param String $status ajax返回类型 JSON XML
     * @return void
     */
    protected function ajaxReturn($data, $info = '', $status = 1, $type = 'JSON')
    {
        // 保证AJAX返回后也能保存日志
        if (C('LOG_RECORD')) {
            Log::save();
        }

        $result           = array();
        $result['status'] = $status;
        $result['info']   = $info;
        $result['data']   = $data;
        if (empty($type)) {
            $type = C('DEFAULT_AJAX_RETURN');
        }

        if (strtoupper($type) == 'JSON') {
            // 返回JSON数据格式到客户端 包含状态信息
            header("Content-Type:text/html; charset=utf-8");
            exit(json_encode($result));
        } elseif (strtoupper($type) == 'XML') {
            // 返回xml格式数据
            header("Content-Type:text/xml; charset=utf-8");
            exit(xml_encode($result));
        } elseif (strtoupper($type) == 'EVAL') {
            // 返回可执行的js脚本
            header("Content-Type:text/html; charset=utf-8");
            exit($data);
        } else {
            // TODO 增加其它格式
        }
    }

    /**
     * Action跳转(URL重定向） 支持指定模块和延时跳转
     * @access protected
     * @param string $url 跳转的URL表达式
     * @param array $params 其它URL参数
     * @param integer $delay 延时跳转的时间 单位为秒
     * @param string $msg 跳转提示信息
     * @return void
     */
    protected function redirect($url, $params = array(), $delay = 0, $msg = '')
    {
        if (C('LOG_RECORD')) {
            Log::save();
        }

        $url = U($url, $params);
        redirect($url, $delay, $msg);
    }

    /**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     * @param string $message 提示信息
     * @param Boolean $status 状态
     * @param Boolean $ajax 是否为Ajax方式
     * @access private
     * @return void
     */
    private function _dispatch_jump($message, $status = 1, $ajax = false)
    {
        // 判断是否为AJAX返回
        if ($ajax || $this->isAjax()) {
            $data['jumpUrl'] = false;
            if ($this->get('jumpUrl')) {
                $data['jumpUrl'] = $this->get('jumpUrl');
            }
            $this->ajaxReturn($data, $message, $status);
        }
        // 提示标题
        $this->assign('msgTitle', $status ? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        //如果设置了关闭窗口，则提示完毕后自动关闭窗口
        if ($this->get('closeWin')) {
            $this->assign('jumpUrl', 'javascript:window.close();');
        }

        $this->assign('status', $status); // 状态
        empty($message) && ($message = $status == 1 ? '操作成功' : '操作失败');
        $this->assign('message', $message); // 提示信息
        //保证输出不受静态缓存影响
        C('HTML_CACHE_ON', false);
        if ($status) {
            //发送成功信息
            // 成功操作后默认停留1秒
            if (!$this->get('waitSecond')) {
                $this->assign('waitSecond', "2");
            }

            // 默认操作成功自动返回操作前页面
            if (!$this->get('jumpUrl')) {
                $this->assign("jumpUrl", $_SERVER["HTTP_REFERER"]);
            }

            //sociax:2010-1-21
            //$this->display(C('TMPL_ACTION_SUCCESS'));
            if ($this->is_wap) {
                echo $this->fetch(THEME_PATH . '/success_w3g.html', $charset = 'utf-8', $contentType = 'text/html', true);
            } else {
                $this->display(THEME_PATH . '/success.html');
            }
        } else {
            //发生错误时候默认停留3秒
            if (!$this->get('waitSecond')) {
                $this->assign('waitSecond', "2");
            }

            // 默认发生错误的话自动返回上页
            if (!$this->get('jumpUrl')) {
                $this->assign('jumpUrl', "javascript:history.back(-1);");
            }

            //sociax:2010-1-21
            //$this->display(C('TMPL_ACTION_ERROR'));
            if ($this->is_wap) {
                echo $this->fetch(THEME_PATH . '/success_w3g.html', $charset = 'utf-8', $contentType = 'text/html', true);
            } else {
                $this->display(THEME_PATH . '/success.html');
            }
        }
        if (C('LOG_RECORD')) {
            Log::save();
        }

        // 中止执行  避免出错后继续执行
        exit;
    }

    /**
     * 是否AJAX请求
     * @access protected
     * @return bool
     */
    protected function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            if ('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                return true;
            }

        }
        if (!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
        // 判断Ajax方式提交
        {
            return true;
        }

        return false;
    }
    /**
     * @name 获取用户访问的地区信息
     */
    public function getVisitCity()
    {
        $_SESSION['visit_city'] = cookie('visit_city');
        if (!$_SESSION['visit_area'] && !$_SESSION['visit_city']) {
            $area = getCurrentCity();
            if ($area['city_id']) {
                $_SESSION['visit_area'] = $area;
                $_SESSION['visit_city'] = $area['city_id'];
            }
        }
        return $_SESSION['visit_city'] ?: ($_SESSION['visit_area']['city_id'] ?: '110100');
    }
}; //类定义结束
