<?php

/**********************************************************************
	CLASS:	INTRANET
	PURPOSE:	handles specific processing for document objects.  this
						class DOCMGR_is streamlined from the regular document class
						for faster content delivery
**********************************************************************/
class DOCMGR_INTRANET extends DOCMGR {

	/*******************************************************************************
		called from DOCMGR class
	*******************************************************************************/

	/********************************************************************
  		FUNCTION: get
  		PURPOSE:  retrieves the intranet page from the system.  also
  							returns any data under current page
	********************************************************************/
	public function get() 
	{

		//pull our content
		if ($this->objectInfo["object_type"]=="url")
		{

			$u = new DOCMGR_URL($this->apidata);
			$u->get();
		
		} else if ($this->objectInfo["object_type"]=="collection") 
		{

	    $s = new DOCMGR_SEARCH($this->apidata);
	    $s->browse();

		} else 
		{
		
			$this->getcontent();
		
		}
            
		//get sub pages of this page	
		$this->PROTO->add("object_name",$this->objectInfo["name"]);
		$this->PROTO->add("object_type",$this->objectInfo["object_type"]);
		$this->PROTO->add("object_path",$this->objectInfo["object_path"]);
		$this->PROTO->add("objectid_path",$this->objectInfo["objectid_path"]);
		$this->PROTO->add("display_path",$this->objectInfo["display_path"]);
		$this->PROTO->add("bitmask",$this->objectInfo["bitmask"]);
		$this->PROTO->add("bitmask_text",$this->objectInfo["bitmask_text"]);

		$this->getchildren();
		
	}

	/********************************************************************
  		FUNCTION: getcontent
  		PURPOSE:  retrieves document from the system.  this one skips
  							logging to cut down on time
	********************************************************************/
	private function getcontent() 
	{

		//make sure we have perms to view this thing
		if (!DOCMGR_UTIL_OBJPERM::check($this->objectBitset,"view"))
		{
			$this->throwError("You do not have permissions to view this object");
			return false;
		}

		$realname = $this->objectInfo["name"];
		$version = $this->objectInfo["version"];
		
		if ($this->apidata["document_id"]) $documentId = $this->apidata["document_id"];
		else 
		{
		
			$sql = "SELECT id FROM docmgr.dm_document WHERE object_id='".$this->objectId."' AND version='$version'";
			$info = $this->DB->single($sql);
			$documentId = $info["id"];

		}

		// get the filename
		$filename = DOC_DIR."/".$this->getObjectDir()."/".$documentId.".docmgr";

		//get the contents
		$xhtml = $this->stripHeaders(formatEditorStr(@file_get_contents($filename)));

		$this->PROTO->add("content",$xhtml);

	}

	/****************************************************************************
		FUNCTION:	getChildren
		PURPOSE:	gets all objects that exist under the "pages" folder of the
							current document object
	****************************************************************************/
	public function getchildren() 
	{

		//make sure we have perms to view this thing
		if (!DOCMGR_UTIL_OBJPERM::check($this->objectBitset,"view"))
		{
			$this->throwError("You do not have permissions to view this object");
			return false;
		}

		//get the name from the path
		$pageColId = $this->getPagesFolder($this->objectId);

    $s = new DOCMGR_SEARCH($pageColId);
    $s->browse();

	}

	/****************************************************************************
		FUNCTION:	getPagesFolder
		PURPOSE:	creates/returns the .objectXXXX_pages folder for storing pages
							under a document in intranet mode
	****************************************************************************/
	private function getPagesFolder($objId) 
	{

		$d = new DOCMGR_DOCUMENT($objId);
		return $d->getPagesFolder($objId);

	}


	/********************************************************************
  		FUNCTION: create
  		PURPOSE:  saves a new intranet page.  all subsequent saves
  							will be handled by the own indiviual modules
	********************************************************************/
	public function create() 
	{

		//get the parent id
		$parent = $this->getPagesFolder($this->apidata["parent_id"]);
		$this->apidata["parent_id"] = $parent;

		//collection for files
		if ($this->apidata["object_type"]=="collection")
		{

			$o = new DOCMGR_OBJECT($this->apidata);
			$o->create();		
		
		//url redirect page
		} else if ($this->apidata["object_type"]=="url") 
		{
		
			$o = new DOCMGR_URL($this->apidata);
			$o->save();		

		//inline document		
		} else 
		{

			$o = new DOCMGR_DOCUMENT($this->apidata);
			$o->save();		

		}

		//toss and error if we have one
		$err = $o->getError();
		if ($err) $this->throwError($err[0],$err[1]);

	}

	/********************************************************************
  		FUNCTION: stripHeaders
  		PURPOSE:  takes a full html page and removes everything before
  							and after and including the body tags.  just leaves
								core page content
	********************************************************************/
	private function stripHeaders($content)
	{

		$matches = array();
		$ret = preg_match("/<body>(.*)<\/body>/smi",$content,$matches);

		//if a match is found, return it, otherwise return unaltered
		if ($ret) return $matches[1];
		else return $content;
	
	}
		
}
	
