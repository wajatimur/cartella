<?php


function mergeTB($dso = null) {

	if (!defined("TEA_ENABLE")) return false;
	
	if ($dso) {
		$entry = "onMouseEnter=\"setupDD('mergeSub')\" onMouseLeave=\"hideDD('mergeSub')\"";
		$subentry = "onMouseEnter=\"setRow()\" onMouseLeave=\"unsetRow()\"";
	} 
	else {
		$entry = null;
		$subentry = null;
	}
	
	$str = file_get_contents("config/mergefields.xml");
	$arr = parseGenericXml("merge",$str);
	
	$merge = "<td class=\"toolbarCell\" ".$entry.">
	          <div class=\"toolbarBtn\"><img src=\"".THEME_PATH."/images/icons/letter.png\"> Insert Merge Field</div>
            <div class=\"toolbarSub\" id=\"mergeSub\">
	";
	
	$num = count($arr["name"]);
	
	for ($i=0;$i<$num;$i++) {

	  $name = $arr["name"][$i];
	  $val = $arr["value"][$i];
	  $merge .= "<div class=\"toolbarSubRow\" ".$subentry." onClick=\"insertMergeField('".$val."');\">".$name."</div>\n";

	} 

	$merge .= "</div>\n</td>\n";
	
	return $merge;
	
}

function getEditorType($objectName)
{

	$ext = fileExtension($objectName);

	//default to ckeditor
	$editor = "ckeditor";

	//figure out what editor to use 
	$str = file_get_contents("config/extensions.xml");
	$arr = XML::decode($str);
	$stop = false;
	
	foreach ($arr["object"] AS $entry)
	{

		if ($entry["extension"]==$ext && $entry["open_with"])
		{

			$ow = explode(",",$entry["open_with"]);
			
			foreach ($ow AS $o)
			{

				if ($o=="dsoframer")
				{
				
					if (DSOFRAMER_ENABLE==1 && BROWSER=="ie" && $_SESSION["accountSettings"]["editor"]=="msoffice")
					{
						$editor = "dsoframer";
						$stop = true;
						break;
					}
					
				}
				else
				{
					$editor = $o;
					break;
				}

			}

		}

		if ($stop) break;

	}

	return $editor;

}