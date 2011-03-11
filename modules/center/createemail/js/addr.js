
function loadAddressBook() {

	addrbox = openSitePopup("500","350");

	//header
	var header = ce("div","addrHeader","");
	var search = createTextbox("addrSearch","Search For Name");
	setKeyUp(search,"ajaxAddrSearch()");
	setClick(search,"checkSearchField()");

	if (USE_LDAP=="1")
	{

	  var filter = createSelect("addrFilter","searchAddress()");
	  filter[0] = new Option("My Address Book","local");
	  filter[1] = new Option("LDAP Directory","account");

	} else var filter = createHidden("addrFilter","local");

  header.appendChild(filter);
	header.appendChild(search);
	header.appendChild(ctnode("Address Book"));

	var list = ce("div","","addrList");
	list.innerHTML = "<div class=\"statusMessage\">No results to display</div>";

	//put it all together
	addrbox.appendChild(header);
	addrbox.appendChild(list);
	searchAddress();
	
}

/**************************************************************
  FUNCTION: ajaxAddrSearch
  PURPOSE:  uses a timer to prevent queries from being sent
            at every key stroke, but queries after a set time
            of inactivity
**************************************************************/
function ajaxAddrSearch() {

  //reset the timer
  clearTimeout(timer);

  var mydiv = ge("addrList");
  mydiv.innerHTML = "<div class=\"statusMessage\">Searching...</div>";

  //set it again.  when it times out, it will run.  this method keeps fast typers from querying the database a lot
  timer = setTimeout("searchAddress()",250);

}

function checkSearchField() {

	var sf = ge("addrSearch");
	if (sf.value=="Search For Name") sf.value = "";

}

function searchAddress() {

	var ss = ge("addrSearch").value;
  var filter = ge("addrFilter").value;

  var url = "index.php?module=emailsuggest&addressbook=" + filter;
	if (ss && ss!="Search For Name") url += "&searchString=" + ss;

	protoReq(url,"writeAddrResults");

}

function writeAddrResults(data) {

	 
	var al = ge("addrList");

	if (data.error) alert(data.error);
	else if (!data.contact) al.innerHTML = "<div class=\"errorMessage\">No results found</div>";
	else {

		clearElement(al);

		for (var i=0;i<data.contact.length;i++) {
			al.appendChild(addrEntry(data.contact[i]));
		}

	}

}

function addrEntry(entry) {

	var row = ce("div","addrrow");

	if (entry.email) setClick(row,"useAddr('" + entry.first_name + "','" + entry.last_name + "','" + entry.email + "','" + entry.contact_id + "')");
	else setClick(row,"alert('There is no email address set for this contact')");

	if (entry.email) var email = entry.email;
	else email = "Not set";

	var namecell = ce("div","namecell","",entry.first_name + " " + entry.last_name);
	var emailcell = ce("div","emailcell","",email);

	row.appendChild(namecell);
	row.appendChild(emailcell);
	row.appendChild(createCleaner());

	return row;	

}

function useAddr(fn,ln,email,id) {

	if (!cursorfocus) cursorfocus = "to";

	/*
	//if using a local filter, add this contact id to the contactId field so any merging will be done
	if (ge("addrFilter").value=="local") {

		var add = 0;
		var cid = ge("contactId");

		if (cid.length > 0) {
			var arr = cid.split(",");
			var key = arraySearch(id,arr);
			if (key==-1) add = 1;
		} else {
			add = 1;
		}

		if (add==1) cid.value = cid.value + "," + id;

	}	
	*/

	var tostr = ge(cursorfocus).value;
	if (tostr.length>0) tostr += ", ";
	tostr += fn + " " + ln + " <" + email + ">";

	ge(cursorfocus).value = tostr;
	closeSitePopup();

}
