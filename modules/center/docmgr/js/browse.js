/********************************************************************
	FILENAME: browse.js
	PURPOSE:  contains docmgr browsing functions

*********************************************************************/

var browseloc;
var browsemode;
var browsedata;
var patharr;
var newtype;
var timer;
var searchOffset = 0;
var searchLimit = RESULTS_PER_PAGE;
var searchTotal;
var searchPage = 1;
var currentTotal;
var filestack;
var sort_field;
var sort_dir;
var mbmode;
var useLast;

function loadBrowser() 
{

	//setup our ceiling and our browse path
	var ceilingpath = ge("ceilingPath").value;
	var path = ge("objectPath").value;

	//the ceiling is lower than the path
	if (ceilingpath.length > path.length) path = ceilingpath;

	//defaults
	if (!ceilingpath) ceilingpath = "/Users/" + USER_LOGIN;
	if (!path) path = "/Users/" + USER_LOGIN;

	//load them up
	setCeiling(ceilingpath);
	browsePath(path);

}

//stores our new ceiling client and server-side
function setCeiling(ceil)
{

	//store our ceiling in a session for page refresh
	if (ceil != ceiling)
	{

		var url = "index.php?module=setceiling&ceiling=" + ceil;
		protoReq(url);

		ceiling = ceil;

	}	

}

function preBrowse()
{

	updateSiteStatus("Updating");
	browsemode = "browse";

	//switch to browse mode
	searchView(1);

	if (!sort_field || sort_field=="rank") 
	{
		sort_field="name";
		sort_dir="ASC";
	}

	ge("searchFilters").style.display = "none";

}

function browse(id)
{

	if (id) curobject = id;
	preBrowse();

	//setup the xml
	var p = new PROTO();
	p.add("command","docmgr_search_browse");
	p.add("object_id",curobject);
	p.add("sort_field",sort_field);
	p.add("sort_dir",sort_dir);

	if (BROWSE_PAGINATE==1)
	{
  	p.add("limit",searchLimit);
  	p.add("offset",searchOffset);

		if (useLast) 
		{
			p.add("use_last","1");
			useLast = "";
		}
	
	}

	p.post(DOCMGR_API,"writeBrowseResults");

}

function browsePath(path,setceil)
{

	if (setceil) 
	{

		setCeiling(setceil);

		//add the ceiling to the path if it isn't there already
		//if (path && path.indexOf(setceil)!="-1") path = setceil + "/" + path; 

	}

	//store if passed
	if (path) 
	{

		if (path!=curpath) 
		{
			useLast = "";	
			searchOffset = 0;
			searchPage = 1;
		}

		curpath = path;

	}

	preBrowse();

	//default to root if no path was passed
	if (!curpath) 
	{
		curpath = "/Users/" + USER_LOGIN;
		setCeiling(curpath);
	}

	//setup the xml
	var p = new PROTO();
	p.add("command","docmgr_search_browse");
	p.add("path",curpath);
	p.add("sort_field",sort_field);
	p.add("sort_dir",sort_dir);

	if (BROWSE_PAGINATE==1)
	{

  	p.add("limit",searchLimit);
  	p.add("offset",searchOffset);

		//use values stored in session, no need to requery		
		if (useLast) 
		{
			p.add("use_last","1");
			useLast = "";
		}

	}

	p.post(DOCMGR_API,"writeBrowseResults");

  //update the tree.  if not passed a path, we are doing an init so don't do crap
  if (path && curtree) 
	{
		curtree.cycleObjectPath(path,1);
	}
}
	
function writeBrowseResults(data,txt) 
{

	clearSiteStatus();

	browsedata = data;
	clearElement(content);

	//set keywords. 
	if (browsedata.keyword) keywords = browsedata.keyword;
	else keywords = new Array();

	//no keywords in this section, hide them
	if (keywords.length==0) 
		ge("keywordShow").style.display = "none";
	else 
		ge("keywordShow").style.display = "block";	

	//if passed a search path back, set that as our current path
	if (browsedata.path) 
	{
		curpath = browsedata.path;
		if (curpath=="/") setCeiling(curpath);
	}

	if (browsedata.error) alert(browsedata.error);
	else if (!browsedata.object) 
	{

		breadcrumbs();

		if (ge("searchPagerText")) ge("searchPagerText").innerHTML = "Viewing Search Results";

	} else {

		breadcrumbs();

    //figure out the default view
    if (isData(browsedata.account_view)) curview = browsedata.account_view;
    else if (isData(browsedata.default_view)) curview = browsedata.default_view;

	}

	showBrowseResults();

}

