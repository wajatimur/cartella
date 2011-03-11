<?php

/**
 * File class 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id: File.php 706 2010-01-10 15:09:17Z evertpot $
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_DOCMGR_File extends Sabre_DAV_DOCMGR_Node implements Sabre_DAV_IFile {

    /**
     * Updates the data 
     * 
     * @param resource $data 
     * @return void 
     */

    function getAllHeaders()
    {
          
      foreach ($_SERVER as $name => $value) 
      {
         if (substr($name, 0, 5) == 'HTTP_') 
        {
          $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
      }
      return $headers;     
    }

    public function put($data) {

      global $DOCMGR;

      //write the file to a temp directory
      $tmpdir = TMP_DIR."/".USER_LOGIN;
      recurmkdir($tmpdir);

      $tmpfile = $tmpdir."/".md5(rand()).".dat";

      file_put_contents($tmpfile,$data);

      $name = array_pop(explode("/",$this->path));

      $opt = null;
      $opt["name"] = $name;
      $opt["filepath"] = $tmpfile;
      $opt["command"] = "docmgr_file_save";
      $opt["path"] = $this->path;

      if ($name[0]==".") $opt["hidden"] = "t";
      
      $opt["token"] = $this->getLockToken();

      $arr = $DOCMGR->call($opt);

      @unlink($tmpfile);
                                                                                          
    }

    function getLockToken()
    {

      $token = null;
    
      foreach ($_SERVER as $name => $value) 
      {
         if (substr($name, 0, 5) == 'HTTP_') 
         {

           $key = substr($name,5);

          //windows uses this
          if ($key=="IF")
          {

            $prefix = "<opaquelocktoken:";

            $pos = strpos($value,$prefix);
            if ($pos!==FALSE) 
            {
            
              $token = substr($value,strlen($prefix)+1);
              $token = substr($token,0,strpos($token,">"));
              break;  
            }

          }
          //Finder uses this
          else if ($key=="LOCK_TOKEN")
          {

            $prefix = "<opaquelocktoken:";

            $pos = strpos($value,$prefix);
            if ($pos!==FALSE) 
            {
            
              $token = substr($value,strlen($prefix)+1);
              $token = substr($token,0,strpos($token,">"));
              break;  
            }

          }

        }

      }

      return $token;

    }
    
    /**
     * Returns the data 
     * 
     * @return string 
     */
    public function get() {


      global $DOCMGR;

      $obj = Sabre_DAV_DOCMGR_Directory::getObj($this->path);

      if ($obj["object_type"]=="document") 
      {

        //just delete the entire directory
        $opt = null;
        $opt["command"] = "docmgr_document_get";
        $opt["stream"] = 1;
        $opt["path"] = $this->path;
        $arr = $DOCMGR->call($opt);

        return $arr["stream"];

      }
      else
      {

        //just delete the entire directory
        $opt = null;
        $opt["command"] = "docmgr_file_get";
        $opt["path"] = $this->path;
        $opt["stream"] = 1;
        $arr = $DOCMGR->call($opt);

        return $arr["stream"];

      }
      
    }

    /**
     * Delete the current file
     *
     * @return void 
     */
    public function delete() {

      global $DOCMGR;

      //just delete the entire directory
      $opt = null;
      $opt["command"] = "docmgr_object_delete";
      $opt["path"] = $this->path;
      $arr = $DOCMGR->call($opt);

    }

    /**
     * Returns the size of the node, in bytes 
     * 
     * @return int 
     */
    public function getSize() {

      $obj = Sabre_DAV_DOCMGR_Directory::getObj($this->path);
      return $obj["filesize"];

    }

    /**
     * Returns the ETag for a file
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     *
     * Return null if the ETag can not effectively be determined
     * 
     * @return mixed
     */
    public function getETag() {

        return null;

    }

    /**
     * Returns the mime-type for a file
     *
     * If null is returned, we'll assume application/octet-stream
     *
     * @return mixed 
     */ 
    public function getContentType() {

      $obj = Sabre_DAV_DOCMGR_Directory::getObj($this->path);

      if ($obj["object_type"]=="document") $type = "text/html";
      else
      {
        $fn = array_pop(explode("/",$this->path));
        $type =  return_file_mime($fn);
      }
      
      return $type;

    }

}

