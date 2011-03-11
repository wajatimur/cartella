

window.onresize = setFrameSize;
window.onbeforeunload = checkState;
window.onunload = refreshParent;

var savenotes;

function createNew()
{

	remotesaved = 0;
	
	EDITOR.createNew();

}

function openLocal()
{

	EDITOR.openLocal();

}

function saveLocal()
{
	EDITOR.saveLocal();
}

function saveWithNotes()
{

	var ref = openSitePopup("450","300");

	ref.appendChild(ce("div","sitePopupHeader","","Revision Notes"));

	var cell = ce("div","sitePopupCell");
	cell.appendChild(createTextarea("saveNotes"));
	ref.appendChild(cell);

	var cell = ce("div","sitePopupCell");	
	var btn = createBtn("saveWithNotesBtn","Save To Server");
	setClick(btn,"storeNotes()");
	cell.appendChild(btn);

	ref.appendChild(cell);

}

function storeNotes()
{

	savenotes = ge("saveNotes").value;
	closeSitePopup();

	saveServer();

}

function saveServer()
{

    //if we've already saved this file once, just save it directly
    if (remotesaved == 1 && !readonly) 
    {
     
      EDITOR.runServerSave();
  
			//reset our notes
			savenotes = "";

    //spawn minib to pick where to save the file
    } 
		else 
    {
     
      mbmode = "save";
      curobj = "";
  
			openMiniB("save",parentpath,"document,file,collection",cureditor);
  
		}

}

/*******************************************************************
  FUNCTION: writeServerSave
  PURPOSE:  response handler for runServerSave
  INPUTS:   resp -> ajax response
*******************************************************************/
function writeServerSave(data) 
{
  
  clearSiteStatus();
  
  if (data.error) alert(data.error);
  else 
	{
  
    //show it's been saved
    EDITOR.markDirty(false);
  
    //save the objectid
    remotesaved = 1;   
    readonly = "";     
    curobj = data.object_id;
    ge("objectId").value = curobj;
  
  }  

}

function saveServerCopy()
{

	remotesaved = 0;
	saveServer();

}

function printDocument()
{
	EDITOR.print();
}

function printPreview()
{
	EDITOR.printPreview();
}

function pageLayout()
{
	EDITOR.pageLayout();
}

function setFrameSize()
{
	EDITOR.setFrameSize();
}

function checkState()
{
	EDITOR.checkState();
}

function refreshParent()
{

  //if we have an object, make sur eit's unlocked
  if (curobj)
  {

		if (cureditor=="dsoframer")
		{

	    //we need to see if the file is locked
	    //api xml
	    var xml = "<data>";
	    xml += cdata("command","docmgr_lock_clear");
	    xml += cdata("object_id",curobj);
	    xml += "</data>";
	
	    var url = DOCMGR_API + "?apidata=" + xml;
	    loadReqSync(url);

		}
		else
		{

	    //unlock our document
	    var p = new PROTO(); 
	    p.add("command","docmgr_lock_clear");
	    p.add("object_id",curobj);
	    p.setAsync(false);
	    p.post(DOCMGR_API);

		}

  }
   
  if (window.opener.closeWindow)
  {
    window.opener.closeWindow(curobj);
  } 
  else
  {   
    var url = window.opener.location.href;
    window.opener.location.href = url;
  }

}

function openServerObject()
{
  remotesaved = 0;
  
  mbmode = "open";

	openMiniB("open",curobj,"document,file,collection");

}

function getEditorType(type,ext)
{

	//ugh.  this is all because dsoframer and mootools don't play together
	if (cureditor=="dsoframer")
	{

		var xml = loadReqSync("config/extensions.xml");
		extensions = parseXML(xml);

	}
	else
	{

		var p = new PROTO();
		p.setAsync(false);
		p.setProtocol("XML");
		extensions = p.post("config/extensions.xml");

	}

	var neweditor = "";

	//type,ext
	if (type!="document") 
	{

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
				neweditor = ow[i];
				break;
			}
			else if (ow[i]=="dsoframer" && document.all && DSOFRAMER_ENABLE==1 && SETTING["editor"]=="msoffice")
			{
				neweditor = "dsoframer";
				break;
			}

		}

	}

	//default to docmgr editor
	if (!neweditor) neweditor = "dmeditor";

	return neweditor;

}

function mbSelectObject(res)
{

	if (mbmode=="open")
	{

		var neweditor = getEditorType(res.type,res.ext);
	
		//setup the new editor for the new type if we are in the wrong one
		if (neweditor!=cureditor) 
		{
	
			//switch back and forth betwen dsoframer and non-dsoframer editors
			if (cureditor=="dsoframer" || neweditor=="dsoframer") 
			{
				location.href = "index.php?module=editor&editor=" + neweditor + "&objectId=" + res.id;
				return false;
			}
			else
			{
				cureditor = neweditor;
				loadEditor();
			}
	
		}

	}

	//now handle the selected file
	EDITOR.mbSelectObject(res);

}


function setReadOnly()
{
 
  var rc = ge("readonlyCell");
	clearElement(rc);

  if (readonly)
  {
   
    if (readonly=="locked") rc.appendChild(ctnode("Read-only.  Locked by another user"));
    else rc.appendChild(ctnode("Read-only"));

  }

}