function breadcrumbs()
{

	var ref = ge("siteNavHistory");
  clearElement(ref);

  var arr = curpath.split("/");

  var showpath = "";

  //if we are starting out at the top of our ceiling
  if (curpath==ceiling)
  {

    //if in the ceiling, create a link.  Otherwise just show text 
    var cont = ce("div","siteNavHistoryDiv");

    if (ceiling=="/") var txt = ROOT_NAME;
    else var txt = arr[arr.length-1];

    var link = ce("a","","",txt);
    link.setAttribute("href","javascript:browsePath(\"" + showpath + "\")");
    cont.appendChild(link);

    ref.appendChild(cont);

  } 
	else
  {

    for (var i=0;i<arr.length;i++)
    {

      //if in the ceiling, create a link.  Otherwise just show text
      var cont = ce("div","siteNavHistoryDiv");

      if (!arr[i] && i==0)
      {
        showpath = "/";                     //starting at toplevel
        arr[i] = ROOT_NAME;
      }
      else if (showpath=="/") showpath += arr[i];       //previous was toplevel, just add directory name
      else showpath += "/" + arr[i];                    //add directory marker and name

			//don't show anything before our ceiling
      if (ceiling!="/" && showpath.indexOf(ceiling)==-1) continue;

      //setup the link
      var link = ce("a","","",arr[i]);
      link.setAttribute("href","javascript:browsePath(\"" + showpath + "\")");
      cont.appendChild(link);

      ref.appendChild(cont);

      /******************************
        add image delimiter
      ******************************/
      //add an arrow if we're not at the last one
      if (i!=(arr.length-1)) 
      {

        var imgdiv = ce("div","siteNavHistoryImg");
        var img = ce("img");
        img.setAttribute("src",theme_path + "/images/navarrow.gif");

        if (document.all) img.style.marginTop = "1px";

        imgdiv.appendChild(img);

        ref.appendChild(imgdiv);

      }

    }

  }
   
  ref.appendChild(createCleaner());
	
}

function showBrowseResults()
{

		clearElement(content);

		//update our counts if from a search
		if (browsemode=="search" || BROWSE_PAGINATE==1) 
		{

			searchTotal = browsedata.totalCount;
			currentTotal = browsedata.count;
			createPager();

			if (isData(browsedata.search_params)) popSearchFilters();

		}

		if (!browsedata.object) 
		{
			content.appendChild(ce("div","errorMessage","","No files found"));
			return false;
		}


		//listview
		if (curview=="list") 
		{

			createBrowseHeader();

	  	//I hate ie
			var tbl = createTable("browseTable","","100%","0","0","0");
	  		var tbd = ce("tbody");

			for (var i=0;i<browsedata.object.length;i++) {
				tbd.appendChild(createBrowseRow(browsedata.object[i]));
			}

			tbl.appendChild(tbd);
			content.appendChild(tbl);

		//thumbnail view
		} else {

			for (var i=0;i<browsedata.object.length;i++) {
				content.appendChild(createBrowseThumb(browsedata.object[i]));
			}

		}

}

function createBrowseRow(entry) {

	var path = entry.object_path;

	//for icon creation later
	entry.object_path = path;

	var row = ce("tr","browseRow");

	//set our object attributes on our row for access later
	row.setAttribute("object_id",entry.id);
	row.setAttribute("object_path",path);
	row.setAttribute("object_type",entry.object_type);
	row.setAttribute("object_name",entry.name);
	row.setAttribute("object_perm",entry.bitmask_text);
	row.setAttribute("object_share",entry.share);

	if (isData(entry.open_with)) row.setAttribute("open_with",entry.open_with);
	if (isData(entry.extension)) row.setAttribute("extension",entry.extension);

	//legacy. set attributes on the checkbox for older functions to use
	var cb = createCheckbox("objectId[]",entry.id);
	cb.setAttribute("object_path",path);
	cb.setAttribute("object_type",entry.object_type);
	cb.setAttribute("object_name",entry.name);
	cb.setAttribute("object_perm",entry.bitmask_text);
	cb.setAttribute("obj_share",entry.share);

	if (isData(entry.open_with)) cb.setAttribute("open_with",entry.open_with);
	if (isData(entry.extension)) cb.setAttribute("extension",entry.extension);

	var selectcell = ce("td","browseSelect");
	selectcell.appendChild(cb);

	var iconcell = ce("td","browseIcon");
	iconcell.appendChild(createObjIcon(entry));

	//setup the browse icon
	var namecell = ce("td","browseName","",entry.name);
	var sizecell = ce("td","browseSize","",entry.filesize_view);
	var editcell = ce("td","browseEdited","",entry.last_modified_view);
	var optcell = ce("td","browseOptions");

	if (browsemode=="search") 
	{
		//setup a rank cell
		var rankcell = ce("td","browseRank","",parseInt(entry.rank) + "%");
	}

	setClick(iconcell,"handleResultClick(event)");
	setClick(namecell,"handleResultClick(event)");
	setClick(sizecell,"handleResultClick(event)");
	setClick(editcell,"handleResultClick(event)");
	setClick(editcell,"handleResultClick(event)");

	//handle descriptions
	if (entry.summary) 
	{
		var summary = ce("div","browseSummary","",entry.summary);
		namecell.appendChild(summary);
	}

	//handle paths
	if (entry.object_path && browsemode=="search")
	{
		namecell.appendChild(showObjectPath(entry.object_path));
	}

	//run if available
	var func = eval(entry.object_type + "Options");
	optcell.appendChild(func(entry));

	row.appendChild(selectcell);
	row.appendChild(iconcell);
	row.appendChild(namecell);	
	row.appendChild(sizecell);
	if (rankcell) row.appendChild(rankcell);
	row.appendChild(editcell);
	row.appendChild(optcell);

	return row;

}

