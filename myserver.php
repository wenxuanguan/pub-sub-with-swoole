<?php

include_once 'key.php';

$config = array(
	'worker_num' => 1,
	'open_eof_check' => true,
	'package_eof' => "\r\n",
	'heartbeat_check_interval' => 60,
	'heartbeat_idle_time' => 600,
	'daemonize' => 1,
	'log_file' => '/var/log/swoole.log',
);

$sess_list=array();
$kv=array();

$serv = new swoole_server("0.0.0.0", 9502);
$serv->set($config);

$serv->on('connect', function ($serv, $fd){
	echo "Client {$fd} Connected.\n";
});
          
function my_receive($serv, $fd, $from_id, $data)
{
	echo "Receive data:{$data}";

	$err=check_key($data);
	if(!$err)
	{
		$serv->send($fd,"res=0&desc=key or uid error\r\n");
		return;
	}

	global $sess_list,$kv;

	parse_str($data);
	if($cmd=="keep")
	{
		$res="cmd={$cmd}&res=1\r\n";
	}   
	if($cmd=="subscribe")
	{
        	$sess_list[$topic][$fd]=$fd;
	        $kv[$fd]=$topic;
		$res="cmd={$cmd}&res=1&&desc=success subscribe channel {$channel}\r\n";
	}
	if($cmd=="publish")
	{
		$sub_list=$sess_list[$topic];
		foreach($sub_list as $conn)
		{
			$serv->send($conn, "cmd={$cmd}&content={$message}\r\n");
		}
		$res="cmd={$cmd}&res=1\r\n";
	}
	if($cmd=="subscribe_num")
	{
		$num=count($sess_list[$topic]);
	        $res="{$num}\r\n";
	}
	
	$serv->send($fd,$res);
}

function my_close($serv, $fd)
{
	global $sess_list,$kv;
	echo "close=$fd\r\n";

	$k=$kv[$fd];
	if($k)
	{
		unset($sess_list[$k][$fd]);
	}
}
	$serv->on('receive', 'my_receive');
	$serv->on('close', 'my_close');

	$serv->start();
?>
