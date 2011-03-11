<?php

$GLOBALS["attempts"] = 0;

while ($GLOBALS["attempts"]<2) {

  $ret = processRequest($allowedProxy);

  //if successfull, processReturn will die.  if not, our loop will run again
  processReturn($ret);
  
}

die("Process failed");

function processReturn($ret) {

	$key = $_REQUEST["proxy"];

	if (strstr($ret->headers["Content-Disposition"],"attachment")!=NULL) {

	  // send headers to browser to initiate file download
	  header ("Content-Type: application/octet-stream");
	  header ("Content-Type: ".$ret->headers["Content-Type"]);
	  header ("Content-Length: ".$ret->headers["Content-Length"]);
	  header ("Content-Disposition: ".$ret->headers["Content-Disposition"]);
	
	  header ("Content-Transfer-Encoding: ".$ret->headers["Content-Transfer-Encoding"]);
	  header ("Cache-Control: ".$ret->headers["Cache-Control"]);
	  header ("Pragma: ".$ret->headers["Pragma"]);
	
	  die($ret->body);
	
	} else if ($ret->headers["Content-Type"]=="text/xml") {

	  header("Content-Type: text/xml");

	  //convert our xml to an array so we can extract the session id
    $arr = XML::decode($ret->body);
    $_SESSION["apiSession"][$key] = $arr["session_id"];

	  die($ret->body);
	
	} else {

	  //we got booted, try again
	  $GLOBALS["attempts"]++;
	  
	  $check = substr($ret->body,0,100);

    if (stristr($check,array("Warning:","ERROR_SQL","Parse Error","parser error","Fatal error"))) {
	  	header("Content-Type",$ret->headers["Content-Type"]);
	  	die($ret->body);
    }
    die($ret->body);

    return false;
	
	}
	
}
        


function processRequest($allowedProxy) {

	//make sure this url is setup
	$key = $_REQUEST["proxy"];
	$url = $allowedProxy[$key];
	
	$xml = $_REQUEST["apidata"];

	//setup our session saver
	if (!$_SESSION["apiSession"][$key]) {
	  $uid=USER_ID;
	  $_SESSION["apiSession"][$key] = array();
	}

	//relay the entire request variable as post data	
	$postdata = $_REQUEST;

	//use a saved session id if available
	if ($_SESSION["apiSession"][$key]) {
	  if (strstr($url,"?")) $url .= "&sessionId=".$_SESSION["apiSession"][$key];
	  else $url .= "?sessionId=".$_SESSION["apiSession"][$key];
	} else {
	  $postdata["login"] = USER_LOGIN;
	  $postdata["password"] = USER_PASSWORD;
	  $_SESSION["apiSession"][$key] = null;
	}

	/*
	//if there is a file, do this a bit differently
	if ($_FILES["uploadfile"]) {

		$filearr = array();

		$url .= "&module=api&postUploadFile=1";
		$file = $_FILES["uploadfile"]["tmp_name"];
		http_put_file($url,$_FILES["uploadfile"]["tmp_name"],null,$info);
		die("success");
	
	}
	*/
	
	//send all our data to the api module on the destination host
	return http_parse_message(http_post_fields($url,$postdata)); 
	
}
	