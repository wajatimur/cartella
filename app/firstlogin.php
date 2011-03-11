<?php

function checkDefaultDashboard()
{

  global $DB;

  $sql = "SELECT account_id FROM dashboard WHERE account_id='".USER_ID."'";
  $info = $DB->single($sql);
  
  if (!$info)
  {
  
    //get the dashboard from the Everyone group and use it
    $sql = "SELECT * FROM group_dashboard WHERE group_id='0'";
    $gd = $DB->fetch($sql);

    for ($i=0;$i<$gd["count"];$i++)
    {
    
      //use the info, remove the group_id and use our new account id
      $opt = $gd[$i];
      unset($opt["group_id"]);
      $opt["account_id"] = USER_ID;
      $DB->insert("dashboard",$opt);
          
    }  
  
  }

}

function checkDocmgr() {

  $d = new DOCMGR_OBJECT("/Users/".USER_LOGIN);
  $info = $d->getInfo();

  if (!$info)
  {

    $arr = null;
    $arr["object_type"] = "collection";
    $arr["parent_path"] = "/Users";
    $arr["name"] = USER_LOGIN;
    $arr["mkdir"] = 1;
    $arr["noinherit"] = 1;
    $arr["protected"] = 1;

    $d = new DOCMGR_OBJECT($arr);
    $d->save();

    //bail, something is wrong config wise if this doesn't work
    if ($d->getError()) 
    {
      print_r($d->getError());
      die;
    }

  }

  //setup our bookmarks
  $b = new DOCMGR_BOOKMARK();
  $info = $b->get();
  
  $u = null;
  $r = null;

  //if there are some bookmarks for this user, make sure we have the required ones
  if ($info["bookmark"]) 
  {

    foreach ($info["bookmark"] AS $b) 
    {
      if ($b["object_path"]=="/Users/".USER_LOGIN) $u = 1;
      if ($b["object_path"]=="/") $r = 1;
    }

  }

  //create the ones we are missing
  if (!$u) 
  {
  
    $arr = null;
    $arr["path"] = "/Users/".USER_LOGIN;
    $arr["name"] = USER_LOGIN;
    $arr["expandable"] = "t";
    $arr["protected"] = "1";

    $b = new DOCMGR_BOOKMARK($arr);
    $b->save();

  }
  
  //if they can create or browse root, but there is no root, make one
  if (!$r && (PERM::check(CREATE_ROOT) || PERM::check(BROWSE_ROOT))) 
  {

    $arr = null;
    $arr["path"] = "/";
    $arr["name"] = ROOT_NAME;
    $arr["expandable"] = "t";
    $arr["protected"] = "1";

    $b = new DOCMGR_BOOKMARK($arr);
    $b->save();
  
  }

}

