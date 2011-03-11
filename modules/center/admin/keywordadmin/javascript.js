
var keyword;
var keyinfo;
var selopt;

function loadPage() {

	//get the list of keywords
	loadToolbar();
	loadKeywordList();

	if (keyword) loadKeywordForm();
	else loadKeyword();

}

function loadToolbar() {

	var tb = ge("toolbar");
	clearElement(tb);

	tb.appendChild(siteToolbarCell("Save","saveKeyword()","save.png"));
	if (keyword) {
		tb.appendChild(siteToolbarCell("Delete","deleteKeyword()","delete.png"));
		tb.appendChild(siteToolbarCell("Clear Form","clearForm()","new.png"));

		if (keyinfo.type=="select")
		{

			tb.appendChild(ce("div","toolbarDivider","","|"));
			tb.appendChild(siteToolbarCell("Add Option","addOption()","new.png"));
			tb.appendChild(siteToolbarCell("Remove Option","deleteOption()","delete.png"));

		}	

	}

}

function loadKeyword(id) {

	if (id)
	{

    updateSiteStatus("Loading keyword");

  	//setup the xml
		var p = new PROTO();
  	p.add("command","docmgr_keyword_getinfo");
  	p.add("keyword_id",id);
		p.post(DOCMGR_API,"writeKeyword");

	} else 
	{
		loadKeywordForm();
	}

}

function writeKeyword(data) {

	 

	clearSiteStatus();

	if (data.error) alert(data.error);
	else {

		keyword = data.keyword[0].id;
		keyinfo = data.keyword[0];
		loadKeywordForm();
		loadToolbar();

	}

}


function loadKeywordList() {

	updateSiteStatus("Loading keyword list");

	//setup the xml
	var p = new PROTO();
	p.add("command","docmgr_keyword_getall");
	p.post(DOCMGR_API,"writeKeywordList");

}

function writeKeywordList(data) {

	showModNav();
	 

	clearSiteStatus();
	clearElement(moduleNav);

	if (data.error) alert(data.error);
	else if (!data.keyword) {

		moduleNav.appendChild(ce("div","errorMessage","","No keywords found"));

	} else {

		for (var i=0;i<data.keyword.length;i++) {

			addModNav(data.keyword[i].name,"loadKeyword('" + data.keyword[i].id + "')");

		}

	}

}

function loadKeywordForm() {

	clearElement(ge("left"));
	clearElement(ge("right"));

	var ref = ge("left");

	//keyword name
	var cell = ce("div","keywordCell");
	cell.appendChild(ce("div","formHeader","","Name"));
	var tb = createTextbox("keyword_name");
	if (keyinfo) tb.value = keyinfo.name;
	cell.appendChild(tb);
	ref.appendChild(cell);

	//keyword type
	var cell = ce("div","keywordCell");
	cell.appendChild(ce("div","formHeader","","Type"));

	var sel = createSelect("keyword_type");
	sel[0] = new Option("Text","text");
	sel[1] = new Option("Select","select");
	if (keyinfo) sel.value = keyinfo.type;

	cell.appendChild(sel);
	ref.appendChild(cell);

	//keyword options
	var cell = ce("div","keywordCell","selectOption");
	cell.appendChild(ce("div","formHeader","","Select Options"));
	selopt = ce("div","selectList");
	cell.appendChild(selopt);
	ref.appendChild(cell);

	var cell = ce("div","keywordCell");
	cell.appendChild(ce("div","formHeader","","Keyword is required"));

	var yescb = createRadio("required","1");
	cell.appendChild(yescb);
	cell.appendChild(ctnode("Yes"));

	var nocb = createRadio("required","0");
	cell.appendChild(nocb);
	cell.appendChild(ctnode("No"));

	if (keyinfo && keyinfo.required=="t") yescb.checked = true;
	else nocb.checked = true;
	ref.appendChild(cell);

	var cont = ce("div","keywordCell");
	cont.appendChild(ce("div","formHeader","","Available For Which Collections (blank for all)"));
	var tree = ce("div");
	cont.appendChild(tree);
	ge("right").appendChild(cont);

	//generate the tree
	var opt = new Array();
	opt.mode = "checkbox";
	opt.formname = "parent_id";
	opt.container = tree;
	if (keyinfo && isData(keyinfo.parent_id)) opt.curval = keyinfo.parent_id;

	var t = new TREEFORM();
	t.load(opt);

	cycleOptions();
	loadOptions();

}

