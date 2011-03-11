
/********************************************************************
	FILENAME: editaccount.js
	PURPOSE:  contains editprospect section related functions

*********************************************************************/

/****************************************************************
	FUNCTION: loadEditAccount
	PURPOSE:  main function for loading our editaccount page.
						calls loadForms with appropriate page template
						and handlers
	INPUT:	  none
*****************************************************************/	
function loadEditAccount() {

	content.innerHTML = "";

	//set the page title
	toolbarTitle.innerHTML = "";
  toolbarTitle.appendChild(ctnode("Editing Account Access For Contact"));
	updateToolbarName();

	//create our address selects
	content.appendChild(createAccountSelect());

	//load the account list and current list of there's a contact
	loadAccountList();
	if (contact) {
		showObject('accountSelect');
		loadMemberList();
	}

	//clear all status and buttons
	toolbarBtns.innerHTML = "";
	clearSiteStatus();

}

/****************************************************************
	FUNCTION: createAccountQuestion
	PURPOSE:  prompt user to add this to their address book, or
						someone elses
	INPUT:	  none
*****************************************************************/	
function createAccountQuestion() {

	var qdiv = ce("div","accountQuestion");
	var header = ce("div","formHeader","","Will this contact go into your personal address book?");

	var formdiv = ce("div");
	setClass(formdiv,"accountform");

	var yesdiv = ce("div","","","Yes");
	var nodiv = ce("div","","","No");

	var yesbtn = createForm("radio","createQuestion","yes");
	var nobtn = createForm("radio","createQuestion");

	setClick(yesbtn,"hideObject('accountSelect');setAccountMode('self')");
	setClick(nobtn,"showObject('accountSelect');setAccountMode('other')");

	formdiv.appendChild(yesbtn);
	formdiv.appendChild(yesdiv);
	formdiv.appendChild(nobtn);
	formdiv.appendChild(nodiv);

	qdiv.appendChild(header);
	qdiv.appendChild(formdiv);
	qdiv.appendChild(createCleaner());

	return qdiv;

}

function setAccountMode(newmode) {

	accountmode = newmode;

}

/****************************************************************
	FUNCTION: createAccountSelect
	PURPOSE:  show the window for selecting an account
	INPUT:	  none
*****************************************************************/	
function createAccountSelect() {

	var seldiv = ce("div","","accountSelect");

	var lc = ce("div","leftColumn");
	var rc = ce("div","rightColumn");

	var memheader = ce("div","formHeader","","Assigned Accounts");
	var listheader = ce("div","formHeader","","Select From Accounts");

	lc.appendChild(memheader);
	lc.appendChild(ce("div","","memberList"));
	rc.appendChild(listheader);
	rc.appendChild(ce("div","","accountList"));

	//put it all together
	seldiv.appendChild(lc);
	seldiv.appendChild(rc);
	seldiv.appendChild(createCleaner());

	return seldiv;

}

/****************************************************************
	FUNCTION: loadAccountList
	PURPOSE:  shows a list of all accounts thsi user can pick
	INPUT:	  none
*****************************************************************/	
function loadAccountList() {

	ge("accountList").innerHTML = "<div class=\"statusMessage\">Loading Accounts</div>\n";
	var url = "index.php?module=accountlist";
	protoReq(url,"writeAccountList");

}

/****************************************************************
	FUNCTION: writeAccountList
	PURPOSE:  handler for complete account list
	INPUT:	  none
*****************************************************************/	
function writeAccountList(data) {
	ge("accountList").innerHTML = "";
	 
	writeAccounts(data,"accountList");
}

/****************************************************************
	FUNCTION: loadMemberList
	PURPOSE:  shows a list of all accounts that can see this contact
	INPUT:	  none
*****************************************************************/	
function loadMemberList() {

	ge("memberList").innerHTML = "<div class=\"statusMessage\">Loading Accounts</div>\n";
	var url = "index.php?module=contactaccount&action=showassigned&contactId=" + contact;
	protoReq(url,"writeMemberList");

}