//handle clicks from either the breadcrumb tree or clicking
//on the row to view the file or folder
function handleResultClick(e)
{

	var ref = getEventSrc(e);
	var cn = getObjClass(ref.parentNode);

	if (cn=="searchObjectPath")
	{

		browsePath(ref.getAttribute("object_path"));
		e.cancelBubble = true;

	} else if (ref.id=="objectId[]") {

		e.cancelBubble = true;

	} else {

		var cb = ref.parentNode.getElementsByTagName("input")[0];

		if (!cb) return false;
		
		//get object attributes from our checkbox
		var ot = cb.getAttribute("object_type");
		var ow = cb.getAttribute("open_with");
		var id = cb.value;
		var name = cb.getAttribute("object_name");
		var p = cb.getAttribute("object_path");
		var ext = fileExtension(name);

		if (ot=="collection") 
		{
			browsePath(p);
		}
		else if (ot=="file" || ot=="document" || ot=="url")
		{
			siteViewFile(id);
		} 
		else if (ot=="search") 
		{
			viewSaveSearch(id,name);
		}

	}

}


function showObjectPath(op)
{

	var dp = op;

	var fullpath = "";
	var objpath = "";

	//show the path to the object
	var parr = op.split("/");

	//remove the object itself
	parr.pop();

	var cell = ce("div","searchObjectPath");

	for (var i=1;i<parr.length;i++)
	{

		fullpath += "/" + parr[i];

		//don't go any further, we haven't reached our display ceiling yet
		if (fullpath.length < ceiling.length) continue;

		var link = ce("a","","",parr[i]);
		link.setAttribute("href","javascript:void(0)");
		link.setAttribute("object_path",fullpath);
		
		cell.appendChild(link);
		if (i!=(parr.length-1)) cell.appendChild(ctnode(" -> "));

	}
	
	return cell;

}

function changeView(view) 
{

	curview = view;

	//store the view
	var p = new PROTO();
	p.add("command","docmgr_collection_saveview");
	p.add("account_view",view);
	p.add("path",curpath);
	p.post(DOCMGR_API);

	showBrowseResults();

}

function createBrowseHeader() {

	//create the message list table and header row
	var header = createTable("browseHeader","","","0","0","0");
 	var tbd = ce("tbody");

	var row = ce("tr");       //header row

	//attachment
	var selectcell = ce("td","","browseHeaderSelect");
	var selectimg = ce("img");
	selectimg.setAttribute("title","Select All Objects");
	selectimg.setAttribute("src",theme_path + "/images/icons/checkbox.png");
	setClick(selectimg,"checkAllObjects()");
	selectcell.appendChild(selectimg);  

	//name
	var namecell = ce("td","","browseHeaderName","Name");
	setClick(namecell,"changeSort('name')");
	var namesort = ce("span","","messageNameSort");
	namecell.appendChild(namesort);

	//last modified
	var editedcell = ce("td","","browseHeaderEdited","Edited");
	setClick(editedcell,"changeSort('edit')");
	var editedsort = ce("span","","messageEditedSort");
	editedcell.appendChild(editedsort);

	//size
	var sizecell = ce("td","","browseHeaderSize","Size");
	setClick(sizecell,"changeSort('size')");
	var sizesort = ce("span","","messageSizeSort");
	sizecell.appendChild(sizesort);

	if (browsemode=="search") {
	  var rankcell = ce("td","","browseHeaderRank","Rank");
	  setClick(rankcell,"changeSort('rank')");
	  var ranksort = ce("span","","messageRankSort");
	  rankcell.appendChild(ranksort);
	}

	//options
	var optcell = ce("td","","browseHeaderOptions","Options");
	

	row.appendChild(selectcell);
	row.appendChild(namecell);  
	row.appendChild(sizecell);   
	if (rankcell) row.appendChild(rankcell);
	row.appendChild(editedcell);  
	row.appendChild(optcell);  
	tbd.appendChild(row);
	header.appendChild(tbd);

	content.appendChild(header);

}


