<?php
/**
 * Created by PhpStorm.
 * User: chenjie
 */


//DB配置
define('DB_TYPE', 'mysql');
define('DB_NAME', 'short_url');
define('DB_USER', 'root');
define('DB_PASSWORD', 'devop');
define('DB_HOST', 'localhost');
define('DB_TABLE', 'shortenedurls');

//是否统计访问次数
define('TRACK', TRUE);

//是否检查长网址可访问
define('CHECK_URL', TRUE);

//hashids的可选字符串集
define('ALLOWED_CHARS', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

//是否缓存
define('CACHE', TRUE);

//缓存文件夹目录
define('CACHE_DIR', dirname(__FILE__) . '/cache/');
