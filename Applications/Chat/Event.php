<?php

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\Db;
use \Model\MessageModel;

class Event
{

    /**
     * 有消息时
     * @param int $client_id
     * @param mixed $message
     */
    private static $db = null;

    //event::start
    public static function onWorkerStart()
    {
        self::$db = Db::instance('user');
    }

    /*
     * 数据结构
     * type,to_id,from_id,username,avator,data(content,time)
     */

    public static function onMessage($client_id, $message)
    {
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:" . json_encode($_SESSION) . " onMessage:" . $message . "\n";
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        $message_type = $message_data['type'];
        switch ($message_type)
        {
            case 'INIT':
                $uid = $message_data['uid'];
                $_SESSION['uid'] = $uid;
                $_SESSION['username'] = $message_data['username'];
                Gateway::bindUid($client_id, $uid);
                break;
            case 'CHATMESSAGE':
                $uid = $_SESSION['uid'];
                $date = date('Y-m-d H:i:s', time());
                $to_id = $message_data['uid'];
                $data = [
                    'from_id' => $uid,
                    'to_id' => $to_id,
                    'content' => htmlspecialchars($message_data['content']),
                    'date' => $date,
                ];
                var_dump($data);
                self::$db->insert('message', $data);
                $toArr = [];
                $toArr[] = $uid;
                //判断是否在线                       
                if (Gateway::isUidOnline($to_id))
                {
                    $toArr[] = $to_id;
                } else
                {
                    $d['from_id'] = $where['from_id'] = $uid;
                    $d['to_id'] = $where['to_id'] = $to_id;
                    $d['type'] = 1;
                    $d['date'] = $date;
                    $d['is_have_message'] = 1;
                    self::$db->replace('message_record', $d, $where);
                }
                $chat_message = [
                    'to_id' => $message_data['to_id'],
                    'from_id' => $uid,
                    'username' => $message_data['username'],
                    'type' => 'CHATMESSAGE',
                    'data' => [
                        'content' => $message_data['content'],
                        'date' => $date,
                    ]
                ];
                Gateway::sendToUid($toArr, json_encode($chat_message));
        }
    }

    /**
     * 当客户端断开连接时
     * @param integer $client_id 客户端id
     */
    public static function onClose($client_id)
    {
        
    }

}
