<?php
header('Content-type: text/plain');
//print_r($_SERVER);
$req = $_SERVER['REQUEST_URI'];
echo "Raw: $req \n\n";

$req = urldecode($req)."\n";
echo "urldecode: $req\n";

$parts = explode('|', $req);
echo "Parts to Fetch: \n";
//print_r($parts);

$fetchQueue = array();
foreach ($parts as $part) {

    $part = trim($part);
    
    if (substr($part, -3) != '.js') {
        continue;
    }

    if (preg_match('/\[(.*)\]/', $part, $matches)) {
        if (isset($matches[1])) {
            $pattern = $matches[0];
            
            $filenames = explode(',', $matches[1]);
            $filenames = array_map('trim', $filenames);

            $filesToFetch = array_map(function($str) use ($part, $pattern) {
                return str_replace($pattern, $str, $part);
            }, $filenames);

            $filesToFetch = array_map('trim', $filesToFetch);            
        }
    } else {
        // a single file
        $filesToFetch = array($part);
    }
    $fetchQueue = array_merge($fetchQueue, $filesToFetch);
}

$host = str_replace('.localdev.squishjs.com', '', $_SERVER['HTTP_HOST']);


$fetchQueue = array_map(function($path) use ($host) {
    return 'https://'.$host.$path; // need to figure out the scheme 
}, $fetchQueue);

print_r($fetchQueue);

// let's fetch w/ curl
$source = '';

if (false) { // do multifetch
    $mh = curl_multi_init();
    $handles = array(); 
    foreach ($fetchQueue as $filename) {
        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $filename);
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
            print_r(array(
                'url' => $info['url'],
                'http_code' => $info['http_code']
            ));
        }
        curl_close($ch);
    }
    
} else {
    $start = microtime(true);
    foreach ($fetchQueue as $filename) {
        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $filename);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // grab URL and pass it to the browser
        $source .= curl_exec($ch);
        // close cURL resource, and free up system resources
        curl_close($ch);
    }
    $total = round(microtime(true) - $start, 4);
}


echo "Fetch Time: ${total}s\n";
echo "Size: ".strlen($source)."\n\n";

// stdout it to uglify
$descriptor = array(
    0 => array("pipe", "r"),
    1 => array("pipe", "w"),
    2 => array("file", "/tmp/uglify-errors", "a")
);

$cwd = "/tmp";
$start = microtime(true);
$process = proc_open('/var/web-projects/squishjs/uri-parser/uglifyjs -nc', $descriptor, $pipes, $cwd);
fwrite($pipes[0], $source);
fclose($pipes[0]);
$min = stream_get_contents($pipes[1]);
fclose($pipes[1]);
$total = round(microtime(true)-$start, 4);

echo "Min time: ${total}s\n";
echo "Size (After Min): ".strlen($min)."\n";