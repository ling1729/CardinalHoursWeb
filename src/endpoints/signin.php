<?php
require('sheets.php');
require('datafuncs.php');

// start tracking time on signin
if (isset($_REQUEST['password'])) {
  echo getUserData($_REQUEST['password'])[2];
  if(getUserData($_REQUEST['password'])[2] == FALSE){
    echo getUserData($_REQUEST['password'])[0];
  }
}

?>