/*******************************************************************
	Object option icons
*******************************************************************/

function createOptLink(imgsrc,title,clickval) {

	var img = ce("img");
	img.setAttribute("src",theme_path + "/images/docmgr/icons/" + imgsrc);
	img.setAttribute("title",title);
	setClick(img,clickval);

	return img;

}

function createCommonOptions(entry) {

	var div = ce("div");

	div.appendChild(createOptLink("property.png","Edit Properties","editObjProp('" + entry.id + "','" + entry.object_type + "')"));
	div.appendChild(createOptLink("subscribe.png","Edit Subscription","manageSubscription('" + entry.id + "','" + entry.object_type + "')"));
	

	return div;


}

function collectionOptions(entry) {

	var div = createCommonOptions(entry);
	div.appendChild(createOptLink("bookmark.png","Bookmark Collection","bookmarkCollection('" + entry.id + "',\"" + entry.name + "\")"));
	div.appendChild(createOptLink("zip.png","Zip & Download Collection","zipCollection('" + entry.id + "')"));
	return div;

}

function fileOptions(entry) {

	var div = createCommonOptions(entry);

	if (isData(entry.openoffice)) {
		div.appendChild(createOptLink("convert.png","Convert To Other Format","convertObjectWin(event)"));			
	}

	//convert to pdf link
	var pos = entry.name.lastIndexOf(".");
	var ext = entry.name.substr(pos+1);

	if ((entry.bitmask_text == "admin" || entry.bitmask_text=="edit")) {

		//pdf editing/reordering
		if (entry.locked == "f") {

				if (ext=="pdf") div.appendChild(createOptLink("edit.png","Advanced Edit","advPDFEdit('" + entry.id + "')"));
				div.appendChild(createOptLink("checkout.png","Checkout File","checkout('" + entry.id + "')"));
				div.appendChild(createOptLink("checkin.png","Checkin File","checkinFile('" + entry.id + "')"));

		} else {

			if (isLockOwner(entry)) {
				div.appendChild(createOptLink("checkin.png","Checkin File","checkinFile('" + entry.id + "')"));
			}

		}

	}

	return div;
	
}

function documentOptions(entry) {

	var div = createCommonOptions(entry);

	//div.appendChild(createOptLink("email.png","Email","emailObjects('" + entry.id + "')"));
	div.appendChild(createOptLink("convert.png","Convert To Other Format","convertObjectWin(event)"));

	return div;
	
}

function urlOptions(entry) {

	var div = createCommonOptions(entry);

	return div;
	
}

function searchOptions(entry) {

	var div = createCommonOptions(entry);

	return div;
	
}

/****************************************
	object option functions
****************************************/

function deleteObj(id) 
{

		if (confirm("Are you sure you want to delete this object?")) 
		{

			updateSiteStatus("Deleting Object");

		  //setup the xml
			var p = new PROTO();
			p.add("command","docmgr_object_delete");
			p.add("object_id",id);
			p.post(DOCMGR_API,"writeDeleteObjects");

		}

}

function editObjProp(id,type) 
{

	var mod;
	if (type=="collection") mod = "colprop";
	else if (type=="file") mod = "fileprop";
	else if (type=="search") mod = "searchprop";
	else if (type=="document") mod = "docprop";
	else if (type=="url") mod = "urlprop";

	var url = "index.php?module=" + mod + "&objectId=" + id + "&parentPath=" + curpath;
	location.href = url;

}


/********************************************************************
	FUNCTION: createObjIcon
********************************************************************/
function createObjIcon(entry) {

	//if the trash can, use a special icon
	if (entry.object_path=="/Users/" + USER_LOGIN + "/Trash") var icon = "trash.png";
	else var icon = entry.icon;

	var img = ce("img");
	img.setAttribute("src",theme_path + "/images/docmgr/fileicons/" + icon);
	return img;

}

