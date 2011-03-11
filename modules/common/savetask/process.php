<?php

//get our taskId and init the class
$taskId = $_REQUEST["taskId"];
$contactId = $_REQUEST["contactId"];
$contractId = $_REQUEST["contractId"];

//init our task object
$task = new TASK($taskId);

//get a contact id if necessary
if ($contactId) $task->setContactId($contactId);

//if an error was created initting the task, stop here
if ($errorMessage = $task->getError()) return false;

//save the task
if ($_REQUEST["action"]=="saveTask") {

  $task->save();
  if (!$taskId) $taskId = $task->getId();			//get the id in case we were creating new task
  
//save notes only
} else if ($_REQUEST["action"]=="saveNotes") {

  $task->saveNotes();

} else if ($_REQUEST["action"]=="saveObject") {

  $task->saveObject();

//mark complete or incomplete
} else if ($_REQUEST["action"]=="setcomplete") {

  if ($_REQUEST["complete"]=="f") $task->markIncomplete();
  else $task->markComplete($_REQUEST["notes"]);

//delete
} else if ($_REQUEST["action"]=="deleteTask") {

  $task->delete();

//have no idea what they sent to us
} else $errorMessage = "Invalid action specified";

//look for error messages from our process above
if (!$errorMessage) {

  $errorMessage = $task->getError();

}                      
