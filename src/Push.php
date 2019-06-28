<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-06-27
 * Time: 16:59
 */

namespace VivoPush;

use VivoPush\Http\Http;
use VivoPush\Http\Request;
use VivoPush\Http\Response;


class VivoPush
{

    /**
     * 构造函数。
     *
     * @param array $config
     * @throws \Exception
     */
    public function __construct($appId, $appKey, $appSecret, $logFile)
    {
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->logFile = $logFile;
        $this->AccessToken = '';
        $this->regId = [];
        $this->notifyType = 4;
        $this->title = '';
        $this->message = '';
        $this->timeToLive = 60 * 60 * 6;
        $this->skipType = 3;
        $this->skipContent = '';
        $this->networkType = -1;
        $this->clientCustomMap = [];
        $this->extra = [];
        $this->_http = new Request();
        $this->_http->setHttpVersion(Http::HTTP_VERSION_1_1);
        $this->url = 'https://api-push.vivo.com.cn';
    }


    /**
     * 获取AccessToken信息
     * @return mixed
     * @throws \Exception
     */
    public function getAccessToken()
    {
        $sendData = [
            'appId' => $this->appId,
            'appKey' => $this->appKey,
            'timestamp' => time() . '000',
        ];
        $sign = md5($sendData['appId'] . $sendData['appKey'] . $sendData['timestamp'] . $this->appSecret);
        $sendData['sign'] = $sign;
        $url = $this->url . '/message/auth';
        $data = $this->getDataByInfo($url, $sendData);
        if (is_array($data) && isset($data['authToken']) && $data['authToken']) {
            $this->AccessToken = $data['authToken'];
        }
        return $this;
    }

    /**
     * 批量推送用户接口
     * @return mixed|string
     * @throws \Exception
     */
    public function sendMessage()
    {
        $this->saveListPayload();
        if (!$this->taskId) {
            file_put_contents($this->logFile, json_encode(['errot' => 'VIVO推送必须要设置taskId','dateTime' => date("Y-m-d H:i:s")]) . "\r\n", FILE_APPEND);
            throw new \Exception("VIVO推送必须要设置taskId");
        }
        $sendArr = [
            'regIds' => $this->regId,
            'taskId' => $this->taskId,
            'requestId' => $this->randCode(),
        ];
        $url = $this->url . '/message/pushToList';
        $data = $this->getDataByInfo($url, $sendArr, $this->AccessToken);
        return $data;
    }

    /*
     * 保存群推消息公共体接口
     */
    public function saveListPayload()
    {
        $this->check();
        $url = $this->url . '/message/saveListPayload';
        $sendArr = [
            'requestId' => $this->randCode(),
//            'regId' => implode(',', $this->regId),
            'title' => $this->title,
            'content' => $this->content,
            'notifyType' => $this->notifyType,
            'timeToLive' => $this->timeToLive,
            'skipType' => $this->skipType,
            'skipContent' => $this->skipContent,
            'networkType' => $this->networkType,
            'clientCustomMap' => $this->clientCustomMap,
            'extra' => $this->extra,
        ];
        $data = $this->getDataByInfo($url, $sendArr, $this->AccessToken);
        if (is_array($data) && isset($data['taskId']) && $data['taskId']) {
            $this->taskId = $data['taskId'];
        }
        return $data;
    }

    /**
     * 设置消息标题
     * @param string $title
     * @return $this
     */
    public function setTitle($title = "")
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置消息内容
     * @param string $message
     * @return $this
     */
    public function setcontent($content = "")
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 设置要推送的用户token
     * @param string $deviceToken
     * @return $this
     */
    public function setregId($deviceToken = [])
    {
        $this->regId = $deviceToken;
        return $this;
    }

    /**
     * //通知类型 1:无， 2:响铃， 3:振动， 4:响铃和振动
     * @param $notifyType
     * @return int
     */
    public function setnotifyType($notifyType = 4)
    {
        $this->notifyType = $notifyType;
        return $this;
    }

    /**
     * 消息保留时长 单位： 秒， 取值至少 60 秒， 最 长 7 天。 当值为空时， 默认一天
     * @param int $timeToLive
     * @return $this
     */
    public function settimeToLive($timeToLive = 4)
    {
        $this->timeToLive = $timeToLive;
        return $this;
    }

    /**
     * 点击跳转类型 1：打开 APP 首页 2：打开链 接 3：自定义 4:打开 app 内指定页面
     * @param $skipType
     * @return $this
     */
    public function setskipType($skipType = 3)
    {
        $this->skipType = $skipType;
        return $this;
    }

    /**
     * 跳转内容 跳转类型为 2 时， 跳转内容最大1000 个字符，跳转类型为 3 或 4 时， 跳转内 容最大 1024 个字符
     * @param string $skipContent
     */
    public function setskipContent($skipContent = '')
    {
        $this->skipContent = $skipContent;
        return $this;
    }

