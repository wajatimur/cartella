
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

function loadEditGroup() {

	//setup the toolbar
	loadGroupToolbar();

	//load our forms
	loadForms("config/forms/accounts/group.xml","","writeEditGroup","getGroupData","dataChanged()");

}

/****************************************************************
  FUNCTION: getGroupData
  PURPOSE:  our data handler.  it gets data for our contact
            and returns it in array form to the calling function
            even those it calls synchronously, it is called
            asynchronously by loadForms, so there's no loag
  INPUT:    none
  RETURN:   contact information in array form
*****************************************************************/

function getGroupData() {

  var data = protoReqSync("index.php?module=accountinfo&accountId=" + account);

  //set our globals from contact information
  setGlobals(data.account[0]);

  return data.account[0];

}
 
/****************************************************************
	FUNCTION: writeEditGroup
	PURPOSE:  handler for object returned from loadForms function.
						puts the object in the appropriate spot on the page.
	INPUT:	  cont -> html object containing data to be placed
*****************************************************************/	

function writeEditGroup(cont) {

	updateToolbarName("Groups");

  content.innerHTML = "";
  content.appendChild(cont);

	clearSiteStatus();

}


/****************************************************************
	FUNCTION: loadGroupToolbar
	PURPOSE:  setups up toolbar for main contact page
	INPUT:	  none
*****************************************************************/	

function loadGroupToolbar() {

	var saveBtn = siteToolbarCell("Save","saveGroup()","save.png");

	//add our stuff to it
	toolbarBtns.innerHTML = "";
	toolbarBtns.appendChild(saveBtn);

}

function saveGroup() {

  updateSiteStatus("Saving group information");
  var url = "index.php?module=accountsave&accountId=" + account + "&action=savegroup&" + dom2Query(content);
  protoReq(url,"writeGroupSave","POST");

}

function writeGroupSave(data) {

  clearSiteStatus();

  if (data.error) alert(data.error);
  else 
	{
		dirty = false;
		showSaveMsg("Groups saved successfully");
	}

}
