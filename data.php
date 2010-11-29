<?php
include "config.php";

$cs = mysql_connect($dbhost, $dbuser, $dbpass ) or die ( 'Can not connect to server' );

mysql_select_db ( $dbname, $cs ) or die ( 'Can not select database' );

$sql = "SELECT * FROM stats_outboundstats";
$r = mysql_query ( $sql, $cs ) or die ( 'Query Error' );



$permalink = str_replace($dynstring, "", $_SERVER["REQUEST_URI"]);
$permalink = str_replace("/","",$permalink);

while($row = mysql_fetch_array($r))
	{
	if($permalink === $row['GOTO']){
		$gourl = $row['URL'];
		
		if($row['PARENT'] > 0){
		$sql = "INSERT INTO stats_linkstats (DATUM,OWNER,SLUG) VALUES ('" .date("Y-m-d") ."','" .$row['PARENT'] ."','" .$row['GOTO'] ."')";
		
		}else{
		$sql = "INSERT INTO stats_linkstats (DATUM,OWNER,SLUG) VALUES ('" .date("Y-m-d") ."','" .$row['ID'] ."','" .$row['GOTO'] ."')";
		
		}
		
		$q = mysql_query ( $sql, $cs ) or die ( 'Query Error2' );
		
	}
	
  }

header("HTTP/1.1 301 Moved Permanently"); 
header('Location: '.$gourl.'');
mysql_close($cs);

?>