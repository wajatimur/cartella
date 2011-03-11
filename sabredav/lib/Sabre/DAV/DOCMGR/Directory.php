<?php

$GLOBALS["mycounter"] = 0;
/**
 * Directory class 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id: Directory.php 706 2010-01-10 15:09:17Z evertpot $
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_DOCMGR_Directory extends Sabre_DAV_DOCMGR_Node implements Sabre_DAV_IDirectory, Sabre_DAV_IQuota {

    protected $objects;
    protected $object;
    
    /**
     * Creates a new file in the directory 
     * 
     * data is a readable stream resource
     *
     * @param string $name Name of the file 
     * @param resource $data Initial payload 
     * @return void
     */
    public function createFile($name, $data = null) {

      global $DOCMGR;

      //write the file to a temp directory
      $tmpdir = TMP_DIR."/".USER_LOGIN;
      recurmkdir($tmpdir);

      $tmpfile = $tmpdir."/".md5(rand()).".dat";
      file_put_contents($tmpfile,$data);
      
      $opt = null;
      $opt["name"] = $name;
      $opt["filepath"] = $tmpfile;
      $opt["parent_path"] = $this->path;
      $opt["command"] = "docmgr_file_save";      

      //make it hidden if it starts with a "."
      if ($name[0]==".") $opt["hidden"] = "t";

      $arr = $DOCMGR->call($opt);

    }

    /**
     * Creates a new subdirectory 
     * 
     * @param string $name 
     * @return void
     */
    public function createDirectory($name) {

      global $DOCMGR;

      $opt = null;
      $opt["name"] = $name;
      $opt["parent_path"] = $this->path;
      $opt["object_type"] = "collection";
      $opt["command"] = "docmgr_object_save";
      $arr = $DOCMGR->call($opt);

    }

    /**
     * Returns a specific child node, referenced by its name 
     * 
     * @param string $name 
     * @throws Sabre_DAV_Exception_FileNotFound
     * @return Sabre_DAV_INode 
     */
    public function getChild($name,$objinfo=null) {

      global $DOCMGR;

        if (!$path) $path = "/";

        $path = $this->path . '/' . $name;

        $path = str_replace("/webdav.php","/",$path);
        $path = str_replace("/DavWWWRoot","/",$path);
        $path = str_replace("//","/",$path);

        if ($path=="/")
        {
          return new Sabre_DAV_DOCMGR_Directory($path);
        }
        else
        {

          if ($objinfo) $this->object = $objinfo;
          else
          {
          
            $opt = null;
            $opt["command"] = "docmgr_object_getinfo";
            $opt["path"] = $path;
            $arr = $DOCMGR->call($opt);

            $this->object = $arr["object"][0];

          }
          
          if ($this->object)
          {
          
            if ($this->object["object_type"]=="collection")
            {
              return new Sabre_DAV_DOCMGR_Directory($path);            
            }
            else
            {
              return new Sabre_DAV_DOCMGR_File($path);            
            }          

          }
          else
          {
          
            throw new Sabre_DAV_Exception_FileNotFound('File with name ' . $path . ' could not be located');          

          }
          
    
        }
            
    }

    /**
     * Returns an array with all the child nodes 
     * 
     * @return Sabre_DAV_INode[] 
     */
    public function getChildren() 
    {

          global $DOCMGR;

          $opt = null;
          $opt["command"] = "docmgr_search_browse";
          $opt["path"] = $this->path;
          $opt["no_paginate"] = 1;
          $arr = $DOCMGR->call($opt);

          $this->objects = $arr["object"];
          $_SESSION["docmgr_objects"] = $this->objects;
          
          $num = count($this->objects);
          $nodes = array();
          
          for ($i=0;$i<$num;$i++)
          {
            $nodes[] = $this->getChild($this->objects[$i]["name"],$this->objects[$i]);
          }


          return $nodes;

    }

    /**
     * Deletes all files in this directory, and then itself 
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
     * Returns available diskspace information 
     * 
     * @return array 
     */
    public function getQuotaInfo() {

        return array(
            @disk_total_space($this->path)-@disk_free_space($this->path),
            @disk_free_space($this->path)
            ); 

    }

    public function getObj($path)
    {

      global $DOCMGR;

      $objects = $_SESSION["docmgr_objects"];
      $num = count($objects);
      $obj = null;

      for ($i=0;$i<$num;$i++)
      {
        if ($objects[$i]["object_path"]==$path)
        {
          $obj = $objects[$i];
          break;
        }
      }

      //path error, hit the api directly
      if (!$obj)
      {

        $opt = null;
        $opt["command"] = "docmgr_object_getinfo";
        $opt["path"] = $path;
        $arr = $DOCMGR->call($opt);

        $obj = $arr["object"][0];

      }
 
      return $obj;
      
    }
 
}

