<?php
require('aws.php');
require('cors.php');
cors();
use Aws\DynamoDb\Marshaler;
$marshaler = new Marshaler();
echo json_encode($marshaler->unmarshalItem(getUser($_REQUEST['password'])));
?>
