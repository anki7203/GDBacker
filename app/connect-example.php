<?php
function dbconnect(){
	$servername = "-----";
	$username = "-----";
	$password = "-----";
	$dbname = "-----";

	$conn = new mysqli($servername, $username, $password, $dbname);

	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}

	return $conn;
}


function dbclose($conn){
	$conn->close();
}


function hybridauthCatch($e){
	switch( $e->getCode() ){
		case 0 : $msg = "Unspecified error"; break;
		case 1 : $msg = "Authorization configuration error"; break;
		case 2 : $msg = $provider." is not configured"; break;
		case 3 : $msg = $provider." is unknown or disabled"; break;
		case 4 : $msg = "Missing ".$provider." credentials"; break;
		case 5 : $msg = "Authentication failed"; break;
		case 6 : $msg = "User profile request failed. Try again in a few moments";
				 $authProvider->logout(); break;
		case 7 : $msg = "User not connected".$provider;
				 $authProvider->logout(); break;
		case 8 : $msg = $provider." does not support this feature"; break;
	}
	if(isset($msg)){
		error_log('hybridauthCatch :: '.$msg);
		return $msg;
	}else{
		return false;
	}
}


function curlIt($url){
	$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	curl_setopt($ch, CURLOPT_URL,$url);
	$result = curl_exec($ch);
	return $result;
}

function dumpit($v){ echo '<pre>'.var_export($v, true).'<pre>'; }

?>