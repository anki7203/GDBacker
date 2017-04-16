<?php
include('connect.php');
include('achievements.php');

if(isset($_GET['projectid'])){
	$conn = dbconnect();

	$reward = true;

	$vote = $_GET['vote'];
	$projectid = $_GET['projectid'];
	$userid = $_COOKIE['userid'];

	$stmt = $conn->prepare("SELECT score, uvotes, dvotes FROM projects WHERE projectid=?");//get votes for request project
	$stmt->bind_param("i", $projectid);
	$stmt->execute();
	$stmt->bind_result($score, $uvotes, $dvotes);
	$stmt->fetch();
	$stmt->close();

	$uArray = explode(",",$uvotes);//turn string to array
	$dArray = explode(",",$dvotes);

	if(in_array($userid, $uArray)){//if user previous upvoted...
		$i = array_search($userid, $uArray);//find poition of user is...
		unset($uArray[$i]);//and remove it.
		$uvotes = implode(",",$uArray);//turn back into string
		$reward = false;//don't reward, previously upvoted.
		$score--;
	}elseif(in_array($userid, $dArray)){
		$i = array_search($userid, $dArray);
		unset($dArray[$i]);
		$dvotes = implode(",",$dArray);
		$reward = false;
		$score++;
	}

	if($vote == 'up'){ //if upvote...
		$score++;//add to score...
		$uvotes = $uvotes.','.$userid;//and add user id to upvote string.
		$uvotes = trim($uvotes,",");
	}
	if($vote == 'down'){ 
		$score--;
		$dvotes = $dvotes.','.$userid;
		$dvotes = trim($dvotes,",");
	}

	$stmt = $conn->prepare("UPDATE projects SET score=?, uvotes=?, dvotes=? WHERE projectid=?");//add new values to project's row i ndb
	$stmt->bind_param("issi", $score, $uvotes, $dvotes, $projectid);
	$stmt->execute();
	$stmt->close();

	//if user retracted vote, remove earned point and achievement progression
	if($reward && $vote != 'nuteralize'){ editUserPoints(1, true, $userid); updateAchievement(1,'votedOneTimes,votedTwentyTimes,votedHundredTimes',true, $userid); }
	if($vote == 'nuteralize'){ editUserPoints(1, false, $userid); updateAchievement(1,'votedOneTimes,votedTwentyTimes,votedHundredTimes',false, $userid); }

	dbclose($conn);
}
?>