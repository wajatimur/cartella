var localsaved;
var editmode = "normal";					//can be "normal" or "web"
var oa;
var makenewdoc = "";
var directpath;

function DSOFRAMER()
{

	/*********************************************
		FUNCTION: loadPage
		PURPOSE:  initialize the page
	*********************************************/
	this.load = function()
	{
	
		directpath = ge("directPath").value;
	
		if (!curobj) makenewdoc = 1;
	
		EDITOR.loadEditor();
	
		oa = document.all.oframe;
	
		//stop here if controler is not loaded
		if (oa.readyState=="0") return false;
	
		//init the control and hide the menubar
		//oa.Activate();
		oa.Menubar = false;
	
		setTimeout("EDITOR.LoadOfficePage('" + makenewdoc + "')","1");		//for some reason, it allows the editor to load
	
		updateSiteStatus("Loading");
	
	}
	
	this.loadEditor = function()
	{
	
		//create the editor object
		var str = "<object id=\"oframe\" width=\"100%\" height=\"95%\" ";
		str += " classid=\"clsid:00460182-9E5E-11D5-B7C8-B8269041DD57\" ";
		str += " codebase=\"" + site_url + "controls/dsoframer.ocx#version=2,2,1,2\">";      
		str += " <param name=\"BorderColor\" value=\"-2147483632\">";
		str += " <param name=\"BackColor\" value=\"-2147483643\">";
		str += " <param name=\"ForeColor\" value=\"-2147483640\">";
		str += " <param name=\"TitlebarColor\" value=\"-2147483635\">";
		str += " <param name=\"TitlebarTextColor\" value=\"-2147483634\">";
		str += " <param name=\"BorderStyle\" value=\"1\">";
		str += " <param name=\"Titlebar\" value=\"0\">";
		str += " <param name=\"Toolbars\" value=\"1\">";
		str += " <param name=\"Menubar\" value=\"1\">";
		str += " <param name=\"MenuAccelerators\" value=\"1\">";
		str += " </object>";
	
		ge("editorDiv").innerHTML = str;
	
	}
	
	/*************************************************
		file open/save functions
	*************************************************/
	
	/****************************************************
		FUNCTION: LoadOfficePage
		PURPOSE:  loads a file into dsoframer.  Either loads 
							existing file, a template, or spawns the
							file type chooser
	****************************************************/
	
	this.LoadOfficePage = function(newdoc) {
	
		updateSiteStatus("Opening File");
	
		//disable all the filecommands
		EDITOR.disableFileCommands();
	
		//open a new document
		if (newdoc) {
	
			clearSiteStatus();
			EDITOR.createNew();
			oa.Activate();
	
		} else {
	
			//open existing document
			if (curobj && !directpath) 
			{
				EDITOR.openDoc();
			}
			else if (directpath) 
			{
	
				//passed a direct merge file...
				var prog = EDITOR.getObjProg();		
	
				oa.Open(directpath,false,prog);
	
				//show the menubar for non-ribbon microsoft office
				if (parseFloat(oa.ActiveDocument.Application.Version) < parseFloat("12.0")) oa.Menubar = true;
	
				clearSiteStatus();
				oa.Activate();
	
				//blank it out to keep it from opening again
				directpath = "";
	
			}
			else {
	
				if (parseFloat(oa.ActiveDocument.Application.Version) >= parseFloat("12.0")) {
					var url = site_url + "/controls/template.docx";
				} else {
					var url = site_url + "/controls/template.doc";
			  }
	
				remotesaved = 0;
	
				//var url = site_url + "/controls/template.html";
				//open file with appropriate program not readonly
				var prog = EDITOR.getObjProg();		
				oa.Open(url,false,prog);
	
				//show the menubar for non-ribbon microsoft office
				if (parseFloat(oa.ActiveDocument.Application.Version) < parseFloat("12.0")) oa.Menubar = true;
	
				clearSiteStatus();
				oa.Activate();
	
			}
	
		}
	
	}
	
	this.openDoc = function() 
	{

  	//we need to see if the file is locked
  	//api xml
  	var xml = "<data>";
  	xml += cdata("command","docmgr_object_getinfo");
  	xml += cdata("object_id",curobj);
  	xml += "</data>";

  	var url = DOCMGR_API + "?apidata=" + xml;
  	loadReq(url,"EDITOR.writeOpenDoc");

	}
	
	this.writeOpenDoc = function(resp)
	{
	
		var data = parseXML(resp);	 
		
		if (!data.object)
		{
	
			alert("Error retrieving object information");
			return false;
	
		} else var obj = data.object[0];

    if (data.locked && data.locked=="t") readonly = "locked";
    else if (data.bitmask_text=="view") readonly = "view";
    else readonly = "";

    setReadOnly();
	
		if (obj.locked=="t" && obj.lock_owner!=USER_ID) 
		{
			readonly = "locked";
			remotesaved = "";
		}
		else if (obj.bitmask_text=="view")
		{
			readonly = "view";
			remotesaved = "";
		} 
		else 
		{
			readonly = "";
			remotesaved = 1;
		}
	
		setReadOnly();
	
		var xml = "<data>";
	
		if (objtype=="document") 
		{
	
			xml += cdata("command","docmgr_document_get");
			xml += cdata("object_id",curobj);
			xml += cdata("direct","1");
			xml += cdata("lock","1");
			xml += cdata("timeout","1800");
	
		} else 
		{
	
			xml += cdata("command","docmgr_file_get");
			xml += cdata("object_id",curobj);
			xml += cdata("lock","1");
			xml += cdata("timeout","1800");
	
		}
	
		xml += "</data>";
	
		var url = site_url + DOCMGR_API + "?apidata=" + xml;
	
		//var url = site_url + "/controls/template.html";
		//open file with appropriate program not readonly
		var prog = EDITOR.getObjProg();		
		oa.Open(url,false,prog);
	
		//show the menubar for non-ribbon microsoft office
		if (parseFloat(oa.ActiveDocument.Application.Version) < parseFloat("12.0")) oa.Menubar = true;
	
		clearSiteStatus();
		oa.Activate();
	
	
	}
	
	
	/****************************************************
		FUNCTION: mbSelectObject
		PURPOSE:  called from minib module, returns selected
							file information
	****************************************************/
	this.mbSelectObject = function(res) {
	
		if (!res) {
	
			alert("Error selecting object to load");
	
		} else {
	
			//store our globals
			objname = res.name;
			parentpath = res.parent;
			objpath = res.path;
	
			if (mbmode=="save") 
			{
				updateSiteStatus("Saving File");
				setTimeout("runServerSave()","10");
			} else 
			{
	
				//store additional info if we are selecting a new object
				curobj = res.id;
	
				//if in tea and there's a contact id, reload the entire module so everything gets merged in
				if (TEA_ENABLE=="1" && ge("contactId").value.length > 0) {
	
					//disalbe the state checkers
					window.onbeforeunload = null;
					window.onunload = null;
	
					updateSiteStatus("Please wait");
					var arr = ge("contactId").value.split(",");
	
					//form the url and reload teh page
					var url = "index.php?module=editor&objectId=" + res.id;
					for (var i=0;i<arr.length;i++) url += "&contactId[]=" + arr[i];
					location.href = url;
	
				} else 
				{
	
					updateSiteStatus("Opening File");
					setTimeout("LoadOfficePage()","10");
	
				}
	
			}
	
		}
	
	}
	
	
	
	
	/****************************************************
		FUNCTION: runServerSave
		PURPOSE:  actually posts the file to the server
	****************************************************/
	this.runServerSave = function(overwrite) {
	
		EDITOR.hideToolbars();
		updateSiteStatus("Saving File");
	
		//see if they file exists already
		if (!overwrite && checkObjectExists()) overwrite = 1;
		else if (!overwrite) overwrite = "0";
	
		//get the filename and parent path
		var arr = objpath.split("/");
		var fn = arr.pop();
		var parent = arr.join("/");
	
		//make sure the file and it's extension are okay for this app
		if (remotesaved!=1 && objtype!="document") 
		{
	
			fn = setFileExtension(fn);
	
			//update the object path with the file and extension
			objpath = parent + "/" + fn;
	
		}
	
		//api xml
		var xml = "<data>";
		xml += cdata("command","docmgr_file_savedsoframer");
		xml += cdata("parent_path",parent);
		xml += cdata("name",fn);
		xml += cdata("overwrite",overwrite);
		xml += cdata("lock","1");
		xml += cdata("revision_notes",savenotes);
	
		if (curobj) 
		{
			xml += cdata("object_id",curobj);
			xml += cdata("object_type",objtype);
		} 
	
		xml += "</data>";
	
		//assemble the url
		var url = site_url + DOCMGR_API + "?apidata=" + xml;
	
		//post the file
		oa.HttpInit(); 
		oa.HttpAddPostCurrFile("uploadfile",fn);
		oa.HttpPost(url);
		oa.Activate();
	
		clearSiteStatus();
	
		//make sure it saved it properly, and set curobj
		if (!curobj && !checkObjectExists(1))
		{
			
			alert("File was not saved correctly");
	
		} else
		{
	
			//edraw methods
			//oa.HttpInit();
			//oa.HttpAddPostOpenedFile(fn);
			//oa.HttpPost(url);
	
			//store we saved it
			remotesaved = 1;
	
		}
	
	}
	
	/****************************************************
		dialog functions
	****************************************************/
	
	/****************************************************
		FUNCTION: printDocument
		PURPOSE:  spawns print dialog
	****************************************************/
	this.print = function() {
	
		EDITOR.hideToolbars();
	 	oa.showdialog(4);		//dsoDialogPrint
	
		//handle task and contact processing for tea
		if (TEA_ENABLE=="1") {
	
		  if (ge("taskId").value.length > 0) {
		
		    //mark complete if it's a task
		    if (confirm("Mark this task complete?")) {
		
		      updateSiteStatus("Marking task complete");
		      var url = "index.php?module=savetask&action=setcomplete&taskId=" + ge("taskId").value;
		      if (ge("contactId").value.length > 0) url += "&contactId=" + ge("contactId").value;
		      loadReq(url,"writeTaskComplete");
		
		    }
		
		  } 
			else if (ge("contactId").value.length > 0) 
			{
	
	    	//basically we run the merge again, but don't output anything
		      var url = "index.php?module=recordmsoffice&contactId=" + ge("contactId").value;
		      if (curobj) url += "&objectId=" + curobj;
		      loadReq(url);
		
		  }  
	
		}
	
	}
	
	//for handling task complete requests
	this.writeTaskComplete = function(resp) {
	
		var data = parseXML(resp);
	   
	  clearSiteStatus();
	
	  if (data.error) alert(data.error);
	  else {
	
	    //success.  blank out the task Id so it doesn't happen again
	    ge("taskId").value = "";
	
	  }
	   
	}
	/****************************************************
		FUNCTION: printPreview
		PURPOSE:  show print preview
	****************************************************/
	this.printPreview = function() {
	
		EDITOR.hideToolbars();
		oa.ActiveDocument.PrintPreview();
	
	}
	
	/****************************************************
		FUNCTION: showProperties
		PURPOSE:  show document properties window
	****************************************************/
	this.showProperties = function() {
		EDITOR.hideToolbars();
		oa.showdialog(6);		//dsoDialogPrint
	}
	
	/****************************************************
		FUNCTION: openLocalFile
		PURPOSE:  open a file on the local filesystem
	****************************************************/
	this.openLocalFile = function() {
		EDITOR.hideToolbars();
	 	oa.showdialog(1);		//dsoDialogOpen
		localsaved = 1;
		oa.Activate();
	}
	
	/****************************************************
		FUNCTION: createNewFile
		PURPOSE:  spawns new file dialog for creating
							office file
	****************************************************/
	this.createNew = function()
	{
	
		EDITOR.hideToolbars();
		oa.showdialog(0);		//dsoDialogNew
		oa.Activate();
		localsaved = 0;
		remotesaved = 0;
	
	}
	
	/****************************************************
		FUNCTION: saveLocalCopy
		PURPOSE:  saves a new copy of the file locally
	****************************************************/
	this.saveLocalCopy = function() {
	
		localsaved = 0;
		EDITOR.saveLocalFile();
	
	}
	
	/****************************************************
		FUNCTION: saveLocalFile
		PURPOSE:  saves the document to local filesystem
	****************************************************/
	this.saveLocalFile = function() {
	
		//oa.ActiveDocument.Application
		EDITOR.hideToolbars();
	
		//if already saved, just save the document outright
		if (localsaved == 1) {
			oa.Save();
	 	} else {
			var ref = oa.showdialog(3);		//dsoDialogSaveCopy
			localsaved = 1;
		}
	
		oa.Activate();
	
	}
	
	/****************************************************
		FUNCTION: changePageLayout
		PURPOSE:  shows page layout dialog
	****************************************************/
	this.pageLayout = function() {
		EDITOR.hideToolbars();
	 	oa.showdialog(5);		//dsoDialogPageLayout
	}
	
	/*********************************************
		utility functions
	*********************************************/
	
	/****************************************************
		FUNCTION: disableFileCommands
		PURPOSE:  keeps anyone from using "File" menu so
							they have to use ours
	****************************************************/
	this.disableFileCommands = function() {
	
		//disable all file commands
		oa.EnableFileCommand(0) = false;
		oa.EnableFileCommand(1) = false;
		oa.EnableFileCommand(2) = false;
		oa.EnableFileCommand(3) = false;
		oa.EnableFileCommand(4) = false;
		oa.EnableFileCommand(5) = false;
		oa.EnableFileCommand(6) = false;
		oa.EnableFileCommand(7) = false;
		oa.EnableFileCommand(8) = false;
	
	}
	
	/*********************************************
		FUNCTION: checkState
		PURPOSE:  checks if file needs to be saved
	*********************************************/
	this.checkState = function() {
	
		//this doesn't seem to update after the file is saved
		if (oa.ActiveDocument.Saved==false) {
			return "You have made changes to this document since it was last saved.";
		} 
	
	}
	
	/****************************************************
		FUNCTION: hideToolbars
		PURPOSE:  hides expandable menu options after an
							option has been clicked
	****************************************************/
	this.hideToolbars = function() {
	
		var arr = ge("editorToolbar").getElementsByTagName("div");
	
		for (var i=0;i<arr.length;i++) {
			if (arr[i].className=="toolbarSub") arr[i].style.display = "none";
		}
	
	}
	
	/****************************************************
		FUNCTION: setupDD
		PURPOSE:  sets up a frame to show our dropdown menus on, because
							ie won't let us show it over the regular control
	****************************************************/
	this.setupDD = function(e) {

		var ref = getEventSrc(e);
		var cn = getObjClass(ref);

		if (cn!="toolbarCell") return false;
		if (ge("ddFrame").style.display == "block") return false;

		//get the main menu
		var subref = ref.getElementsByTagName("div")[0];

		      subref.style.display = "block";
					subref.style.marginTop = "15px";
					subref.style.marginLeft = "-81px";
					subref.style.position = "absolute";
					subref.style.zIndex = "500";
					subref.style.border = "1px solid darkslategray";
					subref.style.width = "196px";

					var arr = subref.getElementsByTagName("div");
					for (var i=0;i<arr.length;i++)
					{
						setMouseOver(arr[i],"EDITOR.setHover(event)");
						setMouseOut(arr[i],"EDITOR.setNormal(event)");
						arr[i].style.backgroundColor = "white";
					}
	
		      var ref = ge("ddFrame");
		      ref.style.display="block";
					ref.style.position = "absolute";
							
		      if (subref.id=="documentSub") {
		              ref.style.width="197px";
		              ref.style.height="199px";
		              ref.style.top="22px";
		              ref.style.left="12px";
		      } else if (subref.id=="mergeSub") {
		              ref.style.width="196px";
		              ref.style.height="355px";
		              ref.style.top="22px";
		              ref.style.left="124px";
		      }
	 
	}

	this.setHover = function(e)
	{
		var ref = getEventSrc(e);
		ref.style.backgroundColor = "lightyellow";
		ref.style.cursor = "pointer";
	}

	this.setNormal = function(e)
	{
		var ref = getEventSrc(e);
		ref.style.backgroundColor = "white";
		ref.style.cursor = "normal";
	}
	
	/****************************************************
		FUNCTION: hideDD
		PURPOSE:  hide the dropdown frame
	****************************************************/
	
	this.hideDD = function(e) {
	
		ref = getEventSrc(e);

		var cn = getObjClass(ref);

		if (cn!="toolbarCell") return false;

		//get the main menu
		var sub = ref.getElementsByTagName("div")[0];

		if (!sub.id) return false;

		sub.style.display = "none";
	
		var ref = ge("ddFrame");
		ref.style.display = "none";

	}
	
	
	
	/****************************************************
		FUNCTION: getObjProg
		PURPOSE:  determines which office program to use
							based on file extension
	****************************************************/
	this.getObjProg = function() {
	
		var prog;
	
		var ext = fileExtension(objname);
	
		if (ext=="xls" || ext=="xlsx") prog = "Excel.Sheet";
		else if (ext=="ppt" || ext=="pptx") prog = "PowerPoint.Show";
		else prog = "Word.Document";
	
		return prog;
	
	}
	
	
	/****************************************************
		FUNCTION: newFileExtension
		PURPOSE:  figure out file extension for our new file
							when saving
	****************************************************/
	this.setFileExtension = function(fn) {
	
			//figure out what it should be
			var app = oa.ActiveDocument.Application.Name;
			var ver = oa.ActiveDocument.Application.Version;
	
			var ext = "";
	
			if (app=="Microsoft Word") {
	
				ext = "doc";
				var saveformat = oa.ActiveDocument.SaveFormat;
	
				if (saveformat==12) ext += "x";					//word doc
				else if (saveformat==13) ext += "m";		//macro enabled word document
				else if (saveformat==23) ext = "odt";		//OASIS document
	
			} else if (app=="Microsoft Excel") {
	
				ext = "xls";
				var saveformat = oa.ActiveDocument.Application.ActiveWorkbook.FileFormat;
				alert(saveformat);
	
				if (saveformat==51) ext += "x";					//xml doc
				else if (saveformat==52) ext += "m";		//macro enabled xml doc
				else if (saveformat==60) ext = "ods";		//OASIS document
	
			}
			else if (app=="Microsoft PowerPoint") {
	
		 		ext = "ppt";
		
		  	//I can't find a way to get a document version for powerpoint.  So, we have ot use
		  	//this much less reliable way
		  	if (parseFloat(ver) < parseFloat("12.0")) ext += "x";
	
			}
	
			//figure out what it is
			var curext = fileExtension(fn);
	
			//if none typed, just add it on, otherwise replace the existing one
			if (curext) {
				var pos = fn.lastIndexOf(".");
				fn = fn.substr(0,pos);
			}
	
			//add the extension
			fn += "." + ext;
	
			return fn;
	
	}
	
	/****************************************************
		FUNCTION: checkObjectExists
		PURPOSE:  figure out if a file is alredy on the system
	****************************************************/
	
	this.checkObjectExists = function(set) 
	{
	
		var xml = "<data>";
		xml += cdata("command","docmgr_object_getid");
		xml += cdata("path",objpath);
		xml += "</data>";
	
		var resp = loadReqSync(DOCMGR_API + "?apidata=" + xml);
		var data = parseXML(resp);
	
		if (data.object_id) 
		{
			if (set) curobj = data.object_id;
			return true;
		}
		else return false;
	
	}
	
	/****************************************************
		FUNCTION: insertImage
	****************************************************/
	this.insertImage = function() {
	
		if (curobj) var ip = parentpath + "/" + objname + "/.object" + curobj + "_storage";
		else var path = "/Users/" + USER_LOGIN + "/.temp_storage";
	
		var url = "index.php?module=pickme&path=" + path;
		var parms = centerParms(800,600,1);
	
		var ref = window.open(url,"_pickme",parms);
		ref.focus();
	
	}
	
	this.dialogSelectImage = function(url) {
	
		//document.oframe.ActiveDocument.Shapes.AddPicture imgUrl, false, true, 0,0100100, document.oframe.ActiveDocument.Application.Selection.Range
		//document.oframe.ActiveDocument.Shapes(1).ZOrder 4 document.oframe.ActiveDocument.Shapes (1). ZOrder 4
	
		//var url = "http://www.google.com/intl/en_ALL/images/logo.gif";
		if (url) {
			oa.ActiveDocument.Application.Selection.InlineShapes.AddPicture(url,true,false);
		} else {
			alert("Error selecting image");
		}
	
		/*
		//FileName:= _
		, LinkToFile:=True, _
		SaveWithDocument:=False
		Selection.InlineShapes.AddPicture FileName:= _
		"http://www.google.com/intl/en_ALL/images/logo.gif", LinkToFile:=True, _
		SaveWithDocument:=False
		*/
	
	}
	
	
	this.insertMergeField = function(val) {
	
	  //put a space at the end
	  val += " ";
	
	  //hide the toolbars
	  hideToolbars();
	
	  //insert the text into the document
	   var wrdDoc = oa.ActiveDocument;
	   wrdDoc.ActiveWindow.Selection.TypeText(val);
	   oa.Activate();
	
	}
	
	this.setRow = function() {
	
		var ref = getEventSrc(window.event);
		setClass(ref,"toolbarSubRowHover");
	
	}
	
	this.unsetRow = function() {
	
		var ref = getEventSrc(window.event);
		setClass(ref,"toolbarSubRow");
	
	}

	this.setFrameSize = function()
	{
	}

}
