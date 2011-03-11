<?php

function getLogFromDB($category,$account,$show) {

  global $DB;

  $sql = "SELECT * FROM logger.logs";

  if ($category!="all" || $account!="any") $sql .= " WHERE ";

  if ($category!="all") $sql .= " category='".$category."'";
  if ($category!="all" && $account!="any") $sql .= " AND ";
  if ($account!="any") $sql .= " user_login='".$account."'";

  $sql .= " ORDER BY log_timestamp DESC";
  if ($show!="all") $sql .= " LIMIT ".$show;
  $list = $DB->fetch($sql);

  //convert post and get data to arrays
  for ($i=0;$i<$list["count"];$i++) {
   
    $post = null;
    $get = null;
/*
    if ($list[$i]["post_data"]) {
      $post = @XML::decode("<data>".base64_decode($list[$i]["post_data"])."</data>");
      $list[$i]["post_data"] = array($post);
    } else $list[$i]["post_data"] = null;

    if ($list[$i]["get_data"]) {
      $get = XML::decode("<data>".base64_decode($list[$i]["get_data"])."</data>");
      $list[$i]["get_data"] = array($get);
    } else $list[$i]["get_data"] = null;
    
 */           
  }
              
  return $list;

}

function getLogFromXML() {

  $logfile = file_get_contents(FILE_DIR."/logger/log.xml");
  $logInfoArray = XML::decode(outputXmlHeader().$logfile.outputXmlFooter());
  $logs = $logInfoArray["log"];
 
  return $logs;    
     
}

function setupRequestData($data) {

  $str = null;
  if (count($data)<2) return $str;

  foreach ($data AS $key=>$val) {

    $str .= "<div class=\"requestHeader\">".$key."</div>
              <div class=\"requestContent\">".@wordwrap($val,80,"<br>")."</div>
              ";
  }

  return $str;
    
}
