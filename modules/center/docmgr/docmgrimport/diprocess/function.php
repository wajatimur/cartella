<?php

class IMPORT 
{

  private $path;
  private $error;
  private $smallthumb;
  private $largethumb;
  private $hugethumb;
  private $DB;
	private $debug;
	private $PROTO;
	
  function __construct($directory,$debug = null) {

    $this->DB = $GLOBALS["DB"];
    $this->path = $directory;
    $this->smallthumb = $this->path."/smallthumb";
    $this->largethumb = $this->path."/largethumb";
    $this->hugethumb = $this->path."/hugethumb";
    $this->PROTO = $GLOBALS["PROTO"];
    
    if ($debug) $this->debug = 1;
    
  }

  function throwError($msg) {
    
    $this->error = $msg;
    
  }
  
  function getError() {
  
    return $this->error;
    
  }

  function browse() {

    //sanity checking  
    if (!is_dir($this->path)) recurmkdir($this->path);

    $filearr = scandir($this->path);
     
    foreach ($filearr AS $file) {
    
      //skip directory markers and directories
      if ($file[0]==".") continue;
      if (is_dir($this->path."/".$file)) continue;

      //get our thumbnail
      $thumb = $this->getThumbName($file);
      $metafile = ".".str_replace(".PDF",".XML",$file);
      
      //if our thumbs don't exist, make them
      if (!file_exists($this->smallthumb."/".$thumb) || 
      	!file_exists($this->largethumb."/".$thumb) || 
				!file_exists($this->hugethumb."/".$thumb)) 
			{
					if ($this->debug) echo "Thumbnailing ".$this->path."/".$file."\n";
        	$this->thumb($this->path."/".$file);
      }

			$ret = array();
      $ret["name"] = $file;
      $ret["path"] = $this->path."/".$file;
      $ret["small_thumb"] = str_replace(SITE_PATH."/","",$this->smallthumb)."/".$thumb;
      $ret["large_thumb"] = str_replace(SITE_PATH."/","",$this->largethumb)."/".$thumb;
      $ret["huge_thumb"] = str_replace(SITE_PATH."/","",$this->hugethumb)."/".$thumb;
      $ret["size"] = displayFileSize(filesize($this->path."/".$file));

      //give us an icon for the small ones
      if (!file_exists($ret["small_thumb"])) $ret["small_thumb"] = THEME_PATH."/images/thumbnails/file.png";

      //add the contents of the xml file to our data
      if (file_exists($this->path."/".$metafile)) 
      {

        $meta = file_get_contents($this->path."/".$metafile);
        $arr = XML::decode($meta);

        if (count($arr["tag"]) > 0) 
        {

          foreach ($arr["tag"] AS $tag) 
          {
          	$ret["tag"][count($ret["tag"])] = $tag;
          }

        }
        
      }      

			$this->PROTO->add("file",$ret);
    
    }
  
  }

  function merge() {
  
    $files = $_REQUEST["filePath"];

    if (count($files)==0) {
      $this->throwError("No files specified to merge");
      return false;
    }
    if (count($files)==1) {
      $this->throwError("Must specify more than one file");
      return false;
    }

    //the output file is the first file
    $arr = explode("/",$files[0]);
    $arr[count($arr)-1] = "merged-".$arr[count($arr)-1];
    $output = implode("/",$arr);

    $cmd = "gs -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=\"".$output."\" -dBATCH";
    for ($i=0;$i<count($files);$i++)  $cmd .= " \"".$files[$i]."\"";

    //execute the command
    `$cmd`;

    //if the output file exists, delete the other files, otherwise throw an error
    if (file_exists($output)) {

      //thumbnail the new file
      $this->thumb($output);

      for ($i=0;$i<count($files);$i++) {
        unlink($files[$i]);
        $thumb = $this->getThumbName($files[$i]);
        @unlink($this->smallthumb."/".$thumb);
        @unlink($this->largethumb."/".$thumb);
      }
      
    } else {
      $this->throwError("Error creating merged file");
    }
      
  }  

  function delete() {
  
    $file = $_REQUEST["filePath"];

    if (!$file) {
      $this->throwError("No files specified to delete");
      return false;
    }

    if (unlink($file)) {

      //delete the thumbnails as well
      $tn = $this->getThumbName($file);
      @unlink($this->smallthumb."/".$tn);
      @unlink($this->largethumb."/".$tn);

    } else  $this->throwError("Error removing the file");
      
  }  

