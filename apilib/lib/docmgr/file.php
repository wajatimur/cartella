<?php

class DOCMGR_FILE extends DOCMGR_AOBJECT
{

	/**********************************************************************
		CALLED FROM DOCMGR
	**********************************************************************/
	private $fileMode;
	
	/*********************************************************************
	  FUNCTION:	multisave
	  PURPOSE:	arranges multiple submitted files and saves them
	*********************************************************************/
	public function multisave() 
	{
  
		$pathArr = $_FILES['uploadfile']['tmp_name'];
		$nameArr = $_FILES['uploadfile']['name'];  
		$ret = null;

		$this->fileMode = "upload";

		//uploading a file.  this means iframes.  this also means we can't return 
		//messages as anything but XML
		$this->PROTO->setProtocol("XML");

		for ($i=0;$i<count($pathArr);$i++) 
		{

			//preset our data values and go      
			$this->apidata["filepath"] = $pathArr[$i];
			$this->apidata["name"] = $nameArr[$i];
			$this->objectId = null;
			$ret .= $this->save();
  
		}

		return $ret;
			
	}

	/*********************************************************************
		FUNCTION:	import
		PURPOSE:	imports all files from the specified directory on the server
							to the selected parent
	*********************************************************************/
	public function import()
	{

		//first check the directory.  no relative path links, and it must either 
		//be in the import directory or our files directory
		if ( ($this->apidata["directory"]!=IMPORT_DIR && strpos($this->apidata["directory"],FILE_DIR)!="0")
					|| strstr($this->apidata["directory"],"../")) 
	  {
	  
	  	$this->throwError("You are not allowed to import from ".$this->apidata["directory"]);
	  	return false;
	  
	  }

		$arr = scandir($this->apidata["directory"]);
		$num = count($arr);
		
		for ($i=0;$i<$num;$i++)
		{

			$filepath = $this->apidata["directory"]."/".$arr[$i];

			//skip directories
			if (is_dir($filepath)) continue;			

			//set up and import
			$this->objectId = null;
			$this->apidata["filepath"] = $filepath;
			$this->apidata["name"] = $arr[$i];
			$objId = $this->save();

			//largethumb,smallthumb,hugethumb			
			if ($objId)
			{
			
				//get our thumbnails
				$ext = fileExtension($arr[$i]);
				$st = $this->apidata["directory"]."/smallthumb/".str_replace($ext,"png",$arr[$i]);
				$lt = $this->apidata["directory"]."/largethumb/".str_replace($ext,"png",$arr[$i]);
				$ht = $this->apidata["directory"]."/hugethumb/".str_replace($ext,"png",$arr[$i]);

				//delete the file and the thumbnails				
				@unlink($filepath);
				@unlink($st);
				@unlink($lt);
				@unlink($ht);

			}
									
		}
			
	}

	/*********************************************************************
		FUNCTION:	save
		PURPOSE:	master function for saving a file.  creates/updates the
	            		object as necessary and calls the upload processor
	*********************************************************************/
	public function save() 
	{

		$summary = $this->apidata["summary"];

		//if passed a filepath, we are importing from the filesystem instead of uploading from the browser
		if ($this->apidata["filepath"]) 
		{

			$filePath = $this->apidata["filepath"];
			$fileName = $this->apidata["name"];

		} 
		else if ($this->apidata["editor_content"])
		{
		
			$tmpdir = TMP_DIR."/".USER_LOGIN;
			recurmkdir($tmpdir);
			
			$filePath = $tmpdir."/content.txt";
			$fileName = $this->apidata["name"];
		
			//write it to a file to import later	
			file_put_contents($filePath,$this->apidata["editor_content"]);
		
		}
		else 
		{

			$this->fileMode = "upload";

			//if passed file data, handle it
			$filePath = $_FILES["uploadfile"]["tmp_name"];

			//if passed the name in apidata, use it.  otherwise use the FILES value
			if ($this->apidata["name"]) $fileName = $this->apidata["name"];
			else $fileName = $_FILES["uploadfile"]["name"];

			//uploading a file.  this means iframes.  this also means we can't 
			//return messages as anything but XML
			$this->PROTO->setProtocol("XML");

		}

		if ($fileName && $filePath)
		{

			//at this point, no matter what, we are dealing with a file
			//on the file system.  
			$this->apidata["name"] = $fileName;
			$this->apidata["file_path"] = $filePath;
			$this->apidata["object_type"] = "file";
			
			//save the object info
			$o = new DOCMGR_OBJECT($this->apidata);		
			$objId = $o->save();

			//if no errors, return success.  we have to return this as XML, otherwise
			//the browser tries to download it
			$err = $o->getError();
			
		}
		else $err = "Could not get fileName or filePath";
		
		
		if ($err)
		{
			$this->PROTO->add("error",$err[0]);		
		}
		else
		{	
			$this->PROTO->add("success","success");
		}
						
		return $objId;
	
	}

