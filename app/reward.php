<?php
function editUserPoints($pts, $action, $userid){

	$conn = dbconnect();
	$stmt = $conn->prepare("SELECT points FROM users WHERE id=?");//get users current points
	$stmt->bind_param("i", $userid);
	$stmt->execute();
	$stmt->bind_result($points);
	$stmt->fetch();
	$stmt->close();

	$pts = intval($pts);
	$points = intval($points);
	if($action){ $points = $points+$pts; }else{ $points = $points-$pts; }//add to points if $action is true, otherwise subtract from points

	$lifespan = time() + (86400 * 30);//update 'points' cookie
	unset($_COOKIE['points']);
	setcookie('points', $points, $lifespan, "/");

	$stmt = $conn->prepare("UPDATE users SET points=? WHERE id=?");//update users points in db
	$stmt->bind_param("ii", $points, $userid);
	$stmt->execute();
	$stmt->close();
	dbclose($conn);
}
?>