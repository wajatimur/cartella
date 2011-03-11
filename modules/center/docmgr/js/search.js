var searchref;
var searchpath;
var searchparams;
var formloading = false;;
var P;

function cycleSearchView()
{

  searchref = ge("searchFilters");

	//reset our limits and search filters
	searchOffset = 0;   
	searchPage = 1;

  if (searchref.style.display=="block") 
	{
		browsemode = "browse";
		curpath = savecurpath;
		setCeiling(saveceiling);
	}
	else 
	{
		browsemode = "search";
		savecurpath = curpath;
		saveceiling = ceiling;
	}

	searchView();

}

function searchView(nosearch) {

	searchref = ge("searchFilters");

	if (browsemode=="browse")
	{

		clearElement(searchref);
		searchref.style.display = "none";

		if (BROWSE_PAGINATE==1) 
			ge("browsePager").style.display = "block";
		else 
			ge("browsePager").style.display = "none";

		ge("msgSiteSearchString").value = "";
		searchinit = "";

		if (!nosearch) browsePath();

		setClass(ge("searchBtn"),"toolbarCell");

	}
	else 
	{

		searchref.style.display = "block";
		if(!searchinit) initSearch();
		setClass(ge("searchBtn"),"toolbarCellSelect");

    //update the pager
    ge("browsePager").style.display = "block";

		if (!nosearch) objectSearch();

	}

}

function initSearch() {

	searchinit = 1;
	
	searchref = ge("searchFilters");
	if (searchref.style.display=="none")
	{
		setClass(ge("searchBtn"),"toolbarCellSelect");
 		searchref.style.display = "block";
	}

	var row = ce("div","searchToolbar","coreSearchToolbar");

	row.appendChild(addBtn(1));

	//where we search
	var incell1 = ce("div","searchCell");
	incell1.appendChild(createRadio("search_path","folder","folder","objectSearch()"));
	incell1.appendChild(ctnode("This Collection"));

	var incell2 = ce("div","searchCell");
	incell2.appendChild(createRadio("search_path","all","","objectSearch()"));
	incell2.appendChild(ctnode("Everywhere"));

	row.appendChild(incell1);
	row.appendChild(incell2);
	row.appendChild(ce("div","toolbarDivider","","|"));

	var cb1 = ce("div","searchCell");
	cb1.appendChild(createCheckbox("search_option[]","name","name","objectSearch()"));
	cb1.appendChild(ctnode("Name"));

	var cb2 = ce("div","searchCell");
	cb2.appendChild(createCheckbox("search_option[]","summary","summary","objectSearch()"));
	cb2.appendChild(ctnode("Summary"));

	var cb3 = ce("div","searchCell");
	cb3.appendChild(createCheckbox("search_option[]","content","content","objectSearch()"));
	cb3.appendChild(ctnode("Content"));

	row.appendChild(cb1);
	row.appendChild(cb2);
	row.appendChild(cb3);

	row.appendChild(ce("div","toolbarDivider","","|"));

	var cell = ce("div","searchCell");
	cell.appendChild(ctnode("My Shares "));
	
	var sel = createSelect("share_filter");
	sel[0] = new Option("-- Select --","any");
	sel[1] = new Option("Yes","t");
	sel[2] = new Option("No","f");
	setChange(sel,"objectSearch()");

	cell.appendChild(sel);

	row.appendChild(cell);


	searchref.appendChild(row);
	searchref.appendChild(createCleaner());

	return row;

}

function addBtn(norem) {

	var div = ce("div","addremDiv");

	var addimg = ce("img");
	addimg.setAttribute("src",theme_path + "/images/icons/add-criteria.png");
	addimg.setAttribute("title","Add Criteria");
	setClick(addimg,"addSearchFilter()");

	div.appendChild(addimg);

	if (!norem) 
	{

		var remimg = ce("img");
		remimg.setAttribute("src",theme_path + "/images/icons/remove-criteria.png");
		setClick(remimg,"removeSearchFilter(event)");
		remimg.setAttribute("title","Remove Criteria");
		div.appendChild(remimg);

	}


	return div;		
	
}

function removeSearchFilter(e) 
{

	//find row if init by an element
	var ref = getEventSrc(e);
	row = ref.parentNode.parentNode;

	searchref.removeChild(row);
	objectSearch();

}

