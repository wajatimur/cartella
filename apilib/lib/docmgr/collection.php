<?php

/**************************************************************************
	CLASS:	collection
	PURPOSE:	handle specific processing for the collection object
**************************************************************************/

class DOCMGR_COLLECTION extends DOCMGR_AOBJECT
{

	function get()
	{
		return false;
	}
	
	function save()
	{

		//make sure it's saved as a document
		$this->apidata["object_type"] = "collection";

		$o = new DOCMGR_OBJECT($this->apidata);
		$objId = $o->save();	

		//toss and error if we have one
		$err = $o->getError();

		if ($err)
		{ 
			$this->throwError($err[0],$err[1]);
		}
		else 
		{
			return $objId;
		}
		
	}

	function saveview()
	{

		$sql = "SELECT object_id FROM docmgr.object_view WHERE object_id='".$this->objectId."' AND account_id='".USER_ID."'";
		$info = $this->DB->single($sql);
		
		if ($info)	
		{
			$sql = "UPDATE docmgr.object_view SET view='".$this->apidata["account_view"]."' WHERE object_id='".$this->objectId."' AND account_id='".USER_ID."'";
		}
		else
		{
			$sql = "INSERT INTO docmgr.object_view (object_id,account_id,view) VALUES ('".$this->objectId."','".USER_ID."','".$this->apidata["account_view"]."');";
		}

		$this->DB->query($sql);
		
	}

	/***********************************************************************
		FUNCTION: update
		PURPOSE:	called by DOCMGR_OBJECT class DOCMGR_to perform additional processing
							for updating a collection
	***********************************************************************/
	protected function update($data) 
	{

		if ($data["default_view"]) $dv = $data["default_view"];

		$sql = null;
		
		//delete current values for default browsing view, and add new oens
		if ($dv)
		{
		 	$sql .= "DELETE FROM docmgr.object_view WHERE object_id='".$this->objectId."' AND account_id='0';";
			$sql .= "INSERT INTO docmgr.object_view (object_id,account_id,view) VALUES ('".$this->objectId."','0','".$dv."');";
		}

		$this->DB->query($sql);

	}

	/***********************************************************************
		FUNCTION: remove
		PURPOSE:	called by DOCMGR_OBJECT class DOCMGR_to perform additional processing
							for removing a collection
	***********************************************************************/
	protected function remove() 
	{

		//reset everyone's home directory if they were using this one
		$sql = "UPDATE auth_settings SET home_directory='0' WHERE home_directory='".$this->objectId."'";
		if (!$this->DB->query($sql)) return false;

		//return true if we make it to here
		return true;

	}

	
	/***********************************************************************
	  Displaying:
	  This private function returns the link and the icon to be displayed
	  in the finder in list view
	  return $arr("link" => $link, "icon" => $icon);
	***********************************************************************/
	protected function listDisplay($info) 
	{
	
	  $arr["icon"] = THEME_PATH."/images/fileicons/folder.png";
	  $arr["link"] = "javascript:browseCollection('".$info["id"]."');";
	  return $arr;
	
	}

	/***********************************************************************
		FUNCTION: zip
		PURPOSE:	gets all children of the current collection and 
							zips them up and pushes to them to the browser for download
	***********************************************************************/
	function zip()
	{
	
		$dir = TMP_DIR."/".USER_LOGIN;
	
		//create the temp directory. otherwise empty any previous contents in that dir
		if (is_dir("$dir")) `rm -r "$dir"`;
		mkdir("$dir");
	
		$sql = "SELECT * FROM docmgr.dm_view_collections WHERE id='".$this->objectId."'";
		$info = $this->DB->single($sql);
		
		//create a folder which is a mirror of our collection
		$arcdir = $this->zipProcessCol($info,$dir);	
	
		if (is_dir("$arcdir")) 
		{
	
			$arr = explode("/",$arcdir);
			$arcsrc = array_pop($arr);
	
			//zip up our file
			//$arc = $info["name"].".zip";	
			
			//create our archive
			//$cmd = "cd \"".$dir."\"; zip -r \"".$arc."\" \"".$arcsrc."\"";
			//`$cmd`;

			$path = $dir."/".$info["name"];
			$path = $this->zipDir($path);
			
			//handle everything else
			header ("Content-Type: application/zip");
			header ("Content-Type: application/force-download");
			header ("Content-Length: ".filesize($path));
			header ("Content-Disposition: attachment; filename=\"".$info["name"].".zip\"");
			header ("Content-Transfer-Encoding:binary");
			header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header ("Pragma: public");

			//chunked handles bigger files well 
			readfile_chunked($path);

		} else return false;
	
	}

	/***********************************************************************
		FUNCTION: zipDir
		PURPOSE:	creates a zip archive from all the files in the passed
							directory
	***********************************************************************/
	protected function zipDir($dir)
	{
	
		$zipfile = $dir.".zip";
		$zip = new ZipArchive();
	
		$zip->open($zipfile,ZIPARCHIVE::CREATE);
			
		$arr = listDir($dir);
		
		foreach ($arr AS $file)
		{

			$fileName = str_replace(TMP_DIR."/".USER_LOGIN,"",$file);
			
			if (is_dir($file)) $zip->addEmptyDir($fileName);
			else $zip->addFile($file,$fileName);
		
		}				
	
		return $zipfile;
	
	}

	/***********************************************************************
		FUNCTION: zipProcessFile
		PURPOSE:	copies the called file into the collection for zipping
	***********************************************************************/
	protected function zipProcessFile($obj,$dir) {
	
		$sql = "SELECT name,version,level1,level2 FROM docmgr.dm_view_objects WHERE id='".$obj["id"]."'";
		$objInfo = $this->DB->single($sql);
		$version = $objInfo["version"];
	
		$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$obj["id"]."' AND version='$version'";
		$info = $this->DB->single($sql);
	
		//copy the file to the temp directory with the correct name
		$filename = $dir."/".$obj["name"];
		$source = DATA_DIR."/".$objInfo["level1"]."/".$objInfo["level2"]."/".$info["id"].".docmgr";

		if (file_exists("$source")) copy("$source","$filename");
	
		//log the event since this means the file will be viewed
		//logEvent($conn,OBJ_VIEWED,$obj["id"]);
		
	}
	
	/***********************************************************************
		FUNCTION: zipProcessCollection
		PURPOSE:	makes a collection and gets all children of it and copies
							them in for download
	***********************************************************************/
	protected function zipProcessCol($obj,$passDir) {
	
		$sql = "SELECT * FROM docmgr.dm_view_objects WHERE parent_id='".$obj["id"]."'";
	
		//add perm string filter if not admin
		if (!PERM::check(ADMIN)) $sql .= " AND ".permString();
	
		$list = $this->DB->fetch($sql);
		
		//first, create a directory with this column.
		$dir = $passDir."/".$obj["name"];
	
		//remove the directory if it is there
		if (is_dir("$dir")) `rm -r "$dir"`;
		mkdir("$dir");
	
		for ($i=0;$i<$list["count"];$i++) 
		{
	
			//only add files and collections to the archive	
			if ($list[$i]["object_type"]=="collection") $this->zipProcessCol($list[$i],$dir);
			else if ($list[$i]["object_type"]=="file") $this->zipProcessFile($list[$i],$dir);
		
		}
	
		//return the directory we created
		return $dir;
	}
	
}



