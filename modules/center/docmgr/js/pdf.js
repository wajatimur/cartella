/********************************************************************
	FILENAME: pdf.js
	PURPOSE:  contains docmgr pdf editing functions

*********************************************************************/

var orderchanged = 0;
var advpath;
var popupref;

function mergePDF() 
{

	var arr = content.getElementsByTagName("input");
	var pdfarr = new Array();
	
	for (var i=0;i<arr.length;i++) {

		if (arr[i].name=="objectId[]" && arr[i].checked==true) {

			//make sure it's a pdf that we are trying to merge
			if (getExtension(arr[i].getAttribute("object_path"))!="pdf") {
				alert("You can only merge pdf files");
				return false;
			}

			pdfarr.push(arr[i].value);

		}

	}
	
	if (pdfarr.length==0) {
		alert("You must select the files you want to merge");
		return false;
	}
	if (pdfarr.length==1) {
		alert("You must select more than one file to merge");
		return false;
	}

	//setup the xml
	updateSiteStatus("Merging selected files");
	var p = new PROTO();
	p.add("command","docmgr_pdf_merge");

	for (var i=0;i<pdfarr.length;i++) p.add("object_id",pdfarr[i]);

	p.post(DOCMGR_API,"writePDFMerge");

}

function writePDFMerge(data) {

	//show error if there is one, otherwise refresh current folder
	if (data.error) alert(data.error);
	else browsePath();

}

function getExtension(name) {

	var pos = name.lastIndexOf(".");

	//if no extension, return empty
	if (pos==-1) return "";
	else return name.substr(pos+1).toLowerCase();

}



function advPDFEdit(id) {

	curobject = id;

	//setup the xml
	updateSiteStatus("Opening file for editing");
	var p = new PROTO();
	p.add("command","docmgr_pdf_advedit");
	p.add("object_id",id);
	p.post(DOCMGR_API,"writePDFAdvEdit");

}

function writePDFAdvEdit(data) {

	clearSiteStatus();

	if (data.error) alert(data.error);
	else 
	{

		popupref = openSitePopup("800","480");
		writePDFAdvList(data);

	}

}


function writePDFAdvList(data) {

		clearElement(popupref);

		//selection tool
		var sel = createSelect("fileselector");
		setChange(sel,"selectFiles()");
		sel[0] = new Option("Select...","0");
		sel[1] = new Option("All files","all");
		sel[2] = new Option("Every other from first","alternatefirst");
		sel[3] = new Option("Every other from second","alternatesecond");

		var tb = ce("div","advToolbar");
		tb.appendChild(ce("div","advToolbarHeader","","Advanced PDF Editing"));
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
	img.setAttribute("src",site_url + "app/showpic.php?image=" + file.small_thumb + "?time=" + new Date().getTime());
	setClick(img,"advPDFPreview('" + file.huge_thumb + "')");
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

	//rework src with showpic and the site url
	imgsrc = site_url + "app/showpic.php?image=" + imgsrc + "?time=" + new Date().getTime();

	var img = ce("img");
	img.setAttribute("src",imgsrc);
	ref.appendChild(img);


}


function writePDFAdvHandler(data) {

	clearSiteStatus();
	 
	orderchanged = 0;

	if (data.error) alert(data.error);
	else writePDFAdvList(data);

}

function rotateFiles(dir) {

	var arr = popupref.getElementsByTagName("input");
	var check = "";

	//setup the xml
	var p = new PROTO();
	p.add("command","docmgr_pdf_rotate");
	p.add("direction",dir);
	p.add("object_id",curobject);

	//figure out which files to rotate
	for (var i=0;i<arr.length;i++) 
	{
		if (arr[i].type=="checkbox" && arr[i].checked) 
		{
			check = 1;
			p.add("file",arr[i].value);
		}
	}

	if (!check) 
	{
		alert("You must check which files you with to rotate");
		return false;
	}

	//if we've changed the order, store that also
	if (orderchanged==1) 
	{

		p.add("saveorder","1");

		for (var i=0;i<arr.length;i++) 
		{
			if (arr[i].type=="checkbox") p.add("reorderfile",arr[i].value);
		}

	}

	updateSiteStatus("Rotating files");
	p.post(DOCMGR_API,"writePDFAdvHandler");

}

function commitChanges() {

	//get our files
	var arr = popupref.getElementsByTagName("input");

	//setup the xml
	var p = new PROTO();
	p.add("command","docmgr_pdf_commit");
	p.add("object_id",curobject);

	//if we've changed the order, store that also
	if (orderchanged==1) 
	{

		p.add("saveorder","1");

		for (var i=0;i<arr.length;i++) 
		{
			if (arr[i].type=="checkbox") p.add("reorderfile",arr[i].value);
		}

	}

	updateSiteStatus("Saving all changes");
	p.post(DOCMGR_API,"writeEditCommit");

}

function writeEditCommit(data) 
{

	clearSiteStatus();

	if (data.error) alert(data.error);
	else 
	{
		closeSitePopup();
		browsePath();
	}

}

function selectFiles() 
{

	var ref = ge("fileselector");
	if (ref.value=="0") return false;

	var arr = editlist.getElementsByTagName("input");

	if (ref.value=="all") 
	{

		for (var i=0;i<arr.length;i++) 
		{
			if (arr[i].type=="checkbox") arr[i].checked = true;
		}

	} else if (ref.value=="alternatefirst") 
	{

		for (var i=0;i<arr.length;i++) 
		{
			if (i%2==0) arr[i].checked = true;
			else arr[i].checked = false;
		}

	} else if (ref.value=="alternatesecond") 
	{

		for (var i=0;i<arr.length;i++) 
		{
			if (i%2==1) arr[i].checked = true;
			else arr[i].checked = false;
		}
		
	}

	//go back to the beginning
	ref.selectedIndex = 0;

}
