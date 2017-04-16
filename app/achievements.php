<?php
include('reward.php');
if(!function_exists('dbconnect')){ include('connect.php'); }

if(isset($_GET['clearNotification'])){

	$nick = $_GET['nick'];
	$userid = $_GET['userid'];

	$conn = dbconnect();

	$stmt = $conn->prepare(" SELECT value FROM achievements WHERE nickname = '$nick' ");//get value of achievement
	$stmt->execute();
	$stmt->bind_result($value);
	$stmt->fetch();
	$stmt->close();

	$stmt = $conn->prepare("UPDATE unlocked SET $nick=2 WHERE userid=?");//set achievemetn as seet
	$stmt->bind_param("i", $userid);
	$stmt->execute();
	$stmt->close();

	dbclose($conn);

	editUserPoints($value, true, $userid);//claim points (reward.php)

	echo 'notifications cleared';
	exit;
}

function getAchievements(){//build achievement obj from db
	$achievments = [];
	$conn = dbconnect();
	$stmt = $conn->prepare("SELECT id, title, goal, icon, blurb, value, nickname FROM achievements ORDER BY value DESC");
	$stmt->execute();
	$stmt->bind_result($id, $title, $goal, $icon, $blurb, $value, $nickname);
	while($stmt->fetch()){
		$achievments[$id]['title'] = $title;
		$achievments[$id]['goal'] = $goal;
		$achievments[$id]['icon'] = $icon;
		$achievments[$id]['blurb'] = $blurb;
		$achievments[$id]['value'] = $value;
		$achievments[$id]['nickname'] = $nickname;
		$achievments[$id]['unlocked'] = false;
	}
	$stmt->close();
	dbclose($conn);
	return $achievments;
}


function getUserUnlocks($userid){//build user (unlocked achievemetnt status) obj from db
	$user = [];
	$conn = dbconnect();
	$stmt = $conn->prepare("SELECT createdAccount, supportedTenProjects, supportedTwentyProjects, sentOneTweets, sentFiveTweets, sentOneFBWalls, sentFiveFBWalls, votedOneTimes, votedTwentyTimes, votedHundredTimes FROM unlocked WHERE userid=".$userid);
	$stmt->execute();
	$stmt->bind_result($createdAccount, $supportedTenProjects, $supportedTwentyProjects, $sentOneTweets, $sentFiveTweets, $sentOneFBWalls, $sentFiveFBWalls, $votedOneTimes, $votedTwentyTimes, $votedHundredTimes);
	$stmt->fetch();
	$stmt->close();
	dbclose($conn);

	$user['createdAccount'] = $createdAccount;
	$user['supportedTenProjects'] = $supportedTenProjects;
	$user['supportedTwentyProjects'] = $supportedTwentyProjects;
	$user['sentOneTweets'] = $sentOneTweets;
	$user['sentFiveTweets'] = $sentFiveTweets;
	$user['sentOneFBWalls'] = $sentOneFBWalls;
	$user['sentFiveFBWalls'] = $sentFiveFBWalls;
	$user['votedOneTimes'] = $votedOneTimes;
	$user['votedTwentyTimes'] = $votedTwentyTimes;
	$user['votedHundredTimes'] = $votedHundredTimes;

	return $user;
}


function addAchNotification($icon,$title,$blurb,$value,$nick){//buld 'ach-notifications' session
	$session = '';
	if(isset($_SESSION['ach-notifications'])){ $session = $_SESSION['ach-notifications']; }// if 'ach-notifications' already exists (in the event of multiple achievement unlocks)....
	$session = $session.'|{"icon":"'.$icon.'","title":"'.$title.'","blurb":"'.$blurb.'","value":"'.$value.'","nick":"'.$nick.'"}';//add to it.
	$session = trim($session,"|");
	$_SESSION["ach-notifications"] = $session;
}


function updateAchievement($pts,$nicks,$action,$userid){
	$conn = dbconnect();

	$nicks = explode(',', $nicks);

	foreach ($nicks as $key => $nick) {
 
		$stmt = $conn->prepare("SELECT $nick FROM progress WHERE userid=$userid");//get current achievement status via nickname
		$stmt->execute();
		$stmt->bind_result($score);
		$stmt->fetch();
		$stmt->close();

		$stmt = $conn->prepare(" SELECT goal, value FROM achievements WHERE nickname = '$nick' ");//get goal and value of achievement
		$stmt->execute();
		$stmt->bind_result($goal, $value);
		$stmt->fetch();
		$stmt->close();

		if($action){ $score = $score+$pts; }else{ $score = $score-$pts; }//add to points if $action is true, otherwise subtract from points

		$stmt = $conn->prepare("UPDATE progress SET $nick=$score WHERE userid=?");//update achievemt progress
		$stmt->bind_param("i", $userid);
		$stmt->execute();
		$stmt->close();

		if($score == $goal){//if newly calculated score is equal to goal...
			$stmt = $conn->prepare("UPDATE unlocked SET $nick=1 WHERE userid=?");//unlock achievemtn and set to be shown to user
			$stmt->bind_param("i", $userid);
			$stmt->execute();
			$stmt->close();
		}
	}

	dbclose($conn);
	exit;
}

?>