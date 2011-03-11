
var isDirty;

function TEXTEDIT()
{

	/*******************************************************************
		FUNCTION: loadPage
		PURPOSE:	initializes and loads the editor
	*******************************************************************/
	this.load = function()
	{

		EDITOR.loadEditor();
	
		if (!curobj) 
		{
	
			clearSiteStatus();	
			remotesaved = 0;
	
		} else {
	
			//if there's an object passed, use it
			if (curobj.length>0) {
	
				updateSiteStatus("Loading " + objtype);
	
				//snag the document converted to html for us
				EDITOR.getContent();
	
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
	
			var ed = ge("editorDiv");
			clearElement(ed);
	
			var ta = createTextarea("editor_content");
			setChange(ta,"EDITOR.markDirty(true)");	
			if (curval) ta.value = curval;
	
			ed.appendChild(ta);
			ta.style.width = "99%";
	
			clearSiteStatus();
	
			EDITOR.setFrameSize();

			EDITOR.markDirty(false);
	
	}

	this.markDirty = function(dirty)
	{
		isDirty = dirty;
	}
	
	/*******************************************************************
		FUNCTION: getContent
		PURPOSE:	get the html for the specified file.  The file is
							called by docmgr and convert to html.  that html is
							returned to here
	*******************************************************************/
	this.getContent = function() {
	
		objtype = "file";
	
		var p = new PROTO();
		p.add("command","docmgr_object_getcontent");
		p.add("object_id",curobj);
		p.post(DOCMGR_API,"EDITOR.writeContent");
	
	}
	
	this.writeContent = function(data)
	{
	
		clearSiteStatus();
	
		if (data.error) alert(data.error);
		else 
		{

			ge("editor_content").value = data.content;
			remotesaved = 1;
			EDITOR.markDirty(false);

     	if (data.locked && data.locked=="t") readonly = "locked";
      else if (data.bitmask_text=="view") readonly = "view";
      else readonly = "";

      setReadOnly();

		}
	
	}
	
	/*********************************************
	  FUNCTION: checkState
	  PURPOSE:  checks if file needs to be saved
	*********************************************/
	this.checkState = function() {

		if (isDirty==true)
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
		p.add("command","docmgr_file_save");
		p.add("parent_path",parentpath);			//pass the path too so we know where to put it (parent folder)
		p.add("name",objname);
		if (curobj) p.add("object_id",curobj);
		p.add("lock","1");
		p.add("revision_notes",savenotes);
		p.add("editor_content",ge("editor_content").value);
	
		p.post(DOCMGR_API,"writeServerSave");
	
	}
	
	
	/*******************************************************************
		FUNCTION: mbSelectObject
		PURPOSE:	called by minib dialog.  returns the selected document
							information
		INPUTS:		res -> array containing document info
	*******************************************************************/
	this.mbSelectObject = function(res) {
	
		if (!res) {
			alert("Error selecting object");
			return false;
		} 
	
		//store our globals
		objname = res.name;
		parentpath = res.parent;
	
		//handle a save operation
		if (mbmode=="save") {
	
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
	
	 		//otherwise pull the file and convert it to html
	 		setTimeout("EDITOR.getContent()","10");
	
		}
	
	}
	

	/*******************************************************************
	  FUNCTION: setFrameSize
	  PURPOSE:  sets the fckeditor to fill the window when resized
	  INPUTS:   none
	*******************************************************************/
	this.setFrameSize = function()
	{
	 
	  var ref = ge("editor_content");
	  var base = 50;
	
	  ref.style.height = (getWinHeight() - base) + "px";
	   
	}
		 
}
	