	protected function update($data)
	{

		//we're not uploading anything so bail
		if (!$data["file_path"]) return false;

		$data["version"] = $this->objectInfo["version"] + 1;
	
		//check to see if it's locked
		//now handle the uploaded file
		$this->upload($data);

		//handle some updating for non-new documents (handled by object->create method 
		//if it was a new document)
		if ($data["version"]>1) 
		{

			//if there is a file revision limit, delete the previous file if necessary
			$this->removeRevision("earliest",1);
		       
		}
	
	
	}

	/*********************************************************************
		FUNCTION:	upload
		PURPOSE:	handles uploaded file for the object
	*********************************************************************/
	private function upload($data)
	{

		$filePath = $data["file_path"];
		$fileName = $data["name"];
		$version = $data["version"];
		$cv = $data["custom_version"];
		$rn = $data["revision_notes"];

		$dataPath = DATA_DIR."/".$this->getObjectDir();

		//run a virus scan on the file
		if (defined("CLAMAV_SUPPORT")) 
		{
	
			$r = clamAvScan(stripsan($filePath));
	
			if ($r===FALSE) 
			{
				$this->throwError("There was an error scanning the file");
				return false;
			}
			if ($r!="clean") 
			{
				$this->throwError($r);
				return false;
			}

			logEvent(OBJ_VIRUS_PASS,$this->objectId);

		}

		//get the file size
		$file_size = @filesize(stripsan($filePath));
		if (!$file_size) $file_size = "0";

		$this->DB->begin();

		$option = null;
		$option["object_id"] = $this->objectId;
		$option["name"] = $fileName;
		$option["size"] = $file_size;
		$option["version"] = $version;
		$option["modify"] = date("Y-m-d H:i:s");
		$option["object_owner"] = USER_ID;
		$option["md5sum"] = md5_file($filePath);
		$option["custom_version"] = $cv;
		$option["notes"] = $rn;
				
		$fileId = $this->DB->insert("docmgr.dm_file_history",$option,"id");

		//update the object with the file size, and the new version if it's an update
		$opt = null;
		$opt["filesize"] = $file_size;
		$opt["where"] = "id='".$this->objectId."'";
		$opt["name"] = $fileName;
		$opt["version"] = $version;
		$opt["last_modified"] = date("Y-m-d H:i:s");
		$opt["modified_by"] = USER_ID;
		$this->DB->update("docmgr.dm_object",$opt);

		$err = $this->DB->error();

		if (!$err) 
		{

			//copy the actual file now
			$fileDest = $dataPath."/".$fileId.".docmgr";	
			$str .= "Copying ".$filePath." to ".$fileDest."\n";

			//if it's an uploaded file, move it to save time
			if ($this->fileMode=="upload")
			{

				if (!move_uploaded_file("$filePath", "$fileDest")) 
					$err = "Could not move file to destination";
			
			}
			//we're doing a copy from the server, so we don't want to delete it
			else
			{
			
				if (!copy("$filePath", "$fileDest")) 
					$err = "Could not copy file to destination";
			
			}
	    
			if ($version==1) sendEventNotify($this->objectId,"OBJ_CREATE_ALERT");
			
		} 

		$this->DB->end();

		if ($err) $this->throwError($err);

	}