/********************************************************************
	object creation/editing functions
********************************************************************/
function createFolder() {

	var cont = openSitePopup("400","300");

	var namecell = ce("div","sitePopupCell");
	var namehead = ce("div","formHeader","","Collection Name");
	var nameform = createTextbox("name");
	namecell.appendChild(namehead);
	namecell.appendChild(nameform);

	var desccell = ce("div","sitePopupCell");
	var deschead = ce("div","formHeader","","Description");
	var descform = createTextarea("summary");
	desccell.appendChild(deschead);
	desccell.appendChild(descform);

	var sbtcell = ce("div","sitePopupCell");
	var sbtbutton = createBtn("submitObject","Create Collection","createNewFolder()");
	sbtcell.appendChild(sbtbutton);

	cont.appendChild(namecell);
	cont.appendChild(desccell);
	cont.appendChild(sbtcell);

}

function createNewFolder() {

	updateSiteStatus("Creating new collection");

 //setup the xml
	var p = new PROTO();
	p.add("command","docmgr_object_save");
	p.add("object_type","collection");
	p.add("parent_path",curpath);
	p.add("name",ge("name").value);
	p.add("summary",ge("summary").value);
	p.post(DOCMGR_API,"writeNewFolder");

	closeSitePopup();
 
}

function writeNewFolder(data) {

	//show error if there is one, otherwise refresh current folder
	if (data.error) alert(data.error);
	else 
	{

		browsePath();
  	if (curtree)
  	{
    	curtree.cycleObjectPath(curpath,1);
  	}


	}

}

function openEditor(type) {

	var url = "index.php?module=editor&parentPath=" + curpath;
	if (type=="web") url += "&createMode=web";
	var parms = centerParms(800,600,1) + ",resizable=1";
	popupref = window.open(url,"_editor",parms);

}

function moveObjects() {

	var movearr = getChecked();
	mbmode = "move";

	if (movearr.length==0) {

		alert("You must select which objects you want to move");
		return false;

	} else {

		//store the files we'll move into our global stack
		filestack = movearr;

		openMiniB("open","","collection");

	}

}

function mbSelectObject(res) {

	if (mbmode=="convert")
	{

		//move our objects
		setTimeout("convertObjProcess(\"" + res.path + "\")","10");

	}
	else
	{

		//move our objects
		setTimeout("moveObjProcess(\"" + res.id + "\")","10");

	}
	
}

function moveObjProcess(id) {

	updateSiteStatus("Moving objects");

	//setup the xml
	var p = new PROTO();
	p.add("command","docmgr_object_move");
	p.add("dest_parent_id",id);
	p.add("source_parent_path",curpath);
		
	for (var i=0;i<filestack.length;i++) {
 		p.add("object_id",filestack[i]);
	}

	p.post(DOCMGR_API,"writeMoveObjects");

}

function writeMoveObjects(data) {

	clearSiteStatus();

	//show error if there is one, otherwise refresh
	if (data.error) alert(data.error);
	else browsePath();

}


function deleteObjects() {

	var delarr = getChecked();
	
	if (delarr.length==0) 
	{
		alert("You must select objects to delete");
		return false;
	} 
	else 
	{

		if (confirm("Are you sure you want to delete these objects?")) 
		{

			updateSiteStatus("Deleting Objects");

			var p = new PROTO();
			p.add("command","docmgr_object_delete");

			for (var i=0;i<delarr.length;i++) 
			{
				p.add("object_id",delarr[i]);
			}

			p.post(DOCMGR_API,"writeDeleteObjects");

		}

	}

}

function writeDeleteObjects(data) 
{
	 
	clearSiteStatus();

	//show error if there is one, otherwise refresh
	if (data.error) alert(data.error);
	else browsePath();

}

function checkAllObjects() {

	var arr = content.getElementsByTagName("input");
	var cbarr = new Array();
	var mode;

	for (var i=0;i<arr.length;i++) {
		if (arr[i].type=="checkbox") cbarr.push(arr[i]);
	}

	//get out if nothing there
	if (cbarr.length==0) return false;

	//what's our mode
	if (cbarr[0].checked==true) mode = false;
	else mode = true;	

	for (var i=0;i<cbarr.length;i++) {
		cbarr[i].checked = mode;
	}

}


