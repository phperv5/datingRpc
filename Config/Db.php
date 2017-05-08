<?php

namespace Config;

/**
 * mysql配置
 * @author walkor
 */
class Db
{

    public static $user = array(
        'database_type' => 'mysql',
        'database_name' => 'dating',
        'server' => '127.0.0.1',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8',
        // 可选参数
        'port' => 3306,
        // 可选，定义表的前缀
        'prefix' => 'dt_',
    );

}
