
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

function loadEditPassword() {

	//setup the toolbar
	loadPassToolbar();

	//load our forms
	loadForms("config/forms/accounts/password.xml","","writeEditPassword","","dataChanged()");

}

 
/****************************************************************
	FUNCTION: writeEditPassword
	PURPOSE:  handler for object returned from loadForms function.
						puts the object in the appropriate spot on the page.
	INPUT:	  cont -> html object containing data to be placed
*****************************************************************/	

function writeEditPassword(cont) {

	updateToolbarName("Reset Password");

  content.innerHTML = "";
  content.appendChild(cont);

	clearSiteStatus();

}


/****************************************************************
	FUNCTION: loadPassToolbar
	PURPOSE:  setups up toolbar for main contact page
	INPUT:	  none
*****************************************************************/	

function loadPassToolbar() {

	var saveBtn = siteToolbarCell("Save","savePassword()","save.png");

	//add our stuff to it
	toolbarBtns.innerHTML = "";
	toolbarBtns.appendChild(saveBtn);

}

function savePassword() {

	var p1 = ge("password");
	var p2 = ge("password2");

	//sanity checking
	if (p1.value.length==0) {
		alert("You must enter a new password");
		p1.focus();
		return false;
	}

	if (p2.value.length==0) {
		alert("You must confirm your new password");
		p2.focus();
		return false;
	}

	if (p1.value != p2.value) {
		alert("Your passwords do not match");
		p1.focus();
		return false;
	}

	updateSiteStatus("Resetting password");
	var url = "index.php?module=accountsave&accountId=" + account + "&action=savepassword&" + dom2Query(content);
	protoReq(url,"writeSavePassword","POST");

}


function writeSavePassword(data) {

	clearSiteStatus();

	 
	if (data.error) alert(data.error);
	else {

		dirty = false;
		updateSiteStatus("Password reset successfully");
		setTimeout("clearSiteStatus()","2000");

	}

}

