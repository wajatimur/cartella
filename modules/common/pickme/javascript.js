
var PM;
var curpath;

function loadPage() {

	PM = new pickme();
	PM.create();
	self.focus();

}


function pickme() {

	//pull values from form
	curpath = ge("path").value;

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
		this.browse();

	};


	/**************** setup functions ****************/

	this.setupToolbar = function() {

		var mydiv = ge("mbObjectToolbar");

		var headdiv = ce("div","winHeader","","DocMGR File Chooser");

		//make our dropdown for ceiling switching
		this.ceilswitch = createSelect("ceilingSwitch");
		setChange(this.ceilswitch,"PM.switchCeiling()");

    //assemble the xml
		var p = new PROTO();
		p.add("command","docmgr_bookmark_get");   
		p.post(DOCMGR_API,"PM.writeBookmarks");

		var switchdiv = ce("div","browseSwitch");
		switchdiv.appendChild(ctnode("Switch To: "));
		switchdiv.appendChild(this.ceilswitch);

		if (this.filter=="*" || !this.filter) var filtertxt = "Current Filter: None";
		else filtertxt = "Current Filter (" + this.filter + ")";
		var filterdiv = ce("div","filterDesc","",filtertxt);

		mydiv.appendChild(switchdiv);
		mydiv.appendChild(headdiv);

	};

	this.writeBookmarks = function(data) {

		 

		if (data.error) alert(data.error);
		else if (!data.bookmark) alert("Error.  No bookmarks found");
		else {

			for (var i=0;i<data.bookmark.length;i++) {

				PM.ceilswitch[i] = new Option(data.bookmark[i].name,data.bookmark[i].object_path);

			}

		}

	}

	this.close = function() {

		self.close();

	};

	/**************** everything below here needs to be called and call others statically (PM.funcname)**********************/

	/*******************************************************************
		FUNCTION: browse
		PURPOSE:	makes call to get objects in the requested column
	*******************************************************************/
	this.browse = function(path) {

		if (path) curpath = path;

		//assemble the xml
		var p = new PROTO();
		p.add("command","docmgr_search_browse");
		p.add("path",curpath);
		p.post(DOCMGR_API,"PM.browseResults");

	};

	/******************************************************************
		FUNCTION: browseResults
		PURPOSE:	result handler for column list data
	*******************************************************************/
	this.browseResults = function(data) {

		var rs = ge("resultList");

		 
		if (data.error) alert(data.error);
		else if (!data.object) rs.appendChild(ce("div","errorMessage","","No images found"));
		else {

			for (var i=0;i<data.object.length;i++) {

				rs.appendChild(PM.objThumb(data.object[i]));

			}	
			
		}

	}

	this.objThumb = function(obj) {
	
	  //the parent container
	  var thumbdiv = ce("div","thumbcontainer");
		var filediv = ce("div","filecontainer");

		var thumbimg = ce("img","thumbnail");
	  var thumburl = site_url + "app/showthumb.php?sessionId=" + SESSION_ID + "&objectId=" + obj.id;
	  thumburl += "&objDir=" + obj.level1 + "/" + obj.level2;
	  thumburl += "&time=" + new Date().getTime();
	  thumbimg.setAttribute("src",thumburl);
	
	  if (document.all) {
	    thumbimg.setAttribute("height","75");
	    thumbimg.setAttribute("width","100");
	  }
	  filediv.appendChild(thumbimg);
	
	  imgclick = "PM.setUrl('" + site_url + "app/viewimage.php?objectId=" + obj.id + "&sessionId=" + SESSION_ID + "')";
	  setClick(thumbimg,imgclick);
	
	  //the name
	  var fndiv = ce("div","filename","",obj.name);
	
	  //the options
	  var optdiv = ce("div","fileopt");
	
	  var rlimg = ce("img");
	  rlimg.setAttribute("src",theme_path + "/images/rotate_left.gif");
	  setClick(rlimg,"rotateImage('" + obj.id + "','left')");
	
	  var rrimg = ce("img");
	  rrimg.setAttribute("src",theme_path + "/images/rotate_right.gif");
	  setClick(rrimg,"rotateImage('" + obj.id + "','right')");
	
	  optdiv.appendChild(rlimg);
	  optdiv.appendChild(rrimg);
	
	  var delimg = ce("img");
	  delimg.setAttribute("src",theme_path + "/images/trash.gif");
	  setClick(delimg,"removeImage('" + obj.id + "')");
	  optdiv.appendChild(delimg);
	
	  thumbdiv.appendChild(filediv);
	  thumbdiv.appendChild(fndiv);
	  thumbdiv.appendChild(optdiv);
	
	  return thumbdiv;
	
	};

	this.setUrl = function(url) {

		if (window.opener.dialogSelectImage) {
			window.opener.dialogSelectImage(url);
		}

		PM.close();

	}
	
}


	