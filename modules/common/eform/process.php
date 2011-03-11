<?php

/**********************************************************************
  MODULE:	eform
  PURPOSE: loads a module template and populates the forms w/
            data from the database as required (usually for
            selects, checkboxes, radio buttons, etc
**********************************************************************/

$file = $_REQUEST["file"];
$deffile = $_REQUEST["deffile"];

//sanity checking
if (!$file) die("No file template specified");
if ($file[0]=="/") die("Cannot use absolute paths in the filename");
if (strstr($file,"..")!=null) die("Cannot use \"../\" in the filename");
if (!file_exists($file)) die("File \"".$file."\" does not exist");
$str = file_get_contents("$file");

//do some global search and replaces
$str = str_replace("[CUR_LOC_STATE]",CUR_LOC_STATE,$str);

//load our master file
if ($deffile) {

  if ($deffile[0]=="/") die("Cannot use absolute paths in the definition filename");
  if (strstr($deffile,"..")!=null) die("Cannot use \"../\" in the definition filename");
  $defstr = file_get_contents($deffile);

  //create defforms
  $defdom = DOMDocument::loadXML($defstr);
  $defforms = $defdom -> getElementsByTagName("form");
  $defarr = array();

  //populate our defform array
  foreach ($defforms AS $defform) {

    $defchildren = $defform -> childNodes;
    $arr = array();

    //store in an array for later access
    foreach ($defchildren AS $defchild) {
      if ($defchild->nodeType!=1) continue;

      //handle manual option tags      
      if ($defchild->nodeName=="option") {

        $tmp = array();
        $chittlens = $defchild->childNodes;
        foreach ($chittlens AS $chittle) {
          if ($chittle->nodeType!=1) continue;
          $tmp[$chittle->nodeName] = $chittle->textContent;
        }
        
        $arr["option"] = $tmp;

      } else {
        $arr[$defchild->nodeName] = $defchild->textContent;
      }
    }

    //the key for looking this up later
    $key = &$arr["name"];
    $defarr[$key] = $arr;
  
  }  


}

//convert simple to dom so we can work with it
//$dom = dom_import_simplexml($xml);
$dom = DOMDocument::loadXML($str);

//append our typeid to the dom
$typeid = $dom -> createElement("typeid");
$typeid -> appendChild($dom->createTextNode("eform"));

$dom -> documentElement -> appendChild($typeid);

//get all forms
$forms = $dom -> getElementsByTagName("form");

//form types that require us to query a database for their possible values
$dataforms = array("select","checkbox","radio","pricerange","age");

//loop through and see if we have to add any options
foreach ($forms AS $form) {

  $children = $form -> childNodes;
  
  //reset our element fields
  $arr = array();
 
  //store in an array for later access
  foreach ($children AS $child) {

    //if we have a nodetype of 3 and a value, it's a single form
    if ($deffile && $child->nodeType==3 && $child->textContent) {
      $arr["form"] = trim($child->textContent);			//the name of our form
      $arr["child"] = $child;									//child ref so we can delete it later
      break;
    }

    //process regular nodes here
    if ($child->nodeType!=1) continue;
    $arr[$child->nodeName] = $child->textContent;
  }

  //if form is set, this means it's defined in our def file.  pull the form info from there
  if ($deffile && $arr["form"]) {

    //form doesn't appear to be defined, try to find it in our def array
    $key = $arr["form"];
    $tempform = $defarr[$key];

    //stop here, form couldn't be found
    if (!$tempform) die("Form ".$key." not found in definition file");

    //merge our defarray data back into the main dom
    if ($tempform["type"]) {

      //remove the text only child from the form
      $form->removeChild($arr["child"]);

      //add all our keys from the definition into the dom
      $keys = array_keys($tempform);
      foreach ($keys AS $formkey) {

        //if there's a subarray, add it as a new child element
        if (is_array($tempform[$formkey])) {

          //get key sof the cild
          $subkeys = array_keys($tempform[$formkey]);        

          //create new element
          $e = $dom->createElement($formkey);

          //add array values to new element          
          foreach ($subkeys AS $sub) {
            $e->appendChild($dom->createElement($sub,$tempform[$formkey][$sub]));            
          }
          //add back to the main form
          $form->appendChild($e);

        //otherwise just add an element with text content        
        } else {
          $form->appendChild($dom->createElement($formkey,$tempform[$formkey]));
        }
      }
    //we didn't find a valid definition in the def file, skip this one
    } else {
      die("Form ".$key." does not appear to be defined properly in the def file");
    }
    //reset arr to our temp form for further data extraction if necessary
    $arr = $tempform;

  }

  //is it defined correctly
  if (!$arr["type"]) continue;
  if ($arr["location_filter"] && !@in_array(CUR_LOC_VARNAME,$arr["location_filter"]["location"])) continue;

  //if it's not a data form, we don't need to pull additional data for it
  if (!in_array($arr["type"],$dataforms)) continue;

  $title_field = &$arr["title_field"];
  $data_field = &$arr["data_field"];
  $table = &$arr["table"];
  $filter = &$arr["filter"];
  $central = &$arr["central"];
  $defaultval = &$arr["defaultval"];
  $sort = &$arr["sort"];
    
  if (!$title_field) $title_field = "name";
  if (!$data_field) $data_field = "id";

  //create our query
  if ($title_field==$data_field) $fields = $title_field;
  else $fields = $data_field.",".$title_field;

  //do we query teh central database?
  if ($central) $dbconn = $cent_conn;
  else $dbconn = $conn;

  //setup our filter
  if ($filter) {
    $filter = str_replace("[CUR_CHILD]",CUR_CHILD,$filter);
    $filter = str_replace("[CUR_LOCATION]",CUR_LOCATION,$filter);
    $filter = str_replace("[CUR_PARENT]",CUR_PARENT,$filter);
    $filter = str_replace("[USER_ID]",USER_ID,$filter);
  }

  //if it's an ldap query, run that.  otherwise run the sql query
  if ($arr["ldap"]) {

    $list = runLdapQuery($title_field,$data_field,$filter,$arr["sort"]);

  //price range type
  } else if ($arr["type"]=="pricerange") {

    $list = runPriceRangeQuery();

  } else {

    //skip if a table isn't defined
    if (!$table) continue;

    //assemble our query
    $sql = "SELECT $fields FROM $table";
    if ($filter) $sql .= " WHERE ".$filter;
    if ($sort) $sql .= " ORDER BY ".$sort;
    
    //run it!
    $list = list_result($dbconn,$sql);

  }

  //append any matches to the form xml so the options are passed back to the browser
  if ($list["count"] > 0) {

    //append our results to our xml object
    for ($i=0;$i<$list["count"];$i++) {

      $option = $dom->createElement("option");
      $title = $dom->createElement("title");
      $data = $dom->createElement("data");

      //append the data to the nodes
      $title->appendChild($dom->createTextNode($list[$i][$title_field]));
      $data->appendChild($dom->createTextNode($list[$i][$data_field]));

      //add to the option, and add the option to the dom
      $option -> appendChild($title);
      $option -> appendChild($data);
      $form -> appendChild($option);

    }

  } 

}

header("Content-Type: text/xml");

//output back to the screen
print $dom -> saveXML();

die;
