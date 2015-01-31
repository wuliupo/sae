<?php
session_start();
$redis = new Redis() ;
while(!$redis->connect(REDIS_HOST,REDIS_PORT))
{
	echo iconv('UTF-8','GBK','redis 连接错误，正在尝试从新连接。').PHP_EOL ;
	sleep(2) ;
}

$redis->select(APP_NUMBER) ;
header("Content-type:image/png");
echo $redis->get($_REQUEST['key']) ;

$redis->close() ;
?>