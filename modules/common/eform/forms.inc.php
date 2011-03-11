
function ageFormGenerate($formInfo) {

	$prospectId = $formInfo["prospect_id"];
	$contactId = $formInfo["contact_id"];

	if (defined("READ_ONLY")) $readonly = "disabled";

	$content = "<div class=\"formHeader\">";
	$content .= $formInfo["title"];
	$content .= "</div>";

	$optionTable = $formInfo["name"]."_option";
	$valueTable = $formInfo["name"];
	$name = $formInfo["name"];
	$conn = $formInfo["conn"];
	$size = $formInfo["size"];

	//get possible options
	$sql = "SELECT * FROM prospect.$optionTable;";
	$list = total_result($conn,$sql);

	//get selected values
	if ($prospectId) {
		$sql = "SELECT * FROM prospect.$valueTable WHERE prospect_id='$prospectId';";
		$values = total_result($conn,$sql);
	}
	
	$content .= "<table><tr><td><table>";

	for ($row=0;$row<count($list["id"]);$row++) {

		$check = intValue(count($list["id"])/$size);
		if ($row==$check) $content .= "</table></td><td valign=top><table>";

		//get the user-selected number for this option
		if (is_array($values) && in_array($list["id"][$row],$values["option_id"])) {
	
			$n = array_search($list["id"][$row],$values["option_id"]);
			$curValue = $values["number"][$n];

		} else $curValue = "0";

		//display our box and field name
		$content .= "<tr><td align=right>";
		$content .= $list["name"][$row];
		$content .= "</td><td>";
		$content .= "<select 	name=\"".$name.$list["id"][$row]."\" 
					id=\"".$name.$list["id"][$row]."\" 
					".$readonly."
					size=1>";

		for ($i=0;$i<=5;$i++) {

			if ($curValue==$i) $selected = " SELECTED ";
			else $selected = null;
						
			$content .= "<option ".$selected." value=\"".$i."\" >".$i."\n";
		}

		$content .= "</select></td></tr>";		

	}

	$content .= "</table></td></tr></table>";

	return $content;

}
