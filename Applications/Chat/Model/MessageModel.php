<?php

namespace Model;

class MessageModel
{

    private $db = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    //处理获取的mssage_record信息
    public function getMessageRecord($data)
    {
        $rst = $this->db->select('*')->from('message_record')->where($data)->query();
        $message_record = array();
        foreach ($rst as $v)
        {
            $message_record[$v['id']] = $v;
        }
        return empty($message_record) ? false : $message_record;
    }

    //判断是否已经插入
    public function WritemessageRecord($data, $where)
    {
        $rst = $this->db->select('count(*) as c')->from('message_record')->where($where)->query();
        if ($rst)
        {
            $rst = $this->db->update('message_record')->cols($data)->query();
        } else
        {
            $rst = $this->db->insert('message_record')->cols($d)->query();
        }
        return $rst;
    }

}
