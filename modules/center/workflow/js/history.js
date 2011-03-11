
var workflowhist;
var wflist;
var curobject;
	
function loadHistoryPage()
{

  clearElement(ge("toolbarBtns"));
  clearElement(ge("toolbarTitle"));
 
	setupHistoryPage();
	getHistory();

	if (workflowhist) endReq("viewHistory('" + workflowhist.id + "')");

}

function setupHistoryPage()
{

	clearElement(content);

	var divider = ce("div","toolbar","workflowDivider");
	divider.appendChild(ce("div","toolbarBtns","dividerBtns"));
	divider.appendChild(ce("div","toolbarTitle","dividerTitle"));
	
	content.appendChild(ce("div","","histListDiv"));
	content.appendChild(divider);
	content.appendChild(ce("div","","histDataDiv"));

	loadHistoryToolbar();

	setSizes();

}

function loadHistoryToolbar() {

	var tbBtns = ge("toolbarBtns");
	var tbTitle = ge("toolbarTitle");
	var divBtns = ge("dividerBtns");
	var divTitle = ge("dividerTitle");

  clearElement(tbBtns);
  clearElement(tbTitle);
  clearElement(divBtns);
  clearElement(divTitle);

	tbTitle.appendChild(ctnode("Workflow History Viewer"));

  if (workflowhist && workflowhist.account_id==USER_ID) 
	{

  	divBtns.appendChild(siteToolbarCell("Delete","deleteHistory()","delete.png"));

		divTitle.appendChild(ctnode(workflowhist.object_name));

  }
	
} 

function getHistory()
{

	var p = new PROTO();
	p.add("command","docmgr_workflow_getlist");
	p.add("filter","history");
	p.post(DOCMGR_API,"writeHistory");

}

function writeHistory(data)
{
	
	wflist = data;

	clearSiteStatus();

	var tl = ge("histListDiv");
	clearElement(tl);

	if (wflist.error) alert(wflist.error);
	else if (!wflist.workflow)
	{
		tl.appendChild(ce("div","errorMessage","","No outstanding tasks found"));
	}
	else
	{

		var tbl = createTable("workflowTable");
		var thead = ce("thead");
		var tbody = ce("tbody","","workflowTableBody");

		tbl.appendChild(thead);
		tbl.appendChild(tbody);

		//header row
		var row = ce("tr");
		row.appendChild(ce("th","workflowHeaderCell","","File"));
		row.appendChild(ce("th","workflowHeaderCell","","Created"));
		row.appendChild(ce("th","workflowHeaderCell","","Created By"));
		row.appendChild(ce("th","workflowHeaderCell","","Status"));
		thead.appendChild(row);

		for (var i=0;i<wflist.workflow.length;i++)
		{

			var wf = wflist.workflow[i];

			var row = ce("tr","workflowListRow");
			row.setAttribute("workflow_id",wf.id);
			row.appendChild(ce("td","workflowListCell","",wf.object_path));
			row.appendChild(ce("td","workflowListCellCenter","",wf.date_create_view));
			row.appendChild(ce("td","workflowListCellCenter","",wf.account_name));
			row.appendChild(ce("td","workflowListCellCenter","",wf.status_view));

			var arr = row.getElementsByTagName("td");
			for (var c=0;c<arr.length;c++) setClick(arr[c],"viewHistory('" + wf.id + "')");

			tbody.appendChild(row);

		}

		tl.appendChild(tbl);

	}	

}


function viewHistory(id)
{

  var arr = ge("workflowTableBody").getElementsByTagName("tr");

  for (var i=0;i<arr.length;i++)
  {
    if (arr[i].getAttribute("workflow_id")==id) setClass(arr[i],"workflowListRowSelected");
    else setClass(arr[i],"workflowListRow");
  }

	updateSiteStatus("Fetching workflow information");
	var p = new PROTO();
	p.add("command","docmgr_workflow_getinfo");
	p.add("workflow_id",id);
	p.post(DOCMGR_API,"writeViewHistory");

}

function writeViewHistory(data)
{

	clearSiteStatus();

	if (data.error) alert(data.error);
	else
	{

		workflowhist = data.workflow[0];
		curobject = workflowhist.object_id;

		loadHistoryToolbar();

		setSizes();

		var dd = ge("histDataDiv");
		clearElement(dd);

		if (workflowhist.recipient)
		{

			var tbl = createTable("recipientTable");
			var thead = ce("thead");
			var tbody = ce("tbody");
	
			tbl.appendChild(thead);
			tbl.appendChild(tbody);
	
			//header row
			var row = ce("tr");
			row.appendChild(ce("th","recipientHeaderCell","","Recipient"));
			row.appendChild(ce("th","recipientHeaderCell","","Status"));
			row.appendChild(ce("th","recipientHeaderCell","","Due"));
			row.appendChild(ce("th","recipientHeaderCell","","Stage"));
			row.appendChild(ce("th","recipientHeaderCell","","Comments"));
			thead.appendChild(row);
	
			for (var i=0;i<workflowhist.recipient.length;i++)
			{
	
				var wf = workflowhist.recipient[i];
	
				var row = ce("tr","recipientListRow");
				row.appendChild(ce("td","recipientName","",wf.account_name));
				row.appendChild(ce("td","recipientStatus","",wf.status_view));
				row.appendChild(ce("td","recipientDue","",wf.date_due_view));
				row.appendChild(ce("td","recipientStage","",parseInt(wf.sort_order)+1));
				row.appendChild(ce("td","recipientComment","",wf.comment));
	
				tbody.appendChild(row);
	
			}
	
			dd.appendChild(tbl);
	
		//none to display
		} else dd.appendChild(ce("div","errorMessage","","No Recipients Found"));

	}

}

function createRecipCell(title,data)
{

	//info
	var sub = ce("div");
	sub.appendChild(ce("div","recipRowHeader","",title));
	sub.appendChild(ce("div","recipRowInfo","",data));
	sub.appendChild(createCleaner());
	return sub;

}


function createHistoryCell(title,data)
{

	var cell = ce("div","taskCell");	
	cell.appendChild(ce("div","formHeader","",title));
	cell.appendChild(ce("div","taskCellData","",data));

	return cell;

}


function deleteHistory() {

	if (confirm("Are you sure you want to delete this workflow?"))
	{

	  updateSiteStatus("Deleting workflow");

	  var p = new PROTO();
	  p.add("command","docmgr_workflow_delete");
	  p.add("object_id",curobject);
	  p.add("workflow_id",workflowhist.id);	
	  p.post(DOCMGR_API,"writeDeleteHistory");

	}

}

function writeDeleteHistory(data) {

  if (data.error) alert(data.error);
  else
  {
		curtask = "";
		workflowhist = "";

    loadHistoryPage();
  }

}

