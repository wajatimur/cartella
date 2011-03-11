/***********************************************
	file for editing object properties
***********************************************/

var stageData = new Array();
var stagearr = new Array();
var popupref;
var taskrecip;
var recipid = "";
var routedata;
var curstage;
var templatelist;

function editRecipient() {

	if (!curobject) return false;

	//if forced ocomplete, stop
	if (workflow && workflow.status_view.toLowerCase().indexOf("complete")!=-1)
	{

		alert("You cannot edit the recipients of a completed workflow");

	}
	else
	{

		updateSiteStatus("Loading recipients");

		recipid = "";
		taskrecip = "";
		stagearr = new Array();
		routedata = "";

		clearElement(content);
		loadRecipToolbar();
		content.appendChild(ce("div","","stageContent"));

		loadStages();

	}

}

function loadRecipToolbar() {

  var tbBtns = ge("toolbarBtns");
  var tbTitle = ge("toolbarTitle");

	clearElement(tbBtns);
	clearElement(tbTitle);

	tbBtns.appendChild(siteToolbarCell("Templates","showWorkflowTemplates()","letter.png"));
	tbBtns.appendChild(siteToolbarCell("Save Template","saveWorkflowTemplate()","save.png"));
	tbBtns.appendChild(siteToolbarCell("New Stage","createNewStage()","new.png"));
	tbBtns.appendChild(siteToolbarCell("Back","loadManagePage()","back.png"));

}

function loadStages() {

	var p = new PROTO();
  p.add("command","docmgr_workflow_getinfo");
	p.add("workflow_id",workflow.id);
  p.add("object_id",curobject);
	p.post(DOCMGR_API,"writeStages");

}

function writeStages(data) {

	clearSiteStatus();

	if (data.error) alert(data.error);
	else 
	{

		stageData = new Array();
		workflow = data.workflow[0];

		if (!workflow.recipient) {

			routedata = new Array();
			stageData.push(routedata);

		} else {

			routedata = workflow.recipient;

			for (var i=0;i<routedata.length;i++) 
			{

				var key = routedata[i].sort_order;

				//store our recipient in our master stage array
				if (stageData[key]) stageData[key].push(routedata[i]);
				else stageData[key] = new Array(routedata[i]);
			
			}

		}

		//now update the display
		updateStageDisplay();

	}

}

function updateStageDisplay()
{

		var cont = ge("stageContent");
		clearElement(cont);

		for (var i=0;i<stageData.length;i++)
		{

			//create the stage container
			var sc = createStageContainer(i);
			var recip = stageData[i];

			//append recipients to the stage
			for (var c=0;c<recip.length;c++)
			{
				sc.appendChild(addStageRecip(recip[c]));
			}

			sc.appendChild(createCleaner());

			//append master container
			cont.appendChild(sc);

		}

}

function createStageContainer(key) 
{

	var stage = ce("div","stageContainer");
	stage.setAttribute("stage",key);

	var header = ce("div","stageHeader");

	//add recip link
	header.appendChild(createLink("[Add Recipient]","javascript:addRecipient('" + (key) + "')"));
	header.appendChild(ctnode("Stage " + ( parseInt(key)+1 )));

	stage.appendChild(header);
	stage.appendChild(createCleaner());

	return stage;

}



//add a recipient to a stage
function addStageRecip(r) {

	var cont = ce("div","recipContainer");

	//add account and date due
	cont.appendChild(ce("div","","",r.account_name));
	if (isData(r.date_due)) cont.appendChild(ce("div","","",r.date_due_view));
	cont.appendChild(ce("div","","","Task Type: " + ucfirst(r.task_type)));

	var links = ce("div");
	links.appendChild(createLink("[Edit]","javascript:addRecipient('" + r.sort_order + "','" + r.id + "')"));
	links.appendChild(createLink("[Delete]","javascript:deleteStageRecip('" + r.sort_order + "','" + r.id + "')"));
	cont.appendChild(links);

	return cont;

}

function createNewStage() {

	//if no key, figure out how many stages their are
	var arr = content.getElementsByTagName("div");

		var num = 0;
		for (var i=0;i<arr.length;i++) {
			if (arr[i].getAttribute("stage")) num++;
		}


	ge("stageContent").appendChild(createStageContainer(num));

}


