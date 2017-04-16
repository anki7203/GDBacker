<?php
include('connect.php');
// HybridAuth Includes
include('../src/Facebook/autoload.php');
include('../hybridauth/config.php');
include('../hybridauth/Hybrid/Auth.php');

session_start();
// START HybridAuth
if(isset($_GET['provider'])){
	$provider = $_GET['provider'];
	$idtype = getIdType($provider);
	try{
	    $hybridauth = new Hybrid_Auth( $config );//login user based on provider
	    $authProvider = $hybridauth->authenticate($provider);
	    $data = $authProvider->getUserProfile();
	    if($data && isset($data->identifier) && !isset($_COOKIE['userid'])){//if data retrieved successfully
	    	$providerid = $data->identifier;

	    	if(isset($_GET['previd'])){//if user was logged i nwith another account
	    		$previd = $_GET['previd'];
	    		updateUserPlatforms($previd,$providerid,$idtype);//add neww platform to user in db
	    		createSession($providerid,$idtype,$provider);//sign in
	    		exit;
	    	}

	    	if(userExists($providerid,$idtype)){//if user already exists
				createSession($providerid,$idtype,$provider);//sign in
			}else{
				createUser($data,$idtype);//if user doesn't exist, create new user
				createSession($providerid,$idtype,$provider);//sign in
			}
	    }else{
	    	header('Location: http://andrewkiproff.com/gdbacker/');
	    }         
	}
	catch( Exception $e ){ showError(hybridauthCatch($e));	}
}else{
	header('Location: http://andrewkiproff.com/gdbacker/');
}
// END HybridAuth

function getIdType($provider){//translates official provider name to db nickname
	if($provider == "Facebook"){ return 'faceid'; }
	if($provider == "Twitter"){ return 'twitid'; }
	if($provider == "Google"){ return 'googid'; }
}


function userExists($providerid,$idtype){//checks user table for user's info
	$conn = dbconnect();
	$stmt = $conn->prepare("SELECT id FROM users WHERE $idtype=?");
	$stmt->bind_param("i", $providerid);
	$stmt->execute();
	$stmt->bind_result($userid);
	$stmt->fetch();
	if($userid){ return true; }else{ return false; }
	$stmt->close();
	dbclose($conn);
}


function createUser($data,$idtype){//adds new user infor to db and creats rows for tracking achievements (progress/unlocked)
	$conn = dbconnect();

	$providerid = $data->identifier ?: '-';
	$name = $data->displayName ?: '-';
	$img = $data->photoURL ?: '-';
	$email = $data->email ?: '-';

	$stmt = $conn->prepare("INSERT INTO users ($idtype, name, email, img ) VALUES (?, ?, ?, ?)");
	$stmt->bind_param("isss", $providerid, $name, $email, $img);
	$stmt->execute();
	$stmt->close();

	$stmt = $conn->prepare("SELECT id FROM users WHERE $idtype=?");
	$stmt->bind_param("i", $providerid);
	$stmt->execute();
	$stmt->bind_result($userid);
	$stmt->fetch();
	$stmt->close();

	$stmt = $conn->prepare("INSERT INTO progress (userid) VALUES (?)");
	$stmt->bind_param("i", $userid);
	$stmt->execute();
	$stmt->close();

	$stmt = $conn->prepare("INSERT INTO unlocked (userid, createdAccount) VALUES (?,1)");
	$stmt->bind_param("i", $userid);
	$stmt->execute();
	$stmt->close();

	dbclose($conn);
}


function createSession($providerid,$idtype,$provider){//sets requird cookies to sign in user

	$conn = dbconnect();
	$stmt = $conn->prepare("SELECT id, points, img, name FROM users WHERE $idtype=?");
	$stmt->bind_param("i", $providerid);
	$stmt->execute();
	$stmt->bind_result($id, $points, $img, $name);
	$stmt->fetch();
	$stmt->close();
	dbclose($conn);

	$lifespan = time() + (86400 * 30);
	setcookie('userid', $id, $lifespan, "/");
	setcookie('points', $points, $lifespan, "/");
	setcookie('img', $img, $lifespan, "/");
	setcookie('username', $name, $lifespan, "/");
	setcookie('provider', $provider, $lifespan, "/");

	if(isset($_GET['projectid'])){ setcookie('projectid', $_GET['projectid'], time()+3600, "/"); }
	if(isset($_GET['share'])){ setcookie('projectshare', $_GET['share'], time()+3600, "/"); }

	header('Location: http://andrewkiproff.com/gdbacker/');
}


function updateUserPlatforms($previd,$providerid,$idtype){//adds new platform infor to existing user
	$conn = dbconnect();
	$stmt = $conn->prepare("UPDATE users SET $idtype=? WHERE id=?");
	$stmt->bind_param("ii", $providerid, $previd);
	$stmt->execute();
	$stmt->close();
	dbclose($conn);
}


function showError($msg){//if hybrid auth catches arror, display an error page with unique error message
	$output = ' <!doctype html>
				<html lang="en">
				<head>

					<meta charset="utf-8">
					<meta name="author" content="Andrew Kiproff">
					<title>GD Backer: Login</title>

					<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet">
					<link href="https://fonts.googleapis.com/css?family=Raleway|Open+Sans" rel="stylesheet">
					<link rel="stylesheet" href="../css/main.css?<?= rand()?>">
					<link rel="stylesheet" href="../css/mCustomScrollbar.css">

				</head>
				<body>
					<div class="login-modal error">
						<span>
							<img class="logo" alt="GD Backer" src="../img/logo.png">
							<h1>Oops... something went wrong!</h1>
							<h2><strong>Error:</strong> '.$msg.'</h2>
							<a href="http://andrewkiproff.com/gdbacker/"><i class="fa fa-home" aria-hidden="true"></i> Go Home</a>
						</span>
					</div>
					<footer>
						<p>© GD Backer 2017 | <a href="#">Privacy & Terms</a> | <a href="#">Sitemap</a> | <a target="_blank" href="http://www.andrewkiproff.com/">AK</a></p>
					</footer>
					<script src="../js/plugins.js?v=<?= rand()?>"></script>
					<script src="../js/main2.js?v=<?= rand()?>"></script>
				</body>
				</html>';
	echo $output;
}
?>