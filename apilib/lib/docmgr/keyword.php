<?php

/****************************************************************************
	CLASS:	KEYWORD
	PURPOSE:	master class DOCMGR_for dealing with object keywords
****************************************************************************/

class DOCMGR_KEYWORD extends DOCMGR 
{

	public function getAll()
	{

		$k = new DOCMGR_UTIL_KEYWORD();
		$list = $k->getAll();
		
		if ($list["count"]>0)
		{
			unset($list["count"]);
			$this->PROTO->add("keyword",$list);
		}

	}

	/****************************************************************************
		FUNCTION: saveValues
		PURPOSE:	get all the keywords for the current parent if passed, also
						return the current object's keyword data if there is a current obj
	****************************************************************************/
	public function getlist() 
	{

		$k = new DOCMGR_UTIL_KEYWORD($this->objectId);
		$list = $k->getlist();
		
		if ($list["count"]>0)
		{
			unset($list["count"]);
			$this->PROTO->add("keyword",$list);
		}
  
	}

	/****************************************************************************
		FUNCTION: save
		PURPOSE:	saves or updates a keyword setup entry
	****************************************************************************/
	public function save()
	{

		if (!PERM::check(ADMIN))
		{
			$this->throwError("You do not have permissions to edit keywords");
			return false;
		}
	
		if ($this->apidata["keyword_id"]) $keywordId = $this->apidata["keyword_id"];
		$this->DB->begin();
		
  	$opt = null;
  	$opt["name"] = $this->apidata["keyword_name"];
  	$opt["type"] = $this->apidata["keyword_type"];
  	$opt["required"] = $this->apidata["required"];

  	if ($keywordId)
  	{

    	$opt["where"] = "id='".$keywordId."'";
    	$this->DB->update("docmgr.keyword",$opt);

		} else {

    	$keywordId = $this->DB->insert("docmgr.keyword",$opt,"id");

		}
		
		//update which collections this keyword applies to
		$sql = "DELETE FROM docmgr.keyword_collection WHERE keyword_id='$keywordId';";
  
		$parents = $this->apidata["parent_id"];
		if ($parents && !is_array($parents)) $parents = array($parents);
		
		for ($i=0;$i<count($parents);$i++) 
		{
    	$sql .= "INSERT INTO docmgr.keyword_collection (keyword_id,parent_id) VALUES ('$keywordId','".$parents[$i]."');";
		}
  
		$this->DB->query($sql);

		$this->DB->end();

		//return the keyword info
		if ($keywordId) return $this->getInfo($keywordId);

	}

/****************************************************************************
	FUNCTION: addOption
	PURPOSE:	add a select keyword option
****************************************************************************/
	public function saveOption()
	{

		if (!PERM::check(ADMIN))
		{
			$this->throwError("You do not have permissions to edit keywords");
			return false;
		}
	
	  $opt = null;
	  $opt["name"] = $this->apidata["option_name"];
	  $opt["keyword_id"] = $this->apidata["keyword_id"];
	  $this->DB->insert("docmgr.keyword_option",$opt);

	  return $this->getOptions();
	
	}

/****************************************************************************
	FUNCTION: deleteOption
	PURPOSE:	removes a select keyword option
****************************************************************************/
	public function deleteOption()
	{

		if (!PERM::check(ADMIN))
		{
			$this->throwError("You do not have permissions to edit keywords");
			return false;
		}
	
		$optionId = $this->apidata["option_id"];
		if (!is_array($optionId)) $optionId = array($optionId);
		
  	$sql = null;
  
  	for ($i=0;$i<count($optionId);$i++) {
	    $sql .= "DELETE FROM docmgr.keyword_option WHERE id='".$optionId[$i]."';";
		}
		
		if ($sql) $this->DB->query($sql);

	  return $this->getOptions();
		  
  }

/****************************************************************************
	FUNCTION: getinfo
	PURPOSE:	get all setup information regarding a specific keyword
****************************************************************************/
  public function getinfo($keyid=null) 
  {

  	if ($keyid) $keywordId = $keyid;
  	else $keywordId = $this->apidata["keyword_id"];

	  $sql = "SELECT * FROM docmgr.keyword WHERE id='$keywordId'";
	  $keywordList = $this->DB->fetch($sql);

	  $sql = "SELECT parent_id FROM docmgr.keyword_collection WHERE keyword_id='$keywordId'";
	  $parents = $this->DB->fetch($sql,1);
  
	  //comma-delimited list of the parent restrictions for keyword
	  $keywordList[0]["parent_id"] = @implode(",",$parents["parent_id"]);

	  for ($i=0;$i<$keywordList["count"];$i++)
	  {

	  	$this->PROTO->add("keyword",$keywordList[$i]);

		}

  }

/****************************************************************************
	FUNCTION: getoptions
	PURPOSE:	get all setup options for a select keyword
****************************************************************************/
	public function getoptions()
	{

  	$keywordId = $this->apidata["keyword_id"];
	
	  $sql = "SELECT * FROM docmgr.keyword_option WHERE keyword_id='$keywordId' ORDER BY name";
	  $optionList = $this->DB->fetch($sql);

	  for ($i=0;$i<$optionList["count"];$i++)
	  {

	  	$this->PROTO->add("option",$optionList[$i]);

		}
		
	}  

/****************************************************************************
	FUNCTION: delete
	PURPOSE:	delete a keyword
****************************************************************************/
	public function delete()
	{

		if (!PERM::check(ADMIN))
		{
			$this->throwError("You do not have permissions to edit keywords");
			return false;
		}

  	$keywordId = $this->apidata["keyword_id"];
	
	  $sql = "DELETE FROM docmgr.keyword WHERE id='$keywordId';";
	  $sql .= "DELETE FROM docmgr.keyword_option WHERE keyword_id='$keywordId';";
	  $sql .= "DELETE FROM docmgr.keyword_collection WHERE keyword_id='$keywordId';";
	  $sql .= "DELETE FROM docmgr.keyword_value WHERE keyword_id='$keywordId';";
	  $this->DB->query($sql);

	}  

}

