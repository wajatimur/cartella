/*************************************************
	FILENAME: common.js
	PURPOSE:  contains miscellaneous common
						site-specific javascript functions
*************************************************/

var pageLoadArr = new Array();
var moduleNav;
var moduleNavCtrl;
var moduleNavFooter;
var logintimer;
var docmgrtimer;
var siteFileUpload = 0;			//set this when uploading a file to prevent other background processes from running
var sitepopupref;
var altpressed = false;
 
document.onkeyup = siteHandleKeyUp;
document.onkeydown = siteHandleKeyDown;

/*************************************************
	FUNCTION: performTask
	PURPOSE:  opens task module to perform specified task
	INPUTS:		taskId -> id of task to perform
*************************************************/

function performTask(taskId) {

	var parms = centerParms(700,600,1) + ",scrollable=yes,resizable=yes";

	var url = "index.php?module=managetasks&hideHeader=1&taskId=" + taskId;
	var ref = window.open(url,"_task",parms);
	ref.focus();

}

/*************************************************
	FUNCTION: createNewTask
	PURPOSE:  opens addtask module to create new task
*************************************************/

function createNewTask() {

	var parms = centerParms(600,450,1) + ",scrollable=yes,resizable=yes";

	var url = "index.php?module=managetasks&hideHeader=1";
	var ref = window.open(url,"_task",parms);
	ref.focus();

}

/*************************************************
	FUNCTION: switchLocation
	PURPOSE:  opens addtask module to create new task
*************************************************/

function switchLocation() {

	//make sure we aren't in a banned module
	var banned = new Array("editcontact","editcontract");
	var key = arraySearch(MODULE,banned);

	if (key!=-1) {
		alert("You cannot switch locations while in this module");
	} else {

		var ref = ge("locSwitchDiv");
	
		if (ref.style.display == "block") {
			ref.style.display = "none";
		}
		else {

			ref.style.display = "block";

			if (ref.innerHTML.length == 0)  {
	
				ref.appendChild(ce("div","statusMessage","","Loading Locations"));
				var url = "index.php?module=loclist";
				protoReq(url,"writeSwitchLocation");

			}

		}

	}

}

function writeSwitchLocation(data) {

	 
	var ref = ge("locSwitchDiv");

	clearElement(ref);

	if (data.error) alert(data.error);
	else if (!data.location) ref.appendChild(ce("div","errorMessage","","Error retrieving locations"));
	else {

		for (var i=0;i<data.location.length;i++) {

			var loc = data.location[i];

			var row = ce("div","","",loc.name);
			setClick(row,"setLocation('" + loc.id + "')");

			ref.appendChild(row);

		}

	}

}

function setLocation(id) {

	var url = location.href;

	if (url.indexOf("?")==-1) url += "?switchLocation=" + id;
	else url += "&switchLocation=" + id;

	location.href = url;

}

function loadUnreadEmail() {

	//if (TOPMODULE!="email") setInterval("getUnreadEmail()","60000");

}

function getUnreadEmail() {

	//if uploading a file, bail
	if (siteFileUpload==1) return false;

	var url = "index.php?module=mbox&action=getunseen&mbox=INBOX";
	protoReq(url,"writeUnreadEmail");
	
}

function writeUnreadEmail(data) {

	var ref = ge("emailModTab");

	if (isData(data.unseen) && data.unseen!="0") ref.innerHTML = "Email (" + data.unseen + ")";
	else ref.innerHTML = "Email";

}


function historyManager(f,t){
	frames["hFrame"].location.href=site_url + "history.php?f="+f+"&t="+t;
}

function historyFunc(f){
	f = f+"()";
	eval(f);
}

function runPageLoader() {

	for (var i=0;i<pageLoadArr.length;i++) {

		eval(pageLoadArr[i]);

	}

	//empty page load array
	pageLoadArr = new Array();

}

function downloadFile(objPath) {
	siteViewFile(objPath,"other","file");
}

function siteViewFile(objid)
{

  setTimeout("runSiteViewFile('" + objid + "')","10");

}

