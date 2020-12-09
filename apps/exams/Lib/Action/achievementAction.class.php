<?php
/**
 * 考试证书管理
 * @author martinsun
 * @version CY1.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');

class AchievementAction extends AdministratorAction
{
    protected $mod = ''; //当前操作模型
    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->allSelected = false;
        $this->mod = D('ExamsCert', 'exams');
    }

    //证书列表
    public function index()
    {

        $listData   = $this->_getData(20, 1);

        $chengjilist=M('achievement')->where('isdel = 0')->select();

        $this->assign('list',$listData['data']);
        $this->assign('listData',$listData);
        $this->assign('chengjilist',$chengjilist);


        $this->display();
    }

    //证书列表
    public function editchengji()
    {

        $id=isset($_REQUEST['id'])?$_REQUEST['id']:'';
        $kaoqi=isset($_REQUEST['kaoqi'])?$_REQUEST['kaoqi']:'';
        $this->assign('kaoqi',$kaoqi);
        if($id)
        {
            $where['id']=$id;
            $where['isdel']=0;
            $info=M('achievement')->where($where)->find();
        }
        

        $this->assign('info',$info);
        $this->display();
    }

    public function kaoqi(){

        $this->display();
    } 

    public function daoru(){

        $this->display();
    }

    public function subchengjidr(){

        $attach_id = trim($_POST['file_ids_ids'], '|') ?: 0;
 
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
                $field = array('area','name','idcard','bkxm','level','class','kaiqi','yingjiao','shijiao','date','xueli','lunwen','zkz','kswz','chengji','tzcj','content');
                //循环获取excel中的值\

                $failtel="";
                $add_count   = 0;
                $total_count = 0;
                if (!empty($data)) {
                    foreach ($data as $key => $v) {
                        if ($key > 0 ) {
                            $wheretype['title']=$v[5];
                            $infotype=M('achievement_type')->where($wheretype)->find();
                            if(empty($infotype))
                            {
                                $data=array();
                                $data['title']=$v[5];
                                $data['isdel']=1;
                                $data['add']=time();
                                $data['update']=time();
                                $result=M('achievement_type')->add($data);
                                $kaoqi=$result;
                            }else{
                                $kaoqi=$infotype['id'];
                            }



                            $where['kaoqi']=$kaoqi;
                            $where['idcard']=$v[3];
                            $info=M('achievement')->where($where)->find();
                            if(!empty($info))
                            {
                                continue;
                            }


                            $insert=array();
     
                            $insert['name']=$v[0];
                            $insert['mobile']=$v[1];
                            $insert['idcard']=$v[2];
                            $insert['bkxm']=$v[3];
                            $insert['level']=$v[4];
                            $insert['kaoqi']=$kaoqi;
                            $insert['chengji']=$v[6];
                            $insert['tzcj']=$v[7];
                            $insert['status']=$v[8];
                            $insert['add']=time();
                            $insert['update']=time();
                            $add[]=$insert;
                           
                            if ($uid = M('achievement')->add($insert)) {
                                $add_count++;
                            }
                        }
                    }
                }
                $this->jumpUrl = U('exams/achievement/daoru');
                $total_count=count($add);
                if ($add_count > 0) {
                    $this->success('共计' . $total_count . '个成绩信息,本次成功导入' . $add_count . '个用户');
                } else {
                    $this->error('导入失败,或导入信息重复无需重复导入');
                }
            }
        }
        $this->error('请重新上传导入附件');
    }



    public function subchengji()
    {

         if( isset($_POST) ) {
            
            $id=isset($_POST['id'])?$_POST['id']:'';
            if(empty($_POST['kaoqi'])){$this->error("考期不存在，返回首页重试！");}
            if(empty($_POST['area'])){$this->error("请填写地区");}
            if(empty($_POST['name'])){$this->error("请填写考生姓名");}
            if(empty($_POST['idcard'])){$this->error("请填写身份证号");}
            if(empty($_POST['bkxm'])){$this->error("请填写报考项目");}
            if(empty($_POST['chengji'])){$this->error("请填写成绩");}
            if(empty($_POST['status'])){$this->error("请填写是否通过");}
            $where['idcard']=$_POST['idcard'];
            $where['kaoqi']=$_POST['kaoqi'];
            $ret=M('achievement')->where($where)->find();
            if(!empty($ret))
            {
                $this->error("该身份证在该考期成绩已经录入");
            }
            if(empty($_POST['achievement'])){$this->error("请填写考生成绩");}

            $insert=array();
            $insert['area']     =$_REQUEST['area'];
            $insert['name']     =$_REQUEST['name'];
            $insert['mobile']   =$_REQUEST['mobile'];
            $insert['idcard']   =$_REQUEST['idcard'];
            $insert['bkxm']     =$_REQUEST['bkxm'];
            $insert['level']    =$_REQUEST['level'];
            $insert['class']    =$_REQUEST['class'];
            $insert['kaoqi']    =$_REQUEST['kaoqi'];
            $insert['yingjiao'] =$_REQUEST['yingjiao'];
            $insert['shijiao']  =$_REQUEST['shijiao'];
            $insert['date']     =$_REQUEST['date'];
            $insert['xueli']    =$_REQUEST['xueli'];
            $insert['content']  =$_REQUEST['content'];
            $insert['lunwen']   =$_REQUEST['lunwen'];
            $insert['zkz']      =$_REQUEST['zkz'];
            $insert['kswz']     =$_REQUEST['kswz'];
            $insert['chengji']  =$_REQUEST['chengji'];
            $insert['tzcj']     =$_REQUEST['tzcj'];
            $insert['status']   =intval($_REQUEST['status']);
            $insert['update']   =time();
            if($id)
            {
                $result=M('achievement')->where('id = '.$id)->save($data);
            }else{
                $data['add']=time();

                $result=M('achievement')->add($data);
            }
            if($result){
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
            
        }

    }



    public function delchengji()
    {
        $id = intval($_REQUEST['id']);
        $type = intval($_REQUEST['type']);
        $this->assign('pid', $cid);
        $update['isdel']=0;
        $result=M('achievement')->where('id ='.$id)->save($update);
        if($result) {
            $res['status'] = 1;
            $res['data'] = '操作成功';
        } else {
            $res['status'] = 0;
            $res['data'] = '操作失败';
        }
        exit(json_encode($res));
    }

    public function deltype()
    {
        $id = intval($_REQUEST['id']);
        $type = intval($_REQUEST['type']);
        $this->assign('pid', $cid);
        $update['isdel']=0;
        $result=M('achievement_type')->where('id ='.$id)->save($update);
        if($result) {
            $res['status'] = 1;
            $res['data'] = '操作成功';
        } else {
            $res['status'] = 0;
            $res['data'] = '操作失败';
        }
        exit(json_encode($res));
    }

    /**
     * 获取证书相关数据
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    integer                        $limit  [description]
     * @param    [type]                         $is_del [description]
     * @return   [type]                                 [description]
     */
    private function _getData($limit = 20, $is_del)
    {

        $map['isdel'] = $is_del;

        $list          = M("achievement_type")->where($map)->order('id desc')->findPage($limit);
   
        foreach ($list['data'] as $key => $value) {
            $list['data'][$key]['add'] = $value['add'] ? date("Y-m-d H:i:s", $value['add']) : '';
            $list['data'][$key]['update'] = $value['update'] ? date("Y-m-d H:i:s", $value['update']) : '';
            $list['data'][$key]['DOACTION'] .= '<a href="javascript:admin.getlist(' . $value['id'] . ');">添加成绩</a> | <a href="javascript:;" onclick="admin.upTreeCategory('.$value['id'].');">编辑</a> | <a href="javascript:admin.deletetype(' . $value['id'] . ');">删除</a>';
        }

        return $list;
    }


}
