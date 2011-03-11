
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

function loadEditContact() {

	//setup the toolbar
	loadECToolbar();

	//load our forms
	loadForms("config/forms/editcontact.xml","","writeEditContact","getContactData");

}

/****************************************************************
	FUNCTION: getContactData
	PURPOSE:  our data handler.  it gets data for our contact
						and returns it in array form to the calling function
						even those it calls synchronously, it is called
						asynchronously by loadForms, so there's no loag
	INPUT:	  none
	RETURN:		contact information in array form
*****************************************************************/	

function getContactData() {

  if (!contact) 
	{
		loadMenu();			//do it here because it won't be done by setGlobals() later
		return false;
	}

  var data = protoReqSync("index.php?module=contactinfo&contactId=" + contact);

  //set our globals from contact information
	setGlobals(data.contact[0]);

	return data.contact[0];

}
 
/****************************************************************
	FUNCTION: writeEditContact
	PURPOSE:  handler for object returned from loadForms function.
						puts the object in the appropriate spot on the page.
	INPUT:	  cont -> html object containing data to be placed
*****************************************************************/	

function writeEditContact(cont) {

	toolbarTitle.innerHTML = "";
	toolbarTitle.appendChild(ctnode("Editing Contact"));

  content.innerHTML = "";
  content.appendChild(cont);

	clearSiteStatus();

}


/****************************************************************
	FUNCTION: loadECToolbar
	PURPOSE:  setups up toolbar for main contact page
	INPUT:	  none
*****************************************************************/	

function loadECToolbar(sp) {

	//skip if in wizard mode
	if (pagemode=="wizard") return false;

	var saveBtn = siteToolbarCell("Save","saveContact()","save.png");

	//add our stuff to it
	clearElement(toolbarBtns);
	toolbarBtns.appendChild(saveBtn);

}


/****************************************************************
	FUNCTION: saveContact
	PURPOSE:  submits the current form to the save module
	INPUT:	  none
*****************************************************************/	

function saveContact() 
{

	//make sure we have a first and last name
	if (ge("firstName").value.length==0) 
	{
		alert("You must enter a first name");
		ge("firstName").focus();
		return false;
	}
	if (ge("lastName").value.length==0) 
	{
		alert("You must enter a last name");
		ge("lastName").focus();
		return false;
	}

	//create our url
	updateSiteStatus("Saving contact");
	var url = "index.php?module=savecontact&pageAction=save&" + dom2Query(content);
	if (contact) url += "&contactId=" + contact;
	protoReq(url,"writeSaveContact","POST");

}


/****************************************************************
	FUNCTION: writeSaveContact
	PURPOSE:  handles response from save module
	INPUT:	  resp -> xml data from savecontact module
*****************************************************************/	

function writeSaveContact(data) {

	//handle errors
	if (data.error) {
		alert(data.error);
		return false;
	} else {

		contact = data.contact_id;
		showSaveMsg("Contact saved successfully");

	}

	//reload our menu
	loadMenu();

}



/****************************************************************
  FUNCTION: upperCase
  PURPOSE:  takes contents of textbox and makes the first letter
            of every word uppercase
  INPUT:    tbox -> id of box to perform action on
*****************************************************************/
function upperCase(tboxName) {

  var tbox = ge(tboxName);
  var str = tbox.value;

  if (str.length==0) return false;

  var arr = str.split(" ");
  var retarr = new Array();

  for (var i=0;i<arr.length;i++) {

    var newstr = arr[i].charAt(0).toUpperCase() + arr[i].substr(1);
    retarr[i] = newstr;

  }

  tbox.value = retarr.join(" ");

}
