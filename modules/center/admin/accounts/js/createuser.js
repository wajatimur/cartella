
/********************************************************************
	FILENAME: editcontact.js
	PURPOSE:  contains editcontact section related functions

*********************************************************************/
var createref;

/****************************************************************
	FUNCTION: loadEditContact
	PURPOSE:  main function for loading our editcontact page.  
						calls loadForms with the appropriate handlers set
	INPUT:	  none
*****************************************************************/	

function loadCreateUser() {

	updateSiteStatus("Loading form");

	//load our forms
	loadForms("config/forms/accounts/createuser.xml","","writeCreateUser");

}

/****************************************************************
	FUNCTION: writeCreateUser
	PURPOSE:  handler for object returned from loadForms function.
						puts the object in the appropriate spot on the page.
	INPUT:	  cont -> html object containing data to be placed
*****************************************************************/	

function writeCreateUser(cont) {

	createref = openSitePopup("500","275");
	createref.appendChild(cont);

	//add a submit button
	var div = ce("div","createBtnDiv");
	var btn = createBtn("createuser","Create New User","createUser()");

	div.appendChild(btn);
	createref.appendChild(div);	
	createref.appendChild(createCleaner());

	clearSiteStatus();

}


function createUser() {

	account = "";
	accountinfo = "";

	updateSiteStatus("Saving account profile");
	var url = "index.php?module=accountsave&action=createuser&" + dom2Query(createref);
	protoReq(url,"writeCUSave","POST");

}

function writeCUSave(data) {

	clearSiteStatus();

	 

	if (data.error) alert(data.error);
	else {
		account = data.account_id;
		closeSitePopup();
		loadAccountPage('profile');
	}

}
