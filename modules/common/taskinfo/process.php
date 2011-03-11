<?php

$taskId = $_REQUEST["taskId"];

//get our taskId and init the class
$taskId = $_REQUEST["taskId"];
$task = new TASK($taskId);

//if an error was created initting the task, stop here
if ($errorMessage = $task->getError()) return false;

//get our info for this task
$taskInfo = $task->getTask();

//make sure no errors were thrown
$errorMessage = $task->getError();
