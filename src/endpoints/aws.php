<?php
  require '../../vendor/autoload.php';
  require '../../credentials.php';
  use Aws\DynamoDb\Exception\DynamoDbException;
  use Aws\DynamoDb\Marshaler;
  if($AWS_KEY == null || $AWS_SECRET_KEY == null){
    if(getenv('AWS_KEY') == null || getenv('AWS_SECRET_KEY') == null){
      echo 'No credentials found';
    } else {
      $AWS_KEY = getenv('AWS_KEY');
      $AWS_SECRET_KEY = getenv('AWS_SECRET_KEY');
    }
  }
  $sdk = new Aws\Sdk([
    'region'   => 'us-east-2',
    'version'  => 'latest',
    'credentials' => array(
      'key'=> $AWS_KEY,
      'secret'=> $AWS_SECRET_KEY
    )
  ]);

  $dynamodb = $sdk->createDynamoDb();

  $tableName = 'Hours';

  function getUserPassword($name){
    global $tableName;
    global $dynamodb;
    try {
      $scan_response = $dynamodb->scan(array(
        'TableName' => $tableName,
        'ProjectionExpression' => 'username, password'
      ));
      $data = $scan_response;
    } catch (DynamoDbException $e){
      return null;
    }

    foreach($data["Items"] as $user){
      if($user["username"]["S"] == $name){
        return $user["password"]["S"];
        break;
      }
    }
  }

  // Might give you the requested user data if you pleased the god jeff besos
  function getData(){
    global $tableName;
    global $dynamodb;
    try {
      $scan_response = $dynamodb->scan(array(
        'TableName' => $tableName,
        'ProjectionExpression' => 'signedIn, lastTime, totalTime, username'
      ));
      return $scan_response;
    } catch (DynamoDbException $e){
      return null;
    }
  }

  // Might give you the requested user data if you pleased the god jeff besos
  function getUser($password){
    global $tableName;
    global $dynamodb;
    $key = ['password' => ['S' => strval($password)]];
    $params = [
      'TableName' => $tableName,
      'Key' => $key
    ];
    try {
      $result = $dynamodb->getItem($params);
      return $result['Item'];
    } catch (DynamoDbException $e) {
      return null;
    }
  }

  // Might add a user, might not. 1 in 100000000000 chance to not work
  function addUser($username, $password){
    global $tableName;
    global $dynamodb;
    $item = [
      'password' => ['S' => strval($password)],
      'username' => ['S' => strval($username)],
      'lastTime' => ['N' => strval(time())],
      'totalTime' =>['N' => "0"],
      'signedIn' => ['BOOL' => false],
      'sessions' => ['L' => []]
    ];
    $params = [
      'TableName' => $tableName,
      'Item' => $item
    ];
    $dynamodb->putItem($params);
  }

  // Handle sessions seperately because of their funky format
  function addSession($password, $session){
    global $tableName;
    global $dynamodb;

    $key = ['password' => ['S' => strval($password)]];
    $params = [
      'TableName' => $tableName,
      'Key' => $key,
      'UpdateExpression' => 
        'set #sessions = list_append(if_not_exists(#sessions, :empty_list), :session)',
      'ExpressionAttributeNames' => ["#sessions" => "sessions"],
      'ExpressionAttributeValues' => $session
    ];
    $dynamodb->updateItem($params);
  }

  // changes the password and sends the old one to jeff bezos
  function changePassword($password, $newPassword){
    global $tableName;
    global $dynamodb;

    $user = getUser($password);
    $user['password']['S'] = strval($newPassword);
    $key = ['password' => ['S' => strval($password)]];
    $newUser = [
      'TableName' => $tableName,
      'Item' => $user
    ];
    $oldUser = [
      'TableName' => $tableName,
      'Key' => $key
    ];
    $dynamodb->putItem($newUser);
    $dynamodb->deleteItem($oldUser);
  }

  // Updates the user to windows 10
  function updateUser($password, $data){
    global $tableName;
    global $dynamodb;

    $key = ['password' => ['S' => strval($password)]];
    $params = [
      'TableName' => $tableName,
      'Key' => $key,
      'UpdateExpression' => 
          'set signedIn=:signedIn, lastTime=:lastTime, totalTime=:totalTime',
      'ExpressionAttributeValues'=> $data
    ];
    $dynamodb->updateItem($params);
  }

  function isFriday($time){
    return date('N', $time) == 6;
  }

  function countFridays($password){
    $marshaler = new Marshaler();
    $sessions = $marshaler->unmarshalValue(getUser($password)["sessions"]);
    $fridays = 0;
    foreach($sessions as $session)
      if(isFriday($session["date"]))
        $fridays ++;
    return $fridays;
  }
?>
