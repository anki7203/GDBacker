<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('app/achievements.php');

$body = '';
if(isset($_COOKIE['userid'])){ $body = 'user'; }

?>
<!doctype html>
<html lang="en">
<head>

	<meta charset="utf-8">
	<meta http-equiv="expires" content="0">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="author" content="Andrew Kiproff">
	<title>GD Backer</title>

	<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Raleway|Open+Sans|Domine" rel="stylesheet">
	<link rel="stylesheet" href="css/main.css?<?= rand()?>">
	<link rel="stylesheet" href="css/mCustomScrollbar.css">

</head>
<body class="<?=$body?>">
<div class="message hidden"><p></p><button>OK</button><span>Don't link me right now.</span></div>
<div class="userbar off"><img class="logo" alt="GD Backer" src="img/logo.png" /></div>

	<?php
		$output = '';
		if(isset($_COOKIE['userid'])){//if user logged in, show user info based on set cookies
			$output  .= '<div class="user-info">';
			$output .= '<img src="'.$_COOKIE['img'].'" alt="'.$_COOKIE['username'].'"/> ';
			$output .= $_COOKIE['username'];
			$output .= '<span>'.$_COOKIE['points'].'</span>';
			$output .= '<button class="logout">log out</button>';
			$output .= '</div>';
			echo $output;
		}else{
			$output  .= '<div class="login-btn">Login/Sign up</div>';//otherwise, who signup
			$output .= '<div class="login-modal hidden">';
			$output .= '<i class="fa fa-times login-close" aria-hidden="true"></i>';
			$output .= '<span>';
			$output .= '<h2>Login or Sign-up with:</h2>';
			$output .= '<button data-provider="Facebook" id="facebook-login"><i class="fa fa-facebook" aria-hidden="true"></i> Facebook</button>';
			$output .= '<button data-provider="Twitter" id="twitter-login"><i class="fa fa-twitter" aria-hidden="true"></i> Twitter</button>';
			$output .= '<button data-provider="Google" id="google-login"><i class="fa fa-google-plus" aria-hidden="true"></i> Google</button>';
			$output .= '</span>';
			$output .= '</div>';
			echo $output;
		}
	?>

	<div class="ach-btn"><span>Achievements</span><i class="fa fa-trophy" aria-hidden="true"></i></div>
	<div class="ach-modal hidden">
		<i class="fa fa-times ach-close" aria-hidden="true"></i>
		<ul class="mCustomScrollbar" data-mcs-theme="light-3">
			<?php
				$output = '';
				if(isset($_COOKIE['userid'])){ //if user logged in...
					$userid = intval($_COOKIE['userid']);
					$user = getUserUnlocks($userid); //... check for unlocked achievements by NICKNAME (achievments.php)
				}else{
					$user = false;
				}
				$achievements = getAchievements();// get achievements from db (achievments.php)
				foreach ($achievements as $key => $achievement) {
					$unlocked = '';

					if($user){
						$nick = $achievement['nickname'];
						$unlocked = $user[$nick]; //check unlock status via NICKNAME
						if($unlocked == 1){//if achivement was just unlocked
							$unlocked = 'on';
							addAchNotification($achievement['icon'],$achievement['title'],$achievement['blurb'],$achievement['value'],$achievement['nickname']);
						}
						if($unlocked == 2){ $unlocked = 'on'; }//if user has already seen notification
					}

					$output .= '<li>';
					$output .= '<span class="ach-icon"><i class="fa '.$achievement['icon'].'" aria-hidden="true"></i></span>';
					$output .= '<span class="ach-desc"><h3>'.$achievement['title'].'</h3><p>'.$achievement['blurb'].' - '.$achievement['value'].'p</p></span>';
					$output .= '<span class="ach-status '.$unlocked.'"><i class="fa fa-check" aria-hidden="true"></i></span>';
					$output .= '</li>';
				}

				echo $output;
			?>
		</ul>
	</div>

	<div class="lead-btn"><span>Leaderboard</span><i class="fa fa-list-ol" aria-hidden="true"></i></div>
	<div class="lead-modal hidden">
		<i class="fa fa-times lead-close" aria-hidden="true"></i>
		<ul class="mCustomScrollbar" data-mcs-theme="light-3">
			<?php
				$output = '';
				$conn = dbconnect();
				$stmt = $conn->prepare("SELECT name, points, img FROM users ORDER BY points DESC");
				$stmt->execute();
				$stmt->bind_result($name, $points, $img);
				while ($stmt->fetch()){
					$output .= '<li>';
					$output .= '<span class="lead-icon">';
					$output .= '<img src="'.$img.'" alt="'.$name.'">';
					$output .= '</span>';
					$output .= '<span class="lead-desc">';
					$output .= '<h3>'.$name.'</h3>';
					$output .= '<p>'.$points.' Points</p>';
					$output .= '</span>';
					$output .= '</li>';
				}
				$stmt->close();
				dbclose($conn);
				echo $output;
			?>
		</ul>
	</div>

	<header>
		<img class="logo" alt="GD Backer" src="img/logo.png" />
		<p><em>GD Backer</em>&nbsp; is a fun, easy and FREE way to find and promote projects that benefit global development!</p>
		<button>Get Started!</button>
	</header>

	<section>
		<?php
			$output = '';
			$conn = dbconnect();
			$stmt = $conn->prepare("SELECT projectid, title, img, platform, url, blurb, score, uvotes, dvotes FROM projects ORDER BY score DESC");//get project info
			$stmt->execute();
			$stmt->bind_result($projectid, $title, $img, $platform, $url, $blurb, $score, $uvotes, $dvotes);
			while ($stmt->fetch()){
				$uArray = explode(",",$uvotes);
				$dArray = explode(",",$dvotes);
				$upVote = '';
				$dnVote = '';
				if(isset($_COOKIE['userid'])){ 
					$userid = $_COOKIE['userid'];
					if(in_array($userid, $uArray)){ $upVote = 'on';	}//if userid is in upvote or downvote array add class to vote icon
					if(in_array($userid, $dArray)){	$dnVote = 'on';	}
				}
				$output .= '<article class="project" data-id="'.$projectid.'">';
				$output .= '<div class="vote-box" data-id="'.$projectid.'">';
				$output .= '<span class="upvote '.$upVote.'"><i class="fa fa-chevron-up" aria-hidden="true"></i></span>';
				$output .= '<span class="score">'.$score.'</span>';
				$output .= '<span class="dnvote '.$dnVote.'"><i class="fa fa-chevron-down" aria-hidden="true"></i></span>';
				$output .= '</div>';
				$output .= '<span class="source-badge '.$platform.'"></span>';
				$output .= '<img class="target" src="'.$img.'" alt="Project Image" />';
				$output .= '<h3 class="target">'.$title.'</h3>';
				$output .= '<p>'.$blurb.'</p>';
				$output .= '<a href="'.$url.'" target="_blank">Learn More</a>';
				$output .= '<button>Promote</button>';
				$output .= '<div class="project-modal hidden">';
				$output .= '<i class="fa fa-times project-close" aria-hidden="true"></i>';
				$output .= '<div class="info" data-id="'.$projectid.'" >';
				$output .= '<ul class="promo-list">';
				$output .= '<li data-provider="Facebook" data-share="fbwall" class="fb"><i class="fa fa-facebook" aria-hidden="true"></i><span class="promo-title">Post on Wall</span><span class="promo-desc">Make a post to your Facebook wall</span><span class="points">3pt</span></li>';
				$output .= '<li data-provider="Twitter" data-share="tweet" class="tw"><i class="fa fa-twitter" aria-hidden="true"></i><span class="promo-title">Send a Tweet</span><span class="promo-desc">Tweet to your followers</span><span class="points">2pt</span></li>';
				$output .= '<li data-provider="Reddit" data-share="subreddit" class="rd"><i class="fa fa-reddit" aria-hidden="true"></i><span class="promo-title">Post on Subreddit</span><span class="promo-desc">Post to a Subreddit</span><span class="points">4pt</span></li>';
				$output .= '</ul>';
				$output .= '</div>';
				$output .= '</div>';
				$output .= '</article>';
			}
			$stmt->close();
			dbclose($conn);
			echo $output;
		?>
	</section>

	<ul class="notifications hidden">
		<?php
			if(isset($_SESSION['ach-notifications'])){//'ach-notifications' set via addAchNotification() in achievements.php
				$output = '';
				$notifications = $_SESSION['ach-notifications'];
				$notifications = explode("|",$notifications);
				foreach ($notifications as $key => $notification) {
					$info = json_decode($notification);
					$output .= '<li class="pulse">';
					$output .= '<h2>Achievement Unlocked!</h2>';
					$output .= '<h3><i class="fa '.$info->icon.'" aria-hidden="true"></i>'.$info->title.'</h3>';
					$output .= '<p>'.$info->blurb.' - <span>'.$info->value.'</span></p>';
					$output .= '<button data-nick="'.$info->nick.'">OK!</button>';
					$output .= '</li>';
				}
				echo $output;
			}
		?>
	</ul>

	<footer>
		<p>© GD Backer 2017 | <a href="#">Privacy & Terms</a> | <a href="#">Sitemap</a> | <a target="_blank" href="http://www.andrewkiproff.com/">AK</a></p>
	</footer>

	<script src="js/plugins.js?<?php echo time(); ?>"></script>
	<script src="js/main.js?<?php echo time(); ?>"></script>
</body>
</html>