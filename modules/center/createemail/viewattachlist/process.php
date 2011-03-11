<?php

/*******************************************************************
  MODULE: viewattachlist
  PURPOSE:  displays all current attachments for a user to be sent
            in an email
*******************************************************************/

$dir = TMP_DIR."/".USER_LOGIN."/email";

if (is_dir($dir)) {

  $dirlist = scandir($dir);

  //get rid of directory markers (always first two entries)
  array_shift($dirlist);
  array_shift($dirlist);

} else {
  
  //make sure the directories even exist
  @mkdir(TMP_DIR."/".USER_LOGIN);
  @mkdir(TMP_DIR."/".USER_LOGIN."/email");

  //return an empty dir array
  $dirlist = array();

}
