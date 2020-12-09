<?php
/**
 * 云课堂后台配置
 * 1.课程管理 - 目前支持1级分类
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php');
use Qiniu\Auth as QiniuAuth;

class AdminVideoAction extends AdministratorAction
{
    protected $cc_video_config = array();

    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        $this->cc_video_config = model('Xdata')->get('classroom_AdminConfig:ccyun');

        parent::_initialize();
    }

    //通过审核课程列表
    public function index()
    {
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        $this->pageKeyList = array('id', 'video_title', 'cover', 'v_price', 't_price', 'user_title', 'teacher_name',
            'video_collect_count', 'video_comment_count', 'video_question_count', 'video_note_count', 'video_score',
            'video_order_count', 'video_order_count_mark','is_charge', 'activity', 'best', 'ctime', 'DOACTION');
        $this->pageButton[]         = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[]         = array('title' => '禁用', 'onclick' => "admin.delVideoAll('delVideoAll')");
        $this->searchKey            = array('id', 'video_title', 'uid', 'teacher_id', 'is_charge', 'is_best', 'is_best_like', 'is_cete_floor', 'is_re_free', array('listingtime', 'listingtime2'), array('uctime', 'uctime2'), 'quanzhong');
        $this->searchPostUrl        = U('classroom/AdminVideo/index', array('tabHash' => index));
        $this->opt['is_best']       = array('0' => '不限', '1' => '否', '2' => '是');
        $this->opt['is_charge']     = array('0' => '不限', '1' => '否', '2' => '是');
        $this->opt['is_best_like']  = array('0' => '不限', '1' => '否', '2' => '是');
        $this->opt['is_cete_floor'] = array('0' => '不限', '1' => '否', '2' => '是');
        $this->opt['is_re_free']    = array('0' => '不限', '1' => '否', '2' => '是');

        $this->opt['quanzhong'] = array('best_sort asc' => '精选推荐', 'best_like_sort asc' => '喜欢推荐', 'cete_floor_sort asc' => '分类楼层推荐', 're_free_sort asc' => '天天特价推荐');

        $listData = $this->_getData(20, 0, 1, 1);
        $this->displayList($listData);
    }

    //未通过审核课程列表
    public function unauditList()
    {
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        //$this->pageButton[] = array("title"=>"审核","onclick"=>"admin.crossVideos('crossVideos','批量审核','课程')");
        $this->pageKeyList = array('id', 'video_title', 'cover', 'user_title', 'teacher_name', 'ctime', 'DOACTION');
        $listData          = $this->_getData(20, 0, ['in', '3,6'], 2);
        $this->displayList($listData);
    }

    //待审核课程列表
    public function forwordUnauditList()
    {
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        $this->pageButton[] = array("title" => "批量删除", "onclick" => "admin.crossVideos('','crossVideos','批量删除','课程')");
        $this->pageKeyList  = array('id', 'video_title', 'user_title', 'activity', 'ctime', 'DOACTION');
        $listData           = $this->_getData(20, 0, 0, 2);
        $this->displayList($listData);
    }

    //课程回收站(被隐藏的课程)
    public function recycle()
    {
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        $this->pageButton[] = array("title" => "彻底删除", "onclick" => "admin.crossVideos('delVideos')");
        $this->pageKeyList  = array('id', 'video_title', 'user_title', 'activity', 'ctime', 'DOACTION');
        $listData           = $this->_getData(20, $is_del = 1, 1, 2);
        $this->displayList($listData);
    }

    //视频库
    public function videoLib()
    {
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTitle['videoLib'] = '视频库';
        $this->pageButton[]          = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[]          = array("title" => "添加", "onclick" => "admin.addVideoLib()");
        $this->pageButton[]          = array("title" => "禁用", "onclick" => "admin.delVideoLib('status')");
        $this->pageButton[]          = array("title" => "删除", "onclick" => "admin.delVideoLib('is_del')");
        $this->pageKeyList           = array('id', 'uid', 'title', 'video_type', 'type', 'ctime', 'is_syn', 'status_txt', 'DOACTION');
        $this->searchKey             = array('id', 'title', 'uid', 'type', 'status', array('ctime', 'ctime1'));
        $this->searchPostUrl         = U('classroom/AdminVideo/videoLib', array('tabHash' => 'videoLib'));
        $this->opt['type']           = array('0' => '不限', '1' => '视频', '2' => '音频', '3' => '文本', '4' => '文档');
        $this->opt['status']         = array('0' => '不限', '1' => '禁用', '2' => '正常');
        if ($_POST['status']) {
            if ($_POST['status'] == 0) {
                unset($_POST['status']);
            }
            if ($_POST['status'] == 1) {
                $_POST['status'] && $map['status'] = 0;
            }
            if ($_POST['status'] == 2) {
                $_POST['status'] && $map['status'] = 1;
            }
        }

        !empty($_POST['id']) && $map['id']   = intval($_POST['id']);
        !empty($_POST['uid']) && $map['uid'] = intval($_POST['uid']);
        $_POST['title'] && $map['title']     = array('like', '%' . t($_POST['title']) . '%');
        if (!empty($_POST['ctime'][0]) && !empty($_POST['ctime'][1])) {
            // 时间区间条件
            $map['ctime'] = array('BETWEEN', array(strtotime($_POST['ctime'][0]),
                strtotime($_POST['ctime'][1])));
        } else if (!empty($_POST['ctime'][0])) {
            // 时间大于条件
            $map['ctime'] = array('GT', strtotime($_POST['ctime'][0]));
        } elseif (!empty($_POST['ctime'][1])) {
            // 时间小于条件
            $map['ctime'] = array('LT', strtotime($_POST['ctime'][1]));
        }
        $map['is_del'] = 0;
        if ($_POST['type']) {
            if ($_POST['type'] == 0) {
                unset($_POST['type']);
            } else {
                !empty($_POST['type']) && $map['type'] = intval($_POST['type']);
            }
        }
        $map['is_syn']  = 1;
        $listData       = M('zy_video_data')->where($map)->order('ctime desc')->findPage(20);
        $video_type_arr = [0 => '本地', 1 => '七牛', 4 => 'CC存储'];
        foreach ($listData['data'] as &$value) {
            $value['video_type'] = $video_type_arr[$value['video_type']];
            switch ($value['type']) {
                case 1:
                    $value['type'] = '视频';
                    break;
                case 2:
                    $value['type'] = '音频';
                    break;
                case 3:
                    $value['type'] = '文本';
                    break;
                case 4:
                    $value['type'] = '文档';
                    break;
                default;
            }
            $value['uid']    = getUserSpace($value['uid'], null, '_blank');
            $value['is_syn'] = '已同步';
            if ($value['status'] == 1) {
                $status_txt          = ['<font color="red">转码失败</font>', '<font color="green">正常</font>', '正在转码'];
                $value['status_txt'] = $status_txt[$value['transcoding_status']];
            } else {
                $value['status_txt'] = '禁用';
            }

            $value['ctime']    = date('Y-m-d H:i', $value['ctime']);
            $value['DOACTION'] = '<a href="javascript:void(0);" onclick="admin.resourcesPreview(' . $value['id'] . ');">预览</a> | ';
            $value['DOACTION'] .= '<a href="' . U('classroom/AdminVideo/addVideoLib', array('id' => $value['id'], 'tabHash' => 'addVideoLib')) . '">编辑</a> | ';
            $value['DOACTION'] .= $value['status'] ? '<a onclick="admin.opervideo(' . $value['id'] . ' , \'status\', 0);" href="javascript:void(0)">禁用</a>  | ' : '<a onclick="admin.opervideo(' . $value['id'] . ',\'status\', 1);" href="javascript:void(0)">恢复</a>  | ';
            $value['DOACTION'] .= '<a onclick="admin.opervideo(' . $value['id'] . ', \'is_del\', 1);" href="javascript:void(0)">删除</a> ';
        }
        $this->displayList($listData);
    }

    //资源预览
    public function resourcesPreview()
    {
        $video_data = M('zy_video_data')->where('id=' . intval($_GET['id']))->field('video_address,videokey,video_type,type,transcoding_status')->find();

        $this->assign($video_data);

        $video_address = $video_data['video_address'];

        //如果上传到CC服务器
        if ($video_data['video_type'] == 4) {
            $cc_video_config = model('Xdata')->get('classroom_AdminConfig:ccyun');
            $this->assign("ccvideo_config", $cc_video_config);
            $this->assign('videokey', $video_data['videokey']);
        } else if ($video_data['video_type'] == 1) {
            // 转码状态
            if ($video_data['transcoding_status'] == 2) {
                echo '<div style="padding: 30px;">正在转码中,请稍后查看...</div>';exit;
            }
            $qiniuauth = new QiniuAuth(getAppConfig('qiniu_AccessKey', 'qiniuyun'), getAppConfig('qiniu_SecretKey', 'qiniuyun'));
            // 自动检测是否为HTTPS访问
            $host = IS_HTTPS ? 'https://' : "http://";
            // 获取配置的访问域名
            $domain = getAppConfig('qiniu_Domain', 'qiniuyun');
            if ($video_data['type'] == 1) {
                $t   = 86400 * 3;
                $url = $host . $domain . '/' . $video_data['videokey'] . '?pm3u8/0/expires/' . $t;
                $this->assign('is_hls', 1);
            } elseif ($video_data['type'] == 2) {
                $url = $host . $domain . '/' . $video_data['videokey'];
            }
            $video_address = $qiniuauth->privateDownloadUrl($url, 3600);

        } else {
            // 本地
            $secret   = $_SERVER['HTTP_HOST']; // 密钥
            $url_info = parse_url($video_data['video_address']);
            $path     = $url_info['path']; // 下载文件
            if ($path) {
                if ($video_data['type'] == 4) {
                    $extension = substr(strrchr($path, '.'), 1);
                    // 扩展名不是pdf
                    if ($extension != 'pdf') {
                        $file         = SITE_PATH . $path;
                        $turnFileName = substr($file,0,strrpos($file,'.')).'.pdf';
                        if (!is_file($turnFileName)) {
                            $command = 'PATH=$PATH unoconv -l -f pdf ' . $file . '> /dev/null &';
                            exec($command);
                            echo '<div style="padding: 30px;">文档正在转码,请稍后查看</div>';exit;
                        }
                        // 更新扩展名
                        $video_address = str_replace($extension, 'pdf', $video_data['video_address']);
                    }

                } elseif ($video_data['type'] != 3) {
                    // 下载到期时间,time是当前时间,300表示300秒,也就是说从现在到300秒之内文件不过期
                    $expire = time() + 3600;
                    // 用文件路径、密钥、过期时间生成加密串
                    $md5 = base64_encode(md5($secret . $path . $expire, true));
                    $md5 = strtr($md5, '+/', '-_');
                    $md5 = str_replace('=', '', $md5);

                    $video_address = $video_data['video_address'] . '?m=' . $md5 . '&e=' . $expire;
                }
            }
        }
        if (!trim($video_address) && $video_data['video_type'] != 4) {
            echo '<div style="padding: 30px;">没有找到该资源</div>';exit;
        }
        $this->assign('video_address', $video_address);
        $this->display();
    }

    //CC待同步视频库
    public function videoLibVerify()
    {

        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();

        $this->pageTitle['videoLib'] = 'CC待同步视频库';

        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array("title" => "添加", "onclick" => "admin.addVideoLib()");
        $this->pageButton[] = array("title" => "下载", "onclick" => "admin.doVideoDown()");

        $this->pageKeyList   = array('id', 'uid', 'title', 'type', 'videokey', 'ctime', 'is_syn', 'status_txt', 'DOACTION');
        $this->searchKey     = array('id', 'title', 'uid', 'type', 'status', array('ctime', 'ctime1'));
        $this->searchPostUrl = U('classroom/AdminVideo/videoLibVerify', array('tabHash' => 'videoLibVerify'));

        $this->opt['type']   = array('0' => '不限', '1' => '视频', '2' => '音频', '3' => '文本', '4' => '文档');
        $this->opt['status'] = array('0' => '不限', '1' => '禁用', '2' => '正常');

        if ($_POST['status']) {
            if ($_POST['status'] == 0) {
                unset($_POST['status']);
            }
            if ($_POST['status'] == 1) {
                $_POST['status'] && $map['status'] = 0;
            }
            if ($_POST['status'] == 2) {
                $_POST['status'] && $map['status'] = 1;
            }
        }

        !empty($_POST['id']) && $map['id']   = intval($_POST['id']);
        !empty($_POST['uid']) && $map['uid'] = intval($_POST['uid']);
        $_POST['title'] && $map['title']     = array('like', '%' . t($_POST['title']) . '%');
        if (!empty($_POST['ctime'][0]) && !empty($_POST['ctime'][1])) {
            // 时间区间条件
            $map['ctime'] = array('BETWEEN', array(strtotime($_POST['ctime'][0]),
                strtotime($_POST['ctime'][1])));
        } else if (!empty($_POST['ctime'][0])) {
// 时间大于条件
            $map['ctime'] = array('GT', strtotime($_POST['ctime'][0]));
        } elseif (!empty($_POST['ctime'][1])) {
// 时间小于条件
            $map['ctime'] = array('LT', strtotime($_POST['ctime'][1]));
        }
        $map['is_del'] = 0;
        if ($_POST['type']) {
            if ($_POST['type'] == 0) {
                unset($_POST['type']);
            } else {
                !empty($_POST['type']) && $map['type'] = intval($_POST['type']);
            }
        }

        $map['video_type'] = 4;
        $map['_string']    = " is_syn = 0 or video_address = '' or duration = '00:00' or filesize = ''";

        $listData = M('zy_video_data')->where($map)->order('ctime desc')->findPage(20);

        foreach ($listData['data'] as &$value) {
            switch ($value['type']) {
                case 1:
                    $value['type'] = '视频';
                    break;
                case 2:
                    $value['type'] = '音频';
                    break;
                case 3:
                    $value['type'] = '文本';
                    break;
                case 4:
                    $value['type'] = '文档';
                    break;
                default;
            }
            $value['uid']        = getUserSpace($value['uid'], null, '_blank');
            $value['is_syn']     = '待同步';
            $value['status_txt'] = $value['status'] ? '正常' : '禁用';
            $value['ctime']      = date('Y-m-d H:i', $value['ctime']);
            $value['DOACTION']   = '<a href="' . U('classroom/AdminVideo/addVideoLib', array('id' => $value['id'], 'tabHash' => 'addVideoLib')) . '">编辑</a> | ';
            $value['DOACTION'] .= '<a onclick="admin.auditVideo(' . $value['id'] . ');" href="javascript:void(0)">同步信息</a> ';
        }
        $this->displayList($listData);
    }

    public function doVideoDown()
    {
        if (isset($_POST)) {
            //'videoid_from','videoid_to','num_per_page','page'
            //        $find_url  = $this->cc_video_config['cc_apiurl'].'video/v2?';

            if ($_POST['down_opt'] == 1) {
                if ($_POST['num_per_page'] && intval($_POST['num_per_page']) > 100) {
                    $this->error("每页数据最多为100条");
                }
                $url = $this->cc_video_config['cc_apiurl'] . 'videos/v3?';

                t($_POST['videoid_from']) ? $query['videoid_from'] = urlencode(t($_POST['videoid_from'])) : '';
                t($_POST['videoid_to']) ? $query['videoid_to']     = urlencode(t($_POST['videoid_to'])) : '';
                t($_POST['num_per_page']) ? $query['num_per_page'] = urlencode(t($_POST['num_per_page'])) : '';
                t($_POST['page']) ? $query['page']                 = urlencode(t($_POST['page'])) : '';
                $query['userid']                                   = urlencode($this->cc_video_config['cc_userid']);
                $query['format']                                   = urlencode('json');

                $url = $url . createVideoHashedQueryString($query)[1] . '&time=' . time() . '&hash=' . createVideoHashedQueryString($query)[0];

                $res = getDataByUrl($url);

                $insert_video_value = "";
                foreach ($res['videos']['video'] as $video => $ino) {
                    $is_down = M('zy_video_data')->where(['videokey' => $ino['id']])->getField('videokey');
                    if (!$is_down) {
                        $insert_video_value .= "(" . $this->mid . ",'{$ino['title']}','1','{$ino['id']}'," . time() . ",1,0,'" . secondsToHour($ino['duration']) . "','{$ino['totalsize']}',1,4,1),";
                    }
                }
                if ($insert_video_value) {
                    $live_order_sql = "INSERT INTO " . C("DB_PREFIX") . "zy_video_data (`uid`,`title`,`type`,`videokey`,`ctime`,`status`,`is_del`,`duration`,`filesize`,`transcoding_status`,`video_type`,`is_syn`) VALUE " . trim($insert_video_value, ',');
                    $res            = M('zy_video_data')->execute($live_order_sql) ? true : false;
                }
                $this->assign('jumpUrl', U('classroom/AdminVideo/doVideoDown', array('data' => json_encode($query))));
            } else {
                if (!$_POST['videoid']) {
                    $this->error("每页数据最多为100条");
                }
                $url = $this->cc_video_config['cc_apiurl'] . 'video/v3?';

                $query['videoid'] = urlencode(t($_POST['videoid']));
                $query['userid']  = urlencode($this->cc_video_config['cc_userid']);
                $query['format']  = urlencode('json');

                $url = $url . createVideoHashedQueryString($query)[1] . '&time=' . time() . '&hash=' . createVideoHashedQueryString($query)[0];

                $res     = getDataByUrl($url);
                $is_down = M('zy_video_data')->where(['videokey' => $res['video']['id']])->getField('videokey');

                $this->assign('jumpUrl', U('classroom/AdminVideo/doVideoDown'));

                if (!$is_down) {
                    $insert_video_value = "(" . $this->mid . ",'{$res['video']['title']}','1','{$res['video']['id']}'," . time() . ",1,0,'" . secondsToHour($res['video']['duration']) . "','{$res['video']['totalsize']}',1,4,1),";
                    $live_order_sql     = "INSERT INTO " . C("DB_PREFIX") . "zy_video_data (`uid`,`title`,`type`,`videokey`,`ctime`,`status`,`is_del`,`duration`,`filesize`,`transcoding_status`,`video_type`,`is_syn`) VALUE " . trim($insert_video_value, ',');
                    $res                = M('zy_video_data')->execute($live_order_sql) ? true : false;
                } else {
                    $this->error("该视频已下载");
                }
            }

            if ($res) {
                $this->success("下载成功");
            } else {
                $this->error("下载失败");
            }
        } else {
            $_REQUEST['tabHash'] = 'doVideoDown';

            $this->_initClassroomListAdminTitle();
            $this->_initClassroomListAdminMenu();

            $this->pageTitle['doVideoDown'] = "CC视频—同步";
            $this->onload[]                 = 'admin.doVideoDownFun()';

            $this->pageKeyList = array('down_opt', 'videoid', 'videoid_from', 'videoid_to', 'num_per_page', 'page');
            $this->notEmpty    = array();

            $this->opt['down_opt'] = [0 => '单个', 1 => '批量'];

            $this->savePostUrl = U('classroom/AdminVideo/doVideoDown');
            $data              = json_decode($_GET['data']);
            $this->displayConfig(['videoid_from' => $data->videoid_from, 'videoid_to' => $data->videoid_to, 'num_per_page' => $data->num_per_page, 'page' => $data->page]);
        }
    }

    //添加视频
    public function addVideoLib()
    {
        if ($_POST) {
            if ($_POST['type'] == 3) {
                $video_address              = $_POST['content'];
                $duration                   = '00:00:00';
                $data['video_type']         = 0;
                $data['is_syn']             = 1;
                $data['transcoding_status'] = 1; // 文本无需转码
            } else {
                //格式化七牛数据
                $videokey = t($_POST['videokey']);
                //获取上传空间 0本地 1七牛 2阿里云 3又拍云
                if (getAppConfig('upload_room', 'basic') == 0) {
                    if ($_POST['attach_ids']) {
                        $video_address = getAttachUrlByAttachId($_POST['attach_ids']);
                    } else {
                        $video_address = $_POST['video_address'];
                    }
                    //TODO
                    //$avinfo = json_decode(file_get_contents($video_address . '?avinfo'), true);
                    $duration           = t($_POST['duration']) ?: number_format($avinfo['format']['duration'] / 60, 2, ':', '');
                    $file_size          = $avinfo['format']['size'];
                    $data['video_type'] = 0;
                    $data['is_syn']     = 1;
                    // 本地仅仅文档转码
                    $data['transcoding_status'] = $_POST['type'] == 4 ? 2 : 1;
                } else if (getAppConfig('upload_room', 'basic') == 4) {
                    $find_url = $this->cc_video_config['cc_apiurl'] . 'video/v2?';
                    $play_url = $this->cc_video_config['cc_apiurl'] . 'video/playcode?';

                    $query['videoid'] = urlencode(t($videokey));
                    $query['userid']  = urlencode($this->cc_video_config['cc_userid']);
                    $query['format']  = urlencode('json');

                    $find_url = $find_url . createVideoHashedQueryString($query)[1] . '&time=' . time() . '&hash=' . createVideoHashedQueryString($query)[0];
                    $play_url = $play_url . createVideoHashedQueryString($query)[1] . '&time=' . time() . '&hash=' . createVideoHashedQueryString($query)[0];

                    $info_res = getDataByUrl($find_url);
                    $play_res = getDataByUrl($play_url);

                    $duration = t($_POST['duration']) ?: secondsToHour($info_res['video']['duration']);

                    $video_address = $play_res['video']['playcode'];
                    $file_size     = $info_res['video']['definition'][3]['filesize'] ?: 0;

                    $data['video_type']         = 4;
                    $data['is_syn']             = 0;
                    $data['transcoding_status'] = 1;
                } else {
                    $video_address = null;
                    // getAppConfig('qiniu_Domain', 'qiniuyun') . "/" . $videokey;
                    // $avinfo        = json_decode(file_get_contents($video_address . '?avinfo'), true);

                    // $duration = t($_POST['duration']) ?: secondsToHour($avinfo['format']['duration']);

                    //$file_size = $avinfo['format']['size'];
                    $duration                   = '00:00:00';
                    $file_size                  = 0;
                    $data['transcoding_status'] = 2;
                    $data['video_type']         = 1;
                    $data['is_syn']             = 1;
                }
            }

            if ($_POST['id']) {//修改
                $datas['title']    = t($_POST['title']);
                $res              = M('zy_video_data')->where('id=' . intval($_POST['id']))->save($datas);
            } else {//添加

                $data['uid']           = $this->mid;
                $data['title']         = t($_POST['title']);
                $data['duration']      = $duration;
                $data['type']          = t($_POST['type']);
                $data['video_address'] = $video_address;
                $data['videokey']      = $videokey;
                $data['filesize']      = $file_size;
                $data['ctime']         = time();

                $res = M('zy_video_data')->add($data);
            }
            if ($res !== false) {
                if ($_POST['id']) {
                    exit(json_encode(array('status' => '1', 'info' => '编辑成功')));
                } else {
                    exit(json_encode(array('status' => '1', 'info' => '添加成功')));
                }
            } else {
                exit(json_encode(array('status' => '0', 'info' => '操作失败')));
            }
        } else {
            $this->_initClassroomListAdminMenu();
            $this->_initClassroomListAdminTitle();
            $this->pageTab[] = array('title' => '添加视频', 'tabHash' => 'addVideoLib', 'url' => U('classroom/AdminVideo/addVideoLib'));
            $upload_room = getAppConfig('upload_room', 'basic');
            //如果上传到七牛服务器
            if ($upload_room == 1) {
                $qiniuConf = model('Xdata')->get('classroom_AdminConfig:qiniuyun');
                $auth      = new QiniuAuth($qiniuConf['qiniu_AccessKey'], $qiniuConf['qiniu_SecretKey']);
                //生成上传凭证
                $bucket = $qiniuConf['qiniu_Bucket'];
                //Qiniu_SetKeys(getAppConfig('qiniu_AccessKey','qiniuyun'), getAppConfig('qiniu_SecretKey','qiniuyun'));
                //$putPolicy = new Qiniu_RS_PutPolicy($bucket);
                $filename = "{$this->site['site_keyword']}" . rand(5, 8) . time();
                //$str = "{$bucket}:{$filename}";
                // $entryCode = Qiniu_Encode($str);
                //$putPolicy->PersistentOps= "avthumb/mp4/ab/160k/ar/44100/acodec/libfaac/r/30/vb/5400k/vcodec/libx264/s/1920x1080/autoscale/1/strpmeta/0|saveas/".$entryCode;
                //$upToken = $putPolicy->Token(null);
                $pattern = \Qiniu\base64_urlSafeEncode('ts_' . $filename . '.m3u8_$(count)');
                $saveas  = \Qiniu\base64_urlSafeEncode("{$bucket}:{$filename}.m3u8");
                $hlsKey  = C('QINIU_TS_KEY');
                if (!$hlsKey) {
                    // 写入默认的加密key
                    $config                 = include CONF_PATH . '/config.inc.php';
                    $config['QINIU_TS_KEY'] = $hlsKey = 'eduline201701010';
                    file_put_contents(CONF_PATH . '/config.inc.php', ("<?php \r\n return " . var_export($config, true) . "; \r\n ?>"));
                }
                $hlsKeyUrl = \Qiniu\base64_urlSafeEncode(SITE_URL . '/qiniu/getVideoKey');
                $hlsKey    = \Qiniu\base64_urlSafeEncode($hlsKey);
                // /hlsKeyType/1.0
                //$pipeline = $qiniuConf['qiniu_Pipeline'];
                // 处理命令参数
                $fops = 'avthumb/m3u8/noDomain/1/segtime/10/ab/160k/ar/44100/acodec/libfaac/r/30/vb/5400k/vcodec/libx264/s/1920x1080/stripmeta/0/pattern/' . $pattern . '/hlsKey/' . $hlsKey . '/hlsKeyUrl/' . $hlsKeyUrl;
                //$fops = 'avthumb/m3u8/noDomain/1/segtime/10/ab/160k/ar/44100/acodec/libfaac/r/30/vb/5400k/vcodec/libx264/s/1920x1080/stripmeta/0/pattern/' . $pattern;
                $is_open = getAppConfig('water_open', 'basic');
                if ($is_open == 1) {
                    // 是否设置了水印
                    $water_image = getAppConfig('water_image', 'basic');

                    if ($water_image) {
                        // 图片是否存在
                        $water_file = getAttachUrlByAttachId($water_image);
                        if ($water_file) {
                            $fops .= '/wmImage/' . \Qiniu\base64_urlSafeEncode($water_file);
                            // 水印位置
                            $water_postion = getAppConfig('water_postion', 'basic') ?: 'NorthWest';
                            $fops .= '/wmGravity/' . $water_postion;
                        }

                    }
                }
                $policy = array(
                    'persistentOps'       => $fops . '|saveas/' . $saveas,
                    'persistentPipeline'  => $qiniuConf['qiniu_Pipeline'], // 获取转码队列名称
                    'persistentNotifyUrl' => SITE_URL . '/qiniu/persistent/pipelineToHLS', // 回调通知
                );
                $upToken = $auth->uploadToken($bucket, $filename, 3600, $policy);
                $this->assign("uptoken", $upToken);
            } else if ($upload_room == 4) {
                $filename = "{$this->site['site_keyword']}" . rand(5, 8) . time();
                $this->assign("ccvideo_config", $this->cc_video_config);
            }
            $this->assign('upload_room', $upload_room);
            $this->assign("filename", $filename);

            if ($_GET['id']) {
                $data = M('zy_video_data')->where('id=' . intval($_GET['id']))->find();
                $this->assign($data);
            }

            $this->display();
        }
    }

    //同步cc视频信息
    public function verifyVideo()
    {
        $id       = intval($_POST['id']);
        $videokey = M('zy_video_data')->where('id=' . $id)->getField('videokey');
        if ($videokey) {
            $find_url = $this->cc_video_config['cc_apiurl'] . 'video/v2?';
            $play_url = $this->cc_video_config['cc_apiurl'] . 'video/playcode?';

            $query['videoid'] = urlencode(t($videokey));
            $query['userid']  = urlencode($this->cc_video_config['cc_userid']);
            $query['format']  = urlencode('json');

            $find_url = $find_url . createVideoHashedQueryString($query)[1] . '&time=' . time() . '&hash=' . createVideoHashedQueryString($query)[0];
            $play_url = $play_url . createVideoHashedQueryString($query)[1] . '&time=' . time() . '&hash=' . createVideoHashedQueryString($query)[0];

            $info_res = getDataByUrl($find_url);
            $play_res = getDataByUrl($play_url);

            if ($info_res["video"]['duration'] >= 60) {
                $duration = number_format($info_res["video"]['duration'] / 60, 2, ':', '');
            } else {
                $duration = "00:" . $info_res["video"]['duration'];
            }

            $video_address = $play_res['video']['playcode'];
            $file_size     = $info_res['video']['definition'][3]['filesize'] ?: 0;

            $data['is_syn']        = 1;
            $data['duration']      = $duration;
            $data['video_address'] = $video_address;
            $data['filesize']      = $file_size;
            if (!$duration || !$video_address || !$file_size) {
                $this->mzError("第三方数据查询失败");
            }

            $res = M('zy_video_data')->where('id=' . intval($_POST['id']))->save($data);
            if ($res) {
                $this->mzSuccess("同步成功");
            } else {
                $this->mzError("同步失败,请稍后再试");
            }
        } else {
            $this->mzError("第三方数据查询失败");
        }

    }

    //批量视频操作
    public function delVideoLib()
    {
        $ids = implode(",", $_POST['ids']);
        $ids = trim(t($ids), ",");
        if ($ids == "") {
            $ids = intval($_POST['ids']);
        }
        $msg   = array();
        $where = array(
            'id' => array('in', $ids),
        );
        if ($_POST['type'] == 'status') {
            $data['status'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('zy_video_data')->where($where)->save($data);
        if ($res !== false) {
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data']   = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }
    //视频操作
    public function opervideo()
    {
        $map['id']             = intval($_POST['id']);
        $data[$_POST['field']] = $_POST['val'];
        if (M('zy_video_data')->where($map)->save($data)) {
            $video                     = M('zy_video_data')->where($map)->field('uid,filesize')->select();
            $school_id                 = model('School')->where('uid=' . $video['uid'])->getField('id');
            $video_space               = M('zy_video_space')->where('mhm_id=' . $school_id)->getField('used_video_space');
            $datas['used_video_space'] = $video_space - $video['filesize'];
            M('zy_video_space')->where('mhm_id=' . $school_id)->save($datas);

            exit(json_encode(array('status' => 1, 'data' => '操作成功')));
        } else {
            exit(json_encode(array('status' => 0, 'data' => '操作失败')));
        }
    }

    //课程课时管理
    public function lesson()
    {
        $_REQUEST['tabHash']       = 'lesson';
        $vid                       = intval($_GET['vid']);
        $v_title                   = M('zy_video')->where('id=' . $vid)->getField('video_title');
        $this->pageTitle['lesson'] = $v_title . '-课时管理';
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();

        $vid      = intval($_GET['vid']);
        $treeData = D('VideoSection')->setTable('zy_video_section')->getNetworkList(0, $vid);
        $this->assign('tree', $treeData);
        $this->assign('stable', 'zy_video_section');
        $this->assign('level', 2);
        $this->assign('vid', $vid);
        $this->display();
    }

    //学习记录
    public function learn($limit = 20)
    {
        $this->_initClassroomListAdminMenu();
        $_REQUEST['tabHash'] = 'learn';
        $id                  = intval($_GET['id']);
        $this->pageButton[]  = array('title' => '删除', 'onclick' => "admin.delLearnAll('delArticle')");
//        $this->pageButton[]  = array('title'=>'搜索记录','onclick'=>"admin.fold('search_form')");
        $this->pageKeyList = array('id', 'user_title', 'video_title', 'section_title', 'time', 'ctime', 'DOACTION');
        $this->searchKey   = array('id', 'uid', 'video_title', 'sid');
        $map['is_del']     = ['neq', 2];
        $map['vid']        = $id;

        $video_title = M('zy_video')->where('id=' . $id)->getField("video_title");
        $learn       = M('learn_record')->where($map)->order("ctime DESC")->findPage($limit);
        foreach ($learn['data'] as &$val) {
            $val['ctime'] = date('Y-m-d H:i:s', $val['ctime']);
//            $val['stime'] = date('Y-m-d H:i:s',$val['stime']) ;
            //            $val['time'] = date('Y-m-d H:i:s',$val['time']) ;
            if ($val['time'] == 0) {
                $val['time'] = '<span style="color: green;">已完成</span>';
            }
            $val['user_title']    = getUserSpace($val['uid'], null, '_blank');
            $url                  = U('classroom/Video/view', array('id' => $val['vid']));
            $val['video_title']   = getQuickLink($url, $video_title, "未知课程");
            $val['section_title'] = M('zy_video_section')->where(array('zy_video_section_id' => $val['sid']))->getField('title');
            if ($val['is_del'] == 1) {
                $val['DOACTION'] = '<a href="javascript:admin.mzLearnEdit(' . $val['id'] . ',\'closelearn\',\'显示\',\'学习记录\');">显示</a>';
            } else {
                $val['DOACTION'] = '<a href="javascript:admin.mzLearnEdit(' . $val['id'] . ',\'closelearn\',\'隐藏\',\'学习记录\');">隐藏</a>';
            }
        }
        unset($val);
        $this->_listpk = 'id';
        $this->assign('pageTitle', '学习记录--' . $video_title);
        $this->displayList($learn);
    }

    /**
     * 添加章节页面
     * @return void
     */
    public function addLesson()
    {
        $id     = intval($_GET['id']);
        $stable = t($_GET['stable']);
        $vid    = intval($_GET['vid']);

        $this->assign('id', $id);
        $this->assign('stable', $stable);
        $this->assign('vid', $vid);
        $this->assign('oper', 'add');
        $this->assign('lev', intval($_GET['lev']));
        //$this->assign('list' , $this->getVideoList());

        $this->display();
    }

    /**
     * 编辑章节页面
     * @return void
     */
    public function upLesson()
    {
        $id     = intval($_GET['id']);
        $stable = t($_GET['stable']);

        // 获取该分类的信息
        $res = D('VideoSection')->setTable('zy_video_section')->getCategoryById($id);
        $this->assign($res);
        $this->assign('id', $id);
        $this->assign('stable', $stable);
        $this->assign('oper', 'up');
        //$this->assign('list' , $this->getVideoList());
        $this->display('addLesson');
    }

    //视频库
    public function getVideoList()
    {
        $map['status'] = 1;
        $map['is_del'] = 0;
        if ($_POST['s_title']) {
            $map['title'] = array('like', '%' . t($_POST['s_title']) . '%');
        }
        if ($_POST['s_type']) {
            $map['type'] = intval($_POST['s_type']);
        }

        $total     = M('zy_video_data')->where($map)->count(); //总记录数
        $page      = intval($_POST['pageNum']); //当前页
        $pageSize  = 10; //每页显示数
        $totalPage = ceil($total / $pageSize); //总页数

        $startPage = $page * $pageSize; //开始记录
        //构造数组
        $list['total']     = $total;
        $list['pageSize']  = $pageSize;
        $list['totalPage'] = $totalPage;

        $list['data'] = M('zy_video_data')->where($map)->order('ctime desc')->limit("{$startPage} , {$pageSize}")->findAll();
        $type_text = [0=>'未知',1=>'视频',2=>'音频',3=>'文档',4=>'文本'];
        foreach ($list['data'] as &$val) {
            $val['type']  = $type_text[$val['type']];
            $val['uid']   = getUserName($val['uid']);
            $val['ctime'] = date('Y-m-d', $val['ctime']);
        }
        exit(json_encode($list));
    }

    /**
     * 添加章节操作
     * @return json 返回相关的JSON信息
     */
    public function doAddLesson()
    {
        $pid    = intval($_POST['pid']);
        $title  = t($_POST['title']);
        $stable = t($_POST['stable']);
        $free   = t($_POST['free']);

        if (intval($_POST['vid'])) {
            $data['vid'] = $_POST['vid'];
        }
        if (intval($_POST['cid'])) {
            $data['cid']         = $_POST['cid'];
            $data['is_free']     = $free;
            $data['is_activity'] = 1;
        }
        if (t($_POST['oper']) == 'add') {
            $result = D('VideoSection')->setTable($stable)->addTreeCategory($pid, $title, $data);
        } else {
            $result = D('VideoSection')->setTable($stable)->upTreeCategory($pid, $title, $data);
        }
        $res = array();

        if ($result !== false) {
            $res['status'] = 1;
            if (t($_POST['oper']) == 'add') {
                $res['data'] = '添加章节成功';
            } else {
                $res['data'] = '修改章节成功';
            }
        } else {
            $res['status'] = 0;
            if (t($_POST['oper']) == 'add') {
                $res['data'] = '添加章节失败';
            } else {
                $res['data'] = '修改章节失败';
            }
        }
        exit(json_encode($res));
    }

    //编辑、添加课程
    public function addVideo()
    {
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        if (t($_GET['id'])) {
            $data = D('ZyVideo', 'classroom')->getVideoById(intval($_GET['id']));
            $this->assign($data);
            $this->assign('data', $data);
            //查询讲师列表
            $trlist = $this->teacherList($data['mhm_id']);
            $this->assign('trlist', $trlist);
        } else {
            $this->assign("listingtime", time());
            $this->assign("uctime", time() + 604800);
            $this->assign("video_intro", "");
            $this->assign("is_mount", 1);
        }

        //获取会员等级
        $vip_levels = M('user_vip')->where('is_del=0')->order('sort desc')->getField('id,title');
        $school     = model('School')->where(array('status' => 1, 'is_del' => 0))->field('id,title')->findALL();
        $this->assign('vip_levels', $vip_levels);
        //$this->assign('album_list', $album_list);
        $this->assign('school', $school);
        $this->display();
    }

    public function findSchoolTeacher()
    {
        $map = array(
            'is_del'    => 0,
            'is_reject' => 0,
            'mhm_id'    => $_POST['mhm_id'],
        );
        $teacherlist = D('ZyTeacher')->where($map)->order("ctime DESC")->select();
        $teacher=explode(',',$_POST['teacher_id']);
        $this->assign('teacher', $teacher);
        $this->assign('trlist', $teacherlist);
        $this->assign('teacher_id', $_POST['teacher_id']);
        $trlist_data = $this->fetch('school_teacher');

        $this->mzSuccess($trlist_data);
    }

    //添加课程操作
    public function doAddVideo()
    {
        $post = $_POST;
        
        if (empty($post['video_title'])) {
            exit(json_encode(array('status' => '0', 'info' => "请输入课程标题")));
        }

        if (empty($post['video_levelhidden'])) {
            exit(json_encode(array('status' => '0', 'info' => "请选择课程分类")));
        }

        if (empty($post['cover_ids'])) {
            exit(json_encode(array('status' => '0', 'info' => "请上传课程封面")));
        }

        // if (empty($post['video_binfo'])) {
        //     exit(json_encode(array('status' => '0', 'info' => "请输入课程简介")));
        // }

        if (empty($post['video_intro'])) {
            exit(json_encode(array('status' => '0', 'info' => "请输入课程简介")));
        }

        if (empty($post['teacher_id'])) {
            exit(json_encode(array('status' => '0', 'info' => "请选择讲师")));
        }

        if (empty($post['listingtime'])) {
            exit(json_encode(array('status' => '0', 'info' => "请选择上架时间")));
        }

        if (empty($post['uctime'])) {
            exit(json_encode(array('status' => '0', 'info' => "请选择下架时间")));
        }

        //if(empty($post['term'])) exit(json_encode(array('status'=>'0','info'=>"课程有效期不能为空")));

        $data['listingtime'] = $post['listingtime'] ? strtotime($post['listingtime']) : 0; //上架时间
        $data['uctime']      = $post['uctime'] ? strtotime($post['uctime']) : 0; //下架时间
        if ($data['uctime'] < $data['listingtime']) {
            exit(json_encode(array('status' => '0', 'info' => '下架时间不能小于上架时间')));
        }

        if (intval($post['video_order_count_mark']) >  intval($post['view_nums_mark'])) {
            exit(json_encode(array('status' => '0', 'info' => '学习人数不能大于浏览人数')));
        }

        if (isset($post['is_tlimit'])) {
            if ($post['limit_discount'] > 1 || $post['limit_discount'] < 0) {
                exit(json_encode(array('status' => '0', 'info' => '请输入0-1的数字')));
            }
            if (empty($post['starttime'])) {
                exit(json_encode(array('status' => '0', 'info' => "请选择打折开始时间")));
            }

            if (empty($post['endtime'])) {
                exit(json_encode(array('status' => '0', 'info' => "请选择打折结束时间")));
            }

            $data['starttime'] = $post['starttime'] ? strtotime($post['starttime']) : 0; //上架时间
            $data['endtime']   = $post['endtime'] ? strtotime($post['endtime']) : 0; //下架时间
            if ($data['endtime'] < $data['starttime']) {
                exit(json_encode(array('status' => '0', 'info' => '打折结束时间不能小于打折开始时间')));
            }
        }
        $myAdminLevelhidden       = getCsvInt(t($post['video_levelhidden']), 0, true, true, ','); //处理分类全路径
        $fullcategorypath         = explode(',', $post['video_levelhidden']);
        $category                 = array_pop($fullcategorypath);
        $category                 = $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
        $data['fullcategorypath'] = $myAdminLevelhidden; //分类全路径
        $data['video_category']   = $category == '0' ? array_pop($fullcategorypath) : $category;
        $data['is_activity']      = 1;
        $data['video_title']      = t($post['video_title']); //课程名称
        $data['video_binfo']      = t($post['video_binfo']); //课程介绍
        $data['video_intro']      = $post['video_intro']; //课程介绍
        $data['exam_id']          = t($post['exam_id']); //相关考试id
        $data['v_price']          = $post['v_price']; //市场价格
        $data['t_price']          = $post['t_price']; //销售价格
        $data['vip_level']        = $post['vip_levels']; //vip等级
        $data['is_tlimit']        = isset($post['is_tlimit']) ? intval($post['is_tlimit']) : 0; //限时打折
        $data['starttime']        = $post['starttime'] ? strtotime($post['starttime']) : 0; //限时开始时间
        $data['endtime']          = $post['endtime'] ? strtotime($post['endtime']) : 0; //限时结束时间
        $data['limit_discount']   = isset($post['is_tlimit']) && ($post['limit_discount'] <= 1 && $post['limit_discount'] >= 0) ? $post['limit_discount'] : 1; //限时折扣
        $data['mhm_id']           = intval($_POST['school']); //获取机构
        $data['teacher_id']       = intval($_POST['teacher_id']); //获取讲师
        $data['cover']            = intval($post['cover_ids']); //封面
        $data['term']             = intval($post['term']); //有效期
        $data['videofile_ids']    = isset($post['attach'][0]) ? intval($post['attach'][0]) : 0; //课件id
        //        $data['videofile_ids']          = intval($post['videofile_ids']);
        $data['is_best']         = isset($post['is_best']) ? intval($post['best_recommend']) : 0; //编辑精选
        $data['is_mount']        = 1;
        $data['best_sort']       = intval($post['best_sort']);
        $data['is_best_like']    = intval($post['is_best_like']);
        $data['best_like_sort']  = intval($post['best_like_sort']);
        $data['is_cete_floor']   = intval($post['is_cete_floor']);
        $data['cete_floor_sort'] = intval($post['cete_floor_sort']);
        $data['is_re_free']      = intval($post['is_re_free']);
        $data['re_free_sort']    = intval($post['re_free_sort']);
        $data['video_order_count_mark']    = intval($post['video_order_count_mark']);
        $data['view_nums_mark']    = intval($post['view_nums_mark']);
//        $data['t_price']            = $data['v_price'] * $data['limit_discount'];
        $data['type'] = 1; //类型为点播
        $video_tag    = t($post['video_tag']); //课程标签
        if ($data['vip_level']) {
            $data['vip_pattern'] = $post['vip_pattern']; //vip使用模式
        }
        $data['is_best']   = intval($post['is_best']); //精选
        $data['is_charge'] = intval($post['is_charge']); //免费

        if ($post['id']) {
            $data['utime'] = time();
            $result        = M('zy_video')->where('id = ' . $post['id'])->data($data)->save();
        } else {
            $data['ctime'] = time();
            $data['utime'] = time();
            $data['uid']   = $this->mid;
            $result        = M('zy_video')->data($data)->add();
        }

        if ($result) {
            unset($data);
            if ($post['id']) {
                //添加标签
                model('Tag')->setAppName('classroom')->setAppTable('zy_video')->deleteSourceTag($post['id']);
                $tag_reslut = model('Tag')->setAppName('classroom')->setAppTable('zy_video')->addAppTags($post['id'], $video_tag);
            } else {
                $tag_reslut = model('Tag')->setAppName('classroom')->setAppTable('zy_video')->addAppTags($result, $video_tag);
                if ($post['crow_id']) {
                    M('Crowdfunding')->where(array('id' => $post['crow_id']))->data(array('video_id' => $result))->save();
                }
            }
            $data['str_tag'] = implode(',', getSubByKey($tag_reslut, 'name'));
            $data['tag_id']  = ',' . implode(',', getSubByKey($tag_reslut, 'tag_id')) . ',';
            $map['id']       = $post['id'] ? $post['id'] : $result;
            M('zy_video')->where($map)->data($data)->save();
            if ($post['id']) {
                exit(json_encode(array('status' => '1', 'info' => '编辑成功')));
            } else {
                exit(json_encode(array('status' => '1', 'info' => '添加成功')));
            }
        } else {
            exit(json_encode(array('status' => '0', 'info' => '操作失败，请检查数据是否完整')));
        }
    }

    //批量审核课程
    public function crossVideos()
    {
        $map['id']           = is_array($_POST['ids']) ? array('IN', $_POST['ids']) : intval($_POST['ids']);
        $table               = M('zy_video');
        $data['is_activity'] = 1;
        $result              = $table->where($map)->data($data)->save();
        if ($result) {
            $credit = M('credit_setting')->where(array('id' => 38, 'is_open' => 1))->field('id,name,score,count')->find();
            if ($credit['score'] > 0) {
                $ctype = 6;
                $note  = '上传课程获得的积分';
            }
            $uids = $table->where($map)->field('uid')->findAll();
            foreach ($uids as &$val) {
                model('Credit')->addUserCreditRule($val['uid'], $ctype, $credit['id'], $credit['name'], $credit['score'], $credit['count'], $note);
            }
            $this->ajaxReturn('审核成功');
        } else {
            $this->ajaxReturn('系统繁忙，稍后再试');
        }
    }
    //批量禁用课程
    public function delVideoAll()
    {
        $ids = implode(",", $_POST['ids']);
        $ids = trim(t($ids), ",");
        if ($ids == "") {
            $ids = intval($_POST['ids']);
        }
        $msg   = array();
        $where = array(
            'id' => array('in', $ids),
        );

        $data['is_del'] = 1;
        $res            = M('zy_video')->where($where)->save($data);
        if ($res !== false) {
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data']   = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }

    //批量删除课程
    public function delVideos()
    {
        $map['id'] = is_array($_POST['ids']) ? array('IN', $_POST['ids']) : intval($_POST['ids']);
        $table     = M('zy_video');
        $result = $table->where($map)->delete();
       // $data['is_del'] = 2;
        //$result         = $table->where($map)->save($data);
        if ($result) {
            $video['video_id']= $map['id'];
            M('zy_order_course')->where($video)->delete();
            $live['live_id']= $map['id'];
            M('zy_order_live')->where($live)->delete();
            $kid['kid']= $map['id'];
            M('group_giving')->where($kid)->delete();
            $this->ajaxReturn('删除成功');
        } else {
            $this->ajaxReturn('系统繁忙，稍后再试');
        }
    }

    //删除(隐藏)课程
    public function delVideo()
    {
        if (!$_POST['id']) {
            exit(json_encode(array('status' => 0, 'info' => '请选择要删除的对象!')));
        }
        $map['id']      = intval($_POST['id']);
        $data['is_del'] = $_POST['is_del'] ? 0 : 1; //传入参数并设置相反的状态
        if (M('zy_video')->where($map)->data($data)->save()) {
            exit(json_encode(array('status' => 1, 'info' => '操作成功')));
        } else {
            exit(json_encode(array('status' => 1, 'info' => '操作失败')));
        }
    }

    //显示/隐藏 学习记录
    public function closelearn()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg   = array();
        $where = array(
            'id' => array('in', $id),
        );
        $is_del = M('learn_record')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('learn_record')->where($where)->save($data);

        if ($res !== false) {
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data']   = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }

    /**
     * 删除视频(删除存储空间的视频)
     */
    public function deletevideo()
    {
        $videokey = t($_POST['videokey']); //获取视频key

        $bucket = getAppConfig('qiniu_Bucket', 'qiniuyun');
        Qiniu_SetKeys(getAppConfig('qiniu_AccessKey', 'qiniuyun'), getAppConfig('qiniu_SecretKey', 'qiniuyun'));
        $client = new Qiniu_MacHttpClient(null);
        $err    = Qiniu_RS_Delete($client, $bucket, $videokey);

        if ($err !== null) {
            exit(json_encode(array('status' => '0', 'info' => "删除失败或视频已不存在！")));
        } else {
            $data['qiniu_key'] = "";
            D('ZyVideo')->where(array("qiniu_key" => $videokey))->save($data);
            exit(json_encode(array('status' => '1', 'info' => '删除成功，请添加新视频！')));
        }
    }

    //讲师列表
    private function teacherList($mhm_id)
    {
        $map = array(
            'is_del'          => 0,
            'is_reject'       => 0,
            'verified_status' => 1,
        );
        if ($mhm_id) {
            $map['mhm_id'] = $mhm_id;
        }
        $teacherlist = D('ZyTeacher')->where($map)->order("ctime DESC")->select();
        return $teacherlist;
    }

    //待审核课时列表
    public function unauditLesson()
    {
        $vid = intval($_GET['vid']);
        $this->_initClassroomListAdminMenu();
        $_REQUEST['tabHash']              = 'unauditLesson';
        $v_title                          = D('ZyVideo', 'classroom')->getVideoTitleById($vid);
        $this->pageTitle['unauditLesson'] = $v_title . '-课时审核';

        $id                  = intval($_POST['zy_video_section_id']);
        $title               = t($_POST['title']);
        $pid                 = intval($_POST['pid']);
        $this->pageKeyList   = array('id', 'title', 'ptitle', 'video_title', 'DOACTION');
        $this->pageButton[]  = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->searchKey     = array('zy_video_section_id', 'title');
        $this->pageButton[]  = array("title" => "通过", "onclick" => "admin.crossVideoSection('','$vid',1)");
        $this->pageButton[]  = array("title" => "驳回", "onclick" => "admin.crossVideoSection('','$vid',1,1)");
        $this->searchPostUrl = U('classroom/AdminVideo/unauditLesson', array('vid' => $vid, 'tabHash' => unauditLesson));
        //$this->pageButton[]  = array('title' => '查看课程', 'onclick' => "window.open('" . U('classroom/Video/view', array('id' => $vid, 'is_look' => 1)) . "')");

        $map['vid']         = $vid;
        $map['pid']         = ['gt', 0];
        $map['is_activity'] = 3;
        if (!empty($id)) {
            $map['zy_video_section_id'] = $id;
        }

        if (!empty($title)) {
            $map['title'] = array("like", "%$title%");
        }

        if (!empty($pid)) {
            $map['pid'] = $pid;
        }

        $listData = M('zy_video_section')->where($map)->order('sort asc,zy_video_section_id desc')->findPage(20);
        foreach ($listData['data'] as $key => $val) {
            $val['id']          = $val['zy_video_section_id'];
            $val['ptitle']      = M('zy_video_section')->where(array('zy_video_section_id' => $val['pid']))->getField('title');
            $val['video_title'] = D('ZyVideo')->getVideoTitleById($val['vid']);
            //$val['DOACTION']    = '<a href="' . U('classroom/Video/view', array('id' => $vid, 'is_look' => 1)) . '" target="_blank">预览</a>  | ';
            $val['DOACTION'] = '<a href="javascript:void(0);" onclick="admin.resourcesPreview(' . $val['cid'] . ');">预览</a> | ';
            $val['DOACTION'] .= '<a href="javascript:void();" onclick="admin.crossVideoSection(' . $val['id'] . ',' . $val['vid'] . ',2)">通过</a> | ';
            $val['DOACTION'] .= '<a href="javascript:void();" onclick="admin.crossVideoSection(' . $val['id'] . ',' . $val['vid'] . ',2,1)">驳回</a>';
            $listData['data'][$key] = $val;
        }
        $this->_listpk = 'id';
        $this->displayList($listData);
    }

    //获取课程数据
    private function _getData($limit = 20, $is_del = 0, $is_activity = 1, $mount)
    {
        $order = 'ctime desc,id desc';
        if (isset($_POST)) {
            $_POST['id'] && $map['id']                   = intval($_POST['id']);
            $_POST['teacher_id'] && $map['teacher_id']   = intval($_POST['teacher_id']);
            $_POST['video_title'] && $map['video_title'] = array('like', '%' . t($_POST['video_title']) . '%');
            $_POST['uid'] && $map['uid']                 = intval($_POST['uid']);
            if ($_POST['is_best'] == 1) {
                $map['is_best'] = 0;
            } else if ($_POST['is_best'] == 2) {
                $map['is_best'] = 1;
            }
            if ($_POST['is_charge'] == 1) {
                $map['is_charge'] = 0;
            } else if ($_POST['is_charge'] == 2) {
                $map['is_charge'] = 1;
            }
            if ($_POST['is_best_like'] == 1) {
                $map['is_best_like'] = 0;
            } else if ($_POST['is_best_like'] == 2) {
                $map['is_best_like'] = 1;
            }
            if ($_POST['is_cete_floor'] == 1) {
                $map['is_cete_floor'] = 0;
            } else if ($_POST['is_cete_floor'] == 2) {
                $map['is_cete_floor'] = 1;
            }
            if ($_POST['is_re_free'] == 1) {
                $map['is_re_free'] = 0;
            } else if ($_POST['is_re_free'] == 2) {
                $map['is_re_free'] = 1;
            }
            if ($_POST['quanzhong']) {
                $order = '';
                foreach ($_POST['quanzhong'] as $val) {
                    $order .= $val . ",";
                }
                $order = substr($order, 0, strlen($order) - 1);
            }
            if (!empty($_POST['endTime'][0]) && !empty($_POST['endTime'][1])) {
                // 时间区间条件
                $map['endTime'] = array('BETWEEN', array(strtotime($_POST['endTime'][0]),
                    strtotime($_POST['endTime'][1])));
            } else if (!empty($_POST['uctime'][0])) {
                // 时间大于条件
                $map['uctime'] = array('GT', strtotime($_POST['uctime'][0]));
            } elseif (!empty($_POST['uctime'][1])) {
                // 时间小于条件
                $map['uctime'] = array('LT', strtotime($_POST['uctime'][1]));
            }

        }
        if ($is_del == 1) {
            $map['is_del'] = $is_del; //搜索非隐藏内容
        } else {
            $map['is_del'] = 0;
        }
        $map['type'] = 1;
        //        if(isset($is_activity) && $is_activity != 3){
        //            $map['is_activity'] = $is_activity;
        //        }
        if (isset($is_activity)) {
            $map['is_activity'] = $is_activity;
        }

        $list = D('ZyVideo','classroom')->where($map)->order($order)->findPage($limit);
        foreach ($list['data'] as &$value) {
            $value['video_title'] = msubstr($value['video_title'], 0, 20);
            $value['video_title'] = getQuickLink(U('classroom/Video/view', ['id' => $value['id']]), $value['video_title'], '未知课程');
            $value['user_title']  = getUserSpace($value['uid'], null, '_blank');
            $value['activity']    = $value['is_activity'] == '1' ? '<span style="color:green">已审核</span>' : '<span style="color:red">未审核</span>';
            $value['best']        = $value['is_best'] == '1' ? '<span style="color:green">是</span>' : '<span style="color:red">否</span>';
            //处理讲师信息
            $teacher_name          = M('zy_teacher')->where(array('id' => $value['teacher_id']))->getField('name');
            $tuid                  = M('zy_teacher')->where(array('id' => $value['teacher_id']))->getField('uid');
            $value['teacher_name'] = getQuickLink(U('classroom/Teacher/view', ['id' => $value['teacher_id']]), $teacher_name, '未知讲师');

            $value['ctime'] = date("Y-m-d H:i:s", $value['ctime']);
            $value['cover'] = "<img src=" . getCover($value['cover'], 60, 60) . " width='60px' height='60px'>";
            if ($value['is_charge'] == 0) {
                $value['is_charge'] = "<p style='color: red;'>否</p>";
            } else if ($value['is_charge'] == 1) {
                $value['is_charge'] = "<p style='color: green;'>是</p>";
            }

            if ($value['is_activity'] == 1) {
                $value['DOACTION'] = '<a href=" ' . U('classroom/AdminVideo/lesson', array('vid' => $value['id'])) . ' ">课时管理</a> | ';
                $value['DOACTION'] .= '<a href=" ' . U('classroom/AdminVideo/learn', array('uid' => $value['uid'], 'id' => $value['id'])) . ' ">学习记录</a> | ';
                //获取章节
                $video_section_data = D('VideoSection')->setTable('zy_video_section')->getNetworkList(0, $value['id']);
                $s_id               = reset(reset($video_section_data)['child'])['id'] ?: 0;
                $value['DOACTION'] .= '<a target="_blank" href=" ' . U('classroom/Video/watch', array('id' => $value['id'], 's_id' => $s_id)) . ' ">查看</a> | ';
                $value['DOACTION'] .= '<a href="' . U('classroom/AdminVideo/askVideo', array('tabHash' => 'askVideo', 'id' => $value['id'])) . '">提问</a> | ';
                $value['DOACTION'] .= '<a href="' . U('classroom/AdminVideo/noteVideo', array('tabHash' => 'noteVideo', 'id' => $value['id'])) . '">笔记</a> | ';
                $value['DOACTION'] .= '<a href="' . U('classroom/AdminVideo/reviewVideo', array('tabHash' => 'reviewVideo', 'id' => $value['id'])) . '">评价</a> | ';
                $value['DOACTION'] .= '<a href="' . U('classroom/AdminVideo/addVideo', array('id' => $value['id'], 'tabHash' => 'editVideo')) . '">编辑</a> | ';
                $value['DOACTION'] .= '<a href="' . U('classroom/AdminCourseOrder/addCourseOrder', array('id' => $value['id'], 'tabHash' => 'editVideo')) . '">赠送</a> | ';

                $videotitle = t($value['video_title']);
                if ($value['is_del'] == 1) {
                    $value['DOACTION'] .= '<a onclick="admin.openObject(' . $value['id'] . ',\'Video\',' . $value['is_del'] . ',' . "'{$videotitle}'" . ',' . "'{$tuid}'" . ');" href="javascript:void(0)">启用</a> | ';
                } else if ($value['is_del'] == 0) {
                    $value['DOACTION'] .= '<a onclick="admin.closeObject(' . $value['id'] . ',\'Video\',' . $value['is_del'] . ',' . "'{$videotitle}'" . ',' . "'{$tuid}'" . ');" href="javascript:void(0)">禁用</a> |';
                }

                if ($value['listingtime'] > time() || $value['uctime'] < time()) {
                    $value['DOACTION'] .= '<a onclick="admin.putawayObject(' . $value['id'] . ',\'up\',' . "'{$videotitle}'" . ',' . "'{$tuid}'" . ',1);" href="javascript:void(0)">立即上架</a>';
                } else {
                    $value['DOACTION'] .= '<a onclick="admin.putawayObject(' . $value['id'] . ',\'down\',' . "'{$videotitle}'" . ',' . "'{$tuid}'" . ',2);" href="javascript:void(0)">立即下架</a>';
                }
            } else {
                $videotitle = t($value['video_title']);
//                $value['DOACTION'] .= '<a href="javascript:void();" onclick="admin.crossVideo('.$value['id'].',true,' ."'{$videotitle}'".',' ."'{$tuid}'".')">通过审核</a> | ';
                //                $value['DOACTION'] .= '<a href="javascript:void();" onclick="admin.crossVideo('.$value['id'].',false,' ."'{$videotitle}'".',' ."'{$tuid}'".')">驳回</a>';
                $value['DOACTION'] = '<a href=" ' . U('classroom/AdminVideo/unauditLesson', array('vid' => $value['id'], 'tabHash' => 'unauditLesson')) . ' ">课时审核</a> ';

            }

//            $value['DOACTION'] .=    '<a onclick="admin.closeObject('.$value['id'].',\'Video\','.$value['is_del'].');" href="javascript:void(0)"> 删除</a> ';
            //$value['DOACTION'] .=  ' | <a href="'.U('classroom/Video/view',array('id'=>$value['id'],'is_look'=>1)).'" target="_blank">查看课程</a> ';

        }
        return $list;
    }

    /**
     * 启用课程
     */
    public function openVideo()
    {
        $id = intval($_POST['id']);
        if (!$id) {
            $this->ajaxReturn(null, '参数错误', 0);
        }

        $map['id']      = $id;
        $data['is_del'] = 0;
        $video          = M('zy_video');
        $result         = $video->where($map)->save($data);
        if (!$result) {
            $this->ajaxReturn(null, '启用失败', 0);
            return;
        }

        $this->ajaxReturn(null, '启用成功', 1);
    }

    /**
     * 禁用课程
     */
    public function closeVideo()
    {
        $id = intval($_POST['id']);
        if (!$id) {
            $this->ajaxReturn(null, '参数错误', 0);
        }

        $map['id']      = $id;
        $data['is_del'] = 1;
        $video          = M('zy_video');
        $result         = $video->where($map)->save($data);
        if (!$result) {
            $this->ajaxReturn(null, '禁用失败', 0);
            return;
        }

        $this->ajaxReturn(null, '禁用失败', 1);
    }

    /**
     * 启用课程
     */
    public function putaway()
    {
        $id = intval($_POST['id']);
        if (!$id) {
            $this->ajaxReturn(null, '参数错误', 0);
        }

        $type      = t($_POST['type']);
        $map['id'] = $id;
        if ($type == 'up') {
            $info                = '上架';
            $data['listingtime'] = time() - 1;
            $data['uctime']      = time() + 86400 * 7;
        } else {
            $info           = '下架';
            $data['uctime'] = time() - 1;
        }
        $video  = M('zy_video');
        $result = $video->where($map)->save($data);
        if (!$result) {
            $this->ajaxReturn(null, $info . '失败', 0);
            return;
        }

        $this->ajaxReturn(null, $info . '成功', 1);
    }

    /**
     * 课程对应的提问
     */
    public function askVideo()
    {
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[]             = array('title' => '课程提问列表', 'tabHash' => 'askVideo', 'url' => U('classroom/AdminVideo/askVideo', array('id' => $_GET['id'])));
        $this->pageTitle['askVideo'] = '课程问题列表';
        if (!$_GET['id']) {
            $this->error('请选择要查看的课程');
        }

        $field             = 'id,uid,oid,qst_description,qst_comment_count';
        $this->pageKeyList = array('id', 'qst_description', 'uid', 'oid', 'qst_comment_count', 'DOACTION');
        $map['oid']        = intval($_GET['id']);
        $map['parent_id']  = 0; //父类id为0
        $map['type']       = 1;
        $data              = D('ZyQuestion', 'classroom')->getListForId($map, 20, $field);
        foreach ($data['data'] as $key => $vo) {
            $video_title                    = D('ZyVideo', 'classroom')->getVideoTitleById($vo['oid']);
            $url                            = U('classroom/Video/view', array('id' => $vo['oid']));
            $data['data'][$key]['oid']      = getQuickLink($url, $video_title, "未知课程");
            $data['data'][$key]['uid']      = getUserSpace($vo['uid'], null, '_blank');
            $data['data'][$key]['DOACTION'] = '<a href="' . U('classroom/AdminVideo/answerVideo', array('oid' => $vo['oid'], 'id' => $vo['id'], 'tabHash' => 'answerVideo')) . '">查看回答</a> | <a href="javascript:void();" onclick="admin.delContent(' . $vo['id'] . ',\'Video\',\'ask\')">删除(连带删除回答及回答的评论)</a>';
        }
        $this->displayList($data);
    }

    /**
     * 提问对应的回答
     */
    public function answerVideo()
    {
        if (!$_GET['id']) {
            $this->error('请选择要查看的问题');
        }

        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[]                = array('title' => '回答列表', 'tabHash' => 'answerVideo', 'url' => U('classroom/AdminVideo/answerVideo', array('oid' => $_GET['oid'], 'id' => $_GET['id'])));
        $this->pageTitle['answerVideo'] = '回答列表';
        $field                          = 'id,uid,oid,qst_description';
        $this->pageButton[]             = array('title' => '删除', 'onclick' => "admin.mzQuestionEdit('delquestion')");
        $this->pageKeyList              = array('id', 'uid', 'qst_description', 'oid', 'DOACTION');
        $map['parent_id']               = intval($_GET['id']); //父类id为问题id
        $map['oid']                     = intval($_GET['oid']);
        $map['type']                    = 1;
        $data                           = D('ZyQuestion', 'classroom')->getListForId($map, 20, $field);
        foreach ($data['data'] as $key => $vo) {
            $video_title                    = D('ZyVideo', 'classroom')->getVideoTitleById($vo['oid']);
            $url                            = U('classroom/Video/view', array('id' => $vo['oid']));
            $data['data'][$key]['oid']      = getQuickLink($url, $video_title, "未知课程");
            $data['data'][$key]['uid']      = getUserSpace($vo['uid'], null, '_blank');
            $data['data'][$key]['DOACTION'] = ' <a href="javascript:void();" onclick="admin.delContent(' . $vo['id'] . ',\'Video\',\'ask\')">删除</a>';
        }
        $this->displayList($data);
    }

    /**
     * 对回答的评论
     */
    public function commentVideo()
    {
        if (!$_GET['id']) {
            $this->error('请选择要查看的回答');
        }

        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $field                           = 'id,uid,oid,qst_title';
        $this->pageTab[]                 = array('title' => '评论列表', 'tabHash' => 'commentVideo', 'url' => U('classroom/AdminVideo/commentVideo'));
        $this->pageTitle['commentVideo'] = '评论列表';
        $this->pageKeyList               = array('id', 'qst_title', 'uid', 'oid', 'DOACTION');
        $map['parent_id']                = intval($_GET['id']); //父类id为问题id
        $map['oid']                      = intval($_GET['oid']);
        $map['type']                     = 1;
        $data                            = D('ZyQuestion', 'classroom')->getListForId($map, 20, $field);
        foreach ($data['data'] as $key => $vo) {
            $data['data'][$key]['oid']      = D('ZyVideo', 'classroom')->getVideoTitleById($vo['oid']);
            $data['data'][$key]['uid']      = getUserName($vo['uid']);
            $data['data'][$key]['DOACTION'] = '<a href="javascript:void();" onclick="admin.delContent(' . $vo['id'] . ',\'Video\',\'ask\')">删除</a>';
        }
        $this->displayList($data);
    }

    /******************************************提问结束，笔记开始 ************/

    /**
     * 课程对应的笔记
     */
    public function noteVideo()
    {
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[]              = array('title' => '课程笔记列表', 'tabHash' => 'noteVideo', 'url' => U('classroom/AdminVideo/noteVideo', array('id' => $_GET['id'])));
        $this->pageTitle['noteVideo'] = '课程笔记列表';
        if (!$_GET['id']) {
            $this->error('请选择要查看的课程');
        }

        $field             = 'id,uid,oid,note_title,note_comment_count';
        $this->pageKeyList = array('id', 'note_title', 'uid', 'oid', 'note_comment_count', 'DOACTION');
        $map['oid']        = intval($_GET['id']);
        $map['parent_id']  = 0; //父类id为0
        $map['type']       = 1;
        $data              = D('ZyNote', 'classroom')->getListForId($map, 20, $field);
        foreach ($data['data'] as $key => $vo) {
            $video_title                    = D('ZyVideo', 'classroom')->getVideoTitleById($vo['oid']);
            $url                            = U('classroom/Video/view', array('id' => $vo['oid']));
            $data['data'][$key]['oid']      = getQuickLink($url, $video_title, "未知课程");
            $data['data'][$key]['uid']      = getUserSpace($vo['uid'], null, '_blank');
            $data['data'][$key]['DOACTION'] = '<a href="' . U('classroom/AdminVideo/noteCommentVideo', array('oid' => $vo['oid'], 'id' => $vo['id'], 'tabHash' => 'noteCommentVideo')) . '">查看评论</a> | <a href="javascript:void();" onclick="admin.delContent(' . $vo['id'] . ',\'Video\',\'note\')">删除(连带删除回答及回答的评论)</a>';
        }
        $this->displayList($data);
    }

    /**
     * 笔记对应的评论
     */
    public function noteCommentVideo()
    {
        if (!$_GET['id']) {
            $this->error('请选择要查看的评论');
        }

        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[]                     = array('title' => '评论列表', 'tabHash' => 'noteCommentVideo', 'url' => U('classroom/AdminVideo/noteCommentVideo', array('oid' => $_GET['oid'], 'type' => $_GET['type'], 'id' => $_GET['id'])));
        $this->pageTitle['noteCommentVideo'] = '评论列表';
        $this->pageButton[]                  = array('title' => '删除', 'onclick' => "admin.delNoteAllEdit('delnote')");
        $field                               = 'id,uid,oid,note_title,note_comment_count';
        $this->pageKeyList                   = array('id', 'uid', 'note_description', 'type', 'oid', 'DOACTION');
        $map['parent_id']                    = intval($_GET['id']); //父类id为问题id
        $map['oid']                          = intval($_GET['oid']);

        $data = M('zy_note')->where($map)->findpage(20);
        foreach ($data['data'] as $key => $vo) {
            if ($vo['type'] == 1) {
                $vo['type'] = "课程";
            }
            $video_title                    = D('ZyVideo', 'classroom')->getVideoTitleById($vo['oid']);
            $url                            = U('classroom/Video/view', array('id' => $vo['oid']));
            $data['data'][$key]['oid']      = getQuickLink($url, $video_title, "未知课程");
            $data['data'][$key]['uid']      = getUserSpace($vo['uid'], null, '_blank');
            $data['data'][$key]['DOACTION'] = ' <a href="javascript:void();" onclick="admin.delContent(' . $vo['id'] . ',\'Video\',\'note\')">删除</a>';
        }
        $this->displayList($data);
    }

    /**
     * 对笔记评论的回复
     */
    public function noteReplayVideo()
    {
        if (!$_GET['id']) {
            $this->error('请选择要查看的评论');
        }

        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $field                           = 'id,uid,oid,note_title';
        $this->pageTab[]                 = array('title' => '回复列表', 'tabHash' => 'noteReplayVideo', 'url' => U('classroom/AdminVideo/commentVideo'));
        $this->pageTitle['commentVideo'] = '回复列表';
        $this->pageKeyList               = array('id', 'note_title', 'uid', 'oid', 'DOACTION');
        $map['parent_id']                = intval($_GET['id']); //父类id为问题id
        $map['oid']                      = intval($_GET['oid']);
        $map['type']                     = 1;
        $data                            = D('ZyNote', 'classroom')->getListForId($map, 20, $field);
        foreach ($data['data'] as $key => $vo) {
            $data['data'][$key]['oid']      = D('ZyVideo', 'classroom')->getVideoTitleById($vo['oid']);
            $data['data'][$key]['uid']      = getUserName($vo['uid']);
            $data['data'][$key]['DOACTION'] = '<a href="javascript:void();" onclick="admin.delContent(' . $vo['id'] . ',\'Video\',\'note\')">删除</a>';
        }
        $this->displayList($data);
    }

    /*******************************************笔记操作结束,评论开始******************/
    /**
     * 课程对应的评价
     */
    public function reviewVideo()
    {
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[]                = array('title' => '课程评价列表', 'tabHash' => 'reviewVideo', 'url' => U('classroom/AdminVideo/reviewVideo', array('id' => $_GET['id'])));
        $this->pageTitle['reviewVideo'] = '课程评价列表';
        if (!$_GET['id']) {
            $this->error('请选择要查看的评价');
        }

        $field             = 'id,uid,oid,review_description,star,review_comment_count';
        $this->pageKeyList = array('id', 'review_description', 'uid', 'oid', 'star', 'review_comment_count', 'DOACTION');
        $map['oid']        = intval($_GET['id']);
        $map['parent_id']  = 0; //父类id为0
        $map['type']       = 1;
        $data              = D('ZyReview', 'classroom')->getListForId($map, 20, $field);
        foreach ($data['data'] as $key => $vo) {
            $video_title                    = D('ZyVideo', 'classroom')->getVideoTitleById($vo['oid']);
            $url                            = U('classroom/Video/view', array('id' => $vo['oid']));
            $data['data'][$key]['oid']      = getQuickLink($url, $video_title, "未知课程");
            $data['data'][$key]['uid']      = getUserSpace($vo['uid'], null, '_blank');
            $data['data'][$key]['DOACTION'] = '<a href="' . U('classroom/AdminVideo/reviewCommentVideo', array('oid' => $vo['oid'], 'id' => $vo['id'], 'tabHash' => 'reviewCommentVideo')) . '">查看评论</a> | <a href="javascript:void();" onclick="admin.delContent(' . $vo['id'] . ',\'Video\',\'review\')">删除(连带删除回复)</a>';
            $data['data'][$key]['start']    = $vo['start'] / 20;
        }
        $this->displayList($data);
    }

    /**
     * 评价对应的回复
     */
    public function reviewCommentVideo()
    {
        if (!$_GET['id']) {
            $this->error('请选择要查看的评论');
        }

        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[]                       = array('title' => '评论列表', 'tabHash' => 'reviewCommentVideo', 'url' => U('classroom/AdminVideo/reviewCommentVideo', array('oid' => $_GET['oid'], 'id' => $_GET['id'])));
        $this->pageButton[]                    = array('title' => '删除', 'onclick' => "admin.delReviewAll('delReview')");
        $this->pageTitle['reviewCommentVideo'] = '评论列表';
        $field                                 = 'id,uid,oid,review_description';
        $this->pageKeyList                     = array('id', 'uid', 'review_description', 'oid', 'DOACTION');
        $map['parent_id']                      = intval($_GET['id']); //父类id为问题id
        $map['oid']                            = intval($_GET['oid']);
        $map['type']                           = 1;
        $data                                  = D('ZyReview', 'classroom')->getListForId($map, 20, $field);
        foreach ($data['data'] as $key => $vo) {
            $video_title                    = D('ZyVideo', 'classroom')->getVideoTitleById($vo['oid']);
            $url                            = U('classroom/Video/view', array('id' => $vo['oid']));
            $data['data'][$key]['oid']      = getQuickLink($url, $video_title, "未知课程");
            $data['data'][$key]['uid']      = getUserSpace($vo['uid'], null, '_blank');
            $data['data'][$key]['DOACTION'] = "<a href=javascript:admin.reviewhuifu(" . $vo['id'] . ",'delreviewhuifu');>删除回复</a>";
        }
        $this->displayList($data);
    }

    //删除问答回复
    public function delreviewhuifu()
    {
        $ids = implode(",", $_POST['ids']);

        $ids = trim(t($ids), ",");
        if ($ids == "") {
            $ids = intval($_POST['ids']);
        }
        $msg   = array();
        $where = array(
            'id' => array('in', $ids),
        );
        $res = M('zy_review')->where($where)->delete();
        if ($res !== false) {
            $msg['data']   = "刪除成功！";
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data'] = "删除失败!";
            echo json_encode($msg);
        }
    }
    //****************************评论结束***********************//
    /**
     * 删除提问、回答、评论
     *
     */
    public function delProperty()
    {
        if (!$_POST['id']) {
            exit(json_encode(array('status' => 0, 'info' => '错误的参数')));
        }

        if (!$_POST['property'] || !in_array($_POST['property'], array('ask', 'note', 'review'))) {
            exit(json_encode(array('status' => 0, 'info' => '参数错误')));
        }

        if ($_POST['property'] == 'ask') {
            $result = D('ZyQuestion', 'classroom')->doDeleteQuestion(intval($_POST['id']));
        } else if ($_POST['property'] == 'note') {
            $result = D('ZyNote', 'classroom')->doDeleteNote(intval($_POST['id']));
        } else if ($_POST['property']) {
            $result = D('ZyReview', 'classroom')->doDeleteReview(intval($_POST['id']));
        }
        if ($result['status'] == 1) {
            exit(json_encode(array('status' => 1, 'info' => '删除成功')));
        } else {
            exit(json_encode(array('status' => 0, 'info' => '删除失败，请稍后再试')));
        }
    }

    /**
     * 审核课程
     */
    public function crossVideo()
    {
        if (!$_POST['id']) {
            exit(json_encode(array('status' => 0, 'info' => '错误的参数')));
        }

        $map['id']           = intval($_POST['id']);
        $data['is_activity'] = $_POST['cross'] == 'true' ? 1 : 0; //0为未通过状态
        $video               = M('zy_video')->where($map)->getField('is_school');
        if ($data['is_activity'] == 0 && $video == 1) {
            $data['is_activity'] = 4;
        }
        $video = M('zy_video')->where($map)->getField('is_activity');
        if ($video == 7) {
            $data['is_activity'] = 1;
        }
        $data['utime'] = time();
        $data['atime'] = time();
        $res           = M('zy_video')->where($map)->data($data)->save();
        if ($res) {
            $video_info = M('zy_video')->where($map)->field('uid,video_title,is_activity')->find();

            if ($video_info['is_activity'] == 0) {
                $message['title'] = "课程审核被驳回";
                $message['body']  = "您好，您上传的课程 {$video_info['video_title']} 已被平台驳回，请修改信息后重新提交审核。";
            } else if ($video_info['is_activity'] == 1) {
                $message['title'] = "课程审核通过";
                $message['body']  = "您好，您上传的课程 {$video_info['video_title']} 已审核通过。";
            }
            if ($_POST['cross'] == 'true') {
                $sec_data['is_activity'] = 1;
            } else {
                $sec_data['is_activity'] = 2;
            }
            M('zy_video_section')->where(array('vid' => $map['id'], 'pid' => ['neq', 0], 'is_activity' => 0))->data($sec_data)->save();
            $message['uid']   = $video_info['uid'];
            $message['ctime'] = time();
            model('Notify')->sendMessage($message);

            $credit = M('credit_setting')->where(array('id' => 38, 'is_open' => 1))->field('id,name,score,count')->find();
            if ($credit['score'] > 0) {
                $ctype = 6;
                $note  = '上传课程获得的积分';
            }
            model('Credit')->addUserCreditRule($video_info['uid'], $ctype, $credit['id'], $credit['name'], $credit['score'], $credit['count'], $note);

            exit(json_encode(array('status' => 1, 'info' => '操作成功')));
        } else {
            exit(json_encode(array('status' => 0, 'info' => '操作失败')));
        }
    }

    /**
     * 课程后台管理菜单
     * @return void
     */
    private function _initClassroomListAdminMenu()
    {
        $this->pageTab[] = array('title' => '已审', 'tabHash' => 'index', 'url' => U('classroom/AdminVideo/index'));
        $this->pageTab[] = array('title' => '待审', 'tabHash' => 'unauditList', 'url' => U('classroom/AdminVideo/unauditList'));
//        $this->pageTab[] = array('title'=>'机构挂载课程列表','tabHash'=>'mount','url'=>U('classroom/AdminVideo/mount'));
        //$this->pageTab[] = array('title'=>'前台投稿待审课程列表','tabHash'=>'forwordUnauditList','url'=>U('classroom/AdminVideo/forwordUnauditList'));
        $this->pageTab[] = array('title' => '回收站', 'tabHash' => 'recycle', 'url' => U('classroom/AdminVideo/recycle'));
        $this->pageTab[] = array('title' => '添加', 'tabHash' => 'addVideo', 'url' => U('classroom/AdminVideo/addVideo'));
        $this->pageTab[] = array('title' => '视频库', 'tabHash' => 'videoLib', 'url' => U('classroom/AdminVideo/videoLib'));
        $this->pageTab[] = array('title' => 'CC待同步视频库', 'tabHash' => 'videoLibVerify', 'url' => U('classroom/AdminVideo/videoLibVerify'));
    }

    /**
     * 课程后台的标题
     */
    private function _initClassroomListAdminTitle()
    {
        $this->pageTitle['index'] = '已审';
        // $this->pageTitle['forwordUnauditList'] = '前台投稿待审课程列表';
        // $this->pageTitle['mount'] = '机构挂载课程';
        $this->pageTitle['unauditList']    = '待审';
        $this->pageTitle['recycle']        = '回收站';
        $this->pageTitle['addVideo']       = '添加';
        $this->pageTitle['videoLib']       = '视频库';
        $this->pageTitle['videoLibVerify'] = 'CC待同步视频库';
    }

    /**
     * 删除笔记
     * @return void
     */
    public function delcourse()
    {
        $ids = $_POST['ids'];
        $res = M('zy_video')->where('id =' . $ids)->delete();
        if ($res !== false) {
            $msg['data']   = "操作成功";
            $msg['status'] = 1;

        } else {
            $msg['data']   = '操作错误';
            $msg['status'] = 0;
        }
        echo json_encode($msg);
        exit();

    }

    public function video_examine()
    {
        $msg = array();
        $id  = implode(",", $_POST['ids']);
        $id  = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['ids']);
        }
        $msg   = array();
        $where = array(
            'id' => array('in', $id),
        );
        $is_del = M('zy_video')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('zy_video')->where($where)->save($data);

        if ($res !== false) {
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data']   = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }

    public function test()
    {
        //将云存储空间里的视频移入回收站内（特定的分类）,以防没有删掉掉的视频
        $upurl                = $this->cc_video_config['cc_apiurl'] . 'video/update?';
        $up_map['videoid']    = urlencode(t($_POST['cc_videoid']));
        $up_map['userid']     = urlencode($this->cc_video_config['cc_userid']);
        $up_map['categoryid'] = urlencode($this->cc_video_config['cc_delid']);
        $up_map['format']     = urlencode('json');
        $upurl                = $upurl . createVideoHashedQueryString($up_map)[1] . '&time=' . time() . '&hash=' . createVideoHashedQueryString($up_map)[0];

        $up_res = getDataByUrl($upurl);
        if (in_array("error", $up_res)) {
            $this->mzError("出错了");
        }
        dump($up_res);
    }
    /**
     * cc删除上传到第三方的视频
     */
    public function delCCVideo()
    {
        if (!$_POST['cc_videoid']) {
            $this->mzError("请选择取消上传的文件");
        }
        $data['uid']             = $this->mid;
        $data['video_file_name'] = $vmap['video_file_name'] = t($_POST['videofilename']);
        $data['out_title']       = t($_POST['out_title']);
        $data['out_id']          = $vmap['out_id']          = t($_POST['cc_videoid']);
        $data['ctime']           = time();

        $oid = M('zy_video_data_out')->where($vmap)->getField('id');
        if ($oid) {
            $id = M('zy_video_data_out')->where('id=' . $oid)->save($data);
        } else {
            $id = M('zy_video_data_out')->add($data);
        }

        if ($id) {
            $url                  = $this->cc_video_config['cc_apiurl'] . 'video/delete?';
            $query_map['videoid'] = urlencode(t($_POST['cc_videoid']));
            $query_map['userid']  = urlencode($this->cc_video_config['cc_userid']);
            $query_map['format']  = urlencode('json');
            $url                  = $url . createVideoHashedQueryString($query_map)[1] . '&time=' . time() . '&hash=' . createVideoHashedQueryString($query_map)[0];

            $res = getDataByUrl($url);
            if ($res["result"] == 'OK') {
                M('zy_video_data_out')->where('id=' . $id)->delete();
                $this->mzSuccess("操作成功");
            } else {
                $this->mzError("操作失败，或许服务器有延迟!");
            }
        } else {
            $this->mzError("操作失败，或许服务器有延迟");
        }

    }

    /**
     * 挂载课程
     */
    public function openMount()
    {
        $id = intval($_POST['id']);
        if (!$id) {
            $this->ajaxReturn(null, '参数错误', 0);
        }

        $map['id']        = $id;
        $data['is_mount'] = 1;
        $data['atime']    = time();
        $video            = M('zy_video');
        $result           = $video->where($map)->save($data);
        if (!$result) {
            $this->ajaxReturn(null, '挂载失败', 0);
            return;
        }

        $this->ajaxReturn(null, '挂载成功', 1);
    }

    /**
     * 取消挂载课程
     */
    public function closeMount()
    {
        $id = intval($_POST['id']);
        if (!$id) {
            $this->ajaxReturn(null, '参数错误', 0);
        }

        $map['id']        = $id;
        $data['is_mount'] = 0;
        $video            = M('zy_video');
        $result           = $video->where($map)->save($data);
        if (!$result) {
            $this->ajaxReturn(null, '取消挂载失败', 0);
            return;
        }

        $this->ajaxReturn(null, '取消挂载成功', 1);
    }
    /**
     * 审核课时
     */
    public function crossVideoSection()
    {
        if (!$_POST['ids']) {
            exit(json_encode(array('status' => 0, 'info' => '错误的参数')));
        }
        $videomap['zy_video_section_id'] = ['in', $_POST['ids']];
        if ($_POST['ctype'] == 1) {
            $data['is_activity'] = 0;
        } else {
            $data['is_activity'] = 1;
        }
        $res = M('zy_video_section')->where($videomap)->data($data)->save();
        if ($res) {
            $map['vid']         = $_POST['vid'];
            $map['pid']         = ['gt', 0];
            $map['is_activity'] = 3;
            $count              = M('zy_video_section')->where($map)->count();
        } else {
            $this->ajaxReturn('系统繁忙，稍后再试');
        }
        if ($count == 0) {
            $sectionmap['vid']         = $_POST['vid'];
            $sectionmap['pid']         = ['gt', 0];
            $sectionmap['is_activity'] = 1;
            $section_count             = M('zy_video_section')->where($sectionmap)->count();
            $activity                  = M('zy_video')->where(array('id' => $_POST['vid']))->getField('is_activity');
            if ($activity != 1) {
                if ($section_count > 0) {
                    $videoData['is_activity'] = 1;
                } else {
                    $videoData['is_activity'] = 0;
                }
            }
            $videoData['utime'] = time();
            $videoData['atime'] = time();
            M('zy_video')->where(array('id' => $_POST['vid']))->data($videoData)->save();
            if ($videoData['is_activity'] == 0) {
                $uid                = M('zy_video')->where(array('id' => $_POST['vid']))->getField('uid');
                $message['title']   = "课程审核被驳回";
                $message['content'] = "你好，你上传的课程已被机构驳回，请修改信息后重新提交审核。";
                $message['to']      = $uid;
                model('Message')->postMessage($message, $this->mid);
            }
        }
        /**
        if($count > 0){
        $videoData['is_activity'] = 1;
        }else{
        $videoData['is_activity'] = 0;
        }
        $videoData['utime'] = time();
        M('zy_video')->where(array('id'=>$_POST['vid']))->data($videoData)->save();
        if ($videoData['is_activity'] == 0) {
        $uid                = M('zy_video')->where(array('id'=>$_POST['vid']))->getField('uid');
        $message['title']   = "课程审核被驳回";
        $message['content'] = "你好，你上传的课程已被机构驳回，请修改信息后重新提交审核。";
        $message['to']      = $uid;
        model('Message')->postMessage($message, $this->mid);
        }
         **/
        if ($_POST['ctype'] == 1) {
            $this->ajaxReturn('驳回成功');
        } else {
            $this->ajaxReturn('审核成功');
        }
    }

    /**
     * 更改七牛上传Token
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-15
     * @return   [type]                         [description]
     */
    public function changeQiniuUptoken()
    {
        if (isAjax() && $_POST) {
            $type      = intval($_POST['type']);
            $qiniuConf = model('Xdata')->get('classroom_AdminConfig:qiniuyun');
            $auth      = new QiniuAuth($qiniuConf['qiniu_AccessKey'], $qiniuConf['qiniu_SecretKey']);
            //生成上传凭证
            $bucket   = $qiniuConf['qiniu_Bucket'];
            $filename = "{$this->site['site_keyword']}" . rand(5, 8) . time();
            // 类型区分
            if ($type == 1) {
                $pattern = \Qiniu\base64_urlSafeEncode('ts_' . $filename . '.m3u8_$(count)');
                $saveas  = \Qiniu\base64_urlSafeEncode("{$bucket}:{$filename}.m3u8");
                $hlsKey  = C('QINIU_TS_KEY');
                if (!$hlsKey) {
                    // 写入默认的加密key
                    $config                 = include CONF_PATH . '/config.inc.php';
                    $config['QINIU_TS_KEY'] = $hlsKey = 'eduline201701010';
                    file_put_contents(CONF_PATH . '/config.inc.php', ("<?php \r\n return " . var_export($config, true) . "; \r\n ?>"));
                }
                $hlsKeyUrl = \Qiniu\base64_urlSafeEncode(SITE_URL . '/qiniu/getVideoKey');
                $hlsKey    = \Qiniu\base64_urlSafeEncode($hlsKey);
                // /hlsKeyType/1.0
                // 处理命令参数
                $fops = 'avthumb/m3u8/noDomain/1/segtime/10/ab/160k/ar/44100/acodec/libfaac/r/30/vb/5400k/vcodec/libx264/s/1920x1080/stripmeta/0/pattern/' . $pattern . '/hlsKey/' . $hlsKey . '/hlsKeyUrl/' . $hlsKeyUrl;
                //$fops = 'avthumb/m3u8/noDomain/1/segtime/10/ab/160k/ar/44100/acodec/libfaac/r/30/vb/5400k/vcodec/libx264/s/1920x1080/stripmeta/0/pattern/' . $pattern;
                $is_open = getAppConfig('water_open', 'basic');
                if ($is_open == 1) {
                    // 是否设置了水印
                    $water_image = getAppConfig('water_image', 'basic');
                    if ($water_image) {
                        // 图片是否存在
                        $water_file = getAttachUrlByAttachId($water_image);
                        if ($water_file) {
                            $fops .= '/wmImage/' . \Qiniu\base64_urlSafeEncode($water_file);
                            // 水印位置
                            $water_postion = getAppConfig('water_postion', 'basic') ?: 'NorthWest';
                            $fops .= '/wmGravity/' . $water_postion;
                        }

                    }
                }
                $policy = array(
                    'persistentOps'       => $fops . '|saveas/' . $saveas,
                    'persistentPipeline'  => $qiniuConf['qiniu_Pipeline'], // 获取转码队列名称
                    'persistentNotifyUrl' => SITE_URL . '/qiniu/persistent/pipelineToHLS', // 回调通知
                );
            } else if ($type == 2) {
                // 音频处理
                $saveas = \Qiniu\base64_urlSafeEncode("{$bucket}:{$filename}.mp3");
                $fops   = 'avthumb/mp3';
                $policy = array(
                    'persistentOps'       => $fops . '|saveas/' . $saveas,
                    'persistentPipeline'  => $qiniuConf['qiniu_Pipeline'], // 获取转码队列名称
                    'persistentNotifyUrl' => SITE_URL . '/qiniu/persistent/pipelineToHLS', // 回调通知
                );
            }
            $upToken = $auth->uploadToken($bucket, $filename, 3600, $policy);
            echo json_encode(['status' => 1, 'data' => ['upToken' => $upToken, 'filename' => $filename]]);
        }
    }
}
