<?php

/**
 * Base node-class 
 *
 * The node class implements the method used by both the File and the Directory classes 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id: Node.php 706 2010-01-10 15:09:17Z evertpot $
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_DOCMGR_Node implements Sabre_DAV_ILockable, Sabre_DAV_IProperties {

    /**
     * The path to the current node
     * 
     * @var string 
     */
    protected $path; 

    /**
     * Sets up the node, expects a full path name 
     * 
     * @param string $path 
     * @return void
     */
    public function __construct($path) {

        $this->path = $path;
        $this->path = str_replace(BASE_URI,"/",$this->path);
        
    }

    /**
     * Returns the name of the node 
     * 
     * @return string 
     */
    public function getName() {

        return basename($this->path);

    }


    /**
     * Returns all the locks on this node
     * 
     * @return array 
     */
    function getLocks() {

      global $DOCMGR;

      $opt = null;
      $opt["command"] = "docmgr_lock_get";
      $opt["path"] = $this->path;
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
     * Locks this node 
     * 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return void
     */
    function lock(Sabre_DAV_Locks_LockInfo $lockInfo) {

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
        $opt["path"] = $this->path;
        $opt["command"] = "docmgr_lock_set";
        $arr = $DOCMGR->call($opt);
                                 
    }

    /**
     * Removes a lock from this node
     * 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return bool 
     */
    function unlock(Sabre_DAV_Locks_LockInfo $lockInfo) {

      global $DOCMGR;

        $opt = null;
        $opt["token"] = $lockInfo->token;
        $opt["command"] = "docmgr_lock_clear";
        $opt["path"] = $this->path;
        $arr = $DOCMGR->call($opt);

        if ($arr["error"]) return false;
        else return true;

    }

    /**
     * Updates properties on this node,
     *
     * The mutations array, contains arrays with mutation information, with the following 3 elements:
     *   * 0 = mutationtype (1 for set, 2 for remove)
     *   * 1 = nodename (encoded as xmlnamespace#tagName, for example: http://www.example.org/namespace#author
     *   * 2 = value, can either be a string or a DOMElement
     * 
     * This method should return a similar array, with information about every mutation:
     *   * 0 - nodename, encoded as in the $mutations argument
     *   * 1 - statuscode, encoded as http status code, for example
     *      200 for an updated property or succesful delete
     *      201 for a new property
     *      403 for permission denied
     *      etc..
     *
     * @param array $mutations 
     * @return void
     */
    function updateProperties($mutations) {

      global $DOCMGR;

        $resourceData = $this->getProperties(array());
        
        $result = array();

        foreach($mutations as $mutation) {

            switch($mutation[0]){ 
                case Sabre_DAV_Server::PROP_SET :
                   if (isset($resourceData[$mutation[1]])) {
                       $result[] = array($mutation[1],200);
                   } else {
                       $result[] = array($mutation[1],201);
                   }
                   $resourceData[$mutation[1]] = $mutation[2];
                   break;
                case Sabre_DAV_Server::PROP_REMOVE :
                   if (isset($resourceData[$mutation[1]])) {
                       unset($resourceData[$mutation[1]]);
                   }
                   // Specifying the deletion of a property that does not exist, is _not_ an error
                   $result[] = array($mutation[1],200);
                   break;

            }

        }

        $opt = null;
        $opt["command"] = "docmgr_object_saveproperties";
        $opt["properties"] = $resourceData;
        $opt["path"] = $this->path;
        $arr = $DOCMGR->call($opt);

        return $result;

    }

    /**
     * Returns a list of properties for this nodes.
     *
     * The properties list is a list of propertynames the client requested, encoded as xmlnamespace#tagName, for example: http://www.example.org/namespace#author
     * If the array is empty, all properties should be returned
     *
     * @param array $properties 
     * @return void
     */
    function getProperties($properties) {

      global $DOCMGR;

        $opt = null;
        $opt["command"] = "docmgr_object_getproperties";
        $opt["path"] = $this->path;
        $arr = $DOCMGR->call($opt);

        $propData = $arr["properties"][0];
        if (!$propData) $propData = array();
       
        // if the array was empty, we need to return everything
        if (!$properties || count($properties)=="0") return $propData;

        $props = array();
        foreach($properties as $property) {
            if (isset($propData[$property])) $props[$property] = $propData[$property];
        }

        return $props;

    }

    /**
     * Renames the node
     *
     * @param string $name The new name
     * @return void
     */
    public function setName($name) {

      global $DOCMGR;

        $opt = null; 
        $opt["command"] = "docmgr_object_save";
        $opt["name"] = basename($name);
        $opt["parent_path"] = dirname($this->path);
        $opt["path"] = $this->path;
        $DOCMGR->call($opt);
                                       
        $this->path = dirname($this->path) .'/' . basename($name);

    }

    public function delete() {

        return $this->deleteResourceData();

    }

    /**
     * Returns the last modification time, as a unix timestamp 
     * 
     * @return int 
     */
    public function getLastModified() {

      $obj = Sabre_DAV_DOCMGR_Directory::getObj($this->path);

      return strtotime($obj["last_modified"]);
      
    }


}


