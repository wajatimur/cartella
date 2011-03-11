<?php

/**
 * Temporary File Filter Plugin
 *
 * The purpose of this filter is to intercept some of the garbage files
 * operation systems and applications tend to generate when mounting
 * a WebDAV share as a disk.
 *
 * It will intercept these files and place them in a separate directory.
 * these files are not deleted automatically, so it is adviceable to
 * delete these after they are not accessed for 24 hours.
 *
 * Currently it supports:
 *   * OS/X style resource forks and .DS_Store
 *   * desktop.ini and Thumbs.db (windows)
 *   * .*.swp (vim temporary files)
 *   * .dat.* (smultron temporary files)
 *
 * @package Sabre
 * @subpackage DAV
 * @version $Id: TemporaryFileFilterPlugin.php 706 2010-01-10 15:09:17Z evertpot $
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_TemporaryFileFilterLockPlugin extends Sabre_DAV_ServerPlugin {

    
    /**
     * This is the directory where this plugin
     * will store it's files.
     * 
     * @var string 
     */
    private $dataDir;

    /**
     * A reference to the main Server class
     * 
     * @var Sabre_DAV_Server 
     */
    private $server;

    private $lockDir;
    
    /**
     * Creates the plugin.
     *
     * Make sure you specify a directory for your files. If you don't, we
     * will use PHP's directory for session-storage instead, and you might
     * not want that.
     * 
     * @param string $dataDir 
     * @return void
     */
    public function __construct($dataDir = null) {

        if (!$dataDir) $dataDir = ini_get('session.save_path').'/sabredav/';
        if (!is_dir($dataDir)) mkdir($dataDir);
        $this->dataDir = $dataDir;

        $this->lockDir = $dataDir."/locks/";
        if (!is_dir($this->lockDir)) mkdir($this->lockDir);        

    } 

    /**
     * Initialize the plugin
     *
     * This is called automatically be the Server class after this plugin is
     * added with Sabre_DAV_Server::addPlugin()
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->subscribeEvent('beforeMethod',array($this,'beforeMethod'));
        $server->subscribeEvent('beforeCreateFile',array($this,'beforeCreateFile'));
        $server->subscribeEvent('beforeLock',array($this,'beforeLock'));
        $server->subscribeEvent('beforeUnlock',array($this,'beforeUnlock'));

    }

    /**
     * This method is called before any HTTP method handler
     *
     * This method intercepts any GET, DELETE, PUT and PROPFIND calls to 
     * filenames that are known to match the 'temporary file' regex.
     * 
     * @param string $method 
     * @return bool 
     */
    public function beforeMethod($method) {

        if (!$tempLocation = $this->isTempFile($this->server->getRequestUri()))
            return true;

        switch($method) {
            case 'GET' :
                return $this->httpGet($tempLocation);
            case 'PUT' :
                return $this->httpPut($tempLocation);
            case 'PROPFIND' :
                return $this->httpPropfind($tempLocation);
            case 'DELETE' :
                return $this->httpDelete($tempLocation);
         }
         return true;

    }

    /**
     * This method is invoked if some subsystem creates a new file.
     *
     * This is used to deal with HTTP LOCK requests which create a new 
     * file.
     * 
     * @param string $uri 
     * @param resource $data 
     * @return bool 
     */
    public function beforeCreateFile($uri,$data) {

        if ($tempPath = $this->isTempFile($uri)) {
            
            $hR = $this->server->httpResponse;
            $hR->setHeader('X-Sabre-Temp','true');
            file_put_contents($tempPath,$data);
            return false;
        }
        return true; 

    }

    /**
     * This method will check if the url matches the temporary file pattern
     * if it does, it will return an path based on $this->dataDir for the 
     * temporary file storage.
     * 
     * @param string $path 
     * @return mixed 
     */
    protected function isTempFile($path) {

        $tempPath = basename($path);

        $tempFiles = array(
            '/^\._(.*)$/',      // OS/X resource forks
            '/^.DS_Store$/',   // OS/X custom folder settings
            '/^desktop.ini$/', // Windows custom folder settings
            '/^Thumbs.db$/',   // Windows thumbnail cache
            '/^.(.*).swp$/',   // ViM temporary files
            '/\.dat(.*)$/',     // Smultron seems to create these
        );

        $match = false;
        foreach($tempFiles as $tempFile) {

            if (preg_match($tempFile,$tempPath)) $match = true; 

        }

        if ($match) {
            return $this->dataDir . '/sabredav_' . md5($path) . '.tempfile';
        } else {
            return false;
        }

    }


    /**
     * This method handles the GET method for temporary files.
     * If the file doesn't exist, it will return false which will kick in
     * the regular system for the GET method.
     * 
     * @param string $tempLocation 
     * @return bool 
     */
    public function httpGet($tempLocation) {

        if (!file_exists($tempLocation)) return true;

        $hR = $this->server->httpResponse;
        $hR->setHeader('Content-Type','application/octet-stream');
        $hR->setHeader('Content-Length',filesize($tempLocation));
        $hR->setHeader('X-Sabre-Temp','true');
        $hR->sendStatus(200);
        $hR->sendBody(fopen($tempLocation,'r'));
        return false;

    }

    /**
     * This method handles the PUT method.
     * 
     * @param string $tempLocation 
     * @return bool 
     */
    public function httpPut($tempLocation) {

        $hR = $this->server->httpResponse;
        $hR->setHeader('X-Sabre-Temp','true');

        $newFile = !file_exists($tempLocation);
       
        if (!$newFile && ($this->server->httpRequest->getHeader('If-None-Match'))) {
             throw new Sabre_DAV_Exception_PreconditionFailed('The resource already exists, and an If-None-Match header was supplied');
        }

        file_put_contents($tempLocation,$this->server->httpRequest->getBody());
        $hR->sendStatus($newFile?201:200);
        return false;

    }

    /**
     * This method handles the DELETE method.
     *
     * If the file didn't exist, it will return false, which will make the 
     * standard HTTP DELETE handler kick in.
     * 
     * @param string $tempLocation 
     * @return bool 
     */
    public function httpDelete($tempLocation) {

        if (!file_exists($tempLocation)) return true;

        unlink($tempLocation);
        $hR = $this->server->httpResponse;
        $hR->setHeader('X-Sabre-Temp','true');
        $hR->sendStatus(204);
        return false;

    }

    /**
     * This method handles the PROPFIND method. 
     *
     * It's a very lazy method, it won't bother checking the request body
     * for which properties were requested, and just sends back a default
     * set of properties.
     *
     * @param string $tempLocation 
     * @return void
     */
    public function httpPropfind($tempLocation) {

        if (!file_exists($tempLocation)) return true;
       
        $hR = $this->server->httpResponse;
        $hR->setHeader('X-Sabre-Temp','true');
        $hR->sendStatus(207);
        $hR->setHeader('Content-Type','application/xml; charset=utf-8');

        $requestedProps = $this->server->parsePropFindRequest($this->server->httpRequest->getBody(true)); 

        $properties = array(
            'href' => $this->server->getRequestUri(),
            200 => array(
                '{DAV:}getlastmodified' => new Sabre_DAV_Property_GetLastModified(filemtime($tempLocation)),
                '{DAV:}getcontentlength' => filesize($tempLocation),
                '{DAV:}resourcetype' => new Sabre_DAV_Property_ResourceType(null),
                '{http://www.rooftopsolutions.nl/NS/sabredav}tempFile' => true, 

            ),
         );

        $data = $this->server->generateMultiStatus(array($properties));
        $hR->sendBody($data);
        return false;

    }

    /**
     * Locks an uri
     *
     * The WebDAV lock request can be operated to either create a new lock on a file, or to refresh an existing lock
     * If a new lock is created, a full XML body should be supplied, containing information about the lock such as the type 
     * of lock (shared or exclusive) and the owner of the lock
     *
     * If a lock is to be refreshed, no body should be supplied and there should be a valid If header containing the lock
     *
     * Additionally, a lock can be requested for a non-existant file. In these case we're obligated to create an empty file as per RFC4918:S7.3
     * 
     * @return void
     */
    public function beforeLock() {

        if (!$tempLocation = $this->isTempFile($this->server->getRequestUri()))
            return true;

        //setup our lockfile
        $lockfile = str_replace($this->dataDir,$this->lockDir,$tempLocation);


        $uri = $tempLocation;

        /*  FUCK IT.  we don't really care if it gets overwritten
        $lastLock = null;

        //we only allow exclusive locks.  so just load the file and see if it has our token
        if (file_exists($lockfile))
        {

          //snag from the header
          $lockToken = $this->server->httpRequest->getHeader('Lock-Token');

          //snag from the file
          $lockData = unserialize(file_get_contents($lockfile));
          $checkToken = 'opaquelocktoken:' . $lockData["token"];

          //only doing exclusive, they have to be equal          
          if ($lockToken!=$checkToken)
          {
            throw new Sabre_DAV_Exception_ConflictingLock($lastLock);
          }

        }
        */
        
        $lockInfo = new Sabre_DAV_Locks_LockInfo();
     
        $lockInfo->owner = "";

        $lockToken = '44445502';
        $id = md5(microtime() . 'somethingrandom');
        $lockToken.='-' . substr($id,0,4) . '-' . substr($id,4,4) . '-' . substr($id,8,4) . '-' . substr($id,12,12);

        $lockInfo->token = $lockToken;
        $lockInfo->scope = Sabre_DAV_Locks_LockInfo::EXCLUSIVE;
        $lockInfo->uri = $uri;
        
        if ($timeout = $this->getTimeoutHeader()) $lockInfo->timeout = $timeout;

        $newFile = false;

        // If we got this far.. we should go check if this node actually exists. If this is not the case, we need to create it first

        if (file_exists($uri))
        {
            
            // We need to call the beforeWriteContent event for RFC3744
            $this->server->broadcastEvent('beforeWriteContent',array($uri));

        } else {
            
            // It didn't, lets create it
            $this->server->file_put_contents($uri,fopen('php://memory','r')); 
            $newFile = true; 

        }

        $this->lockNode($lockfile,$lockInfo);

        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Lock-Token','opaquelocktoken:' . $lockInfo->token);
        $this->server->httpResponse->sendStatus($newFile?201:200);
        $this->server->httpResponse->sendBody($this->generateLockResponse($lockInfo));

        return false;
        
    }

    /**
     * Unlocks a uri
     *
     * This WebDAV method allows you to remove a lock from a node. The client should provide a valid locktoken through the Lock-token http header
     * The server should return 204 (No content) on success
     *
     * @return void
     */
    public function beforeUnlock() {

        if (!$tempLocation = $this->isTempFile($this->server->getRequestUri()))
            return true;

        $uri = $tempLocation;

        //setup our lockfile
        $lockfile = str_replace($this->dataDir,$this->lockDir,$tempLocation);
        
        $lockToken = $this->server->httpRequest->getHeader('Lock-Token');

        // If the locktoken header is not supplied, we need to throw a bad request exception
        if (!$lockToken) throw new Sabre_DAV_Exception_BadRequest('No lock token was supplied');

        $locks = unserialize($this->getLocks($lockfile));

        // We're grabbing the node information, just to rely on the fact it will throw a 404 when the node doesn't exist 
        //$this->server->tree->getNodeForPath($uri);

        foreach($locks as $lock) {

            if ('<opaquelocktoken:' . $lock->token . '>' == $lockToken) {

                $this->unlockNode($lockfile,$lock);
                $this->server->httpResponse->setHeader('Content-Length','0');
                $this->server->httpResponse->sendStatus(204);
                return false;

            }

        }

        // If we got here, it means the locktoken was invalid
        throw new Sabre_DAV_Exception_LockTokenMatchesRequestUri();

        
    }

    /**
     * Returns all lock information on a particular uri 
     * 
     * This function should return an array with Sabre_DAV_Locks_LockInfo objects. If there are no locks on a file, return an empty array.
     *
     * Additionally there is also the possibility of locks on parent nodes, so we'll need to traverse every part of the tree 
     *
     * @param string $uri 
     * @return array 
     */
    public function getLocks($lockfile) {

      return unserialize(file_get_contents($lockfile));

    }

    /**
     * Locks a uri
     *
     * All the locking information is supplied in the lockInfo object. The object has a suggested timeout, but this can be safely ignored
     * It is important that if the existing timeout is ignored, the property is overwritten, as this needs to be sent back to the client
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return void
     */
    public function lockNode($lockfile,Sabre_DAV_Locks_LockInfo $lockInfo) {

      // We're making the lock timeout 30 minutes
      $lockInfo->timeout = 1800;
      $lockInfo->created = time();

      $keys = array_keys($lockInfo);
      $key = $keys[0];

      file_put_contents($lockfile,serialize($lockInfo[$key]));

    }

    /**
     * Unlocks a uri
     *
     * This method removes a lock from a uri. It is assumed all the supplied information is correct and verified
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return void
     */
    public function unlockNode($lockfile,Sabre_DAV_Locks_LockInfo $lockInfo) {

      @unlink($lockfile);

      return false;

    }


    /**
     * Returns the contents of the HTTP Timeout header. 
     * 
     * The method formats the header into an integer.
     *
     * @return int
     */
    protected function getTimeoutHeader() {

        $header = $this->server->httpRequest->getHeader('Timeout');
        
        if ($header) {

            if (stripos($header,'second-')===0) $header = (int)(substr($header,7));
            else if (strtolower($header)=='infinite') $header=Sabre_DAV_Locks_LockInfo::TIMEOUT_INFINITE;
            else throw new Sabre_DAV_Exception_BadRequest('Invalid HTTP timeout header');

        } else {

            $header = 0;

        }

        return $header;

    }

    /**
     * Generates the response for successfull LOCK requests 
     * 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return string 
     */
    protected function generateLockResponse(Sabre_DAV_Locks_LockInfo $lockInfo) {

        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        
        $prop = $dom->createElementNS('DAV:','d:prop');
        $dom->appendChild($prop);

        $lockDiscovery = $dom->createElementNS('DAV:','d:lockdiscovery');
        $prop->appendChild($lockDiscovery);

        $lockObj = new Sabre_DAV_Property_LockDiscovery(array($lockInfo),true);
        $lockObj->serialize($this->server,$lockDiscovery);

        return $dom->saveXML();

    }
    
    /**
     * validateLock should be called when a write operation is about to happen
     * It will check if the requested url is locked, and see if the correct lock tokens are passed 
     *
     * @param mixed $urls List of relevant urls. Can be an array, a string or nothing at all for the current request uri
     * @param mixed $lastLock This variable will be populated with the last checked lock object (Sabre_DAV_Locks_LockInfo)
     * @return bool
     */
    protected function validateLock($urls = null,&$lastLock = null) {

        if (is_null($urls)) {
            $urls = array($this->server->getRequestUri());
        } elseif (is_string($urls)) {
            $urls = array($urls);
        } elseif (!is_array($urls)) {
            throw new Sabre_DAV_Exception('The urls parameter should either be null, a string or an array');
        }

        $conditions = $this->getIfConditions();

        // We're going to loop through the urls and make sure all lock conditions are satisfied
        foreach($urls as $url) {

            $locks = $this->getLocks($url);

            // If there were no conditions, but there were locks, we fail 
            if (!$conditions && $locks) {
                reset($locks);
                $lastLock = current($locks);
                return false;
            }
          
            // If there were no locks or conditions, we go to the next url
            if (!$locks && !$conditions) continue;

            foreach($conditions as $condition) {

                $conditionUri = $condition['uri']?$this->server->calculateUri($condition['uri']):'';

                // If the condition has a url, and it isn't part of the affected url at all, check the next condition
                if ($conditionUri && strpos($url,$conditionUri)!==0) continue;

                // The tokens array contians arrays with 2 elements. 0=true/false for normal/not condition, 1=locktoken
                // At least 1 condition has to be satisfied
                foreach($condition['tokens'] as $conditionToken) {

                    $etagValid = true;
                    $lockValid  = true;

                    // key 2 can contain an etag
                    if ($conditionToken[2]) {

                        $uri = $conditionUri?$conditionUri:$this->server->getRequestUri(); 
                        $node = $this->server->tree->getNodeForPath($uri);
                        $etagValid = $node->getETag()==$conditionToken[2]; 

                    }

                    // key 1 can contain a lock token
                    if ($conditionToken[1]) {

                        $lockValid = false;
                        // Match all the locks
                        foreach($locks as $lockIndex=>$lock) {

                            $lockToken = 'opaquelocktoken:' . $lock->token;

                            // Checking NOT
                            if (!$conditionToken[0] && $lockToken != $conditionToken[1]) {

                                // Condition valid, onto the next
                                $lockValid = true;
                                break;
                            }
                            if ($conditionToken[0] && $lockToken == $conditionToken[1]) {

                                $lastLock = $lock;
                                // Condition valid and lock matched
                                unset($locks[$lockIndex]);
                                $lockValid = true;
                                break;

                            }

                        }

                        if ($etagValid && $lockValid) continue 2;

                    }
               }
               // No conditions matched, so we fail
               throw new Sabre_DAV_Exception_PreconditionFailed('The tokens provided in the if header did not match');
            }

            // Conditions were met, we'll also need to check if all the locks are gone
            if (count($locks)) {

                // There's still locks, we fail
                $lastLock = current($locks);
                return false;

            }


        }

        // We got here, this means every condition was satisfied
        return true;

    }

    /**
     * This method is created to extract information from the WebDAV HTTP 'If:' header
     *
     * The If header can be quite complex, and has a bunch of features. We're using a regex to extract all relevant information
     * The function will return an array, containg structs with the following keys
     *
     *   * uri   - the uri the condition applies to. This can be an empty string for 'every relevant url'
     *   * tokens - The lock token. another 2 dimensional array containg 2 elements (0 = true/false.. If this is a negative condition its set to false, 1 = the actual token)
     *   * etag - an etag, if supplied
     * 
     * @return void
     */
    public function getIfConditions() {

        $header = $this->server->httpRequest->getHeader('If'); 
        if (!$header) return array();

        $matches = array();

        $regex = '/(?:\<(?P<uri>.*?)\>\s)?\((?P<not>Not\s)?(?:\<(?P<token>[^\>]*)\>)?(?:\s?)(?:\[(?P<etag>[^\]]*)\])?\)/im'; // (?:\s?)(?:\[(?P<etag>[^\]]*)\])';
        preg_match_all($regex,$header,$matches,PREG_SET_ORDER);

        $conditions = array();

        foreach($matches as $match) {

            $condition = array(
                'uri'   => $match['uri'],
                'tokens' => array(
                    array($match['not']?0:1,$match['token'],isset($match['etag'])?$match['etag']:'')
                ),    
            );

            if (!$condition['uri'] && count($conditions)) $conditions[count($conditions)-1]['tokens'][] = array(
                $match['not']?0:1,
                $match['token'],
                isset($match['etag'])?$match['etag']:''
            );
            else {
                $conditions[] = $condition;
            }

        }

        return $conditions;

    }

}




