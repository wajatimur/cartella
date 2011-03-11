
/********************************************************************
	FILENAME: javascript.js
	PURPOSE:  contains common contact editing related functions

*********************************************************************/

//globals
var timer;								//timer used for existing contact suggest
var toolbar;
var toolbarBtns;
var toolbarTitle;
var toolbarStatus;
var content;
var taskList;
var taskView;

var cursort = "date";
var curdate = "week";
var curfilter = "incomplete";
var curtask;										//when editing a task, id of the task we are editing
var curform;										//form we are using to save data
var curpath;

/****************************************************************
	FUNCTION: loadPage
	PURPOSE:  loads our page for the first time
	INPUT:	  none
*****************************************************************/	

function loadPage(pmode) {

	setupStructure();
	loadMenu();
	loadToolbar();

	loadTasks();

}

function setupStructure() {

	taskList = ge("taskList");
	taskView = ge("taskView");

}

function changeFilter() {

	curfilter = ge("taskFilter").value;
	loadTasks();

}

function changePeriod() {

	curdate = ge("taskPeriod").value;
	loadTasks();

}

function changeSort() {

	cursort = ge("taskSort").value;
	loadTasks();

}

/****************************************************************
  FUNCTION: loadTasks
  PURPOSE:  setups up toolbar for main contact page
  INPUT:    none
*****************************************************************/

function loadTasks() {

	updateSiteStatus("Updating...");

	var ss = ge("msgSiteSearchString").value;

  //can't use asynced calls in the modlets.  get our list of tasks
  var url = "index.php?module=tasksearch&action=showtasks&sort=" + cursort + "&date=" + curdate + "&filter=" + curfilter;

	//limit if there's a search string also
	if (ss.length > 0) url += "&searchString=" + ss;

  protoReq(url,"writeTaskList");

}

function writeTaskList(data) {

	 

	clearElement(taskList);
	clearSiteStatus();

	if (data.error) alert(data.error);
	else if (!data.task) taskList.appendChild(ce("div","errorMessage","","There are no tasks to display"));
	else {

		//now setup the body
		for (var i=0;i<data.task.length;i++) {

			var t = data.task[i];
			taskList.appendChild(createTaskRow(t));

		}

	}

}


/****************************************************************
  FUNCTION: createTaskRow
  PURPOSE:  creates a row with title and data to display task info
  INPUT:    task -> task data
*****************************************************************/
function createTaskRow(task) {

	var row = ce("div","taskRow");

	//complete/incomplete checkbox
	if (task.completed=="t") var completed = task.id;
	else var completed = "";

	var cb = createCheckbox("taskId[]",task.id,completed);
	setClick(cb,"markComplete('" + task.id + "')");
	var cbcell = ce("div","selectCell","",cb);

	//name
	var titlecell = ce("div","titleCell","",task.title);
	setClick(titlecell,"showTask('" + task.id + "','1')");

	//date due
	var ddcell = ce("div","dateCell","",task.date_due_view);
	setClick(ddcell,"showTask('" + task.id + "','1')");

	row.appendChild(cbcell);
	row.appendChild(titlecell);
	row.appendChild(ddcell);
	row.appendChild(createCleaner());

	return row;

}

function loadToolbar() {

  //create our columns
  toolbar = ge("toolbar");

	var filterCell = ce("div","toolbarCell","","Show: ");

	var filter = createSelect("taskFilter");
	setChange(filter,"changeFilter()");
	filter[0] = new Option("Incomplete Tasks","incomplete");
	filter[1] = new Option("Complete Tasks","complete");
	filter[2] = new Option("All Tasks","all");

	filterCell.appendChild(filter);

	var periodCell = ce("div","toolbarCell","","Period: ");

	var period = createSelect("taskPeriod");
	setChange(period,"changePeriod()");
	period[0] = new Option("Due This Week","week");
	period[1] = new Option("Due This Month","month");
	period[2] = new Option("All Tasks","all");

	periodCell.appendChild(period);

	sortCell = ce("div","toolbarCell","","Sort: ");

	var sort = createSelect("taskSort");
	setChange(sort,"changeSort()");
	sort[0] = new Option("Due Date","date");
	sort[1] = new Option("Priority","priority");
	sort[2] = new Option("Date Completed","datecompleted");

	sortCell.appendChild(sort);

  toolbar.appendChild(filterCell);
	toolbar.appendChild(periodCell);
	toolbar.appendChild(sortCell);
	toolbar.appendChild(createCleaner());

	//create task button for the list
	ge("taskListBtns").appendChild(siteToolbarBtn("Create New Task","editTask()","new.png"));

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
  timer = setTimeout("loadTasks()",250);

}


function markComplete(tid) {

	var curtask = tid;

	var arr = taskList.getElementsByTagName("input");
	
	for (var i=0;i<arr.length;i++) {

		if (arr[i].id=="taskId[]" && arr[i].value == tid) {

			if (arr[i].checked==true) var action = "t";
			else var action = "f";

			var url = "index.php?module=savetask&action=setcomplete&complete=" + action + "&taskId=" + tid;
			protoReq(url,"loadTasks");

			break;

		}

	}

}


/****************************************************************
  FUNCTION: loadMenu
  PURPOSE:  creates our left column nav menu
  INPUT:    none
*****************************************************************/

function loadMenu() {

  showModNav();

  //always show contact information
  addModNav("Create New Task","editTask()");

}


