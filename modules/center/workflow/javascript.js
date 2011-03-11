var content;
var curpage;
var curobject;

window.onresize = setSizes;

function loadPage()
{

	content = ge("container");
	curobject = ge("object_id").value;
	curtask = ge("route_id").value;

	loadMenu();

	if (curtask)
	{
		loadWorkflowPage("tasks");
	}
	else if (ge("action").value=="newWorkflow")
	{

		curpage = "workflow";

		//we have our object, use it
		if (curobject) 
		{
			var arr = new Array();
			arr.id = curobject;
			mbSelectObject(arr);
		}
		else
		{
			loadManagePage();
			newWorkflow();
		}

	}
	else
	{
		loadWorkflowPage("workflow");
	}

}


function loadWorkflowPage(page,direct) {

  if (page) curpage = page;
  updateSiteStatus("Loading Page");

  //if a directlink, clear the wizard mode
  if (direct) pagemode = "normal";

  switch (curpage) {   

    case "tasks":
      historyManager("loadTasksPage","Current Workflow Tasks");
      break;
 
    case "workflow":
      historyManager("loadManagePage","Manage Workflows");
      break;

    case "history":
      historyManager("loadHistoryPage","Workflow History");
      break;

  }

}


function setToolbarTitle(txt)
{

	var ref = ge("toolbarTitle");
	clearElement(ref);
	ref.appendChild(ctnode(txt));

}

/****************************************************************
  FUNCTION: loadMenu
  PURPOSE:  creates our left column nav menu
  INPUT:    none
*****************************************************************/

function loadMenu() {

  showModNav();

  //always show contact information
	menuEntry("Current Workflows","workflow");
  menuEntry("Current Tasks","tasks");
	menuEntry("Workflow History","history");

}

/****************************************************************
  FUNCTION: menuEntry
  PURPOSE:  creates one entry for our nav menu
  INPUT:    none
*****************************************************************/

function menuEntry(title,page) {

  var link = "loadWorkflowPage('" + page + "','1')";

  addModNav(title,link);  

}

function closeWindow()
{
	return false;
}

function setSizes()
{

	var listdiv;
	var datadiv;
	var check;

	if (curpage=="history")
	{
		listdiv = ge("histListDiv");
		datadiv = ge("histDataDiv");
		check = workflowhist;
	}
	else if (curpage=="workflow")
	{
		listdiv = ge("wfListDiv");
		datadiv = ge("wfDataDiv");
		check = workflow
	}
	else
	{
		listdiv = ge("taskListDiv");
		datadiv = ge("taskDataDiv");
		check = curtask;
	}

	var startheight = getWinHeight() - 110;		

	if (check)
	{

		var newheight = "200px";

		//if it's currently expanded
		if (parseInt(listdiv.style.height)==startheight)
		{

      var myEffect = new Fx.Morph(listdiv, {duration: 500, transition: Fx.Transitions.Sine.easeOut});

      myEffect.start({
        'height': [startheight + "px", newheight] //Morphs the 'height' style from 10px to 100px.
      });

		} else listdiv.style.height = newheight;

	}
	else
	{

		listdiv.style.height = startheight + "px";

	}


}




