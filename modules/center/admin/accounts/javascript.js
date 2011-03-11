
/********************************************************************
	FILENAME: javascript.js
	PURPOSE:  contains common contact editing related functions

*********************************************************************/

//globals
var curpage = "contact";		//default to the editcontact page
var account = "";
var accountinfo;
var toolbar;
var toolbarBtns;
var toolbarTitle;
var toolbarStatus;
var content;
var container;
var searchResults;
var curloc = "";
var dirty = false;

/****************************************************************
	FUNCTION: loadPage
	PURPOSE:  loads our page for the first time
	INPUT:	  none
*****************************************************************/	

function loadPage() {

	//set globals from our input fields
	account = ge("accountId").value;

	//store these for easy access
	toolbar = ge("toolbar");
	container = ge("container");
	content = ge("content");

	searchResults = ce("div","","searchResults");
	ge("messageSiteSearch").appendChild(searchResults);

	if (document.all) searchResults.style.marginLeft = "-140px";

	//load our navigation
	loadMenu();
	loadToolbar();

	//if an admin, we have to show them the prompt to pick the agents that can see this account
	if (bitset_compare(BITSET,MANAGE_USERS,ADMIN)) loadAccountPage("profile");
	else loadAccountPage("docmgrsettings");

}


/****************************************************************
	FUNCTION: loadAccountPage
	PURPOSE:  runs the appropriate load function based on the passed
						page name
	INPUT:	  page -> name of the page we are loading
*****************************************************************/	

function loadAccountPage(page) {

	if (page) curpage = page;

	//make sure the form has been saved if changed
	if (!checkDirty()) return false;

	updateSiteStatus("Loading Page");

	switch (curpage) {

		case "profile":
			historyManager("loadEditProfile","Edit Profile");
			break;

		case "password":
			historyManager("loadEditPassword","Reset Password");
			break;

		case "group":
			historyManager("loadEditGroup","DocMGR Group");
			break;

		case "permission":
			historyManager("loadEditPermission","Edit Permission");
			break;

		case "docmgrsettings":
			historyManager("loadEditDocmgrSetting","Edit Docmgr Settings");
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
	toolbarBtns = ce("div","","toolbarBtns");
	toolbarTitle = ce("div","toolbarTitle","toolbarTitle");

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

	addModNav("Reset Password","loadAccountPage('password')");
	addModNav("DocMGR Settings","loadAccountPage('docmgrsettings')");

	if (perm_check(MANAGE_USERS))
	{

	  addModSpacer();

		addModNav("Edit Account Profile","loadAccountPage('profile')");
		addModNav("DocMGR Groups","loadAccountPage('group')");
		addModNav("DocMGR Permissions","loadAccountPage('permission')");
		addModSpacer();
		addModNav("Select Account","accountList()");
		addModNav("Create New User","loadCreateUser()");
		addModNav("Delete This Account","deleteAccount()");

	}

}

function updateToolbarName(txt) {

	var txt =  accountinfo.first_name + " " + accountinfo.last_name + " - " + txt;

	clearElement(toolbarTitle);
	toolbarTitle.appendChild(ctnode(txt));

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

		accountinfo = entry;

}

function accountSearch() {

	var ss = ge("msgSiteSearchString");

	if (ss.value.length > 0) {

		var url = "index.php?module=accountsearch&searchString=" + ss.value;
		protoReq(url,"writeAccountSearch");

	} else {

		searchResults.style.display = "none";
		clearSiteStatus();

	}

}

function writeAccountSearch(data) 
{

	searchResults.style.display = "block";
	clearElement(searchResults);

	if (data.error) alert(data.error);
	else {

		if (!data.account) searchResults.appendChild(ce("div","errorMessage","","No results found"));
		else {

			for (var i=0;i<data.account.length;i++) 
			{

				var name = data.account[i].login;
				var row = ce("div","","",name);
				setClick(row,"showAccount('" + data.account[i].id + "')");
				searchResults.appendChild(row);

			}

		}

	}

}

function showAccount(id) {

	searchResults.style.display = "none";
	ge("msgSiteSearchString").value = "";
	closeSitePopup();

	curloc = "";
	account = id;
	loadAccountPage();

}

function deleteAccount() {

	if (confirm("Are you sure you want to remove this account?")) {

		updateSiteStatus("Deleting account");
		var url = "index.php?module=accountsave&action=deleteaccount&accountId=" + account;
		protoReq(url,"writeDeleteAccount","POST");

	}

}

function writeDeleteAccount(data) {

	clearSiteStatus();

	if (data.error) alert(data.error);
	else {

		//reload us at the main page
		account = USER_ID;
		loadAccountPage('profile');

	}

}

function accountList() {

	var ref = openSitePopup(320,380);

	ref.appendChild(ce("div","sitePopupHeader","","Account List"));

	var cell = ce("div","","accountList","Please Wait...");

	ref.appendChild(cell);

	var url = "index.php?module=accountsearch&showAll=1";
	protoReq(url,"writeAccountList");

}

function writeAccountList(data) 
{

	var ref = ge("accountList");
	clearElement(ref);

	if (data.error) alert(data.error);
	else {

		if (!data.account) ref.appendChild(ce("div","errorMessage","","No results found"));
		else {

			for (var i=0;i<data.account.length;i++) 
			{

				var name = data.account[i].login + " - " + data.account[i].first_name + " " + data.account[i].last_name;
				var row = ce("div","","",name);
				setClick(row,"showAccount('" + data.account[i].id + "')");

				ref.appendChild(row);

			}

		}

	}

}

function checkDirty()
{

	var ret = true;

	if (dirty==true)
	{

		if (!confirm("You have unsaved information on this page.  Do you wish to continue?")) ret = false;

	}

	return ret;

}

function dataChanged()
{  
  dirty = true;
}
