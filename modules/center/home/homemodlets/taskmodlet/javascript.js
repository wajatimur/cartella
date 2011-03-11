
var cursort = "date";
var curdate = "week";
var curfilter = "incomplete";
var winref;
var modletid;
var timer;

function loadTaskList(mid) {

	
	ge("taskList").innerHTML = "<div class=\"statusMessage\">Updating...</div>";
	modletid = mid;

	var daterange = ge("taskSpan" + modletid).value;

	//can't use asynced calls in the modlets.  get our list of tasks
	var url = "index.php?module=tasksearch&action=showtasks&filter=incomplete&date=" + daterange;
	protoReq(url,"writeTaskList");
	
}

function writeTaskList(data) {

	 

	var tl = ge("taskList");

	tl.innerHTML = "";

	if (data.error) alert(data.error);
	else if (!data.task) tl.innerHTML = "<div class=\"errorMessage\">No tasks to display</div>";
	else {

		var d = new Date();
		var today = d.getFullYear() + "-" + (d.getMonth()+1) + "-" + d.getDate();

		for (var i=0;i<data.task.length;i++) {

			var task = data.task[i];

			//the class
			if (data.task[i].expired==1) var cn = "expiredTask";
			else var cn = "curTask";

			//the text
			if (data.task[i].date_due_view) var str = data.task[i].date_due_view + ": " + data.task[i].title;
			else var str = data.task[i].title;

			//put it together
			var row = ce("li",cn,"",str);
			setClick(row,"performTask('" + data.task[i].id + "')");

			//I don't know
			if (BROWSER=="safari") row.style.marginLeft = "6px";

			tl.appendChild(row);

		}

	}

}

function changeTaskSort() {

	ge("taskList").innerHTML = "<div class=\"statusMessage\">Updating...</div>";
	loadTaskList();

}


function setDate(str) {
	curdate = str;

	resetClass("dateOpts",str);

	loadTaskList();	
}

function setFilter(str){ 
	curfilter = str;

	resetClass("filterOpts",str);

	loadTaskList();
}

function setSort(str) {
	cursort = str;

	resetClass("sortOpts",str);

	loadTaskList();
}

function resetClass(divname,newval) {

	var arr = ge(divname).getElementsByTagName("div");

	for (var i=0;i<arr.length;i++) {
		if (arr[i].getAttribute("id")==newval) setClass(arr[i],"selected");
		else setClass(arr[i],"unselected");
	}

}

function reloadTasks() {

	var url = location.href;
	location.href = url;

}



/****************************************************************
  FUNCTION: manageTasks
  PURPOSE:  main function for loading our edittask page.
  INPUT:    none
*****************************************************************/
function manageTasks(modid) {

	modletid = modid;
	winref = openSitePopup(300,150,"closeManageTask()");	

	var namediv = ce("div","sitePopupCell");
	var spandiv = ce("div","sitePopupCell");

	winref.appendChild(namediv);
	winref.appendChild(spandiv);

	//name of the calendar modlet
	var taskname = ce("div","formHeader","","Tasks Applet Name");
	var namebox = createTextbox("taskName",ge("taskName" + modid).value);
	setKeyUp(namebox,"ajaxUpdateTasks()");

	namediv.appendChild(taskname);
	namediv.appendChild(namebox);

	var taskspan = ce("div","formHeader","","Show Tasks Due");
	var sel = createSelect("taskSpan");
	setChange(sel,"updateTasks()");
	sel[0] = new Option("Today","day");
	sel[1] = new Option("Next Week","week");
	sel[2] = new Option("Next Month","month");

	sel.value = ge("taskSpan" + modid).value;

	spandiv.appendChild(taskspan);
	spandiv.appendChild(sel);

}

function ajaxUpdateTasks() {

	clearTimeout(timer);
	timer = setTimeout("updateTasks()","500");

}

function closeManageTask() {
	closeSitePopup();
	var url = location.href;
	location.href = url;
}

function updateTasks() {

	var url = "index.php?module=savetaskmodlet&action=update&" + dom2Query(winref) + "&container=" + modletid;
	protoReq(url,"writeTaskResp");

}

function writeTaskResp(data) {

	 
	if (data.error) alert(data.error);

}
