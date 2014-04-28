<?php
require_once('memcached-client.php');
require_once('dbMysql.php');

function sql_injection($content) 
{ if (!get_magic_quotes_gpc()) { if (is_array($content)) { foreach ($content as $key=>$value) { $content[$key] = addslashes($value); } } else { addslashes($content); } } return $content; }



function  sql_replace($content){


foreach ($content as $key=>$value) {
$value=preg_replace("/select|update|inser|0x|#/i","andy",$value); 
$content[$key] =$value;


}

return $content;

}


  $dbhost="127.0.0.1:3306";
  $dbuser="root";
  $dbpassword="hongxia";
  $dbname="cduino";
$memcached_host="127.0.0.1:11211";

function  db_insert($sql)
{
    global  $dbhost,$dbuser,$dbpassword,$dbname;

    $db=new db_Mysql();
    $db->init_con($dbhost,$dbuser,$dbpassword,$dbname);
    $db->create_con();
    $ret=$db->query($sql);
    if ($ret==false){
        $db->free();
        $db->close();
        return false;
    }else{
        $id=$db->get_insert_id();
        $db->free();
        $db->close();
        return $id?$id:false;
    }
}


function  db_delete($sql)
{
    global  $dbhost,$dbuser,$dbpassword,$dbname;

    $db=new db_Mysql();
    $db->init_con($dbhost,$dbuser,$dbpassword,$dbname);
    $db->create_con();
    $ret=$db->query($sql);
    if ($ret==false){
        $db->free();
        $db->close();
        return false;
    }else{
        $db->free();
        $db->close();
        return true;
    }

}


function  db_update($sql)
{
    global  $dbhost,$dbuser,$dbpassword,$dbname;

    $db=new db_Mysql();
    $db->init_con($dbhost,$dbuser,$dbpassword,$dbname);
    $db->create_con();
    $ret=$db->query($sql);
    if ($ret==false){
        $db->free();
        $db->close();
        return false;
    }else{
        $db->free();
        $db->close();
        return true;
    }

}

function   db_query_mysql($sql,&$res,$timeout=0,$type='0',$key=null)
{
#echo "MC:".($type)."#<br>";

    global  $dbhost,$dbuser,$dbpassword,$dbname;
    $db=new db_Mysql();
    $db->init_con($dbhost,$dbuser,$dbpassword,$dbname);
    $db->create_con();
    $result=$db->query($sql);
    if ($result==false){
        $db->free();
        $db->close();
        return false;
    }else{


        $res=array();
        while ($row = mysql_fetch_array($result, MYSQL_BOTH)){
            $res[]=$row;
        }

        global $memcached_host;
        $options = array(
                       'servers' => array($memcached_host),
                       'debug' => false,
                       'compress_threshold' => 510240,
                       'persistant' => false
                   );
        $mc = new memcached($options);

        if ($type=="1"){//add type
            $mc->add($key,$res,$timeout);
        }
        if ($type=="2"){//replace
            $mc->replace($key,$res,$timeout);
        }
        $mc->disconnect_all();

    }
    $db->free();
    $db->close();
    return true;
}
function  db_query($sql,&$res,$timeout,$reload)
{

    global $memcached_host;
    $options = array(
                   'servers' => array($memcached_host),
                   'debug' => false,
                   'compress_threshold' => 510240,
                   'persistant' => false
               );
    if ($timeout<11){
        return db_query_mysql($sql,$res,$timeout,"0");
    }
    $mc = new memcached($options);
    $key=md5($sql);
    $res = $mc->get($key);
    $mc->disconnect_all();

    if (!$res ){
        return db_query_mysql($sql,$res,$timeout,"1",$key);//添加memcached

    }else{
//	echo "cached<br>";
        if ($reload){
            return db_query_mysql($sql,$res,$timeout,"2",$key);//替换memcached
        }
    }
    return true;
}


function send_execute_sql($sql,&$res,$timeout=10,$reload=false)
{

    
	$pattern_and_or = "/.*(\s|\t|\/|\+|\()(or|and|union|load_file|select|insert|update)(\s|\t|\/|\+).*/i";
	$pattern_v = "/(\W)(union|load_file|select|insert).*/i";
	$r_str=date("Y-m-d H:i:s")." ".$sql;
#$pattern_and_or = "/.*(or|and|union|load_file|select|insert).*/i";	
    	error_log($r_str."\r\n",3,"/tmp/exec_sql.log");
/*    if(count($_GET)>0)
    {
        foreach ($_GET as  $value)
        {
                        if(is_array($value))
                        {
                                continue;
                        }

                if( preg_match( $pattern_and_or,$value )===  1 || preg_match( $pattern_v,$value )===  1)
                {
                        echo "Forbidden Query $value";
    			error_log($r_str."\r\n".print_r($_GET,true),3,"/tmp/sql.log");
			error_log(date("Y-m-d H:i:s")."\r\n".print_r($_GET,true).print_r($_SERVER,true),3,"/tmp/ip.log");
			 $ip=$_SERVER['REMOTE_ADDR'];
                        #error_log("iptables -I INPUT -s $ip -j DROP\r\n",3,"/tmp/iptables.log");
			error_log("$ip\r\n",3,"/tmp/iptables.log");
			error_log(date("Y-m-d H:i:s\r\n")."$ip\r\n",3,"/tmp/jing.log");
			
                        exit;
                }
        }
    }
    if(count($_POST)>0)
    {
        foreach ($_POST as $value)
        {
                        if(is_array($value))
                        {
                                continue;
                        }

                if( preg_match( $pattern_and_or,$value ) === 1 || preg_match( $pattern_v,$value )===  1 )
                {
                        echo "Forbidden Query $value";
    			error_log($r_str."\r\n".print_r($_POST,true),3,"/tmp/sql.log");
			error_log(date("Y-m-d H:i:s")."\r\n".print_r($_POST,true).print_r($_SERVER,true),3,"/tmp/ip.log");
			$ip=$_SERVER['REMOTE_ADDR'];
			error_log(date("Y-m-d H:i:s")."$ip\r\n",3,"/tmp/jing.log");
                        #error_log("iptables -I INPUT -s $ip -j DROP\r\n",3,"/tmp/iptables.log");
			error_log("$ip\r\n",3,"/tmp/iptables.log");
			exit;
                }
        }
    }*/

    $cmd=substr($sql,0,7);
    $cmd=strtolower($cmd);

    if (strstr($cmd,"select")){
        return db_query($sql,$res,$timeout,$reload);
    }
    if (strstr($cmd,"insert")){
        return db_insert($sql);

    }
    if (strstr($cmd,"delete")){
        return db_delete($sql);

    }
    if (strstr($cmd,"update")){
        return db_update($sql,$res);

    }

    return false;

}

?>
