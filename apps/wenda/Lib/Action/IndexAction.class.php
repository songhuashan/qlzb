<?php
/**
 * 问答版块首页控制器
 * User: Ashang
 * Date: 14-10-11
 * Time: 下午2:46
 */

class IndexAction extends Action{

    protected $wenda=null;
    protected $wenda_comment=null;
    protected $wenda_cate = null;
    /**
     * 初始化
     */
    public function _initialize(){
        $this->wenda = D('ZyWenda');//问答模型
        $this->wenda_comment=D('ZyWendaComment');//问答评论模型
        $this->wenda_cate = M('zy_wenda_category')->order('sort asc')->findAll();

        //查询标签集合
        $tags=M('tag')->select();

        $this->assign("taglist",$tags);//渲染标签
        //加载推荐问答列表
        $recommend=$this->wenda->getRecommendList();
        //加载牛人排行榜
        $nblist=$this->wenda_comment->query("SELECT uid,COUNT(id) as count FROM ".C('DB_PREFIX')."zy_wenda_comment WHERE is_del=0
GROUP BY uid   ORDER BY count DESC LIMIT 6");
        //查询一周内热门问答
        $senhotwd=$this->wenda->query("select * from ".C('DB_PREFIX')."zy_wenda  where DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= ctime and is_del=0 ORDER BY wd_comment_count DESC limit 5");
        //获取问答类型
        $wdtype=intval($_GET['wdtype']);
        $wendatagid = M('app_tag')-> where(array('table'=>'zy_wenda','app'=>'classroom')) ->field('tag_id')->select();
        $Taghotname = array();
       foreach($wendatagid as $key =>$val)
       {
      $Taghotname[$key]['hottagname'] =M('tag') ->where(array('tag_id'=>$val['tag_id'])) ->getField('name') ;
       }

        //猜你喜欢
        $guess_you_like = D('ZyGuessYouLike','classroom')->getGYLData(0,$this->mid,7);

        foreach ($guess_you_like as $key=> $val){
            $mhmName = model('School')->getSchoolInfoById($val['mhm_id']);
            $datas[$key]['mhmName'] = $mhmName['title'];
            //教师头像和简介
            $teacher = M('zy_teacher')->where(array('id'=>$val['teacher_id']))->find();
            $guess_you_like[$key]['teacherInfo']['name'] = $teacher['name'];
            $guess_you_like[$key]['teacherInfo']['inro'] = $teacher['inro'];
            $guess_you_like[$key]['teacherInfo']['head_id'] = $teacher['head_id'];
            //直播课时
            if($val['type'] == 2){
                $live_data = $this->live_data($val['live_type'],$val['id']);
                $guess_you_like[$key]['live']['count'] = $live_data['count'];
                $guess_you_like[$key]['live']['now'] = $live_data['now'];
            }
        }
        $this->assign("wdtype",$wdtype);
        $this->assign("hotwd",$senhotwd);
        $this->assign("nblist",$nblist);
        $this->assign("data",$recommend);
        $this->assign("wenda_cate",$this->wenda_cate);
        $this->assign("guess_you_like",$guess_you_like);
        $this->assign("WendaHottag",$Taghotname);
    }


    //直播数据处理
    protected function live_data($live_type,$id)
    {
        $count = 0;
        //第三方直播类型
        if($live_type == 3){
            $live_data = M('zy_live_gh')->where(array('live_id'=>$id,'is_del'=>0))->order('endTime asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['endTime'] < time()){
                        $count = $count + 1 ;
                    }
                    $startDate   = $value['beginTime'];
                    $invalidDate = $value['endTime'];
                }
            }else{
                $live_data = array(1);
                $count = 1;
            }
        }elseif ($live_type == 1){
            $live_data = M('zy_live_zshd')->where(array('live_id'=>$id,'is_del'=>0))->order('invalidDate asc')->select();
            if($live_data){
                foreach ($live_data as $item=>$value){
                    if($value['invalidDate'] < time()){
                        $count = $count + 1 ;
                    }
                    $startDate   = $value['startDate'];
                    $invalidDate = $value['invalidDate'];
                }
            }else {
                $live_data = array(1);
                $count = 1;
            }
        }
        $live_data['count'] = count($live_data);
        $live_data['now'] = $count;
        $live_data['startDate'] = $startDate;
        $live_data['invalidDate'] = $invalidDate;

