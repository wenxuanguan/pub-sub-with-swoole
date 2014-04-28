<?
include_once 'key.php';

$redis=new Redis();
$redis->connect('127.0.0.1',6379);
$fd_channel=array();

$serv = new swoole_server("0.0.0.0", 9501);
$serv->set(array(
	'worker_num' => 2,
	'open_eof_check'  => 1,
	'package_eof'   => "\r\n",
	'package_max_length' => 1024 * 16,
	'daemonize' => 0
));
$serv->on('timer', function($serv, $interval) {
});
$serv->on('workerStart', function($serv, $worker_id) {
});
$serv->on('connect', function ($serv, $fd, $from_id){
});
$serv->on('receive', function ($serv, $fd, $from_id, $iot_data) {
	echo $iot_data;
	$iot_data=str_replace("\r\n","",$iot_data);
	$err=check_key($iot_data);
	if(!$err)
	{
		$serv->send($fd,"res=0&desc=key or uid error\r\n");
		return;
	}

	global $redis;
	global $fd_channel;
	parse_str($iot_data);
	if($cmd=="subscribe")
	{
		$redis->sAdd($channel,$fd);
		$fd_channel[$fd][]=$channel;
		$ret="cmd=$cmd&res=1&desc=success subscribe channel $channel\r\n";
	}
	if($cmd=="publish")
	{
		$cha=$redis->sMembers($channel);
		$data="cmd=$cmd&data=$data\r\n";
		foreach($cha as $row)
		{
			$serv->send($row,$data);
		}
		$ret="cmd=$cmd&res=1&desc=success\r\n";
	}

	$serv->send($fd, $ret);
	
});
$serv->on('close', function ($serv, $fd, $from_id) {
		$cha='';
		global $fd_channel;
		global $redis;
		foreach($fd_channel[$fd] as $row)
		{
			$redis->sRem($row,$fd);
			$cha.=$row.",";
		}
		unset($fd_channel[$fd]);
		$cha=rtrim($cha,",");
		$ret="subscribed channel $cha are lost\r\n";
		error_log($ret,3,"/tmp/close_channel.log");

});
$serv->start();

?>