function addSearchFilter(preset) 
{

	var arr = searchref.getElementsByTagName("div");
	var num = 0;

	for (var i=0;i<arr.length;i++) 
	{
		var sp = arr[i].getAttribute("search_param");
		if (sp) num++;
	}

	var row = ce("div","searchToolbar");
	row.setAttribute("search_param",num);

	row.appendChild(addBtn());
	row.appendChild(createFilterOpt(preset));

	setMatch(row);
	searchref.appendChild(row);
	searchref.appendChild(createCleaner());

	return row;

}

function createFilterOpt(preset) {

	//if we have keywords available, show the keyword filter
	if (keywords.length > 0)
	{

		var txtarr = new Array("Date Added","Date Modified","Type","Keywords","Owned By");
		var valarr = new Array("date_add","date_mod","object_type","keyword","account");

	}
	else
	{

		var txtarr = new Array("Date Added","Date Modified","Type","Owned By");
		var valarr = new Array("date_add","date_mod","object_type","account");

	}

	var sel = createSelect("filter[]");
	setChange(sel,"setMatch(event,1)");

	for (var i=0;i<txtarr.length;i++) 
	{

		sel[i] = new Option(txtarr[i],valarr[i]);

	}

	if (preset) sel.value = preset;

	var div = ce("div","searchCell","",sel);
	return div;

}

function setMatch(row,useevent) {

	//find row if init by an event
	if (useevent) {
		var ref = getEventSrc(row);
		row = ref.parentNode.parentNode;
	}

	var sparr = row.getElementsByTagName("span");
	if (sparr.length > 0) {
		clearElement(sparr[0]);
		var sp = sparr[0];
	} else {
		var sp = ce("span");
		row.appendChild(sp);
	}

	var filterval = row.getElementsByTagName("select")[0].value;
	
	//setup the remaining filter based on the first value
	var func = filterval + "Filter(sp)";
	eval(func);

}

function object_typeFilter(row) {

	var sel = createSelect("match[]");
	sel[0] = new Option("Equals","equals");

	setChange(sel,"objectSearch()");

	//value box
	var val = createSelect("value[]");
	val[0] = new Option("Any Type","any");
	val[1] = new Option("Collection","collection");
	val[2] = new Option("File","file");
	val[3] = new Option("Document","document");
	val[4] = new Option("URL","url");
	val[5] = new Option("Search","search");

	setChange(val,"objectSearch()");

	row.appendChild(ce("div","searchCell","",sel));
	row.appendChild(ce("div","searchCell","",val));

}

function date_addFilter(row) {

	var sel = createSelect("match[]");
	sel[0] = new Option("On","on");
	sel[1] = new Option("Before","before");
	sel[2] = new Option("After","after");

	setClick(sel,"objectSearch()");

	//value box
	var tb = createTextbox("value[]");
	setChange(tb,"objectSearch()");
	tb.size = 10;

	row.appendChild(ce("div","searchCell","",sel));
	row.appendChild(ce("div","searchCell","",tb));

}

function date_modFilter(row) {
	date_addFilter(row);
}

function getCurKey(row) {

	var arr = row.getElementsByTagName("select");
	var id = arr[0].value;
	
	var curkey = new Array();

	for (var i=0;i<keywords.length;i++) 
	{

		if (id==keywords[i].id) 
		{
			curkey = keywords[i];
			break;
		}

	}

	return curkey;

}

function keywordFilter(row) {

	var which = createSelect("keywordFilter[]");
	setChange(which,"keywordMatch(event)");

	for (var i=0;i<keywords.length;i++) {
		var k = keywords[i];
		which[i] = new Option(k.name,k.id);
	}

	row.appendChild(ce("div","searchCell","",which));

	keywordMatch(which);

}

function keywordMatch(e) {

	if (e.type=="change") var p = getEventSrc(e);
	else var p = e;

	var row = p.parentNode.parentNode;

	//clear the dropdowns if they are already there
	var divarr = row.getElementsByTagName("div");
	var len = divarr.length;

	if (divarr.length > 1)
	{
		row.removeChild(divarr[2]);
		row.removeChild(divarr[1]);
	}

	//make a new match dropdown
	var curkey = getCurKey(row);

	var sel = createSelect("keywordMatch[]");
	setChange(sel,"objectSearch()");
	sel[0] = new Option("matches","matches");

	if (curkey.type!="select") sel[1] = new Option("contains","contains");

	var cell = ce("div","searchCell","",sel);
	cell.setAttribute("searchopt","1");
	row.appendChild(cell);

	keywordValue(sel);

}

