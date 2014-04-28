<?
$value="/and/";
$pattern_and_or = "/.*(\s|\t|\/)(or|and|union|load_file)(\s|\t|\/).*/i";
#$pattern_and_or = "/.*(or|and|union|load_file|select|update|insert).*/i";
if( preg_match( $pattern_and_or,$value )===  1){


echo "error";

}

echo urlencode("\t");
echo urldecode("%09");
?>