	/*********************************************************************
		FUNCTION:	get
		PURPOSE:	returns selected file for viewing
	*********************************************************************/
	public function get($mode="download") 
	{

		$lock = $this->apidata["lock"];
		$realname = $this->objectInfo["name"];
		$version = $this->objectInfo["version"];

		//a revision was specified, view that one instead
		if ($this->apidata["file_id"]) $fileId = $this->apidata["file_id"];
		else 
		{	
			$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' AND version='$version'";
			$info = $this->DB->single($sql);
			$fileId = $info["id"];
		}
	
		// get the filename
		$filename = DATA_DIR."/".$this->getObjectDir()."/".$fileId.".docmgr";

		//verify the md5sum for the file, log the results
		if (!fileChecksum($this->conn,$fileId,$filename)) 
		{
	
			$errorMessage = "The MD5 Checksum for this file is invalid";
			logEvent(OBJ_CHECKSUM_VERIFY_FAIL,$this->objectId);

			$this->throwError($errorMessage);
			return false;

		} 
		else 
		{
			logEvent(OBJ_CHECKSUM_VERIFY_PASS,$this->objectId);	
		}

		//scan the file and log the results before the view
		if (defined("CLAMAV_SUPPORT")) 
		{
	       
			$str = clamAvScan($filename);
			
			if ($str===FALSE) logEvent(OBJ_VIRUS_ERROR,$this->objectId);		//scanning error, continue
			elseif ($str=="clean") logEvent(OBJ_VIRUS_PASS,$this->objectId);	//file clean, continue
			else 
			{
				logEvent(OBJ_VIRUS_FAIL,$this->objectId,$str);			//virus found, stop and alert
				$this->throwError($str);
				return false;
			}
			
		}	                                                

		//return the content as a string
		if ($this->apidata["contentonly"] || $mode=="contentonly")
		{

			$c = file_get_contents($filename);		
			$this->PROTO->add("content",$c);
			
			$retvalue = $c;
		
		} 
		else if ($this->apidata["stream"] || $mode=="stream")
		{

			$stream = fopen($filename,"r");

			$this->PROTO->add("filepath",$filename);
			$this->PROTO->add("stream",$stream);
			
			$retvalue = $stream;

		}
		else
		{

			$info = fileInfo($realname);
			$type = $info["mime_type"];

			$retvalue = null;

			// send headers to browser to initiate file download
			
			//just pass directly to the browser and let it deal with it
			if ($info["inline"] && $type)
			{
				//handle inline documents as specified in the extensions.xml file
				header ("Content-Type: ".$type);
				//header ("Content-Disposition: inline; filename=\"$realname\"");
			}
			//force download on known types.  force a download
			else if ($type)
			{
				//handle non-inline documents that we still know what they are
				header ("Content-Type: ".$type);
				header ("Content-Type: application/force-download");
				header ("Content-Disposition: attachment; filename=\"$realname\"");
			}
			//force download of unknown file type
			else
			{
				//handle everything else
				header ("Content-Type: application/octet-stream");
				header ("Content-Type: application/force-download");
				header ("Content-Disposition: attachment; filename=\"$realname\"");
			}
      
			//the rest of the headers			
			header ("Content-Length: ".filesize($filename));
			header ("Content-Transfer-Encoding:binary");
			header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header ("Pragma: public");

			//chunked handles bigger files well	
			readfile_chunked($filename);

		}

		if ($lock && $this->permCheck("edit"))
		{

			$l = new DOCMGR_UTIL_LOCK($this->objectId);

			if (!$l->isLocked()) 
			{

				//so here, the file is obviously being checked out via a browser to be uploaded later
				//so no limit on the lock clearing.  DSOFramer clients will pass a timeout, so
				//we must honor that
				if (!$this->apidata["timeout"]) $this->apidata["timeout"] = -1;

				$l->set($this->apidata);

			}
			
		} 
		else 
		{

			//log the view
			logEvent(OBJ_VIEWED,$this->objectId);
		
		}

		//if given something to return, pass it back, otherwise stop here
		if ($retvalue) return $retvalue;
		else die;
	
	}

