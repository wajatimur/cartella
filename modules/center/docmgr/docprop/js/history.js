/***********************************************
	file for editing object properties
***********************************************/

var histcont;

function loadDocHistory() {

	if (!object) return false;

	updateSiteStatus("Loading history");

	clearElement(content);
	loadHistoryToolbar();

	runDocHistory();

}

function loadHistoryToolbar()
{

	clearElement(tbBtns);
	clearElement(tbTitle);

  tbTitle.appendChild(ctnode("Document Revision History"));

  tbBtns.appendChild(siteToolbarCell("Promote To Latest","promote()","checkin.png"));
  tbBtns.appendChild(siteToolbarCell("Delete","deleteDoc()","delete.png"));

}

function runDocHistory() {

	var p = new PROTO();
	p.add("command","docmgr_document_gethistory");
	p.add("object_id",object);
	p.post(DOCMGR_API,"writeDocHistory");

}

function writeDocHistory(data) {

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
	row.setAttribute("document_id",entry.id);

	if (!isData(entry.notes)) entry.notes = "Not entered";

	row.appendChild(ce("td","objCheckbox","",createCheckbox("docId[]",entry.id)));
	row.appendChild(ce("td","objVersion","",entry.version));
	row.appendChild(ce("td","objModOn","",entry.view_modified_date));
	row.appendChild(ce("td","objModBy","",entry.view_modified_by));
	row.appendChild(ce("td","objSize","",entry.size));
	row.appendChild(ce("td","objNotes","",entry.notes));

	var arr = row.getElementsByTagName("td");
	for (var i=0;i<arr.length;i++) setClick(arr[i],"viewRevision(event)");

	return row;

}

function writeDocAction(data) {

	clearSiteStatus();
	 
	if (data.error) alert(data.error);
	else runDocHistory();

}

function viewRevision(e) {

	var src = getEventSrc(e);

	if (src.type=="checkbox") 
	{
		cancelBubble = true;
		return false;
	}
	
	var id = src.parentNode.getAttribute("document_id");

	updateSiteStatus("Loading preview");
	var p = new PROTO();
	p.add("command","docmgr_document_get");
	p.add("object_id",object);
	p.add("document_id",id);
	p.post(DOCMGR_API,"writeViewRevision");

}

function writeViewRevision(data) {

	clearSiteStatus();
	 
	if (data.error) alert(data.error);
	else {

		var ref = openSitePopup(800,600);
		var cont = ce("div","","viewDoc");
		if (data.content) cont.innerHTML = data.content;
		ref.appendChild(cont);

	}

}

function promote() 
{

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

	updateSiteStatus("Promoting document");
	var p = new PROTO();
	p.add("command","docmgr_document_promote");
	p.add("object_id",object);
	p.add("document_id",arr[0]);
	p.post(DOCMGR_API,"writeDocAction");

}

function deleteDoc() 
{

  var arr = getChecked();
 
  if (arr.length==0)
  {
    alert("You must select which revisions you want to remove");
    return false;   
  }

	if (confirm("Are you sure you want to remove this document revision?")) 
	{

		updateSiteStatus("Removing document version");
		var p = new PROTO();
		p.add("command","docmgr_document_removerevision");
		p.add("object_id",object);
		p.add("document_id",arr);
		p.post(DOCMGR_API,"writeDocAction");

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
