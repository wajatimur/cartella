<?php

class ACCOUNTSAVE {

  private $accountId;
  private $DB;
  private $CDB;
  private $error;
  private $ACCOUNT;
    
  function __construct($aid=null) {
  
    $this->DB = $GLOBALS["DB"];
    $this->CDB = $GLOBALS["CDB"];

    if ($aid) {
      $this->accountId = $aid;
      if (!$this->sanityCheck()) return false;
    }

    $this->ACCOUNT = new ACCOUNT($aid);
    
  }

  function sanityCheck() {

    if ($this->accountId!=USER_ID && !bitset_compare(BITSET,MANAGE_USERS,ADMIN)) {
      $this->throwError("You do not have permission to view information for this account");
      return false;
    }

    return true;

  }

  function throwError($msg) {
  
    $this->error = $msg;
    
  }
  
  function getError() {
  
    return $this->error;
    
  }

  /*******************************************************
    profile
  *******************************************************/
  function saveProfile($data = null) {

    if (!$data) $data = $_POST;

    //save basic profile information
    $this->ACCOUNT->saveProfile($data);

    //if ldap threw an error, keep it for later
    $err = $this->ACCOUNT->getError();
    if ($err) $this->throwError($err);
  
  }

  /*******************************************************
    password
  *******************************************************/
  function savePassword($data = null) {

    if (!$data) $data = $_POST;

    //save basic profile information
    $this->ACCOUNT->setPassword($data["password"]);

    //if ldap threw an error, keep it for later
    $err = $this->ACCOUNT->getError();
    if ($err) $this->throwError($err);
  
  }

  /*******************************************************
    DOCMGR settings
  *******************************************************/
  function saveDocmgrSetting($data = null) {

    if (!$data) $data = $_POST;

    //look for a record for this account
    $sql = "SELECT account_id FROM auth_settings WHERE account_id='".$this->accountId."'";
    $info = $this->DB->single($sql);
    
    $opt = null;
    $opt["language"] = $data["language"];
    $opt["editor"] = $data["editor"];
    
    if ($info) 
    {
      $opt["where"] = "account_id='".$this->accountId."'";
      $this->DB->update("auth_settings",$opt);
    } else 
    {
      $opt["account_id"] = $this->accountId;
      $this->DB->insert("auth_settings",$opt);
    }

    //update account settings
    $_SESSION["accountSettings"] = null;
    setAccountSettings();

    //if ldap threw an error, keep it for later
    $err = $this->DB->error();
    if ($err) $this->throwError($err);
  
  }

  function saveGroup($data = null) {
  
    if (!$data) $data = $_POST;

    $sql = "DELETE FROM auth_grouplink WHERE accountid='".$this->accountId."';";
    $this->DB->query($sql);

    if ($data["groupId"]) {

      foreach ($data["groupId"] AS $gid) 
      {

        $opt = null;
        $opt["accountid"] = $this->accountId;
        $opt["groupid"] = $gid;
        $this->DB->insert("auth_grouplink",$opt);

      }

    }

    $err = $this->DB->error();
    if ($err) $this->throwError($err);

  }

  function savePermission($data = null) {
  
    if (!$data) $data = $_POST;

    $p = new PERM($this->accountId);
    $p->saveAccount($data["perm"]);

    $sql = "UPDATE auth_accountperm SET enable='".$data["enable"]."' WHERE account_id='".$this->accountId."';";
    $this->DB->query($sql);

    $err = $this->DB->error();
    if ($err) $this->throwError($err);
        
  }


  /*******************************************************
    profile
  *******************************************************/
  function createUser($data = null) {

    if (!$data) $data = $_POST;

    $this->DB->begin();
    
    //save basic profile information
    $this->accountId = $this->ACCOUNT->insert($data);

    //set our default settings for this account
    $opt = null;
    $opt["account_id"] = $this->accountId;
    $opt["editor"] = "web";
    $this->DB->insert("auth_settings",$opt);

    //get perms for "Everyone" group
    $sql = "SELECT bitmask FROM auth_groupperm WHERE group_id='0'";
    $info = $this->DB->single($sql);
    
    //default permissions
    $opt = null;
    $opt["bitmask"] = $info["bitmask"];
    $opt["where"] = "account_id='".$this->accountId."'";
    $this->DB->update("auth_accountperm",$opt);
  
    $this->DB->end();
       
    //if ldap threw an error, keep it for later
    $err = $this->ACCOUNT->getError();
    if ($err) $this->throwError($err);

    $GLOBALS["PROTO"]->add("account_id",$this->accountId);
  
  }

  function deleteAccount($data = null) {

    $this->ACCOUNT->delete($data);
    
    //if ldap threw an error, keep it for later
    $err = $this->ACCOUNT->getError();
    if ($err) $this->throwError($err);
  
  }
    
}

