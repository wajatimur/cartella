

/*****************************************************************
	TASK MANAGEMENT FUNCTIONS
*****************************************************************/


//globals
var task;
var curtask = "";					//entry we are currently viewing or editing
var contact;							//contact id
var taskinfo;
var contract;
var saveNotes = "";						//saves notes when switching between views
var timer;
var taskDiv;
var infoDiv;
var taskToolbar;
var taskToolbarTitle;
var taskToolbarBtns;
var cardDiv;
var conListDiv;
var taskType;
var curConType;

function setupTaskBody() {

	//clear out any current stuff
	taskToolbarTitle = ge("taskToolbarTitle");
	taskToolbarBtns = ge("taskToolbarBtns");
	taskDiv = ge("taskDiv");
	infoDiv = ge("infoDiv");

	clearElement(taskToolbarTitle);
	clearElement(taskToolbarBtns);
	clearElement(taskDiv);
	clearElement(infoDiv);

}

function loadEditPage() {

  //get our taskid, contactid, and ref to the contact div
  curtask = ge("taskId").value;
	ge("editTask").style.width = "100%";			//override stylesheet width

  //if in view mode, load the task for viewing, otherwise we are in new task mode
  if (curtask) showTask();
  else editTask();

}

/****************************************************************
	FUNCTION: showTask
	PURPOSE:  gets info for a task entry and displays it
	INPUT:		id -> id of task entry to display
*****************************************************************/	
function showTask(id,clear) {

	setupTaskBody();

	//set the status and empty the view section
	updateSiteStatus("Loading task entry");

	//transfer notes
	if (clear) saveNotes = "";
	else if (ge("notes")) saveNotes = ge("notes").value;

	//store the id we are viewing
	if (id) curtask = id;

	//call our module for info about the task
	var url = "index.php?module=taskinfo&mode=view&taskId=" + curtask;
	if (contact) url += "&contactId=" + contact;
	if (contract) url += "&contractId=" + contract;
	protoReq(url,"writeTaskInfo");

}


function storeGlobals(entry) {

	//we store them in the form also so all data is submitted automatically when
	//we save a task, instead of having to append them manually
	curtask = entry.id;
	taskType = entry.task_type;
	taskinfo = entry;

	if (isData(entry.contact_id)) contact = entry.contact_id;
	if (isData(entry.contract_id)) contract = entry.contract_id;

}

/****************************************************************
	FUNCTION: writeTaskInfo
	PURPOSE:  handler for showtask data, displays results on screen
	INPUT:		resp -> xml response from module
*****************************************************************/	
function writeTaskInfo(data) {

	clearSiteStatus();

	if (data.error) alert(data.error);
	else if (!data.task) alert("Error retrieving task entry");
	else {

		//create a task entry with our result
		var entry = data.task[0];
		storeGlobals(entry);

		/*******************************
			common task info
		*******************************/

		//default date
		if (!entry.date_due) entry.date = "Not Set";

		//setup the task view information.  creates a Name: Value row in the table
		taskDiv.appendChild(createTaskEntry("Task",entry.title));
		taskDiv.appendChild(createTaskEntry("Priority",entry.priority_view));
		taskDiv.appendChild(createTaskEntry("Date Due",entry.date));

		//show completed time & date
		var comptxt;
		if (entry.completed=="t") {
			if (entry.date_completed_view) comptxt = "Completed on " + entry.date_completed_view;
			else comptxt = entry.completed_view;
		}
		else comptxt = entry.completed_view;;

		taskDiv.appendChild(createTaskEntry("Completed",comptxt));

		if (entry.contact) loadTaskContacts(entry.contact);

		loadTaskBtns(entry);

		//required buttons.  create and put them on the toolbar
		if (entry.completed!="t") {
			var editbtn = siteToolbarBtn("Edit","editTask('" + entry.id + "')","edit.png");
			taskToolbarBtns.appendChild(editbtn);
		}

		var delbtn = siteToolbarBtn("Delete","deleteTask('" + entry.id + "')","delete.png");
		taskToolbarBtns.appendChild(delbtn);

		//say we are viewing a task
		taskToolbarTitle.appendChild(ctnode("Viewing Task"));

		//notes for the task
		if (saveNotes) entry.notes = saveNotes;					//if switching between modes, carry the notes with us
		else if (!entry.notes) entry.notes = "None";


		//add the form
		taskDiv.appendChild(notesForm(entry.notes));

	}

}