	/*********************************************************************
		FUNCTION:	saveDsoframer
		PURPOSE:	master function for saving a edraw file.  Creates/updates
				the object and calls the file uploader for the rest
	*********************************************************************/
	public function saveDsoframer() 
	{

		//dont' take this out, this keeps IE from crashing
		echo "DEBUG!!!";

		$errorCode = null;
		
		$this->save();
		
		if (!$this->getError()) exit(0);
		else exit(1);	

	}
	
	/**********************************************************************
		FUNCTION:	remove
		PURPOSE:	perform additional deletion when removing an object
	***********************************************************************/
	protected function remove() 
	{

		$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."'";
		$info = $this->DB->fetch($sql,1);
	
		//get our directory path for this object
		$dirPath = $this->getObjectDir($this->objectId);
	
		//delete the thumbnail and preview
		@unlink(THUMB_DIR."/".$dirPath."/".$this->objectId.".docmgr");
		@unlink(PREVIEW_DIR."/".$dirPath."/".$this->objectId.".docmgr");
	
		//delete any physical files associated with our revisions
		if (is_array($info["id"])) 
			foreach ($info["id"] AS $id) @unlink(DATA_DIR."/".$dirPath."/".$id.".docmgr");
	
		$sql = "DELETE FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."';";
		$this->DB->query($sql);

		//delete our workflow and associated routes
		$sql = "SELECT id,status FROM docmgr.dm_workflow WHERE object_id='".$this->objectId."'";
		$list = $this->DB->fetch($sql,1);

		$sql = null;
		
		//there is a pending workflow, handle it's keys
		if ($list["count"]>0) {

			$sql .= "DELETE FROM docmgr.dm_workflow_route WHERE workflow_id IN (".implode(",",$list["id"]).");"; 	
	
			//make sure none of them are pending
			if (in_array("pending",$list["status"])) 
			{
				
				//get the object's name
				$sql = "SELECT name FROM docmgr.dm_object WHERE id='".$this->objectId."';";
				$info = $this->DB->single($sql);
				$this->throwError(_PENDING_WORKFLOW_ERROR." \"".$info["name"]."\"");
				return false;

			}
	
			$wsql = "SELECT id FROM docmgr.dm_workflow_route WHERE workflow_id IN (".implode(",",$list["id"]).");";
			$tmp = $this->DB->fetch($sql,1);
			
			if ($tmp["count"]>0) {
				$sql .= "DELETE FROM docmgr.dm_task WHERE task_id IN (".implode(",",$tmp["id"]).");"; 	
			}

			
		}
	
		$sql .= "DELETE FROM docmgr.dm_workflow WHERE object_id='".$this->objectId."';";
		$this->DB->query($sql);
	
	}
	
	
	/***********************************************************************
		FUNCTION:	thumb
		PURPOSE:	This function creates a thumbnail for the object when imported
							into the system
	***********************************************************************/
	protected function thumb() 
	{

		//make sure thumbnail support is enabled.  Also make sure imagemagick is enabled
		if (!defined("THUMB_SUPPORT")) return false;

		//get the id of the most reccent revision.  also snag the name for filetype checking
		$sql = "SELECT id,object_id,(SELECT name FROM docmgr.dm_object WHERE dm_object.id=dm_file_history.object_id) AS name
			FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' ORDER BY version DESC LIMIT 1";
		$info = $this->DB->single($sql);

		//get the filesystem path to the object
		$dirPath = $this->getObjectDir();

		$filename = $info["name"];
		$filepath = DATA_DIR."/".$dirPath."/".$info["id"].".docmgr";

		$fileinfo = return_file_info($filename,$filepath);
		$type = &$fileinfo["fileType"];
		$mime = &$fileinfo["mimeType"];

		$thumb = THUMB_DIR."/".$dirPath."/".$this->objectId.".png";
		$finalThumb = str_replace(".png",".docmgr",$thumb);

		//init our thumbnail creator in thumb node
		$t = new DOCMGR_UTIL_FILETHUMB("thumb",$filepath,$filename,$thumb);

		//rename the file to a docmgr extension for security
		if (file_exists($thumb)) rename($thumb,$finalThumb);

	}

