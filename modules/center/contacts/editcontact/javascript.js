
/********************************************************************
	FILENAME: javascript.js
	PURPOSE:  contains common contact editing related functions

*********************************************************************/

//globals
var curpage = "editcontact";		//default to the editcontact page
var contact = "";
var toolbar;
var toolbarBtns;
var toolbarTitle;
var toolbarStatus;
var content;
var container;
var pagemode;
var forms;											//container for forms in their divs created from xl file
var formconfig;									//container for forms from xml file
var formdata;
var	displayName = ""; 
var bypasscheck = "";
var contactinfo;								//for storing generic info about our contact to be accessible to all functions
var accountmode;

/****************************************************************
	FUNCTION: loadPage
	PURPOSE:  loads our page for the first time
	INPUT:	  none
*****************************************************************/	

function loadPage(pmode) {

	//set globals from our input fields
	pageMode = ge("pageMode").value;
	contact = ge("contactId").value;

	//store these for easy access
	toolbar = ge("toolbar");
	container = ge("container");
	content = ge("content");

	//load our navigation
	loadToolbar();
	loadContactPage("editcontact");
	loadMenu();

}


/****************************************************************
	FUNCTION: loadContactPage
	PURPOSE:  runs the appropriate load function based on the passed
						page name
	INPUT:	  page -> name of the page we are loading
*****************************************************************/	

function loadContactPage(page,direct) {

	if (page) curpage = page;
	updateSiteStatus("Loading Page");

	//if a directlink, clear the wizard mode
	if (direct) pagemode = "normal";

	switch (curpage) {

		case "editcontact":
			historyManager("loadEditContact","Edit Contact");
			break;

		case "editaccount":
			historyManager("loadEditAccount","Edit Account");
			break;

		case "newcontact":
			location.href = "index.php?module=editcontact";
			break;

	}

}

/****************************************************************
  FUNCTION: loadToolbar
  PURPOSE:  setups up toolbar for main contact page
  INPUT:    none
*****************************************************************/

function loadToolbar() {

  //create our columns
	toolbarBtns = ce("div");
	toolbarTitle = ce("div","toolbarTitle");

	toolbar.appendChild(toolbarBtns);
	toolbar.appendChild(toolbarTitle);
  toolbar.appendChild(createCleaner());
 
}

/****************************************************************
  FUNCTION: loadMenu
  PURPOSE:  creates our left column nav menu
  INPUT:    none
*****************************************************************/

function loadMenu() {

	showModNav();

	//always show contact information
	menuEntry("Contact Information","editcontact");
	if (contact) menuEntry("Assigned Accounts","editaccount");

  addModSpacer();
  menuEntry("Create New Entry","newcontact");

}

function newContact() {

	location.href = "index.php?module=editcontact";

}

/****************************************************************
  FUNCTION: menuEntry
  PURPOSE:  creates one entry for our nav menu
  INPUT:    none
*****************************************************************/

function menuEntry(title,page) {

	var link = "loadContactPage('" + page + "','1')";

	addModNav(title,link);	

}

function createEmail(id) {
  if (!id) id = contact;
  var url = "index.php?module=createemail&contactId=" + id;
  location.href = url;
}

function deleteContact() {


	if (confirm("Are you sure you want to remove this contact from your address book?")) {

		var url = "index.php?module=deletecontact&contactId=" + contact;
		protoReq(url,"writeDeleteContact","POST");
	}

}

function writeDeleteContact(data) {

	 

	if (data.error) alert(data.error);
	else location.href = "index.php?module=contact";

}


function updateToolbarName() {
	var txt = toolbarTitle.innerHTML;
	if (displayName.length>0) {
		clearElement(toolbarTitle);
		toolbarTitle.appendChild(ctnode(txt + " (" + displayName + ")"));
	}
}

function showSaveMsg(msg) {

	updateSiteStatus(msg);
	setTimeout("clearSiteStatus()","3000");

}

if (!document.all) {
   window.captureEvents(Event.KEYDOWN);
   window.onkeydown = NetscapeEventHandler_KeyDown;
} else {
   document.onkeydown = MicrosoftEventHandler_KeyDown;
}

function NetscapeEventHandler_KeyDown(e) {
  if (e.which == 13 && e.target.type != 'textarea' && e.target.type != 'submit') { return false; }
  return true;
}

function MicrosoftEventHandler_KeyDown() {
  if (event.keyCode == 13 && event.srcElement.type != 'textarea' && event.srcElement.type != 'submit')
    return false;
  return true;
}


function setGlobals(entry) {

	  //quick reference to the contact
	  contactinfo = entry;

		contact = contactinfo.id;

	  //set some globals
	  displayName = contactinfo.first_name + " " + contactinfo.last_name;
	  updateToolbarName();

}

