/***********************************************
	file for editing object properties
***********************************************/

function loadObjParents() {

	clearElement(content);
	loadPAToolbar();

	//convert object parents to a string
	var valarr = new Array();

	if (objinfo.parents) valarr = objinfo.parents;

	var hcell = ce("div","propCell");
	hcell.appendChild(ce("div","formHeader","","Check the box next to the collection you want this object to reside in"));

	var tcell = ce("div","propCell");

	content.appendChild(hcell);
	content.appendChild(tcell);

	//create the form tree
	var opt = new Array();
	opt.container = tcell;
	opt.mode = "checkbox";
	opt.ceiling = "0";
	opt.ceilingname = ROOT_NAME;
	opt.curval = valarr;
	var t = new TREEFORM();
	t.load(opt);

}

function loadPAToolbar() {

	clearElement(tbBtns);
	clearElement(tbTitle);

	//setup our buttons
	tbBtns.appendChild(siteToolbarCell("Save","saveParents()","save.png"));

}

function saveParents() {

	//check our form
	var arr = content.getElementsByTagName("input");
	var parr = new Array();

	for (var i=0;i<arr.length;i++) {
		if (arr[i].checked==true) parr.push(arr[i].value);
	}

	//stop if none are selected
	if (parr.length==0) {
		alert("You must select a new collection if you wish to change where this object resides");
		return false;
	}

	updateSiteStatus("Saving object parents");

	var p = new PROTO();
	p.add("command","docmgr_object_saveparent");
	p.add("object_id",object);

	for (var i=0;i<parr.length;i++) p.add("parent_id",parr[i]);

	p.post(DOCMGR_API,"writeSaveParent");

}

function writeSaveParent(data) {

	 
	clearSiteStatus();

	if (data.error) alert(data.error);
	else {
	
		//updateSiteStatus("Reloading Tree");
		objpage = "loadObjParents";
		loadObjInfo();

	}

}
