
var importcont;
var importlist;
var importidx = 0;
var importopt;
var importpath;								//the docmgr path we are importing to
var importfile;								//the current file we are importing
var savefiletype = "file";		//default import file type
var importmode;
var lastpath;

function loadImportPage() {

	clearElement(container);
	importcont = ce("div","importContainer");
	importmode = "single";

	container.appendChild(importcont);
	loadImportActions();
	
	updateSiteStatus("Loading file information");
  var url = "index.php?module=diprocess&action=browse&path=" + browsepath;
  protoReq(url,"writeImportBrowse");

}

function writeImportBrowse(data) {

	clearSiteStatus();
	 

	if (data.error) alert(data.error);
	else if (!data.file) importcont.appendChild(ce("div","errorMessage","","No files found to import"));
	else {

		importlist = data.file;

		//show the first file
		showImportFile(importidx);

	}

}

function directImportFile(idx) {

	importidx = idx;
	loadImportPage();

}


function loadImportActions() {

  clearElement(toolbar);

	toolbar.appendChild(siteToolbarCell("Import This File","importFile()","letter.png"));
	toolbar.appendChild(siteToolbarCell("Show Prev File","changeImportFile('prev')","back.png"));
	toolbar.appendChild(siteToolbarCell("Show Next File","changeImportFile('next')","next.png"));

}

function changeImportFile(dir) {

	if (dir=="prev") {

		if (importidx==0) importidx = importlist.length - 1;
		else importidx--;

	} else {

		if (importidx==(importlist.length-1)) importidx = 0;
		else importidx++;

	}

	showImportFile(importidx);

}


function showImportFile(idx) {

	importidx = idx;
	importfile = importlist[importidx];

	//clear the display
	clearElement(importcont);

	var lc = ce("div","leftColumn");
	var rc = ce("div","rightColumn");
	importcont.appendChild(lc);
	importcont.appendChild(rc);

	/***** setup the file preview and the name *******/

	var img = ce("img","importFileImage");
	img.setAttribute("src",importfile.large_thumb);
	setClick(img,"viewFilePreview('" + importfile.huge_thumb + "')");
	lc.appendChild(img);	

	lc.appendChild(ce("div","importFileName","",importfile.name));
	importopt = rc;

	loadFileSaver();
	setFileSaver();

}


function cleanupImport() {

 		//delete the originals
		var url = "index.php?module=diprocess&action=delete&path=" + browsepath + "&filePath=" + importfile.path;
    protoReq(url,"writeFileCleanup","POST");

}

function writeFileCleanup(data) {

	clearSiteStatus();

	 
	if (data.error) alert(data.error);	
	else {

		importpath = "";
		importfile = "";
		loadImportPage();

	}
}


//generic handler for the end of a file import
function writeImportHandler(data) {

	if (data.error) {
		alert(data.error);
		clearSiteStatus();
	}
	else {

		cleanupImport();

	}

}

/***************************************************************
  regular file imports
***************************************************************/
function loadFileOpts() {

  loadFileSaver();
  setFileSaver();

}

function loadFileSaver() {

	var cell = ce("div","importCell");
	cell.appendChild(ce("div","formHeader","","Save File To..."));

	var save = createSelect("fileSave");
	setChange(save,"setFileSaver()");

	if (lastpath) 
	{
		save[0] = new Option("Last Import Destination","lastimport");
		var i = 0;
	}
	else var i = -1;

	save[++i] = new Option("User Directory","home");
	save[++i] = new Option("Browse...","browse");


	cell.appendChild(save);
	cell.appendChild(ce("div","","showFilePath"));

	importopt.appendChild(cell);

}

function setFileSaver() {

	var saveval = ge("fileSave").value;
	var sp = ge("showFilePath");
	importpath = "";

	clearElement(sp);

	if (saveval=="home") importpath = "/Users/" + USER_LOGIN;
	else if (saveval=="browse") browseToPath();
	else if (saveval=="lastimport") importpath = lastpath;

	sp.appendChild(ce("div","","importPath",importpath));

}

function browseToPath() {

	openMiniB("open","","collection");

}

function importAll()
{

	importmode = "all";
	browseToPath();

}

function runImportAll() {

	updateSiteStatus("Importing all files");

  //this will happen n several stages.  First, we send the object info to the server, then when send the file itself
  //setup the xml
  var p = new PROTO();
  p.add("command","docmgr_file_import");
  p.add("parent_path",importpath); 
	p.add("directory",browsepath);
	p.post(DOCMGR_API,"writeImportAll");

}

function writeImportAll(data)
{

	 
	clearSiteStatus();

	if (data.error) alert(data.error);
	else loadEditPage();	

}

function mbSelectObject(res) {

	if (importmode=="all")
	{

		importpath = res.path;
		runImportAll();

	} else {

		importpath = res.path;
		ge("showFilePath").appendChild(ce("div","","importPath",importpath));

	}

}

function importFile() {

	updateSiteStatus("Importing current file");

  //this will happen n several stages.  First, we send the object info to the server, then when send the file itself
  //setup the xml
	var p = new PROTO();
  p.add("command","docmgr_file_save");
  p.add("parent_path",importpath); 
  p.add("name",importfile.name);
	p.add("filepath",importfile.path);
	p.post(DOCMGR_API,"writeImportHandler");

	lastpath = importpath;

}

/*********************** end file imports **************************/
