<?php

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

/**
 * 聊天主逻辑
 * 主要是处理 onMessage onClose 
 */
//$insert_id = $db1->insert('Persons')->cols(array('Firstname'=>'abc', 'Lastname'=>'efg', 'Sex'=>'M', 'Age'=>13))->query();

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
    static $db = null;
    static $msgObj = null;

    //event::start
    public static function onWorkerStart()
    {
        self::$db = Db::instance('user');
    }

    public static function onMessage($client_id, $message)
    {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:" . json_encode($_SESSION) . " onMessage:" . $message . "\n";
        self::$msgObj = new MessageModel(self::$db);
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        $message_type = $message_data['type'];
        switch ($message_type)
        {
            case 'init':
                $uid = $message_data['id'];
                $_SESSION['uid'] = $uid;
                $_SESSION['username'] = $message_data['username'];
                Gateway::bindUid($client_id, $uid);
                //获取离线的信息
                $map['to_id'] = $uid;
                $map['is_have_message'] = 1;
                $message_record = self::$msgObj->getMessageRecord($map);
                if ($message_record)
                {
                    foreach ($message_record as $key => $vo)
                    {
                        $condition = 'to_id=' . $uid . ' AND time>="' . $vo['date'] . '"';
                        $message_rst = self::$db->select('*')->from('message')->where($condition)->query();
                        foreach ($message_rst as $v)
                        {
                            $data = array(
                                'username' => $v['from_name'],
                                'id' => $v['to_id'],
                                'type' => 'friend',
                                'content' => $v['content'],
                                'timestamp' => $v['time'],
                            );
                            $chat_message = array(
                                'message_type' => 'chatMessage',
                                'data' => $data,
                            );

                            Gateway::sendToUid($uid, json_encode($chat_message));
                        }

                        self::$db->update('message_record')->cols(array('is_have_message' => 0))->where('id=' . $key)->query();
                    }
                }
                break;
            case 'chatMessage':
                $uid = $_SESSION['uid'];
                $to_id = $message_data['data']['to']['id'];
                $type = $message_data['data']['to']['type'];
                $date = date('Y-m-d H:i:s', time());
                $content = htmlspecialchars($message_data['data']['mine']['content']);
                $chat_message = array(
                    'message_type' => 'chatMessage',
                    'data' => array(
                        'username' => $_SESSION['username'],
                        'avatar' => $_SESSION['avatar'],
                        'id' => $to_id,
                        'type' => $type,
                        'content' => $content,
                        'timestamp' => $date,
                    )
                );
                switch ($type)
                {
                    case 'friend':
                        $data = array(
                            'from_id' => $uid,
                            'from_name' => $_SESSION['username'],
                            'to_id' => $to_id,
                            'to_name' => '',
                            'content' => $content,
                            'time' => $date,
                        );
                        $toArr = array();
                        $toArr[] = $uid;
                        self::$db->insert('message')->cols($data)->query();
                        //判断是否在线                       
                        if (Gateway::isUidOnline($to_id))
                        {
                            $toArr[] = $to_id;
                        } else
                        {
                            $d['from_id'] = $where['from_id'] = $uid;
                            $d['to_id'] = $where['to_id'] = $to_id;
                            $d['type'] = 'friend';
                            $d['date'] = $date;
                            $d['is_have_message'] = 1;
                            self::$msgObj->WritemessageRecord($d, $where);
                        }
                        Gateway::sendToUid($toArr, json_encode($chat_message));
                }
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
