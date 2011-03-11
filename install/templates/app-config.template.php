<?php
/*****************************************************************************************
  Fileame: app-config.php

  Purpose: Contains all settings external applications called by docmgr
           This is part of an effort to abstract out all program calls.  DocMGR should
           expect only a certain kind of response from the calling program.  The used program
           should contain the appropriate cli options to pass the desired output.
  Created: 05-07-2006

******************************************************************************************/

//setup
$arr = array("value","enabled","description","hidden from user","required");

$template = array();
$template["Apps"] = array();
$template["Apps"]["OPENOFFICE_PATH"] = array("/opt/openoffice.org3",true,"Path to openoffice installation",false,true);
$template["Apps"]["APP_OCR"] = array("ocrad --format=utf8",true,"OCR Program.  All content should be output to stdout",false,false);
$template["Apps"]["APP_WGET"] = array("wget -O",true,"WGET for url objects.  Outputs to file",false,true);
$template["Apps"]["APP_PDFTOTEXT"] = array("pdftotext -nopgbrk -q",true,"Dont' remove the -nopgbrk option",false,true);
$template["Apps"]["APP_PDFIMAGES"] = array("pdfimages -q",true,"PDF Images processing",false,true);
$template["Apps"]["APP_SENDMAIL"] = array("sendmail",true,"Path to sendmail",false,true);
$template["Apps"]["APP_PHP"] = array("php",true,"PHP CLI binary",false,true);
$template["Apps"]["APP_CLAMAV"] = array("clamscan",true,"Virus scanner",false,false);
$template["Apps"]["APP_TIFFINFO"] = array("tiffinfo",true,"Tiff Info",false,true);
$template["Apps"]["APP_TIFFSPLIT"] = array("tiffsplit",true,"Tiff Split",false,true);
$template["Apps"]["APP_CONVERT"] = array("convert",true,"Imagemagick convert",false,true);
$template["Apps"]["APP_MOGRIFY"] = array("mogrify",true,"Imagemagick mogrify",false,true);
$template["Apps"]["APP_MONTAGE"] = array("montage",true,"Imagemagick montage",false,true);
$template["Apps"]["APP_IDENTIFY"] = array("identify",true,"Imagemagick identify",false,true);

