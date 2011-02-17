<?php
require_once('../libs/aws/sdk-1.2.3/sdk.class.php');
require_once('../libs/aws/sdk-1.2.3/services/sdb.class.php');

$sdb = new AmazonSDB();
//$response = $sdb->create_domain('squishjs');

//$response = $sdb->put_attributes('squishjs', 'test-item1', array('a' => 1, 'b' => 'hi'));
//$response = $sdb->put_attributes('squishjs', 'test-item2', array('a' => 1, 'b' => 'hi'));
//$response = $sdb->put_attributes('squishjs', 'test-item3', array('a' => 1, 'b' => 'hi'));
//print_r($response);


$response = $sdb->select('select * from squishjs');
$results = $response->body->SelectResult;

echo count($results->Item); echo "\n";
print_r($results->Item[1]);
