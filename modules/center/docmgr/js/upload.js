/**********************************************************************
	FILE:		upload.js
	PURPOSE:	contains functions pertaining to uploading
			files into the docmgr api
**********************************************************************/
var checkinobject;

/******************************************************************
	FUNCTION:	uploadFile
	PURPOSE:	opens the popup window to select a file
			and upload into the system
******************************************************************/
function uploadFile() {

	var cont = openSitePopup("450","350");

	var filecell = ce("div","popupCell");
	var filehead = ce("div","formHeader","","Select File To Upload");
	var fileform = createForm("file","uploadfile");
	fileform.setAttribute("multiple","true");
	filecell.appendChild(filehead);
	filecell.appendChild(fileform);

	var desccell = ce("div","popupCell");
	var deschead = ce("div","formHeader","","Description");
	var descform = createTextarea("objectDescription");
	desccell.appendChild(deschead);
	desccell.appendChild(descform);

	var sbtcell = ce("div","popupCell");
	var sbtbutton = createBtn("submitObject","Upload File","runUploadFile()");
	sbtcell.appendChild(sbtbutton);

	cont.appendChild(filecell);
	cont.appendChild(desccell);
	cont.appendChild(showKeywordOptions());
	cont.appendChild(sbtcell);

}

/******************************************************************
	FUNCTION:	runUploadFile
	PURPOSE:	submits the selected single file to
			the api
******************************************************************/
function runUploadFile() {

	if (!checkRequiredKeywords())
	{

		alert("Not all required keywords have been filled out");
		return false;

	}

	//stop any background processes from running
	siteFileUpload = 1;

	updateSiteStatus("Uploading file");

	//again, ie...
	if (document.all) {
		window.frames["uploadframe"].document.open();
		window.frames["uploadframe"].document.write("");
		window.frames["uploadframe"].document.close();
	} else {	
		clearElement(window.frames["uploadframe"].document);
	}

	var cont = ge("uploadContainer");
	clearElement(cont);

	var fileval = ge("uploadfile").value;

	//how is the directory structure
	if (fileval.indexOf("/")!=-1) var arr = fileval.split("/");
	else var arr = fileval.split("\\");

	var fn = arr.pop();

	//this will happen n several stages.  First, we send the object info to the server, then when send the file itself
	//setup the xml
	var p = new PROTO();
	p.add("command","docmgr_file_save");
	p.add("parent_path",curpath);
	p.add("name",fn);
	p.add("summary",ge("objectDescription").value);
	if (ge("keywordTable")) p.addDOM(ge("keywordTable"));

	var url = DOCMGR_API + "?" + p.encodeData();

	//append a timestamp for ie to stop the god damn caching
	if (document.all) 
	{
		var d = new Date();
		url += "&timestamp=" + d.getTime();
	}

	//copy our file form into the actual form container so it can be submitted
	var ufarr = sitepopupwin.getElementsByTagName("input");
	cont.appendChild(ufarr[0]);

	closeSitePopup();
	closeKeepAlive();

	document.pageForm.action = url;
	document.pageForm.target = "uploadframe";	
	document.pageForm.submit();

	timer = setInterval("checkUpload()","100");

}

/******************************************************************
	FUNCTION:	checkRequiredKeywords
	PURPOSE:	make sure all required keywords have been filled out
******************************************************************/

function checkRequiredKeywords()
{

	var ret = true;
	var tbl = ge("keywordTable");

	if (tbl)
	{

	var arr = tbl.getElementsByTagName("input");
	
		for (var i=0;i<arr.length;i++)
		{
	
			var kid = arr[i].getAttribute("keyword_id");
			var req = arr[i].getAttribute("required");
			
			if (kid && req && req=="t" && arr[i].value.length=="0")
			{
	
				ret = false;
				break;
	
			}
	
		}
	
	}

	return ret;

}

/******************************************************************
	FUNCTION:	checkUpload
	PURPOSE:	determines if they submitted files have been
			processed by the api.  If they have, refreshes
			the interface or handles errors
******************************************************************/
function checkUpload() {

	//this was so much cleaner w/o ie handling
	if (document.all) {

		var tmp = window.frames["uploadframe"].document;

		if (tmp.XMLDocument) var txt = tmp.XMLDocument.documentElement;
		else return false;

	}
	else var txt = window.frames["uploadframe"].document;

	var err = txt.getElementsByTagName("error");
	var success = txt.getElementsByTagName("success");

	if (err.length > 0 || success.length > 0) {

		clearSiteStatus();
    clearInterval(timer);

		//clear our multi-upload forms
  	clearElement(ge("uploadList"));
  	clearElement(ge("uploadContainer"));

		//allow background processes
		siteFileUpload = 0;

		//if success just refresh, otherwise show the error
		if (success.length > 0) {
			browsePath();
		} else {
			alert(err[0].firstChild.nodeValue);
		}

		//again, ie...
		if (document.all) {
			window.frames["uploadframe"].document.open();
			window.frames["uploadframe"].document.write("");
			window.frames["uploadframe"].document.close();
		} else {		
			clearElement(window.frames["uploadframe"].document);
		}

	}

}

