<?php
/**
 * Qiniu 处理控制器
 */
use GuzzleHttp\Client;
use Qiniu\Auth as QiniuAuth;
use Qiniu\Config as QiniuConfig;
use Qiniu\Storage\BucketManager as QiniuBucketManager;

class QiniuAction extends Action
{
    /**
     * 处理转码为HLS流视频回调
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-14
     * @return   [type]                                [description]
     */
    public function persistentNotifyUrlForHLS()
    {
        $qiniuConf = model('Xdata')->get('classroom_AdminConfig:qiniuyun');
        $qiniuauth = new QiniuAuth($qiniuConf['qiniu_AccessKey'], $qiniuConf['qiniu_SecretKey']);
        $_body     = file_get_contents('php://input');
        $pipelineInfo = json_decode($_body, true);
        if ($pipelineInfo && $pipelineInfo['code'] == 0) {
            // 自动检测是否为HTTPS访问
            $host = IS_HTTPS ? 'https://' : "http://";
            // 获取配置的访问域名
            $domain      = $qiniuConf['qiniu_Domain'];
            $url         = $host . $domain . '/' . $pipelineInfo['inputKey'] . '?avinfo';
            $fileInfoUrl = $qiniuauth->privateDownloadUrl($url, 120);
            // 初始化请求类
            $client = new Client([
                'headers' => [
                    'Referer' => SITE_URL,
                ],
            ]);
            // 获取元文件信息
            $res = $client->get($fileInfoUrl);
            $res = $res->getBody()->getContents();
            if ($res) {
                $avinfo   = json_decode($res, true);
                // 更改视频信息
                $saveData = [
                    'transcoding_status' => 1,
                    'videokey'           => $pipelineInfo['items'][0]['key'],
                    'filesize'           => $avinfo['format']['size'],
                    'duration'           => $this->secToTime($avinfo['format']['duration']),
                ];
                // 查询是否存在
                if(M('zy_video_data')->where(['videokey' => $pipelineInfo['inputKey']])->count() < 1){
                    // 存储数据
                    $transcoding_data = [
                        'transcoding_file_key'=>$pipelineInfo['inputKey'],
                        'transcoding_info' => json_encode($saveData)
                    ];
                    $saveStatus = M('transcoding')->add($transcoding_data);
                }else{
                    
                    $saveStatus = M('zy_video_data')->where(['videokey' => $pipelineInfo['inputKey']])->save($saveData);
                    
                }

                if ($saveStatus) {
                    // 删除元视频
                    $qiniuConfig   = new QiniuConfig();
                    $bucketManager = new QiniuBucketManager($qiniuauth, $qiniuConfig);
                    $bucketManager->delete($pipelineInfo['inputBucket'], $pipelineInfo['inputKey']);
                    exit;
                }
                
            }
        }
        // 其他情况 等待处理,正在处理
        if($pipelineInfo && in_array($pipelineInfo['code'],[1,2])){
            exit;
        }
        if(!M('zy_video_data')->where(['videokey' => $pipelineInfo['inputKey']])->save(['transcoding_status'=>0])){
            // 存储数据
            $transcoding_data = [
                'transcoding_file_key'=>$pipelineInfo['inputKey'],
                'transcoding_info' => json_encode(['transcoding_status'=>0])
            ];
            M('transcoding')->add($transcoding_data);
        }
        // 删除元视频
        $qiniuConfig   = new QiniuConfig();
        $bucketManager = new QiniuBucketManager($qiniuauth, $qiniuConfig);
        $bucketManager->delete($pipelineInfo['inputBucket'], $pipelineInfo['inputKey']);
    }

    /**
     * 把秒数转换为时分秒的格式
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-15
     * @param    [type]                         $times [description]
     * @return   [type]                                [description]
     */
    public function secToTime($seconds)
    {
    	$seconds = ceil($seconds);
        if ($seconds > 3600) {
            $hours   = intval($seconds / 3600);
            $minutes = $seconds % 3600;
            $time    = $hours . ":" . gmstrftime('%M:%S', $minutes);
        } else {
            $time = gmstrftime('%H:%M:%S', $seconds);
        }
        return $time;
    }

    /**
     * 获取视频加密的key
     * @Author   MartinSun<syh@sunyonghong.com>
     * @DateTime 2017-12-15
     * @return   [type]                         [description]
     */
    public function getVideoKey()
    {
        $key = C('QINIU_TS_KEY');
		if(!$key){
			// 写入默认的加密key
			$config = include CONF_PATH.'/config.inc.php';
			$config['QINIU_TS_KEY'] = $key = 'eduline201701010';
			file_put_contents(CONF_PATH.'/config.inc.php', ("<?php \r\n return " . var_export($config, true) . "; \r\n ?>") );
		}
    	// 判断是否为七牛服务器
    	if(stripos($_SERVER['HTTP_USER_AGENT'],'qiniu') !== false){
    		echo $key;exit;
    	}
        
    	// 判断是否为手机访问
    	if(is_mobile() || isiOS()){
    		echo $key;exit;
    	}

    	// 检测是否在本站内打开
    	preg_match('/http(s)?:\/\/(.*)\.(.*\..*)/',SITE_URL,$matchs);
        if(stripos($_SERVER['HTTP_REFERER'],'.'.$matchs[3]) !== false) {
            echo $key;exit;
        }

    	// 响应403状态
    	http_response_code(403);exit;
    }

}
