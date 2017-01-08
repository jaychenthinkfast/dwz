<?php
/**
 * Created by PhpStorm.
 * User: chenjie
 */

ini_set('display_errors', 1);
require 'vendor/autoload.php';
require('config.php');

$serv = new swoole_http_server("127.0.0.1", 9501);

$serv->set([
    'worker_num' => 1,
    'daemonize' => 0
]);
$serv->on('Start' , function(){
    swoole_set_process_name('dwz_master');
});

$serv->on('ManagerStart' , function(){
    swoole_set_process_name('dwz_manager');
});

$serv->on('WorkerStart' , function(){
    swoole_set_process_name('dwz_worker');

    (spl_autoload_register(function($class){
        $baseClasspath = \str_replace('\\', DIRECTORY_SEPARATOR , $class) . '.php';

        $classpath = __DIR__ . '/' . $baseClasspath;
        if (is_file($classpath)) {
            require "{$classpath}";
            return;
        }
    }));

});

$serv->on('Request', function($request, $response) {

    $path_info = explode('/',$request->server['path_info']);
    if( isset($path_info[1]) && !empty($path_info[1])) {  // ctrl
        $ctrl = 'ctrl\\' . $path_info[1];
    } else {
        $ctrl = 'ctrl\\Index';
    }
    if( isset($path_info[2] ) ) {  // method
        $action = $path_info[2];
    } else {
        $action = 'index';
    }

    $result = 'Ctrl not found';
    if( class_exists($ctrl) ) {

        $class = new $ctrl();

        $result = 'Action not found';

        if( method_exists($class, $action) ) {
            $result = $class->$action($request, $response);
        }

    }
    if($result == 'Ctrl not found'){
        $bash_url = $request->header['host'];
        $response->status(301);
        $response->header('Location', 'http://'.$bash_url.'/Index/redirect?url='.$path_info[1]);
    }
    $response->end($result);
});

$serv->start();
