<?php
header('Content-Type: application/json');

require_once('../vendor/autoload.php');

use App\AmqpWrapper\Database;

$db = new Database();

$entityBody = file_get_contents('php://input');
$data = json_decode(utf8_encode($entityBody), true);

if (json_last_error()) {
  $warning = array("error" => "JSON parsing error: " . json_last_error());
  print json_encode($warning);
  return;
}

$jobid = $data["id"];
$action = $data["action"];
$timestamp = $data["timestamp"];

if($action == "completed") {
  $update = $db->query("UPDATE jobs SET completedAt = :cd WHERE id = :id", 
    array("cd" => $timestamp, "id" => $jobid)
  );

  if($update > 0 ) {
    echo '{ "status" : "ok" }';
  }
} 
else if($action == "check") {
  $db->bind("id", $jobid);
  $id = $db->single("SELECT id FROM jobs WHERE id = :id");

  if($id > 0 ) {
    echo '{ "status" : true }';
  } else {
    echo '{ "status" : false }';
  }
}