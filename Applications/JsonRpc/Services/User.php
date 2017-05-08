<?php

namespace Applications\JsonRpc\Services;

class User extends Base
{
    /*
     * 查询条件
     * gender
     * age
     * edu
     */
    public function getMemberList($param)
    {
        if ($param['gender'])
        {
            $where['gender'] = (int) $param['gender'];
        }
        if ($param['age'])
        {
            $where['age'] = $param['age'];
        }
        $result = self::$db->select('members', '*', $where);
        var_dump($result);
        return $result;
    }

}
