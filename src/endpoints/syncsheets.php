<?php
require('aws.php');
require('sheets.php');

$hours = $service->spreadsheets_values->get($spreadsheetId, 'Hours')->getValues(); // Gets all relevant data from sheets

function getUserRow($first, $last){
  global $hours;
  $firstNameRow = 0;
  $lastNameRow = 1;
  if (empty($hours)) {
    return 'Uh Oh. Something broke.\n';
  } else {
    $num = count($hours);
    for ($i = 1; $i < $num; $i++) { // Starts at 1 to exclude the top labels
      if($hours[$i][$firstNameRow] == $first && $hours[$i][$lastNameRow] == $last){
        return $i + 1;
      }
    }
  }
}

function syncUser($password){
  // Data of the user from the database
  $dbData = getUser(['password' => ['S' => strval($password)]]);
  $first = explode(' ', $dbData["username"]["S"], 2)[0];
  $last = explode(' ', $dbData["username"]["S"], 2)[1];

  $row = getUserRow($first, $last);

  // Update the hours
  $teamHours = ("'Hours'!") . ("H" . $row);
  changeData([[round($dbData["totalTime"]["N"]/3600, 2)]], $teamHours);

  // Update the meetings
  $meetings = ("'Hours'!") . ("E" . $row);
  changeData([[countFridays($password)]], $meetings);
}
?>
