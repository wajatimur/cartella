
var ckeditor;

function DMEDITOR()
{

	
	/*******************************************************************
		FUNCTION: loadPage
		PURPOSE:	initializes and loads the editor
	*******************************************************************/
	this.load = function()
	{
	
		if (!objtype) objtype = "document";
	
		if (!curobj) {
	
			clearSiteStatus();	
			remotesaved = 0;
			EDITOR.loadEditor();
	
		} else {
	
			//if there's an object passed, use it
			if (curobj.length>0) {
	
				updateSiteStatus("Loading " + objtype);
	
				//snag the document converted to html for us
				if (objtype=="file") EDITOR.getFile();
				else EDITOR.getDocument();
	
			//open blank document
			} else {
	
				remotesaved = 0;
				clearSiteStatus();
	
			}
	
		}
	
	}
	
	/*******************************************************************
		FUNCTION: loadEditor
		PURPOSE:	load the actual editor.  
		INPUTS:		curval -> html we'll populate the editor with
	*******************************************************************/
	this.loadEditor = function(curval)
	{
	
			if (ckeditor)
			{
				ckeditor.destroy();
			}

			if (curobj) var ip = parentpath + "/" + objname + "/.object" + curobj + "_storage";
			else var ip = "/Users/" + USER_LOGIN + "/.temp_storage";
	
	    //create a new one
	    ckeditor = CKEDITOR.replace('editor_content',
									{
										image_mode: 'docmgr',
										image_path: ip,
										toolbar: 'Docmgr',
										fullPage: true,
										on: 
										{
											pluginsLoaded: function (ev) { addDialogs(this); },	
											instanceReady: function (ev) { EDITOR.setFrameSize();}
										}
	
									});

	    if (curval) ckeditor.setData(curval);
	
			clearSiteStatus();
	
	}

	this.createNew = function()
	{

		remotesaved = 0;
		readonly = 0;
		EDITOR.loadEditor();

	}
	
	/*******************************************************************
		FUNCTION: getDocumentContent
		PURPOSE:	get the html for the specified document
	*******************************************************************/
	this.getDocument = function()
	{

		objtype = "document";
	
		var p = new PROTO();
		p.add("command","docmgr_document_get");
		p.add("object_id",curobj);
		p.add("lock","1");
		p.post(DOCMGR_API,"EDITOR.writeDocumentContent");
	
	}
	
	/*******************************************************************
		FUNCTION: writeDocumentContent
		PURPOSE:	response handler for getDocumentContent.  Populates
							the editor with the html returned from docmgr
		INPUTS:		resp -> ajax response
	*******************************************************************/
	this.writeDocumentContent = function(data) 
	{

		clearSiteStatus();
	
		if (data.error) alert(data.error);
		else 
		{
	
			//set filesaved so we automatically overwrite
			remotesaved = 1;
	
			if (data.locked && data.locked=="t") readonly = "locked";
			else if (data.bitmask_text=="view") readonly = "view";
			else readonly = "";
	
			setReadOnly();
	
			if (!data.content) data.content = "";
	
			EDITOR.loadEditor(data.content);
	
		}

	}
	
	/*******************************************************************
		FUNCTION: getFileContent
		PURPOSE:	get the html for the specified file.  The file is
							called by docmgr and convert to html.  that html is
							returned to here
	*******************************************************************/
	this.getFile = function()
	{
	
		objtype = "file";
	
		var p = new PROTO();
		p.add("command","docmgr_file_getashtml");
		p.add("object_id",curobj);
		p.add("lock","1");
		p.post(DOCMGR_API,"EDITOR.writeDocumentContent");
	
	}
	
	/*********************************************
	  FUNCTION: checkState
	  PURPOSE:  checks if file needs to be saved
	*********************************************/
	this.checkState = function()
	{
	
		if (ckeditor.checkDirty())
		{
			return "You have made changes to this document since it was last saved.";
		}
	
	
	}
	
	
	/*******************************************************************
		FUNCTION: runServerSave
		PURPOSE:	posts the document content to docmgr for saving, 
							along with the path to save it to
		INPUTS:		none
	*******************************************************************/
	this.runServerSave = function() 
	{
	
		updateSiteStatus("Saving Document");
	
		//make sure we have the latest path set
		ge("objectId").value = curobj;
		ge("objectPath").value = objpath;
	
		//update or create
	
		//setup xml
		var p = new PROTO();
	
		if (objtype=="file") 
			p.add("command","docmgr_file_savefromhtml");
		else
			p.add("command","docmgr_document_save");
	
	
		p.add("parent_path",parentpath);			//pass the path too so we know where to put it (parent folder)
		p.add("name",objname);
		if (curobj) p.add("object_id",curobj);
		p.add("lock","1");
		p.add("revision_notes",savenotes);
		p.add("editor_content",ckeditor.getData());

		p.post(DOCMGR_API,"writeServerSave");

	}
	

	this.markDirty = function(dirty)
  {
    if (dirty==false) ckeditor.resetDirty();
  }  

	
	/*******************************************************************
		FUNCTION: mbSelectObject
		PURPOSE:	called by minib dialog.  returns the selected document
							information
		INPUTS:		res -> array containing document info
	*******************************************************************/
	this.mbSelectObject = function(res) 
	{

		if (!res) {
			alert("Error selecting object");
			return false;
		} 
	
		//store our globals
		objname = res.name;
		parentpath = res.parent;
	
		//handle a save operation
		if (mbmode=="save") {

			//if we picked a file extension to save as, mark that here
			if (res.savetype=="docmgr") objtype = "document";
			else 
			{

				objtype = "file";

				//make sure the file extension is in the name
				if (objname.indexOf("." + res.savetype)==-1) objname += "." + res.savetype;
	
			}

			updateSiteStatus("Saving File");
			setTimeout("EDITOR.runServerSave()","10");			//have to use settimeout to give the popup time to close
			return false;
	
		//handle an open operation
		} else {
	
			//store additional globals since we are selecting a file
			curobj = res.id; 
			objpath = res.path;
			objtype = res.type;
	
			updateSiteStatus("Opening File");
			ge("objectId").value = curobj;
	
	  	//if it is a document, get the html from docmgr
	  	if (objtype=="document") {
	    		setTimeout("EDITOR.getDocument()","10");
	  	} else if (objtype=="file") {
	    		//otherwise pull the file and convert it to html
	    		setTimeout("EDITOR.getFile()","10");
	  	} 	
	
		}
	
	}
	
	
	/*******************************************************************
		FUNCTION: printDocument
		PURPOSE:	prints the document to a pdf file
		INPUTS:		none
	*******************************************************************/
	this.print = function(preview)
	{
	
		if (!curobj) alert("You must save this document before you can print it");
		else {
	
			if (ge("taskId").value.length > 1)
			{
	
				if (preview) ge("printpreview").value = 1;
				else
				{
	
					var msg = "After the document prints, the task will be marked complete.  If you do not";
					msg += " wish this to occur, click \"Cancel\" and use \"Print Preview\"";
	
					if (!confirm(msg)) return false;
	
				}
	
			}
	
			ge("action").value = "print";
			document.pageForm.submit();
			ge("action").value = "";
	
		}
	
	}
	
	/*******************************************************************
		FUNCTION: setFrameSize
		PURPOSE:	sets the fckeditor to fill the window when resized
		INPUTS:		none
	*******************************************************************/
	this.setFrameSize = function()
	{
	
	  var ref = ge("cke_contents_editor_content");
		var base = 175;
	
	  ref.style.height = (getWinHeight() - base) + "px";
	
	}
	 
	
	this.insertMergeField = function(val) 
	{
	
	  val += " ";
	
		ckeditor.insertHtml(val);
	
	}
	
}
