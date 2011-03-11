
var filelist;
var popupref;
var editlist;
var orderchanged = 0;
var advpath;

function loadEditPage() {

	changeBrowseDir();

}

function loadEditActions() {

	clearElement(toolbar);
	toolbar.appendChild(ce("div","","showBrowsePath"));
	toolbar.appendChild(siteToolbarCell("Merge Selected Files","mergeFiles()","merge.png"));
	toolbar.appendChild(siteToolbarCell("Import Wizard","loadImportPage()","save.png"));
	toolbar.appendChild(siteToolbarCell("Import All","importAll()","save.png"));

}

function changeBrowseDir(loc) {

	if (!ge("showBrowsePath")) loadEditActions();

	setBrowsePath(loc);
	editBrowseDir();

}

function editBrowseDir() {

	clearElement(container);
	filelist = ce("div");
	container.appendChild(filelist);

	updateSiteStatus("Loading file list");

	var url = "index.php?module=diprocess&action=browse&path=" + browsepath;
	protoReq(url,"writeEditBrowseDir"); 

}

function writeEditBrowseDir(data) {

	clearSiteStatus();
	 
	clearElement(filelist);

	if (data.error) alert(data.error);
	else {

		if (!data.file) filelist.appendChild(ce("div","errorMessage","","No files found"));
		else {

			for (var i=0;i<data.file.length;i++) {
				filelist.appendChild(editFileRow(data.file[i],i));
				filelist.appendChild(createCleaner());
			}

		}

	}

}

function editFileRow(file,idx) {

	var row = ce("div","editFileRow");
	row.setAttribute("path",file.path);
	row.setAttribute("filename",file.name);

	var cb = createCheckbox("filePath[]",file.path);
	
	var img = ce("img","editThumb");
	img.setAttribute("src",file.small_thumb + "?time=" + new Date().getTime());
	setClick(img,"viewFilePreview('" + file.huge_thumb + "')");

	var name = ce("div","editName","",file.name);
	name.appendChild(ce("br"));
	name.appendChild(ctnode(file.size));
	
	var optdiv = ce("div");
	optdiv.appendChild(createEditAction("renameFile(event)","Rename file","rename.png"));
	optdiv.appendChild(createEditAction("advancedEdit(event)","Advanced Edit","edit.png"));
	optdiv.appendChild(createEditAction("deleteFile(event)","Delete file","delete.png"));
	optdiv.appendChild(createEditAction("directImportFile('" + idx + "')","Import this file","import.png"));

	if (file.tag) {

		var metadiv = ce("div");
		var num = 0;

		for (var i=0;i<file.tag.length;i++) {

			if (!file.tag[i].value) continue;

			var txt = file.tag[i].title + ": " + file.tag[i].value;
			metadiv.appendChild(ctnode(txt));
			metadiv.appendChild(ce("br"));
			num++;

		}

		name.appendChild(metadiv);

		//hack for top/bottom centering because css let me down
		var mt = num * 10;
		name.style.marginTop = (45 - mt) + "px";

	}

	row.appendChild(cb);
	row.appendChild(img);
	row.appendChild(name);
	row.appendChild(optdiv);

	return row;

}

function viewFilePreview(src) {

	var h = getWinHeight()-30;
	var ref = openSitePopup(700,h);
	var cell = ce("div","sitePopupCell","largePreviewDiv");

	var img = ce("img","largePreviewImg");
	img.setAttribute("src",src);

	cell.appendChild(img);
	ref.appendChild(cell);

}


function createEditAction(action,title,icon) {

	var img = ce("img","editAction");
	img.setAttribute("src",theme_path + "/images/icons/" + icon);
	setClick(img,action);
	img.setAttribute("title",title);

	return img;

}