	/***********************************************************************
		FUNCTION:	index
		PURPOSE:	returns document content to be indexed
	***********************************************************************/
	protected function index() 
	{

		//get the id of the most reccent revision.  also snag the name for filetype checking
		$sql = "SELECT id,(SELECT name FROM docmgr.dm_object WHERE dm_object.id=dm_file_history.object_id) AS name
			FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' ORDER BY version DESC LIMIT 1";
		$info = $this->DB->single($sql);

		$dirPath = $this->getObjectDir();

		$filename = $info["name"];
		$filepath = DATA_DIR."/".$dirPath."/".$info["id"].".docmgr";

		//make sure the user has not prevented indexing of this file
		$idxopt = return_file_idxopt($filename);
		if (!$idxopt) 
		{
			$fi = new DOCMGR_UTIL_FILEINDEX($filename,$filepath);
			return $fi->getContent();
		} 
		else return null;
		
	}
	
	
	/***************************************************************
		remove a revision for a file.if file_id = earliest,
		remove the earliest available version.Otherwise
		remove the passed id
	***************************************************************/
	public function removeRevision($fileArr=null,$bypassPerm = null) 
	{

		//perm check
		if (!$bypassPerm && !$this->permCheck("admin"))
		{
			$this->throwError("You do not have permissions to manage this object");
			return false;
		}
	
		if (!$fileArr) $fileArr = $this->apidata["file_id"];

		if (!is_array($fileArr)) $fileArr = array($fileArr);

		//loop through and kill them all!		
		foreach ($fileArr AS $fileId)
		{
	
			//remove our earliest revision to keep the limit where it should be
			if ($fileId=="earliest") 
			{

				//config check
				if (!defined("FILE_REVISION_LIMIT") || FILE_REVISION_LIMIT=="0") continue;
		
				$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' ORDER BY id";	
				$info = $this->DB->fetch($sql,1);
		
				if ($info["count"] > FILE_REVISION_LIMIT) {
		
					//delete all entries that are less then our current count
					$diff = $info["count"] - FILE_REVISION_LIMIT;
		
					for ($i=0;$i<$diff;$i++) {
				
						$fileId = $info["id"][$i];
					
						$sql = "DELETE FROM docmgr.dm_file_history WHERE id='$fileId'";
						if ($this->DB->query($sql)) 
						{
							$file = DATA_DIR."/".$this->getObjectDir()."/".$fileId.".docmgr";
							@unlink($file);
						}
		
					}		
		
				}
		
			//this portion deletes the specified revision as determined by the fileId	
			} 
			else 
			{
		
				//config check
				if (!defined("FILE_REVISION_REMOVE") || FILE_REVISION_REMOVE=="no") continue;
				
				//get the latest version of this file
				$sql = "SELECT version FROM docmgr.dm_file_history WHERE id='".$fileId."'";
				$hInfo = $this->DB->single($sql);
		
				//get the current version
				$sql = "SELECT version FROM docmgr.dm_object WHERE id='".$this->objectId."'";
				$oInfo = $this->DB->single($sql);
		
				$sql = "DELETE FROM docmgr.dm_file_history WHERE id='".$fileId."'";
				if ($this->DB->fetch($sql)) {
		
					$file = DATA_DIR."/".$this->getObjectDir()."/".$fileId.".docmgr";
					@unlink($file);
		
					//if the file was the latest revision, promote the next in line
					if ($hInfo["version"]==$oInfo["version"]) 
					{
		
						//get the next latest version of this file
						$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' ORDER BY version DESC LIMIT 1";
						$info = $this->DB->single($sql);
		
						//promote our file, then reindex and rethumb
						$this->promote($info["id"]);
						$i = new DOCMGR_OBJINDEX($this->objectId);
						$i->run(USER_ID);
		
					} 
		
				}
		
			}
		
		}	//end for loop

		//since it's possible we removed teh latest one, reset version in the object file
		$sql = "UPDATE docmgr.dm_object SET version=(SELECT max(version) FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."')
																				WHERE id='".$this->objectId."'";
		$this->DB->query($sql);

	}	
	
