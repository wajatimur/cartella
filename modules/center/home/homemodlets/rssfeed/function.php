<?php

function processRss($url,$limit,$desc=null) {

	if ($db = readDatabase("$url")) $string = outputData($db,$limit,$desc);
	else $string = "<div class=\"errorMessage\">RSS Feed Temporarily Unavailable</div>";
	
	return $string;

}


function outputData($db,$limit,$desc) {

	if ($limit) $num = $limit;
	else $num = count($db);

	for ($row=1;$row<=$num;$row++) {

		$string .= "
					<li class=\"rssEntry\" onClick=\"showFeed('".$db[$row]["link"]."')\">
					".translateHtmlEntities($db[$row]["title"])."
				";
		if ($desc) {
		
			$string .= "	<div style=\"padding-left:17px;padding-bottom:3px\">
						".translateHtmlEntities($db[$row]["description"])."
					</div>
					";
		}
		
		$string .= "</li>";

	}

	return $string;

}


function readDatabase($filename) {

	if (!$data = @file_get_contents($filename)) return false; 

	$pos1 = strpos($data,"<item ");
	if (!$pos1) $pos1 = strpos($data,"<item>");

	$pos2 = strrpos($data,"</item>");

	$len = $pos2 - $pos1;

	$prefix = "<news>\n\n\n";
	$suffix = "\n\n\n</news>";

	$data = $prefix.substr($data,$pos1,$len).$suffix;

	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, $data, $values, $tags);
	xml_parser_free($parser);

	return extractData($values);

}

function extractData($stuff) {

	$vals = array();

	$counter = "0";
	$running = null;
	foreach ($stuff AS $mine) {

		$tag = $mine["tag"];
		
		if ($tag=="title") $counter++;

		if ($mine["value"]) {

			$temp[$tag] = $mine["value"];

			$vals[$counter] = $temp;

		}
	}

	return $vals;
}

