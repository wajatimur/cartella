
/********************************************************************
	FILENAME: editcontact.js
	PURPOSE:  contains editcontact section related functions

*********************************************************************/

/****************************************************************
	FUNCTION: loadEditContact
	PURPOSE:  main function for loading our editcontact page.  
						calls loadForms with the appropriate handlers set
	INPUT:	  none
*****************************************************************/	

function loadEditProfile() {

	//setup the toolbar
	loadEPToolbar();

	//load our forms
	loadForms("config/forms/accounts/profile.xml","","writeEditAccount","getAccountData","dataChanged()");

}


/****************************************************************
	FUNCTION: getAccountData
	PURPOSE:  our data handler.  it gets data for our contact
						and returns it in array form to the calling function
						even those it calls synchronously, it is called
						asynchronously by loadForms, so there's no loag
	INPUT:	  none
	RETURN:		contact information in array form
*****************************************************************/	

function getAccountData() {

  var data = protoReqSync("index.php?module=accountinfo&accountId=" + account);

  //set our globals from contact information
	setGlobals(data.account[0]);

	return data.account[0];
	
}
 
/****************************************************************
	FUNCTION: writeEditAccount
	PURPOSE:  handler for object returned from loadForms function.
						puts the object in the appropriate spot on the page.
	INPUT:	  cont -> html object containing data to be placed
*****************************************************************/	

function writeEditAccount(cont) {

	updateToolbarName("Account Profile");

  content.innerHTML = "";
  content.appendChild(cont);

	clearSiteStatus();

}


/****************************************************************
	FUNCTION: loadEPToolbar
	PURPOSE:  setups up toolbar for main contact page
	INPUT:	  none
*****************************************************************/	

function loadEPToolbar() {

	var saveBtn = siteToolbarCell("Save","saveAccount()","save.png");

	//add our stuff to it
	toolbarBtns.innerHTML = "";
	toolbarBtns.appendChild(saveBtn);

}

function saveAccount() {

	updateSiteStatus("Saving account profile");
	var url = "index.php?module=accountsave&accountId=" + account + "&action=saveprofile&" + dom2Query(content);
	protoReq(url,"writeAccountSave","POST");

}

function writeAccountSave(data) {

	clearSiteStatus();

	if (data.error) alert(data.error);
	else 
	{

		dirty = false;
		showSaveMsg("Profile information saved successfully");

	}

}
