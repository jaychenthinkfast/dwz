# 基于Swoole HttpServer的短网址服务


##程序启动
``` bash
composer install //安装项目依赖
php dwz.php  //启动主程序（dwz.php中daemonize选项设置为1程序将转入后台作为守护进程运行）
```

##功能使用
``` bash
http://v2.dwz.wf //短网址生成页
http://v2.dwz.wf/Index/shorten?longurl=http://chenjie.info //短网址生成接口
http://v2.dwz.wf/mO //短网址跳转 
```

##默认配置
``` bash
//DB配置（根据实际修改）
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
``` 

##数据库SQL
``` bash
CREATE TABLE IF NOT EXISTS `shortenedurls` (
  `id` int(10) unsigned NOT NULL,
  `long_url` varchar(255) NOT NULL,
  `created` int(10) unsigned NOT NULL,
  `creator` char(15) NOT NULL,
  `referrals` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `shortenedurls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `long` (`long_url`),
  ADD KEY `referrals` (`referrals`);
``` 

##NGINX参考配置
``` bash
server
    {
        listen 80;
        server_name v2.dwz.wf;
        proxy_set_header Host $http_host;  
        index index.html index.htm index.php default.html default.htm default.php;
        root  /data/wwwroot/v2.dwz.wf;
        location / {
            proxy_pass http://127.0.0.1:9501;
        }
    }

``` 

##热重启
``` bash
ps aux | grep dwz_master | awk  '{print $2}' | xargs kill -USR1
``` 

## 反馈与建议
- QQ：916402586
- 邮箱：<chenjie@chenjie.info>