  function thumb($file=null) {

    if (!$file) $file = $_REQUEST["filePath"];

    //sanity checking
    if (!is_dir($this->smallthumb)) mkdir($this->smallthumb);
    if (!is_dir($this->largethumb)) mkdir($this->largethumb);
    if (!is_dir($this->hugethumb)) mkdir($this->hugethumb);
    if (!$file) {
      $this->throwError("No file passed to thumbnail");
      return false;
    }

    //construct($mode,$filepath,$filename,$thumb)

    //get the name
    $tmpdir = TMP_DIR."/".USER_LOGIN;
    recurmkdir($tmpdir);
    
    $filename = array_pop(explode("/",$file));
    $thumbname = $this->getThumbName($file);
    $thumb = $tmpdir."/".$thumbname;

    $d = new DOCMGR_UTIL_FILETHUMB("preview",$file,$filename,$thumb);

    $smallthumb = $this->smallthumb."/".$thumbname;
    $largethumb = $this->largethumb."/".$thumbname;
    $hugethumb = $this->hugethumb."/".$thumbname;

    system(APP_CONVERT." -resize 75x100 \"".$thumb."\" \"".$smallthumb."\"");
    system(APP_CONVERT." -resize 240x320 \"".$thumb."\" \"".$largethumb."\"");
    system(APP_CONVERT." -resize 480x640 \"".$thumb."\" \"".$hugethumb."\"");
 
  }

  function getThumbName($file) {
  
    //get the name with the new extension
    $arr = explode("/",$file);
    $name = $arr[count($arr)-1];
    $ext = return_file_extension($name);
    $name = str_replace(".".$ext,".png",$name);
    return $name;
  
  }

  function rename() {
  
    $file = $_REQUEST["filePath"];
    $newname = $_REQUEST["name"];
    
    if (!$file) {
      $this->throwError("No files specified to delete");
      return false;
    }
    if (!$newname) {
      $this->throwError("You must specify a new file name");
      return false;
    }

    //get the path of the file
    $arr = explode("/",$file);
    array_pop($arr);
    $dest = implode("/",$arr)."/".$newname;    

    //thumbnail names
    $thumb = $this->getThumbName($file);
    $newthumb = $this->getThumbName($newname);
    
    if (rename($file,$dest)) {

      //rename the thumbnails as well
      @rename($this->smallthumb."/".$thumb,$this->smallthumb."/".$newthumb);
      @rename($this->largethumb."/".$thumb,$this->largethumb."/".$newthumb);
      @rename($this->hugethumb."/".$thumb,$this->hugethumb."/".$newthumb);

    } else  $this->throwError("Error removing the file");
      
  }  

  function companyList() {

    $sql = "SELECT name FROM ewp_company ORDER BY name";
    $list = $this->DB->fetch($sql);
    
    $xml = null;

    for ($i=0;$i<$list["count"];$i++) {

      //make the name db friendly
      $list[$i]["name"] = str_replace("/","-",$list[$i]["name"]);
      $list[$i]["name"] = preg_replace("/[^a-z0-9, -]/i","",$list[$i]["name"]);

			$this->PROTO->add("company",$list[$i]);

    }  
  
    return $xml;
    
  }


  function advedit() {

    //setup our temp directory and make sure it's empty
    $tmp = TMP_DIR."/".USER_LOGIN."/docmgradvedit";
    recurmkdir($tmp);
    $cmd = "rm -r ".$tmp."/*";
    `$cmd`;

    //we'll have several copies of files.  tiffs that we can actually work on, and pngs that we will see in the browser
    $tifftmp = $tmp."/tiff";
    $pngtmp = $tmp."/png";
    $hugepngtmp = $tmp."/hugepng";
    mkdir($tifftmp);
    
    //sanity checking  
    if (!is_dir($tmp)) {
      $this->throwError("Could not create temp directory");
      return false;
    }

    //split our pdf into individual files
    system("pdftoppm \"".$_REQUEST["filePath"]."\" ".$tifftmp."/file");
    `cp -R $tifftmp $pngtmp`;
    `cp -R $tifftmp $hugepngtmp`;

    //shrink the png files to something manageable for viewing
    $cmd = "mogrify -format png -geometry 75x100 ".$pngtmp."/*";
    `$cmd`;
    $cmd = "mogrify -format png -geometry 480x640 ".$hugepngtmp."/*";
    `$cmd`;
      
    $filearr = scandir($pngtmp);
    $xml = null;
     
    foreach ($filearr AS $file) {
    
      //skip directory markers and directories
      if ($file=="." || $file==".." || strstr($file,".ppm")) continue;

			$ret = array();
			$ret["name"] = $file;
			$ret["path"] = $tifftmp."/".str_replace(".png",".ppm",$file);			//point to the real file
      $ret["small_thumb"] = $pngtmp."/".$file;			//point to the real file
      $ret["huge_thumb"] = $hugepngtmp."/".$file;			//point to the real file

			$this->PROTO->add("file",$ret);
			    
    }
  
    return $xml;
  
  }