function runSiteViewFile(objid)
{

	updateSiteStatus("Fetching object information");

	var p = new PROTO();
	p.setAsync(false);
	p.add("command","docmgr_object_getinfo");
	p.add("object_id",objid);
	var data = p.post(DOCMGR_API);

	clearSiteStatus();

	if (data.error)
	{
		alert(data.error);
		return false;
	}
	else if (!data.object)
	{
		alert("Unable to retrieve object information");
		return false;
	}
	else var obj = data.object[0];

  var editor = "";

	//they aren't pushing the alt key, so we are going to try to 
	//view the file inline if possible
  if (altpressed==false)
  {
   
    if (obj.object_type=="document") editor = "dmeditor";
    else editor = getEditorType(obj.name,obj.object_type);

  }

	//we can use an editor to open the file   
  if (editor)
  {
   
    var url = "index.php?module=editor&objectId=" + obj.id;
    var parms = centerParms(800,600,1) + ",resizable=1";  
    popupref = window.open(url,"_editor",parms);

    //if no popup, they probably have a popup blocker
    if (!popupref)
    {
      alert("It appears you have a popup-blocker installed.  Please disable for this site");
    }
    else
    {   
      popupref.focus();
    }

  } 
  //download like normal
  else
  {   
      
		//download the document as an html file
    if (altpressed==true && obj.object_type=="document")
    {

      var p = new PROTO();
      p.add("command","docmgr_document_get");
      p.add("object_id",obj.id);
      p.add("direct","1");
      p.redirect(DOCMGR_API);

    }
		//open a url in a new window
		else if (obj.object_type=="url")
		{

			//get the url to load
			var p = new PROTO();
			p.setAsync(false);
			p.add("command","docmgr_url_get");
			p.add("object_id",objid);
			var data = p.post(DOCMGR_API);

    	var parms = centerParms(800,600,1) + ",scrollbars=1,menubar=1,resizable=1,titlebar=1,toolbar=1,status=1,location=1";
    	var ref = window.open(data.url,"_blank",parms);

    	if (!ref) {
      	alert("It appears you have a popup blocker enabled.  You must disable it for this website to open Web Objects");
    	} else {
      	ref.focus();
    	}

		}
		//just download the file like normal
    else
    {   

      var p = new PROTO();
      p.add("command","docmgr_file_get");
      p.add("object_id",obj.id);
      p.redirect(DOCMGR_API);  

    }

  }
   
  //reset alt key
  altpressed = false;

}


function c2c(dest) {

	var parms = centerParms(350,175,1);
	var url = "index.php?module=clicktocall&calldest=" + dest;
	var ref = window.open(url,"_c2c",parms);
	ref.focus();

}

function setupModNav() {

	//nav div
	var refname = TOPMODULE + "ModuleNav";
	moduleNav = ge(refname);

	//control div 
	var ctrlname = TOPMODULE + "ModuleCtrl";
	moduleNavCtrl = ge(ctrlname);

	//footer div 
	var ctrlname = TOPMODULE + "ModuleFooter";
	moduleNavFooter = ge(ctrlname);

}

function showModNav() {

	var refname = TOPMODULE + "ModuleNav";
	moduleNav = ge(refname);

	if (!moduleNav) return false;

	clearElement(moduleNav);

	moduleNav.style.display = "block";

}

function showModCtrl() {

	var refname = TOPMODULE + "ModuleCtrl";
	moduleNavCtrl = ge(refname);

	clearElement(moduleNavCtrl);
	moduleNavCtrl.style.display = "block";

}

function hideModCtrl() {

	var refname = TOPMODULE + "ModuleCtrl";
	moduleNavCtrl = ge(refname);

	clearElement(moduleNavCtrl);
	moduleNavCtrl.style.display = "none";

}

function showModFooter() {

	var refname = TOPMODULE + "ModuleFooter";
	moduleNavFooter = ge(refname);

	clearElement(moduleNavFooter);

	moduleNavFooter.style.display = "block";

}

function hideModFooter() {

	var refname = TOPMODULE + "ModuleFooter";
	moduleNavFooter = ge(refname);

	clearElement(moduleNavFooter);

	moduleNavFooter.style.display = "none";

}

