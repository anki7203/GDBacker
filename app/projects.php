<?php
include('connect.php');

$lastUpdate = file_get_contents('update.txt');//get timestamp of last update
$nextUpdate = $lastUpdate + 86400;//set next update to 1 day after last update
$now = time();
if($now > $nextUpdate){//if last update was more than 24hrs ago, update projects
	updateProjects();
	file_put_contents('update.txt', $now);
}else{
	outputProjectsJSON();
}


function updateProjects(){
	updateKickStarter();
	outputProjectsJSON();
}


function updateKickStarter(){
	$data = curlIt('https://www.kickstarter.com/projects/search.json?search=&term=global%20development');//get info from kickstarter api
	$json = json_decode($data);

	foreach ($json->projects as $project){//iterate json
		$projectid = $project->id;
		$title = $project->name;
		$img = $project->photo->full;
		$platform = 'kickstarter';
		$url = $project->urls->web->project;
		$blurb = $project->blurb;
		$score = 0;

		if(!projectExists($projectid)){//add to projects table
			$conn = dbconnect();
			$stmt = $conn->prepare("INSERT INTO projects (projectid, title, img, platform, url, blurb, score) VALUES (?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("isssssi", $projectid, $title, $img, $platform, $url, $blurb, $score);
			$stmt->execute();
			$stmt->close();
			dbclose($conn);
		}
	}

}


function projectExists($projectid){//if project already exists, don't add it
	$conn = dbconnect();
	$stmt = $conn->prepare("SELECT id FROM projects WHERE projectid=?");
	$stmt->bind_param("i", $projectid);
	$stmt->execute();
	$stmt->bind_result($userid);
	$stmt->fetch();
	if($userid){ return true; }else{ return false; }
	$stmt->close();
	dbclose($conn);
}


function outputProjectsJSON(){//output projects from db as json
	$conn = dbconnect();
	$output = '{"projects":[';
	$stmt = $conn->prepare("SELECT projectid, title, img, platform, url, blurb, score FROM projects ORDER BY score DESC");
	$stmt->execute();
	$stmt->bind_result($projectid, $title, $img, $platform, $url, $blurb, $score);
	while ($stmt->fetch()){

		$title = str_replace('"',"'", preg_replace( "/\r|\n/", "", $title ));
		$blurb = str_replace('"',"'", preg_replace( "/\r|\n/", "", $blurb ));

		$output .= '{';
		$output .= '"projectid": '.$projectid.',';
		$output .= '"title": "'.$title.'",';
		$output .= '"img": "'.$img.'",';
		$output .= '"platform": "'.$platform.'",';
		$output .= '"url": "'.$url.'",';
		$output .= '"blurb": "'.$blurb.'",';
		$output .= '"score": '.$score;
		$output .= '},';
	}

	$output = rtrim($output,",");
	$output .= ']}';

	$stmt->close();
	dbclose($conn);

	echo $output;
}

?>