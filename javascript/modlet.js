/***********************************************************
	this file contains functions for loading
	modlets into divs onto the page
***********************************************************/

//globals
var helperMode;			//to store state of helper box
var contextmenu;		//for storing helper box contents
var modletcount = 0;
var modletcontainer = new Array();
var siteSorter;

function loadAllModlets(lc,rc) 
{

  var col1 = ge("column1"); 
  var col2 = ge("column2"); 

  var cont1 = ge("containerid1"); 
  var cont2 = ge("containerid2"); 

  var i;

  //if modules in column 1, load them
  if (col1.value.length > 0) {

    var colarr = col1.value.split(",");
		var contarr = cont1.value.split(",");

    //loop through and load each modlet
    for (i=0;i<colarr.length;i++) {
			loadModletDiv(lc,colarr[i],contarr[i]);
		}

  }
   
  //if modules in column 2, load them
  if (col2.value.length > 0) {

    var colarr = col2.value.split(",");
		var contarr = cont2.value.split(",");

    //loop through and load each modlet
    for (i=0;i<colarr.length;i++) {
			loadModletDiv(rc,colarr[i],contarr[i]);
		}
  }

}


/*******************************************************************************
	FUNCTION:	loadModletDiv
	PURPOSE:	creates a div for each modlet and fetches the modlet content.  this
						makes sure they are in the right order on the page
	INPUT:		col -> element where modlet div will go
						modlet -> modlet name
*******************************************************************************/

function loadModletDiv(col,modlet,modletid) {

	//create the div with all the attributes set
	var cont = ce("div","siteMessage","","Loading Modlet");

	//set our modletcount to the highest
	var c = returnNumbers(modletid);

	if (modletcount<c) modletcount = c;

	setClass(cont,"Modlet");
	cont.setAttribute("id",modletid);
	cont.setAttribute("modlet",modlet);

	//append this to the column
	if (document.all) col.appendChild(cont);
	else col.adopt(cont);

	//get the contents for this modlet
	loadModlet(modlet,modletid);

}

/*******************************************************************************
	FUNCTION:	loadModlet
	PURPOSE:	load a modlet and insert it's content into the div. 
	INPUT:		modlet -> module name of the modlet we are loading.  it is also
											the name of the div where the content will go
*******************************************************************************/
function loadModlet(modlet,id) {

	var url = "index.php?module=" + modlet + "&modletid=" + id;
	protoReq(url,"writeModlet");

}


/*******************************************************************************
	FUNCTION:	writeModlet
	PURPOSE:	handler for response from modlet loading
	INPUT:		resp -> xml data passed from modlet
*******************************************************************************/
//write the modlet data to the container
function writeModlet(data) {

	//this will give us data.containerid, data.header, and data.content for populating the div
	//ge(modletname) will give us the reference to the modlet's div.  we assume
	//there can only be one modlet of the same name per page

	//use the saved id if passed
	if (data.containerid) var cid = data.containerid;
	else var cid = data.modletid;

	//load the javascript and stylesheet
	//not sure why, but this needs to be called twice.  Once here, and once evalled later
	//otherwise can't run our pageload function
	if (data.javascript) loadJavascript(data.javascript);	
	if (data.stylesheet) loadStylesheet(data.stylesheet);

	//create our container
	var cont = ge(cid);
	clearElement(cont);

	//create the header div
	var header = ce("div","ModletHeader");

	//add our controls
	var ctrl = ce("div","ModletCtrl");
	var headertxt = ce("div","","",data.header);

	//delete
	var del = ce("img");
	del.setAttribute("src","themes/default/images/icons/delete.png");	
	del.setAttribute("title","Remove applet from page");
	setClick(del,"removeModlet('" + cid + "')");

	//allow additional data to be put on right side of header
	if (data.rightheader) {

		var s = ce("span");
		s.innerHTML = data.rightheader;
		ctrl.appendChild(s);
		ctrl.appendChild(del);

	//otherwise just add our delete icon
	} else ctrl.appendChild(del);

	//append the ctrl div to the header
	header.appendChild(ctrl);
	header.appendChild(headertxt);

	//create our content div and populated it
	var content = ce("div","ModletContent");
	content.innerHTML = data.content;

	//put it all together
	if (!cont) return false;
	cont.appendChild(header);
	cont.appendChild(content);

	//look for javascript 
	if (data.pageload) {
		eval(loadScript(site_url + data.javascript));
	}

	//do we have a function to load?
	if (data.pageload) {
		eval(data.pageload);
	}

	siteSorter.addItems(cont);

}


