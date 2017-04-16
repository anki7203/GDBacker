<?php

include('../src/Facebook/autoload.php');
include('../hybridauth/config.php');
include('../hybridauth/Hybrid/Auth.php');

if(isset($_GET['logoutuser'])){ logOutUser("http://andrewkiproff.com/gdbacker/"); }

if(isset($_GET['previd'])){
	$hybridauth = new Hybrid_Auth($config);
	$adapter = $hybridauth->authenticate($_GET['provider']);
	logOutUser('http://andrewkiproff.com/gdbacker/app/login.php?share='.$_GET['share'].'&projectid='.$_GET['projectid'].'&provider='.$_GET['provider'].'&previd='.$_GET['previd']); 
}


function logOutUser($redirect){
	if(session_status() == PHP_SESSION_NONE){ session_start(); }
	session_destroy();

	// unset cookies
	if (isset($_SERVER['HTTP_COOKIE'])) {
	    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
	    foreach($cookies as $cookie) {
	        $parts = explode('=', $cookie);
	        $name = trim($parts[0]);
	        setcookie($name, '', time()-1000);
	        setcookie($name, '', time()-1000, '/');
	    }
	}
	if($redirect){ header("Location: ".$redirect); }
}
?>