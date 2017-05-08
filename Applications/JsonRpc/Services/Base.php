<?php

namespace Applications\JsonRpc\Services;

use \GatewayWorker\Lib\Db;

class Base
{

    static $db = null;

    public function __construct()
    {
         self::$db = Db::instance('user');
    }
    

}
