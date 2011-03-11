<?php
/***********************************************************************/
//        FILE: presentsite.php
//
// DESCRIPTION: Contains functions that setup the 
//              different section of the site application.
//              Including, the left and right columns and 
//              center content.
//    CREATION 
//        DATE: 04-19-2006
//
//     HISTORY:
//	  DATE: 09-12-2006 -> stripped down to core functions, except for isabel's alt funcs
//
//
/**********************************************************************/

/*****************************************************************************
	displays primary section data (center column) on the page
*****************************************************************************/
function sectionDisplay($optionArray) {

	extract($optionArray);

	//set some defaults for our header
	if ($leftHeader) $header = $leftHeader;
	if ($hideHeader) $header = null;

	if ($header) $header = "<div class=\"sectionHeader\">
				".$header."
				</div>\n";
	
	$string = "
	<div class=\"sectionContainer\">
		".$header."
		<div class=\"sectionContent\">
		".$content."
		</div>
	</div>
	";
	
	return $string;

}

//this is a non-standard left column display so we get our
//rounded corners
function leftColumnDisplay($optionArray) {

	$string = "<div class=\"leftColumnModule\">
		   <div class=\"leftColumnHeader\">".$optionArray["leftHeader"]."</div>
		   <div class=\"leftColumnContent\">".$optionArray["content"]."</div>
		   </div>
		   ";

	return $string;

}

//show modules in the right column
function rightColumnDisplay($optionArray) {

	extract($optionArray);
	if ($leftHeader) $header = $leftHeader;

	$string = "<div class=\"rightColumnModule\">\n";
	if (!$optionArray["hideHeader"]) $string .= "<div class=\"rightColumnHeader\">".$header."</div>";
	$string .= "<div class=\"rightColumnContent\">".$content."</div>";
	$string .= "</div>\n";

	return $string;

}


/*************************************************************
	legacy functions
*************************************************************/

function createSectionHeader($option) {

	extract($option);

	$string = null;

	if (!$hideHeader) {
		
		if ($admin) $rightHeader = 
				"
				<table cellpadding=0 cellspacing=0 style=\"float:right\">
				<tr><td style=\"padding-right:5px\">
				".$rightHeader."
				</td><td>
				".$admin."
				</td></tr>
				</table>
				";

		$string .= "	<div class=\"sectionHeader\">
				<div class=\"sectionHeaderLeft\">
					".$leftHeader."
				</div>
				";

		if ($rightHeader) $string .= "	<div class=\"sectionHeaderRight\">
						".$rightHeader."
						</div>
						";

		$string .= "</div>";
		//$string .= "<div class=\"cleaner\">&nbsp;</div>";

	}	
	elseif ($admin) {
	
		$string .= "	<div class=\"compAdminMenu\" style=\"float:right;text-align:right\">
					".$admin."
				</div>";
				
		/* a cleaner div was removed here because it was taking too much space.  This may
			cause display problems */
	}

	return $string;

}
function createSectionContent($content) {

	return "<div class=\"sectionContent\">\n
		".$content."
		</div>
		";

}


function sectionDisplayAlt($optionArray) {

	//return if there is nothing to display
	if ($optionArray["hideHeader"] && (!$optionArray["admin"] && !$optionArray["content"])) return false;

	$string = null;
	
	if ($optionArray["tab"]) $string .= "<div class=\"sectionTab\">".$optionArray["tab"]."</div>";
	
	$string .= "<div class=\"sectionTableAlt\">";

	$string .= createSectionHeader($optionArray);

	$string .= "	<div class=\"sectionContentAlt\">
				".$optionArray["content"]."
			</div>
			";

	$string .= "</div>";

	return $string;

}

                                                                        
function leftColumnDisplayAlt($optionArray) {

	$string = "<div class=\"leftColumnModuleAlt\">\n";
	
	if (!$optionArray["hideHeader"]) $string .= "<div class=\"leftColumnHeaderAlt\">".$optionArray["leftHeader"]."</div>";

	$string .= "<div class=\"leftColumnContentAlt\">".$optionArray["content"]."</div>";

	$string .= "</div>\n";

	return $string;

}
function rightColumnDisplayAlt($optionArray) {

	if (!$optionArray["hideHeader"]) $string = "<div class=\"rightColumnHeaderAlt\">".$optionArray["leftHeader"]."</div>";

	$string .= "<div class=\"rightColumnModuleAlt\">".$optionArray["content"]."</div>";

/*
	$string = "	<fieldset class=\"rightColumnModuleAlt\">
			<legend class=\"rightColumnHeaderAlt\">".$optionArray["rightHeader"]."</legend>
			".$optionArray["content"]."
			</fieldset>
			";	
*/


	return $string;

}

//EOF
