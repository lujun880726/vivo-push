<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-06-27
 * Time: 16:58
 */

/*
 * 没有对接VIVO的单推接口，直接使用多推的流程实现
 */


require_once '../vendor/autoload.php';

// 然后可以这样使用。
$title = '推送的消息标题';
$message = '需要推送的消息内容';
$appid = '10004';
$appKey = '25509283-3767-4b9e-83fe-b6e55ac6243e';
$appSecret = '25509283-3767-4b9e-83fe-b6e55ac6243e';
$AccessTokenArr = ['1231312321', '123123123'];
$clientCustomMap = ['a' => 'b', 'c' => 'd'];

$push = new \VivoPush\VivoPush($appid, $appKey, $appSecret, './abc.log');
$push->setTitle($title)
    ->setcontent($message)
    ->setregId($AccessTokenArr)
    ->setclientCustomMap($clientCustomMap);

var_dump($push->sendMessage()); // 执行推送消息。
