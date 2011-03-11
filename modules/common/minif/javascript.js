
var MF;

function loadPage() {

	var ceil = ge("browseCeiling").value;
	var fil = ge("browseFilter").value;
	var path = ge("browsePath").value;

	MF = new miniF(ceil,path,fil);
	MF.create();
	self.focus();

}


function miniF(ceil,p,filt) {

	this.path = p;
	this.ceiling = ceil;
	this.filter = filt;
	this.width = "600";
	this.height = "460";

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
		this.setupControls();
		this.setupList();

	};


	/**************** setup functions ****************/

	this.setupToolbar = function() {

		var mydiv = ge("mbObjectToolbar");

		var headdiv = ce("div","winHeader","","File Chooser");

		if (this.filter=="*" || !this.filter) var filtertxt = "Current Filter: None";
		else filtertxt = "Current Filter (" + this.filter + ")";
		var filterdiv = ce("div","filterDesc","",filtertxt);

		mydiv.appendChild(headdiv);

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
		for (var i=0;i<3;i++) this.createCell("miniCell" + i,"");

		//remove the ceiling from the path
		if (this.path.length > 0) {
			this.path = path.substr(this.ceiling.length);
		} else {
			this.path = "";
		}

		this.browse(this.ceiling);

		//convert path into an array
		var patharr = this.path.split("/");
		var pathstr = this.ceiling;

		//get the data only AFTER all the divs have been setup
		for (var i=0;i<(patharr.length);i++) {

			if (!patharr[i]) continue;
			pathstr += "/" + patharr[i];											//regular addition on the rest of the loops
			this.browse(pathstr,1);

		}

		//if not an initial load, update our settings once ajax is over with
		//endReq("this.updateDisplay('1')",100);

		tbd.appendChild(this.tableRow);
		tbl.appendChild(tbd);
		this.list.appendChild(tbl);

	};

	/*******************************************************************
		FUNCTION: setupPathDiv
		PURPOSE:	sets up the div containing path textbox for the user
							to see path of selected object
	*******************************************************************/

	this.setupPathDiv = function() {
		
		var pd = ge("mbPathDiv");

		//setup path box
		var header = ce("div","mbHeader","","Selected Path");
		var tb = createTextbox("selectedPath",this.path);
		tb.setAttribute("readonly","true");

		pd.appendChild(header);
		pd.appendChild(tb);

	};
	
	this.setupControls = function() {

		var mydiv = ge("mbObjectControls");

		if (this.filter=="*" || !this.filter) var filtertxt = "Current Filter: None";
		else filtertxt = "Current Filter (" + this.filter + ")";
		var filterdiv = ce("div","filterDesc","",filtertxt);

		var selbtn = createBtn("selbtn","Select");
		selbtn.onclick = createMethodReference(this,"selectPath");

		var cancelbtn = createBtn("cancelbtn","Cancel");
		cancelbtn.onclick = createMethodReference(this,"close");

		mydiv.appendChild(filterdiv);
		mydiv.appendChild(selbtn);
		mydiv.appendChild(cancelbtn);

	};

	this.setPathByRef = function(e) {

		var ref = getEventSrc(e);
		var path = ref.getAttribute("path");

		this.setPath(path);

	};

	this.setPath = function(path) {

		ge("selectedPath").value = path;

	};

	this.selectPath = function () {

		if (window.opener.mfSelectPath) {
			window.opener.mfSelectPath(ge("selectedPath").value);
			setTimeout("self.close()","100");
		}

	};

	this.close = function() {

		self.close();

	};

	/**************** everything below here needs to be called and call others statically (this.funcname)**********************/

	/*******************************************************************
		FUNCTION: browse
		PURPOSE:	makes call to get objects in the requested column
	*******************************************************************/
	this.browse = function(path) {

		//assemble the xml
		var url = "index.php?module=browsefilesystem&path=" + path;
		protoReq(url,createMethodReference(this,"browseResults"));

	};

	this.browsePath = function(e) {

		var ref = getEventSrc(e);
		var path = ref.getAttribute("path");

		//assemble the xml
		var url = "index.php?module=browsefilesystem&path=" + path;
		protoReq(url,createMethodReference(this,"browseResults"));

	};

	/******************************************************************
		FUNCTION: browseResults
		PURPOSE:	result handler for column list data
	*******************************************************************/
	this.browseResults = function(data) {

		 
		if (data.error) alert(data.error);
		else {

			this.setPath(data.path);
	
			//get our column to update
			var ref = ge("miniCell" + this.calCellNum(data.path));

			//doesn't exist, make it
			if (!ref) {
				//if we need another table cell to add data, make it	
				ref = this.createCell("miniCell" + this.calCellNum(data.path),"Updating...");
			}

			//get rid of existing data	
			clearElement(ref);

			//nothing found
			if (!data.entry) {
				ref.appendChild(ce("div","errorMessage","","No Results Found"));
			} else {

				//a container for our data that will scroll	
				var cont = ce("div","mbObjectCellContainer");
	
				for (var i=0;i<data.entry.length;i++) {
	
					var obj = data.entry[i];

					//create the row and image
					var row = ce("div","mbObjectRow");
					row.setAttribute("path",obj.path);

					//allow browsing of directories
					if (obj.type=="directory") {
						row.onclick = createMethodReference(this,"browsePath");
						var icon = "folder.png";
					} else {
						row.onclick = createMethodReference(this,"setPathByRef");
						var icon = "file.png";
					}

		 			var img = ce("img");
	  			img.setAttribute("src",theme_path + "/images/docmgr/fileicons/" + icon);
	
					//put them together
					row.appendChild(img);
					row.appendChild(ctnode(obj.name.substr(0,20)));


					cont.appendChild(row);			
	
				}
	
				ref.appendChild(cont);
	
			}

			this.updateTable(data.path);
			this.updateSelectedClass(data.path);
	
		}


	};

	/******************************************************************
		FUNCTION: updateTable
		PURPOSE:	makes sure we don't have stale cells when backing up
							in the path
	*******************************************************************/
	this.updateTable = function(path) {

		//remove all divs to the right of the one we're updating (their info is stale)
		var cells = this.tableRow.getElementsByTagName("td");
		var save = this.calCellNum(path);
		var rem = 0;

		for (var i=0;i<cells.length;i++) {

			//return only number of the cell
			if (i>save) rem++;

		}

		
		if (rem > 0) {

			for (var i=(cells.length-rem);i<cells.length;i++) {

				if (i<=2) clearElement(cells[i]);
				else this.tableRow.removeChild(cells[i]);

			}

		}

		//we always need at least three cells in there (will be at least 2 by default already)
		var cells = this.tableRow.getElementsByTagName("td");
		if (cells.length < 3) {

				this.createCell("miniCell2");
		
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
	
				this.tableRow.appendChild(ref);

				return ref;

	};
	
	/******************************************************************
		FUNCTION: updateScroll
		PURPOSE:	scrolls the list columns to see the most recent element
	*******************************************************************/
	this.updateScroll = function() {

		cells = this.tableRow.getElementsByTagName("td");
		var c = 0;

		this.list.scrollLeft = cells.length * 185;

	};

	/******************************************************************
		FUNCTION: callCelNum
		PURPOSE:	figure out which table cell holds this path's info
	*******************************************************************/

	//figure out which table cell holds this path's information
	this.calCellNum = function(path) {

		if (path=="/") return 0;
		else {
			
			//remove the ceiling from the equation
			path = path.substr(this.ceiling.length);
			var arr = path.split("/");
			return arr.length-1;

		}

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
		var cell = ge("miniCell" + (this.calCellNum(path)-1));
		if (!cell) return false;

		var arr = cell.getElementsByTagName("div");

		for (var i=0;i<arr.length;i++) {

			if (arr[i].className=="mbObjectRowSel") {
				setClass(arr[i],"mbObjectRow");
			}

			if (arr[i].getAttribute("path")==path) setClass(arr[i],"mbObjectRowSel");

		}

	};

	this.updateColumnScroll = function(path) {

		//reset all divs in this cell
		var cell = ge("miniCell" + (this.calCellNum(path)-1));
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

}
	