function addRecipient(key,id) {

	if (id) popupref = openSitePopup("350","275");
	else popupref = openSitePopup("350","375");

	//stored values.  init for later
	var tasktype = "";
	var taskdate = "";
	var tasknotes = "";

	if (id) {

		recipid = id;
		var curroute = "";

		//get our data for the current route
		for (var i=0;i<routedata.length;i++) {

			if (routedata[i].id==id) {
				tasktype = routedata[i].task_type;
				taskdate = routedata[i].date_due_view;
				tasknotes = routedata[i].task_notes;
				break;
			}

		}
		
	}
	else recipid = "";

	popupref.appendChild(createHidden("stage",key));
	popupref.appendChild(ce("div","sitePopupHeader","","Edit Recipient"));	

	//type and date due
	var cell = ce("div","sitePopupCell");
	var lc = ce("div","leftColumn","",createTypeForm(tasktype));
	var rc = ce("div","rightColumn","",taskDateForm(taskdate));
	cell.appendChild(lc);
	cell.appendChild(rc);
	cell.appendChild(createCleaner());
	popupref.appendChild(cell);

	//recipients, only load when adding new
	if (!id) {

		var cell = ce("div","sitePopupCell");
		cell.appendChild(ce("div","formHeader","","Task Recipients"));
		taskrecip = ce("div","taskRecipList","","Fetching Account List");
		cell.appendChild(taskrecip);
		popupref.appendChild(cell);

		//now fetch the list of recipeints
		var url = "index.php?module=accountlist";
		protoReq(url,"writeTaskRecipList");

	}

	//for extra notes
	popupref.appendChild(createNoteForm(tasknotes));		

	//save button
	var cell = ce("div","sitePopupCell");
	cell.appendChild(createBtn("addRecipBtn","Save","saveAddRecips()"));
	popupref.appendChild(cell);


}

function writeTaskRecipList(data) 
{

	clearElement(taskrecip);

	if (data.error) alert(data.error);
	else if (!data.account) taskrecip.appendChild(ce("div","errorMessage","","No accounts found"));
	else {

		for (var i=0;i<data.account.length;i++) 
		{

			var row = ce("div");
			row.appendChild(createCheckbox("accountId[]",data.account[i].id));
			row.appendChild(ctnode(data.account[i].name));
			taskrecip.appendChild(row);

		}

	}

}

function saveAddRecips() {


	var p = new PROTO();
  p.add("command","docmgr_workflow_saverecip");
	p.add("workflow_id",workflow.id);
	p.add("route_id",recipid);
  p.add("object_id",curobject);
	p.addDOM(popupref);

	var d = p.getData();

	//make sure there's an account id set
	if (!isData(d.accountId))
	{

		alert("You must assign this task to an account");

	}
	else
	{	
		updateSiteStatus("Saving Recipient");
		p.post(DOCMGR_API,"writeSaveAddRecips");
	}

}

function writeSaveAddRecips(data) {

	clearSiteStatus();

	if (data.error) alert(data.error);
	else {

		//close the popup and reload the stage page
		closeSitePopup();
		loadStages();		

	}

}

//creates the form for task type selection
function createTypeForm(curdata) {

  var typediv = ce("div");
  var typeheader = ce("div","formHeader","","Task Type");

  var typeform = createSelect("taskType");
  typeform[0] = new Option("View","view");
  typeform[1] = new Option("Edit","edit");
  typeform[2] = new Option("Approve","approve");
  if (curdata) typeform.value = curdata;

  typediv.appendChild(typeheader);
  typediv.appendChild(typeform);

  return typediv;

}

//creates the form for date selection
function taskDateForm(curdata) {

  var datediv = ce("div");

  var dateheader = ce("div","formHeader","","Date Due");
  var dateform = createTextbox("dateDue",curdata);
  var datebtn = createBtn("setDateDue","...");

  datediv.appendChild(dateheader);
  datediv.appendChild(dateform);
  datediv.appendChild(datebtn);

  Calendar.setup({
          inputField      :    dateform,
          ifFormat        :   "%m/%d/%Y",
          button          :    datebtn,
          singleClick     :    true,           // double-click mode
          step            :    1                // show all years in drop-down boxes (instead of every other year as default)
      });

  return datediv;

}

//creates the form for note entry
function createNoteForm(curdata) {

  var notediv = ce("div","sitePopupCell");
  var noteheader = ce("div","formHeader","","Notes");
  var noteform = createTextarea("taskNotes",curdata);

  notediv.appendChild(noteheader);
  notediv.appendChild(noteform);

  return notediv;

}

