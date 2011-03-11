<?php

/**********************************************************************
	FILENAME:	email.php
	PURPOSE:	contains generic functions used for sending an email
	MODIFIED:	06/22/2007 -> descriptions added to functions
**********************************************************************/


/**************************************************************
	FUNCTION:   assembleEmail
	PURPOSE:		takes all passed info and assembles the email headers
	INPUTS:			to -> who we are sending to
							from -> who's it coming from
							subject -> email subject
							message -> email message
							attachArray -> assoc array of attachments
								path -> path to file
								name -> name of file
								type -> mime type
								description -> file description
							replyTo -> replies go here
							toFile -> write the email headers to a file
							cc -> carbon copy recips
							bcc -> blind carbon copy recips
		RETURNS:	string -> email headers ready for sending
***************************************************************/
function assembleEmail($to,$from,$subject,$message,$attachArray = null,$replyTo=null,$toFile=null,$cc = null, $bcc = null) {

	//trim returnAddr to try and prevent the ATTACK messages
	$from = trim($from);
	$to = trim($to);
	$cc = trim($cc);
	$bcc = trim($bcc);

	//if the to,cc, or bcc are using ";" for separators, replace with ","
	if (strstr($to,";")) $to = str_replace(";",",",$to);
	if ($cc && strstr($cc,";")) $cc = str_replace(";",",",$cc);
	if ($bcc && strstr($bcc,";")) $cc = str_replace(";",",",$bcc);
	
	//add From: header
	$headers = "From: ".$from."\r\n";

	//add optional carbon copy headers
	if ($cc) $headers .= "Cc: ".$cc."\r\n";
	if ($bcc) $headers .= "Bcc: ".$bcc."\r\n";
		
	//add a reply to header if set
	if ($replyTo) $headers .= "Reply-To: ".$replyTo."\r\n";
	else if (defined("EMAIL_REPLYTO")) $headers .= "Reply-To: ".EMAIL_REPLYTO."\r\n";

	//specify MIME version 1.0
	$headers .= "MIME-Version: 1.0\r\n";

	//process for a plain-text message
	if (!is_array($attachArray)) $headers .= createEmailHeaders($message);

	//process for an email with an attachment	
	else {

		//unique boundary
		$boundary = uniqid("----=_NextPart_").".EW_----";

		//tell e-mail client this e-mail contains//alternate versions
		//note that enclosing the boundary in quotes allows MIME email
		//to work with AOL.
		$headers .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"\r\n";
		$headers .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

		//message to people with clients who do not understand MIME
		$headers .= "This is a multi-part message in MIME format.\r\n";

		//add the text or html part of the message
		$headers .= "--$boundary\r\n";
		$headers .= createEmailHeaders($message);

		//loop through and add all attachments
		foreach ($attachArray AS $attach) 
		{

			$attachPath = $attach["path"];
			$attachName = $attach["name"];
			$attachDesc = $attach["description"];
			$attachType = $attach["type"];
			$attachCid = $attach["cid"];
			
			//get the file as a string and encode
			$h = fopen($attachPath,"rb");
			$contents = fread($h,filesize($attachPath));
			fclose($h);

			$FILE = chunk_split(base64_encode($contents));

			$headers .= "--$boundary\r\n";

			$headers .= "Content-Transfer-Encoding: base64\r\n";

			//if there's a cid, make it an inline attachment
			if ($attachCid) {

				$info = fileInfo($attachName);
				$headers .= "Content-Id: <".$attachCid.">\r\n";
				$headers .= "Content-Type: ".$info["mime_type"]."; name=\"".$attachName."\"\r\n";

			} else {

				$headers .= "Content-Type: application/octet-stream; name=\"".$attachName."\"\r\n";
				$headers .= "Content-Disposition: attachment; filename=\"".$attachName."\"\r\n";
			
			}

			$headers .= "\r\n";
			$headers .= $FILE;

		}

		$headers .= "\r\n--$boundary--\r\n";
		
	}

	//if toFile is set, set this up to write the entire email to a file
	//prepend the destination and subject to the headers
	$to = "To: ".$to."\r\n";
	$subject = "Subject: ".$subject."\r\n";
		
	$headers = $to.$subject.$headers;

	return $headers;

}

/****************************************************************
	FUNCTION:	send_email
	PURPOSE:	actually sends email to designated recipients
	INPUTS:			to -> who we are sending to
							from -> who's it coming from
							subject -> email subject
							message -> email message
							attachArray -> assoc array of attachments
								path -> path to file
								name -> name of file
								type -> mime type
								description -> file description
							replyTo -> replies go here
							toFile -> write the email headers to a file
							cc -> carbon copy recips
							bcc -> blind carbon copy recips
		RETURNS:	string -> email headers we sent
****************************************************************/
function send_email($to,$from,$subject,$message,$attachArray=null,$replyTo = null, $cc = null, $bcc = null) {

	$subject = stripslashes($subject);

	//stop here if in training mode
	if (defined("TRAINER")) return false;

	//use php_imap if available, otherwise use sendmail
	//DISABLED: sendmail option seems to work better for setting return addresses
	if (function_exists("imap_mail") && 1==2) {

		$headers = assembleEmail($to,$from,$subject,$message,$attachArray,$replyTo,null,$cc,$bcc);

		if (imap_mail("$to","$subject","","$headers","")) return true;
		else return false;

	} else {

		$headers = assembleEmail($to,$from,$subject,$message,$attachArray,$replyTo,1,$cc,$bcc);

		//write our headers to a temp file for passing to sendmail	
		$file = TMP_DIR."/".rand().".eml";
	
		$fp = fopen($file,w);
		fwrite($fp,$headers);
		fclose($fp);

		//pass the file to sendmail
		`cat "$file" | sendmail -t -f "$from"`;

		//remove the temp file and exit
		return $headers;

	}
}

/*****************************************************************
	FUNCTION:	createEmailHeaders
	PURPOSE: 	creates heades for the message portion of our email.
						creates an html version and a plain text version
	INPUTS:		msg -> message to send
	RETURNS:	string -> message in header form
*****************************************************************/
function createEmailHeaders($msg) {

	//encode the message for html
	//if (function_exists("imap_8bit")) $HTML = imap_8bit("$msg");
	$HTML = $msg;

	if (!defined("DBENCODING")) define("DBENCODING","ISO-8859-1");

	//get rid of all formatting in the message
	$msg = preg_replace("/<br>/i","\r\n",$msg);
	$TEXT = strip_tags($msg);

	$headers = null;
	
	if ($HTML) {
	
		//tell e-mail client this e-mail contains//alternate versions
		//note that enclosing the boundary in quotes allows MIME email
		//to work with AOL.
		$headers .= "Content-Type: text/html; charset=".DBENCODING."; format=flowed\r\n";
		$headers .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";

		if (function_exists("quoted_printable_encode")) 
			$headers .= quoted_printable_encode($HTML);
		else if (function_exists("imap_8bit"))
			$headers .= imap_8bit($HTML);
		else
			$headers .= $HTML;
			
		$headers .= "\r\n\r\n";
	
	} else {
	
		//tell e-mail client this e-mail contains//alternate versions
		//note that enclosing the boundary in quotes allows MIME email
		//to work with AOL.
		$headers .= "Content-Type: text/plain; charset=".DBENCODING."; format=flowed\r\n";
		$headers .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
		$headers .= $TEXT;
		$headers .= "\r\n\r\n";

	}

	return $headers;

}

