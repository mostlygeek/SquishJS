<?php
/**
 * Demo of squish js... will take a URL to a js file
 * and run it through jsmin
 *
 */
require_once('jsmin.php');
header('Content-Type: text/javascript');

$errors = array();
if (!isset($_GET['f'])) {
    $errors[] = "JavaScript file not defined";
} else {
    $url = $_GET['f'];
}

// load the file from the web
$parts = parse_url($_GET['f']);

if ($parts === false) {
    $errors[] = 'Invalid URL format';
}


if (is_array($parts)) {
    
    if (!in_array($parts['scheme'], array('http', 'https'))) {
        $errors[] = 'Protocol must be http or https';
    }

    if (substr($parts['path'], -2) != 'js') {
        $errors[] = 'File must end in .js'; 
    }
}

if (empty($errors)) {

    $url = $parts['scheme'].'://'.$parts['host'].$parts['path'];
    $start = microtime(true);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second max
    curl_setopt($ch, CURLOPT_USERAGENT, 'SquishJS');

    $js = curl_exec($ch);
    $fetch = round(microtime(true) - $start, 3);

    $info = curl_getinfo($ch);
    
    if (curl_error($ch)) {
        $errors[] = 'Unable to fetch JS File';
    }

    if ($info['http_code'] != '200') {
        $errors[] = 'HTTP response code != 200'; 
    }
    
    curl_close($ch);

    if (empty($errors)) {
        $start = microtime(true);
        $min = jsMin::minify($js); // boy this is slow
        $minTime = round(microtime(true) - $start, 3);

        $olen = strlen($js);
        $nlen = strlen($min);
        $pct = round($nlen / $olen * 100);
    }
}

if (count($errors) > 0) {
    echo "/**\n * ERRORS: \n";
    echo " * -------------------\n";
    foreach ($errors as $key => $msg) {
        printf(" * % 4s) %s\n", $key+1, $msg);
    }
    echo " **/";
    die();
}

?>
/**
 * Minified by squishjs (oh so Alpha)
 * http://squishjs.com
 * 
 * Minified : <?php echo date('Y-m-d H:m:s'); echo "\n"?>
 * Fetch    : <?php echo $fetch?>s
 * Min Time : <?php echo $minTime?>s
 * Size     : <?php echo $pct."% of original\n"; ?>
 */
<?php echo $min; ?>