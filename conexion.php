<?php

$sql_serverName = "localhost";
$sql_database = "crediweb";
$sql_user = "root";
$sql_pwd = "";

// $sql_serverName = "50.87.184.179";
// $sql_database = "wsoqajmy_crediweb";
// $sql_user = "wsoqajmy_jorge";
// $sql_pwd = "Equilivre3*";


try {
  $pdo = new PDO("mysql:host=".$sql_serverName.";dbname=".$sql_database,$sql_user,$sql_pwd); 

} catch (PDOException $e) {
  die('Connected failed:' . $e->getMessage());
}
