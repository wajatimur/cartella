<?php
//preventCache();

$msg = null;
$sizelimit = $_REQUEST["sizelimit"];
$error = null;

if ($_FILES["attachUpload"]) {

  //each user gets their own destination form emails
  $dir = TMP_DIR."/".USER_LOGIN."/email";
  
  if (!is_dir($dir)) {

    //make sure the directories even exist
    @mkdir(TMP_DIR."/".USER_LOGIN);
    @mkdir(TMP_DIR."/".USER_LOGIN."/email");

  }
  
  $dest = $dir."/".$_FILES["attachUpload"]["name"];
  
  if (move_uploaded_file($_FILES["attachUpload"]["tmp_name"],$dest)) {

    //make sure we didn't top the limit
    if ($sizelimit) {

      $arr = scandir($dir);
      array_shift($arr);		//kill directory markers
      array_shift($arr);

      $size = 0;
      foreach ($arr AS $file) $size += filesize($dir."/".$file);

      //if greater than the limit, delete the last file and throw an error
      if ($size>$sizelimit) {
      
        unlink($dest);
        $error = "Error.  Total size of uploaded files exceeds max size limit";
      
      }

    }

  }

}

$uploadErrors = array(
UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the allowed maximum file size.",
UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded.",
UPLOAD_ERR_NO_FILE => "No file was uploaded.",
UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
UPLOAD_ERR_EXTENSION => "File upload stopped by extension.",
);

$errkey = $_FILES["attachUpload"]["error"];

if ($error) $msg = $error;
else if ($errkey=="0") $msg = "uploadsuccess";
else $msg = "Error.  ".$uploadErrors[$errkey];

die($msg);
