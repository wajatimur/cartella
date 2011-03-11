
var permDiv;
var permList;
var timer;
var curperm;

function loadPermissions() {
	
	clearElement(content);

	//the header
	permDiv = ce("div","","permDiv");
	permDiv.appendChild(ce("div","","permHeader"));
	permDiv.appendChild(createCleaner());
	
	//the div containing our list
	permList = ce("div");
	setClass(permList,"permList");
	permDiv.appendChild(permList);

	loadPEToolbar();

	showPermList();

  //if we are dealing with a collection, allow the ability to propagate permissions on children
  if (objinfo.object_type=="collection" || isData(objinfo.pages_id)) 
	{

    var cell = ce("div","","resetOption");
    cell.appendChild(createCheckbox("reset_perms","1"));
    cell.appendChild(ctnode("Reset permissions on all child objects of this collection"));
    permDiv.appendChild(cell);

  }

	content.appendChild(permDiv);
	
}

function loadPEToolbar() {

  clearElement(tbBtns);
	clearElement(tbTitle);

  //setup our buttons
  tbBtns.appendChild(siteToolbarCell("Save","savePermissions()","save.png"));

	tbTitle.appendChild(createPermFilter());
	tbTitle.appendChild(createSearch());

}


function savePermissions() {

	var arr = permList.getElementsByTagName("li");

	var p = new PROTO();
	p.add("command","docmgr_object_saveperms");
	p.add("object_id",object);
	
	if (ge("reset_perms") && ge("reset_perms").checked==true) p.add("reset_perms","1");

	for (var i=0;i<arr.length;i++) 
	{

		if (arr[i].hasAttribute("obj_id")) 
		{

			var objid = arr[i].getAttribute("obj_id");
			var type = arr[i].getAttribute("obj_type");
		
			var forms = arr[i].getElementsByTagName("input");

			for (var c=0;c<forms.length;c++) 
			{

				//add the entry for this object if checked	
				if (forms[c].checked==true) 
				{

					var data = new Object();
					data.id = objid;
					data.type = type;
					data.value = forms[c].value;
					p.add("perm",data);

				}

			}

		}

	}

	updateSiteStatus("Saving permissions");
	p.post(DOCMGR_API,"writeSavePerm");

}


function writeSavePerm(data) {

	clearSiteStatus();

	if (data.error) alert(data.error);

}

function clearPermissions(id,type) {

	var arr = permList.getElementsByTagName("li");

	for (var i=0;i<arr.length;i++) {

		var objid = arr[i].getAttribute("obj_id");
		var objtype = arr[i].getAttribute("obj_type");

		if (objid && objid==id && objtype==type) {

			var forms = arr[i].getElementsByTagName("input");

			for (var c=0;c<forms.length;c++) forms[c].checked = false;
			
			break;

		}

	}

}

function showPermList() {

	updateSiteStatus("Loading permissions list");

	var pf = ge("permFilter").value;

	var p = new PROTO();
	p.add("command","docmgr_object_getperms");
	p.add("object_id",object);
	p.add("perm_filter",pf);
	p.add("search_string",ge("agSearchString").value);
	p.post(DOCMGR_API,"writePermList");

}

function createPermHeader() {

	var header = ge("permHeader");
	clearElement(header);

	//create the divs
	header.appendChild(ce("div","permNameHeader","","Account/Group"));
	header.appendChild(ce("div","permBoxHeader","","Admin"));
	header.appendChild(ce("div","permBoxHeader","","Edit"));
	header.appendChild(ce("div","permBoxHeader","","View"));
	header.appendChild(ce("div","permBoxHeader","","Clear"));
	header.appendChild(createCleaner());

}

//populate our permissions list with names and data
function writePermList(data) {

	permList.innerHTML = "";
	curperm = data.current_object_perm;

	clearSiteStatus();
	createPermHeader();

	//we shouldn't ever see this
	if (!data.entry) 
	{
		permList.innerHTML = "<div class=\"statusMessage\">No groups or accounts to display</div>";
	} else {

		var num = data.entry.length;
		var i;

		for (i=0;i<num;i++) writePermEntry(data.entry[i]);

	}


	clearSiteStatus();
	
}

//writes an individual row
function writePermEntry(entry) {

	//create the row
	var row = ce("li");
	
	//store the user id
	row.setAttribute("obj_id",entry.id);
	row.setAttribute("obj_type",entry.type);

	//this makes it so you can only check one horizontally
	var formname = "permData" + entry.type + entry.id;

	//populate them
	var aradio = createRadio(formname,"admin",entry.perm);
	var eradio = createRadio(formname,"edit",entry.perm);
	var vradio = createRadio(formname,"view",entry.perm);

	var img = createImg(theme_path + "/images/icons/delete.png");
	setClick(img,"clearPermissions('" + entry.id + "','" + entry.type + "')");

	//put it all together
	row.appendChild(ce("div","permNameCell","",entry.name));

	row.appendChild(ce("div","permBoxCell","",aradio));
	row.appendChild(ce("div","permBoxCell","",eradio));
	row.appendChild(ce("div","permBoxCell","",vradio));
	row.appendChild(ce("div","permBoxCell","",img));
	row.appendChild(createCleaner());
	
	permList.appendChild(row);

}



function createSearch() {

  /*********************************************
    message search
  *********************************************/
  var msgsearch = ce("div","","agSearch");       //search for messages
  var searchoptdiv = ce("div","","searchOptionDiv");        //option container for search
  var searchoptimg = ce("img","","searchOptionImg");        //search magnifying glass image
  searchoptimg.setAttribute("src",theme_path + "/images/icons/search-options.gif");
  var searchopt = ce("div","","searchOptions");         //container for all search options

  //alignment help for ie
  if (document.all) searchopt.style.marginLeft = "-20px";

  var searchtxt = createTextbox("agSearchString");     //textbox for entering a search string
  searchtxt.setAttribute("autocomplete","off");
  setKeyUp(searchtxt,"ajaxSearch()");

  //create our search option div
  //searchoptdiv.appendChild(searchoptimg);

  //assemble everything until the main message search
  msgsearch.appendChild(searchoptdiv);

  msgsearch.appendChild(searchtxt);
  msgsearch.appendChild(createCleaner());
	return msgsearch;

}


/**************************************************************
  FUNCTION: ajaxSearch
  PURPOSE:  uses a timer to prevent queries from being sent
            at every key stroke, but queries after a set time
            of inactivity
**************************************************************/
function ajaxSearch() {

  //reset the timer
  clearTimeout(timer);

  updateSiteStatus("Searching...");

  //set it again.  when it times out, it will run.  this method keeps fast typers from querying the database a lot
  timer = setTimeout("showPermList()",250);

}

function createPermFilter() {

	var cell = ce("div","","agFilter","Show: ");

	var sel = createSelect("permFilter","showPermList()");
	sel[0] = new Option("Show all entries","all");
	sel[1] = new Option("Accounts only","accounts");
	sel[2] = new Option("Groups only","groups");
	sel[3] = new Option("Selected only","selected");

	cell.appendChild(sel);

	return cell;


}