function createURL() {

	var cont = openSitePopup("400","300");

	var namecell = ce("div","sitePopupCell");
	var namehead = ce("div","formHeader","","Site Name");
	var nameform = createTextbox("name");
	namecell.appendChild(namehead);
	namecell.appendChild(nameform);

	var urlcell = ce("div","sitePopupCell");
	var urlhead = ce("div","formHeader","","Web Address (URL)");
	var urlform = createTextbox("url");
	urlcell.appendChild(urlhead);
	urlcell.appendChild(urlform);

	var desccell = ce("div","sitePopupCell");
	var deschead = ce("div","formHeader","","Description");
	var descform = createTextarea("summary");
	desccell.appendChild(deschead);
	desccell.appendChild(descform);

	var sbtcell = ce("div","sitePopupCell");
	var sbtbutton = createBtn("submitObject","Create URL","createNewURL()");
	sbtcell.appendChild(sbtbutton);

	cont.appendChild(namecell);
	cont.appendChild(urlcell);
	cont.appendChild(desccell);
	cont.appendChild(sbtcell);

}

function createNewURL() 
{

	var p = new PROTO();
 	p.add("command","docmgr_object_save");
	p.add("parent_path",curpath);
	p.add("name",ge("name").value);
	p.add("url",ge("url").value);
	p.add("object_type","url");
	p.add("summary",ge("summary").value);
	p.post(DOCMGR_API,"writeNewURL");

	closeSitePopup();
 
}

function writeNewURL(data) 
{

	//show error if there is one, otherwise refresh current folder
	if (data.error) alert(data.error);
	else browsePath();

}

function changeSort(newsort) 
{

	if (newsort==sort_field) 
	{
		if (sort_dir=="ASC") sort_dir = "DESC";
		else sort_dir = "ASC";
	} else 
	{
		sort_dir = "ASC";
	}

	sort_field = newsort;

	if (browsemode=="search") objectSearch("",1);
	else browsePath();

}

function checkout(id) 
{

	//download like normal
	var p = new PROTO();
	p.add("command","docmgr_file_get");
	p.add("lock","1");
	p.add("object_id",id);
	p.redirect(DOCMGR_API);

}

function createBrowseThumb(entry) {

	if (curpath=="/") var path = curpath + entry.name;
	else var path = curpath + "/" + entry.name;

	var cont = ce("div","browseThumb");
	var ts = new Date().getTime();

	if (entry.object_type=="file" || entry.object_type=="document") {
		var url = DOCMGR_URL + "app/showthumb.php?sessionId=" + SESSION_ID + "&objectId=" + entry.id + "&objDir=" + entry.object_directory + "&timestamp=" + ts;
	} else if (entry.object_path=="/Users/" + USER_LOGIN + "/Trash") {
		var url = theme_path + "/images/thumbnails/trash.png";
	} else if (entry.object_type=="collection") {
		var url = theme_path + "/images/thumbnails/folder.png";
	} else if (entry.object_type=="search") {
		var url = theme_path + "/images/thumbnails/search_folder.png";
	} else if (entry.object_type=="url") {
		var url = theme_path + "/images/thumbnails/url.png";
	}

	var thumbcont = ce("div");

	//setup the thumbnail image	
	var img = ce("img");
	img.setAttribute("src",url);
	thumbcont.appendChild(img);

	var n = ce("div","","",entry.name);
	thumbcont.appendChild(n);

	//store some object info on the checkbox
	var cb = createCheckbox("objectId[]",entry.id);
	cb.setAttribute("object_path",path);
	cb.setAttribute("object_type",entry.object_type);
	cb.setAttribute("object_name",entry.name);
	if (isData(entry.extension)) cb.setAttribute("extension",entry.extension);

	thumbcont.appendChild(cb);

	//handle the person clicking on the thumbnail
	setClick(thumbcont,"handleResultClick(event)");
	//setClick(n,"handleResultClick(event)");

	cont.appendChild(thumbcont);

	//run if available
	var func = eval(entry.object_type + "Options");
	if (entry.bitmask_text=="admin") {
		if (window.func) {
			var opts = func(entry);
			cont.appendChild(opts);
		}
	}

	return cont;

}

function viewSaveSearch(id,name)
{

  browsemode = "search";
	searchinit = "";
	clearElement(ge("searchFilters"));
  searchView(1);

  sort_field="rank";
  sort_dir="DESC";  

  searchLimit = RESULTS_PER_PAGE;
  searchOffset=0;
  searchTotal=0; 
  searchPage = 1;
  reset = 1;

  updateSiteStatus("Running search");

	var p = new PROTO();
  p.add("command","docmgr_search_get");
  p.add("object_id",id);
	p.post(DOCMGR_API,"writeSearchGet");

}