  function rotate() {

    //setup our temp directory and make sure it's empty
    $tmp = TMP_DIR."/".USER_LOGIN."/docmgradvedit";

    //we'll have several copies of files.  tiffs that we can actually work on, and pngs that we will see in the browser
    $tifftmp = $tmp."/tiff";
    $pngtmp = $tmp."/png";
    $hugepngtmp = $tmp."/hugepng";

    if ($_REQUEST["direction"]=="right") $deg = "90";
    elseif ($_REQUEST["direction"]=="flip") $deg = "180";
    else $deg = "270";

    foreach ($_REQUEST["file"] AS $file) {     

      if ($file=="." || $file=="..") continue;

      //rotate the real file
      `mogrify -rotate $deg $file`;

      //rotate the thumbnail
      $fn = array_pop(explode("/",$file));
      $thumbpath = $pngtmp."/".str_replace(".ppm",".png",$fn);
      `mogrify -rotate $deg $thumbpath`;

      //rotate the huge thumbnail
      $fn = array_pop(explode("/",$file));
      $thumbpath = $hugepngtmp."/".str_replace(".ppm",".png",$fn);
      `mogrify -rotate $deg $thumbpath`;
      
    }

    //if the order was changed, save that first
    if ($_REQUEST["saveorder"]) $this->reorder();    

    //rescan and return the new directory structure
    $filearr = scandir($tifftmp);
    
    foreach ($filearr AS $file) {

      if ($file=="." || $file=="..") continue;

      //spit out the new file list 
      $ret = array();
	    $ret["name"] = $file;
      $ret["path"] = $tifftmp."/".$file;			//point to the real file
      $ret["small_thumb"] = str_replace(SITE_PATH."/","",$pngtmp)."/".str_replace(".ppm",".png",$file);			//point to the real file
      $ret["huge_thumb"] = str_replace(SITE_PATH."/","",$hugepngtmp)."/".str_replace(".ppm",".png",$file);			//point to the real file

			$this->PROTO->add("file",$ret);

    }
    
    return $xml;
  
  }
  
  function reorder() {

    //setup our temp directory and make sure it's empty
    $tmp = TMP_DIR."/".USER_LOGIN."/docmgradvedit";

    //we'll have several copies of files.  tiffs that we can actually work on, and pngs that we will see in the browser
    $tifftmp = $tmp."/tiff";
    $pngtmp = $tmp."/png";

    //directories for putting renamed items in
    $tiffrename = $tmp."/tiffreorder";
    $pngrename = $tmp."/pngreorder";
    mkdir($tiffrename);
    mkdir($pngrename);
    
    $filearr = scandir($tifftmp);

    $c = 0;
    $filenum = 1;
    
    foreach ($_REQUEST["reorderFile"] AS $file) {

      $newfile = "file-".str_pad($filenum,"6","0",STR_PAD_LEFT).".ppm";
      $filenum++;

      $new = $tiffrename."/".$newfile;
      rename($file,$new);

      //extract filename only to reference thumbs
      $fn = array_pop(explode("/",$file));
      
      //do again for the thumbnails
      $oldpng = $pngtmp."/".str_replace(".ppm",".png",$fn);
      $newpng = $pngrename."/".str_replace(".ppm",".png",$newfile);
      rename($oldpng,$newpng);


    }

    //remove old directories and replace with newly renamed files
    `rm -r $tifftmp; rm -r $pngtmp`;
    `mv $tiffrename $tifftmp; mv $pngrename $pngtmp`;      

  }

  function commit() {
  
    //setup our temp directory and make sure it's empty
    $tmp = TMP_DIR."/".USER_LOGIN."/docmgradvedit";

    //we'll have several copies of files.  tiffs that we can actually work on, and pngs that we will see in the browser
    $tifftmp = $tmp."/tiff";
    $pngtmp = $tmp."/png";

    //if the order was changed, save that first
    if ($_REQUEST["saveorder"]) $this->reorder();    

    //convert all to individual pdf files
    $cmd = "mogrify -format pdf ".$tifftmp."/*.ppm";
    `$cmd`;
      
    $filearr = scandir($tifftmp);

    $cmd = "gs -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=\"".$_REQUEST["filePath"]."\" -dBATCH";

    foreach ($filearr AS $file) {
      if (strstr($file,".pdf")) $cmd .= " \"".$tifftmp."/".$file."\"";
    }

    //remerge all files back over the original
    `$cmd`;

    //rethumb the file
    $this->thumb($_REQUEST["filePath"]);
    $this->thumb($_REQUEST["filePath"]);
  }


	
	
}

