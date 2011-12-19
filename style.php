<?php
header("Content-type: text/css");
header("Cache-Control: must-revalidate");
$offset = 72000 ;
$ExpStr = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
header($ExpStr);
//echo "in the css file<br>";
if(isset($_GET['ver'])) {
	$css=explode('|', $_REQUEST['ver']);
}
/* it seems silly, but need to white list and validate passed parameters, again */
if(isset($css[1]) && $css[1] > '0' && $css[1] < '11') {
	$thumbPadding = $css[1];
} else {
	$thumbPadding = 0;
}
echo "# version=$css[0]\n";
echo "# thumbPadding=$css[1]\n";
$size_0 = 80+(2*$thumbPadding).'px';
$size_1 = 60+(2*$thumbPadding).'px';
$size_10 = 120+(2*$thumbPadding).'px';
$size_11 = 200+(2*$thumbPadding).'px';
$size_2 = 400+(2*$thumbPadding).'px';
echo <<<CSS
#content .zfp_0, .zfp_0 {width: $size_0; height: $size_0;}
#content .zfp_1, .zfp_1 {width: $size_1; height: $size_1;}
#content .zfp_10, .zfp_10 {width: $size_10; height: $size_10;}
#content .zfp_11, .zfp_11 {width: $size_11; height: $size_11;}
#content .zfp_2, .zfp_2 {width: 400px; height: 400px;}
#content .zfp_3, .zfp_3 {width: 580px; height: 450px;}
#content .zfp_4, .zfp_4 {width: 800px; height: 630px;}
#content .zfp_5, .zfp_5 {width: 1100px; height: 850px;}
#content .zfp_6, .zfp_6 {width: 1550px; height: 960px;}
#content .zfp_frame, .zfp_frame {font-size: 0; border: 0; padding:0; margin:0;}
#content table.zfp_frame,table.zfp_frame {float: left; border-collapse: collapse; margin: 0; background: none; background-color: transparent; border: 0;}
#content td.zfp_frame,td.zfp_frame {text-align: center; line-height: 0; vertical-align: middle; background: none; background-color: transparent; border: 0;}
CSS;
?>