	//promotes a file to the latest revision for the object
	public function promote($fileId=null) 
	{

		//perm check
		if (!$this->permCheck("admin"))
		{
			$this->throwError("You do not have permissions to manage this object");
			return false;
		}
	
		if (!$fileId) $fileId = $this->apidata["file_id"];
	
		/* with this, we will pretty much have to put the ids in an array, and alter them accordingly */
		$sql = "SELECT * FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' ORDER BY version DESC";
		$info = $this->DB->fetch($sql,1);
	
		$id_array = &$info["id"];
		$version_array = &$info["version"];
	
		/* figure out which number in the array our orignal belongs to */
		$orig_num=array_search($fileId,$id_array);
	
		/* set the new file to the highest version number */
		$sql = null;
	
		//create a new array with the swapped orders
		$temp = array($fileId);
		foreach ($info["id"] AS $id) if ($id!=$fileId) $temp[] = $id;
		$num = count($temp);
	
		for ($row=0;$row<$num;$row++) 
		{
	
			//a hack to fix the promote errors some people are getting
			if (!$temp[$row]) continue;
	
			$version = $num - $row;
			$sql .= "UPDATE docmgr.dm_file_history SET version='$version' WHERE id='".$temp[$row]."';";
	
		}
	
		//get the new name to set our object to
		$sqltmp = "SELECT name FROM docmgr.dm_file_history WHERE id='$fileId'";
		$arr = $this->DB->single($sqltmp);
	
		//update the database to use the highest one if there is a name field in the database
		$sql .= "UPDATE docmgr.dm_object SET name='".sanitizeString($arr["name"])."',version='".max($version_array)."' WHERE id='".$this->objectId."';";
		
		//run our query
		$this->DB->query($sql);
	
		logEvent(OBJ_VERSION_PROMOTE,$this->objectId);
	
	}


	public function getHistory() 
	{

		$sql = "SELECT * FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' ORDER BY version DESC";	
		$list = $this->DB->fetch($sql);

		// get the filename
		$filedir = DATA_DIR."/".$this->getObjectDir();
		
		//convert to xml and return
		for ($i=0;$i<$list["count"];$i++) 
		{

			$info = returnAccountInfo($list[$i]["object_owner"]);
			$name = $info["first_name"]." ".$info["last_name"];

			//add formated modified info
			$list[$i]["view_modified_date"] = dateView($list[$i]["modify"]);
			$list[$i]["view_modified_by"] = $name;
			$list[$i]["size"] = displayFileSize(filesize($filedir."/".$list[$i]["id"].".docmgr"));

			$this->PROTO->add("history",$list[$i]);
		
		}
	
	}

	/***********************************************************************
		Displaying:
		This function returns the link and the icon to be displayed
		in the finder in list view
		return $arr("link" => $link, "icon" => $icon);
	***********************************************************************/
	protected function listDisplay($info) {
	
		//$extension = return_file_extension($info["name"]);
		$type = return_file_type($info["name"]);

		if (file_exists(THEME_PATH."/images/fileicons/".$type.".png"))
			$arr["icon"] = THEME_PATH."/images/fileicons/".$type.".png";
		else
			$arr["icon"] = THEME_PATH."/images/fileicons/file.png";
	
		$arr["link"] = "index.php?module=fileview&objectId=".$info["id"];
	
		return $arr;

	}
	
	/********************************************************************
		FUNCTION: getashtml
		PURPOSE:	retrieves a file from the system as html
	********************************************************************/
	public function getashtml() 
	{
	
		$realname = $this->objectInfo["name"];
		$version = $this->objectInfo["version"];

 		//add our user's permissions
 		$this->PROTO->add("bitmask_text",$this->objectInfo["bitmask_text"]);
                                                                                                             
		//lock the bastard if askedk
		if ($this->apidata["lock"] && $this->permCheck("edit"))
		{

			$l = new DOCMGR_UTIL_LOCK($this->objectId);

			if (!$l->isLocked()) 
			{

				//so here, the file is obviously being checked out via a browser to be uploaded later
				//so no limit on the lock clearing.  DSOFramer clients will pass a timeout, so
				//we must honor that
				if (!$this->apidata["timeout"]) $this->apidata["timeout"] = -1;

				$l->set($this->apidata);

			//let the interface know we are locked
			}
			else $this->PROTO->add("locked","t");
			
		} 

		$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' AND version='$version'";
		$info = $this->DB->single($sql);
		$file_id = $info["id"];
	
		// get the filename
		$filename = DATA_DIR."/".$this->getObjectDir()."/".$file_id.".docmgr";

		//copy the file to the temp directory with it's proper name
		$temp = TMP_DIR."/".USER_LOGIN;
		$tempfile = $temp."/".$realname;
		recurmkdir($temp);
		
		copy($filename,$tempfile);

		$oo = new OPENOFFICE($tempfile,$this->objectId);
		$htmlfile = $oo->convert("html");
		
		$xhtml = formatEditorStr(file_get_contents($htmlfile));

		//return our data in a <content> tag
		$this->PROTO->add("content",$xhtml);

	}

