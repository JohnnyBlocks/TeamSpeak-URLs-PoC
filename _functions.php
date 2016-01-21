<?php
//All of this code was found on the web!!

//http://stackoverflow.com/questions/4348912/get-title-of-website-via-link
function get_title($url){
  $str = file_get_contents($url);
  if(strlen($str)>0){
    $str = trim(preg_replace('/\s+/', ' ', $str)); // supports line breaks inside <title>
    preg_match("/\<title\>(.*)\<\/title\>/i",$str,$title); // ignore case
    return $title[1];
  }
}

//http://forum.teamspeak.com/threads/52556-PHP-Telnet-function
$teamspeakhandle=false;
function tsconnect($ip,$port,$user,$password)
{
	global $teamspeakhandle;
	$teamspeakhandle=fsockopen($ip,$port);
	stream_set_timeout($teamspeakhandle,0,100000);
	if ($teamspeakhandle)
	{
		tscmd("",true);
		$result=tscmd("login ".$user." ".$password."");
		if ($result["status"]["id"]=="0")
		{
			return "OK";
		}
		else
		{
			return $result["status"]["msg"];
		}
	}
	else
	{
		return "Socket connection failed!";
	}
}
function tsunescape($text) 
{ 
	$text=str_replace(chr(92).chr(92) ,chr(92) ,$text);
	$text=str_replace(chr(92).chr(47) ,chr(47) ,$text);
	$text=str_replace(chr(92).chr(115),chr(32) ,$text);
	$text=str_replace(chr(92).chr(112),chr(124),$text);
	$text=str_replace(chr(92).chr(97) ,chr(7)  ,$text);
	$text=str_replace(chr(92).chr(98) ,chr(8)  ,$text);
	$text=str_replace(chr(92).chr(102),chr(12) ,$text);
	$text=str_replace(chr(92).chr(110),chr(10) ,$text);
	$text=str_replace(chr(92).chr(114),chr(13) ,$text);
	$text=str_replace(chr(92).chr(116),chr(9)  ,$text);
	$text=str_replace(chr(92).chr(118),chr(11) ,$text);
	$text=utf8_decode($text);
	return $text;
}
function tsescape($text)
{
	$text=str_replace(chr(92) ,chr(92).chr(92) ,$text);
	$text=str_replace(chr(47) ,chr(92).chr(47) ,$text);
	$text=str_replace(chr(32) ,chr(92).chr(115),$text);
	$text=str_replace(chr(124),chr(92).chr(112),$text);
	$text=str_replace(chr(7)  ,chr(92).chr(97) ,$text);
	$text=str_replace(chr(8)  ,chr(92).chr(98) ,$text);
	$text=str_replace(chr(12) ,chr(92).chr(102),$text);
	$text=str_replace(chr(10) ,chr(92).chr(110),$text);
	$text=str_replace(chr(13) ,chr(92).chr(114),$text);
	$text=str_replace(chr(9)  ,chr(92).chr(116),$text);
	$text=str_replace(chr(11) ,chr(92).chr(118),$text);
	$text=utf8_encode($text);
	return $text;
}
function tsread($size,$timeout=1)
{
	global $teamspeakhandle;
	$start=microtime(true);
	do
	{
		usleep($size*10);
		$data=fread($teamspeakhandle,1);
		$info=stream_get_meta_data($teamspeakhandle);
	}
	while ((microtime(true)-$start)<$timeout and $info['timed_out']);
	do
	{
		usleep($size*10);
		if ($info['unread_bytes']>$size)
		{
			$data.=fread($teamspeakhandle,$size);
		}
		else
		{
			$data.=fread($teamspeakhandle,$info['unread_bytes']+1);
		}            
		$info=stream_get_meta_data($teamspeakhandle);
	}
	while ($info['unread_bytes']>0);
	return $data;
}
function tscmd($command="",$raw=false)
{
	global $teamspeakhandle;
	// Send Command, if specified
	if ($command!="")
	{
		fputs($teamspeakhandle,$command."\n");
	}
	// Format Output
	if ($raw)
	{
		$content=tsread(256);
		return $content;
	}
	else
	{
		// Read Commandreturn
		$content=tsread(256);
		// If Return is empty
		if (strlen($content)==0)
		{
			$content.= "error id=9876 msg=No/sData/srecieved";
		}
		else
		{
			// Read Statusmessage if not already sent
			if (strpos($content,"error id=")===false and $command!="")
			{
				$content.=tsread(256);
				if (strpos($content,"error id=")===false)
				{
					$content.=" error id=9876 msg=No/sStatusmessage/srecieved";
				}
			}
			else
			{
				if ($command=="" and strpos($content,"error id=")===false)
				{
					$content.=" error id=-1 msg=Server/sresponse/swithout/sCommand";
				}
			}
		}
		$result=array();
		$status=array();
		$resultstring=substr($content,0,strpos($content,"error id="));
		$statusstring=substr($content,strpos($content,"error id=")+6);
		$parameters=explode(" ",$statusstring);
		foreach ($parameters as $parameter)
		{
			list($name,$value)=explode("=",$parameter);
			$error[trim($name)]=tsunescape(trim($value));
		}
		if (strpos($resultstring,"|")!==false)
		{
			$lines=explode("|",$resultstring);
		}
		else
		{
			$lines=explode("\n",$resultstring);
		}
		foreach ($lines as $count=>$line)
		{
			if ($line!="")
			{
				$parameters=explode(" ",$line);
				
				foreach ($parameters as $parameter)
				{
					list($name,$value)=explode("=",$parameter);
					$result[$count][trim($name)]=tsunescape(trim($value));
				}
			}
		}
		return array("status"=>$error,"result"=>$result);
	}
}
?>