function writeSearchGet(data)
{

	if (data.error) alert(data.error);
	else
	{

		formloading = true;

  	var jsdata = JSON.decode(data.search_params);
		popSearchFilters(jsdata);
		formloading = false;

		if (!jsdata.path) jsdata.path = "/";

		//update the ceiling 
		curpath = jsdata.path;
		setCeiling(curpath);
		breadcrumbs();

		objectSearch();

		//special case.  wait until everything is done then tack our name onto the breadcrumb trail
		endReq("showSearchName(\"" + data.search_name + "\")");

	}

}

function showSearchName(name)
{

	var ref = ge("siteNavHistory");

	//remove the cleaner
	var arr = ref.getElementsByTagName("div");

	if (arr.length > 1)
	{
		ref.removeChild(arr[arr.length-1]);
	}
	
	var imgdiv = ce("div","siteNavHistoryDiv");
	var img = createImg(theme_path + "/images/navarrow.gif");
	img.style.marginBottom = "-1px";
	img.style.marginLeft = "5px";
	img.style.marginRight = "5px";

	imgdiv.appendChild(img);
	ref.appendChild(imgdiv);

	ref.appendChild(ce("div","siteNavHistoryDiv","",name));

	ref.appendChild(createCleaner());

}

function bookmarkCollection(id,name) 
{

	var txt = prompt("Please enter a name for the bookmark",name);

	if (txt && txt.length>0) 
	{

		//setup the xml
		var p = new PROTO();
		p.add("command","docmgr_bookmark_save");
		p.add("object_id",id);
		p.add("name",txt);
		p.post(DOCMGR_API,"writeNewBookmark");

	}

}

function writeNewBookmark(data) {

	if (data.error) alert(data.error);
	else 
	{

		//reload bookmarks
		loadBookmarks();

	}

}

function zipCollection(id)
{

		//setup the xml
		var p = new PROTO();
		p.add("command","docmgr_collection_zip");
		p.add("object_id",id);
		p.redirect(DOCMGR_API);

}

function emailObjects() {

	var f = new Array("file","document");
	var emailarr = getChecked(f);

	if (emailarr.length==0) 
	{

		alert("You must select documents or files to email");
		return false;

	} else {

		var url = "index.php?module=createemail&docmgrAttachments=" + emailarr.join(",");
		location.href = url;

	}

}

function createViewLink()
{

	var f = new Array("file","document");
	var viewarr = getChecked(f);

	if (viewarr.length==0) 
	{

		alert("You must select documents or files to create a link for");
		return false;

	} else if (viewarr.length>1) {

		alert("You may only select one file or document to create a link for");
		return false;

	} else {

		var popupref = openSitePopup(350,150);
		popupref.appendChild(ce("div","sitePopupHeader","","View Link Options"));

		var cell = ce("div","sitePopupCell");
		cell.appendChild(ce("div","formHeader","","Link will be valid for"));

		//time it's valid for
		var sel = createSelect("expire");
		setChange(sel,"genViewLink('" + viewarr[0] + "')");
		sel[0] = new Option("Choose expiration length","0");
		sel[1] = new Option("24 Hours","24");
		sel[2] = new Option("48 Hours","48");
		sel[3] = new Option("1 Week","168");

		cell.appendChild(sel);
		popupref.appendChild(cell);

		//results div
		var cell = ce("div","sitePopupCell","objViewLink");
		popupref.appendChild(cell);

	}

}

function genViewLink(id)
{

	updateSiteStatus("Generating link for object viewing");

	var ex = ge("expire").value;

	if (ex!="0")
	{

		//setup the xml
		var p = new PROTO();
		p.add("command","docmgr_object_createlink");
		p.add("object_id",id);
		p.add("expire",ex);
		p.post(DOCMGR_API,"writeViewLink");
	
	}	

}


function writeViewLink(data)
{

	clearSiteStatus();

	if (data.error) alert(data.error);
	else 
	{

		var ref = ge("objViewLink");
		clearElement(ref);

		//header
		ref.appendChild(ce("div","formHeader","","Object Link"));

		//readonly textbox
		var tb = createTextbox("objLink",data.object_link);				
		tb.setAttribute("size","40");
		tb.readOnly = true;
		ref.appendChild(tb);

		//embed button
		var btn = createBtn("embedLinkBtn","Embed link in email","embedLink()");		
		ref.appendChild(btn);

	}


}

function embedLink() 
{

	var dest = ge("objLink").value;
	var link = "<a href=\"" + dest + "\">" + dest + "</a>";

	location.href = "index.php?module=createemail&editor_content=" + link;

}