/******************************************************************
	FUNCTION:	multiUpload
	PURPOSE:	opens the popup for uploading multiple
			files to the api simultaneously
******************************************************************/

function multiUpload() {

	var cont = openSitePopup("450","350");

	var filecell = ce("div","popupCell","uploadFileForm");
	var filehead = ce("div","formHeader","","Select Files To Upload");
	var fileform = createForm("file","uploadfile");
	setChange(fileform,"addFile()");
	filecell.appendChild(filehead);
	filecell.appendChild(fileform);
	cont.appendChild(filecell);

	var filelist = ce("div","popupCell");
	filelist.appendChild(ce("div","formHeader","","The following files will be uploaded"));
	var uploadlist = ce("div","","uploadList","No files selected");
	filelist.appendChild(uploadlist);
	cont.appendChild(filelist);

	var sbtcell = ce("div","popupCell");
	var sbtbutton = createBtn("submitObject","Upload File","runMultiUpload()");
	sbtcell.appendChild(sbtbutton);
	cont.appendChild(sbtcell);

}

/******************************************************************
	FUNCTION:	addFile
	PURPOSE:	adds a file to the upload queue after picked
			by the user
******************************************************************/
function addFile() {

	var list = ge("uploadList");
	var cont = ge("uploadContainer");

	var arr = list.getElementsByTagName("li");
	if (arr.length==0) clearElement(list);

	//now create a text indicator to show the file
	var newfile = ce("li");

	//get our form containing the file we want to upload
	filediv = ge("uploadFileForm");
	var ufarr = filediv.getElementsByTagName("input");
	var uf = ufarr[0];

	if (!uf || !uf.value) return false;

	//this is kind of awkward, but I couldn't get a file input to clone
	//properly in i.e.  Basically we move the file object used to select files
	//down and change it's name, then create a new one in it's place

	//copy the current one and change its name, then append it into our form
	uf.setAttribute("name","uploadfile[]");
	uf.style.visibility="hidden"; 
	uf.style.position="absolute";
	uf.style.left="0";
	uf.style.top="0";
	cont.appendChild(uf);

	//create a new one
	var uploadfile = ce("input");
	uploadfile.type = "file";
	setChange(uploadfile,"addFile()");
	filediv.appendChild(uploadfile);

	//the link for clearing the file
	var cleardiv = ce("div");
	setFloat(cleardiv,"right");
	cleardiv.style.textAlign="right";
 
	newfile.setAttribute("filename",uf.value);

	var clearlink = ce("a","","","[Remove]");
	setClick(clearlink,"clearUpload(event)");
	clearlink.setAttribute("href","javascript:void(0)");
	cleardiv.appendChild(clearlink);
	newfile.appendChild(cleardiv);  

	var lbldiv = ce("div");
	if (uf.value.indexOf("/") != -1) var stArr = uf.value.split("/");
	else var stArr = uf.value.split("\\");
	var len = stArr.length - 1;
	lbldiv.appendChild(ctnode(stArr[len]));
	newfile.appendChild(lbldiv);

	//add to the parent
	list.appendChild(newfile);

}


/******************************************************************
	FUNCTION:	clearUpload
	PURPOSE:	clears a selected file from the upload
			queue
******************************************************************/
function clearUpload(e) {

	var ref = getEventSrc(e).parentNode.parentNode;
	var fp = ref.getAttribute("filename");

	//remove the div
	ref.parentNode.removeChild(ref);

	var list = ge("uploadList");
	var cont = ge("uploadContainer");
	var farr = cont.getElementsByTagName("input");

	for (var i=0;i<farr.length;i++) 
	{

		if (farr[i].value==fp) 
		{
			cont.removeChild(farr[i]);
		}

	}

	var liarr = list.getElementsByTagName("li");
	if (liarr.length==0) 
	{
		clearElement(list);
		list.innerHTML = "No files selected";
	}

}

/******************************************************************
	FUNCTION:	runMultiUpload
	PURPOSE:	submits the multi-upload queue to the api
******************************************************************/
function runMultiUpload() {

	//stop any background processes from running
	siteFileUpload = 1;

	updateSiteStatus("Uploading all files");
	closeSitePopup();

	//again, ie...
	if (document.all) 
	{
	  window.frames["uploadframe"].document.open();
	  window.frames["uploadframe"].document.write("");
	  window.frames["uploadframe"].document.close();  
	} else {
	  clearElement(window.frames["uploadframe"].document);
	}
	 
	//this will happen n several stages.  First, we send the object info to the server, then when send the file itself
	//setup the xml
	var p = new PROTO();
	p.add("command","docmgr_file_multisave");
	p.add("parent_path",curpath);
	var url = DOCMGR_API + "?" + p.encodeData();

	//append a timestamp for ie to stop the god damn caching
	if (document.all) 
	{
	  var d = new Date();
	  url += "&timestamp=" + d.getTime();
	}

	closeKeepAlive();

	document.pageForm.action = url;
	document.pageForm.target = "uploadframe";
	document.pageForm.submit();

	timer = setInterval("checkUpload()","100");

}

