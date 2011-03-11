
/********************************************************************
	FILENAME: javascript.js
	PURPOSE:  contains common contact editing related functions

*********************************************************************/

//globals
var object = "";
var objinfo;
var curpage = "objprop";
var toolbar;
var container;
var content;
var tbBtns;
var tbTitle;
var savehandler;

/****************************************************************
	FUNCTION: loadPage
	PURPOSE:  loads our page for the first time
	INPUT:	  none
*****************************************************************/	

function loadPage() 
{

	//set globals from our input fields
	object = ge("objectId").value;

	//store these for easy access
	container = ge("container");
	content = ge("content");

	//load our navigation
	loadToolbar();

	//update our site nav history
	ge("siteNavHistory").innerHTML = ge("objectNav").innerHTML;

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

function loadObjectPage(page) 
{

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

    case "logs":
      historyManager("loadObjLogs","Object Logs");
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

	//setup our buttons
	tbBtns.appendChild(siteToolbarBtn("Save","save()","save.png"));
 
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
	}

  menuEntry("Logs","logs");

  addModSpacer();
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

