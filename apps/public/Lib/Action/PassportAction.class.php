<?php
/**
 * PassportAction 通行证模块
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
tsload(APPS_PATH . '/classroom/Lib/Action/CommonAction.class.php');
class PassportAction extends CommonAction
{

    private $passport;
    private $_user_model;
    private $_register_model; // 注册模型字段
    private $_config;
    private $wx_config;

    /**
     * 初始化
     * @return void
     */
    public function _initialize()
    {
        $this->passport        = model('Passport');
        $this->_user_model     = model('User');
        $this->_config         = model('Xdata')->get('admin_Config:register');
        $this->_register_model = model('Register');
        $this->wx_config       = model('Xdata')->lget('login');

        parent::_initialize();
    }

    /**
     * 通行证首页
     * @return void
     */
    public function index()
    {
        // 如果设置了登录前的默认应用
        // U('welcome','',true);
        // 如果没设置
        $this->login();
    }

    public function landed()
    {
        //-------配置
        $AppID    = $this->wx_config['wx_app_id'];
        $callback = U('public/Passport/smessage'); //回调地址

        //微信登录
        session_start();
        //-------生成唯一随机串防CSRF攻击
        $state                = md5(uniqid(rand(), true));
        $_SESSION["wx_state"] = $state; //存到SESSION
        $callback             = urlencode($callback);
        $wxurl                = "https://open.weixin.qq.com/connect/qrconnect?appid=" . $AppID . "&redirect_uri=" . $callback .
            "&response_type=code&scope=snsapi_login&state=" . $state . "#wechat_redirect";

        header("Location: $wxurl");
    }

    public function smessage()
    {
        if ($_GET['state'] != $_SESSION["wx_state"]) {
            $this->assign('jumpUrl', U('classroom/Index/index'));
            $this->error("获取用户信息失败");
        }
        $wx_auth = session('wx_auth');
        // 如果没有授权信息，通过code兑换
        if (!$wx_auth) {
            $AppID     = $this->wx_config['wx_app_id'];
            $AppSecret = $this->wx_config['wx_app_secret'];

            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $AppID . '&secret=' . $AppSecret .
                '&code=' . $_GET['code'] . '&grant_type=authorization_code';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $json = curl_exec($ch);
            curl_close($ch);
            $wx_auth = json_decode($json, 1);
            session('wx_auth', $wx_auth);
        }

        $user_info = session($wx_auth['openid']);
        if (!$user_info || $user_info['user_info_time'] <= time()) {
            //通过  access_token 与 openid 重新获取用户信息
            $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $wx_auth['access_token'] . '&openid=' .
                $wx_auth['openid'] . '&lang=zh_CN';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $json = curl_exec($ch);
            curl_close($ch);
            //得到 用户资料
            $user_info                   = json_decode($json, 1);
            $user_info['user_info_time'] = time() + 1800;
            session($wx_auth['openid'], $user_info);
        }
//        //缺失access_token参数
        //        if($user_info['errcode'] == 41001 || !$user_info){
        //            $this->assign('jumpUrl', U('classroom/Index/index'));;
        //            $this->error("获取用户信息失败");
        //        }
        $uid = M('login')->where(array('oauth_token' => $user_info['unionid'], 'oauth_token_secret' => $user_info['openid']))->getField('uid');
        if ($uid) {
            $login = M('user')->where(array('uid' => $uid))->getField('login');
            $login = model('Passport')->loginLocalWithoutPassword($login);
            if ($login) {
                $this->assign('jumpUrl', U('classroom/Index/index'));
                $this->success("同步登录成功");
            }
        } else {
            $this->assign($user_info);
            $this->display();
        }
    }

    public function authentication_wx()
    {
        //-------配置
        $AppID    = $this->wx_config['wx_app_id'];
        $callback = U('public/Passport/weChatCertified'); //回调地址

        //微信登录
        session_start();
        //-------生成唯一随机串防CSRF攻击
        $state                = md5(uniqid(rand(), true));
        $_SESSION["wx_state"] = $state; //存到SESSION
        $callback             = urlencode($callback);
        $wxurl                = "https://open.weixin.qq.com/connect/qrconnect?appid=" . $AppID . "&redirect_uri=" . $callback .
            "&response_type=code&scope=snsapi_login&state=" . $state . "#wechat_redirect";

        header("Location: $wxurl");
    }

    public function weChatCertified()
    {
        if ($_GET['state'] != $_SESSION["wx_state"]) {
            $this->assign('jumpUrl', U('classroom/Index/index'));
            $this->error("获取用户信息失败");
        }
        $AppID     = $this->wx_config['wx_app_id'];
        $AppSecret = $this->wx_config['wx_app_secret'];

        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $AppID . '&secret=' . $AppSecret .
            '&code=' . $_GET['code'] . '&grant_type=authorization_code';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $json = curl_exec($ch);
        curl_close($ch);

        $arr = json_decode($json, 1);

        //得到 access_token 与 openid
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $arr['access_token'] . '&openid=' .
            $arr['openid'] . '&lang=zh_CN';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $json = curl_exec($ch);
        curl_close($ch);
        //得到 用户资料
        $user_info = json_decode($json, 1);

        $is_login = M('login')->where(array('uid' => $this->mid, 'type' => 'weixin'))->find();
        if (!$is_login) {
            $data['uid']                = $this->mid;
            $data['type']               = "weixin";
            $data['type_uid']           = $user_info['unionid'];
            $data['oauth_token']        = $user_info['unionid'];
            $data['oauth_token_secret'] = $user_info['openid'];
            $res                        = M('login')->add($data);
            if ($res) {
                $this->assign('jumpUrl', U('classroom/User/setInfo', array('tab' => 3)));
                $this->success("绑定成功");
            } else {
                $this->assign('jumpUrl', U('classroom/User/setInfo', array('tab' => 3)));
                $this->error("绑定失败，请重试");
            }
        } else {
            $this->assign('jumpUrl', U('classroom/User/setInfo', array('tab' => 3)));
            $this->error("已绑定");
        }
    }

    /**
     * 登录中转操作
     */
    public function redirect()
    {

    }

    /**
     * 默认登录页
     * @return void
     */
    public function login()
    {
        U('classroom/Index/index', '', true); //dengjb 2014-5-8
    }

    /**
     * 登录页
     * @return void
     */
    public function login_g()
    {
        $this_mhm_id = $_GET['this_mhm_id'];
        // 添加样式
        $this->appCssList[] = 'login.css';
        if (model('Passport')->isLogged()) {
            U('classroom/Index/index', '', true); //dengjb 个人中心
        }

        // 获取邮箱后缀
        $registerConf = model('Xdata')->get('admin_Config:register');
        $this->assign('emailSuffix', explode(',', $registerConf['email_suffix']));
        $this->assign('register_type', $registerConf['register_type']);
        $data = model('Xdata')->get("admin_Config:seo_login");
        !empty($data['title']) && $this->setTitle($data['title']);
        !empty($data['keywords']) && $this->setKeywords($data['keywords']);
        !empty($data['des']) && $this->setDescription($data['des']);

        $login_bg = getImageUrlByAttachId($this->site['login_bg']);
        if (empty($login_bg)) {
            $login_bg = APP_PUBLIC_URL . '/image/body-bg2.jpg';
        }

        $reurl = $_SERVER['HTTP_REFERER'];
        $this->assign('reurl', $reurl);

        $this->assign('login_bg', $login_bg);
        $this->assign('this_mhm_id', $this_mhm_id);

        $this->display('login');
    }
    /**
     * 快速登录
     */
    public function quickLogin()
    {
        $registerConf = model('Xdata')->get('admin_Config:register');
        $this->assign('register_type', $registerConf['register_type']);
        $this->display();
    }

    /**
     * 用户登录
     * @return void
     */
    public function doLogin()
    {
        $login    = addslashes($_POST['login_email']);
        $password = trim($_POST['login_password']);
        $remember = intval($_POST['login_remember']);
        $result   = $this->passport->loginLocal($login, $password, $remember);
        if (!$result) {
            $status = 0;
            $info   = $this->passport->getError();
            $data   = 0;
        } else {
            $status = 1;
            $info   = $this->passport->getSuccess();
            //$data     = ($GLOBALS['ts']['site']['home_url'])?$GLOBALS['ts']['site']['home_url']:0;
            $data = U('classroom/Index/index');
        }

        $this->ajaxReturn($data, $info, $status);
    }

    /**
     * 注销登录
     * @return void
     */
    public function logout()
    {
	
		  
		 $this->passport->logoutLocal();
         $this->mzSuccess("退出成功！");	
		
    }

    /**
     * 找回密码页面
     * @return void
     */
    public function findPassword()
    {

        // 添加样式
        $this->appCssList[] = 'login.css';

        $this->display();
    }

    /**
     * 通过安全问题找回密码
     * @return void
     */
    public function doFindPasswordByQuestions()
    {
        $this->display();
    }

    /**
     * 通过Email找回密码
     */
    public function doFindPasswordByEmail()
    {
        unset($_SESSION['setpwduser']);
        $_POST["email"] = t($_POST["email"]);
        $verify         = t($_POST["everify"]);
        if (!$this->_isEmailString($_POST['email'])) {
            $this->error(L('PUBLIC_EMAIL_TYPE_WRONG'));
        }
        $user = model("User")->where('`email`="' . $_POST["email"] . '"')->find();
        if (!$user) {
            $this->mzError('找不到该邮箱注册信息!');
        }
        /* if($user['mail_activate']==0){
        $this->mzError('此邮箱未通过验证，无法使用！');
        } */
        //检查验证码
        if (md5(strtoupper($verify)) != $_SESSION['verify']) {
            $this->mzError('验证码错误！');
        }
        $result = $this->_sendPasswordEmail($user);
        if ($result) {
            $_SESSION['setpwduser'] = $user; //将找回密码的邮箱放入session
            $nowtime                = time();
            $_SESSION['setpwdtime'] = $nowtime + 60; //找回密码限制时间
            $this->mzSuccess('发送成功，请注意查收邮件');
        } else {
            $this->mzError('操作失败，请重试');
        }
    }
    /**
     * 找回密码邮件发送成功页面
     */
    public function okemailadd()
    {
        $email = $_SESSION['setpwduser']; //取出邮箱

        if (empty($email)) {
            $this->error("非法操作！");
        }
        $emaildata = explode("@", $email['email']);
        $emailhr   = $emaildata[1];
        $time      = $_SESSION['setpwdtime'];
        $this->assign("time", $time - time());
        $this->assign("email", $email['email']);
        $this->assign("emailhr", $emailhr);
        $this->display();

    }
    /**
     * 重新发送邮件
     */
    public function fasemail()
    {
        $email = $_SESSION['setpwduser'];

        $time    = $_SESSION['setpwdtime'];
        $nowtime = time();
        if (empty($email)) {
            $this->redirect($GLOBALS['ts']['site']['home_url']);
        }
        if ($time > $nowtime) {
            $this->mzError("请" . $time - $nowtime . "秒后重试！");
        }
        $result = $this->_sendPasswordEmail($email);
        if ($result) {
            $nowtime += 60;
            $_SESSION['setpwdtime'] = $nowtime;
            $this->success("发送成功，请注意查收！");
        } else {
            $this->mzError('操作失败，请重试');
        }
    }

    /**
     * 找回密码页面
     */
    private function _sendPasswordEmail($user)
    {
        if ($user['uid']) {
            $this->appCssList[] = 'login.css'; // 添加样式
            $code               = md5($user["uid"] . '+' . $user["password"] . '+' . rand(1111, 9999));
            $config['reseturl'] = U('public/Passport/resetPassword', array('code' => $code));
            //设置旧的code过期
            D('FindPassword')->where('uid=' . $user["uid"])->setField('is_used', 1);
            //添加新的修改密码code
            $add['uid']     = $user['uid'];
            $add['email']   = $user['email'];
            $add['code']    = $code;
            $add['is_used'] = 0;
            $result         = D('FindPassword')->add($add);
            if ($result) {
                model('Notify')->sendNotify($user['uid'], 'password_reset', $config);
                return true;
            } else {
                return false;
            }
        }
    }

    public function doFindPasswordByEmailAgain()
    {
        $_POST["email"] = t($_POST["email"]);
        $user           = model("User")->where('`email`="' . $_POST["email"] . '"')->find();
        if (!$user) {
            $this->error('找不到该邮箱注册信息');
        }

        $result = $this->_sendPasswordEmail($user);
        if ($result) {
            $this->success('发送成功，请注意查收邮件');
        } else {
            $this->error('操作失败，请重试');
        }
    }

    /**
     * 通过手机短信找回密码
     * @return void
     */
    public function doFindPasswordBySMS()
    {
        $this->display();
    }

    /**
     * 重置密码页面
     * @return void
     */
    public function resetPassword()
    {
        $code = t($_GET['code']);
        $this->_checkResetPasswordCode($code);
        $this->assign('code', $code);
        $this->display();
    }

    /**
     * 执行重置密码操作
     * @return void
     */
    public function doResetPassword()
    {
        $code      = t($_POST['code']);
        $user_info = $this->_checkResetPasswordCode($code);

        $password   = trim($_POST['password']);
        $repassword = trim($_POST['repassword']);

        if (!model('Register')->isValidPassword($password, $repassword)) {
            $this->mzError(model('Register')->getLastError());
        }
        /* echo $repassword.'<br>'.$password;
        die(); */
        $map['uid']         = $user_info['uid'];
        $data['login_salt'] = rand(10000, 99999);
        $data['password']   = md5(md5($password) . $data['login_salt']);
        $res                = model('User')->where($map)->save($data);
        if ($res) {
            D('find_password')->where('uid=' . $user_info['uid'])->setField('is_used', 1);
            model('User')->cleanCache($user_info['uid']);
            $this->assign('jumpUrl', U('public/Passport/login'));
            //邮件中会包含明文密码，很不安全，改为密文的
            $config['newpass'] = $this->_markPassword($password); //密码加星号处理
            model('Notify')->sendNotify($user_info['uid'], 'password_setok', $config);
            $_SESSION['setpwduser'];
            $this->mzSuccess(L('PUBLIC_PASSWORD_RESET_SUCCESS'));
        } else {
            $this->mzError(L('PUBLIC_PASSWORD_RESET_FAIL'));
        }
    }

    /**
     * 检查重置密码的验证码操作
     * @return void
     */
    private function _checkResetPasswordCode($code)
    {
        $map['code']    = $code;
        $map['is_used'] = 0;
        $uid            = D('find_password')->where($map)->getField('uid');
        if (!$uid) {
            $this->assign('jumpUrl', U('public/Passport/findPassword'));
            $this->error('重置密码链接已失效，请重新找回');
        }
        $user_info = model('User')->where("`uid`={$uid}")->find();

        if (!$user_info) {
            $this->redirect = U('public/Passport/login');
        }

        return $user_info;
    }

    /*
     * 验证安全邮箱
     * @return void
     */
    public function doCheckEmail()
    {
        $email = t($_POST['email']);
        if ($this->_isEmailString($email)) {
            die(1);
        } else {
            die(0);
        }
    }

    /*
     * 正则匹配，验证邮箱格式
     * @return integer 1=成功 ""=失败
     */
    private function _isEmailString($email)
    {
        return preg_match("/[_a-zA-Z\d\-\.]+@[_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+$/i", $email) !== 0;
    }

    /*
     * 替换密码为星号
     * @return integer 1=成功 ""=失败
     */
    private function _markPassword($str)
    {
        $c = strlen($str) / 2;
        return preg_replace('|(?<=.{' . (ceil($c / 2)) . '})(.{' . floor($c) . '}).*?|', str_pad('', floor($c), '*'), $str, 1);
    }

    /**
     * 登录或注册页面
     */
    public function regLogin()
    {
        $data = $this->fetch("reg_login");
        exit(json_encode($data));
    }

    /**
     * 登录或注册页面
     */
    public function reg()
    {
        if ($this->mid) {
            header("Location: " . U('classroom/Index/index'));
        }
        $registerConf = model('Xdata')->get('admin_Config:register');
        $interest     = M('zy_currency_category')->where('pid=0')->findALL();
        $school       = model('School')->findALL();
        $school_title = array_column($school, 'title', 'id');
        $this_mhm_id  = explode('H', $_GET['this_mhm_id']);
        if ($this_mhm_id[1]) {
            $this->assign('mount_reg', true);
        }
        $this_mhm_id = $this_mhm_id[0];
        if (!$this_mhm_id) {
            //获取默认机构
            $this_mhm_id = model('School')->getDefaultSchol('id');
            /* $this_mhm_id = M('user')->where('uid = 1') ->getField('mhm_id');
        $banid = M('school')->where('uid = 1') ->getField('id');
        if(!$banid)
        {
        if($this_mhm_id != $banid)
        {
        $data['mhm_id'] = $banid;
        M('school')->where('uid = 1') ->save($data);
        $this_mhm_id = $banid;
        }
        }*/
        }
        $this->assign('register_type', $registerConf['account_type']);
        $this->assign('interest', $interest);
        $this->assign('school_title', $school_title);
        $this->assign('this_mhm_id', $this_mhm_id);
        $this->display();
    }

    /**
     * 验证邮箱是否唯一
     * 异步方法
     */
    public function clickEmail()
    {
        $email = t($_POST['email']);
        $res   = M('user')->where(array('login' => $email))->find();
        if ($res) {
            echo 0;
            exit;
        } else {

            echo 1;
            exit;
        }
    }
    /**
     * 验证验证码是否正确
     * 异步方法
     */
    public function clickVerify()
    {
        $verify = t($_POST['verify']);
        if (md5(strtoupper($verify)) != $_SESSION['verify']) {
            echo 0;
            exit;
        } else {
            echo 1;
            exit;
        }
    }
    /**
     *验证用户名是否唯一
     * 异步方法
     */
    public function clickUname()
    {
        $uname = t($_POST['uname']);
        $res   = $this->_register_model->isValidName($uname);
        if ($res) {
            echo 1;
            exit;
        } else {
            echo 0;
            exit;
        }
    }
    /**
     *验证手机是否唯一
     * 异步方法
     */
    public function clickPhone()
    {
        $phone = $_POST['phone'];

        $res = M('user')->where(array('phone' => $phone))->find();
        if ($res) {
            echo 0;
            exit;
        } else {
            echo 1;
            exit;
        }
    }
    /**
     * 获取验证码
     */
    public function getVerify()
    {
    	// 检测是否在本站内打开
    	preg_match('/http(s)?:\/\/(.*)\.(.*\..*)/',SITE_URL,$matchs);
        if(stripos($_SERVER['HTTP_REFERER'],'.'.$matchs[3]) === false) {
        	http_response_code(403);
            exit;
        }
        $verifytime = $_SESSION['verifytime'];
        $nowtime    = time();
        if ($nowtime < $verifytime) {
            $xctime = $verifytime - $nowtime;
            $this->mzError("请" . $xctime . "秒后重新获取！");
            exit();
        }
        $phone = $_POST['phone'];
        $res   = M('user')->where(array('phone' => $phone))->find();
        if ($res) {
            $this->mzError('此手机号已被注册,请更换！');
        }
        $rnum    = rand(100000, 999900);
        $sendres = model('Sms')->send($phone, $rnum);
        if ($sendres) {
            //将验证码存入session
            $_SESSION['phoneverify'] = $rnum;
            //将号码存入session
            $_SESSION['getverphone'] = $phone;
            $nowtime += 60;
            $_SESSION['verifytime'] = $nowtime;

            //验证码入库
            $map['phone'] = $phone;
            $map['code']  = $rnum;
            $map['stime'] = time();
            M('ResphoneCode')->add($map);
            $this->mzSuccess("发送成功，请注意查收！");
        } else {
            $this->mzError('请检查短信配置');
        }

    }
    //手机注册下一步
    public function clickPhoneVer()
    {
        $phone    = $_POST['phone'];
        $verify   = intval($_POST['verify']);
        $verphone = $_SESSION['getverphone']; //取得获取验证码的手机
        $verifys  = $_SESSION['phoneverify']; //取得验证码
        if ($phone != $verphone || $verify != $verifys) {
            $this->mzError("对不起，验证码错误，请重试！");
        } else {
            $this->mzSuccess();
        }
    }
    /**
     * 异步注册
     */
    public function ajaxReg()
    {
        if ($this->mid) {
            header("Location: " . U('classroom/Index/index'));
        }
        $phone      = $_POST['phone'];
        $email      = t($_POST['email']);
        $uname      = t($_POST['uname']);
        $sex        = 1 == $_POST['sex'] ? 1 : 2;
        $password   = trim($_POST['password']);
        $profession = t($_POST['profession']);
        $intro      = t($_POST['intro']);
        $interest   = intval($_POST['interest']);
        //获取默认机构
        $default_school = model('School')->getDefaultSchol('id');
        $mhm_id         = intval($_POST['mhm_id']) ?: $default_school;
        $mount_reg      = $_POST['mount_reg'];
        $type           = 1 == $_POST['type'] ? 1 : 2;

        /* $repassword = trim($_POST['repassword']); */
        if (!$this->_register_model->isValidName($uname)) {
            $this->mzError($this->_register_model->getLastError());
        }
        if ($type == 1) {
            if (!$this->_register_model->isValidEmail($email)) {
                $this->mzError($this->_register_model->getLastError());
            }
            //检查验证码
            if (md5(strtoupper($_POST['verify'])) != $_SESSION['verify']) {
                $this->mzError('验证码错误');
            }
            $map['login'] = $email;
            $map['email'] = $email;
        } else {
            if (!preg_match("/^[1][34578]\d{9}$/", $phone)) {
                $this->mzError("手机号格式错误");
            }
            if ($phone != $_SESSION['getverphone'] || $_POST['verify'] != $_SESSION['phoneverify']) {
                $this->mzError("验证码错误");
            }

            $map['phone'] = $phone;
            $map['login'] = $phone;
        }

        if ($password == "" || strlen($password) < 6 || strlen($password) > 20) {
            $this->mzError("密码长度为6-20位");
        }
        $login_salt = rand(11111, 99999);

        if ($mount_reg) {
            $divideIntoConfig           = model('Xdata')->get('admin_Config:divideIntoConfig');
            $map['ouschool_buyer_time'] = time() + (86400 * floatval($divideIntoConfig['ouschool_buyer_day']));
            $map['ouschool_buyer_num']  = $divideIntoConfig['ouschool_buyer_num'];
        }
        $map['uname']      = $uname;
        $map['sex']        = $sex;
        $map['profession'] = $profession;
        $map['intro']      = $intro;
        $map['interest']   = $interest;
        $map['mhm_id']     = $mhm_id;
        $map['login_salt'] = $login_salt;
        $map['password']   = md5(md5($password) . $login_salt);
        $map['reg_ip']     = get_client_ip();
        $map['ctime']      = time();

        // 添加地区信息
        $map['location']                       = t($_POST['city_names']);
        $cityIds                               = t($_POST['city_ids']);
        $cityIds                               = explode(',', $cityIds);
        isset($cityIds[0]) && $map['province'] = intval($cityIds[0]);
        isset($cityIds[1]) && $map['city']     = intval($cityIds[1]);
        isset($cityIds[2]) && $map['area']     = intval($cityIds[2]);
        // 审核状态： 0-需要审核；1-通过审核
        $map['is_audit'] = $this->_config['register_audit'] ? 0 : 1;
        // 需求添加 - 若后台没有填写邮件配置，将直接过滤掉激活操作
        $isActive = $this->_config['need_active'] ? 0 : 1;
        if ($isActive == 0) {
            $emailConf = model('Xdata')->get('admin_Config:email');
            if (empty($emailConf['email_host']) || empty($emailConf['email_account']) || empty($emailConf['email_password'])) {
                $isActive = 1;
            }
        }
        $map['is_active']    = $isActive;
        $map['first_letter'] = getFirstLetter($uname);
        //如果包含中文将中文翻译成拼音
        if (preg_match('/[\x7f-\xff]+/', $map['uname'])) {
            //昵称和呢称拼音保存到搜索字段
            $map['search_key'] = $map['uname'] . ' ' . model('PinYin')->Pinyin($map['uname']);
        } else {
            $map['search_key'] = $map['uname'];
        }
        $map['login_num'] = 1;

        $uid = $this->_user_model->add($map);
        if ($uid) {
            // 添加至默认的用户组
            $userGroup = model('Xdata')->get('admin_Config:register');
            $userGroup = empty($userGroup['default_user_group']) ? C('DEFAULT_GROUP_ID') : $userGroup['default_user_group'];
            model('UserGroupLink')->domoveUsergroup($uid, implode(',', $userGroup));
            $data['oauth_token']        = getOAuthToken($uid); //添加app认证
            $data['oauth_token_secret'] = getOAuthTokenSecret();
            $data['uid']                = $uid;
            $savedata                   = $data;
            D('')->table(C('DB_PREFIX') . 'ZyLoginsync')->add($savedata);

            if ($type == 2) {
                $email = $phone;
            }

            D('Passport')->loginLocal($email, $password);
            model('Register')->overUserInit($uid);
            // M('user')->where(array('uid'=> $_SESSION['mid']))->setField('login_num','1');

            model('Notify')->sendNotify($_SESSION['mid'], 'public_account_Register');
            // 检测是否配置开启
            $youtuscene = model('Xdata')->get('admin_Config:youtuscene');
            if ($this->youtu_status === 1 && $youtuscene && (isset($youtuscene['login_force_verify']) && $youtuscene['login_force_verify'] == 1)) {
                $url = U('public/Passport/loginFaceVerify', ['verified_module' => 'check_face', 'redirect_url' => urlencode(U('classroom/Index/index'))]);
            } else {
                $url = U('classroom/Index/index');
            }
            $this->ajaxReturn($url, '恭喜您，注册成功', 1);
        } else {
            $this->mzError(L('PUBLIC_REGISTER_FAIL')); // 注册失败
        }
    }
    /**
     * 回调用户头像设置
     */
    public function setUserFace()
    {
        $data = model("User")->getUserInfo($this->mid);
        $this->assign("data", $data);
        $this->display();
    }

    /**
     * 异步登录
     */
    public function ajaxLogin()
    {
        
        $login    = addslashes($_POST['log_username']);
        $password = trim($_POST['log_pwd']);
        $remember = intval($_POST['login_remember']);
        $result   = $this->passport->loginLocal($login, $password, $remember);
    
        

        
        if (!$result) {
            $this->mzError($this->passport->getError());
            exit;
        } else {
            $login_num = M('user')->where(array('uid' => $_SESSION['mid']))->setInc('login_num');
            $this->mzSuccess("登录成功，努力加载中。。");
            exit;
        }

    }

    /**
     * 单点登录
     */
    public function syncLogin()
    {
        $params = array(
            'u' => $_GET['u'],
            'e' => $_GET['e'],
        );
        $date      = date('Ymd');
        $key       = 'Z^#Yu&hWx6*AoqRj';
        $mk_string = $key . '|' . $date . '|' . $_GET['u'];
        $en_value  = strtolower(md5($mk_string));
        if ($en_value == strtolower($_GET['e'])) {
            //获取配置的接口地址
            $config = model('Xdata')->get('user:getUser');
            if ($config['url']) {
                $url = $config['url'];
            } else {
                //默认地址
                $url = 'http://59.110.20.120:8181/thirdparty/eduline/getUser';
            }
            //发送请求
            $data = $this->request('get', $params, $url);
            $data = json_decode($data['body'], true);

            try {
                if (!$data) {
                    throw new Exception("提示:请检查获取用户信息接口返回数据格式");
                }
            } catch (Exception $e) {
                echo $e->getMessage();exit;
            }
            if ($data['success'] != true) {
                exit;
            }

            $data = current($data['result']);
            if ($data['gender'] == '男') {
                $sex = 1;
            } else {
                $sex = 2;
            }
            //验证成功,检测本地是否存在用户
            $userInfo = M('user')->where(['sync_uid' => $params['u']])->find();
            if ($userInfo) {
                //账号登录过
                $this->passport->loginLocal($userInfo['sync_uid'], $userInfo['password'], false, false, true);
                $save = [
                    'login_num'       => array('exp', 'login_num+1'),
                    'last_login_time' => time(),
                    'realName'        => $data['realName'],
                    'schoolName'      => $data['schoolName'],
                    'gradeName'       => $data['gradeName'],
                    'className'       => $data['className'],
                    'subjectName'     => $data['subjectName'],
                    //'title'     => $data['title'],
                    //'education' => $data['education'],
                    'cityName'        => $data['cityName'],
                    'v'               => $data['v'],
                    'sex'             => $sex,
                ];
                $this->_user_model->where(array('sync_uid' => $data['userId']))->save($save);
                $this->_user_model->cleanCache(array('uid' => $userInfo['uid']));
            } else {

                //未登录过
                $login_salt = rand(11111, 99999);
                $password   = '123456';
                // 需求添加 - 若后台没有填写邮件配置，将直接过滤掉激活操作
                $isActive = $this->_config['need_active'] ? 0 : 1;
                if ($isActive == 0) {
                    $emailConf = model('Xdata')->get('admin_Config:email');
                    if (empty($emailConf['email_host']) || empty($emailConf['email_account']) || empty($emailConf['email_password'])) {
                        $isActive = 1;
                    }
                }

                $user = [
                    'sync_uid'    => $data['userId'],
                    'realName'    => $data['realName'],
                    'schoolName'  => $data['schoolName'],
                    'gradeName'   => $data['gradeName'],
                    'className'   => $data['className'],
                    'subjectName' => $data['subjectName'],
                    'title'       => $data['title'],
                    'education'   => $data['education'],
                    'cityName'    => $data['cityName'],
                    'v'           => $data['v'],
                    'sex'         => $sex,
                    'password'    => md5(md5($password) . $login_salt),
                    'reg_ip'      => get_client_ip(),
                    'ctime'       => time(),
                    'update_time' => time(),
                    'is_audit'    => $this->_config['register_audit'] ? 0 : 1,
                    'is_active'   => $isActive,
                    'is_init'     => 1,
                    'login_salt'  => $login_salt,

                ];
                $uid = $this->_user_model->add($user);
                if ($uid) {
                    // 添加积分
                    model('Credit')->setUserCredit($uid, 'init_default');
                    // 添加至默认的用户组
                    $userGroup = model('Xdata')->get('admin_Config:register');
                    $userGroup = empty($userGroup['default_user_group']) ? C('DEFAULT_GROUP_ID') : $userGroup['default_user_group'];
                    model('UserGroupLink')->domoveUsergroup($uid, implode(',', $userGroup));
                    $data['oauth_token']        = getOAuthToken($uid); //添加app认证
                    $data['oauth_token_secret'] = getOAuthTokenSecret();
                    $data['uid']                = $uid;
                    $savedata                   = $data;
                    D('')->table(C('DB_PREFIX') . 'ZyLoginsync')->add($savedata);
                    $this->passport->loginLocal($user['sync_uid'], $user['password'], false, false, true);
                    M('user')->where(array('uid' => $_SESSION['mid']))->setField('login_num', '1');
                }
            }
            if ($_SESSION['goto_url']) {
                $url = $_SESSION['goto_url'];
                unset($_SESSION['goto_url']);
            } else {
                $url = U('classroom/Index/index');
            }
            redirect($url);
        } else {
            echo '验证失败';
            exit;
        }
    }

    /**
     * 人脸登录
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-11-23
     * @return   [type]                         [description]
     */
    public function faceLogin()
    {
        $youtuscene = model('Xdata')->get('admin_Config:youtuscene');
        if (!$youtuscene || ($youtuscene && !in_array('login', $youtuscene['scene']))) {
            $this->error('未开启扫脸脸登录功能');
        }
        $this->display('face_login');
    }

    /**
     * 人脸验证
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-11-25
     * @return   [type]                         [description]
     */
    public function faceVerify()
    {
        if (!$this->mid) {
            $this->error('请先登陆');
        }
        $this->display('face_verify');
    }

    /**
     * 登陆人脸验证
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2018-01-09
     * @return   [type]                         [description]
     */
    public function loginFaceVerify()
    {
        $url = isset($_GET['redirect_url']) ? urldecode($_GET['redirect_url']) : U('classroom/Index/index');
        // 检测是否配置开启
        $youtuscene = model('Xdata')->get('admin_Config:youtuscene');
        if (!$youtuscene || (isset($youtuscene['login_force_verify']) && $youtuscene['login_force_verify'] != 1)) {
            redirect($url);
            return;
        }

        if (session('face_check_face_verify')) {
            redirect($url);
            return;
        }

        // 检测是否已经绑定
        $status = model('Youtu')->getInitFaceStatus($this->mid);

        if ($status === 0) {
            $redirect_params = $_GET;

            unset($redirect_params['app'], $redirect_params['mod'], $redirect_params['act']);
            isset($redirect_params['redirect_url']) && $redirect_params['redirect_url'] = urlencode($redirect_params['redirect_url']);
            redirect(U('classroom/User/face', $redirect_params));
            return;
        }

        $this->display('face_verify');
    }
}
