#!/usr/bin/php -q
<?php
//error_reporting(E_ALL);
//error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ERROR);

include '_functions.php';
include '_config.php';


//Connect to TeamSpeak Server via ServerQuery 
$status=tsconnect($TSserver,$TSport,$TSuser,$TSpass);
if ($status!="OK")
{
	echo "Error: ".$status.chr(10);
	sleep(10);
	die();
}  
$response=tscmd("use sid=1",true);
$clid=tscmd("clientfind pattern=$TSuser",true);
$clid=explode(" ",$clid);
$clid=$clid[0];
$response=tscmd("clientmove $clid $TScid",true);
$response=tscmd("servernotifyregister event=textchannel",true);


//Connect to MySQL URL Database
$conn = new mysqli($DBserver, $DBuser, $DBpassword, $DBname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error .chr(10));
} 

//Every 10 seconds, check chat message for URLS and Log to DB
while(true){
	sleep(10);
	$chattext=tscmd("",true);
	$chattext = explode(chr(13),$chattext);
	
	//get the chat messages
	foreach($chattext as $chatline) {
		$chatline = explode(" ",$chatline);
		if(isset($chatline[2])) {
			$URLs=explode("[URL]",$chatline[2]);
			$remove=array_shift($URLs);
			
			//Parse Each URL in the text messages
			foreach($URLs as $URL) {
				$date = date('m/d/Y h:i:s a', time());
				$URL = substr($URL,0,strpos($URL,"[\/URL]"));
				$URL = str_replace("\/","/",$URL);
				$URLtitle =  get_title($URL);
				$user=str_replace("invokername=","",$chatline[4]);
				print $date." ".$user.": ".$URLtitle." | ".$URL.chr(10);	
				
				//insert URLs into the DB
				$sql = "INSERTY!!!!!";
				if ($conn->query($sql) === TRUE) {
					echo "New record created successfully".chr(10);
				} else {
					echo "Error: " . $sql . chr(10) . $conn->error . chr(10);
				}
			}
		}
		
	}
}

//Close Database Connection, but this should be done when script is killed and wont actually get called
$conn->close();	
?>