/***************************************************************
	FUNCTION:	showKeywordOptions
	PURPOSE:  show keyword data if there is any
***************************************************************/
function showKeywordOptions() {

	var keycont = ce("div","keywordList");
	if (keywords.length > 0)
	{

		keycont.appendChild(ce("div","formHeader","","Keywords"));
	
		var tbl = createTable("keywordTable");
		var tbd = ce("tbody");
		tbl.appendChild(tbd);
		keycont.appendChild(tbl);

		for (var i=0;i<keywords.length;i++)
		{
	
			var curkey = keywords[i];
	
			//the row and the options
			var row = ce("tr","keywordRow");
			row.appendChild(createHidden("keyword_id[]",curkey.id));
	
			//display the name
			row.appendChild(ce("td","keywordLabel","",curkey.name));
	
			//display the options
			if (curkey.type=="select") 
			{
	
				var tb = createSelect("keyword_value[]");
				if (curkey.option)
				{
	
					for (var c=0;c<curkey.option.length;c++)
					{
						tb[c] = new Option(curkey.option[c].name,curkey.option[c].id);
					}
	
				}
	
			} else {
	
				//search string	
				var tb = createTextbox("keyword_value[]");
	
			}

			tb.setAttribute("keyword_id",curkey.id);
			tb.setAttribute("required",curkey.required);
					
			row.appendChild(ce("td","keywordEntry","",tb));
	
			tbd.appendChild(row);
	
		}
	
	}

	return keycont;

}


/******************************************************************
	FUNCTION:	checkinFile
	PURPOSE:	opens the popup window to select a file
			and upload into the system
******************************************************************/
function checkinFile(id) {

	checkinobject = id;

	var cont = openSitePopup("450","350");
	cont.appendChild(ce("div","sitePopupHeader","","File Check-In"));

	var filecell = ce("div","sitePopupCell","checkinCell");
	var filehead = ce("div","formHeader","","Select File To Upload");
	var fileform = createForm("file","uploadfile");
	filecell.appendChild(filehead);
	filecell.appendChild(fileform);

	var custcell = ce("div","sitePopupCell","customVersionCell");
	var custhead = ce("div","formHeader","","Custom Version");
	var custform = createTextbox("custom_version");
	custcell.appendChild(custhead);
	custcell.appendChild(custform);

	var desccell = ce("div","sitePopupCell");
	var deschead = ce("div","formHeader","","Revision Notes");
	var descform = createTextarea("revision_notes");
	desccell.appendChild(deschead);
	desccell.appendChild(descform);

	var sbtcell = ce("div","sitePopupCell");
	var sbtbutton = createBtn("submitObject","Checkin File","runCheckinFile()");
	sbtcell.appendChild(sbtbutton);

	cont.appendChild(custcell);
	cont.appendChild(filecell);
	cont.appendChild(createCleaner());
	cont.appendChild(desccell);
	cont.appendChild(sbtcell);

}

/******************************************************************
	FUNCTION:	runCheckinFile
	PURPOSE:	submits the selected single file to
			the api
******************************************************************/
function runCheckinFile() {

	//stop any background processes from running
	siteFileUpload = 1;

	updateSiteStatus("Checking in file");

	//again, ie...
	if (document.all) {
		window.frames["uploadframe"].document.open();
		window.frames["uploadframe"].document.write("");
		window.frames["uploadframe"].document.close();
	} else {	
		clearElement(window.frames["uploadframe"].document);
	}

	var cont = ge("uploadContainer");
	clearElement(cont);

	var fileval = ge("uploadfile").value;

	//how is the directory structure
	if (fileval.indexOf("/")!=-1) var arr = fileval.split("/");
	else var arr = fileval.split("\\");

	var fn = arr.pop();

	//this will happen n several stages.  First, we send the object info to the server, then when send the file itself
	//setup the xml
	var p = new PROTO();
	p.add("command","docmgr_file_save");
  p.add("unlock","1");
  p.add("object_id",checkinobject);
  p.add("custom_version",ge("custom_version").value);
  p.add("revision_notes",ge("revision_notes").value);

	var url = DOCMGR_API + "?" + p.encodeData();

	//append a timestamp for ie to stop the god damn caching
	if (document.all) 
	{
		var d = new Date();
		url += "&timestamp=" + d.getTime();
	}

	//copy our file form into the actual form container so it can be submitted
	var ufarr = sitepopupwin.getElementsByTagName("input");

	//in this case, our file form is the second one
	cont.appendChild(ufarr[1]);

	closeSitePopup();
	closeKeepAlive();

	document.pageForm.action = url;
	document.pageForm.target = "uploadframe";	
	document.pageForm.submit();

	timer = setInterval("checkUpload()","100");

}
