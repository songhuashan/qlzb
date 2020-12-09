<?php
/**
 * 考试证书管理
 * @author martinsun
 * @version CY1.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');

class AdminExamsCertAction extends AdministratorAction
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
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => '添加', 'onclick' => "javascript:window.location.href='" . U('exams/AdminExamsCert/addCert', array('tabHash' => 'addCert')) . "'");

        $this->pageKeyList   = array('cert_id', 'cert_name', 'exams_paper_title', 'cert_unit', 'cert_validity_time', 'create_time', 'update_time', 'DOACTION');
        $this->searchKey     = array('cert_id', 'cert_name');
        $this->searchPostUrl = U('exams/AdminExamsCert/index');
        $listData            = $this->_getData(20, 0);
        $this->displayList($listData);
    }
    /**
     * 证书列表后台管理菜单
     * @return void
     */
    private function _initExamListAdminMenu()
    {
        $this->pageTab[] = array('title' => '列表', 'tabHash' => 'index', 'url' => U('exams/AdminExamsCert/index'));
        $this->pageTab[] = array('title' => '用户证书列表', 'tabHash' => 'user', 'url' => U('exams/AdminExamsCert/user'));
    }
    /**
     * 考试后台的标题
     */
    private function _initExamListAdminTitle()
    {
        $this->pageTitle['index']   = '列表';
        $this->pageTitle['addCert'] = '添加';
    }
    /**
     * 添加证书
     * @return void
     */
    public function addCert()
    {
        $this->_initExamListAdminMenu();
        $this->pageTab[] = ['title' => '添加', 'tabHash' => 'addCert', 'url' => U('exams/AdminExamsCert/addCert', ['tabHash' => 'addCert'])];
        $this->_initExamListAdminTitle();
        $this->pageKeyList = array(
            'exams_paper_id', //试卷ID
            'cert_name', //证书名称
            'cert_unit', //证书颁发单位名称
            'cert_content', //证书内容
            'cert_validity_time', //证书有效期，单位：天
            'grade_list', //证书等级列表
        );
        $this->savePostUrl = U('exams/AdminExamsCert/doaddCert');
        $this->displayConfig();
    }
    /**
     * 编辑证书
     * @return void
     */
    public function editCert()
    {
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageTab[]             = array('title' => '编辑', 'tabHash' => 'editCert', 'url' => U('exams/AdminExamsCert/editCert', ['cert_id' => $_GET['cert_id']]));
        $this->pageTitle['editCert'] = '编辑';
        $this->pageKeyList           = array(
            'cert_id', //证书ID
            'exams_paper_id', //试卷ID
            'cert_name', //证书名称
            'cert_unit', //证书颁发单位名称
            'cert_content', //证书内容
            'cert_validity_time', //证书有效期，单位：天,
            'grade_list', //证书等级列表
        );
        $this->savePostUrl = U('exams/AdminExamsCert/doeditCert');
        $data              = $this->mod->getCertById($_GET['cert_id']);
        $this->displayConfig($data);
    }

    /**
     * 处理添加证书
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @return
     */
    public function doaddCert()
    {
        if (!empty($_POST)) {
            $exams_paper_id = intval($_POST['exams_paper_id']);
            if (!$exams_paper_id || M('exams_paper')->where(['exams_paper_id' => $exams_paper_id, 'is_del' => 0])->count() < 1) {
                $this->error('请填写有效的试卷');
            } elseif ($this->mod->where(['exams_paper_id' => $exams_paper_id])->count() > 0) {
                $this->error('该试卷下已经添加过证书,无法再添加');
            }

            $data = [
                'exams_paper_id'     => $exams_paper_id,
                'cert_unit'          => t($_POST['cert_unit']),
                'cert_validity_time' => intval($_POST['cert_validity_time']),
                'cert_name'          => t($_POST['cert_name']),
                'cert_content'       => $_POST['cert_content'],
                'grade_list'         => t($_POST['grade_list']),
            ];
            if ($this->mod->addCert($data)) {
                $this->success('添加成功');
            } else {
                $this->error('添加失败,请重试');
            }
        }
    }
    /**
     * 处理编辑证书
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @return
     */
    public function doeditCert()
    {
        if (!isset($_POST['cert_id'])) {
            $this->error('未查询到证书');
        }
        $data = [
            'cert_id'            => intval($_POST['cert_id']),
            'cert_unit'          => t($_POST['cert_unit']),
            'cert_validity_time' => intval($_POST['cert_validity_time']),
            'cert_name'          => t($_POST['cert_name']),
            'cert_content'       => $_POST['cert_content'],
            'grade_list'         => t($_POST['grade_list']),
            'update_time'        => time(),
        ];
        if ($this->mod->save($data)) {
            $this->success('编辑证书成功');
        } else {
            $this->error('编辑证书失败,请稍后重试');
        }
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
        $tp = C('DB_PREFIX');
        if (isset($_POST)) {
            if ($_POST['cert_id']) {
                $_POST['cert_id'] && $map['cert_id'] = intval($_POST['cert_id']);
            }
            if ($_POST['cert_name']) {
                $_POST['cert_name'] && $map['cert_name'] = array('like', '%' . t($_POST['cert_name']) . '%');
            }
        }
        $map['e.is_del'] = $is_del;
        $list            = M("exams_cert e")->join("`{$tp}exams_paper` c ON e.exams_paper_id = c.exams_paper_id")->where($map)->field(['e.*', 'c.exams_paper_title'])->order('e.update_time desc')->findPage($limit);

        foreach ($list['data'] as $key => $value) {
            $list['data'][$key]['create_time'] = $value['create_time'] ? date("Y-m-d H:i:s", $value['create_time']) : '';
            $list['data'][$key]['update_time'] = $value['update_time'] ? date("Y-m-d H:i:s", $value['update_time']) : '';
            $list['data'][$key]['DOACTION'] .= '<a href="' . U('exams/AdminExamsCert/editCert', array('cert_id' => $value['cert_id'], 'tabHash' => 'editCert')) . '">编辑</a> | <a href="javascript:exams.rmExamCert(' . $value['cert_id'] . ');">删除</a>';
        }
        return $list;
    }

    /**
     * 删除证书
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @return
     */
    public function rmCert()
    {
        if (isset($_POST['cert_id'])) {
            $res = $this->mod->rmCert($_POST['cert_id']);
            if ($res) {
                echo json_encode(['status' => 1, 'data' => ['info' => '删除成功']]);exit;
            } else {
                echo json_encode(['status' => 0, 'info' => '删除失败,请稍后重试']);exit;
            }
        }
    }

    /**
     * 用户所获证书列表
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @return   [type]                         [description]
     */
    public function user()
    {
        
        $this->_initExamListAdminMenu();
        $this->_initExamListAdminTitle();
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageKeyList   = array('uname','cert_code', 'grade', 'cert_start_time', 'cert_end_time', 'exams_paper_title', 'create_time');
        $this->searchKey     = array('uid', 'cert_code');
        $this->searchPostUrl = U('exams/AdminExamsCert/user');
        $map = [];
        if($_POST){
            $_POST['uid'] && $map['uid'] = intval($_POST['uid']);
            $_POST['cert_code'] && $map['cert_code'] = t($_POST['cert_code']);
        }
        $listData = M('exams_user_cert')->where($map)->findPage();
        if($listData['data']){
            foreach ($listData['data'] as &$v) {
                $v['uname'] = getUsername($v['uid']);
                $v['exams_paper_title'] = D('ExamsPaper','exams')->where('exams_paper_id='.$v['exams_paper_id'])->getField('exams_paper_title');
                $v['cert_start_time'] = date('Y-m-d',$v['cert_start_time']);
                $v['cert_end_time'] = date('Y-m-d',$v['cert_end_time']);
                $v['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            }
        }
        $this->displayList($listData);
    }
}
