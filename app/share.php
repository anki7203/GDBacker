<?php
session_start();
require_once('connect.php');
require_once('achievements.php');
include('../src/bitly.php');
include("../src/Reddit/reddit.php");
// HybridAuth Includes
include('../src/Facebook/autoload.php');
include('../hybridauth/config.php');
include('../hybridauth/Hybrid/Auth.php');
use Facebook\FacebookRequest;



if(isset($_GET['share'])){

	$share = $_GET['share'];
	switch ($share) {
	    case 'fbwall': postToFacebookWall($_GET['projectid'], '', false,$config,$share); break;
	    case 'fbwall-send': postToFacebookWall($_GET['projectid'], $_GET['data'], true, $config,$share); break;
		case 'tweet': postToTwitter($_GET['projectid'], '', false,$config,$share); break;
		case 'tweet-send': postToTwitter($_GET['projectid'], $_GET['data'], true, $config,$share); break;
		case 'subreddit': postToSubreddit($_GET['projectid'], '', false,$config,$share); break;
		case 'subreddit-send': postToSubreddit($_GET['projectid'], $_GET['data'], true, $config,$share); break;
	    default: echo 'something went wrong with "share.php"';
	}	

}


function postToFacebookWall($projectid,$data,$send,$config,$share){
	if($send){
		try{
			$project = getProjectData($projectid);
			$hybridauth = new Hybrid_Auth($config);
			$adapter = $hybridauth->authenticate("Facebook");
			$adapter->setUserStatus(array(
				"message" => $data,
				"link" => $project->url,
				"picture" => $project->img,
			));
			echo "true,3";
			$userid = $_COOKIE['userid'];
			editUserPoints(3, true, $userid);
			updateAchievement(1,'sentOneFBWalls,sentFiveFBWalls,supportedTenProjects,supportedTwentyProjects',true, $userid);
		}catch( Exception $e ){ 
			if(hybridauthCatch($e)){ 
				echo 'redirect||http://andrewkiproff.com/gdbacker/app/login.php?provider=Twitter';
				exit;
			}
		}
	}else{
		if($_COOKIE['provider'] == 'Facebook'){
			$project = getProjectData($projectid);
			$output =  '<div id="makepost" data-bg="fb" class="fbwall">
							<div class="header">
								<img src="'.$_COOKIE['img'].'" alt="'.$_COOKIE['username'].'" />
								<div>
									<p>'.$_COOKIE['username'].'</p>
									<span>Now · GDBacker</span>
								</div>
							</div>
							<span class="marker"></span>
							<textarea class="focus">Hey Friends! Help me spread the word about "'.$project->title.'". It\'s a great idea and I think it could help a lot of people! 😀</textarea>
							<img src="'.$project->img.'" alt="'.$project->title.'" />
							<h1>'.$project->title.'</h1>
							<p>'.$project->blurb.'</p>
						</div>
						<button id="sharebtn" data-share="fbwall-send">Share</button>';
			echo $output;
		}else{
			if(isset($_COOKIE['userid'])){ $previd = '&previd='.$_COOKIE['userid']; }
			echo 'redirect||http://andrewkiproff.com/gdbacker/app/logout.php?share='.$share.'&projectid='.$projectid.'&provider=Facebook'.$previd.'||Facebook';
		}
	}
}


function postToTwitter($projectid,$data,$send,$config,$share){
	if($send){
		try{
			$hybridauth = new Hybrid_Auth($config);
			$adapter = $hybridauth->authenticate("Twitter");
			$adapter->setUserStatus($data);
			echo "true,2";
			$userid = $_COOKIE['userid'];
			editUserPoints(2, true, $userid);
			updateAchievement(1,'sentOneTweets,sentFiveTweets,supportedTenProjects,supportedTwentyProjects',true, $userid);
		}catch( Exception $e ){ 
			if(hybridauthCatch($e)){ 
				echo 'redirect||http://andrewkiproff.com/gdbacker/app/login.php?provider=Twitter';
				exit;
			}
		}
	}else{
		if($_COOKIE['provider'] == 'Twitter'){
			$project = getProjectData($projectid);
			$output =  '<div id="makepost" data-bg="tw" class="tweet">
							<div class="header">Compse new Tweet</div>
							<span class="marker"></span>
							<textarea class="focus count-chars">Check out "'.$project->title.'". Might help a lot of people! 😀'.shortenLink($project->url).'</textarea>
							<span class="char-counter"></span>
							<button id="sharebtn" data-share="tweet-send">Share</button>
						</div>';
			echo $output;
		}else{
			if(isset($_COOKIE['userid'])){ $previd = '&previd='.$_COOKIE['userid']; }
			echo 'redirect||http://andrewkiproff.com/gdbacker/app/logout.php?share='.$share.'&projectid='.$projectid.'&provider=Twitter'.$previd.'||Twitter';
		}
	}
}


function postToSubreddit($projectid,$data,$send,$config,$share){
	if($send){
		$userExists = $reddit->userExists();
		$userExistsArr = explode('||',$share);
		$userExists = $userExistsArr[0];
		$url = $userExistsArr[1];

		if($userExists == 'true'){
			//send it
		}else{
			echo 'redirect||'.$url.'||Reddit';
			exit;
		}

		$reddit = new reddit();
		$userExists = $reddit->userExists();
		echo $userExists;

	}else{
		$reddit = new reddit();
		$userExists = $reddit->userExists();
		$userExistsArr = explode('||',$userExists);
		$userExists = $userExistsArr[0];
		$url = $userExistsArr[1];
		if($userExists == 'true'){
			$project = getProjectData($projectid);
			$output =  '<div id="makepost" data-bg="rd" class="reddit">
							<div class="header">
								<h3 class="title"><span>*</span>Title</h3>
								<span class="char-counter"></span>
							</div>
							<textarea class="focus count-chars">'.$project->title.'</textarea>
							<h3 class="text">Title<span>(optional)</span></h3>
							<span class="marker"></span>
							<textarea class="focus count-chars">Check out "'.$project->title.'". Might help a lot of people! 😀'.$project->url.'</textarea>
							<h3 class="text"><span>*</span>Choose A Subreddit</h3>
							<div id="subreddits"></div>
							<button id="sharebtn" data-share="subreddit-send">Share</button>
						</div>';
			echo $output;
		}else{
			echo 'redirect||'.$url.'||Reddit';
			exit;
		}
	}
}


function getProjectData($projectid){
	$conn = dbconnect();
	$stmt = $conn->prepare("SELECT id, title, img, platform, url, blurb FROM projects WHERE projectid=?");
	$stmt->bind_param("i", $projectid);
	$stmt->execute();
	$stmt->bind_result($id, $title, $img, $platform, $url, $blurb);
	$stmt->fetch();
	$stmt->close();
	dbclose($conn);

	$obj = new stdClass();
	$obj->id = $id;
	$obj->title = $title;
	$obj->img = $img;
	$obj->platform = $platform;
	$obj->url = $url;
	$obj->blurb = $blurb;

	return $obj;
}


function shortenLink($url){ 
	$params = array();
	$params['access_token'] = file_get_contents('bitly_token.txt');
	$params['longUrl'] = $url;
	$results = bitly_get('shorten', $params);
	return $results['data']['url'];
}
?>