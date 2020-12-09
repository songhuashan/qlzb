<?php
/**
 * 考试管理--试卷模型
 * @author MartinSun<syh@sunyonghong.com>
 * @version V2.0
 */
class ExamsPaperModel extends Model
{

    protected $tableName   = 'exams_paper';
    protected $order_field = [
        'exams_paper_id' => 'exams_paper_id desc',
        'default' => 'sort asc',
        'new'     => 'create_time desc',
        'hot'     => 'exams_count desc',
    ];
    /**
     * 获取试卷分页列表
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @param  array $map [description]
     * @param  integer $limit [description]
     * @return array [description]
     */
    public function getPaperPageList($map = [], $limit = 20, $order = 'exams_paper_id')
    {
        
        $map['is_del'] = 0;
        $order         = isset($this->order_field[$order]) ? $this->order_field[$order] : $order;
        $list          = $this->where($map)->order($order)->findPage($limit);
        
        if ($list['data']) {
            $list['data'] = $this->haddleData($list['data']);

        }
        return $list;
    }

    /**
     * 数据处理
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @param  array $data [description]
     * @return [type] [description]
     */
    protected function haddleData($data = array())
    {
        if ($data) {
            $subjectModel = D('ExamsSubject', 'exams');
            foreach ($data as $key => $v) {
                $data[$key]['paper_subject']          = $subjectModel->getSubjectNetWorkName($v['exams_subject_id']);
                $data[$key]['paper_subject_fullpath'] = $subjectModel->getFullPathById($v['exams_subject_id']);
                $data[$key]['exams_module_title']     = $this->getModuleTitleAttr($v['exams_module_id']);
                $data[$key]['level_title']            = $this->getLevelTitle($v['level']);
                // $paper_options = D('ExamsPaperOptions', 'exmas')->getPaperOptionsById($v['exams_paper_id']);
                $paper_options = M("exams_paper_options")->where("exams_paper_id=".$v['exams_paper_id'])->find();
                $data[$key]['questions_count'] = $paper_options['questions_count'] ?: 0;
                $data[$key]['score'] = $paper_options['score'] ?: 0;
                $data[$key]['exams_paper_id'] = intval($v['exams_paper_id']);
                $data[$key]['exams_subject_id'] = intval($v['exams_subject_id']);
                $data[$key]['exams_module_id'] = intval($v['exams_module_id']);
                $data[$key]['exams_limit'] = intval($v['exams_limit']);
                $data[$key]['exams_count'] = intval($v['exams_count']);
                $data[$key]['reply_time'] = intval($v['reply_time']);
                $data[$key]['exams_limit'] = intval($v['exams_limit']);
            }
        }
        return $data;
    }
    /**
     * 获取试卷的所属模块名称
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-09
     * @param  [type] $module_id [description]
     * @return [type] [description]
     */
    protected function getModuleTitleAttr($module_id)
    {
        if ($module_id == 0) {
            return '知识点练习';
        }
        return M('exams_module')->where('exams_module_id=' . $module_id)->getField('title');
    }

    /**
     * 获取试卷难度名称
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-12
     * @param  [type] $level [description]
     * @return [type] [description]
     */
    public function getLevelTitle($level)
    {
        $title = [1 => '简单', 2 => '普通', 3 => '困难'];
        return $title[$level];
    }

    /**
     * 获取单个试卷
     * @Author MartinSun<syh@sunyonghong.com>
     * @Date   2017-10-13
     * @param  integer $paper_id [description]
     * @return [type] [description]
     */
    public function getPaperById($paper_id = 0)
    {
        $paper = $this->where('exams_paper_id=' . $paper_id)->find();
        if ($paper) {
            $paper = $this->haddleData([$paper])[0];
        }
        return $paper;
    }

    /**
     * 是否随机出卷
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-11-25
     * @return   boolean                        [description]
     */
    public function isRand($paper_id = 0)
    {
        if($paper_id){
            // 检验是不是后台访问或者查看试题结果页面
            if(stripos(MODULE_NAME,'admin') !== false || ACTION_NAME == 'examsresult'){
                return false;
            }
            return ($this->where('exams_paper_id='.$paper_id)->getField('is_rand') == '1') ? true : false;
        }
        return false;
    }
}
