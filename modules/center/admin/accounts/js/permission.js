
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

function loadEditPermission() {

	//setup the toolbar
	loadPermissionToolbar();

	//load our forms
	loadForms("config/forms/accounts/permission.xml","","writeEditPermission","getPermissionData","dataChanged()");

}

/****************************************************************
  FUNCTION: getPermissionData
  PURPOSE:  our data handler.  it gets data for our contact
            and returns it in array form to the calling function
            even those it calls synchronously, it is called
            asynchronously by loadForms, so there's no loag
  INPUT:    none
  RETURN:   contact information in array form
*****************************************************************/

function getPermissionData() {

  var data = protoReqSync("index.php?module=accountinfo&accountId=" + account);

  //set our globals from contact information
  setGlobals(data.account[0]);

  return data.account[0];

}
 
/****************************************************************
	FUNCTION: writeEditPermission
	PURPOSE:  handler for object returned from loadForms function.
						puts the object in the appropriate spot on the page.
	INPUT:	  cont -> html object containing data to be placed
*****************************************************************/	

function writeEditPermission(cont) {

	updateToolbarName("Permissions");

  content.innerHTML = "";
  content.appendChild(cont);

	clearSiteStatus();

}

function _permissionForm(curform) {

  var data = loadReqSync(site_url + "config/permissions.xml");

  var mydiv = ce("div","listCell");
  var header = ce("div","multiformHeader","",curform.title);

  mydiv.appendChild(header);

  for (var i=0;i<data.perm.length;i++) {

    //skip admin for non-admins
    if (data.perm[i].bitpos==0 && !bitset_compare(BITSET,ADMIN,"")) continue;

    mydiv.appendChild(createPermForm(data.perm[i],accountinfo.bitmask));

  }

  return mydiv;

}

function createPermForm(entry,bitval) {

	if (bitval && bitval.length > 0) bitval = bitval.reverse();

  var row = ce("div");

  var cb = createCheckbox("perm[]",entry.bitpos);

	setChange(cb,"dataChanged()");
  row.appendChild(cb);
  row.appendChild(ctnode(entry.name));

  if (bitval && bitval.length > 0 && bitval.charAt(entry.bitpos)=="1") cb.checked = true;

  return row;

}


/****************************************************************
	FUNCTION: loadPermissionToolbar
	PURPOSE:  setups up toolbar for main contact page
	INPUT:	  none
*****************************************************************/	

function loadPermissionToolbar() {

	var saveBtn = siteToolbarCell("Save","savePermission()","save.png");

	//add our stuff to it
	toolbarBtns.innerHTML = "";
	toolbarBtns.appendChild(saveBtn);

}

function savePermission() {

  updateSiteStatus("Saving permission information");
  var url = "index.php?module=accountsave&accountId=" + account + "&action=savepermission&" + dom2Query(content);
  protoReq(url,"writePermissionSave","POST");

}


function writePermissionSave(data) {

	clearSiteStatus();

	 
	if (data.error) alert(data.error);
	else {

		dirty = false;
		updateSiteStatus("Permissions set successfully");
		setTimeout("clearSiteStatus()","2000");

	}

}