function getChecked(filterarr,returnref)
{

	var cbarr = new Array();

	var arr = content.getElementsByTagName("input");

	for (var i=0;i<arr.length;i++) {

		if (arr[i].name=="objectId[]" && arr[i].checked==true) 
		{

			//passed an object type filter.  only allow these types of objects into the box
			if (filterarr)
			{
				var key = arraySearch(arr[i].getAttribute("object_type"),filterarr);
				if (key==-1) continue;
			}

			//if passed, return the reference to the checkbox parent row, which has all desired info
			if (returnref) 
				cbarr.push(arr[i].parentNode.parentNode);
			else
				cbarr.push(arr[i].value);

		}

	}

	return cbarr;

}

function createPropLink()
{

	var viewarr = new Array();

	var arr = content.getElementsByTagName("input");

	for (var i=0;i<arr.length;i++) 
	{

		if (arr[i].name=="objectId[]" && arr[i].checked==true) 
		{
			viewarr.push(arr[i]);
		}

	}

	if (viewarr.length==0) 
	{

		alert("You must select documents or files to create a link for");
		return false;

	} else {

		var str = "";

		for (var i=0;i<viewarr.length;i++)
		{

			//figure out our prop mod
			var ot = viewarr[i].getAttribute("object_type");
			var on = viewarr[i].getAttribute("object_name");
			var oid = viewarr[i].value;

			if (ot=="document") var mod = "docprop";
			else if (ot=="collection") var mod = "colprop";
			else if (ot=="url") var mod = "urlprop";
			else if (ot=="search") var mod = "searchprop";
			else var mod = "fileprop";

			var link = SITE_URL + "index.php?module=" + mod + "&objectId=" + oid;

			str += "<div><a href=\"" + link + "\">Click To View \"" + on + "\"</div>";			

		}

		location.href = "index.php?module=createemail&editor_content=" + encodeURIComponent(str);

	}

}

function trashObjects() {

	var delarr = getChecked('',1);
	
	if (delarr.length==0) 
	{
		alert("You must select objects to delete");
		return false;
	} else {

		//do some checking for permissions and whatnot
		for (var i=0;i<delarr.length;i++)
		{

			//we we don't have permissions to delete a file, bail
			if (delarr[i].getAttribute("object_perm")!="admin" && delarr[i].getAttribute("object_share")!="t")
			{
				alert("You do not have permission to delete " + delarr[i].getAttribute("object_name"));
				return false;
			}

			//warn about shared files
			if (delarr[i].getAttribute("object_share")=="t")
			{

				var n = delarr[i].getAttribute("object_name");

				if (!confirm(n + " is an object shared with you.  If you continue, only the share will be removed.  Do you wish to continue?"))
				{
					return false;
				}

			}

		}

		if (confirm("Are you sure you want to move these objects to Trash?")) 
		{

			updateSiteStatus("Moving objects to trash");

			var p = new PROTO();
			p.add("command","docmgr_object_trash");

			for (var i=0;i<delarr.length;i++) 
			{
				p.add("object_id",delarr[i].getAttribute("object_id"));
			}

			p.post(DOCMGR_API,"writeDeleteObjects");

		}

	}

}

function emptyTrash() 
{

		if (confirm("Are you sure you want to permanently remove all items in the Trash?")) 
		{

			updateSiteStatus("Empting Trash");

		  //setup the xml
			var p = new PROTO();
			p.add("command","docmgr_object_emptytrash");
			p.post(DOCMGR_API,"writeEmptyTrash");

		}

}

function writeEmptyTrash(data)
{

	if (data.error) alert(data.error);
	else
	{

		tempSiteStatus("Trash emptied successfully");

		//if in the trash, reload the page
		if (curpath=="/Users/" + USER_LOGIN + "/Trash" || curpath=="/Users/" + USER_LOGIN) browsePath();

	}	

}

function isLockOwner(objinfo)
{

	var ret = false;

	for (var i=0;i<objinfo.lock_owner.length;i++)
	{

		if (objinfo.lock_owner[i]==USER_ID)
		{
			ret = true;
			break;
		}

	}

	return ret;

}


function createWorkflow()
{

	var wfarr = getChecked();

	if (wfarr.length==0) {

		alert("You must select which object you wish to create a workflow for");
		return false;

	} 
	else if (wfarr.length > 1)
	{
		alert("You may only create a workflow for one object at a time");
		return false;
	} 
	else 
	{

		var url = "index.php?module=workflow&action=newWorkflow&objectId=" + wfarr[0];
		location.href = url;

	}

}

function showSharedObjects()
{

  searchref = ge("searchFilters");

  if (searchref.style.display!="block")
  {
		cycleSearchView();
	}

	endReq("runShowSharedObjects()");

}

function runShowSharedObjects()
{

	ge("share_filter").value = "t";
	objectSearch();

}

