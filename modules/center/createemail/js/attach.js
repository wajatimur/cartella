
/************************************************************
	FUNCTION: loadAttachments
	PURPOSE: sets up the left column for attachments
************************************************************/
function loadAttachments() {

  //set the title for our mailbox div
	showModNav();
  moduleNavCtrl.innerHTML = "Attachments";  

	var fileupload = ce("div","fileUploads");
	fileupload.appendChild(ce("div","attachHeader","","File Attachments"));

	uploaddiv = ce("div","uploadDiv");

	var upload = createForm("file","attachUpload");		//the upload form
	upload.size = 17;
	upload.style.fontSize = "9px";
	setChange(upload,"addFile()");
	uploaddiv.appendChild(upload);									//put it together

	attachlist = ce("div","attachList");

	//assemble it all
	fileupload.appendChild(uploaddiv);
	fileupload.appendChild(attachlist);

	moduleNav.appendChild(fileupload);

	//docmgr uploads
	var docupload = ce("div","fileUploads");
	docupload.appendChild(ce("div","attachHeader","","DocMGR Attachments"));

	var docform = ce("div","uploadDiv");
	docform.appendChild(createBtn("selectDoc","Select Document","selectDocmgr()"));

	doclist = ce("div","attachList");

	docupload.appendChild(docform);
	docupload.appendChild(doclist);

	moduleNav.appendChild(docupload);

	loadDocAttachments();

}

//load our list of current attachments
function showAttachList() {

	attachlist.innerHTML = "<div class=\"statusMessage\">Updating</div>";
	var url = "index.php?module=viewattachlist";
	protoReq(url,"writeAttachList");

}

function writeAttachList(data) {

	 
	attachlist.innerHTML = "";
	if (data.count && data.count>0) {

		for (var i=0;i<data.file.length;i++) {
			createAttachEntry(data.file[i]);
		}

	} 
	//else attachlist.innerHTML = "<div class=\"errorMessage\" style=\"margin-left:5px\">No files to display</div>";

}

function createAttachEntry(entry) {

	var row = ce("li");

	var delimg = ce("img");
	setClick(delimg,"attachDelete('" + entry.name + "')");
	delimg.setAttribute("src",theme_path + "/images/icons/delete.png");

	row.appendChild(delimg);
	row.appendChild(ctnode(entry.name));

	attachlist.appendChild(row);

}

function attachDelete(name) {

	updateSiteStatus("Removing Attachment");
	var url = "index.php?module=attachdelete&action=delete&filename=" + name;
	protoReq(url,"writeAttachDelete");

}

function writeAttachDelete(data) {

	 
	if (data.error) alert(data.error);
	else showAttachList();

}

function checkUpload() {

	var txt = uploadframe.document.body.innerHTML;

	if (txt.length > 0) {

		//clear in progress thingy
		siteFileUpload = 0;
		clearInterval(timer);

		if (txt.indexOf("Error")!=-1) {
			alert(txt);
		}

		//reload our attachment list
		loadAttachments();
		showAttachList();

	}

}

function clearUpload(fp) {

        //get the filter select box value
        cd = document.getElementById("searchCriteria");

        //get all bullets in our area
        var liarr = attachlist.getElementsByTagName("li");

        var num = liarr.length;
        var i;

        //cycle thru the bullets
        for (i=0;i<num;i++) {

                //find the hidden input file field.  If it's value matches our file pointer
                //then remove it from the list
                var curli = liarr[i];

								if (curli) {

	                var filearr = curli.getElementsByTagName("input");
	                var curfile = filearr[0];

	                //we have a match, remove this node
	                if (curfile.value==fp) attachlist.removeChild(curli);

								}
        }
}



//add a file to our queue
function addFile() {

	//get our form in the upload div containing the file we want to upload
	var ufarr = uploaddiv.getElementsByTagName("input");
	var uf = ufarr[0];

	if (!uf || !uf.value) return false;

	clearFrame();

	//our upload form is not actually in a form.  So, when the user sets the file
	//we clone the file input into our actual upload form, then submit the form

	//remove all current forms from uploadform so they don't get submitted again
	document.uploadForm.innerHTML = "";

	//copy the current file input to the upload form from the upload div
	uf.style.visibility="hidden";
	uf.style.position="absolute";
	uf.style.left="0";
	uf.style.top="0";
	uploaddiv.removeChild(uf);							//remove from upload div
	document.uploadForm.appendChild(uf);		//put in upload form

	//create a new one and put it in our upload div
	var uploadfile = document.createElement("input");
	uploadfile.type = "file";
	uploadfile.setAttribute("name","attachUpload");
	uploadfile.setAttribute("id","attachUpload");
	uploadfile.setAttribute("size","17");
	uploadfile.style.fontSize="9px";
	setChange(uploadfile,"addFile()");
	uploaddiv.appendChild(uploadfile);

	//set that we are uploading something
	siteFileUpload = 1;

	//submit our form
	document.uploadForm.submit();

	//tell teh user we are uploading
	uploaddiv.innerHTML = "<div class=\"statusMessage\">Uploading</div>";

	//monitor the iframe for the upload to finish
	timer = setInterval("checkUpload()","100");
}


function mbSelectObject(ret) {

	if (attachmode=="attach") attachSelectObject(ret);
	else templateSelectObject(ret);

}

function selectDocmgr() {

		attachmode = "attach";

   	//launch our selector to pick where to save the file
    var parms = centerParms(600,460,1) + ",resizable=no,scrollbars=no";
    var url = "index.php?module=minib";

    var ref = window.open(url,"_minib",parms);

}


function attachSelectObject(data) {

  //make sure they picked a document or file
  if (data.type!="document" && data.type!="file") {
    alert("You must pick an DocMGR Document or File");
    return false;
  }

	//push onto our attachment array
	docattach.push(data.id);
	createDocEntry(data.id,data.name);

}

function createDocEntry(id,name) {

	var row = ce("li");
	row.setAttribute("object_id",id);

	var delimg = ce("img");
	setClick(delimg,"docDelete('" + id + "')");
	delimg.setAttribute("src",theme_path + "/images/icons/delete.png");

	row.appendChild(delimg);
	row.appendChild(ctnode(name));

	doclist.appendChild(row);

}

function docDelete(id) {

	var arr = doclist.getElementsByTagName("li");

	for (var i=0;i<arr.length;i++) {

		if (arr[i].getAttribute("object_id")==id) {
			arr[i].parentNode.removeChild(arr[i]);
		}

	}

	for (var i=0;i<docattach.length;i++) {

		if (docattach[i]==id) docattach.splice(i,1);

	}

}


function loadDocAttachments() {

	var ids = ge("docmgrAttachments").value;

	if (ids.length > 0) {

		updateSiteStatus("Getting Document Information");

	  //setup the xml
		var p = new PROTO();
		p.add("command","docmgr_search_search");
		p.add("object_filter",ids);
		p.post(DOCMGR_API,"writeDocAttachResults");

	}

}

function writeDocAttachResults(data) {

	clearSiteStatus();
	 

	if (data.error) alert(data.error);
	else if (data.object) {

		for (var i=0;i<data.object.length;i++) {

			//push onto our attachment array
			docattach.push(data.object[i].id);

			createDocEntry(data.object[i].id,data.object[i].name);

	  }


	}


}