function mergeFiles() {

	var arr = filelist.getElementsByTagName("input");
	var check = 0;

	for (var i=0;i<arr.length;i++) {
		if (arr[i].checked==true) {

			//make sure it's a pdf that we are trying to merge
			if (getExtension(arr[i].value)!="pdf") {
				alert("You can only merge pdf files");
				return false;
			}
			check++;
		}
	}

	if (check==0) {
		alert("You must select the files you want to merge");
		return false;
	}
	if (check==1) {
		alert("You must select more than one file to merge");
		return false;
	}

	updateSiteStatus("Merging selected files");
	var url = "index.php?module=diprocess&action=merge&path=" + browsepath + "&" + dom2Query(filelist);
	protoReq(url,"writeEditFileAction","POST");

}

function writeEditFileAction(data) {

	if (data.error) alert(data.error);
	else {

		//reload our files
		editBrowseDir();

	}

}

function getExtension(name) {

	var pos = name.lastIndexOf(".");

	//if no extension, return empty
	if (pos==-1) return "";
	else return name.substr(pos+1).toLowerCase();

}

function deleteFile(e) {

	if (confirm("Are you sure you want to remove this file?")) {

		var ref = getEventSrc(e).parentNode.parentNode;
		var p = ref.getAttribute("path");

		var url = "index.php?module=diprocess&action=delete&path=" + browsepath + "&filePath=" + p;
		protoReq(url,"writeEditFileAction","POST");

	}
}

function renameFile(e) {

	var ref = getEventSrc(e).parentNode.parentNode;
	var name = ref.getAttribute("filename");
	var path = ref.getAttribute("path");

	var newname = prompt("Please enter the new file name",name);
	if (newname) {

		var url = "index.php?module=diprocess&action=rename&path=" + browsepath + "&filePath=" + path + "&name=" + newname;
		protoReq(url,"writeEditFileAction","POST");

	}

}

function advancedEdit(e) {

	var ref = getEventSrc(e).parentNode.parentNode;
	var name = ref.getAttribute("filename");
	var path = ref.getAttribute("path");
	advpath = path;

	updateSiteStatus("Preparing file for advanced editing");
	var url = "index.php?module=diprocess&action=advedit&path=" + browsepath + "&filePath=" + path;
	protoReq(url,"writeAdvancedEdit","POST");

}

function writeAdvancedEdit(data) {

	clearSiteStatus();
	 

	if (data.error) alert(data.error);
	else {

		popupref = openSitePopup("800","500");
		writeADVList(data);

	}

}

function selectFiles() {

	var ref = ge("fileselector");
	if (ref.value=="0") return false;

	var arr = editlist.getElementsByTagName("input");

	if (ref.value=="all") {

		for (var i=0;i<arr.length;i++) {
			if (arr[i].type=="checkbox") arr[i].checked = true;
		}

	} else if (ref.value=="alternatefirst") {

		for (var i=0;i<arr.length;i++) {
			if (i%2==0) arr[i].checked = true;
			else arr[i].checked = false;
		}

	} else if (ref.value=="alternatesecond") {

		for (var i=0;i<arr.length;i++) {
			if (i%2==1) arr[i].checked = true;
			else arr[i].checked = false;
		}
		
	}

	//go back to the beginning
	ref.selectedIndex = 0;

}

