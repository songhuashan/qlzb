<?php
/**
 * 云播前台公共控制器
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
class CommonAction extends Action
{
	public $school_id = 0;
	/**
    * 初始化
    * @return void
    */
    public function _initialize() {
    
		$this->twcont=D("ZyQuestion")->where(array('uid'=>$this->mid))->count();//加载提问数量
        $this->videocont=D("ZyOrder")->where(array('uid'=>$this->mid,'is_del'=>0))->count();//加载我的课程总数
        $this->commcont=M("ZyWendaComment")->where(array('uid'=>$this->mid,'is_del'=>0))->count();//加载我的评论
        $this->wdcont=M('ZyWenda')->where(array('uid'=>$this->mid,'is_del'=>0))->count();//加载我的问答数量
        $this->note=M('ZyNote')->where(array('uid'=>$this->mid))->count();
        if(is_school($this->mid)){
            //储存机构ID
            !$_SESSION['school_id'] && $_SESSION['school_id'] = model('School')->where(['uid'=>$this->mid])->getField('id');
            $this->school_id = $_SESSION['school_id'];
        }
    }
	/**
      * 错误提示
      * @return void
    */
	public function mzError($msg,$url='',$data=array()){
		$this->mzajaxReturn($msg,0,$url,$data);
	}
	/**
      * 成功提示
      * @return void
    */
	public function mzSuccess($msg,$url='',$data=array()){
		$this->mzajaxReturn($msg,1,$url,$data);
	}
  /**
    * ajax返回
    * @return void
    */
	private function mzajaxReturn($msg,$status,$url='',$data=array()){
		echo json_encode(array('status'=>(string)$status,'info'=>$msg,'data'=>$data,'referer'=>$url));exit;
	}

	public function _empty(){
		$this->assign('isAdmin', 1);
		$this->assign('jumpUrl', SITE_URL);
		$this->error('你访问的页面不存在！');
	}
    
    /**
     * 获取阿里支付配置
     */
    protected function getAlipayConfig(){
        $config = array(
            'cacert' => join(DIRECTORY_SEPARATOR, array(SITE_PATH, 'api','pay','alipay_v2','cacert.pem')),
            'input_charset'    => strtolower('utf-8'),
            'sign_type' =>  strtoupper('RSA'),
        );
        $conf = unserialize(M('system_data')->where("`list`='admin_Config' AND `key`='alipay'")->getField('value'));
        if(is_array($conf)){
            $config = array_merge($config, array(
                'partner'   =>$conf['alipay_partner'],
                'key'       =>$conf['alipay_key'],
                'seller_email'=> $conf['seller_email'],
                'private_key_path' => $conf['private_key'],
                'ali_public_key_path'    => $conf['public_key']
            ));
        }
        return $config;
    }
	
}