
/********************************************************************
	FILENAME: javascript.js
	PURPOSE:  contains common contact editing related functions

*********************************************************************/

//globals
var object = "";
var objinfo;
var curpage = "objprop";
var container;
var content;
var tbBtns;
var tbTitle;
var savehandler;
var ceiling;

/****************************************************************
	FUNCTION: loadPage
	PURPOSE:  loads our page for the first time
	INPUT:	  none
*****************************************************************/	

function loadPage(pload) {

	//set globals from our input fields
	object = ge("objectId").value;
	ceiling = BROWSE_CEILING;
	if (ceiling.length==0) ceiling = "/";

	//store these for easy access
	container = ge("container");
	content = ge("content");

	//load our navigation
	loadToolbar();

	//update our site nav history
	ge("siteNavHistory").innerHTML = ge("objectNav").innerHTML;

	//set the function to run after we get our objct info
	if (pload) objpage = pload;

	updateSiteStatus("Getting object information");

	//get our object information, then load the properties page when finished
	loadObjInfo();

}

/****************************************************************
	FUNCTION: loadObjectPage
	PURPOSE:  runs the appropriate load function based on the passed
						page name
	INPUT:	  page -> name of the page we are loading
*****************************************************************/	

function loadObjectPage(page) {

	if (page) curpage = page;
	updateSiteStatus("Loading Page");

	switch (curpage) {

		case "properties":
			historyManager("loadProperties","Object Properties");
			break;

		case "permissions":
			historyManager("loadPermissions","Object Permissions");
			break;

		case "parents":
			historyManager("loadObjParents","Object Parents");
			break;

		case "history":
			historyManager("loadFileHistory","Revision History");
			break;

		case "logs":
			historyManager("loadObjLogs","Object Logs");
			break;

		case "discussion":
			historyManager("loadDiscussion","Discussion");
			break;

		case "viewfile":
			siteViewFile(object);
			break;

		case "managesubscription":
			historyManager("propManageSubscription","");
			break;

	}

}

/****************************************************************
	FUNCTION: loadToolbar
	PURPOSE:  setups up toolbar for main contact page
	INPUT:    none
*****************************************************************/

function loadToolbar() {

	tbBtns = ge("toolbarBtns");
	tbTitle = ge("toolbarTitle");

}

function save() {

	var func = eval(savehandler);

}

/****************************************************************
	FUNCTION: loadMenu
	PURPOSE:  creates our left column nav menu
	INPUT:    none
*****************************************************************/

function loadMenu() {

	showModNav();

	menuEntry("Properties","properties");

	if (objinfo.bitmask_text=="admin")
	{

		menuEntry("Parent Collection","parents");
		menuEntry("Permissions","permissions");
		menuEntry("Revision History","history");

	}

	menuEntry("Logs","logs");
	menuEntry("Discussion","discussion");
	addModSpacer();
	menuEntry("View File","viewfile");
	menuEntry("Manage Subscription","managesubscription");

}

/****************************************************************
	FUNCTION: menuEntry
	PURPOSE:  creates one entry for our nav menu
	INPUT:    none
*****************************************************************/

function menuEntry(title,page) {

	var link = "loadObjectPage('" + page + "')";

	addModNav(title,link);	

}