function keywordValue(e) {

	if (e.type=="change") var p = getEventSrc(e);
	else var p = e;

	var row = p.parentNode.parentNode;

	var curkey = getCurKey(row);

	if (curkey.type=="select") 
	{

		var tb = createSelect("keywordValue[]");
		if (curkey.option)
		{

			for (var i=0;i<curkey.option.length;i++)
			{
				tb[i] = new Option(curkey.option[i].name,curkey.option[i].id);
			}

		}

		setChange(tb,"objectSearch()");

	} else 
	{

		//search string	
		var tb = createTextbox("keywordValue[]");
		setKeyUp(tb,"ajaxSiteSearch()");

	}


	var cell = ce("div","searchCell","",tb);
	cell.setAttribute("searchopt","1");
	row.appendChild(cell);

	objectSearch();

}



function objectSearch()
{

	//bail if we are in form load mode
	if (formloading) return false;

  if (!searchinit) 
	{
		browsemode = "search";
		savecurpath = curpath;
		saveceiling = ceiling;
		searchView();
	}

  updateSiteStatus("Searching...");

  if (!useLast) 
	{
    sort_field="rank";
    sort_dir="DESC";
    searchLimit = RESULTS_PER_PAGE;
    searchOffset = 0;
  }

  var ss = ge("msgSiteSearchString").value;
  browsemode = "search";

	P = new PROTO();

  //assemble the rest of our options
  P.add("command","docmgr_search_search");
  P.add("sort_field",sort_field);
  P.add("sort_dir",sort_dir);
  P.add("limit",searchLimit);
  P.add("offset",searchOffset);
  P.add("search_string",ss);

  //use values stored in session, no need to requery 
	if (useLast)
	{
		P.add("use_last","1");
		useLast = "";
	}
	else
	{
		searchPage = 1;
	}

	P.addDOM(ge("searchFilters"));	

	//figure out whether we are searching in the current collection or not
	var sp = getRadioValue("search_path",ge("searchFilters"));

	if (sp=="folder") 
	{
		setCeiling(savecurpath);
		P.add("path",savecurpath);
	}

	//store xml for later
	searchparams = P.encodeData(1);
	P.post(DOCMGR_API,"writeBrowseResults");

}


//basic match->value filters
function popRegFilter(curdata,name) {

		//now, populate the rest of them with type, date add/mod, and keyword
		for (var i=0;i<curdata.length;i++) {

			var tb = addSearchFilter();

			//set the first one
			tb.getElementsByTagName("select")[0].value = name;
			setMatch(tb);

			tb.getElementsByTagName("select")[1].value = curdata[i].match;
			tb.getElementsByTagName("input")[0].value = curdata[i].value;

		}

}

//basic filters that are all checkbox options
function popOptFilter(curdata,name) {

		for (var i=0;i<curdata.length;i++) {

			var tb = addSearchFilter();

			//set the first one
			tb.getElementsByTagName("select")[0].value = name;
			setMatch(tb);

			var arr = tb.getElementsByTagName("input");

			var valarr = curdata[i].value;

			for (var c=0;c<arr.length;c++) {

				arr[c].checked = false;

				for (var q=0;q<valarr.length;q++) {
					if (valarr[q]==arr[c].value) {
						arr[c].checked = true;
						break;
					}

				}

			}

		}

}

/******************************************************
	specialty filter populate
******************************************************/
//sets up the keyword filters with data
function popKeywordFilter(curdata,name) {

		//now, populate the rest of them with type, date add/mod, and keyword
		for (var i=0;i<curdata.length;i++) {

			var tb = addSearchFilter();

			//set the first one
			tb.getElementsByTagName("select")[0].value = name;
			setMatch(tb);

			tb.getElementsByTagName("select")[1].value = curdata[i].keyid;
			tb.getElementsByTagName("select")[2].value = curdata[i].match;
			tb.getElementsByTagName("input")[0].value = curdata[i].value;

		}

}

//sets up the search option filter in the main search bar
function popSearchOptFilter(row,curdata,name) {

	//where we look in
	for (var i=0;i<curdata.length;i++) {

		//set the first one
		var arr = row.getElementsByTagName("input");
		var valarr = curdata[i].value;

		for (var c=0;c<arr.length;c++) {

			if (arr[c].name!=name) continue;

			arr[c].checked = false;

			for (var q=0;q<valarr.length;q++) {
				if (valarr[q]==arr[c].value) {
					arr[c].checked = true;
					break;
				}

			}

		}

	} //end search_option

}

