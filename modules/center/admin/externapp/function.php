<?php

//temp language defines
define("_ENABLED","Enabled");
define("_DISABLED","Disabled");
define("_SUPPORT_STATUS","Support Status");
define("_SUPPORT_DETAILS","Support Details");
define("_SUPPORT_ENABLED","Support Enabled");
define("_SUPPORT_DISABLED","Support Disabled");
define("_BINARY_FOUND","Binary Found");
define("_BINARY_NOTFOUND","Binary Not Found");
define("_PDF_SUPPORT","PDF Support");
define("_MISC_SUPPORT","Miscellaneous Support");
define("_IMAGE_TIFF_SUPPORT","Tiff Image Support");
define("_WITH","with");
define("_RELATED_BINARIES","Related Binaries");
define("_SUPPORT","Support");

function genBinaryAvail($arr,$key) {

	if ($arr[$key]==1) return "<li class=\"successMessage\">\"".$key."\" "._BINARY_FOUND."</li>";
	else return "<li class=\"errorMessage\">\"".$key."\" "._BINARY_NOTFOUND." ".$_SERVER["PATH"]."</li>";

}

function pdfSupport() {

	//figure out which of our external progs exist
	$arr = getExternalApps();

	if (defined("PDF_SUPPORT")) $supportStatus = _ENABLED;
	else $supportStatus = _DISABLED;

	//show our pdf status
	$string = "<div class=\"formHeader\">"._SUPPORT_STATUS."</div>";
	$string .= $supportStatus;
	$string .= "<br><br>";
	
	//show our details for pdf support
	if (defined("PDF_SUPPORT")) { 	

		$string .= "<div class=\"formHeader\">"._SUPPORT_DETAILS."</div>";
		$string .= "<br>";

		if (defined("OCR_SUPPORT")) $string .= "Encapsulated PDF "._ENABLED."\n";
		else $string .= "Encapsulated PDF "._SUPPORT_DISABLED."\n";		
		$string .= "<br>";

		if (defined("THUMB_SUPPORT")) $string .= "PDF Thumbnail "._SUPPORT_ENABLED."";
		else $string .= "PDF Thumbnail "._SUPPORT_DISABLED."";
		$string .= "<br><br>";

	}

	//otherwise, just show what binaries we found
	$string .= "<div class=\"formHeader\">PDF "._RELATED_BINARIES."</div>";
	$string .= genBinaryAvail($arr,"pdftotext");
	$string .= genBinaryAvail($arr,"pdfimages");
	$string .= genBinaryAvail($arr,"ocr");
	$string .= genBinaryAvail($arr,"convert");
	$string .= genBinaryAvail($arr,"mogrify");
	$string .= genBinaryAvail($arr,"montage");

	return $string;

}


function imageSupport() {

	//figure out which of our external progs exist
	$arr = getExternalApps();

	//ocr support
	if (defined("OCR_SUPPORT")) $ocrText = "OCR "._SUPPORT_ENABLED;
	else $ocrText = "OCR "._SUPPORT_DISABLED;

	if (defined("THUMB_SUPPORT")) $thumbText = "Thumbnail "._SUPPORT_ENABLED;
	else $thumbText = "THUMB "._SUPPORT_DISABLED;

	$string = "<div class=\"formHeader\">
		   Image "._SUPPORT_DETAILS."
		   </div>
		   ";

	if ($arr["imagemagick"] && defined("OCR_SUPPORT")) 
		$string .= "Basic Image OCR "._SUPPORT_ENABLED."\n";
	else 
		$string .= "Basic Image OCR "._SUPPORT_DISABLED."\n";

	$string .= "<br>";

	if (defined("THUMB_SUPPORT") && $arr["imagemagick"]) $string .= "Basic Image Thumbnail "._SUPPORT_ENABLED."\n";
	else $string .= "Basic Image Thumbnail "._SUPPORT_DISABLED."\n";

	$string .= "<br><br>";

	$string .= "<div class=\"formHeader\">
		   TIFF "._SUPPORT_DETAILS."
		   </div>
		   ";

	if ($arr["libtiff"] && defined("OCR_SUPPORT")) 
		$string .= "TIFF Image OCR "._SUPPORT_ENABLED."\n";
	else 
		$string .= "TIFF Image OCR "._SUPPORT_DISABLED."\n";
	$string .= "<br>";

	if (defined("THUMB_SUPPORT") && $arr["libtiff"]) $string .= "TIFF Image Thumbnail "._SUPPORT_ENABLED."\n";
	else $string .= "TIFF Image Thumbnail "._SUPPORT_DISABLED."\n";
	$string .= "<br><br>";

	//otherwise, just show what binaries we found
	$string .= "<div class=\"formHeader\">Image and TIFF "._RELATED_BINARIES."</div>";
	$string .= genBinaryAvail($arr,"ocr");
	$string .= genBinaryAvail($arr,"convert");
	$string .= genBinaryAvail($arr,"mogrify");
	$string .= genBinaryAvail($arr,"montage");
	$string .= genBinaryAvail($arr,"tiffsplit");
	$string .= genBinaryAvail($arr,"tiffinfo");

	return $string;

}

function miscSupport() {

	$arr = getExternalApps();

	$string = "<div class=\"formHeader\">
			Email "._SUPPORT_DETAILS."
			</div>
			";
	
	if (!$arr["email"]) $string .= "Email "._SUPPORT_NOT_COMPILED."\n";
	else {
		$string .= "Email "._SUPPORT_ENABLED."\n";
		if (defined("PHP_IMAP_SUPPORT")) $string .= _WITH." PHP_IMAP";
		else $string .= _WITH." Sendmail";
	}

	$string .= "<br><br>";

	$string .= "<div class=\"formHeader\">ClamAV "._SUPPORT."</div>\n";
	if (defined("CLAMAV_SUPPORT")) $string .= "Clam Antivirus "._SUPPORT_ENABLED."\n";
	else $string .= "Clam AntiVirus "._SUPPORT_DISABLED."\n";
	$string .= "<br><br>";

	$string .= "<div class=\"formHeader\">Web "._SUPPORT."</div>\n";
	if (defined("URL_SUPPORT")) $string .= "URL Download "._SUPPORT_ENABLED."\n";
	else $string .= "URL Download "._SUPPORT_DISABLED."\n";
	$string .= "<br><br>";

	$string .= "<div class=\"formHeader\">Zip Collection "._SUPPORT."</div>\n";
	if (defined("ZIP_SUPPORT")) $string .= "Zip Collection "._SUPPORT_ENABLED."\n";
	else $string .= "Zip Collection "._SUPPORT_DISABLED."\n";
	$string .= "<br><br>";

	$string .= "<div class=\"formHeader\">Miscellaneous "._RELATED_BINARIES."</div>\n";
	$string .= genBinaryAvail($arr,"convert");
	$string .= genBinaryAvail($arr,"mogrify");
	$string .= genBinaryAvail($arr,"montage");
	$string .= genBinaryAvail($arr,"clamav");
	$string .= genBinaryAvail($arr,"wget");
	$string .= genBinaryAvail($arr,"email");

	return $string;

}

