
var searchresults;
var searchcriteria;
var sortField = "name";
var sortDir = "ASC";
var timer;

function loadPage() {

	//for later
	searchresults = ge("searchResultContent");
	runSearch();

}

//run the search
function runSearch() {

	updateSiteStatus("Searching...");
	var ms = ge("msgSiteSearchString");

	//assemble our search url for processing
	var url = "index.php?module=contactsearch";
	if (sortField) url += "&sortField=" + sortField + "&sortDir=" + sortDir;
	if (ms.value.length>0) url += "&searchString=" + ms.value;

	protoReq(url,"writeSearchResults");

}

//our search result handler
function writeSearchResults(data) {

	clearSiteStatus();
	 

	var i;

	//get a reference to our results
	clearElement(searchresults);

	//handle if there are no results to display
	if (!data.contact) {

		var row = ce("tr");
		if (document.all) var cell = ce("<td colspan=6>","errorMessage","","No results to display");
		else {
			var cell = ce("td","errorMessage","","No results to display");
			cell.setAttribute("colspan","6");
		}

		row.appendChild(cell);
		searchresults.appendChild(row);

	} else {

		for (i=0;i<data.contact.length;i++) {
			searchresults.appendChild(createResultEntry(data.contact[i]));
		}					

	}
}

//resort our results
function sortResults(field) {

	sortField = field;
	if (sortDir=="ASC") sortDir="DESC";
	else sortDir = "ASC";

	runSearch();

}


//create a result entry
function createResultEntry(entry) {

	//container div
	var row = ce("tr","searchResultRow");

	var cell = ce("td","selectColumn");
	var cb = createCheckbox("contactId[]",entry.id);
	cell.appendChild(cb);
	cell.setAttribute("email",entry.email);
	row.appendChild(cell);
	
	//the name.  don't create a link if it's locked
	var nametxt = formatContactName(entry);
	var cell = ce("td","nameColumn","",nametxt);
	setClick(cell,"location.href = 'index.php?module=editcontact&contactId=" + entry.id + "'");
	row.appendChild(cell);

	if (!isData(entry.work_phone)) entry.work_phone = String.fromCharCode(160);
	if (!isData(entry.home_phone)) entry.home_phone = String.fromCharCode(160);
	if (!isData(entry.mobile)) entry.mobile = String.fromCharCode(160);
	if (!isData(entry.email)) entry.email = String.fromCharCode(160);

	row.appendChild(ce("td","emailColumn","",entry.email));
	row.appendChild(ce("td","phoneColumn","",entry.work_phone));	
	row.appendChild(ce("td","phoneColumn","",entry.home_phone));	
	row.appendChild(ce("td","phoneColumn","",entry.mobile));	

	return row;
	
}



//spawns an email window if the email address appears valid
function sendEmail(id,email,invalid) {

	//see if there's an email address
	if (email.length < 5 || email=="undefined") {
		if (confirm("It appears there's no email address for this contact.  Do you wish to enter one now?")) {
			location.href = "index.php?module=editcontact&contactId=" + id;
			return true;
		} else {
			alert("You cannot send this contact an email until a valid email address has been set");
			return false;
		}
	}
	else if (email.invalid=="t") {
		if (confirm("It appears the contact's email address is invalid.  Do you wish to update it?")) {
			location.href = "index.php?module=editcontact&contactId=" + id;
			return true;
		} else {
			alert("You cannot send this contact an email until a valid email address has been set");
			return false;
		}
	}

	//if we make it to here, spawn the email window
	var url = "index.php?module=createemail&contactId=" + id;
	location.href = url;

}

//create a properly formatted address entry from our data
function addressEntry(entry) {

	var addrdiv = ce("div");

	if (entry.address) addrdiv.appendChild(ctnode(entry.address + " "));

        if (entry.city || entry.state || entry.zip) {
                if (entry.city) addrdiv.appendChild(ctnode(entry.city + ", "));
                if (entry.state) addrdiv.appendChild(ctnode(entry.state + " "));
                if (entry.zip) addrdiv.appendChild(ctnode(entry.zip + " "));
        }
 
	return addrdiv;

}


/**************************************************************
	FUNCTION: ajaxSearch
	PURPOSE: 	uses a timer to prevent queries from being sent
						at every key stroke, but queries after a set time
						of inactivity
**************************************************************/

//runs our search on a timer to keep from querying the database every time the user presses a key
function contactSearch() {

	//reset the timer
	clearTimeout(timer);

	updateSiteStatus("Searching...");

	//set it again.  when it times out, it will run.  this method keeps fast typers from querying the database a lot
	timer = setTimeout("runSearch()",250);

}


/****************************************************************
  FUNCTION: formatContactName
  PURPOSE:  returns a properly displayed contact name, taking
            into account the cobuyer name data
  INPUT:    entry -> array of information as pulled from database
*****************************************************************/
function formatContactName(entry,retdiv) {

  var txt;

  if (isData(entry.cb_first_name)) {

    if (!isData(entry.cb_last_name) || entry.cb_last_name == entry.last_name) {
      txt = entry.first_name + " & " + entry.cb_first_name + " " + entry.last_name;
    } else {
      txt = entry.first_name + " " + entry.last_name + " & " + entry.cb_first_name + " " + entry.cb_last_name;
    }

  } else {
    txt = entry.first_name + " " + entry.last_name;
  }

  //return in a div if set
  if (retdiv) {
    var mydiv = ce("div","","",txt);
    return mydiv;
  } else {
    return txt;
  }

}

function deleteContact() {

		var cids = dom2Query(searchresults);

		if (cids.length==0)
		{
			alert("You must select contacts to delete.");
		}
		else if (confirm("Are you sure you want to remove these contact(s)?")) 
		{

			updateSiteStatus("Deleting contact");
			var url = "index.php?module=deletecontact&delete=1&" + cids;
			protoReq(url,"writeDeleteContact");

		}

}

function writeDeleteContact(data) {

	if (data.error) alert(data.error);
	else runSearch();

}

function emailContact() {

		var cids = dom2Query(searchresults);

		if (cids.length==0)
		{
			alert("You must select contacts to send the email to.");
		}
		else
		{
			var url = "index.php?module=createemail&" + cids;
			location.href = url;
		}
}
