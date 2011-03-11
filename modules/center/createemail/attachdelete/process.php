<?php

if ($_REQUEST["action"]=="delete") {

  $path = TMP_DIR."/".USER_LOGIN."/email";

  if ($_REQUEST["filename"]) {

    $file = $path."/".$_REQUEST["filename"];
    if (file_exists($file)) unlink($file);
    else $errorMessage = "Error removing file";
  
  } else {
    $errorMessage = "File not specified";
  } 


}
