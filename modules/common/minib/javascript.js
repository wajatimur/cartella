
var MB;

function loadPage() {

	MB = new miniB();
	MB.create();
	self.focus();

}


function miniB() {

	//pull values from form
	var curpath = ge("browsePath").value;
	var ceiling = BROWSE_CEILING;
	var filter = ge("browseFilter").value;
	var mode = ge("runMode").value;
	var typefilter = ge("objectTypeFilter").value;
	var objname = ge("objectName").value;

	//set some defaults if not found
	if (!ceiling) ceiling = "/";
	if (!filter) filter = "*";
	if (!mode) mode = "open";
	if (!typefilter) typefilter = "*";

	//globals
	this.browsepath = curpath;
	this.objname = objname;
	this.filter = filter;
	this.typefilter = typefilter;
	this.width = "600";
	this.height = "480";
	this.ceiling = ceiling;
	this.mode = mode;
	this.ceilswitch = "";

	//reference to our window
	this.win;					//window reference
	this.toolbar;			//toolbar
	this.list;				//file/folder list
	this.tableRow;		//row containing list columns
	this.pathdiv;			//div containing path form
	this.controls;		//buttons

	/****************** methods ****************/

	this.create = function() {

		this.win = ge("container");

		//setup the divs
		this.setupToolbar();
		this.setupPathDiv();
		this.setupTypeDiv();
		this.setupControls();
		this.setupList();

	};


	/**************** setup functions ****************/

	this.setupToolbar = function() 
	{

		var mydiv = ge("mbObjectToolbar");

		var headdiv = ce("div","winHeader","","DocMGR File Chooser");

		//make our dropdown for ceiling switching
		this.ceilswitch = createSelect("ceilingSwitch");
		setChange(this.ceilswitch,"MB.switchCeiling()");

    //assemble the xml
		var p = new PROTO();
    p.add("command","docmgr_bookmark_get");   
		p.post(DOCMGR_API,createMethodReference(this,"writeBookmarks"));

		var switchdiv = ce("div","browseSwitch");
		switchdiv.appendChild(ctnode("Switch To: "));
		switchdiv.appendChild(this.ceilswitch);

		if (this.filter=="*" || !this.filter) var filtertxt = "Current Filter: None";
		else filtertxt = "Current Filter (" + this.filter + ")";
		var filterdiv = ce("div","filterDesc","",filtertxt);

		mydiv.appendChild(switchdiv);
		mydiv.appendChild(headdiv);

	};

	this.writeBookmarks = function(data) 
	{

		if (data.error) alert(data.error);
		else if (!data.bookmark) alert("Error.  No bookmarks found");
		else {

			MB.ceilswitch[0] = new Option("Browse To...","0");

			for (var i=0;i<data.bookmark.length;i++) {

				MB.ceilswitch[i+1] = new Option(data.bookmark[i].name,data.bookmark[i].object_path);

			}

			MB.ceilswitch.value = MB.browsepath;

		}

	};

	/*******************************************************************
		FUNCTION: switchCeiling
		PURPOSE:	switches the current path to the selected ceiling
	*******************************************************************/
	
	this.switchCeiling = function() {

		var cs = ge("ceilingSwitch");

		//reset the browse window
		MB.ceiling = cs.value;
		MB.browsepath = cs.value;

		clearElement(ge("mbObjectTableRow"));

		//setup a div to show each level
		//initialize 3 columns by default for our list
		for (var i=0;i<3;i++) MB.createCell("miniCell" + i,"");

		MB.browse(cs.value,1,2);

		//if not an initial load, update our settings once ajax is over with
		endReq("MB.updateDisplay('1')",100);

	};

	/*******************************************************************
		FUNCTION: setupList
		PURPOSE:	sets up the div containing our columns of object lists
	*******************************************************************/
	this.setupList = function() {

		this.list = ge("mbObjectList");

		if (document.all) var tbl = ce("<table cellpadding=\"0\" cellspacing=\"0\" id=\"mbObjectTable\">");
		else {
			var tbl = ce("table","","mbObjectTable");
			tbl.setAttribute("cellpadding","0");
			tbl.setAttribute("cellspacing","0");
		}

		var tbd = ce("tbody");
		this.tableRow = ce("tr","","mbObjectTableRow");

		//setup a div to show each level
		//initialize 3 columns by default for our list
		for (var i=0;i<3;i++) MB.createCell("miniCell" + i,"");

		//if they are equal, just start out browsing the top level of the ceiling		
		if (this.browsepath==this.ceiling) {

			this.browse(this.browsepath,1);

		} else {

			//convert path into an array
			if (this.ceiling=="/") var path = this.browsepath.substr(1);
			else var path = this.browsepath.substr(this.ceiling.length + 1);
			var patharr = path.split("/");

			//start with the root
			var pathstr = this.ceiling;
			this.browse(pathstr,1);

			if (path)
			{
	
				//get the data only AFTER all the divs have been setup
				for (var i=0;i<(patharr.length);i++) {
	
					if (pathstr=="/") pathstr += patharr[i];
					else pathstr += "/" + patharr[i];											//regular addition on the rest of the loops
	
					this.browse(pathstr,1);
	
				}

			}

		}

		//if not an initial load, update our settings once ajax is over with
		endReq("MB.updateDisplay('1')",100);

		tbd.appendChild(this.tableRow);
		tbl.appendChild(tbd);
		this.list.appendChild(tbl);

		//show the current filename if we have one
		if (this.objname) ge("fileName").value = this.objname;

	};

	/*******************************************************************
		FUNCTION: setupPathDiv
		PURPOSE:	sets up the div containing path textbox for the user
							to see path of selected object
	*******************************************************************/

	this.setupPathDiv = function() {
		
		var pd = ge("mbPathDiv");

		var fileheader = ce("div","currentHeader","","Name: ");
		var info = ce("div","currentInfo");

		var filetb = createTextbox("fileName");

		if (this.mode=="open") 
		{
			if (document.all) filetb.disabled = true;
			else filetb.readonly = true;
		}

		pd.appendChild(fileheader);
		pd.appendChild(filetb);
		pd.appendChild(createCleaner());

	};

	this.setupTypeDiv = function() {

		//if we are in save mode, and not spawned by dsoframer, get other save options
		if (this.mode=="save" && ge("editor").value!="dsoframer")
		{

			var p = new PROTO();
			p.setProtocol("XML");
			p.get("config/extensions.xml",createMethodReference(this,"writeTypeDiv"));

		}

	};

	this.writeTypeDiv = function(data)
	{

		//figure out if any are set
		var arr = new Array();

		for (var i=0;i<data.object.length;i++)
		{
			if (data.object[i].allow_dmsave==1) arr.push(new Array(data.object[i].proper_name,data.object[i].extension));
		}

		var pd = ge("mbTypeDiv");

		//we have additional save as file options available.  create a dropdown listing them
		if (arr.length > 0)
		{

			var fileheader = ce("div","currentHeader","","Type: ");

			var sel = createSelect("fileType");
			sel[0] = new Option("DocMGR Document","docmgr");

			for (var i=0;i<arr.length;i++)
			{
				sel[i+1] = new Option(arr[i][0],arr[i][1]);
			}

			pd.appendChild(fileheader);
			pd.appendChild(sel);
			pd.appendChild(createCleaner());

			//set default save type
			if (DMEDITOR_DEFAULT_SAVE!="DMEDITOR_DEFAULT_SAVE")
			{
				sel.value = DMEDITOR_DEFAULT_SAVE;
			}

		}
		else
		{

			//no options available, just save as docmgr
			var hid = createHidden("fileType","docmgr");
			pd.appendChild(hid);

		}

	};

	this.setupControls = function() {

		var mydiv = ge("mbObjectControls");

		if (this.filter=="*" || !this.filter) var filtertxt = "Current Filter: None";
		else filtertxt = "Current Filter (" + this.filter + ")";
		var filterdiv = ce("div","filterDesc","",filtertxt);

		var selbtn = createBtn("selbtn","Select","MB.selectObject();return false");
		var cancelbtn = createBtn("cancelbtn","Cancel","MB.close()");

		mydiv.appendChild(filterdiv);
		mydiv.appendChild(selbtn);
		mydiv.appendChild(cancelbtn);

	};
	
	this.selectObject = function(path,objtype) {

		//if passed a path, update it
		if (path) MB.updateSelectedPath(path,objtype);

		//make sure there's a filename mattching our app
		if (!MB.checkFileName()) return false;

		//if there's a filename, use it instead of the stored path
		var fn = ge("fileName").value;
		MB.objExt = fileExtension(fn);

		if (window.opener.mbSelectObject) 
		{

			//pass all our object information back
			var ret = new Array();
			ret.id = MB.objId;

			//if picking a file, show the file name.  otherwise just show diretory information
			if (fn) {
				ret.name = fn;
				ret.path = MB.browsepath + "/" + fn;
				var savepath = MB.browsepath;
			} else {
				ret.name = "";
				ret.path = MB.browsepath;
				var savepath = ret.path;
			}

			ret.parent = MB.browsepath;
			ret.type = MB.objType;
			ret.ext = MB.objExt;

			//save the file as this type, as long as not in dsoframer
			if (this.mode!="open" && ge("editor")!="dsoframer") ret.savetype = ge("fileType").value;

			//store this in a session so we can jump to it later
			loadReq("index.php?module=minib&action=savePath&savePath=" + savepath);

			//call the save function in the opening window
			window.opener.mbSelectObject(ret);
			MB.close();

		}

	};

	this.close = function() {

		self.close();

	};

	/**************** everything below here needs to be called and call others statically (MB.funcname)**********************/

	/*******************************************************************
		FUNCTION: browse
		PURPOSE:	makes call to get objects in the requested column
	*******************************************************************/
	this.browse = function(path,init,ran) {

		//if not an initial load, update our settings once ajax is over with
		if (!init) {
			MB.updateSelectedPath(path);
			endReq("MB.updateDisplay()",100);
		}

		MB.browsepath = path;

		//set the user know we are doing something
		if (!init) {

			var cell = ge("miniCell" + MB.calCellNum(path));
			if (cell) {
				clearElement(cell);
				cell.appendChild(ce("div","statusMessage","","Updating..."));
			}
		}

		//assemble the xml
		var p = new PROTO();
		p.add("command","docmgr_search_browse");
		p.add("path",path);
		p.add("no_paginate","1");
		p.post(DOCMGR_API,"MB.browseResults");

	};

	/******************************************************************
		FUNCTION: browseResults
		PURPOSE:	result handler for column list data
	*******************************************************************/
	this.browseResults = function(data) {

		if (data.error) alert(data.error);
		else 
		{

			//get our column to update
			var ref = ge("miniCell" + MB.calCellNum(data.current_object_path));

			//doesn't exist, make it
			if (!ref) {
				//if we need another table cell to add data, make it	
				ref = MB.createCell("miniCell" + MB.calCellNum(data.current_object_path),"Updating...");
			}

			//get rid of existing data	
			clearElement(ref);

			//nothing found
			if (!data.object) 
			{
				ref.appendChild(ce("div","errorMessage","","No Results Found"));
			} 
			else 
			{

				//update our selected object to this collection
				MB.updateObjProp("collection","",data.current_object_id);

				//a container for our data that will scroll	
				var cont = ce("div","mbObjectCellContainer");

				for (var i=0;i<data.object.length;i++) {
	
					var obj = data.object[i];

					//check the type filter.  if not match, skip it
					if (!MB.checkTypeFilter(obj.object_type)) continue;

					//setup the row id
					if (MB.browsepath=="/") var curpath = "/" + obj.name;
					else var curpath = data.current_object_path + "/" + obj.name;
	
					//create the row and image
					var row = ce("div","mbObjectRow","row_" + curpath);
		 			var img = ce("img");
	  				img.setAttribute("src",theme_path + "/images/docmgr/fileicons/" + obj.icon);
	
					//put them together
					row.appendChild(img);
					row.appendChild(ctnode(obj.name.substr(0,24)));

					//update the properties for our object if clicked on
					var updateprop = "MB.updateObjProp('" + obj.object_type + "','" + obj.extension + "','" + obj.id + "')";
	
					//set an action for collections and files
					if (obj.object_type=="collection") setClick(row,"MB.browse(\"" + curpath + "\");" + updateprop);
					else if (obj.object_type=="file") {

						//ifit's a file, make sure it's extension matches our filters
						if (MB.checkFilter(obj.extension)) {
							setClick(row,"MB.updateSelectedPath(\"" + curpath + "\",'file');" + updateprop);
							setDblClick(row,updateprop + ";MB.selectObject(\"" + curpath + "\",'file');");
						}
						else setClass(row,"mbObjectRowDisabled");

					}
					else if (obj.object_type=="document") {
						setClick(row,"MB.updateSelectedPath(\"" + curpath + "\",'document');" + updateprop);
						setDblClick(row,updateprop + ";MB.selectObject(\"" + curpath + "\",'document')");
						setClass(row,"mbObjectRow");
					} else {
						setClass(row,"mbObjectRowDisabled");
					}

					cont.appendChild(row);			
	
				}
	
				ref.appendChild(cont);

			}

	
		}
	
	};

	this.updateDisplay = function(init) {

			//only look for stale elements after the table has been initially loaded
			MB.updateTable(MB.browsepath);
			MB.updatePathDisplay();

			//update selected class and scrollbar
			MB.updateSelectedClass(MB.browsepath);
			MB.updateScroll();

			MB.updateSelectedPath(MB.browsepath,MB.objType);

			if (init) MB.updateColumnScroll(MB.browsepath);

	};	


	/********************** utilities ****************************/

	this.updatePathDisplay = function()
	{

		var ref = ge("mbPathDisplay");
		clearElement(ref);

		ref.appendChild(ce("div","currentHeader","","Path: "));
		ref.appendChild(ce("div","currentInfo","",MB.browsepath));
		ref.appendChild(createCleaner());

	};

	this.checkFilter = function(ext) {

		//if everything, just return true
		if (MB.filter=="*" || !MB.filter) return true;

		var check = "," + this.filter + ",";

		if (check.indexOf("," + ext + ",")==-1) return false;
		else return true;

	};

	this.checkTypeFilter = function(objtype) {

		//if everything, just return true
		if (MB.typefilter=="*" || !MB.typefilter) return true;

		var arr = MB.typefilter.split(",");
		var key = arraySearch(objtype,arr);

		if (key==-1) return false;
		else return true;

	};



	/******************************************************************
		FUNCTION: updateTable
		PURPOSE:	makes sure we don't have stale cells when backing up
							in the path
	*******************************************************************/
	this.updateTable = function(path) {

		//remove all divs to the right of the one we're updating (their info is stale)
		var cells = MB.tableRow.getElementsByTagName("td");
		var save = MB.calCellNum(path);
		var rem = 0;

		for (var i=0;i<cells.length;i++) {

			//return only number of the cell
			if (i>save) rem++;

		}

		
		if (rem > 0) {

			for (var i=(cells.length-rem);i<cells.length;i++) {

				if (i<=2) clearElement(cells[i]);
				else MB.tableRow.removeChild(cells[i]);

			}

		}

		//we always need at least three cells in there (will be at least 2 by default already)
		var cells = MB.tableRow.getElementsByTagName("td");
		if (cells.length < 3) {

				MB.createCell("miniCell2");
		
		}

	};

	/******************************************************************
		FUNCTION: createCell
		PURPOSE:	creates a table cell to store info
	*******************************************************************/
	this.createCell = function(nameval,txt) {

				if (document.all) var ref = ce("<td class=\"mbObjectCell\" id=\"" + nameval + "\" valign=\"top\">");
				else {
					var ref = ce("td","mbObjectCell",nameval,"");
					ref.setAttribute("valign","top");
				}

				if (txt) ref.appendChild(ctnode(txt));
	
				MB.tableRow.appendChild(ref);

				return ref;

	};
	
	/******************************************************************
		FUNCTION: updateScroll
		PURPOSE:	scrolls the list columns to see the most recent element
	*******************************************************************/
	this.updateScroll = function() {

		cells = MB.tableRow.getElementsByTagName("td");
		var c = 0;

		MB.list.scrollLeft = cells.length * 185;

	};

	/******************************************************************
		FUNCTION: callCelNum
		PURPOSE:	figure out which table cell holds this path's info
	*******************************************************************/

	//figure out which table cell holds this path's information
	this.calCellNum = function(path) {

		if (!path) path = "/";

		if (path=="/") return 0;
		else {

			//remove the ceiling from the equation
			if (MB.ceiling!="/") path = path.substr(MB.ceiling.length);
			
			var arr = path.split("/");
			return arr.length-1;

		}

	};

	/******************************************************************
		FUNCTION: updateSelectedPath
		PURPOSE:	stores the current path we are viewing
	*******************************************************************/
	this.updateSelectedPath = function(path,objtype) {

		//update the class for what we just cliecked
		MB.updateSelectedClass(path);

		if (path.length > 4) {

			//if viewing fiels and not documents, do this
			if (objtype=="document" || objtype=="file") {

				var arr = path.split("/");
				var fn = arr.pop();
				ge("fileName").value = fn;

				//set the browsepath to the parent of this one
				this.browsepath = arr.join("/");
				MB.updateTable(path);

			} else ge("fileName").value = "";

		} 

	};

	/******************************************************************
		FUNCTION: updateObjProp
		PURPOSE:	store some properties for our selected object
	*******************************************************************/
	this.updateObjProp = function(type,ext,id) {

		MB.objType = type;
		MB.objExt = ext;
		MB.objId = id;

	};
		

	/******************************************************************
		FUNCTION: updateSelectedClass
		PURPOSE:	sets the class to "selected" for all objects that
							are part of the currently browsed path.
							this function is inefficient at the moment.  Some of
							the loops need to be combined
	*******************************************************************/
	this.updateSelectedClass = function(path) {

		//reset all divs in this cell
		var cell = ge("miniCell" + (MB.calCellNum(path)-1));
		if (!cell) return false;

		var arr = cell.getElementsByTagName("div");

		for (var i=0;i<arr.length;i++) {

			if (arr[i].id.indexOf("row_")==-1) continue;
			if (arr[i].className=="mbObjectRowSel") {
				setClass(arr[i],"mbObjectRow");
			}

		}

		//highlight the selected one

		var patharr = path.split("/");
		var pathstr = "";

		for (var i=0;i<patharr.length;i++) {
			if (i==0) pathstr = "/";
			else if (i==1) pathstr += patharr[i];
			else pathstr += "/" + patharr[i];
		
			var ref = ge("row_" + pathstr);
			if (ref) {
				setClass(ref,"mbObjectRowSel");
			}

		}

	};

	this.updateColumnScroll = function(path) {

		//reset all divs in this cell
		var cell = ge("miniCell" + (MB.calCellNum(path)-1));
		if (!cell) return false;

		var arr = cell.getElementsByTagName("div");

		//set the scrolltop so we can always see the selected folder
		for (var i=0;i<arr.length;i++) {

			if (arr[i].id.indexOf("row_")==-1) continue;
			if (arr[i].className=="mbObjectRowSel") {
				cell.getElementsByTagName("div")[0].scrollTop = (i * 20);
				break;
			}

		}

	};

	/******************************************************************
		FUNCTION: checkFileName
		PURPOSE:	makes sure we have the correct extension on the file
							we are saving
	*******************************************************************/
	this.checkFileName = function() {

		if (mode=="save") {

			var ref = ge("fileName");
			if (!ref) return false;

			//did they enter the name
			if (ref.value.length==0) {
				alert("You must enter a name for this file");
				ref.focus();
				return false;
			}		

		}

		return true;

	};
	
}
	