/****************************************************************
	FUNCTION: writeMemberList
	PURPOSE:  handler for members of the contact
	INPUT:	  none
*****************************************************************/	
function writeMemberList(data) {

	clearSiteStatus();
	ge("memberList").innerHTML = "";
	 
	writeAccounts(data,"memberList");
}

/****************************************************************
	FUNCTION: writeAccounts
	PURPOSE:  work function for populating divs with our account list
	INPUT:	  none
*****************************************************************/	
function writeAccounts(data,wdiv) {

	clearSiteStatus();

	var writeDiv = document.getElementById(wdiv);
	writeDiv.innerHTML = "";

	//show an error message if no accounts are found
	if (!data.account) {

		var adiv = document.createElement("div");
		setClass(adiv,"errorMessage");
		adiv.appendChild(document.createTextNode("No accounts set for contact"));
		writeDiv.appendChild(adiv);		

	}
	else {

		var alist = document.createElement("ul");
		alist.style.listStyleType = "none";

		var len = data.account.length;

		for (i=0;i<len;i++) {

			curaccount = data.account[i];

			var curli = document.createElement("li");

			//to pull these later
			curli.setAttribute("accountId",curaccount.id);
			curli.setAttribute("accountName",curaccount.name);

			//create the plusbox for adding an account
			var curimg = document.createElement("img");
			curimg.style.marginRight = 5;

			if (wdiv=="memberList") {	
				curimg.setAttribute("src","themes/default/images/dashbox.gif");
				setClick(curimg,"removeAccount('" + curaccount.id + "')");
			} else {			
				curimg.setAttribute("src","themes/default/images/plusbox.gif");
				setClick(curimg,"addAccount('" + curaccount.id + "')");
			}

			//create the text
			var name = curaccount.name;
			var curtxt = document.createTextNode(name);

			//assemble
			curli.appendChild(curimg);
			curli.appendChild(curtxt);

			alist.appendChild(curli);

		}

		writeDiv.appendChild(alist);

	}
}


/****************************************************************
	FUNCTION: addAccount
	PURPOSE:  adds the passed id to the contact.  if no contact
						around, just stores it in the account array for later
	INPUT:	  id -> account id
*****************************************************************/	
function addAccount(id) {
	
	updateSiteStatus("Adding account");
	//add to our account array
	accountId.push(id);

	//if we have a contact, update the database
	if (contact) {
		url = "index.php?module=contactaccount&action=addaccount&contactId=" + contact + "&accountId=" + id;
		if (prospect) url += "&prospectId=" + prospect;
		protoReq(url,"writeMemberList");
	} else {
		//update it manually using the contents of our array
		manualMemberUpdate();
	}

}

/****************************************************************
	FUNCTION: removeAccount
	PURPOSE:  removes the passed id from the contact.  if no contact
						around, just stores it in the account array for later
	INPUT:	  id -> account id
*****************************************************************/	
function removeAccount(id) {

	updateSiteStatus("Removing account");
	var key = arraySearch(id,accountId);
	if (key!=-1) {
		accountId.splice(key,1);
	}

	if (contact) {
		url = "index.php?module=contactaccount&action=removeaccount&contactId=" + contact + "&accountId=" + id;
		if (prospect) url += "&prospectId=" + prospect;
		protoReq(url,"writeMemberList");
	} else {
		//update it manually uisng hte contents of our array
		manualMemberUpdate();
	}

}


/****************************************************************
	FUNCTION: manualMemberUpdate
	PURPOSE:  manually updates our member list since there's
						no contact id to update the datbase with
*****************************************************************/	
function manualMemberUpdate() {

	//create a phony data array
	var data = new Array();
	var accounts = new Array();

	var arr = ge("accountList").getElementsByTagName("li");

	for (var i=0;i<accountId.length;i++) {

		var name;
		var id;
		var curAccountId = accountId[i];

		for (var c=0;c<arr.length;c++) {

			if (arr[c].getAttribute("accountId")==curAccountId) {

				//create an account arry and add it to data
				var account = new Array();
				account.id = curAccountId;
				account.name = arr[c].getAttribute("accountName");
				accounts.push(account);
				break;

			}

		}

	}


	//now, pass our created data array to the memberList handler
	data["account"] = accounts;
	writeAccounts(data,"memberList");

}