        return $live_data;
    }


    /**
     * 问答首页方法
     */
    public function index(){
        //查询条件
        $wdtype = intval($_GET['wdtype']);
        $tpid=intval($_GET['tpid']);//获取问答分类
        $type=intval($_GET['type']);//获取类型，1热门 2等待回复
        if(!empty($tpid)||$tpid<=3 and $tpid>0){
            $where['type']=$tpid;
        }
        if( $wdtype ) {
            $where['type']=$wdtype;
        }
        $where['is_del']    = 0;
        if($type==1) {
            $order = "wd_comment_count DESC , wd_browse_count DESC";
        }

        if($type ==2) {
            $where['wd_comment_count'] = 0;
        }

        if(!$order)  $order="ctime DESC , id";

        $wenda_cate_neme = M('zy_wenda_category')->where('zy_wenda_category_id='.$wdtype)->getField('title');
        $wendaList = $this->wenda->where($where)->order($order)->findPage(20);
        //循环格式数据
        foreach($wendaList['data'] as &$val){
            $val['ctime'] = date('Y-m-d H:i',$val['ctime']);//格式化时间数据
            $val['tags']=$this->wenda->getWendaTags($val['tag_id']);//取出问答的标签
            $val['wd_description'] = t($val['wd_description']);
            $val['wd_comment']=$this->wenda_comment->getNowWenda($val['id'],1);//取最新的一条评论
            $val['wd_attr'] = array_filter(explode('|',$val['wd_attr']));
        }

        $this->assign("wenda_cate_neme",$wenda_cate_neme);
        $this->assign("wendaList",$wendaList);
        $this->display();
    }

    public function getWendaList(){
        //查询条件
        $wdtype = intval($_GET['wdtype']);
        $tpid=intval($_GET['tpid']);//获取问答分类
        $type=intval($_GET['type']);//获取类型，1热门 2等待回复
        if(!empty($tpid)||$tpid<=3 and $tpid>0){
            $where['type']=$tpid;
        }
        if( $wdtype ) {
            $where['type']=$wdtype;
        }
        $where['is_del']    = 0;
        if($type==1){
            $order="wd_comment_count DESC , wd_browse_count DESC";
        }else{
            $where['wd_comment_count'] = 0;
        }
        if(!$order)$order="ctime DESC , id";

        $wendaList = $this->wenda->where($where)->order($order)->findPage(20);
        //循环格式数据
        foreach($wendaList['data'] as &$val){
            $val['ctime'] = date('Y-m-d H:i',$val['ctime']);//格式化时间数据
            $val['tags']=$this->wenda->getWendaTags($val['tag_id']);//取出问答的标签
            $val['wd_description'] = t($val['wd_description']);
            $val['wd_comment']=$this->wenda_comment->getNowWenda($val['id'],1);//取最新的一条评论
        }
        $this->assign("wendaList",$wendaList);
        $html = $this->fetch('ajax_wenda');
        $wendaList['data']=$html;
        exit( json_encode($wendaList) );
    }
    /**
     * 问题详细页面
     */
    public function detail(){
        $wenda_id=intval($_GET['id']);
        $commentid=intval($_GET['commentid']);//接收从评论消息过来的评论id
        if($wenda_id==0){
            $this->error("对不起，您查询的问答不存在！T_T");
        }
        //查询条件
        $map=array(
            'id'=>$wenda_id,
            'is_del'=>0
        );
        //查询问答详细信息
        $wenda_info=$this->wenda->where($map)->find();

        //设置seo详情
        $this->seo = model('Seo')->installSeo(['_title'=>$wenda_info['wd_description'],'_keywords'=>$wenda_info['intro']],$this->seo);

        if(!$wenda_info){
            $this->error("对不起，您查询的问答不存在或已被删除！T_T");
        }
        //问答浏览量+1
        $this->wenda->addBrowseCount($wenda_id);
        //查询问答下面所说评论
        $where=array(
            'is_del'=>0,
            'wid'=>$wenda_id,
            'parent_id'=>0
        );
        //首先取出这条评论 且它为一级评论
        $commentok=$this->wenda_comment->where(array('id'=>$commentid,'parent_id'=>0))->find();
        //当从系统消息跳转过来的时候
        if(empty($commentid) || empty($commentok)){
            $comment_data=$this->wenda_comment->where($where)->order("ctime asc")->findPage(20);
        }else{
            //优先取出这条评论放在评论数组第一位
            $where['id']=array('neq',$commentid);
            $comment_data=$this->wenda_comment->where($where)->order("ctime asc")->findPage(20);
            //追加数组
            if(empty($comment_data['data'])){
                unset($where['id']);
                $comment_data=$this->wenda_comment->where($where)->order("ctime asc")->findPage(20);

            }else{
                array_push($comment_data['data'],$commentok);
            }

            $this->assign("msgcomid",$commentid);
            $this->assign("is_mescom",true);
        }

        //循环格式评论数据
        foreach($comment_data['data'] as &$val){
            $res=D("ZyWendaPraise")->where(array('comment_id'=>$val['id'],'uid'=>$this->mid))->find();
            //是否有子级评论
            $isson=$this->wenda_comment->where(array('parent_id'=>$val['id']))->count();
            if(!$res){
                $val['is_praise']=false;
            }else{
                $val['is_praise']=true;
            }
            if($isson){
                $val['isson']=true;
            }else{
                $val['isson']=false;
            }
        }

        //查询问答标签属性
        $taglist=$this->wenda->getWendaTags($wenda_info['tag_id']);
        //查询我是否关注提问者 1已关注 0未关注 2自身
        if($this->mid!=$wenda_info['uid']){
            $follow=M('user_follow')->where(array('uid'=>$this->mid,'fid'=>$wenda_info['uid']))->find();
            if($follow){
                $is_follow=1;
            }else{
                $is_follow=0;
            }
        }else{
            $is_follow=2;
        }
        $wenda_info['wdtags']=$this->wenda->getWendaTags($wenda_info['tag_id']);
        $this->assign("wdinfo",$wenda_info);//渲染问答详细
        $this->assign("cmlist",$comment_data);//渲染评论列表
        $this->assign("tags",$taglist);//渲染标签集合
        $this->assign("is_follow",$is_follow);//是否关注提问用户
        //获取推荐的课程
        $time = time();
        $whererec = "is_del=0 AND is_mount = 1 AND is_activity=1 AND uctime>$time AND listingtime<$time";
        $recClass=M("zy_video")->field('id,teacher_id,video_title,video_intro,cover,type')->order('video_order_count desc,video_score desc')->where($whererec)->limit(3)->select();
        //获取相关问答
        $recMap['type'] = $wenda_info['type'];
        $recMap['id'] = array('neq',$wenda_id);
        $recMap['is_del'] = 0;
        $recWenda = $this->wenda->where($recMap)->field('id,wd_description')->limit(5)->select();
         $wendamap['is_del'] = 0;
        $topwenda = M('zy_wenda')-> where($wendamap)->order('ctime desc,wd_help_count desc') -> field('id,wd_description')->limit(5)->select();


		$commentSwitch = model('Xdata')->get('admin_Config:commentSwitch');
		$switch = $commentSwitch['wenda_switch'];

		$this->assign('switch',$switch);
		$this->assign('topwenda',$topwenda);
        $this->assign('recClass',$recClass);
        $this->assign('recWenda',$recWenda);
        $this->display();

    }

    //删除问答
    public function delWenda(){
        $id=intval($_POST['id']);
        $data['is_del']=1;
        $where=array(
            'id'=>$id,
            'uid'=>$this->mid
        );
        $res=$this->wenda->where($where)->save($data);

        if($res){
            $credit = M('credit_setting')->where(array('id'=>46,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] < 0){
                $dtype = 7;
                $note = '删除问题扣除的积分';
            }
            model('Credit')->addUserCreditRule($this->mid,$dtype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

            echo 200;
            exit;
        }else{
            echo 500;
            exit;
        }
    }


    //删除问答
    public function delWendacomment(){
        $id=intval($_POST['id']);
        $data['is_del']=1;
        $where=array(
            'id'=>$id,
            'uid'=>$this->mid
        );
        $res=M('zy_wenda_comment')->where($where)->save($data);

        if($res){
            echo 200;
            exit;
        }else{
            echo 500;
            exit;
        }
    }



    public function verify()
    {
        $Verify = new Verify();
        $Verify->entry();
    }
    /**
     * 添加问答方法
     */
    public function doAddWenda()
    {

//        $Verify = new Verify();
//
//        if(!$Verify->check($_POST['verify']))
//         {
//             echo "验证码错误";
//             return;
//         }
        $type=intval($_POST['typeid']);
        $title=strip_tags($_POST['title'], '<a><br><
        span><b><i><strong><img>');
        $content=filter_keyword($_POST['content']);
        $tags=t($_POST['tags']);
        $tag=$_POST['tag'];
//        if(empty($type) ||$type>4){
//            echo "对不起，发布类型错误！";
//            exit;
//        }
        if(empty($type)){
            echo "对不起，发布类型错误！";
            exit;
        }
        if(strlen($content)<3 ){
            echo "对不起，内容至少为3个字符";
            exit;
        }
        $data = array(
            'type'=>$type,
            'uid'=>$this->mid,
            //'wd_title'=>$title,
            'wd_description'=>$content,
            //'tag_id'=>$tags,
            'ctime'=>time(),
            'tag' => $tag,
            'wd_attr' => t($_POST['wd_attr']),
        );
        $res = $this->wenda->add($data);
        if($res){
            $credit = M('credit_setting')->where(array('id'=>34,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] > 0){
                $wtype = 6;
                $note = '发布问题获得的积分';
            }
            model('Credit')->addUserCreditRule($this->mid,$wtype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

            echo $res;
            exit;
        }else{
            echo "发布失败，请重试！";
            exit;
        }
    }

    /**
     * 修改问答页面
     */
    public function editWenda(){
        $wid=intval($_GET['wid']);//获取问答id
        $map=array(
            'id'=>$wid,
            'uid'=>$this->mid,
            'is_del'=>0

        );
        $res=$this->wenda->where($map)->find();

        if(!$res){
            $this->error("对不起,你请求的问答不存在或已被删除");
        }
        $res['wdtags']=$this->wenda->getWendaTags($res['tag_id']);
        $this->assign("wenda_cate",$this->wenda_cate);
        $this->assign("data",$res);
        $this->display();
    }

    /**
     * 修改问答方法
     */
    public function doEditWenda(){
        $type=intval($_POST['typeid']);
        $title=strip_tags($_POST['title'], '<a><br><span><b><i><strong><img>');
        $content=strip_tags($_POST['content'], '<a><br><span><b><i><strong><img>');
        $tags=t($_POST['tags']);
        $wid=intval($_POST['wid']);

        if( empty($type) ){
            echo "对不起，发布类型错误！";
            exit;
        }

        if(strlen($content)<3 ){
            echo "对不起，内容至少为3个字符";
            exit;
        }
        $data=array(
            'type'=>$type,
            //'wd_title'=>$title,
            'wd_description'=>$content,
            //'tag_id'=>$tags,
            'ctime'=>time()
        );
        $res=$this->wenda->where("id=".$wid)->save($data);

        if($res!==false){
            echo $res;
            exit;
        }else{
            echo "修改失败，请重试！";
            exit;
        }
    }


    public function addWenda(){
        $this->display();
    }

    /**
     * 加载课程问答
     */
    public function question(){
        $type=intval($_GET['type']);
        //查询条件
        $map=array(
            'is_del'=>0,//是否删除
            'recommend'=>0,//是否推荐
        );
        if($type==0){
            $wendaList=D('ZyQuestion')->findPageBySql("SELECT `id`,`uid`,`parent_id`,`oid`,`qst_title` AS `wd_title`, `qst_description` AS `wd_description` ,`qst_help_count` as `wd_help_count` ,`qst_comment_count` as `wd_comment_count`,`ctime` FROM ".C('DB_PREFIX')."zy_question where `parent_id`=0 and `type`=1  ORDER BY `ctime` DESC");
        }else if($type==1){
            $wendaList=D('ZyQuestion')->findPageBySql("SELECT `id`,`uid`,`parent_id`,`oid`,`qst_title` AS `wd_title`, `qst_description` AS `wd_description` ,`qst_help_count` as `wd_help_count` ,`qst_comment_count` as `wd_comment_count`,`ctime` FROM ".C('DB_PREFIX')."zy_question where `parent_id`=0 and `type`=1 ORDER BY `qst_comment_count` DESC");
        }else if($type==2){
            $wendaList=D('ZyQuestion')->findPageBySql("SELECT `id`,`uid`,`parent_id`,`oid`,`qst_title` AS `wd_title`, `qst_description` AS `wd_description` ,`qst_help_count` as `wd_help_count` ,`qst_comment_count` as `wd_comment_count`,`ctime` FROM ".C('DB_PREFIX')."zy_question where `parent_id`=0 and `qst_comment_count`=0 and `type`=1 ORDER BY `ctime` DESC");
        }

        //循环格式数据
        foreach($wendaList['data'] as &$val){
            $val['ctime']=getDateDiffer($val['ctime']);//格式化时间数据
        }

        $this->assign("wendaList",$wendaList);
        $this->display("index");
    }



    /**
     * 取热门
     */
    public function classifywd(){
        $tpid=intval($_GET['tpid']);//获取问答分类
        $type=intval($_GET['type']);//获取类型，1热门 2等待回复
        if(!empty($tpid)||$tpid<=3 and $tpid>0){
            $where['type']=$tpid;
        }
        $where['is_del']=0;
        $where['recommend']=0;
        if($type==1){
            $order="wd_comment_count DESC , wd_browse_count DESC";
        }else{
            $where['wd_comment_count']=0;
        }
        $wdlist=$this->wenda->where($where)->order($order)->findPage(20);
        //循环取时间差
        foreach($wdlist['data'] as &$val){
            $val['ctime']=getDateDiffer($val['ctime']);
            $val['tags']=$this->wenda->getWendaTags($val['tag_id']);
            $val['wd_comment']=$this->wenda_comment->getNowWenda($val['id'],1);//取最新的一条评论
        }

        $this->assign("wendaList",$wdlist);
        $this->display("index");
    }

    /**
     * 加载评论下的子评论
     */
    public function getSonComment(){
        $limit=6;
        $id=intval($_REQUEST['id']);
        $map=array(
            'parent_id'=>$id,
            'is_del'=>0
        );
        $data=$this->wenda_comment->where($map)->order("ctime DESC")->findPage($limit);
        //循环取时间差
        foreach($data['data'] as &$val){
            $val['ctime']=getDateDiffer($val['ctime']);
        }
        $this->assign("data",$data['data']);
        $this->assign("pid",$id);
        $data['data']=$this->fetch("comm_list");
        echo json_encode($data);exit;
    }
    /**
     * 添加子回复
     */
    public function doSonComment(){
        $id=intval($_POST['id']);//获取父级评论id
        $count=t($_POST['txt']);//获取回复内容
        $wid=intval($_POST['wid']);//获取问答id
        if(strlen($count)<3){
            echo "对不起，回复内容最少为3个字符";
            exit;
        }
        $map=array(
            'parent_id'=>$id,
            'wid'=>$wid,
            'description'=>filter_keyword($count),
            'ctime'=>time(),
            'uid'=>$this->mid
        );
        $res=$this->wenda_comment->add($map);
        if($res){
            //设置问答评论数量+1
            $this->wenda->addCommentCount($wid);
            //设置子评论数量+1
            $this->wenda_comment->addCommentCount($id);
            //查询应用的作者
            $wuid=$this->wenda->where(array('id'=>$wid))->getField('uid');
            //查询评论内容
            $cominfo=$this->wenda_comment->where(array('id'=>$id))->find();

            //添加消息记录
            model('Message')->doCommentmsg($this->mid,$cominfo['uid'],$wid,$wuid,'wenda',$res,$id,limitNumber($cominfo['description'],500),$count);

            echo 200;
            exit;
        }else{
            echo "对不起，回复失败，请重试！";
            exit;
        }

    }

    /**
     * 设置赞+1
     */
    public function doWendaCommentZan(){
        $id=intval($_POST['id']);
        $map=array(
            'uid'=>$this->mid,
            'comment_id'=>$id
        );
        $res=M('zyWendaPraise')->where($map)->find();
        if($res){
            echo "500";
            exit;
        }else{
            M('zyWendaPraise')->add($map);
            $this->wenda_comment->addCommentZan($id);
            $this->wenda->where(array('id'=>$res))->setInc('wd_help_count');
            $fid =$this->wenda_comment->where('id='.$id)->getField('uid');
            $credit = M('credit_setting')->where(array('id'=>36,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] > 0){
                $ztype = 6;
                $note = '问题评论被点赞获得的积分';
            }
            model('Credit')->addUserCreditRule($fid,$ztype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

            echo 200;
            exit;
        }
    }

    public function doWendaComment(){
        $wid=intval($_POST['wid']);//获取问答id
        $cont=$_POST['count'];//获取评论内容
        if(empty($wid)||empty($cont)){
            echo "评论失败，请重试！";
            exit;
        }
        $data=array(
            'uid'=>$this->mid,
            'wid'=>$wid,
            'description'=>filter_keyword($cont),
            'ctime'=>time()
        );
        $res=$this->wenda_comment->add($data);
        if($res){//评论成功
            //设置问答评论数量+1
            $this->wenda->addCommentCount($wid);
            //查询应用的作者
            $wdinfo=$this->wenda->where(array('id'=>$wid))->find();
            //添加消息记录
            model('Message')->doCommentmsg($this->mid,$wdinfo['uid'],$wid,$wdinfo['uid'],'wenda',$res,0,limitNumber($wdinfo['wd_description'],500),$cont);
            $credit = M('credit_setting')->where(array('id'=>35,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] > 0){
                $rtype = 6;
                $note = '问题评论获得的积分';
            }
            model('Credit')->addUserCreditRule($this->mid,$rtype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

            echo 200;
            exit;
        }else{
            echo "评论失败，请重试！";
            exit;
        }
    }


    /**
     * 第三级回复
     */
    public function doSonComms(){
        $id=intval($_POST['id']);//获取父级评论id
        $count=t($_POST['txt']);//获取回复内容
        $wid=intval($_POST['wid']);//获取问答id
        $fid=intval($_POST['uid']);//获取被回复人uid
        $sid=intval($_POST['sid']);
        if(strlen($count)<3){
            echo "对不起，回复内容最少为3个字符";
            exit;
        }
        if(strlen($count)>140){
            echo "对不起，内容最多70个字符！";
            exit;
        }
        $map=array(
            'parent_id'=>$id,
            'wid'=>$wid,
            'description'=>$count,
            'ctime'=>time(),
            'uid'=>$this->mid,
            'fid'=>$fid
        );
        $res=$this->wenda_comment->add($map);
        if($res){
            //设置问答评论数量+1
            $this->wenda->addCommentCount($wid);
            //设置子评论数量+1
            $this->wenda_comment->addCommentCount($id);
            //查询应用的作者
            $wuid=$this->wenda->where(array('id'=>$wid))->getField('uid');
            //查询评论内容
            $cominfo=$this->wenda_comment->where(array('id'=>$sid))->find();
            //添加消息记录

            model('Message')->doCommentmsg($this->mid,$fid,$wid,$wuid,'wenda',$res,$id,limitNumber($cominfo['description'],500),$count);

            echo 200;
            exit;
        }else{
            echo "对不起，回复失败，请重试！";
            exit;
        }

    }
    /**
     * 修改三级评论
     */
    public function updateSonComment(){
        $id=$_POST["id"];
        $data["description"]=t($_POST["txt"]);
        $data["ctime"]=time();
        $res=$this->wenda_comment->where('id='.$id)->save($data);
        if($res){
            echo 200;
            exit;
        }else{
            echo "对不起，编辑失败，请重试！";
            exit;
        }
    }
    /**
     * 删除三级评论
     */
    function delCommComment(){
        $id=$_POST["id"];
        $pid=$_POST["pid"];
        $data["is_del"]=1;
        $data["ctime"]=time();
        $res=$this->wenda_comment->where("id=".$id)->save($data);
        if($res){
            //设置问答评论数量-1
            $this->wenda_comment->reductionCommentCount($pid);
            $credit = M('credit_setting')->where(array('id'=>47,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] < 0){
                $dtype = 7;
                $note = '删除回复内容扣除的积分';
            }
            model('Credit')->addUserCreditRule($this->mid,$dtype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

            echo 200;
            exit;
        }else{
            echo "对不起，删除失败，请重试！";
            exit;
        }
    }
    /**
     * 删除二级评论
     */
    function delcomm(){
        $id=$_POST["id"];
        $wid=$_POST["wid"];
        $data["is_del"]=1;
        $data["ctime"]=time();
        $res=$this->wenda_comment->where("id=".$id)->save($data);
        if($res){
            //设置问答评论数量-1
            $this->wenda->reductionCommentCount($wid);
            $credit = M('credit_setting')->where(array('id'=>47,'is_open'=>1))->field('id,name,score,count')->find();
            if($credit['score'] < 0){
                $dtype = 7;
                $note = '删除回复内容扣除的积分';
            }
            model('Credit')->addUserCreditRule($this->mid,$dtype,$credit['id'],$credit['name'],$credit['score'],$credit['count'],$note);

            echo 200;
            exit;
        }else{
            echo "对不起，删除失败，请重试！";
            exit;
        }
    }

    //获取验证码
    public function getcode(){
        //取一个session的值
        session('mzcodeforccvideo',genRandomString(6));
        //后台验证码
        $cc_code = getAppConfig('cc_code', 'other','ddasgagefeagegfeafefe');
        //课程ID
        $mzcur_lesson_id  = t($_POST['id']);
        //课程ccID
        $vid              = t($_POST['vid']);

        $data = array(
            'enable'   => 0,
            'vid'      => $vid,
            'uid'      => intval($this->mid),
            'sess'     => session('mzcodeforccvideo'),
            'sesionid' => session_id(),
            'lid'      => $mzcur_lesson_id,
        );

        //序列化数据
        $info = serialize($data);

        $str = $info.$cc_code;

        echo base64_encode($str);exit;
    }

}
?>