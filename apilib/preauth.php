<?php

//for guests to view files directly
if (array_key_exists("viewobj",$_REQUEST))
{

  include("app/common-docmgr.php");
  directViewObject($_REQUEST["viewobj"]);

}


/***********************************************
  FUNCTION:	__autoload
  PURPOSE:  autoload classes for the api
***********************************************/
function edev_autoload($class_name)
{

  //don't allow any non-alphanumeric info since class names can be passed from the url
  $class = preg_replace("/^a-z0-9_-/i","",$class_name);
 
  //if they don't match, let the user know so they don't get confused
  if ($class!=$class_name)
  {
    throw new Exception('Class '.$class_name.' not exists');
  }
  
  $cn = strtolower($class_name);
  $file = "apilib/lib/".str_replace("_","/",$cn.".php");

  if (!file_exists($file)) 
  {
    throw new Exception('Class '.$class_name.' not exists');
  }
  
  require_once($file);

}

spl_autoload_register('edev_autoload');