function deleteStageRecip(stageid,id) {

	if (confirm("Are you sure you want to remove this recipient?")) {

		curstage = stageid;

		updateSiteStatus("Deleting Recipient");

		var p = new PROTO();
	  p.add("command","docmgr_workflow_deleterecip");
		p.add("workflow_id",workflow.id);
		p.add("route_id",id);
	  p.add("object_id",curobject);
		p.post(DOCMGR_API,"writeDeleteStageRecip");

	}

}

function writeDeleteStageRecip(data) {

	 
	clearSiteStatus();

	if (data.error) alert(data.error);
	else loadStages();

}

function showWorkflowTemplates() {

	popupref = openSitePopup("300","300");
	
	var cell = ce("div","sitePopupCell");
	cell.appendChild(ce("div","formHeader","","Available Workflow Templates"));
	templatelist = ce("div","workflowTemplateList");
	cell.appendChild(templatelist);
	
	popupref.appendChild(cell);

	updateSiteStatus("Loading templates");
	
	var p = new PROTO();
	p.add("command","docmgr_workflow_gettemplates");
	p.add("workflow_id",workflow.id);
	p.add("object_id",curobject);
	p.post(DOCMGR_API,"writeWorkflowTemplates");

}

function writeWorkflowTemplates(data) {

	 
	clearElement(templatelist);
	clearSiteStatus();

	if (data.error) alert(data.error);
	else if (!data.template) templatelist.appendChild(ce("div","errorMessage","","No templates found"));
	else {

		for (var i=0;i<data.template.length;i++) {

			var row = ce("div","","",data.template[i].name);
			setClick(row,"loadWorkflowTemplate('" + data.template[i].id + "')");
			templatelist.appendChild(row);

		}

	}

}

function saveWorkflowTemplate() {

	popupref = openSitePopup("300","300");

	//template name
	var cell = ce("div","sitePopupCell");
	cell.appendChild(ce("div","formHeader","","Template Name"));
	cell.appendChild(createTextbox("template_name"));
	cell.appendChild(createBtn("saveTemplateBtn","Save","runSaveTemplate()"));
	popupref.appendChild(cell);

	//template list	
	var cell = ce("div","sitePopupCell");
	cell.appendChild(ce("div","formHeader","","Or Update Template Below"));
	templatelist = ce("div","workflowTemplateList");
	cell.appendChild(templatelist);
	popupref.appendChild(cell);

	//fetch  the template list
	updateSiteStatus("Loading templates");

	var p = new PROTO();
	p.add("command","docmgr_workflow_gettemplates");
	p.add("workflow_id",workflow.id);
	p.add("object_id",curobject);
	p.post(DOCMGR_API,"writeSaveWorkflowTemplates");

}

function writeSaveWorkflowTemplates(data) {

	 
	clearElement(templatelist);
	clearSiteStatus();

	if (data.error) alert(data.error);
	else if (!data.template) templatelist.appendChild(ce("div","errorMessage","","No templates found"));
	else {

		for (var i=0;i<data.template.length;i++) 
		{

			var row = ce("div");
			row.appendChild(createRadio("template_id",data.template[i].id));
			row.appendChild(ctnode(data.template[i].name));
			templatelist.appendChild(row);

		}

	}

}

function runSaveTemplate() 
{

	var tn = ge("template_name");
	var rv = getRadioValue("template_id",popupref);

	if (tn.value.length==0 && !rv)
	{
		alert("You must specify a name");
		tn.focus();
		return false;
	}

	//fetch  the template list
	updateSiteStatus("Saving template");

	var p = new PROTO();
	p.add("command","docmgr_workflow_savetemplate");
	p.add("workflow_id",workflow.id);
	p.add("object_id",curobject);
	p.addDOM(popupref);
	p.post(DOCMGR_API,"writeRunSaveTemplate");

}

function writeRunSaveTemplate(data) {

	 
	clearSiteStatus();

	if (data.error) alert(data.error);
	else closeSitePopup();

}

function loadWorkflowTemplate(id) {

	//fetch  the template list
	updateSiteStatus("Loading workflow from template");

	var p = new PROTO();
	p.add("command","docmgr_workflow_getfromtemplate");
	p.add("workflow_id",workflow.id);
	p.add("template_id",id);
	p.add("object_id",curobject);
	p.addDOM(popupref);	
	p.post(DOCMGR_API,"writeLoadWorkflowTemplate");

	closeSitePopup();

}

function writeLoadWorkflowTemplate(data) {

	loadRecipToolbar();

	if (data.error) alert(data.error);
	else
	{

		writeStages(data);

	}

}

