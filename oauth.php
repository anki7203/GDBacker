<?php
	include('src/Facebook/autoload.php');
	include('hybridauth/config.php');
	include('hybridauth/Hybrid/Auth.php');
	include('hybridauth/Hybrid/Endpoint.php');
	Hybrid_Endpoint::process();
?>