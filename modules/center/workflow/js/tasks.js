
var taskdata;
var curtask;
var taskinfo;

function loadTasksPage()
{

  clearElement(ge("toolbarBtns"));
  clearElement(ge("toolbarTitle"));

	setupTaskPage();
	getTasks();

	if (curtask) endReq("viewTask('" + curtask + "')");

}

function setupTaskPage()
{

	clearElement(content);

	var tl = ce("div","","taskListDiv");
	var td = ce("div","","taskDataDiv");

  var divider = ce("div","toolbar","workflowDivider");
  divider.appendChild(ce("div","toolbarBtns","dividerBtns"));
  divider.appendChild(ce("div","toolbarTitle","dividerTitle"));

	content.appendChild(tl);
	content.appendChild(divider);
	content.appendChild(td);
	content.appendChild(createCleaner());

	loadTaskToolbar(); 

	setSizes();

}

function getTasks()
{

	var p = new PROTO();
	p.add("command","docmgr_workflow_gettasks");
	p.post(DOCMGR_API,"writeTasks");

}

function writeTasks(data)
{
	
	taskdata = data;

	clearSiteStatus();
	var tl = ge("taskListDiv");
	clearElement(tl);

	if (taskdata.error) alert(taskdata.error);
	else if (!taskdata.task)
	{
		tl.appendChild(ce("div","errorMessage","","No outstanding tasks found"));
	}
	else
	{

    var tbl = createTable("taskTable");
    var thead = ce("thead");
    var tbody = ce("tbody","","taskTableBody");

    tbl.appendChild(thead);
    tbl.appendChild(tbody);

    //header row
    var row = ce("tr");
    row.appendChild(ce("th","taskHeaderCell","","File"));
    row.appendChild(ce("th","taskHeaderCell","","Submited By"));
		row.appendChild(ce("th","taskHeaderCell","","Task Type"));
    row.appendChild(ce("th","taskHeaderCell","","Due")); 
    thead.appendChild(row);

    for (var i=0;i<taskdata.task.length;i++)
    {

			var task = taskdata.task[i];

      var row = ce("tr","taskListRow");
			row.setAttribute("task_id",task.id);
      row.appendChild(ce("td","taskListCell","",task.object_path));
      row.appendChild(ce("td","taskListCellCenter","",task.workflow_account_name));
      row.appendChild(ce("td","taskListCellCenter","",ucfirst(task.task_type)));
      row.appendChild(ce("td","taskListCellCenter","",task.date_due));

      var arr = row.getElementsByTagName("td");
      for (var c=0;c<arr.length;c++) setClick(arr[c],"viewTask('" + task.id + "')");

      tbody.appendChild(row);

    }

    tl.appendChild(tbl);

  }


}

function viewTask(id)
{

	curtask = id;
	taskinfo = new Array();
	var td = ge("taskDataDiv");
	clearElement(td);

	setSizes();

	for (var i=0;i<taskdata.task.length;i++)
	{

		if (taskdata.task[i].id==id)
		{
			taskinfo = taskdata.task[i];
			break;
		}

	}

	//update classes for rows
	var arr = ge("taskTableBody").getElementsByTagName("tr");

	for (var i=0;i<arr.length;i++)
	{
		if (arr[i].getAttribute("task_id")==id) setClass(arr[i],"taskListRowSelected");
		else setClass(arr[i],"taskListRow");
	}

	loadTaskToolbar();

	var lc = ce("div","taskLeftColumn");
	var rc = ce("div","taskRightColumn");

	//task data
	lc.appendChild(createTaskCell("Task Notes",taskinfo.task_notes));
	lc.appendChild(createTaskLink());

	rc.appendChild(createTaskComment());
	
	td.appendChild(lc);
	td.appendChild(rc);
	td.appendChild(createCleaner());	

}

function createTaskCell(title,data)
{

	var cell = ce("div","taskCell");	
	cell.appendChild(ce("div","formHeader","",title));
	cell.appendChild(ce("div","taskCellData","",data));

	return cell;

}

function createTaskLink() {

  var cont = ce("div","taskCell");

  //show view links for view and approval tasks, edit for edit
  if (taskinfo.task_type=="edit") {

    cont.appendChild(ce("div","formHeader","","Please make necessary edits to file, then mark task complete"));
    cont.appendChild(createLink("[Edit File]","javascript:siteViewFile('" + taskinfo.object_id + "')"));

  } else {

    cont.appendChild(ce("div","formHeader","","Please view file, then mark task complete"));
    cont.appendChild(createLink("[View File]","javascript:siteViewFile('" + taskinfo.object_id + "')"));

  }
   
  return cont;

}
 
//creates the form for note entry
function createTaskComment() {   

  var commdiv = ce("div","taskCell");
  var commheader = ce("div","formHeader","","Comments");
  var commform = createTextarea("taskComment");

  commdiv.appendChild(commheader);
  commdiv.appendChild(commform);  

  return commdiv;

}

function completeTask() {

  updateSiteStatus("Marking complete");

  var p = new PROTO();
  p.add("command","docmgr_workflow_markcomplete");
  p.add("route_id",taskinfo.id);
  p.add("object_id",taskinfo.object_id);
  p.add("comment",ge("taskComment").value);
  p.post(DOCMGR_API,"writeCompleteTask");

}
 
function writeCompleteTask(data)
{
 
  clearSiteStatus();

  if (data.error) alert(data.error);
  else 
  {    
		taskinfo = "";
		loadTasksPage();
  }
   
}

function rejectTask() {

  updateSiteStatus("Rejecting Approval");

  var p = new PROTO();
  p.add("command","docmgr_workflow_rejectapproval");
  p.add("route_id",taskinfo.id);
  p.add("comment",ge("taskComment").value);
  p.post(DOCMGR_API,"writeCompleteTask");

}


function loadTaskToolbar() {

  var tbBtns = ge("toolbarBtns");
  var tbTitle = ge("toolbarTitle");
  var divBtns = ge("dividerBtns");
  var divTitle = ge("dividerTitle");
 
  clearElement(tbBtns);
  clearElement(tbTitle);
  clearElement(divBtns);
  clearElement(divTitle);

 	tbTitle.appendChild(ctnode("Current Task List"));
 
  if (taskinfo) {

    divBtns.appendChild(siteToolbarCell("Mark Complete","completeTask()","save.png"));

		divTitle.appendChild(ctnode(taskinfo.object_name));

    //if an approval task, add a rejection button
    if (taskinfo.task_type=="approve") 
		{
      divBtns.appendChild(siteToolbarCell("Reject Approval","rejectTask()","delete.png"));
    }

  }

} 

