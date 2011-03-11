
var curpath;
var browsepath = "home";
var container;
var toolbar;

function loadPage(p) {

	curpath = ge("path").value;
	toolbar = ge("toolBar");
	container = ge("container");
	loadModNav();

	if (p=="import") loadImportPage();
	else loadEditPage();

}

function setBrowsePath(loc) {

	//if not passed a location, look for a default
	if (ge("beginbrowse").value && !loc) loc = ge("beginbrowse").value;

  if (loc=="shared") browsepath = IMPORT_DIR;
	else if (loc=="location") browsepath = SITE_PATH + "/files/import/locations/" + CUR_LOC_VARNAME;
  else browsepath = SITE_PATH + "/files/home/" + USER_LOGIN;

	var ref = ge("showBrowsePath");
	clearElement(ref);

	if (loc=="shared") var txt = "Browsing Shared Directory";
	else if (loc=="location") var txt = "Browsing " + CUR_LOC_NAME + " Directory";
	else var txt = "Browsing User Directory";

	ref.appendChild(ctnode(txt));

}

function loadModNav() {

  showModNav();
	var prev = ge("prevpage").value;

  addModNav("Browse User Folder","changeBrowseDir('home')","browse.png");
	if (TEA_ENABLE=="1") addModNav("Browse Location Folder","changeBrowseDir('location')","browse.png");
	addModNav("Browse Shared Folder","changeBrowseDir('shared')","browse.png");
	
  if (prev.length > 0) {
		addModSpacer();
		addModNav("Back To Previous Page","location.href = '" + prev + "'","back.png");
	}

}