function writeADVList(data) {

		clearElement(popupref);

		//selection tool
		var sel = createSelect("fileselector");
		setChange(sel,"selectFiles()");
		sel[0] = new Option("Select...","0");
		sel[1] = new Option("All files","all");
		sel[2] = new Option("Every other from first","alternatefirst");
		sel[3] = new Option("Every other from second","alternatesecond");

		var tb = ce("div","advToolbar");
		tb.appendChild(ce("div","advToolbarHeader","","Advanced File Editing"));
		tb.appendChild(sel);
    tb.appendChild(siteToolbarBtn("Rotate Left","rotateFiles('left')"));
    tb.appendChild(siteToolbarBtn("Rotate Right","rotateFiles('right')"));
    tb.appendChild(siteToolbarBtn("Flip","rotateFiles('flip')"));
    tb.appendChild(siteToolbarBtn("Save File","commitChanges()"));

		popupref.appendChild(tb);

		var lc = ce("div","leftColumn","advLeft");
		var rc = ce("div","rightColumn","advRight");
		popupref.appendChild(lc);
		popupref.appendChild(rc);
		popupref.appendChild(createCleaner());


		//list containing the files we will edit
		editlist = ce("div","","fileEditList");
		lc.appendChild(editlist);
		
		//start getting our files
		for (var i=0;i<data.file.length;i++) {
			editlist.appendChild(editListRow(data.file[i],i+1));
			editlist.appendChild(createCleaner());
		}

		new Sortables(editlist, {

        revert: { duration: 250, transition: 'linear' },
				opacity: .25,
				clone: true,
        onComplete: function() {
          orderchanged = 1;
        }

      });

		//setup sections for re-ordering and rotating
		var reorder = ce("div","advCell");
		reorder.appendChild(ce("div","formHeader","","Page Reordering"));	

		var msg = "To reorder the pages, drag them into the correct order";
		reorder.appendChild(ce("div","","",msg));
		rc.appendChild(reorder);

    //setup sections for re-ordering and rotating
    var preview = ce("div","advCell");
    preview.appendChild(ce("div","formHeader","","Page Preview (Click page to view)"));
    preview.appendChild(ce("div","","pdfPreview"));
    rc.appendChild(preview);

}

function editListRow(file,idx) {

	var row = ce("div","editListRow");
	row.setAttribute("path",file.path);
	row.setAttribute("filename",file.name);

	var cb = createCheckbox("filePath[]",file.path);
	
	var img = ce("img","editListThumb");
	img.setAttribute("src",file.small_thumb + "?time=" + new Date().getTime());
	setClick(img,"advPDFPreview('" + file.huge_thumb + "')");
	//setClick(img,"viewFilePreview('" + file.huge_thumb + "')");

	var name = ce("div","editListName","","Page " + idx);
	
	var optdiv = ce("div");

	row.appendChild(cb);
	row.appendChild(img);
	row.appendChild(name);
	row.appendChild(optdiv);

	return row;

}

function advPDFPreview(imgsrc) {

  var ref = ge("pdfPreview");
  clearElement(ref);
	imgsrc += "?time=" + new Date().getTime();

  var img = ce("img");
  img.setAttribute("src",imgsrc);
  ref.appendChild(img);

}


function writeAdvHandler(data) {

	clearSiteStatus();
	 
	orderchanged = 0;

	if (data.error) alert(data.error);
	else writeADVList(data);

}

function rotateFiles(dir) {

	var arr = popupref.getElementsByTagName("input");
	var check = "";

	var url = "index.php?module=diprocess&action=rotate&path=" + browsepath + "&direction=" + dir;

	for (var i=0;i<arr.length;i++) {
		if (arr[i].type=="checkbox" && arr[i].checked) {
			check = 1;
			url += "&file[]=" + arr[i].value;
		}
	}

	if (!check) {
		alert("You must check which files you with to rotate");
		return false;
	}

	//if we've changed the order, store that also
	if (orderchanged==1) {

		url += "&saveorder=1";

		for (var i=0;i<arr.length;i++) {
			if (arr[i].type=="checkbox") url += "&reorderFile[]=" + arr[i].value;
		}

	}

	updateSiteStatus("Rotating files");
	protoReq(url,"writeAdvHandler","POST");

}

function commitChanges() {

	updateSiteStatus("Committing changes to file");
	var url = "index.php?module=diprocess&action=commit&path=" + browsepath + "&filePath=" + advpath;

	//if we've changed the order, store that also
	if (orderchanged==1) {

		url += "&saveorder=1";
		var arr = popupref.getElementsByTagName("input");

		for (var i=0;i<arr.length;i++) {
			if (arr[i].type=="checkbox") url += "&reorderFile[]=" + arr[i].value;
		}

	}

	protoReq(url,"writeEditCommit","POST");

}

function writeEditCommit(data) {

	 
	clearSiteStatus();

	if (data.error) alert(data.error);
	else {
		closeSitePopup();
		editBrowseDir();
	}

}