function siteToolbarBtn(txt,clickfunc,img,id) {

	if (BROWSER=="safari") var cn = "safariSiteToolbarBtn";
	else if (BROWSER=="ie") var cn = "ieSiteToolbarBtn";	
	else var cn = "siteToolbarBtn";

	var btn = ce("button",cn);
	btn.setAttribute("type","button"); 

	if (document.all) btn.style.marginTop = "-1px";

	if (clickfunc) {
		clickfunc = clickfunc + "; return false";
		setClick(btn,clickfunc);   
	}

	if (img) {
		var btnimg = ce("img");
		btnimg.setAttribute("align","left");
		btnimg.setAttribute("src",theme_path + "/images/icons/" + img);
		btn.appendChild(btnimg);
	}

	btn.appendChild(ctnode(txt));

	if (id) btn.setAttribute("id",id);

	return btn;

}

function siteToolbarSep() {

	var img = ce("img");
	img.setAttribute("src",theme_path + "/images/tbseparator.png");
	img.style.height = "10px";
	img.style.width = "10px";
	return img;

}

function addModNav(txt,clickfunc,imgsrc,id) {

	if (!moduleNav) return false;

	var linkdiv = ce("div","siteModNavLink");

	if (clickfunc) {
		clickfunc = clickfunc + "; return false";
		setClick(linkdiv,clickfunc);   
	}

	if (imgsrc) {

		var img = ce("img");
		img.setAttribute("src",theme_path + "/images/nav/" + imgsrc);
 
		linkdiv.appendChild(img);

	}

	linkdiv.appendChild(ctnode(txt));

	if (id) linkdiv.setAttribute("id",id);

	moduleNav.appendChild(linkdiv);

}

function addModSpacer() {

	if (!moduleNav) return false;

	var s = ce("div","lcSpacer");
	moduleNav.appendChild(s);
	return s;

}

function startLoginTimer() {

	logintimer = setInterval("loginTimer()","60000");

}

function loginTimer() {

	//if uploading a file, bail
	if (siteFileUpload==1) return false;

	var url = "index.php?module=logintimer";
	protoReq(url,"writeLoginTimer");

}

function writeLoginTimer(data) {

	if (data.time_left <= 0) logoutWarning();

}

function killScreen() {

	var ds = ge("screenKiller");
	var w = getWinWidth();
	var h = getWinHeight();

	if (document.all) h += 200;

	ds.style.width = w + "px";
	ds.style.height = h + "px";
	setClick(ds,"void(0)");

}

function liveScreen() {

	var ds = ge("screenKiller");
	ds.style.width = "0px";
	ds.style.height = "0px";

}

function logoutWarning() {

	//just kick us back to the login screen
	runTimeout();

	/*
	killScreen();

	var sm = ge("screenMessage");

	sm.style.width = "300px";
	sm.style.height = "84px";

	var xPos = (getWinWidth()-300) / 2;
	var yPos = (getWinHeight()-84) / 2;

	sm.style.left = xPos + "px";
	sm.style.top = yPos + "px";

	var msg = "Your session has expired.  Press \"Login\" to log back into the system";

	var cont = ce("div","logoutBox");
	var msgdiv = ce("div","logoutMessage","",msg);
	var btndiv = ce("div");

	btndiv.appendChild(createBtn("loginbtn","Login","runTimeout()"));

	cont.appendChild(msgdiv);
	cont.appendChild(btndiv);
	sm.appendChild(cont);

	//clear the timer
	clearInterval(logintimer);
	*/	

}

function runTimeout() {

	location.href = "index.php?timeout=true";

}

function siteFooterBtn(txt,clickfunc,img,id) {

	if (BROWSER=="safari") var cn = "safariSiteFooterBtn";
	else if (BROWSER=="ie") var cn = "ieSiteFooterBtn";	
	else var cn = "siteFooterBtn";

	var btn = ce("button",cn);
	btn.setAttribute("type","button"); 

	if (clickfunc) {
		clickfunc = clickfunc + "; return false";
		setClick(btn,clickfunc);   
	}

	if (img) {
		var btnimg = ce("img");
		btnimg.setAttribute("align","left");
		btnimg.setAttribute("src",theme_path + "/images/icons/" + img);
		btn.appendChild(btnimg);
	}

	btn.appendChild(ctnode(txt));

	if (id) btn.setAttribute("id",id);

	return btn;

}

//log into docmgr
function loginDocmgr() {

	//login, and run in the foreground.  we don't want to go any further until this is done
	if (DOCMGR_AUTHORIZE!=1) 
	{

		var p = new PROTO();
		p.setAsync(false);
		p.add("command","keepalive");

		var url = DOCMGR_API + "?login=" + USER_LOGIN + "&password=" + USER_PASSWORD;
		p.post(url,"writeLoginDocmgr");

	}
	
}