function loadTaskContacts(cg) {

	if (!cg) return false;
	if (!cg.contact) return false;

	conListDiv = ce("div","","contactList");
	cardDiv = ce("div","","contactCardDiv");

	infoDiv.appendChild(ce("div","","infoTitle","Affected Contacts"));

	for (var i=0;i<cg.contact.length;i++) {

		var con = cg.contact[i];
		if (!isData(con.type)) con.type = "other";

		var row = ce("div");
		var cb = createCheckbox("contactGroupId[]",con.contact_id);
		cb.setAttribute("contype",con.type);
		setClick(cb,"cycleContact('" + con.contact_id + "','" + con.type + "')");

		row.appendChild(cb);

		var txt = con.name + " ";
		if (con.type=="contact") {
			txt += "(Main Contact)";
			cb.checked = true;
			getContactInfo(con.contact_id,con.type);
		}
		else if (con.type=="cobroker") txt += "(Co Broker)";
		else if (con.type=="seller") txt += "(Seller)";
		else if (con.type=="buyer") txt += "(Buyer)";

		row.appendChild(ctnode(txt));
		conListDiv.appendChild(row);

	}

	infoDiv.appendChild(conListDiv);
	infoDiv.appendChild(cardDiv);
	infoDiv.appendChild(createCleaner());

}

function cycleContact(id,contype) {

	var cbarr = conListDiv.getElementsByTagName("input");
	
	for (var i=0;i<cbarr.length;i++) {
		
		if (cbarr[i].value==id && cbarr[i].getAttribute("contype")==contype) {
			if (cbarr[i].checked==true) getContactInfo(id,contype);
			else removeContactInfo(id,contype);
			break;
		}
	
	}

}

function removeContactInfo(id,contype) {

	var divarr = cardDiv.getElementsByTagName("div");

	for (var i=0;i<divarr.length;i++) {

		if (divarr[i].getAttribute("contact")==id && divarr[i].getAttribute("contype")==contype) {
			cardDiv.removeChild(divarr[i]);
			break;
		}

	}

}

function getContactInfo(cid,contype) {

	//save for later
	curConType = contype;

	//get the info
	updateSiteStatus("Fetching Contact Information");
	var url = "index.php?module=contactinfo&contactId=" + cid;
	protoReq(url,"writeContactInfo");

}

function writeContactInfo(data) {

	clearSiteStatus();

	 
	if (data.error) alert(data.error);
	else if (!data.contact) alert("Contact information not found");
	else {

		var card = ce("div","contactCard");
		card.setAttribute("contact",data.contact[0].id);
		card.setAttribute("contype",curConType);
		var c = data.contact[0];

		var namediv = ce("div","","",ce("div","contactCardName","",formatContactName(c)));
		var addrdiv = ce("div","","",formatContactAddress(c));

		var lc = ce("div","leftColumn");	
		var rc = ce("div","rightColumn");


		//left column
		lc.appendChild(namediv);
		lc.appendChild(addrdiv);
	
  	if (isData(c.email)) {
    	lc.appendChild(ctnode(c.email));
    	lc.appendChild(ce("br"));
  	}

		//right column
  	if (isData(c.home_phone)) {
    	rc.appendChild(ctnode("Home: " + c.home_phone));
    	if (ALLOW_C2C=="1") rc.appendChild(callLink(c.home_phone));
    	rc.appendChild(ce("br"));
  	}

  	if (isData(c.work_phone) || isData(c.work_ext)) {
   		if (isData(c.work_phone)) rc.appendChild(ctnode("Work: " + c.work_phone));
    	if (isData(c.work_ext)) rc.appendChild(ctnode(" ext: " + c.work_ext));
    	if (ALLOW_C2C) rc.appendChild(callLink(c.work_phone));
    	rc.appendChild(ce("br"));
  	}

  	if (isData(c.mobile)) {
    	rc.appendChild(ctnode("Mobile: " + c.mobile));
    	if (ALLOW_C2C=="1") rc.appendChild(callLink(c.mobile));
    	rc.appendChild(ce("br"));
  	}

  	if (isData(c.work_fax)) {
    	rc.appendChild(ctnode("Fax: " + c.work_fax));
    	rc.appendChild(ce("br"));
  	}

		card.appendChild(lc);
		card.appendChild(rc);
		card.appendChild(createCleaner());
		cardDiv.appendChild(card);	

	}

}

function callLink(dest) {

  var link = ce("a","",""," [Call]");
  link.setAttribute("href","javascript:c2c('" + dest + "')");
  return link;

}


