
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

function loadEditDocmgrSetting() {

	loadDocmgrSettingToolbar();

	//load our forms
	loadForms("config/forms/accounts/docmgrsettings.xml","","writeEditDocmgrSetting","getDocmgrSettingData","dataChanged()");

}

/****************************************************************
  FUNCTION: getDocmgrSettingData
  PURPOSE:  our data handler.  it gets data for our contact
            and returns it in array form to the calling function
            even those it calls synchronously, it is called
            asynchronously by loadForms, so there's no loag
  INPUT:    none
  RETURN:   contact information in array form
*****************************************************************/

function getDocmgrSettingData() {

  var data = protoReqSync("index.php?module=accountinfo&accountId=" + account);

  //set our globals from contact information
  setGlobals(data.account[0]);

  return data.account[0];

}
 
/****************************************************************
	FUNCTION: writeEditDocmgrSetting
	PURPOSE:  handler for object returned from loadForms function.
						puts the object in the appropriate spot on the page.
	INPUT:	  cont -> html object containing data to be placed
*****************************************************************/	

function writeEditDocmgrSetting(cont) {

	updateToolbarName("DocMGR Settings");

  content.innerHTML = "";
  content.appendChild(cont);

	clearSiteStatus();

}


/****************************************************************
	FUNCTION: loadDocmgrSettingToolbar
	PURPOSE:  setups up toolbar for main contact page
	INPUT:	  none
*****************************************************************/	

function loadDocmgrSettingToolbar() {

	var saveBtn = siteToolbarCell("Save","saveDocmgrSetting()","save.png");

	//add our stuff to it
	toolbarBtns.innerHTML = "";
	toolbarBtns.appendChild(saveBtn);

}

function saveDocmgrSetting() {

  updateSiteStatus("Saving settings");
  var url = "index.php?module=accountsave&accountId=" + account + "&action=savedocmgrsetting&" + dom2Query(content);
 	protoReq(url,"writeDocmgrSettingSave","POST");

}

function writeDocmgrSettingSave(data) {

  clearSiteStatus();

  if (data.error) alert(data.error);
  else 
	{
		dirty = false;
		showSaveMsg("Settings saved successfully");
	}
}
