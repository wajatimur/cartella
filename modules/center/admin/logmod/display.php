<?php

$modCss .= THEME_PATH."/css/toolbar.css;";

if ($_REQUEST["category"]) $category = $_REQUEST["category"];
else $category = "all";

if ($_REQUEST["account"]) $account = $_REQUEST["account"];
else $account = "any";

if ($_REQUEST["show"]) $show = $_REQUEST["show"];
else $show = "100";

/*********************************************
	generate our page
*********************************************/
$arrayFlag = FALSE;

if (LOGGER_MODE=="db") $logs = getLogFromDB($category,$account,$show);
else $logs = getLogFromXML();

$logDiv = null;
$i = 0;
$c = 0;

if (count($logs) > 0) {

	$logstr = "
							<table cellspacing=\"0\" width=\"100%\" border=\"0\" id=\"logList\">
							<thead>
							<tr>
								<th class=\"time\">Time</th>
								<th class=\"message\">Message</th>
								<th class=\"level\">Level</th>
								<th class=\"category\">Category</th>
								<th class=\"ipaddress\">IP Address</th>
								<th class=\"userid\">User ID</th>
								<th class=\"login\">Login</th>
								<th class=\"childlocid\">Child LocID</th>
								<th class=\"sql\">SQL</th>
								<th class=\"getdata\">GET Data</th>
								<th class=\"postdata\">POST Data</th>
							</tr>
							</thead>
							<tbody>
							";

	foreach ($logs AS $log) {

		if (!is_array($log)) continue;

		if ($log["post_data"]) $post = setupRequestData($log["post_data"]);
		if ($log["get_data"]) $get = setupRequestData($log["get_data"]);

		$logstr .= "<tr>
									<td class=\"time\">".date("m/d/Y H:i:s",strtotime($log["log_timestamp"]))."</td>
									<td class=\"message\">".$log["message"]."</td>
									<td class=\"level\">".$log["level"]."</td>
									<td class=\"category\">".$log["category"]."</td>
									<td class=\"ipaddress\">".$log["ip_address"]."</td>
									<td class=\"userid\">".$log["user_id"]."</td>
									<td class=\"userlogin\">".$log["user_login"]."</td>
									<td class=\"childlocid\">".$log["child_location_id"]."</td>
									<td class=\"sql\">
										<a href=\"javascript:void(0)\" onClick=\"viewData(event)\">[View]</a>
										<div style=\"display:none\">".$log["sql"]."</div>
									</td>
									<td class=\"getdata\">
										<a href=\"javascript:void(0)\" onClick=\"viewData(event)\">[View]</a>
										<div style=\"display:none\">".$get."</div>
									</td>
									<td class=\"postdata\">
										<a href=\"javascript:void(0)\" onClick=\"viewData(event)\">[View]</a>
										<div style=\"display:none\">".$post."</div>
									</td>
									</tr>
									";

	}

	$logstr .= "</tbody></table>";
		
}

$l = new ACCOUNT();
$arr = $l->getList();
$num = count($arr);
$userList = null;
for ($i=0;$i<$num;$i++) $userList .= "<option value=\"".$arr[$i]["login"]."\">".$arr[$i]["login"]."\n";

$content = "
            <form name=\"pageForm\" method=\"post\">
            <input type=hidden name=\"module\" id=\"module\" value=\"".$module."\">
            <input type=hidden name=\"pageAction\" id=\"pageAction\" value=\"\">
            <input type=hidden name=\"sortOrder\" id=\"sortOrder\" value=\"\">
            <input type=hidden name=hideHeader id=hideHeader value=\"\">
            <input type=hidden name=\"saveCategory\" id=\"saveCategory\" value=\"".$category."\">
            <input type=hidden name=\"saveAccount\" id=\"saveAccount\" value=\"".$account."\">
            <input type=hidden name=\"saveShow\" id=\"saveShow\" value=\"".$show."\">
						<div class=\"toolbar\">
							<div class=\"toolbarCell\">
								Category: 
								<select name=\"category\" id=\"category\" onChange=\"document.pageForm.submit()\">
								<option value=\"all\">All
								<option value=\"DB_ERROR\">DB Errors
								<option value=\"CONTACT\">Contact
								<option value=\"CONTRACT\">Contract
								<option value=\"AUTH\">Authentication
								<option value=\"IMAP\">IMAP
								<option value=\"DOCMGR\">DOCMGR
								</select>
							</div>
							<div class=\"toolbarCell\">
								User: 
								<select name=\"account\" id=\"account\" onChange=\"document.pageForm.submit()\">
								<option value=\"any\">Any User
								".$userList."
								</select>
							</div>
							<div class=\"toolbarCell\">
								Show: 
								<select name=\"show\" id=\"show\" onChange=\"document.pageForm.submit()\">
								<option value=\"100\">Last 100
								<option value=\"250\">Last 250
								<option value=\"500\">Last 500
								<option value=\"1000\">Last 1000
								<option value=\"all\">All
								</select>
							</div>
						</div>
						<div class=\"cleaner\">&nbsp;</div>							
            <div id=\"container\" style=\"width:95%;padding-left:10px;\">
									<div id=\"logs\">".$logstr."</div>
                
            </div>
            </form>
		";

//clear out the above for a printable view
if ($hideHeader) $content = null;

$onPageLoad = "setFormVals()";

$option = null;
$option["hideHeader"] = 1;
$option["content"] = $content;
$siteContent .= sectionDisplay($option);

?>
