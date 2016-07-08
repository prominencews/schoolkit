<?php

//print "checking in\n";

$prefix='PISIGN-';



$maceth0=system('/sbin/ifconfig eth0 | /bin/grep -o -E "([[:xdigit:]]{1,2}:){5}[[:xdigit:]]{1,2}" | /bin/sed "s/://g"');
$currenthost=exec('/bin/hostname');

$host=$prefix.$maceth0;

if($host==$currenthost){
	echo "host correct\n";
}else{
	echo "host needs update\n";
	
	exec('/bin/hostname '.$host.'; /bin/echo '.$host.' > /etc/hostname; /sbin/reboot;');
	die("tried rebooting\n");
}

//exec('/root/hostname.sh; sleep 20');


//die('break');


exec('/bin/su -c "export DISPLAY=:0; /usr/bin/xrandr --current --verbose 2>/dev/null" user ',$return);
$i=1; //monitor #
$inedid=0;
foreach($return as $line){
	if($inedid==1){
		if(strpos($line,":")!==false){
			//no more edid
			$inedid=0;
			$i++;
		}else{
			$edids[$i][]=trim($line);
		}
	}
	if(strpos($line,"EDID:")!==false){
		//found edid start
		//print '+++++++++++++++++++++++found edid';
		$inedid=1;
	}
}

if(@count($edids)>0){
		
	
	$edids=array_map("implode",$edids);
	
	foreach($edids as $id=>$edid){
		$fileName = "/tmp/edid".$id;
		$packed=pack('H*', trim($edid));
		$ptr = fopen($fileName, 'wb+');
		fwrite($ptr, $packed);
		fclose($ptr);
	
		unset($return);
		exec('/usr/bin/edid-decode '.$fileName.' | /bin/grep "Serial number:" | cut -d":" -f2 ',$return);
		
		foreach($return as $areturn){
			print $areturn."\n";
			$monitors[$id]['serial']=trim($areturn);
			$monitorserials[]=trim($areturn);
		}
		
		unset($return);
		exec('/usr/bin/edid-decode '.$fileName.' | /bin/grep "Monitor name:" | cut -d":" -f2 ',$return);
		foreach($return as $areturn){
			print $areturn."\n";
			$monitors[$id]['model']=trim($areturn);
		}
		
		unset($return);
		exec('/usr/bin/edid-decode '.$fileName.' | /bin/grep "Manufacturer:" | cut -d":" -f2 ',$return);
		foreach($return as $areturn){
			print $areturn."\n";
			$monitors[$id]['manufacturer']=trim($areturn);
		}
		
		
		
	}
	
	
	$monitorserials=implode(',',$monitorserials);
	
	print "monitor serials: ".$monitorserials."\n\n";
}




$macwlan0=system('/sbin/ifconfig wlan0 2>/dev/null 2>/dev/null | /bin/grep -o -E "([[:xdigit:]]{1,2}:){5}[[:xdigit:]]{1,2}" | /bin/sed "s/://g"');

//print "mac is ".$mac."\n";

exec('/usr/sbin/dmidecode -t system | /bin/grep "System Information" -A 7',$info,$return);

$info=implode("\n",$info);

/*
exec('dmidecode -t system | grep Serial | cut -d":" -f2',$serial,$return);
$serial=reset($serial);
*/

//print 'info is '.var_dump($info);

$version=trim(file_get_contents('/root/version'));

$ch=curl_init();
curl_setopt($ch,CURLOPT_URL, "http://10.0.133.212/vnc.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

$fields=array(
	'maceth0'=> urlencode($maceth0),
        'macwlan0'=> urlencode($macwlan0),
	'job'=> urlencode('register'),
	'version'=> urlencode($version),
	'name'=>system('/bin/hostname'),
	'monitorserials'=>$monitorserials,
	'info'=>urlencode($info)
);

foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
rtrim($fields_string, '&');

curl_setopt($ch,CURLOPT_POST, count($fields));
curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
curl_setopt($ch, CURLOPT_USERPWD, ":StaffOnly");  

//die('post is '.print_r($fields_string,true));

$output = curl_exec($ch); 

print "output is ".print_r($output,true)."\n";

$outputarray=json_decode($output,true);

print_r($outputarray);

curl_close($ch);   

$homepagefile='/home/user/settings/homepage.txt';
$chromeargumentsfile='/home/user/settings/chrome-arguments.txt';



if(empty($outputarray['homepage'])){
	$homepage='http://10.0.133.212/elab.php';
}else{
	$homepage=$outputarray['homepage'];
}
	

if(empty($outputarray['chrome-arguments'])){
        $chromearguments=' --start-maximized  ';
}else{
        $chromearguments=$outputarray['chrome-arguments'];
}

if(
	(file_get_contents($homepagefile)!=$homepage) OR
	(file_get_contents($chromeargumentsfile)!=$chromearguments)

){
	$chromechanged=true;
}else{
	$chromechanged=false;
}

file_put_contents($homepagefile,$homepage);
file_put_contents($chromeargumentsfile,$chromearguments);

if($chromechanged){
	exec('/usr/bin/killall chrome');
}

?>