    /**
     * 网络方式 -1：网络方式 -1：不限， 1： wifi 下发送，不填 默认为-1
     * @param $networkType
     * @return $this
     */
    public function setnetworkType($networkType = -1)
    {
        $this->networkType = $networkType;
        return $this;
    }

    /**
     * 客户端自定义键值对 自定义 key 和 Value 键
     * 值对个数不能超过 10 个，且长度不能超过
     * 1024 字符, key 和 Value 键值对总长度不能
     * 超过 1024 字符。
     * @param array $clientCustomMap
     * @return $this
     */
    public function setclientCustomMap($clientCustomMap = [])
    {
        $this->clientCustomMap = $clientCustomMap;
        return $this;
    }

    /*
     * 高级特性（详见： 高级特性 extra）
     * 属性名字 类型 是否必填 Y/N 描述
        callback String Y 第三方接收回执的 http 接口，最大长度
        128 个字符
        callback.param String N 第三方自定义回执参数，最大长度 64 个
        字符
     */
    public function setextra($extra = [])
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     * 参数检查
     * @throws \Exception
     */
    private function check()
    {
        if ($this->AccessToken) {
            $this->getAccessToken();
        }
        if (!$this->appId) {
            file_put_contents($this->logFile, json_encode(['errot' => 'VIVO推送必须要设置appId','dateTime' => date("Y-m-d H:i:s")]) . "\r\n", FILE_APPEND);
            throw new \Exception("VIVO推送必须要设置appId");
        }
        if (!$this->appKey) {
            file_put_contents($this->logFile, json_encode(['errot' => 'VIVO推送必须要设置appKey','dateTime' => date("Y-m-d H:i:s")]) . "\r\n", FILE_APPEND);
            throw new \Exception("VIVO推送必须要设置appKey");
        }
        if (!$this->appSecret) {
            file_put_contents($this->logFile, json_encode(['errot' => 'VIVO推送必须要设置appSecret','dateTime' => date("Y-m-d H:i:s")]) . "\r\n", FILE_APPEND);
            throw new \Exception("VIVO推送必须要设置appSecret");
        }
        if (!$this->AccessToken) {
            file_put_contents($this->logFile, json_encode(['errot' => 'VIVO推送必须要设置AccessToken','dateTime' => date("Y-m-d H:i:s")]) . "\r\n", FILE_APPEND);
            throw new \Exception("VIVO推送必须要设置AccessToken");
        }
        if (!$this->title) {
            file_put_contents($this->logFile, json_encode(['errot' => 'VIVO推送必须要设置title','dateTime' => date("Y-m-d H:i:s")]) . "\r\n", FILE_APPEND);
            throw new \Exception("VIVO推送必须要设置title");
        }
        if (!$this->content) {
            file_put_contents($this->logFile, json_encode(['errot' => 'VIVO推送必须要设置content','dateTime' => date("Y-m-d H:i:s")]) . "\r\n", FILE_APPEND);
            throw new \Exception("VIVO推送必须要设置content");
        }
        if (!$this->regId) {
            file_put_contents($this->logFile, json_encode(['errot' => 'VIVO推送必须要设置regId','dateTime' => date("Y-m-d H:i:s")]) . "\r\n", FILE_APPEND);
            throw new \Exception("VIVO推送必须要设置regId");
        }
        if (!$this->timeToLive) {
            file_put_contents($this->logFile, json_encode(['errot' => 'VIVO推送必须要设置timeToLive','dateTime' => date("Y-m-d H:i:s")]) . "\r\n", FILE_APPEND);
            throw new \Exception("VIVO推送必须要设置timeToLive");
        }
        return $this;
    }

    /**
     * @param $url
     * @param $sendData
     * @return mixed|string
     */
    public function getDataByInfo($url, $sendData, $authToken = '')
    {
        $option = [
            'data' => json_encode($sendData),
            'headers' => [
                "Content-Type" => "application/json",
            ]
        ];
        if ($authToken) {
            $option['headers']['authToken'] = $authToken;
        }
        try {
            $response = $this->_http->post($url, $option);
            $logRes = $res = $response->getResponseArray();
        } catch (\Exception $e) {
            $logRes = $e->getMessage();
            $res = '';
        }
        $option['data'] = $sendData;
        file_put_contents($this->logFile, json_encode(['url' => $url, 'option' => $option, 'res' => $logRes, 'dateTime' => date("Y-m-d H:i:s")]) . "\r\n", FILE_APPEND);
        return $res;
    }

    /**
     * 生成随机字符串
     * @param int $length 要生成的随机字符串长度
     * @param string $type 随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母；4，特殊字符；-1，数字+大小写字母+特殊字符
     * @return string
     */
    public function randCode($length = 32, $type = 0)
    {
        $arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
        if ($type == 0) {
            array_pop($arr);
            $string = implode("", $arr);
        } elseif ($type == "-1") {
            $string = implode("", $arr);
        } else {
            $string = $arr[$type];
        }
        $count = strlen($string) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $string[rand(0, $count)];
        }
        return $code;
    }
}