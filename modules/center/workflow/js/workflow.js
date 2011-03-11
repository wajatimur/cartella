
var workflow;
var wflist;
var curobject;
	
function loadManagePage()
{

  clearElement(ge("toolbarBtns"));
  clearElement(ge("toolbarTitle"));
 
	setupWFPage();
	getWF();

	if (workflow) endReq("viewWF('" + workflow.id + "')");

}


function setupWFPage()
{


	clearElement(content);

	var divider = ce("div","toolbar","workflowDivider");
	divider.appendChild(ce("div","toolbarBtns","dividerBtns"));
	divider.appendChild(ce("div","toolbarTitle","dividerTitle"));

	content.appendChild(ce("div","","wfListDiv"));
	content.appendChild(divider);
	content.appendChild(ce("div","","wfDataDiv"));

	loadWFToolbar();

	setSizes();

}

function loadWFToolbar() {

	var tbBtns = ge("toolbarBtns");
	var tbTitle = ge("toolbarTitle");
	var divBtns = ge("dividerBtns");
	var divTitle = ge("dividerTitle");

  clearElement(tbBtns);
  clearElement(tbTitle);
  clearElement(divBtns);
  clearElement(divTitle);

	tbTitle.appendChild(ctnode("Active Workflow Manager"));

  tbBtns.appendChild(siteToolbarCell("New Workflow","newWorkflow()","new.png"));
 
  if (workflow && workflow.account_id==USER_ID) 
	{

  	if (workflow.status=="nodist") divBtns.appendChild(siteToolbarCell("Edit Recipients","editRecipient()","edit.png"));

  	divBtns.appendChild(siteToolbarCell("Edit Options","editOptions()","edit.png"));

    if (workflow.status=="nodist") {
      divBtns.appendChild(siteToolbarCell("Begin Workflow","beginWorkflow()","save.png"));
    } else if (workflow.status=="pending") {
      divBtns.appendChild(siteToolbarCell("Force Complete","forceComplete()","delete.png"));
    }

  	divBtns.appendChild(siteToolbarCell("Delete","deleteWorkflow()","delete.png"));

		divTitle.appendChild(ctnode(workflow.object_name));

  }

} 

function getWF()
{

	updateSiteStatus("Fetching workflows");

	var p = new PROTO();
	p.add("command","docmgr_workflow_getlist");
	p.add("filter","current");

	p.post(DOCMGR_API,"writeWF");

}

function writeWF(data)
{
	
	wflist = data;

	clearSiteStatus();

	var tl = ge("wfListDiv");
	clearElement(tl);

	if (wflist.error) alert(wflist.error);
	else if (!wflist.workflow)
	{
		tl.appendChild(ce("div","errorMessage","","No workflows were found"));
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
			for (var c=0;c<arr.length;c++) setClick(arr[c],"viewWF('" + wf.id + "')");

			tbody.appendChild(row);

		}

		tl.appendChild(tbl);

	}	

}


function viewWF(id)
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
	p.post(DOCMGR_API,"writeViewWF");

}

