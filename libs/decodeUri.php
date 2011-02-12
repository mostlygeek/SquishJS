<?php

/**
 * Takes the URI sent by the user and returns a list of the filenames
 *
 * @param string $uri
 * @param string $type (js|css)
 * @return array || false on failure
 */
function decodeUri($uri, $type='js')
{

    $uri = trim($uri);
    if ($uri == '/') {
        return array(); 
    }
    
    $parts = explode('|', urldecode($uri));

    $fetchQueue = array();

    foreach ($parts as $part) {

        $part = trim($part);

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

    return $fetchQueue;
}