<?php

if ($_POST["action"]=="sendEmail") {

  $check = sendMsg();
  if (!$check) $errorMessage = "Error sending email";
  else $successMessage = "emailsuccess";
      
}
