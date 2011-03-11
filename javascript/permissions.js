
var permDiv;
var permList;

function loadPermissions(divName) {

	permDiv = ge(divName);

	//the header
	permDiv.appendChild(createPermHeader());
	permDiv.appendChild(createCleaner());
	
	//the div containing our list
	permList = ce("div");
	setClass(permList,"permList");
	permDiv.appendChild(permList);

	//the search bar
	permDiv.appendChild(createPermSearch());

	//go out and get our permissions entries	
	regHandler("permaccounts","writePermList");

	showPermList();

}

function showPermList() {

	showStatus(I18N["updating"]);

	url = "index.php?module=permaccounts";
	
	//if there's an object id, append it to the list
	if (ge("objectId")) url += "&objectId=" + ge("objectId").value;

	//if there's a search filter, append it to the list
	var ss = ge("permString");
	if (ss.value.length > 0 && ss.value != I18N["search_for"]) url += "&searchString=" + ss.value;

	//append the match types
	url += "&permFilter=" + ge("permFilter").value;

	loadXMLReq(url);

}

function createPermHeader() {

	var header = ce("div");
	setClass(header,"permHeader");

	//create the divs
	var nameHeader = ce("div");
	var mHeader = ce("div");
	var eHeader = ce("div");
	var vHeader = ce("div");

	//set the classes
	setClass(nameHeader,"permNameHeader");
	setClass(mHeader,"permBoxHeader");
	setClass(eHeader,"permBoxHeader");
	setClass(vHeader,"permBoxHeader");

	//populate them
	nameHeader.appendChild(ctnode(I18N["account_group"]));
	mHeader.appendChild(ctnode(I18N["manage"]));
	eHeader.appendChild(ctnode(I18N["edit"]));
	vHeader.appendChild(ctnode(I18N["view"]));
	
	//put it all together
	header.appendChild(nameHeader);
	header.appendChild(mHeader);
	header.appendChild(eHeader);
	header.appendChild(vHeader);
	header.appendChild(createCleaner());

	return header;

}

//populate our permissions list with names and data
function writePermList(data) {

	 
	permList.innerHTML = "";

	//we shouldn't ever see this
	if (!data.entry) {
		permList.innerHTML = "<div class=\"statusMessage\">No groups or accounts to display</div>";
	} else {

		var num = data.entry.length;
		var i;

		for (i=0;i<num;i++) writePermEntry(data.entry[i]);

	}

	hideStatus();
	
}

//writes an individual row
function writePermEntry(entry) {

	//create the row
	var row = ce("li");

	//create the container divs
	var nameCell = ce("div");
	var mCell = ce("div");
	var eCell = ce("div");
	var vCell = ce("div");

	//set the classes
	setClass(nameCell,"permNameCell");
	setClass(mCell,"permBoxCell");
	setClass(eCell,"permBoxCell");
	setClass(vCell,"permBoxCell");

	//populate them
	nameCell.appendChild(ctnode(entry.name));
	mCell.appendChild(createPermCheckbox("manageObject[]",entry.type,entry.id,entry.manage_check));
	eCell.appendChild(createPermCheckbox("editObject[]",entry.type,entry.id,entry.edit_check));
	vCell.appendChild(createPermCheckbox("viewObject[]",entry.type,entry.id,entry.view_check));
	
	//put it all together
	row.appendChild(nameCell);
	row.appendChild(mCell);
	row.appendChild(eCell);
	row.appendChild(vCell);
	row.appendChild(createCleaner());
	
	permList.appendChild(row);

}

//creates a checkbox and populates it if necessary
function createPermCheckbox(name,type,id,checked) {

	//create the checkbox
	var cb = createForm("checkbox",name,checked);

	//set the value
	var val = id + "-" + type;
	cb.setAttribute("value",val);

	return cb;

}

//creates the search toolbar
function createPermSearch() {

	var search = ce("div");
	setClass(search,"permSearch");

	//search string filter
	var ss = createForm("text","permString");
	ss.setAttribute("value",I18N["search_for"]);
	setClick(ss,"permSearchClick()");
	setKeyUp(ss,"showPermList()");

	//create select box
	var sel = createSelect("permFilter","showPermList()");
	sel[0] = new Option(I18N["show_all_entries"],"0");
	sel[1] = new Option(I18N["accounts_only"],"accounts");
	sel[2] = new Option(I18N["groups_only"],"groups");
	sel[3] = new Option(I18N["selected_only"],"selected");

	search.appendChild(ss);
	search.appendChild(sel);

	return search;
}

function permSearchClick() {

	var ss = ge("permString");
	if (ss.value=="Search For") ss.value = "";

}
