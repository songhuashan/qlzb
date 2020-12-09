<?php
/**
 * 充值卡管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminRechargeAction extends AdministratorAction{

    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();
    }


/**
     * 初始化专题配置
     * 
     * @return void
     */
    private function _initTabSpecial() {
        // Tab选项
        $this->pageTab [] = array (
                'title' => '充值卡列表',
                'tabHash' => 'index',
                'url' => U ( 'school/AdminRecharge/index' )
        );
        if(!is_admin($this->mid)){
            $this->pageTab [] = array (
                'title' => '添加充值卡',
                'tabHash' => 'addTeacher',
                'url' => U ( 'school/AdminRecharge/addRecharge' )
            );
        }
    }

    public function index(){
        $this->_initTabSpecial();
        $this->assign('pageTitle','充值卡管理');
        //页面配置
        $id     =  intval($_POST['id']);
        $sid    =  intval($_POST['sid']);
        $code   =  intval($_POST['code']);
        $recharge_price   =  intval($_POST['recharge_price']);
        $this->pageKeyList = array('id','school_title','code','recharge_price','exp_date','status','end_time','ctime','is_del','DOACTION');
        $this->searchKey = array('id','sid','code','recharge_price');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title'=>'禁用','onclick'=>"admin.delCouponAll('delcoupon')");

        $school = model('School')->getAllSchol('','id,title');
        $this->opt['sid'] = $school;
        $map = array('type'=>4,'coupon_type'=>0);
        if(!empty($id))$map['id']=$id;
        if(!empty($sid))$map['sid']=$sid;
        if(!empty($code))$map['code']=$code;
        if(!empty($recharge_price))$map['recharge_price']=$recharge_price;
		$coupon = model('Coupon');
        if(!is_admin($this->mid)){
            $map['sid'] = $this -> school_id;
        }
        //数据列表
        $listData = $coupon->where($map)->order('ctime DESC,id DESC')->findPage();
        $time = time();
        foreach($listData['data'] as $key=>$val){
            if($val['is_del'] == 1) {
                $val['status'] == 3;
            }
            if($val['status'] != 3 && $val['end_time'] < $time){
                $val['status'] = 0;
                $coupon->setStatus($val['id'],0);
            }
			$val['school_title'] = model('School')->where(array('id'=>$val['sid']))->getField('title');
            $val['ctime']    = date("Y-m-d H:i:s", $val['ctime']);
			$val['end_time'] = date("Y-m-d H:i:s", $val['end_time']);

            if($val['status'] == 3){
                $val['status'] = "<span style='color: grey;'>已作废</span>";
            }else if($val['status'] == 1){
                $val['status'] = "<span style='color: green;'>未领取</span>";
            }else if($val['status'] == 2){
                $val['status'] = "<span style='color: blue;'>已领取</span>";
            }else if($val['status'] == 0){
                $val['status'] = "<span style='color: red;'>已过期</span>";
            }

            if($val['is_del'] == 1) {
               $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'启用\',\'充值卡\');">启用</a>';
            }else {
               $val['DOACTION'] ='<a href="javascript:admin.mzCouponCardEdit('.$val['id'].',\'closeCoupon\',\'禁用\',\'充值卡\');">禁用</a>';
            }

            if($val['is_del'] == 0){
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1){
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            if(!is_admin($this->mid)){
                $val['DOACTION'].=" | <a href=".U('school/AdminRecharge/addRecharge',array('id'=>$val['id'],'tabHash'=>'revise')).";>修改</a>";
            }
            $listData['data'][$key] = $val;
        }

        $this->displayList($listData);
    }
    /**
     * 添加充值卡
     * Enter description here ...
     */
    public function addRecharge(){
        $id   = intval($_GET['id']);

        $this->_initTabSpecial();
        $this->pageKeyList = array ('sid','recharge_price','exp_date','end_time','counts');
        $this->notEmpty = array ('sid','recharge_price','exp_date','end_time','counts');
		$schoolData = model ( 'School' )->findAll();
        if($id){
            $this->savePostUrl = U ( 'school/AdminRecharge/doAddRecharge','type=save&id='.$id);
            $recharge = model('Coupon')->where( 'id=' .$id )->find ();
			$recharge['end_time'] = date('Y-m-d H:i:s',$recharge['end_time']);
            $this->assign('pageTitle','修改充值卡');
            //说明是编辑
            $this->displayConfig($recharge);
        }else{
            $this->savePostUrl = U ('school/AdminRecharge/doAddRecharge','type=add');
            $this->assign('pageTitle','添加充值卡');
            //说明是添加
            $this->displayConfig();
        }
    }
    
    /**
     * 处理添加充值卡
     * Enter description here ...
     */
    public function doAddRecharge(){
        $id=intval($_GET['id']);
        $type= t($_GET['type']);
        $count = intval($_POST['counts']);
        if($count == 0){
        $count = 1;
        }
        //要添加的数据
         //数据验证

        if($_POST['recharge_price'] == ''){$this->error("充值面额不能为空");}
        if(!is_numeric($_POST['recharge_price'])){$this->error('充值面额必须为数字');}
        if($_POST['exp_date'] == ''){$this->error("有效期不能为空");}
        if(!is_numeric($_POST['exp_date'])){$this->error('有效期必须为数字');}
		if($_POST['end_time'] == ''){$this->error("终止时间不能为空");}
        if(!is_numeric($count)){$this->error('生成数量必须为数字');}
    
         $params = array(
            'type'=> 4,
            'sid'=> $this -> school_id,
            'recharge_price'=>t($_POST['recharge_price']),
            'exp_date'=>intval($_POST['exp_date']),
            'end_time'=>strtotime($_POST['end_time']),
        );
        if($type == 'add'){
            $num = 0;
            $rechargeModel = model('Coupon');
            for ($i = 0; $i < $count; $i++) {
                $params['code'] = $this->create_code();
                $params['ctime'] = time();
                $rechargeModel->add($params);
                $num++;
            }
            if(!$num)$this->error("对不起，添加失败！");
            $this->success("成功添加".$num."个充值卡！");
        }else if($type=='save' && $id){
            $res=model('Coupon')->where("id=$id")->save($params);
            if(!$res)$this->error("对不起，修改失败！");
            $this->success("修改充值卡成功!");
        }
    }
    /**
      * @name 生成code方法
      * 
      */
    private function create_code(){
        static $codeList;
        $code = date('ydis',time()).substr(time(),-3).mt_rand(10000,99999);
        //检测code是否已经存在
        if(in_array($code,$codeList)){
            return $this->create_code();
        }
        $codeList[] = $code;
        return $code;
    }
}