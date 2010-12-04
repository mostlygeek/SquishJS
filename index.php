<?php
/**
 * Demo of squish js... will take a URL to a js file
 * and run it through jsmin
 *
 */
require_once('jsmin.php');
header('Content-Type: text/javascript');

if (!isset($_GET['f'])) {
    echo '/** no file defined **/';
    die();
}

// load the file from the web
$url = $_GET['f'];
$start = microtime(true);
$file = file_get_contents($url);
$min = jsMin::minify($file);
$end = round(microtime(true) - $start, 3);

?>
/**
 * Minified by squishjs Alpha (http://someurl.com)
 * Date: <?php echo date('Y-m-d H:m:s'); echo "\n"?>
 * Speed: <?php echo $end?>s
 */
<?php echo $min; ?>