function cycleOptions() {

	var t = ge("keyword_type");
 
	if (t.value=="select") ge("selectOption").style.display = "block";
	else ge("selectOption").style.display = "none";

}

function loadOptions() {

	if (keyinfo && keyinfo.type=="select") 
	{

  	//setup the xml
		var p = new PROTO();
  	p.add("command","docmgr_keyword_getoptions");
		p.add("keyword_id",keyword);
		p.post(DOCMGR_API,"writeOptions");

	}

}

function writeOptions(data) {

	 
       
	clearElement(selopt);
	clearSiteStatus();

	if (data.error) alert(data.error);
	else if (!data.option) selopt.appendChild(ce("div","errorMessage","","No options found"));
	else {

		for (var i=0;i<data.option.length;i++) 
		{

			var row = ce("div");
			row.appendChild(createCheckbox("optionId[]",data.option[i].id));
			row.appendChild(ctnode(data.option[i].name));
			selopt.appendChild(row);


		}

	}

}	

function saveKeyword() {

	updateSiteStatus("Saving keyword");

 	//setup the xml
	var p = new PROTO();
 	p.add("command","docmgr_keyword_save");
	if (keyword) p.add("keyword_id",keyword);
	p.addDOM(ge("container"));
	p.post(DOCMGR_API,"writeSaveKeyword");

}

function writeSaveKeyword(data) {

	clearSiteStatus();

	if (data.error) alert(data.error); 
	else 
	{

		keyword = data.keyword[0].id;
		keyinfo = data.keyword[0];	
		loadToolbar();

		loadKeywordList();
		cycleOptions();

	}

}

function addOption() {

	var on = prompt("Please enter the name for the option");

	if (on.length > 0) 
	{

	  updateSiteStatus("Adding option");

		//setup the xml
		var p = new PROTO();
  	p.add("command","docmgr_keyword_saveoption");
		p.add("keyword_id",keyword);
		p.add("option_name",on);
		p.post(DOCMGR_API,"writeOptions");

	}

}

function deleteOption() {

	if (confirm("Are you sure you want to remove the selected options?")) 
	{

		 var arr = selopt.getElementsByTagName("input");
		 var sel = new Array();

		 for (var i=0;i<arr.length;i++) if (arr[i].checked==true) sel.push(arr[i].value);

		 if (sel.length=="0") alert("You must select at least one option first");
		 else {

			  updateSiteStatus("Removing option");

				//setup the xml
				var p = new PROTO();
  			p.add("command","docmgr_keyword_deleteoption");
			  for (var i=0;i<sel.length;i++) p.add("option_id",sel[i]);
				p.add("keyword_id",keyword);
				p.post(DOCMGR_API,"writeOptions");

		 }

	}

}

function deleteKeyword() {

	if (confirm("Are you sure you want to delete this keyword?")) 
	{

	  updateSiteStatus("Removing option");

		//setup the xml
		var p = new PROTO();
		p.add("command","docmgr_keyword_delete");
		p.add("keyword_id",keyword);
		p.post(DOCMGR_API,"writeDeleteKeyword");

	}

}

function writeDeleteKeyword(data)
{

	if (data.error) alert(data.error);
	else 
	{

		keyword = "";
		keyinfo = "";
		loadPage();	

	}

}

function clearForm()
{

	keyword = "";
	keyinfo = "";
	loadPage();

}