/*******************************************************************************
	FUNCTION:	saveLayout
	PURPOSE:	when modlet layout is changed, saves layout for user
	INPUT:		none
*******************************************************************************/
function saveLayout() {

	//get all modlets for column1
	var col1arr = ge("LeftColumn").getElementsByTagName("div");
	var col2arr = ge("RightColumn").getElementsByTagName("div");
	var i;

	//determine mode based on presence of accountid or groupid field
	var aid = ge("accountId");
	var gid = ge("groupId");
	var settings;

	//if there's an accountId, save settings for the account,
	//otherwise save for the group
	if (gid && gid.value.length > 0) settings = "mode=group&groupId=" + gid.value;
	else settings = "mode=account&accountId=" + aid.value;


	//assemble our save url
	var url = "index.php?module=savelayout&" + settings + "&saveModule=" + ge("saveModule").value;

	//loop through and get modlets only
	for (i=0;i<col1arr.length;i++) {
		var m = col1arr[i].getAttribute("modlet");
		if (m) url += "&column1[]=" + m + "&containerId1[]=" + col1arr[i].getAttribute("id");
	}

	for (i=0;i<col2arr.length;i++) {
		var m = col2arr[i].getAttribute("modlet");
		if (m) url += "&column2[]=" + m + "&containerId2[]=" + col2arr[i].getAttribute("id");
	}

	//store the settings
	protoReq(url,"writeSaveLayout","POST");

}

/*******************************************************************************
	FUNCTION:	writeSaveLayout
	PURPOSE:	response handler for savelayout module.  only interferes if there is 
						an error saving settings
	INPUT:		resp -> xml response data
*******************************************************************************/
function writeSaveLayout(data) {

	if (data.error) alert("There was an error storing your configuration");

	//if we're in modlet mode, reload the sidebar
	//if (helperMode=="modlet") addModlet();

}


/*******************************************************************************
	FUNCTION:	addModlet
	PURPOSE:	shows available modlets in left column for user to add to screen
	INPUT:		none;
*******************************************************************************/

//shows our available modlets and allows the user to add them to the screen
function addModlet() {

	//get our list of modlets to display
	var url = "index.php?module=getmodlets&showModule=" + ge("saveModule").value;
	if (ge("groupId")) url += "&groupId=" + ge("groupId").value;
	protoReq(url,"writeModletList");

}

/*******************************************************************************
	FUNCTION:	writeModletList
	PURPOSE:	writes avaiable modules to the left column menu
	INPUT:		resp -> xml response data from getmodlets module
*******************************************************************************/
function writeModletList(data) {
	
	var lh = moduleNav;
	var i;

	//store the context menu contents for later
	contextmenu = lh.innerHTML;
	
	//set our mode
	helperMode = "modlet";

	//clear it out
	clearElement(lh);

	showModCtrl();

	var donelink = ce("img");
	donelink.setAttribute("src",theme_path + "/images/icons/delete.png");
	setClick(donelink,"resetHelpers()");

	moduleNavCtrl.appendChild(donelink);
	moduleNavCtrl.appendChild(ctnode("Available Applets"));

	//get out if there is nothing to show
	if (!data.modlet) {
		lh.innerHTML = "No available applets to display";
		return false;
	}

	//add our new list in
	for (i=0;i<data.modlet.length;i++) {

		var title = data.modlet[i].module_name;
		var link = "addModletToPage('" + data.modlet[i].link_name + "')";

		addModNav(title,link);

	}

}

/*******************************************************************************
	FUNCTION:	addModletToPage
	PURPOSE:	loads a new modlet and adds it to the first column on the page
	INPUT:		modlet -> modlet name
*******************************************************************************/
function addModletToPage(modlet) {

	//load the modlet
	modletcount++;
	loadModletDiv(ge("LeftColumn"),modlet,modlet + modletcount);

	//putting this here is a complete hack.  I have to reinit siteSorter after loading the modlet divs
	if (document.all) {

    siteSorter = new Sortables([ge("LeftColumn"),ge("RightColumn")], {

        handle: 'div',

        revert: { duration: 250, transition: 'linear' },

        opacity: .25,

        clone: true,

        onComplete: function() {
          saveLayout();
        },

        onStart: function() {
          this.clone.style.width = "350px";
          if (!document.all) this.clone.style.marginLeft = "250px";
        }

      });

	}

	//now save our settings
	saveLayout();

}


/*******************************************************************************
	FUNCTION:	resetHelpers
	PURPOSE:	resets helper menu to original list
	INPUT:		none;
*******************************************************************************/
//shows our original helper list
function resetHelpers() {

	moduleNav.innerHTML = contextmenu;
	hideModCtrl();
	helperMode = "";

}

/*******************************************************************************
	FUNCTION:	removeModlet
	PURPOSE:	removes modlet from a page
	INPUT:		modlet -> modlet name
*******************************************************************************/
function removeModlet(modlet) {

	//remove the entry
	//ge("dashboard").removeChild(ge(modlet));
	var divarr = ge("dashboard").getElementsByTagName("div");
	var i;

	//loop through all divs at remove the matching one
	for (i=0;i<divarr.length;i++) {

		if (divarr[i].getAttribute("id")==modlet) {
			divarr[i].parentNode.removeChild(divarr[i]);
			break;
		}

	}

	//save our settings
	saveLayout();

}
