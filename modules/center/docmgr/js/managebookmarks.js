
var winref;
var bklist;
var bkcont;
var savetimer;

/****************************************************************
  FUNCTION: manageBKendar
  PURPOSE:  main function for loading our edittask page.
  INPUT:    none
*****************************************************************/
function manageBookmarks() {

	winref = openSitePopup(430,380,"closeManageBK()");
	bklist = ce("div","bkList");

	//create the header
	var header = ce("div","bkHeader");
	header.appendChild(ce("div","bkTitle","","Name"));
	header.appendChild(ce("div","bkExpand","","Expandable"));
	header.appendChild(ce("div","bkDelete","","Delete"));

	//create toolbar
	winref.appendChild(ce("div","sitePopupHeader","","Manage Bookmarks"));
	winref.appendChild(header);
	winref.appendChild(createCleaner());
	winref.appendChild(bklist);

	//get a list fo the bkendars
	loadBKList();

}


function loadBKList(data) 
{

	//passed something (usually from a delete or add)
	if (data) {

		clearSiteStatus();
		if (data.error) alert(data.error);

	}

	var p = new PROTO();
	p.add("command","docmgr_bookmark_get");
	p.post(DOCMGR_API,"writeBKList");

}

function writeBKList(data) 
{

	clearElement(bklist);
	clearSiteStatus();

	if (data.error) alert(data.error);
	else if (!data.bookmark) 
	{
		bklist.innerHTML = "<div class=\"errorMessage\">No bookmarks found to display</div>";
	} else 
	{

		var passBK = "";

		if (data.bookmark) passBK = data.bookmark;
		
		for (var i=0;i<data.bookmark.length;i++) 
		{
			if (data.bookmark[i].id=="0") continue;				//skip the home directory
			bklist.appendChild(createBKEntry(data.bookmark[i]));
			bklist.appendChild(createCleaner());
		}

		//focus on the new textbox if passed
		if (data.bookmark) {

			var arr = bklist.getElementsByTagName("input");
			for (var i=0;i<arr.length;i++) {
				if (arr[i].getAttribute("object_id")==data.bookmark) {
					arr[i].focus();
					arr[i].select();
					break;
				}
			}
		}
	}

}

function createBKEntry(bk) {

	var row = ce("div");
	row.setAttribute("bkId",bk.id);
	var cbdiv;

	var bdiv = ce("div","bkBullet");
	var bullet = ce("img");
	bullet.setAttribute("src",theme_path + "/images/taskbullet.gif");
	bdiv.appendChild(bullet);

	//append the bk name
	var txtdiv = ce("div","bkTitle");
	var txtbox = createTextbox("bkName[]",bk.name);
	setClass(txtbox,"bkName");

	//store the contactid in an attribute for later
	txtbox.setAttribute("title","Click to edit bookmark name");
	setKeyUp(txtbox,"ajaxSaveBK('" + bk.id + "')");

	txtdiv.appendChild(txtbox);

	//expandable
	var exdiv = ce("div","bkExpand");
	var cb = createCheckbox("expandable[]",bk.id);
	setClick(cb,"saveBK('" + bk.id + "')");
	exdiv.appendChild(cb);
	if (bk.expandable=="t") cb.checked = true;

	var imgdiv = ce("div","bkDelete");
	if (bk.protected!="t") {
		var img = ce("img");
		img.setAttribute("src",theme_path + "/images/icons/delete.png");
		setClick(img,"deleteBK('" + bk.id + "')");
		setClass(img,"bkImage");
		imgdiv.appendChild(img);
	}

	//put it all together
	row.appendChild(bdiv);
	row.appendChild(txtdiv);
	row.appendChild(exdiv);
	row.appendChild(imgdiv);

	return row;

}

function writeBKAction(data) {

	//throw an error, otherwise reload our bookmark list
	if (data.error) alert(data.error);

}

function ajaxSaveBK(id) {

  //reset the timer
  clearTimeout(savetimer);
	
  savetimer = setTimeout("saveBK('" + id + "')",250);

}

//save the updated bk
function saveBK(id) {

	//get all input forms
	var arr = bklist.getElementsByTagName("div");

	for (var i=0;i<arr.length;i++) {

		//skip if it's not a main row
		if (!arr[i].hasAttribute("bkId")) continue;

		//if not the row we're working on, stop
		if (arr[i].getAttribute("bkId")!=id) continue;

		var inputs = arr[i].getElementsByTagName("input");

		var p = new PROTO();
 		p.add("command","docmgr_bookmark_save");
		p.add("bookmark_id",id);
		p.add("name",inputs[0].value);
		p.add("expand",inputs[1].checked);
		p.post(DOCMGR_API,"writeBKAction");

		break;

	}

}

//delete a bk
function deleteBK(id) 
{

	if (confirm("Are you sure you want to delete this bookmark?")) 
	{

 	 	//setup the xml
		var p = new PROTO();
 		p.add("command","docmgr_bookmark_delete");
		p.add("bookmark_id",id);
		p.post(DOCMGR_API,"writeBKDelete");

	}

}

function writeBKDelete(data) 
{

	if (data.error) alert(data.error);
	else 
	{

		loadBKList();
		loadBookmarks();

	}

}

function closeManageBK() 
{
	loadBookmarks();
	closeSitePopup();
}