function loadTaskBtns(entry) {

		//possible buttons
		if (entry.completed!="t") {

			if (entry.route_id)
			{

				var docmgrbtn = siteToolbarBtn("Perform Task","viewDocmgr('" + entry.route_id + "')","email.png");

			} else 
			{

				var docmgrbtn = siteToolbarBtn("Mark Complete","markTaskComplete('" + entry.id + "')","save.png");

			}

			//add our button depending on the correspondence type for the task
			taskToolbarBtns.appendChild(docmgrbtn);

		} 
		else
		{

			taskToolbarBtns.appendChild(siteToolbarBtn("Mark Incomplete","markTaskIncomplete('" + entry.id + "')","save.png"));

		}

		//correspondence type
		taskDiv.appendChild(createTaskEntry("Type",entry.c_type_name));

}

function viewDocmgr(id) {

	var url = "index.php?module=workflow&routeId=" + id;

	//handle being in a popup
	if (window.opener)
	{
		window.opener.location.href = url;
		self.close();
	}
	else location.href = url;

}

/****************************************************************
	FUNCTION: createTaskEntry
	PURPOSE:  creates a row with title and data to display task info
	INPUT:		title -> name of field
						data -> entry data to display
						useIH -> use innerHTMl instead of ctnode to show text data
*****************************************************************/	
function createTaskEntry(title,data,extra) {

	var row = ce("div","taskEntryRow");

	//setup our title div and data div
	var titlediv = ce("div","taskEntryTitle","",title);
	var datadiv = ce("div","taskEntryData","",data);

	if (extra) datadiv.appendChild(extra);

	//squish together
	row.appendChild(titlediv);	
	row.appendChild(datadiv);
	row.appendChild(createCleaner());

	return row;

}


/****************************************************************
	FUNCTION: saveTask
	PURPOSE:  submits the current form to the save module
	INPUT:	  none
*****************************************************************/	

function saveTask(handler) {

	//make sure ther'es a title for our task
	if (ge("title").value.length=="0") {
		alert("You must specify a description for this task");
		ge("title").focus();
		return false;
	}

	//return handler
	if (!handler) handler = "writeSaveTask";

	//get the content of our form
	var query = dom2Query(ge("taskDiv"));

	//tack on any ids set from storeGlobals()
	if (contact) query += "&contactId=" + contact;
	if (contract) query += "&contractId=" + contract;
	if (curtask) query += "&taskId=" + curtask;
	if (taskType) query += "&taskType=" + taskType;

	var url = "index.php?module=savetask&action=saveTask&" + query;
	protoReq(url,handler,"POST");	

}


/****************************************************************
	FUNCTION: writeSaveTask
	PURPOSE:  handles response from save module
	INPUT:	  resp -> xml data from saveprospect module
*****************************************************************/	

function writeSaveTask(data) {

	//handle errors
	if (data.error) {
		alert(data.error);
		return false;
	} else {

		//if in new mode, close and refresh parent
		if (curtask) {

			//show everything went okay
			updateSiteStatus("Task saved successfully");
			setTimeout("clearSiteStatus()","5000");

		} else if (window.opener) {

			//close our window and refresh the parent.
			if (window.opener.reloadTasks) {
				window.opener.reloadTasks();
			} else {
				var url = window.opener.location.href;
				window.opener.location.href = url;
			}

			setTimeout("self.close()","100");			//works around a non-closing bug I keep getting

		} else {

			curtask = data.taskId;
			setupTaskToolbar();
			loadTasks();

		}

	}

}

/****************************************************************
	FUNCTION: notesForm
	PURPOSE:  creates a textarea form for task notes to be displayed
						in while module is in the "view" mode
	INPUTS:		curval -> data to populate form with
****************************************************************/
function notesForm(curval) {

  var mydiv = ce("div");
  setClass(mydiv,"inputCell");

  var header = ce("div","multiformHeader","","Notes");
   
  var form = createTextarea("notes");
	setClass(form,"notesForm");
  if (curval) form.innerHTML = curval;

	setKeyUp(form,"ajaxSaveNotes()");

  //put it together
  mydiv.appendChild(header);
  mydiv.appendChild(form);  
  mydiv.appendChild(createCleaner());

  return mydiv;

}

function ajaxSaveNotes() {

  //reset the timer
  clearTimeout(timer);
	timer = setTimeout("saveTaskNotes()","500");
   
}  