function writeLoginDocmgr(data) {
	
	if (data.error) alert("DocMGR: " + data.error);
	else 
	{

		DOCMGR_AUTHORIZE = 1;
		docmgrtimer = setInterval("docmgrKeepAlive()",DOCMGR_KEEPALIVE);

	}

}

function docmgrKeepAlive() {

		//if uploading a file, bail
		if (siteFileUpload==1) return false;

		var p = new PROTO();
		p.add("command","keepalive");
		p.post(DOCMGR_API,"writeDocmgrKeepAlive");

}

function writeDocmgrKeepAlive(data) {

	 	

	//keepalive threw an error.  bail
	if (data.error) {
		DOCMGR_AUTHORIZE = "";
		clearInterval(docmgrtimer);
		alert(data.error);
	}		

}

function fileExtension(fn) {

	var ext = "";

	var pos = fn.lastIndexOf(".");

	if (pos!=-1) ext = fn.substr(pos+1).toLowerCase();

	return ext;

}

function siteToolbarCell(txt,clickfunc,img,sub) {

  if (sub) 
		var cell = ce("div","toolbarSubRow");
	else
		var cell = ce("div","toolbarCell");

  if (clickfunc) setClick(cell,clickfunc);
  
  if (img) {
    var btnimg = ce("img");
    btnimg.setAttribute("align","left");
    btnimg.setAttribute("src",theme_path + "/images/icons/" + img);
    cell.appendChild(btnimg);
  }

  cell.appendChild(ctnode(txt));

  return cell;

}
 
function siteToolbarSep() {

  var img = ce("img");
  img.setAttribute("src",theme_path + "/images/tbseparator.png");
  img.style.height = "10px";
  img.style.width = "10px"; 
  return img;

}
 
/***********************************************
  FUNCTION: handleKeyUp
  PURPOSE:  deactivates the control key setting
************************************************/
function siteHandleKeyUp(evt) {

  if (!evt) evt = window.event;

  if (evt.keyCode=="18") altpressed = false;

}

/***********************************************
  FUNCTION: handleKeyUp
  PURPOSE:  deactivates the control key setting
************************************************/
function siteHandleKeyDown(evt) {

  if (!evt) evt = window.event;

  if (evt.keyCode=="18") altpressed = true;

}


function getEditorType(name,type)
{
 
  var ext = fileExtension(name);
  var editor;
    
  //type,ext
  if (type=="document") editor = "dmeditor";
  else
  {   
      
    var p = new PROTO();
    p.setAsync(false);  
    p.setProtocol("XML");
    var extensions = p.post("config/extensions.xml");
   
    var ow = new Array();

    for (var i=0;i<extensions.object.length;i++)
    {

      var e = extensions.object[i];

      //we have a match, get the handler
      if (e.extension==ext)
      {
        if (isData(e.open_with)) ow = e.open_with.toString().split(",");
        break;
      }

    }

    //loop through the desired editors
    for (var i=0;i<ow.length;i++)
    {

      //regular editor, just use that
      if (ow[i]!="dsoframer") 
      {
        editor = ow[i];
        break;
      }
      else if (ow[i]=="dsoframer" && document.all && DSOFRAMER_ENABLE==1 && SETTING["editor"]=="msoffice")
      {
        editor = "dsoframer";
        break;
      }

    }

  }
   
  return editor;

}

function openMiniB(mode,path,filter,editor)
{

	if (mode=="open") var height = 460;
	else height = 480;

	//make space for chrome's address bar
	if (BROWSERPROG=="chrome") height += 45;

	var parms = centerParms(600,height,1) + ",resizable=no,scrollbars=no";

	var url = "index.php?module=minib&mode=" + mode;
	if (editor) url += "&editor=" + editor;
	if (filter) url += "&objectTypeFilter=" + filter;

	//figure out if our path is an id or an actual path
	if (path) 	
	{

		var nums = returnNumbers(path);

		//string is all numbers if their lengths match
		if (nums.length==path.length) url += "&objectId=" + path;
		else url += "&browsePath=" + path;

	}

	var ref = window.open(url,"_minib",parms);
	ref.focus();

}
