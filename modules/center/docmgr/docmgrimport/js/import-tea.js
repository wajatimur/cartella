
var importcont;
var importlist;
var importidx = 0;
var importopt;
var importpath;								//the docmgr path we are importing to
var importfile;								//the current file we are importing
var savefiletype = "file";		//default import file type
var importmode;

function loadImportPage() {

	clearElement(container);
	importcont = ce("div","importContainer");
	importmode = "single";
	container.appendChild(importcont);
	loadImportActions();
	
	updateSiteStatus("Loading file information");
  var url = "index.php?module=diprocess&action=browse&path=" + browsepath;
  loadReq(url,"writeImportBrowse");

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

	toolbar.appendChild(siteToolbarCell("Import This File","importCurrentFile()","letter.png"));
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

	/***** setup the import options ***********/
	
	var sel = createSelect("fileType");
	setChange(sel,"setImportOpts()");
	sel[0] = new Option("File","file");
	sel[1] = new Option("Invoice","invoice");
	sel[2] = new Option("Financial Statement","financialstatement");
	sel[3] = new Option("Tax Return","taxreturn");
	sel[4] = new Option("Contract Files","contract");
	sel[5] = new Option("Membership Documents","membership");
	sel.value = savefiletype;

	var cell = ce("div","importCell");
	cell.appendChild(ce("div","formHeader","","File Type"));
	cell.appendChild(sel);
	rc.appendChild(cell);

	importopt = ce("div","importCell","importOptions");
	rc.appendChild(importopt);

	//set our defaults
	setImportOpts();

}

function setImportOpts() {

	clearElement(importopt);

	var optval = ge("fileType").value;
	var func = eval("load" + ucfirst(optval) + "Opts");

	savefiletype = optval;

	func();

}


