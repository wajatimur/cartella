
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

/****************************************************************
	FUNCTION: loadPage
	PURPOSE:  loads our page for the first time
	INPUT:	  none
*****************************************************************/	

function loadPage() {

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

    case "logs":
      historyManager("loadObjLogs","Object Logs");
      break;

    case "viewfile":
      historyManager("viewURL","");
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
	tbBtns.appendChild(siteToolbarCell("Save","save()","save.png"));
 
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
  menuEntry("View URL","viewfile");
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

function viewURL() {

	var p = new PROTO();
  p.add("command","docmgr_url_get");
  p.add("object_id",object);
	p.post(DOCMGR_API,"writeViewURL");

}
 
function writeViewURL(data) {

	clearSiteStatus();

  if (data.error) alert(data.error);
  else {

    var parms = centerParms(800,600,1) + ",scrollbars=yes,menubar=yes,resizable=yes,titlebar=yes,toolbar=yes";
    var ref = window.open(data.url,"_blank",parms);

    if (!ref) {
      alert("It appears you have a popup blocker enabled.  You must disable it for this website to open Web Objects");
    } else {
      ref.focus();
    }
     
  }  
     
} 