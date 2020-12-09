<?php
/**
 * @name 证书管理模型
 * @author martinsun
 * @version 1.0
 */
class ExamsCertModel extends Model
{
    protected $tableName = 'exams_cert';

    /**
     * 添加证书
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    array                          $data [description]
     */
    public function addCert(array $data)
    {
        if (empty($data)) {
            return false;
        }
        $data['create_time'] = $data['update_time'] = time();
        return $this->add($data);
    }

    /**
     * 根据证书ID获取证书内容
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    integer                        $cert_id [description]
     * @return   [type]                                  [description]
     */
    public function getCertById($cert_id = 0)
    {
        if ($cert_id) {
            return $this->where(['cert_id' => $cert_id])->find();
        }
        return [];
    }

    /**
     * 删除证书
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    integer                        $cert_id [description]
     * @return   [type]                                  [description]
     */
    public function rmCert($cert_id = 0)
    {
        return $this->where(['cert_id' => $cert_id])->setField('is_del', 1);
    }
    /**
     * 恢复删除的证书
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    integer                        $cert_id [description]
     * @return   [type]                                  [description]
     */
    public function reCert($cert_id = 0)
    {
        return $this->where(['cert_id' => $cert_id])->setField('is_del', 0);
    }
    /**
     * 获取指定证书解析后信息
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    integer                        $cert_id [description]
     * @param    [type]                         $data    [description]
     * @return   [type]                                  [description]
     */
    public function getCertParseInfoById($cert_id = 0, $data = [])
    {
        if (!$cert_id && empty($data)) {
            return [];
        }
        $data = empty($data) ? $this->getCertById($cert_id) : $data;
        if ($data['grade_list']) {
            $grade_list = explode(',', $data['grade_list']);
            $parse_list = [];
            foreach ($grade_list as $v) {
                $value        = $this->getGradeValue('/(\d+)-(\d+)?#(.*)/', $v);
                $parse_list[] = [
                    'min'  => $value[1],
                    'max'  => $value[2],
                    'desc' => $value[3],
                ];
            }
            $data['grade_list'] = $parse_list;
        }
        return $data;
    }

    /**
     * 获取对应的值
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    [type]                         $reg [description]
     * @param    [type]                         $str [description]
     * @return   [type]                              [description]
     */
    public function getGradeValue($reg, $str)
    {
        preg_match($reg, $str, $value);
        return isset($value) ? $value : '';
    }
    /**
     * 颁发证书
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    integer                        $exams_paper_id        [description]
     * @param    integer                        $uid         [description]
     * @param    integer                        $user_exam_score [description]
     * @param    integer                        $exams_users_id         [description]
     * @return   [type]                                          [description]
     */
    public function createUserCert($exams_paper_id = 0, $uid = 0, $user_exam_score = 0, $exams_users_id = 0)
    {
        $data  = $this->where(['exams_paper_id' => $exams_paper_id])->find();
        $info  = $this->getCertParseInfoById($data['cert_id'], $data);
        $grade = '';
        if ($info['grade_list']) {
            $grade = $this->getGrade($info['grade_list'], $user_exam_score);
        }
        if ($grade === '') {
            $this->error = '没有获得相关证书';
            return false;
        }
        //没有证书颁发
        if (!$grade) {
            return false;
        }

        $add = [
            'cert_code'       => date('YmdHi') . mt_rand(1000, 9999),
            'grade'           => $grade,
            'cert_start_time' => time(),
            'cert_end_time'   => strtotime('+' . $info['cert_validity_time'] . ' days'),
            'create_time'   => time(),
            'update_time'   => time(),
            'uid'         => $uid,
            'exams_paper_id'        => $exams_paper_id,
            'exams_users_id'         => $exams_users_id,
        ];
        if (M('exams_user_cert')->add($add)) {
            return true;
        }
        $this->error = '证书颁发失败';
        return false;
    }
    /**
     * 计算并取得等级
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    [type]                         $grade_list [description]
     * @param    [type]                         $score      [description]
     * @return   [type]                                     [description]
     */
    private function getGrade($grade_list, $score)
    {
        if (is_array($grade_list)) {
            foreach ($grade_list as $v) {
                if ($v['min'] <= $score && $score <= $v['max']) {
                    return $v['desc'];
                }
            }
        }
        return '';
    }

    /**
     * 获取某用户的证书列表
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    integer                        $uid [description]
     * @param    [type]                         $map     [description]
     * @return   [type]                                  [description]
     */
    public function getUserCertList($uid = 0, $map = [])
    {
        $list = [];
        if ($uid) {
            $tp             = C('DB_PREFIX');
            $map['uid'] = $uid;
            $list           = M("exams_user_cert e")->join("`{$tp}exams_cert` c ON e.exams_paper_id = c.exams_paper_id")->where($map)->field(['e.*','c.*','c.create_time as u_create_time','c.update_time as u_update_time'])->order('e.update_time desc')->select();
            if ($list) {
                foreach ($list as $k => &$v) {
                    $exams_paper_title = M('exams_paper')->where(['exams_paper_id' => $v['exams_paper_id']])->getField('exams_paper_title');
                    $v['exam_name']    = $exams_paper_title;
                    $v['cert_content'] = $this->parseContent($v['cert_content'], $v);
                }
            } else {
                $list = [];
            }
        }
        return $list;
    }
    /**
     * 解析替换证书内容变量
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    [type]                         $content [description]
     * @param    [type]                         $value   [description]
     * @return   [type]                                  [description]
     */
    private function parseContent($content, $value)
    {
        $value['yyyy']            = date('Y', $value['cert_start_time']);
        $value['mm']              = date('m', $value['cert_start_time']);
        $value['dd']              = date('d', $value['cert_start_time']);
        $value['exam_year']       = date('Y', $value['cert_start_time']);
        $value['exam_month']      = date('m', $value['cert_start_time']);
        $value['exam_day']        = date('d', $value['cert_start_time']);
        $value['user_name']       = getUserName($value['uid']);
        $value['cert_start_time'] = date('Y.m.d', $value['cert_start_time']);
        $value['cert_end_time']   = date('Y.m.d', $value['cert_end_time']);
        $keys                     = array_keys($value);
        $keys                     = array_map(create_function('$v', 'return "[".$v."]";'), $keys);
        return str_replace($keys, $value, $content);
    }

    /**
     * 通过查询获取用户证书
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-05
     * @param    integer                        $type  [description]
     * @param    string                         $value [description]
     * @return   [type]                                [description]
     */
    public function getUserCertBySearch($type = 0, $value = '')
    {
        if (!in_array($type, [0, 1, 2, 3])) {
            $this->error = '请选择正确的查询方式';
            return false;
        }
        $map = [];
        switch ($type) {
            case 0:
            // 用户UID
                $uid = $value;
                break;
            //手机号
            case 1:
                $uid = M('user_verified')->where(['phone' => $value])->getField('uid');
                break;
            //身份证
            case 2:
                $uid = M('user_verified')->where(['idcard' => $value])->getField('uid');
                break;
            //真实姓名
            case 3:
                $uid = M('user_verified')->where(['realname' => $value])->getField('uid');
                break;
            
            //证书编号
            default:
                $uid              = M('exams_user_cert')->where(['cert_code' => $value])->getField('uid');
                $map['cert_code'] = $value;
                break;
        }
        if (!$uid && $type != 0) {
            $this->error = '未能查询到你的相关信息,请先完成认证';
            return false;
        } elseif (!$uid) {
            $this->error = '未能查询到该证书信息';
            return false;
        }
        return $this->getUserCertList($uid, $map);
    }
}