	/********************************************************************
		FUNCTION: savefromhtml
		PURPOSE:	converts html back into the object's original file format
							and saves it
	********************************************************************/
	public function savefromhtml() 
	{

		//get our editor content and convert to original extension		
		$content = cleanupEditorStr($this->apidata["editor_content"]);		

		$tempfile = TMP_DIR."/".USER_LOGIN."/worker.html";
		file_put_contents($tempfile,$content);

		//updating an existing object
		if ($this->objectId)
		{

			$realname = sanitize($this->objectInfo["name"]);
			$exten = fileExtension($realname);

			//convert back to our original extension		
			$oo = new OPENOFFICE($tempfile,$this->objectId);
			$newfile = $oo->convert($exten);

		}
		//saving new object
		else
		{

			$realname = $this->apidata["name"];
			$exten = fileExtension($realname);
			
			$oo = new OPENOFFICE($tempfile);
			$newfile = $oo->convert($exten);

		}
		
		//set the file path to save and check it in
		$this->apidata["filepath"] = $newfile;
		$this->apidata["name"] = $realname;
		$this->save();	

	}

	//for rotating images
	public function rotate() 
	{

		//perm check
		if (!$this->permCheck("edit"))
		{
			$this->throwError("You do not have permissions to edit this object");
			return false;
		}

		$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' ORDER BY version DESC LIMIT 1";
		$info = $this->DB->single($sql);
		$file_id = $info["id"];
	
		// get the filename
		$dir = $this->getObjectDir();
		$file = DATA_DIR."/".$dir."/".$file_id.".docmgr";
		$thumbfile = THUMB_DIR."/".$dir."/".$this->objectId.".docmgr";
  	
		if ($this->apidata["direction"]=="right") $deg = "90";
		elseif ($this->apidata["direction"]=="flip") $deg = "180";
		else $deg = "270";

		//rotate the real file
		`mogrify -rotate $deg $file`;
		`mogrify -rotate $deg $thumbfile`;

	}

	/***********************************************************************
		Getting into system:
		This function creates a thumbnail for the object when imported
		into the system
	***********************************************************************/
	protected function preview() 
	{

		//make sure thumbnail support is enabled.Also make sure imagemagick is enabled
		if (!defined("THUMB_SUPPORT")) return false;

		//get the id of the most reccent revision.also snag the name for filetype checking
		$sql = "SELECT id,object_id,(SELECT name FROM docmgr.dm_object WHERE dm_object.id=dm_file_history.object_id) AS name
						FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' ORDER BY version DESC LIMIT 1";
		$info = $this->DB->single($sql);
		
		$dirPath = $this->getObjectDir();
		
		$filename = $info["name"];
		$filepath = DATA_DIR."/".$dirPath."/".$info["id"].".docmgr";
		
		$fileinfo = return_file_info($filename,$filepath);
		$type = &$fileinfo["fileType"];
		$mime = &$fileinfo["mimeType"];
		 
		$thumb = PREVIEW_DIR."/".$dirPath."/".$this->objectId.".png";
		 
		$finalThumb = str_replace(".png",".docmgr",$thumb);
		 
		//init our thumbnail creator in thumb node
		$t = new DOCMGR_UTIL_FILETHUMB("preview",$filepath,$filename,$thumb);
		 
		//rename the file to a docmgr extension for security
		if (file_exists($thumb)) rename($thumb,$finalThumb);
		 
	}
		 			
}
			