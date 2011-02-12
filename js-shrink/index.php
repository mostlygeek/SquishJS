<?php
require_once('../libs/decodeUri.php');
header('Content-type: text/javascript');


$now = new DateTime('now', new DateTimeZone('UTC'));
$messages = array('Generated: '.$now->format(DATE_ISO8601));

$errorHttpCode = 400; 
$errors = array();
$source = ''; 

/**
 * STEP 1. Check sub-domain (user account) is valid and active
 */
$host = str_replace($_SERVER['SERVER_NAME'], '', $_SERVER['HTTP_HOST']); 
if ($host == '') {
    $errors[] = 'Account hostname not found'; 
    goto ERROR;
} else {
    $host = substr($host, 0, -1); // strip the trailing .
    // check that domain/account is valid
    // check that account is active
}

/**
 * STEP 2: Decode the URI for a list of files to fetch + combine
 */
 
$uri = $_SERVER['REQUEST_URI'];
$parts = parse_url($uri);


// @TODO do some mungling with the URI to get only the parts
// we need.
$uri = $parts['path'];


$files = decodeUri($uri, 'js');

if (empty($files)) {
    $errors[] = 'Nothing to fetch';
    goto ERROR; 
}

foreach ($files as $file) {
    if (substr($file, -3) != '.js') {
        $errors[] = "$file does not end in .js"; 
    }
}

if (count($errors)) {
    goto ERROR;
}

// STEP 3: fetch the files remotely (curl-multi-get)

$uriQueue = array();
foreach ($files as $file) {
    $uriQueue[] = 'https://'.$host.$file; 
}

$mh = curl_multi_init();
$handles = array();
foreach ($uriQueue as $uriToFetch) {
    // create a new cURL resource
    $ch = curl_init();

    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_URL, $uriToFetch);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if (curl_multi_add_handle($mh, $ch) !== 0) {
        die('multi add failed');
    } else {
        $handles[] = $ch; // we need these to get the content later
    }
}

$start = microtime(true);
do {
    usleep(10000); // 10ms
    curl_multi_exec($mh, $running);
} while($running > 0);

$total = round(microtime(true) - $start, 4);

foreach ($handles as $ch) {
    $source .= curl_multi_getcontent( $ch );
    curl_multi_remove_handle($mh, $ch);

    $info = curl_getinfo($ch);
    if ($info['http_code'] != '200') {
        $errors[] = "URI: ".$info['url'].', HTTP: '.$info['http_code'];
    }
    curl_close($ch);
}

if ($errors) {
    goto ERROR;
} else {
    $messages[] = 'Files: '.count($uriQueue);
    $messages[] = 'Fetch time: '.$total.'s';
}

/**
 * STEP 4: Uglify the javascript
 */
$descriptor = array(
    0 => array("pipe", "r"),
    1 => array("pipe", "w"),
    2 => array("file", "/tmp/uglify-errors", "a")
);

$cwd = "/tmp";
$start = microtime(true);
$process = proc_open(
        '/var/web-projects/squishjs/uri-parser/uglifyjs -nc',
        $descriptor, $pipes, $cwd);
fwrite($pipes[0], $source);
fclose($pipes[0]);
$minimizedSource = stream_get_contents($pipes[1]);
fclose($pipes[1]);
$total = round(microtime(true)-$start, 4);
$messages[] = 'JS Size: '.strlen($minimizedSource);
/**
 * Do some error checking here...
 */


// combine all the source files (if multiple)
// minimize the javascript
// if errors
//   -- return error HTTP code ? 500?
//   --
// return valid, minimized JS. 

goto OUTPUT; // skip ERROR output

/*** START:: ERROR */
ERROR:
    header("HTTP/1.0 400 Bad Request");
    header("Status: 400 Bad Request"); // for FastCGI

/*** START:: OUTPUT */
OUTPUT:
    // generate messages
    $messages = array_map(function($str) {
        return ' * '.$str; 
    }, $messages);
    
    $messages = implode("\n", $messages);
    
    $errors = array_map(function($str) {
        return ' *  - '.$str; 
    }, $errors);
    $errors = implode("\n", $errors);

?>
/**
<?php if ($messages) {
    echo $messages."\n"; 
}
if ($errors):
    echo " *\n * Errors:\n"; 
    echo $errors."\n";
endif;
?>
 */
<?php if (!$errors) echo $minimizedSource;?>



