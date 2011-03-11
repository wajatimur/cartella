/***********************************************
	file for editing object properties
***********************************************/

var histcont;

function loadFileHistory() {

	if (!object) return false;

	updateSiteStatus("Loading history");

	clearElement(content);

	loadHistoryToolbar();
	runFileHistory();

}

function loadHistoryToolbar()
{

	clearElement(tbBtns);
	clearElement(tbTitle);

	tbTitle.appendChild(ctnode("File Revision History"));

	tbBtns.appendChild(siteToolbarCell("Promote To Latest","promote()","checkin.png"));
	tbBtns.appendChild(siteToolbarCell("Delete","deleteFile()","delete.png"));

}

function runFileHistory() {

	var p = new PROTO();
	p.add("command","docmgr_file_gethistory");
	p.add("object_id",object);
	p.post(DOCMGR_API,"writeFileHistory");

}

function writeFileHistory(data) {

	clearElement(content);

	clearSiteStatus();
	 
	if (data.error) alert(data.error);
	else if (!data.history) content.appendChild(ce("div","errorMessage","","No entries found to display"));
	else {

		histcont = createTable("historyList");
		content.appendChild(histcont);

		var head = ce("thead");
		var hrow = ce("tr");
		hrow.appendChild(ce("th","objCheckbox","",createNbsp()));
		hrow.appendChild(ce("th","objVersion","","Version"));
		hrow.appendChild(ce("th","objVersion","","Custom Version"));
		hrow.appendChild(ce("th","objModOn","","Modified On"));
		hrow.appendChild(ce("th","objModBy","","Modified By"));
		hrow.appendChild(ce("th","objSize","","Size"));
		hrow.appendChild(ce("th","objNotes","","Notes"));

		head.appendChild(hrow);
		histcont.appendChild(head);

		var bd = ce("tbody");
		histcont.appendChild(bd);

		for (var i=0; i<data.history.length;i++) {

			bd.appendChild(createHistoryRow(data.history[i]));

		}

	}

}

function createHistoryRow(entry) {

	var row = ce("tr","historyRow");
	row.setAttribute("file_id",entry.id);

	if (!isData(entry.notes)) entry.notes = "Not entered";

	row.appendChild(ce("td","objCheckbox","",createCheckbox("fileId[]",entry.id)));
	row.appendChild(ce("td","objVersion","",entry.version));
	row.appendChild(ce("td","objVersion","",entry.custom_version));
	row.appendChild(ce("td","objModOn","",entry.view_modified_date));
	row.appendChild(ce("td","objModBy","",entry.view_modified_by));
	row.appendChild(ce("td","objSize","",entry.size));
	row.appendChild(ce("td","objNotes","",entry.notes));

 	var arr = row.getElementsByTagName("td");
  for (var i=0;i<arr.length;i++) setClick(arr[i],"viewRevision(event)");


	return row;

}

function writeFileAction(data) {

	clearSiteStatus();
	 
	if (data.error) alert(data.error);
	else runFileHistory();

}

function viewRevision(e)
{

  var src = getEventSrc(e);

  if (src.type=="checkbox")
  {
    cancelBubble = true;
    return false;
  }

  var id = src.parentNode.getAttribute("file_id");

	var p = new PROTO();
	p.add("command","docmgr_file_get");
	p.add("object_id",object);
	p.add("file_id",id);
	p.redirect(DOCMGR_API);

}

function promote(id) {

	var arr = getChecked();

	if (arr.length==0)
	{
		alert("You must select which revisions you want to promote");
		return false;
	}
	else if (arr.length>1)
	{
		alert("You may only select one file to promote");
		return false;
	}

	updateSiteStatus("Promoting file");

	var p = new PROTO();
	p.add("command","docmgr_file_promote");
	p.add("object_id",object);
	p.add("file_id",arr[0]);
	p.post(DOCMGR_API,"writeFileAction");

}

function deleteFile() 
{

	var arr = getChecked();

	if (arr.length==0)
	{
		alert("You must select which revisions you want to remove");
		return false;
	}

	if (confirm("Are you sure you want to remove this file revision?")) {

		updateSiteStatus("Removing file version");

		var p = new PROTO();
		p.add("command","docmgr_file_removerevision");
		p.add("object_id",object);
		p.add("file_id",arr);
		p.post(DOCMGR_API,"writeFileAction");

	}

}

function getChecked()
{

	var arr = ge("historyList").getElementsByTagName("input");
	var res = new Array();

	for (var i=0;i<arr.length;i++)
	{

		if (arr[i].type=="checkbox" && arr[i].checked==true) res.push(arr[i].value);

	}

	return res;

}