function saveTaskNotes() {

	var curval = ge("notes").value;

	//stop here if there have been no changes or there's no taskId
	if (!curtask) return false;
	if (curval == saveNotes) return false;

	//if we make it to here update
	saveNotes = curval;

	var url = "index.php?module=savetask&action=saveNotes&taskId=" + curtask + "&notes=" + curval;
	protoReq(url,"writeSaveNotes","POST");	

}

function writeSaveNotes(data) {

	//only show errors
	 
	if (data.error) alert(data.error);

}

   
/***********************************************************
	editing
***********************************************************/

/****************************************************************
	FUNCTION: editTask
	PURPOSE:  loads form for editing a current task entry
	INPUT:		id -> id of task to edit.  if none passed, goes
									into new entry mode
*****************************************************************/	

function editTask(id) {

	if (id) curtask = id;
	else curtask = "";

	//save any already entered notes
	if (ge("notes")) saveNotes = ge("notes").value;

  //load our forms
	updateSiteStatus("Loading task form");
	var form = "config/forms/tasks/basic.xml";
 
 	loadForms(form,"","writeEditTask","getTaskData");
 
}

/****************************************************************
  FUNCTION: getTaskData
  PURPOSE:  our data handler.  it gets data for our task
            and returns it in array form to the calling function
  INPUT:    none
  RETURN:   task information in array form
*****************************************************************/
function getTaskData() {

  if (!curtask) return false;

  var data = protoReqSync("index.php?module=taskinfo&taskId=" + curtask);

  if (data.error || !data.task) {
		alert("Error retrieving data for from");
		return false;
	} else {

		//if savenotes, supplement our data with the new notes
		if (saveNotes) data.task[0].notes = saveNotes;
		return data.task[0];
 	}

}
 
/****************************************************************
  FUNCTION: writeEditTask
  PURPOSE:  handler for object returned from loadForms function.
            puts the object in the appropriate spot on the page.
  INPUT:    cont -> html object containing data to be placed
*****************************************************************/
function writeEditTask(cont) {

	clearSiteStatus();
	setupTaskBody();

	setupTaskToolbar();
	taskDiv.appendChild(cont);

}

function setupTaskToolbar() {

	clearElement(taskToolbarBtns);
	clearElement(taskToolbarTitle);

	//show our save button if not in wizard mode
	var vbtn = siteToolbarBtn("View","showTask()","view.png");
	var sbtn = siteToolbarBtn("Save","saveTask()","save.png");

	if (curtask) {
		taskToolbarBtns.appendChild(vbtn);
		var str = "Editing Task";
	} else var str = "Create New Task";

	taskToolbarBtns.appendChild(sbtn);
	taskToolbarTitle.appendChild(ctnode(str));

}


/****************************************************************
	FUNCTION: deleteTask
	PURPOSE:  removes currently loaded task from system
*****************************************************************/	

function deleteTask() {

	if (confirm("Are you sure you want to remove this task and any associated events?")) {
		var url = "index.php?module=savetask&action=deleteTask&taskId=" + curtask;
		protoReq(url,"writeDeleteTask");
	}

}

/****************************************************************
	FUNCTION: writeDeleteTask
	PURPOSE:  response handler for deleteTask
	INPUTS:   resp -> xml response from savetask module
*****************************************************************/	
function writeDeleteTask(data) {

	 
	if (data.error) alert(data.error);
	else {

		if (window.opener) {

			if (window.opener.reloadTasks) {
				window.opener.reloadTasks();
			} else {
				var url = window.opener.location.href;
				window.opener.location.href = url;
			}

			setTimeout("self.close()","100");

		} else {

			curtask = "";
			setupTaskBody();
			loadTasks();

		}

	}

}

function getContactGroup() {

	if (!conListDiv) return "";

	var str = "";
	var check = new Array();

	var arr = conListDiv.getElementsByTagName("input");
	
	for (var i=0;i<arr.length;i++) {

		//if the box is checked and the account isn't already in the array, continue
		if (arr[i].checked==true) {
			var key = arraySearch(arr[i].value,check);
			if (key==-1) str += "&contactId[]=" + arr[i].value;
		}
	}

	return str;

}


/****************************************************************
	FUNCTION: createEmail
	PURPOSE:  transfers task information to createemail module, where user
						can create email and mark task complete
****************************************************************/
function createEmail() {

	var m = ge("windowMode").value;
	var url = "index.php?module=createemail&hideHeader=1&taskId=" + curtask + getContactGroup();

	//resize to bigger window
	if (m=="popup") {

		  xPos = (screen.width - 900) / 2;
  		yPos = (screen.height - 650) / 2;

			window.moveTo(xPos,yPos);
			window.resizeTo(800,650);

			url += "&hideHeader=1";
			location.href = url;

	} else {

		var parms = centerParms(900,600,1) + ",resizable=1,scrollbars=1";
		window.open(url,"_blank",parms);

	}

}

