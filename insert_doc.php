<html>
<head>

<?php


function seu_get_wp_config_path()
{
    $base = dirname(__FILE__);
	if (@file_exists($base."/wp-config.php"))
		return $base;
	
	while($base != '/') {
		$base = dirname($base);
		if (@file_exists($base."/wp-config.php"))
			 return $base;
	}
	
	return false;
}
$wp_path = seu_get_wp_config_path();

//load WP features
include_once($wp_path .'/wp-config.php');
include_once($wp_path .'/wp-load.php');
include_once($wp_path .'/wp-includes/wp-db.php');

$myBase = plugins_url("", __FILE__);

?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/jquery-ui.min.js"></script>
<script src="<?php echo $myBase; ?>/booklet/jquery.easing.1.3.js"></script>
<script src="<?php echo $myBase; ?>/booklet/jquery.booklet.latest.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $myBase; ?>/booklet/jquery.booklet.latest.css?ver=3.5" media="screen">        
</head>
<body>
<?php

$atts = json_decode( stripslashes($_GET['data']) , true);
$atts['lightbox'] = "0"; //kill the lightwindow variable to prevent loop in ->display
$atts['iframe'] = true;

$pf = new page_flipper();
echo $pf->display($atts);

?>
</body>