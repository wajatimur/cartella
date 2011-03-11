

function loadTemplate() {

	attachmode = "template";

	//load minib!
	var url = "index.php?module=minib";
	url += "&objectFilter=doc,docx&objectTypeFilter=document,file,collection";
	
	//launch our selector to pick where to save the file
  var parms = centerParms(600,460,1) + ",resizable=no,scrollbars=no";	

	var ref = window.open(url,"_minib",parms);
	ref.focus();

}

function templateSelectObject(data) {

	updateSiteStatus("Loading Template Data");

	//if it is a document, get the html from docmgr
	if (data.type=="document") {
	
		setTimeout("getDocumentContent('" + data.id + "')","10");

	} else if (data.type=="file") {

		//otherwise pull the file and convert it to html
		setTimeout("getFileContent('" + data.id + "')","10");


	} else {
	
		alert("Invalid document type selected");

	}

}

function getDocumentContent(id) {

  //this will happen n several stages.  First, we send the object info to the server, then when send the file itself
  //setup the xml
	var p = new PROTO();
	p.add("command","docmgr_document_get");
	p.add("object_id",id);
	p.post(DOCMGR_API,"writeDocumentContent");

}

function writeDocumentContent(data) {

	clearSiteStatus();

  if (data.error) alert(data.error);
  else {

    //set filesaved so we automatically overwrite
    if (!data.content) data.content = "";
    CKEDITOR.instances.editor_content.setData(data.content);

  }

}

function getFileContent(id) {

	var p = new PROTO();
	p.add("command","docmgr_file_getashtml");
	p.add("object_id",id);
	p.post(DOCMGR_API,"writeFileContent");

}

function writeFileContent(data) {

	clearSiteStatus();

   
  if (data.error) alert(data.error);
  else {

    //set filesaved so we automatically overwrite
    if (!data.content) data.content = "";
    CKEDITOR.instances.editor_content.setData(data.content);

  }

}


function hideTemplate() {
	templatebox.style.visibility = "hidden";
	templatebox.style.display = "none";
}

function loadTemplate1() {

	//create our popup window to display the templates
	templatebox = ge("templatewin");	
	templatebox.innerHTML = "";
	templatebox.style.visibility = "visible";
	templatebox.style.display = "block";

	//position it
  if (document.all) {
    var width = document.body.offsetWidth;
    var height = document.body.offsetHeight;
  } else {
    var width = window.innerWidth;
    var height = window.innerHeight;
  }
   
  xPos = (width - 500) / 2 - 150;
  yPos = (height - 350) / 2 - 150;
  templatebox.style.left = xPos + "px";
  templatebox.style.top = yPos + "px"; 		

	//populate it

	//close image
	var closeimg = ce("img","closeImage");
	closeimg.setAttribute("src",theme_path + "/images/icons/close.png");
	setClick(closeimg,"hideTemplate()");

	//header
	var header = ce("div","templateHeader","","Please select a template");

	//private
	var privheader = ce("div","templateSectionHeader","","Private Templates");
	var pubheader = ce("div","templateSectionHeader","","Public Templates");

	//lists go here
	privcontent = ce("div","","privateList");
	pubcontent = ce("div","","pubList");
	privcontent.innerHTML = "<div class=\"statusMessage\">Loading...</div>";
	pubcontent.innerHTML = "<div class=\"statusMessage\">Loading...</div>";

	//columns
	var cont = ce("div","templateContent");
	var lc = ce("div","leftColumn");
	var rc = ce("div","rightColumn");

	//attach to columns
	lc.appendChild(privheader);
	lc.appendChild(privcontent);
	rc.appendChild(pubheader);
	rc.appendChild(pubcontent);
	cont.appendChild(lc);
	cont.appendChild(rc);

	//put it all together
	templatebox.appendChild(closeimg);
	templatebox.appendChild(header);
	templatebox.appendChild(cont);
	
	//get our public letters
	regHandler("letterlist","writeLetters");
	var url = "index.php?module=letterlist&filter=public";
	loadXMLReq(url);

	//get our personal letters
	var url = "index.php?module=letterlist&filter=private";
	loadXMLReq(url);

}

function writeLetters(data) {

	 

	if (data.filter=="public") var ref = pubcontent;
	else var ref = privcontent;

	if (data.error) alert(data.error);
	else if (!data.letter) ref.innerHTML = "<div class=\"errorMessage\">No templates found</div>";
	else {

		ref.innerHTML = "";

		for (var i=0;i<data.letter.length;i++) {
			ref.appendChild(writeTemplateEntry(data.letter[i]));
		}

	}

}

function writeTemplateEntry(entry) {

	var row = ce("div","templateList","",entry.name);
	setClick(row,"applyTemplate('" + entry.id + "')");
	return row;

}

function applyTemplate(id) {

  var et = CKEDITOR.instances.editor_content.getData();
  if (et.length > 10) {
    if (!confirm("There is already text in the editor.  It will be overwritten by the template.  Do you wish to continue?")) {
			hideTemplate();
			return false;
		}
  }

	regHandler("letterlist","writeTemplate");
	var url = "index.php?module=letterlist&letterId=" + id;
	loadXMLReq(url);

}

function writeTemplate(data) {

	 
	if (data.error) alert(data.error);
	else if (!data.letter) alert("Error retrieving letter");
	else {

		CKEDITOR.instances.editor_content.setData(data.letter[0].content);
		hideTemplate();

	}


}

