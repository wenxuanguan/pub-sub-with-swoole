<?

require_once 'db/db.php';

function get_key($uid)
{
	$key='';
	$sql="select * from user where uid='$uid'";
	$err=send_execute_sql($sql,$res);
	if(count($res))
	{
		$row=$res[0];
		$ps=$row['passwd'];
		$key=md5($uid.$ps);
	}
	else
	{
		return false;
	}
	return $key;
}

function check_key($str)
{
	$err=false;
	$ar=parse_str($str);
	$key=$ar['key'];
	$uid=$ar['uid'];
	$k=get_key($uid);
	if($k==$key)
	{
		$err=true;
	}
	return $err;
}
?>
