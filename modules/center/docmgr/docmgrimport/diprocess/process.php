<?php

$action = $_REQUEST["action"];
$path = $_REQUEST["path"];

$import = new IMPORT($path);

if ($action=="browse") 
{

	$import->browse();
  
} else if ($action=="merge") 
{

	$import->merge();

} else if ($action=="thumb") 
{

	$import->thumb();
  
} else if ($action=="delete") 
{

	$import->delete();

} else if ($action=="rename") 
{

	$import->rename();

} else if ($action=="advedit") 
{

	$import->advedit();

} else if ($action=="rotate") 
{

	$import->rotate();

} else if ($action=="commit") 
{

	$import->commit();
  
}


$moduleError = $import->getError();