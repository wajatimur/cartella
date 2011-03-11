
var blist;
var binfo;
var bkdata;
var curbk;
var buttonbar;

function loadPage() {

	blist = ge("bkList");
	binfo = ge("bkInfo");
	buttonbar = ge("toolbarLeft");

}

function loadButtons(force) {

	clearElement(buttonbar);

	if (curbk || force) buttonbar.appendChild(siteToolbarCell("Save","saveBookmark()","save.png"));
	if (curbk) buttonbar.appendChild(siteToolbarCell("Delete","deleteBookmark()","delete.png"));

	buttonbar.appendChild(siteToolbarCell("Create New Bookmark","addBookmark()","new.png"));

}

function selectAccount() {

	loadButtons();
	updateSiteStatus("Loading bookmarks");

	var p = new PROTO();
	p.add("command","docmgr_bookmark_get");
	p.add("account_id",ge("account_id").value);
	p.post(DOCMGR_API,"writeBKList");

}

function writeBKList(data) {

	clearSiteStatus();
	clearElement(blist);

	bkdata = data;

	if (data.error) alert(data.error);
	else if (!data.bookmark) blist.appendChild(ce("div","","","No bookmarks found for user"));
	else {

		for (var i=0;i<data.bookmark.length;i++) {

			var row = ce("div","","",data.bookmark[i].name);
			setClick(row,"selectBookmark('" + data.bookmark[i].id + "')");
			blist.appendChild(row);

		}

	}

}

function selectBookmark(id) {

	clearElement(binfo);

	for (var i=0;i<bkdata.bookmark.length;i++) {
		if (bkdata.bookmark[i].id==id) {
			loadBookmark(bkdata.bookmark[i]);
			break;
		}
	}

}

function loadBookmark(bk) {

	if (bk) curbk = bk.id;
	loadButtons(1);

	//name
	var namecell = ce("div","bkCell");
	namecell.appendChild(ce("div","formHeader","","Bookmark Name"));
	var tb = createTextbox("name");
	if (bk) tb.value = bk.name;
	namecell.appendChild(tb);

	//expandable
	var excell = ce("div","bkCell");
	var cb = createCheckbox("expand","t");
	excell.appendChild(cb);
	excell.appendChild(ctnode("Make bookmark expandable"));
	if (bk && bk.expandable=="t") cb.checked = true;

	//where it points to
	var objcell = ce("div","bkCell");
	objcell.appendChild(ce("div","formHeader","","Points to collection"));
	var cont = ce("div");
	objcell.appendChild(cont);

  var opt = new Array();
  opt.container = cont;
  opt.mode = "radio";
  opt.ceiling = "0";
  opt.ceilingname = "Home";
  if (bk) opt.curval = bk.object_id;
	opt.formname = "object_id";
  var t = new TREEFORM();
  t.load(opt);

	//put it all together
	binfo.appendChild(namecell);
	binfo.appendChild(excell);
	binfo.appendChild(objcell);

}

function saveBookmark() {

	updateSiteStatus("Saving bookmark");

	var p = new PROTO();
	p.add("command","docmgr_bookmark_save");
	p.add("account_id",ge("account_id").value);
	if (curbk) p.add("bookmark_id",curbk);
	p.addDOM(binfo);
	p.post(DOCMGR_API,"writeSaveBK");

}

function writeSaveBK(data) {

	clearSiteStatus();

	if (data.error) alert(data.error);
	else {
		bkid = data.bookmark_id;
		selectAccount();
	}

}


function deleteBookmark() {

	if (confirm("Are you sure you want to delete this bookmark?")) {
	
		updateSiteStatus("Deleting bookmark");

		var p = new PROTO();
		p.add("command","docmgr_bookmark_delete");
		p.add("bookmark_id",curbk);
		p.post(DOCMGR_API,"writeDeleteBK");

	}

}


function writeDeleteBK(data) {

	clearSiteStatus();
	if (data.error) alert(data.error);
	else {
		bkid = "";
		clearElement(binfo);
		selectAccount();
	}
}

function addBookmark() {
	clearElement(binfo);
	curbk = "";
	loadBookmark();
}
