<?php

/**
 * The Lock manager allows you to handle all file-locks centrally.
 *
 * This Lock Manager stores all its data in the filesystem. By default it will do this in PHP's standard temporary session directory,
 * but this can be overriden by specifiying an alternative path in the contructor
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id: FS.php 706 2010-01-10 15:09:17Z evertpot $
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Locks_Backend_DOCMGR extends Sabre_DAV_Locks_Backend_Abstract {

    public function __construct() {

    }

    /**
     * Returns a list of Sabre_DAV_Locks_LockInfo objects  
     * 
     * This method should return all the locks for a particular uri, including
     * locks that might be set on a parent uri.
     *
     * @param string $uri 
     * @return array 
     */
    public function getLocks($uri) {


        global $DOCMGR;
        
        $opt = null;
        $opt["command"] = "docmgr_lock_get";
        $opt["path"] = $uri;
        $arr = $DOCMGR->call($opt);

        $lockList = array();

        for ($i=0;$i<count($arr["lock"]);$i++)
        {

          $row = $arr["lock"][$i];
 
          $lockInfo = new Sabre_DAV_Locks_LockInfo();
          $lockInfo->owner = $row['owner'];
          $lockInfo->token = $row['token'];
          $lockInfo->timeout = $row['timeout'];
          $lockInfo->created = $row['created'];
          $lockInfo->scope = $row['scope'];
          $lockInfo->depth = $row['depth'];
          $lockInfo->uri = $row['uri'];
          $lockList[] = $lockInfo;
 
        }

        return $lockList;

    }

    /**
     * Locks a uri 
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return bool 
     */
    public function lock($uri,Sabre_DAV_Locks_LockInfo $lockInfo) {

        global $DOCMGR;

        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 1800;
        $lockInfo->created = time();

        $opt = null;
        $opt["owner"] = $lockInfo->owner;
        $opt["token"] = $lockInfo->token;	
        $opt["timeout"] = $lockInfo->timeout;
        $opt["created"] = $lockInfo->created;
        $opt["scope"] = $lockInfo->scope;
        $opt["depth"] = $lockInfo->depth;
        $opt["uri"] = $lockInfo->uri;
        $opt["command"] = "docmgr_lock_set";
        $opt["path"] = $uri;
        $arr = $DOCMGR->call($opt);

    }

    /**
     * Removes a lock from a uri 
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return bool 
     */
    public function unlock($uri,Sabre_DAV_Locks_LockInfo $lockInfo) {

        global $DOCMGR;

        $opt = null;
        $opt["token"] = $lockInfo->token;
        $opt["command"] = "docmgr_lock_clear";
        $opt["path"] = $uri;
        $arr = $DOCMGR->call($opt);

    }


}
