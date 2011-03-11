
function dbInsertQuery($conn,$table,$option,$idField = "id") {

	$ignoreArray = array("conn","table","debug","query");

	$keys = array_keys($option);

	$fieldString = null;
	$valueString = null;

	for ($row=0;$row<count($keys);$row++) {

		$field = $keys[$row];
		$value = $option[$field];

		if (!in_array($field,$ignoreArray) && $value!=null) {

			$fieldString .= $field.",";
			$valueString .= "'".$value."',";

		}	


	}

	if ($fieldString && $valueString) {

		$fieldString = substr($fieldString,0,strlen($fieldString) - 1);
		$valueString = substr($valueString,0,strlen($valueString) - 1);
			
		$sql = "INSERT INTO $table (".$fieldString.") VALUES (".$valueString.");";
		if ($option["debug"]) echo $sql."<br>\n";
		if ($option["query"]) return $sql;
		
		if ($result = db_query($conn,$sql)) {

      if ($idField) {		

  			$returnId = db_insert_id($table,$idField,$conn,$result);
  			if ($returnId) return $returnId;
  			else return true;

      } else {
        return true;
      }

		} else return false;

	} else return false;

}

function dbUpdateQuery($conn,$table,$option,$sanitize = null) {

	$ignoreArray = array("conn","table","where","debug","query");

	$keys = array_keys($option);

	$queryString = null;

	for ($row=0;$row<count($keys);$row++) {

		$field = $keys[$row];
		$value = $option[$field];

		if (!in_array($field,$ignoreArray)) {

			if ($value!=null) {
				if ($sanitize) 
					$queryString .= $field."='".sanitize($value)."',";
				else
					$queryString .= $field."='".$value."',";
			}
			else $queryString .= $field."=NULL,";
		} 	


	}

	if ($queryString) {

		$queryString = substr($queryString,0,strlen($queryString) - 1);
			
		$sql = "UPDATE $table SET ".$queryString." WHERE ".$option["where"];

		if ($option["debug"]) echo $sql."<br>\n";
		if ($option["query"]) return $sql;

		if (db_query($conn,$sql)) return true;
		else return false;

	} else return false;

}
