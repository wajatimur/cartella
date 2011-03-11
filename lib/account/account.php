<?php
/************************************************************************************************************
	ldap.php
	
	Holds account processing and search functions for
	an ldap database
	
	02-07-2005 - Fixed returnAccountInfo returning an error if it did not find an account Id (Eric L.)
	02-14-2005 - Split group info
	11-20-2005 - Stripped file down more and added support for an ldap map file

***********************************************************************************************************/

//get our required files
if (defined("USE_LDAP")) require_once("ldap.php");
else require_once("db.php");

class ACCOUNT {

	protected $accountId;
	protected $errorMessage;
	protected $ACCT;
	protected $mode;
	
	function __construct($aid=null) {

		if (defined("USE_LDAP")) {
			$this->ACCT = new LDAP($aid);
			$this->mode = "ldap";
		} else {
			$this->ACCT = new DB($aid);
			$this->mode = "db";
		}
		
		if ($aid) $this->accountId = $aid;
		
		$err = $this->ACCT->getError();

		if ($err) $this->throwError($err);

	}

	function throwError($msg) {
		$this->errorMessage = $msg;
	}
	
	function getError() {
		return $this->errorMessage;
	}

	function getInfo($aid=null) {

		if (!$aid) $aid = $this->accountId;

		return $this->ACCT->getInfo($aid);
		
	}

	function getId($login)
	{
	
		$filter = array("login"=>$login);
		$results = $this->search($filter);
	
		//return first result
		if (count($results) > 0) return $results[0]["id"];
		else return false;
	
	}

	//caches the results for one page run so we don't have to keep hitting the database
	function cachedGetInfo($id)
	{
	
		$this->cachedGetList();

		$num = count($GLOBALS["cachedAccountList"]);
		$info = null;
	
		//loop through our cache and find the match	
		for ($i=0;$i<$num;$i++)
		{
		
			if ($GLOBALS["cachedAccountList"][$i]["id"]==$id)
			{
				$info = $GLOBALS["cachedAccountList"][$i];		
				break;
			}
			
		}
		
		return $info;

	}


	//caches the results for one page run so we don't have to keep hitting the database
	function cachedGetList($filter=null,$sort=null)
	{
	
		if (!$GLOBALS["cachedAccountList"])
		{
			$GLOBALS["cachedAccountList"] =  $this->getList($filter,$sort);
		}
				
		return $GLOBALS["cachedAccountList"];

	}

	function getList($filter=null,$sort=null) {

		return $this->search($filter,$sort);
		
	}

	function search($filter=null,$sort=null) {
	
		//map our filters and sorts to db specific. 
		if ($filter) {
		
			if ($this->mode=="ldap") $filterStr = $this->setLDAPFilter($filter);
			else $filterStr = $this->setDBFilter($filter);
		
		}

		if ($sort) {
		
			if ($this->mode=="ldap") $sortStr = $this->setLDAPSort($sort);
			else $sortStr = $this->setDBSort($sort);
		
		}			

		return $this->ACCT->getList($filterStr,$sortStr);	
	
	}

	function setLDAPFilter($filter) {

		if (!is_array($filter)) return $filter;
	
		$str = "(|";
	
		foreach ($filter AS $key=>$val) {
		
			if ($key=="login") $str .= "(".LDAP_UID."=".$val."*)";
			else if ($key=="name") $str .= "(".LDAP_CN."=*".$val."*)";
			else if ($key=="id") $str .= "(".LDAP_UIDNUMBER."=".$val.")";
			else $str .= "(".$key."=".$val.")";
		
		}

		$str .= ")";
		
		return $str;		
	
	}

	function setDBFilter($filter) {

		if (!is_array($filter)) return $filter;
	
		$str = null;
	
		foreach ($filter AS $key=>$val) {
		
			if ($key=="login") $str .= "(login ILIKE '".$val."%') OR ";
			else if ($key=="name") 
			{

				$arr = organizeName($val);
				
				if (count($arr)==1)
				{
					$str .= "(first_name ILIKE '".$arr["ln"]."%' OR last_name ILIKE '".$arr["ln"]."%') OR ";
				}
				else
				{
					$str .= "(first_name ILIKE '".$arr["fn"]."%' AND last_name ILIKE '".$arr["ln"]."%') OR ";
				}

			}
			else if ($key=="id") $str .= "(id='$val') OR ";
			else $str .= "(".$key."='".$val."')";

		}

		if ($str) $str = substr($str,0,strlen($str)-4);

		return $str;		
	
	}

	function setLDAPSort($sort) {
	
		if ($sort=="login") $str = LDAP_UID;
		else if ($sort=="name") $str = LDAP_CN;
		else if ($sort=="id") $str = LDAP_UIDNUMBER;
	
		return $str;
	
	}

	function setDBSort($sort) {

		if ($sort=="login") $str = "login";
		else if ($sort=="name") $str = "first_name,last_name";
		else if ($sort=="id") $str = "id";
	
		return $str;
	
	}
	

	function insert($option) {

		$ret = $this->ACCT->insert($option);
		$this->throwError($this->ACCT->getError());
		return $ret;
				
	}

	function saveProfile($option) {
	
		$this->update($option);		
	
	}

	function update($option) {

		$ret = $this->ACCT->update($option);
		$this->throwError($this->ACCT->getError());
		return $ret;
		
	}

	function setPassword($option) {

		$ret = $this->ACCT->setPassword($option);
		$this->throwError($this->ACCT->getError());
		return $ret;
		
	}

	function delete($option) {

		$ret = $this->ACCT->delete($option);
		$this->throwError($this->ACCT->getError());
		return $ret;
		
	}

	function password_check($login,$password) {
	
		return $this->ACCT->password_check($login,$password);
		
	}
	
}