function cleanupImport() {

 		//delete the originals
		var url = "index.php?module=diprocess&action=delete&path=" + browsepath + "&filePath=" + importfile.path;
    loadReq(url,"writeFileCleanup","POST");

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

function importCurrentFile() {

	//if fileType doesn't exist, we don't have a file to import
	if (!ge("fileType")) {
		alert("No file loaded to import");
		return false;
	}

	var optval = ge("fileType").value;
	var func = eval("import" + ucfirst(optval));
	func();

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
	save[0] = new Option("Home Directory","home");
	save[1] = new Option("Shared Directory","shared");
	save[2] = new Option("Browse...","browse");
	if (curpath.length > 0) {
		save[3] = new Option("Default location","default");
		save.selectedIndex = 3;
	}

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
	else if (saveval=="shared") importpath = "/Locations/" + CUR_LOC_VARNAME;
	else if (saveval=="browse") browseToPath();
	else if (saveval=="default") importpath = curpath;

	sp.appendChild(ce("div","","importPath",importpath));

}

function browseToPath() {

   //launch our selector to pick where to save the file

		if (ge("mode").value=="intranet")
		{ 

	    var parms = centerParms(600,460,1) + ",resizable=no,scrollbars=no";
	    var url = "index.php?module=minicms&mode=collection";
	    var ref = window.open(url,"_minib",parms);

		} else {

			openMiniB("open","","collection");

		}


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

}

/*********************** end file imports **************************/

/********************************************************************
	FINANCIAL STATEMENTS
********************************************************************/
function loadFinancialstatementOpts() {

	updateSiteStatus("Fetching company list");
	//we're going to ask them the company, month, and year this is for
	var url = "index.php?module=importfiletypes&action=listcompany";
	loadReq(url,"writeFSCompany");

}

function writeFSCompany(data) {

	clearSiteStatus();

	if (data.error) alert(data.error);
	else {

		//first create the company list
		var compcell = ce("div","importCell");
		compcell.appendChild(ce("div","formHeader","","Company Name"));

		//our list of companies in dropdown form
		var sel = createSelect("companyName");
		for (var i=0;i<data.type.length;i++) sel[i] = new Option(data.type[i].var_name);
		compcell.appendChild(sel);

		var d = new Date();

		//now for the month and year
		var lc = ce("div","leftColumn");
		var rc = ce("div","rightColumn");

		var monthcell = ce("div","importCell");
		monthcell.appendChild(ce("div","formHeader","","Month"));

		var sel = createSelect("monthDate");
		sel[0] = new Option("Jan","01");
		sel[1] = new Option("Feb","02");
		sel[2] = new Option("Mar","03");
		sel[3] = new Option("Apr","04");
		sel[4] = new Option("May","05");
		sel[5] = new Option("Jun","06");
		sel[6] = new Option("Jul","07");
		sel[7] = new Option("Aug","08");
		sel[8] = new Option("Sep","09");
		sel[9] = new Option("Oct","10");
		sel[10] = new Option("Nov","11");
		sel[11] = new Option("Dec","12");

		sel.selectedIndex = d.getMonth();
		monthcell.appendChild(sel);
		lc.appendChild(monthcell);

		var curyear = d.getFullYear();
		var past = curyear - 30;
		var future = curyear + 1;

		var yearcell = ce("div","importCell");
		yearcell.appendChild(ce("div","formHeader","","Year"));
		var c = 0;
		var sel = createSelect("yearDate");

		for (var i=past;i<future;i++) {

			sel[c] = new Option(i);
			c++;

		}

		sel.value = d.getFullYear();
		yearcell.appendChild(sel);
		rc.appendChild(yearcell);

		//put it all in the main form
		importopt.appendChild(compcell);
		importopt.appendChild(lc);
		importopt.appendChild(rc);
		importopt.appendChild(createCleaner());

	}

}

function importFinancialstatement() {

	updateSiteStatus("Importing current file");

	var path = "/tea/Financial Statements";
	path += "/" + ge("companyName").value;
	path += "/" + ge("yearDate").value;

	var name = "FS-" + ge("companyName").value + " " + ge("yearDate").value + "-" + ge("monthDate").value;

  //this will happen n several stages.  First, we send the object info to the server, then when send the file itself
  //setup the xml
  var p = new PROTO();
  p.add("command","docmgr_file_save");
  p.add("parent_path",path); 
  p.add("name",name);
	p.add("filepath",importfile.path);
	p.add("mkdir","1");
	p.post(DOCMGR_API,"writeImportHandler");

	/*
	xml += "<tag><name>Financial Statement</name><category_id>1</category_id></tag>";
	xml += "<tag><name>" + ge("yearDate").value + "</name><category_id>2</category_id></tag>";
	xml += "<tag><name>" + ge("monthDate").value + "</name><category_id>3</category_id></tag>";
  xml += "</data>";
	*/
	
}

/************************* financial statement ******************************/

/********************************************************************
	TAX RETURNS
********************************************************************/
function loadTaxreturnOpts() {

	updateSiteStatus("Fetching company list");
	//we're going to ask them the company, month, and year this is for
	var url = "index.php?module=importfiletypes&action=listcompany";
	loadReq(url,"writeTRCompany");

}

function writeTRCompany(data) {

	clearSiteStatus();

	if (data.error) alert(data.error);
	else {

		//first create the company list
		var compcell = ce("div","importCell");
		compcell.appendChild(ce("div","formHeader","","Company Name"));

		//our list of companies in dropdown form
		var sel = createSelect("companyName");
		for (var i=0;i<data.type.length;i++) sel[i] = new Option(data.type[i].var_name);
		compcell.appendChild(sel);

		var d = new Date();

		var curyear = d.getFullYear();
		var past = curyear - 30;
		var future = curyear + 1;

		var yearcell = ce("div","importCell");
		yearcell.appendChild(ce("div","formHeader","","Year"));
		var c = 0;
		var sel = createSelect("yearDate");

		for (var i=past;i<future;i++) {

			sel[c] = new Option(i);
			c++;

		}

		sel.value = d.getFullYear();
		yearcell.appendChild(sel);

		//put it all in the main form
		importopt.appendChild(compcell);
		importopt.appendChild(yearcell);

	}

}

function importTaxreturn() {

	updateSiteStatus("Importing current file");

	var path = "/tea/Tax Returns";
	path += "/" + ge("companyName").value;
	path += "/" + ge("yearDate").value;

	var name = "TR-" + ge("companyName").value + " " + ge("yearDate").value;

  //this will happen n several stages.  First, we send the object info to the server, then when send the file itself
  //setup the xml
  var p = new PROTO();
	p.add("command","docmgr_file_save");
  p.add("parent_path",path); 
  p.add("name",name);
	p.add("filepath",importfile.path);
	p.add("mkdir","1");
	p.post(DOCMGR_API,"writeImportHandler");

	/*
	xml += "<tag><name>Tax Return</name><category_id>1</category_id></tag>";
	xml += "<tag><name>" + ge("yearDate").value + "</name><category_id>2</category_id></tag>";
  xml += "</data>";
	*/
}

/************************* tax return ******************************/

/*******************************************************************
	Contracts
*******************************************************************/
function loadContractOpts() {

  //file type
  var cell = ce("div","importCell");
  cell.appendChild(ce("div","formHeader","","Contract Document Type"));
  cell.appendChild(ce("div","","contractTypeList","Loading document type list"));
  importopt.appendChild(cell);

	//save file to selector
	var cell = ce("div","importCell");
	cell.appendChild(ce("div","formHeader","","Save File To..."));

	var save = createSelect("fileSave");
	setChange(save,"setContractFileSaver()");

	//if passed from contract module, allow direct entry, otherwise force a browse
	if (curpath) save[0] = new Option("Default location","default");
	else save[0] = new Option("Select Destination...","0");

	save[1] = new Option("Browse...","browse");

	cell.appendChild(save);
	cell.appendChild(ce("div","","showFilePath"));

	importopt.appendChild(cell);

	setContractFileSaver();

  var url = "index.php?module=importfiletypes&action=listcontract";
  loadReq(url,"writeContractTypes");

}

function writeContractTypes(data) {

  var ft = ge("contractTypeList");
  clearElement(ft);

  if (data.error) alert(data.error);
  else if (!data.type) ft.appendChild(ce("div","errorMessage","","No file types found"));
  else {

		var sel = createSelect("contractType");
		ft.appendChild(sel);

    for (var i=0;i<data.type.length;i++) {
			sel[i] = new Option(data.type[i].name);
    }

  }

}

function setContractFileSaver() {

	var saveval = ge("fileSave").value;
	var sp = ge("showFilePath");
	importpath = "";

	clearElement(sp);

	if (saveval=="browse") browseToPath();
	else if (saveval=="default") importpath = curpath;

	sp.appendChild(ce("div","","importPath",importpath));

}

function importContract() {

	if (!importpath) {
		alert("Destination not selected");	
		return false;
	}

	updateSiteStatus("Importing current file");

  //this will happen n several stages.  First, we send the object info to the server, then when send the file itself
  //setup the xml
  var p = new PROTO();
	p.add("command","docmgr_file_save");
  p.add("parent_path",importpath); 
  p.add("name",getContractImportName());
	p.add("filepath",importfile.path);
	p.add("exist_rename","1");
	p.post(DOCMGR_API,"writeImportHandler");

}

function getContractImportName() {

	var type = ge("contractType").value;

  //how is the directory structure
  if (importfile.name.indexOf("/")!=-1) var arr = importfile.name.split("/");
  else var arr = importfile.name.split("\\");

  var fn = arr.pop();
  var pos = fn.lastIndexOf(".");

  if (pos==-1) return type;
  else {
    var ext = fn.substr(pos);
    return type + ext;
  }

}



/************************* membership files ******************************/

/*******************************************************************
	Membership
*******************************************************************/
function loadMembershipOpts() {

	//member number
  var cell = ce("div","importCell");
  cell.appendChild(ce("div","formHeader","","Member Number"));
  cell.appendChild(createTextbox("memberNumber"));
  importopt.appendChild(cell);

  //file type
  var cell = ce("div","importCell");
  cell.appendChild(ce("div","formHeader","","Membership Document Type"));
  cell.appendChild(ce("div","","membershipTypeList","Loading document type list"));
  importopt.appendChild(cell);

  var url = "index.php?module=importfiletypes&action=listmembership";
  loadReq(url,"writeMembershipTypes");

}

function writeMembershipTypes(data) {

  var ft = ge("membershipTypeList");
  clearElement(ft);

  if (data.error) alert(data.error);
  else if (!data.type) ft.appendChild(ce("div","errorMessage","","No file types found"));
  else {

		var sel = createSelect("membershipType");
		ft.appendChild(sel);

    for (var i=0;i<data.type.length;i++) {
			sel[i] = new Option(data.type[i].name);
    }

  }

}

function importMembership() {

	updateSiteStatus("Importing current file");

	var path = getMembershipImportPath();
	var name = getMembershipImportName();

	if (ge("memberNumber").value.length==0) {
		alert("You must specify a member number");
		clearSiteStatus();
		return false;
	}
		
	if (!path || !name) {
		alert("Error determining path for file.  Aborting import");
		clearSiteStatus();
		return false;
	}

  //this will happen n several stages.  First, we send the object info to the server, then when send the file itself
  //setup the xml
  var p = new PROTO();
	p.add("command","docmgr_file_save");
  p.add("parent_path",path); 
  p.add("name",name);
	p.add("filepath",importfile.path);
	p.add("mkdir","1");
	p.add("exist_rename","1");
	p.post(DOCMGR_API,"writeImportHandler");

}

function formatMemberNumber(mn) {

	if (CUR_LOC_VARNAME=="wsrc") {

		if (mn.length<6) mn = mn.substr(0,1) + str_pad(mn.substr(1),5,"0","front");

	} else return false;

	return mn.toUpperCase();

}

function getMembershipImportPath() {

	var base = "/tea/tai/Membership";

	var mn = formatMemberNumber(ge("memberNumber").value);

	//tack on the company number, and pad the member number
	base += "/" + CUR_LOC_COMPNUM + "/" + (mn.substr(0,1) + "/" + mn).toUpperCase();	

	return base;

}

function getMembershipImportName() {

	var mn = formatMemberNumber(ge("memberNumber").value);
	var type = ge("membershipType").value;
	

  //how is the directory structure
  if (importfile.name.indexOf("/")!=-1) var arr = importfile.name.split("/");
  else var arr = importfile.name.split("\\");

  var fn = arr.pop();
  var pos = fn.lastIndexOf(".");

  if (pos==-1) return mn + " " + type;
  else {
    var ext = fn.substr(pos);
    return mn + " " + type + ext.toLowerCase();
  }

}