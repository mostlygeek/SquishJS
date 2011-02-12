<?php
require_once('../libs/decodeUri.php');
header('Content-type: text/javascript');


$now = new DateTime('now', new DateTimeZone('UTC'));
$messages = array('Generated: '.$now->format(DATE_ISO8601));

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
$fetchQueue = decodeUri($uri, 'js');

if (empty($fetchQueue)) {
    $errors[] = 'Nothing to fetch';
    goto ERROR; 
}

foreach ($fetchQueue as $file) {
    if (substr($file, -3) != '.js') {
        $errors[] = "$file does not end in .js"; 
    }
}

if (count($errors)) {
    goto ERROR;
}

// STEP 3: fetch the files remotely (curl-multi-get)


// check that there were no fetch errors
// combine all the source files (if multiple)
// minimize the javascript
// if errors
//   -- return error HTTP code ? 500?
//   --
// return valid, minimized JS. 

goto OUTPUT;

/*** START:: ERROR */
ERROR:
    // see: http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
    header("HTTP/1.0 400 Bad Request");
    header("Status: 400 Bad Request");


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
if ($errors) {
    echo " *\n * Errors:\n"; 
    echo $errors."\n"; 
}
?>
<?php ?>
 */