function keywordView() 
{

	browsemode = "search";
	cycleSearchView();
	if (searchref.style.display=="block") addSearchFilter('keyword');

}


function saveSearch()
{

	var popup = openSitePopup("300","225");
	
	popup.appendChild(ce("div","sitePopupHeader","","Save Search"));
	
	var cell = ce("div","sitePopupCell");
	cell.appendChild(ce("div","formHeader","","Save As"));
	cell.appendChild(createTextbox("saveSearchName"));
	popup.appendChild(cell);

	var cell = ce("div","sitePopupCell");
	cell.appendChild(ce("div","formHeader","","Summary"));
	cell.appendChild(createTextarea("saveSearchSummary"));
	popup.appendChild(cell);

	var cell = ce("div","sitePopupCell");
	cell.appendChild(createBtn("saveBtn","Save","runSaveSearch()"));
	popup.appendChild(cell);


}

function runSaveSearch()
{
	
	var n = ge("saveSearchName").value;
	var s = ge("saveSearchSummary").value;

	if (n.length=="0")
	{
		alert("You must enter a name for the saved search");
		ge("saveSearchName").focus();
	} else 
	{

		updateSiteStatus("Saving search");

		var p = new PROTO();
		p.add("name",n);
		p.add("summary",s);
		p.add("command","docmgr_search_save");
		p.add("object_type","search");
		p.add("parent_path",curpath);
		p.add("params",searchparams);

		p.post(DOCMGR_API,"writeSaveSearch");

	}

}

function writeSaveSearch(data)
{

	clearSiteStatus();

	if (data.error) alert(data.error);
	else 
	{
		closeSitePopup();

  	//update the tree.  if not passed a path, we are doing an init so don't do crap
  	if (curtree) 
  	{
    	curtree.cycleObjectPath("/Users/" + USER_LOGIN + "/Saved Searches",1);
  	}

	}

}

function accountFilter(row) {

  var sel = createSelect("match[]");
  sel[0] = new Option("Equals","equals");
  setClick(sel,"objectSearch()");

  //value box
  var accountsel = createSelect("value[]");
  setChange(accountsel,"objectSearch()");
  accountsel[0] = new Option("Select Account","0");

  for (var i=0;i<accounts.length;i++)
  {
    accountsel[i+1] = new Option(accounts[i].name,accounts[i].id);
  }
   
  row.appendChild(ce("div","searchCell","",sel));
  row.appendChild(ce("div","searchCell","",accountsel));

}

function popSearchFilters(data)
{

	//populate the easy ones
	ge("msgSiteSearchString").value = data.search_string;

	popArea(ge("coreSearchToolbar"),data);

	if (data.filter)
	{
	
		var kw = 0;
	
		//now setup the filters
		for (var i=0;i<data.filter.length;i++)
		{
	
			var row = addSearchFilter(data.filter[i])
	
			var selarr = row.getElementsByTagName("select");
			var txtarr = row.getElementsByTagName("input");
	
			if (data.filter[i]=="keyword")
			{
	
				selarr[1].value = data.keywordFilter[kw];			
	
				//run this to make sure the 3rd option is made right
				keywordMatch(selarr[2]);
				selarr[2].value = data.keywordMatch[kw];			
	
				if (selarr[3])
					selarr[3].value = data.keywordValue[kw];			
				else
					txtarr[0].value = data.keywordValue[kw];
	
				kw++;
	
			}
			else
			{
	
				//first set the equals
				selarr[1].value = data.match[i];
		
				if (data.filter[i]=="date_add" || data.filter[i]=="date_mod")
				{
					txtarr[0].value = data.value[i];
				}
				else
				{
					selarr[2].value = data.value[i];
				}
	
			}
	
		}
	
	}

}

function popArea(ref,data)
{

  var arr = ref.getElementsByTagName("input");
  var sel = ref.getElementsByTagName("select");

  for (var i=0;i<arr.length;i++)
  {

		var name = arr[i].id;
		var val = data[name];

    if (val)
    {
 
			if (arr[i].type=="radio" && val==arr[i].value) arr[i].checked = true;
			else if (arr[i].type=="checkbox")
			{

				var key = arraySearch(arr[i].value,val);
				if (key!=-1) arr[i].checked==true;
				else arr[i].checked = false;

			}

    }

  }

  var sel = ref.getElementsByTagName("select");

  for (var i=0;i<sel.length;i++)
  {

		var name = sel[i].id;
		var val = data[name];
    if (val) sel[i].value = val;

  }

}