/****************************************************************
	FUNCTION: createLetter
	PURPOSE:  transfers task information to createletter module, where user
						can create letter and mark task complete
****************************************************************/
function createLetter() {

	var m = ge("windowMode").value;

  var url = "index.php?module=editor&taskId=" + curtask + getContactGroup();

	//resize to bigger window
	if (m=="popup") {

		  xPos = (screen.width - 800) / 2;
  		yPos = (screen.height - 600) / 2;

			window.moveTo(xPos,yPos);
			window.resizeTo(800,600);

			location.href = url;

	} else {

		var parms = centerParms(1000,600,1) + ",resizable=1,scrollbars=1";
		window.open(url,"_blank",parms);

	}

}

/****************************************************************
	FUNCTION: completeTask
	PURPOSE:  marks currently loaded task complete
****************************************************************/
function completeTask() {

	updateSiteStatus("Marking task complete");
	var url = "index.php?module=savetask&action=setcomplete&taskId=" + curtask + getContactGroup();
	protoReq(url,"writeCompleteTask");

}

/****************************************************************
	FUNCTION: writeCompleteTask
	PURPOSE:  response handler for completeTask
	INPUTS:		resp -> xml data from savetask module
****************************************************************/
function writeCompleteTask(data) {

	 
	clearSiteStatus();

	if (data.error) alert(data.error);
	else {

		if (window.opener) {

			if (window.opener.reloadTasks) {
				window.opener.reloadTasks();
			}				
			else {
				var url = window.opener.location.href;
				window.opener.location.href = url;
			}

			setTimeout("self.close()","100");

		} else {

			curtask = "";
			setupTaskBody();
			loadTasks();
			
		}
		
	}

}



function _selectletterForm(curform) {

  var mydiv = ce("div","inputCell","letterChooser");

  var header = ce("div");
  setClass(header,"formHeader");
  header.appendChild(ctnode(curform.title));

  var size;
  if (curform.size) size = curform.size;

  //see if there's data to populate this form
  var curval;
  if (formdata && formdata[curform.data]) curval = formdata[curform.data];
  else if (curform.defaultval) curval = curform.defaultval;

  var form = createTextbox(curform.name,curval,size);
  var hidden = createForm("hidden","objectId");
  if (formdata && formdata["object_id"]) hidden.value = formdata["object_id"];

  //optional settings, set by corresponding xml tags for the form
  form.setAttribute("READONLY","true");

  //put it together   
  mydiv.appendChild(header);
  mydiv.appendChild(form);
  mydiv.appendChild(hidden);

  //browse image for browsing all lots w/o typing
  var img = ce("img","","propertyBrowse");
  img.setAttribute("src",theme_path + "/images/icons/browse.png");
  setClick(img,"browseObjects()");

  //append a link onto the end of it for browsing
  mydiv.appendChild(img);

  mydiv.appendChild(createCleaner());

  return mydiv;

}

function browseObjects() {

    //launch our selector to pick where to save the file
    var parms = centerParms(600,460,1) + ",resizable=no,scrollbars=no";
    var url = "index.php?module=minib&objectTypeFilter=collection,file,document&objectFilter=doc,docx";
    var ref = window.open(url,"_minib",parms);
    ref.focus();

}

function mbSelectObject(path,type,ext,id) {

  setTimeout("setObjPath(\"" + path + "\",'" + id + "')","10");

}

function setObjPath(path,id) {

  ge("objectPath").value = path;
  ge("objectId").value = id;

}

function markTaskComplete(tid) {

	updateSiteStatus("Marking complete");
	var url = "index.php?module=savetask&action=setcomplete&complete=t&taskId=" + tid;
  protoReq(url,"loadTasks");

	endReq("showTask()");

}

function markTaskIncomplete(tid) {

	updateSiteStatus("Marking incomplete");
	var url = "index.php?module=savetask&action=setcomplete&complete=f&taskId=" + tid;
  protoReq(url,"loadTasks");

	endReq("showTask()");

}

function writeTaskComplete(data)
{

	if (data.error) alert(data.error);
	else {

		loadTasks();
		showTask();

	}

}
