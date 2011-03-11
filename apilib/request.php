<?php

/*****************************************************************************************************
  get our passed data.
  data can either be passed in xml via the apidata= parameter, or as a request variable.
  To pass arrays of data, either use field[] notation for request vars, or use the
  variable name twice in xml (i.e: <data><object_id>1</object_id><object_id>2</object_id></data>)
  All xml data must be encompassed in a root tag, like "<data>" in the above example
******************************************************************************************************/

//make sure magic quotes is disabled
if (get_magic_quotes_gpc()==1)
{
	$PROTO->add("error","Magic quotes is enabled.  It must be disabled in your php.ini file");
  return false;
}
    
//try to pull from apidata.  If nothing, default to request (for socket connections)
if ($_REQUEST["apidata"]) 
{

  $apidata = $_REQUEST["apidata"];

  //if this is obviously xml, switch modes
  $pos = stripos($apidata,"<data>");
  
  if ($pos!==FALSE && $pos=="0")
  {
    $PROTO->setProtocol("XML");
  }

//just use the request variables as our input
} else if (!$apidata || !is_array($apidata)) $apidata = $_REQUEST;

//if we have data to process, run with it
if ($apidata)
{

	//decode the command string to an array
	if (!is_array($apidata)) $apidata = $PROTO->decode($apidata);

	//write our api traffic to the log
	if (defined("DEBUG"))
	{
	
		$debug = var_export($apidata,true);
	
		file_put_contents("/tmp/api.log","\n============ REQUEST ==============\n".$debug."\n",FILE_APPEND);

	}
	
	/*************************************************************
	  parse out our command
	*************************************************************/
	if ($apidata["command"])
	{
	
	  //parse out the cmdarr to its separate components
	  $cmdarr = explode("_",$apidata["command"]);

	  //continue only if we have the proper app_object_method structure
	  if (count($cmdarr) == 3)
	  {

	    //subclass name
	    $class = $cmdarr[0]."_".$cmdarr[1];

	    //method
			$mn = $cmdarr[2];
			
    	//keep going
	    $sub = new $class($apidata);

	    //call our requested method
			if (!$sub->getError())
			{		
		    $sub->$mn();
			}	        

			if ($sub->getError())
			{
				$PROTO->add("error",$sub->getError());
			}
			//check for common output
			else 
			{
				$sub->showCommon();
			}

	  } 
	  else if ($apidata["command"]=="keepalive")
	  {
	  
	    //keep the dream alive.  dummy entry to keep session from expiring
	
	  //didn't use the proper command structure  
	  } else
	  {
	  
	    $PROTO->add("error","Error.  Invalid command structure used.  Must follow \"app_object_method\" structure\"");;
	  
	  }
	
	//no command found, bail
	} else 
	{
	
	  $PROTO->add("error","API Command not specified");
	  
	}
	
	//always return the session id
	$PROTO->add("session_id",session_id());

	$debug = var_export($PROTO->getData(),true);
	
	file_put_contents("/tmp/api.log","\n=========== RESPONSE ===============\n".$debug."\n",FILE_APPEND);
	
}
