<?php
function sensor_parse_str($str)
{
        $t=explode("&",$str);
        foreach($t as $item){
                list($key,$val)=explode("=",$item);
//			var_dump($ar,$ar[$key]);
                if(!isset($ar[$key]) ){
                        $ar[$key]=$val;
                }else{
                        if(is_array($ar[$key])){
                                $ar[$key][]=$val;
                        }else{
                                $tmp=array();
                                $tmp[]=$ar[$key];
                                $tmp[]=$val;
                                $ar[$key]=$tmp;
                        }

                }
        }
	return $ar;
}


?>
