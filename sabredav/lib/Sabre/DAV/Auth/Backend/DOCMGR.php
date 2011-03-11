<?php

/**
 * This is an authentication backend that uses a file to manage passwords.
 *
 * The backend file must conform to Apache's htdigest format
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id: File.php 866 2010-02-05 09:55:25Z evertpot@gmail.com $
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Auth_Backend_DOCMGR extends Sabre_DAV_Auth_Backend_AbstractDigest {

    /**
     * List of users 
     * 
     * @var array
     */
    protected $users = array();

    /**
     * Returns a users' information
     * 
     * @param string $realm 
     * @param string $username 
     * @return string 
     */
    public function getUserInfo($realm,$username) {

      $a = new ACCOUNT();
      $matches = $a->search(array("login"=>$username));

      if (count($matches) > 0)
      {

        $username = $matches[0]["login"];
        $password = $matches[0]["password"];      

        //if the digest_hash is stored in the db, use it.  otherwise create
        if ($matches[0]["digest_hash"]) $dg = $matches[0]["digest_hash"];
        else $dg = md5($username.":".$realm.":".$password);

        $ret = array(	"userId"=>$username,
                       "digestHash"=>$dg,
                       "password"=>$password,
                       "uri"=>"principals/".$username
                       );

        return $ret;
              
      }
      else return false;

    }

    public function getUsers()
    {

      if ($_SESSION["_DOCMGR_USER_INFO"]) $matches = $_SESSION["_DOCMGR_USER_INFO"];
      else
      {
    
        $a = new ACCOUNT();
        $matches = $a->search(array("login"=>$username),"login");

        $_SESSION["_DOCMGR_USER_INFO"] = $matches;
        
      }
      
      $ret = array();
      $num = count($matches);
      
      for ($i=0;$i<$num;$i++) $ret[] = array("userId"=>$matches[$i]["login"],
                                              "uri"=>"principals/".$matches[$i]["login"]);
    
      return $ret;
      
    }


    /**
     * Authenticates the user based on the current request.
     *
     * If authentication is succesful, true must be returned.
     * If authentication fails, an exception must be thrown.
     *
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return bool 
     */
    public function authenticate(Sabre_DAV_Server $server,$realm) {

        $digest = new Sabre_HTTP_DigestAuth();

        // Hooking up request and response objects
        $digest->setHTTPRequest($server->httpRequest);
        $digest->setHTTPResponse($server->httpResponse);

        $digest->setRealm($realm);
        $digest->init();

        $username = $digest->getUsername();

        // No username was given
        if (!$username) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('No digest authentication headers were found');
        }

        $userData = $this->getUserInfo($realm, $username);
        // If this was false, the user account didn't exist
        if ($userData===false) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('The supplied username was not on file');
        }
        if (!is_array($userData)) {
            throw new Sabre_DAV_Exception('The returntype for getUserInfo must be either false or an array');
        }

        if (!isset($userData['uri']) || !isset($userData['digestHash'])) {
            throw new Sabre_DAV_Exception('The returned array from getUserInfo must contain at least a uri and digestHash element');
        }

        // If this was false, the password or part of the hash was incorrect.
        if (!$digest->validateA1($userData['digestHash'])) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('Incorrect username');
        }

        $this->currentUser = $userData;

        //init the api
        if (class_exists("APICLIENT")) $GLOBALS["DOCMGR"] = new APICLIENT($userData["userId"],$userData["password"]);

        return true;

    }


}


