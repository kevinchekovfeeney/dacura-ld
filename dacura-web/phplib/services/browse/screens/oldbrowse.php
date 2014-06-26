<?php 
/* 
 * Traffic controller page where all requests are routed
 * The general pattern of context is
 * /[application]|collection_id/dataset_id|[application]/[application]/application/parameters/etc
 */


include_once("phplib/DacuraServer.php");
include_once("phplib/settings.php");

$ds = new DacuraServer($dacura_settings);

$install_base = $ds->settings['install_url'];
$path = (isset($_GET['path']) && $_GET['path']) ? explode("/", $_GET['path']) : array();
$dcuser = $ds->getUser(0);
$app = $ds->setUserContext($dcuser, $path);

//if($ds->)
/*
$section = "front";
if(count($path) == 0 or $path[0] == ""){
	if($dcuser){
		$section = "home";
	}
}
else {
	$page = array_shift($path);
	$section = $page;
}

function publicPage($pg){
	if($pg == 'login' or $pg == 'register' or $pg == 'about' or $pg == "lost") return true;
	return false;
}

function pageExists($pg){
	$oks = array("home", "login", "about", "register", "lost");
	if(in_array($pg, $oks)) return true;
	//if it is not a page, it should be a collection id. 
	
}
*/

?>
<!DOCTYPE html>
<html>
<head>
<meta charset=utf-8 />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

<title>Dacura</title>
<link rel="shortcut icon" href="<?=$install_base?>media/images/favicon2.ico" />
<link href='http://fonts.googleapis.com/css?family=Open+Sans:300,600' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$install_base?>media/css/master.css" />
<script src="<?=$install_base?>media/js/jquery.js"></script>
<script src="<?=$install_base?>media/js/dacura.js"></script>
<script src="<?=$install_base?>media/js/jquery-ui-1.10.2.custom.min.js"></script>

<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body>
<div id="content-container">
<?php
	//echo "<h2>".$app->path()."</h2>";
	if(!$dcuser && $app->isHelloPage()) {?>
		<div id="maincontrol">
		<h1 id='welcome'>Dacura</h1>
		<div class="tagline">Intelligent Dataset Curation</div>
		<a class="button" href="<?=$install_base?>about">Find out more</a>
		<a class="button" href="<?=$install_base?>login">Log in</a>
		</div>
		<?php
	}
	elseif(!file_exists($app->path())) {
	?>
		<div id="maincontrol">
			<h1 id='welcome'>Dacura</h1>
			<div class="tagline"><?=$app->path()?> does not exist</div>
		</div>
	<?php 
	}
	else {
	?>
		<div id="dacura-header">
			<div class="logo"><a href="<?=$install_base?>"><img src="<?=$install_base?>media/images/dacura-logo-simple.png" height=36></a></div>
		</div>
		<script>dacura.system.load("<?=$ds->settings['ajaxurl']?>", "local");</script>
		<div id="dacura-content">
		<?php
		include_once($app->path());
	}					
	?>
		</div>
</div>

<footer>
<div id="footer-bar-container">
<div id="footer-bar">
A research system developed in the <a href="http://kdeg.scss.tcd.ie">Knowledge and Data Engineering Group</a>,
<a href="http://www.tcd.ie">Trinity College Dublin.</a>
<div id='kdegtcdlogos'></div>
</div>
<div id="dacura-debug">
</div>
<?php
/*
 * The following variables are available...
* $ds -> DacuraServer Object
* $dcuser -> Dacura user object (false if no user is logged in)
* $app -> Application Context Object.
*/

//$context = $ds->getUserHomeContext($dcuser);
?>
<div id='dashboard-container'>
		<div id='dashboard-menu'>
			<?php $ds->renderUserMenu($app, $dcuser);?>
		</div>
		<div id='dashboard-content'>
			<?php $ds->renderUserActions($app, $dcuser);?>
			<?php $ds->renderUserGraph($app, $dcuser);?>
			<?php $ds->renderUserStats($app, $dcuser);?>
		</div>
</div>
</div>


<div id="footer-main">
<div class="center">
<div id="footer-links">
<ul>
<li><a href="<?=$install_base?>about/news">News</a></li>
<li><a href="<?=$install_base?>about/contact">Contact</a></li>
<li><a href="<?=$install_base?>about/terms">Terms</a></li>
</ul>
</div>
<div id="footer-copyright">&copy; Copyright Trinity College Dublin 2012 - 2013. All rights reserved.</div>
</div>
</div>
</footer>

</body>
</html>