function writeViewWF(data)
{

	clearSiteStatus();

	if (data.error) alert(data.error);
	else
	{

		workflow = data.workflow[0];
		curobject = workflow.object_id;
		
		setSizes();
		loadWFToolbar();

		var dd = ge("wfDataDiv");
		clearElement(dd);

		if (workflow.recipient)
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
	
			for (var i=0;i<workflow.recipient.length;i++)
			{
	
				var wf = workflow.recipient[i];
	
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


function newWorkflow() {

	mbmode = "new";

	//launch our selector to pick where to save the file
  var parms = centerParms(600,450,1) + ",resizable=no,scrollbars=no";

  var url = "index.php?module=minib&mode=open";
  var ref = window.open(url,"_minib",parms);
  ref.focus();

}

function mbSelectObject(res)
{

	  updateSiteStatus("Creating new workflow");
	
	  var p = new PROTO();
	  p.add("command","docmgr_workflow_create");
	  p.add("object_id",res.id);
	
		//mbSelectObject is called in a timeout, so we can do this
		p.setAsync(false);
	
	  var data = p.post(DOCMGR_API);
	
		if (data.error) alert(data.error);
		else 
		{
	
			workflow = "";
	
			loadManagePage();
			endReq("viewWF('" + data.workflow_id + "')");
	
		}

}

function beginWorkflow() {

  updateSiteStatus("Creating new workflow");

  var p = new PROTO();
  p.add("command","docmgr_workflow_begin");
  p.add("object_id",curobject);
  p.add("workflow_id",workflow.id);
  p.post(DOCMGR_API,"writeBeginWorkflow");

}
 
function writeBeginWorkflow(data) {

  if (data.error) alert(data.error);
  else 
  {    
    viewWF(workflow.id);
  }
   
}  
   
function forceComplete() {

	if (confirm("Are you sure you want to force this workflow complete?"))
	{

	  updateSiteStatus("Forcing workflow complete");

	  var p = new PROTO();
	  p.add("command","docmgr_workflow_forcecomplete");
	  p.add("object_id",curobject);
	  p.add("workflow_id",workflow.id);	
	  p.post(DOCMGR_API,"writeForceComplete");

	}

}

function writeForceComplete(data) {

  if (data.error) alert(data.error);
  else
  {

    clearElement(ge("wfListDiv"));

		curtask = "";
		workflow = "";
		workflowhist = "";

    loadManagePage();

  }

}

function deleteWorkflow() {

	if (confirm("Are you sure you want to delete this workflow?"))
	{

	  updateSiteStatus("Deleting workflow");

	  var p = new PROTO();
	  p.add("command","docmgr_workflow_delete");
	  p.add("object_id",curobject);
	  p.add("workflow_id",workflow.id);	
	  p.post(DOCMGR_API,"writeDeleteWorkflow");

	}

}

function writeDeleteWorkflow(data) {

  if (data.error) alert(data.error);
  else
  {

		curtask = "";
		workflow = "";

		loadManagePage();

  }

}

function editOptions() {

	var ref = openSitePopup("350","100");

	ref.appendChild(ce("div","sitePopupHeader","","Workflow Options"));

	var opt = ref.appendChild(ce("div","sitePopupCell","recipOpts"));

  var cell = ce("div");
  var cb = createCheckbox("recipOpts[]","emailcomplete");
  setClick(cb,"recipSetOpt(event)");
  if (workflow.email_notify=="t") cb.checked = true;
  cell.appendChild(cb);
  cell.appendChild(ctnode("Email me when workflow is complete"));
  opt.appendChild(cell);

  var cell = ce("div");
  var cb = createCheckbox("recipOpts[]","emailexpired");
  setClick(cb,"recipSetOpt(event)");
  if (workflow.expire_notify=="t") cb.checked = true;
  cell.appendChild(cb);
  cell.appendChild(ctnode("Email me when a workflow task is overdue"));
  opt.appendChild(cell);

  ref.appendChild(opt);

}

function recipSetOpt(e) {

  var cb = getEventSrc(e);
  var val = cb.value;

  if (cb.checked==true) var action = "set";
  else var action = "unset";

  var p = new PROTO();
  p.add("command","docmgr_workflow_setopt");
  p.add("workflow_id",workflow.id);
  p.add("object_id",curobject);
  p.add("option",val);
  p.add("action",action);
  p.post(DOCMGR_API,"writeRecipSetOpt");

}
 
function writeRecipSetOpt(data) {
   
  clearSiteStatus();

  if (data.error) alert(data.error);
	else
	{

		//store in our global data array
		var arr = ge("recipOpts").getElementsByTagName("input");
	
		for (var i=0;i<arr.length;i++)
		{

			if (arr[i].checked==true) var val = "t";
			else var val = "f";

			if (arr[i].value=="emailexpired") workflow.expire_notify = val;
			else workflow.email_notify = val;

		}

	}

}
 
