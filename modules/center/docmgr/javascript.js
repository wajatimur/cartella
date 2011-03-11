
/********************************************************************
	FILENAME: javascript.js
	PURPOSE:  contains common contact editing related functions

*********************************************************************/

//globals

var toolbarBtns;
var toolbarSearch;
var content;
var curpath;
var savecurpath;
var curobject;
var ceiling;
var saveceiling;
var ceilingid;
var popupref;
var keywords;
var searchinit;
var curview = "list";
var browsetree;
var bmdiv;
var accounts;
var followpath;
var extensions;
var keyupmode;

document.onkeyup = handleKeyUp;

/****************************************************************
	FUNCTION: loadPage
	PURPOSE:  loads our page for the first time
	INPUT:	  none
*****************************************************************/	

function loadPage() 
{

	content = ge("browseContent");

	loadSidebar();
	loadBrowser();

	getAccounts();
	getExtensions();

	//fix the actionsub for ie
	if (document.all) 
	{

		ge("actionSub").style.marginLeft = "-70px";
		ge("actionSub").style.marginTop = "18px";
		ge("shareSub").style.marginLeft = "-70px";
		ge("shareSub").style.marginTop = "18px";

	}



}

function closeWindow() 
{

	//close the popup and refresh our current page
	setTimeout("browsePath()","10");

}

function loadSidebar() 
{

	showModNav();

	clearElement(moduleNav);

	//makethe bookmark div
	bmdiv = ce("div","bmDiv");
	moduleNav.appendChild(bmdiv);

	//load the tag div
	//tagdiv = ce("div","tagDiv");
	//moduleNav.appendChild(tagdiv);

	//get the info	
	loadBookmarks();
	//loadTags();

}

function loadBookmarks() 
{

	if (bmdiv.innerHTML.length < 10)
	{
		bmdiv.appendChild(ce("div","statusMessage","","Loading bookmarks"));
	}

	var p = new PROTO();
	p.add("command","docmgr_bookmark_get");
	p.post(DOCMGR_API,"writeBKResults");

}

function writeBKResults(data) 
{
	 
	clearElement(bmdiv);

	if (data.error) alert(data.error);
	else if (data.bookmark) 
	{

		for (var i=0;i<data.bookmark.length;i++) 
		{

			var cont = ce("div","bookmark");

			//if expandable, make a tree
			if (data.bookmark[i].expandable=="t") 
			{

				//setup the ceiling array
				var ceiling = data.bookmark[i];
				ceiling.id = data.bookmark[i].object_id;

				var opt = new Array();
				opt.container = cont;
				opt.ceiling = data.bookmark[i].object_id;	
				opt.ceilingname = data.bookmark[i].name;
				opt.ceilingchild = data.bookmark[i].child_count;
				opt.ceilingpath = data.bookmark[i].object_path;

				opt.noexpand = 1;
				var t = new TREE();
				t.load(opt);

			//otherwise, just show another link
			} 
			else 
			{

				var img = ce("img");
				img.setAttribute("src",theme_path + "/images/closed_folder.png");				

				cont.appendChild(img);

				var link = ce("a","","",data.bookmark[i].name);
				link.setAttribute("href","javascript:clearTree();browsePath(\"" + data.bookmark[i].object_path + "\",\"" + data.bookmark[i].object_path + "\",'1')");
				cont.appendChild(link);

				img.style.marginRight = "3px";
				img.style.paddingLeft = "13px";

			}
			
			bmdiv.appendChild(cont);

			

		}

	}

}

function clearTree() 
{
	curtree = "";
}

function importObjects() 
{

	var url = "index.php?module=docmgrimport&path=" + curpath + "&prevpage=" + encodeURIComponent("index.php?module=docmgr&objectPath=" + curpath);
	location.href = url;

}

function getAccounts()
{

	var url = "index.php?module=accountlist";
	protoReq(url,"writeGetAccounts");


}

function writeGetAccounts(data)
{

	if (data.error) alert(data.error);
	else accounts = data.account;

}

function getExtensions()
{

	var p = new PROTO();
	p.setProtocol("XML");
	p.get("config/extensions.xml","writeGetExtensions");


}

function writeGetExtensions(data)
{

	if (data.object) extensions = data.object;

}

/***********************************************
  FUNCTION: handleKeyUp
  PURPOSE:  deactivates the control key setting
************************************************/
function handleKeyUp(evt) {

  if (!evt) evt = window.event;

  if (evt.keyCode=="9")
  {
 
		if (keyupmode=="share") pickFirstShare();

  }

}
