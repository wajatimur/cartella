<?php

function sendMsg() {

  $attach = array();
  
  //get our email information
  $to = $_POST["to"];
  $subject = $_POST["subject"];
  $msg = fixDoctype($_POST["editor_content"]);
  $cc = $_POST["cc"];
  $bcc = $_POST["bcc"];

  //attach all of our objects
  handleAttachments($attach);
  handleEmailAttachments($attach,$msg);
  handleDocmgrAttachments($attach,$msg);

  //default from
  $from = USER_FN." ".USER_LN." <".USER_EMAIL.">";

  $errorMessage = null;
  $successMessage = null;

  //make sure we have our required stuff to send
  if (!$to) $errorMessage = "No recipient specified";
  if (!$from) $errorMessage = "No sender specified";

  //send the email
  $ret = send_email($to,$from,$subject,$msg,$attach,null,$cc,$bcc);

  if ($ret) {

    //delete our attachments
    $dir = TMP_DIR."/".USER_LOGIN."/email/*";
    `rm $dir`;

    //clear the temp directory
    $d = new DOCMGR_OBJECT();
    $d->emptytemp();

    return true;
    
  } return false;
  
}

function getEmailImages($msg) {

  $imgs = array();

  preg_match_all('/\<img.+?src="(.+?)".+?\/>/', $msg, $matches);
  $n = 0;
  $matcharr = $matches[1];
    
  for ($i=0;$i<count($matcharr);$i++) {

    //skip regular links
    if (!strstr($matcharr[$i],"objectId=")) continue;

    //remove the server and everything else before "?"
    $pos = strpos($matcharr[$i],"?");
    if ($pos===FALSE) continue;
    $str = substr($matcharr[$i],$pos+1);

    //extract the objectid from the source    
    $objectId = null;
    parse_str($str);

    //if there was an objectid in the string, save it
    if ($objectId) {
      $imgs[$n] = array();
      $imgs[$n]["src"] = $matcharr[$i];
      $imgs[$n]["object_id"] = $objectId;
      $n++;
    }

  }

  return $imgs;

}

function handleEmailAttachments(&$attach,&$msg) {

  //first we need to pull all images and objects from this email
  $imgarr = getEmailImages($msg);

  if (count($imgarr)==0) return false;

  //our directory for storing the attachments
  $dir = TMP_DIR."/".USER_LOGIN."/email";
  $c = count($attach);
    
  foreach ($imgarr AS $img) 
  {

    $d = new DOCMGR_OBJECT($img["object_id"]);
    $info = $d->getInfo();
    $data = $d->getContent();

    //make sure it's not already attached
    if (!checkAttached($attach,$dir."/".$info["name"])) continue;

    //write the file to our temp directory
    file_put_contents($dir."/".$info["name"],$data);

    $ext = fileExtension($info["name"]);
    $cid = md5(uniqid()).".".$ext;


    //add to the attachment array
    $attach[$c]["path"] = $dir."/".$info["name"];
    $attach[$c]["name"] = $info["name"];
    $attach[$c]["cid"] = $cid;
    $c++;

    //now replace the original source in the message with the inline cid source
    $msg = str_replace($img["src"],"cid:".$cid,$msg);
            
  }

}

function handleDocmgrAttachments(&$attach,&$msg) {

  //stop here if nothing to do
  if (!$_REQUEST["docmgrAttachments"]) return false;

  //our directory for storing the attachments
  $dir = TMP_DIR."/".USER_LOGIN."/email";

  //loop through and get our files
  $docarr = explode(",",$_REQUEST["docmgrAttachments"]);
  $c = count($attach);

  foreach ($docarr AS $docId) 
  {

    $d = new DOCMGR_OBJECT($docId);
    $info = $d->getInfo();
    $data = $d->getContent();

    //log that each one was sent
    logEvent(OBJ_EMAILED,$docId);
   
    //make sure it's not already attached
    if (!checkAttached($attach,$dir."/".$info["name"])) continue;

    //if a document, extract content from the xml
    if ($info["object_type"]=="document") $info["name"] .= ".html";

    //write the file to our temp directory
    file_put_contents($dir."/".$info["name"],$data);

    //add to the attachment array
    $attach[$c]["path"] = $dir."/".$info["name"];
    $attach[$c]["name"] = $info["name"];
    $c++;

  }

}

function checkAttached($attach,$path) {

  $num = count($attach);
  $ret = true;
  
  for ($i=0;$i<$num;$i++) {
    if ($attach[$i]["path"]==$path) {
      $ret = false;
      break;
    }
    
  }
  
  return $ret;
}

function handleAttachments(&$attach) {

  //attachments are in the user's temp directory
  $files = @scandir(TMP_DIR."/".USER_LOGIN."/email");

  //remove directory markers
  array_shift($files);
  array_shift($files);
  
  if (count($files)>0) {

    //prepend the directory name to the file
    for ($i=0;$i<count($files);$i++) {

      //setup our attachment array using the files in our temp directory
      $attach[$i]["path"] = TMP_DIR."/".USER_LOGIN."/email/".$files[$i];
      $attach[$i]["name"] = $files[$i];
            
    }

  }

}
