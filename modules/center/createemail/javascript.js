
/**************************************
	global variables
**************************************/

var attachdiv;
var attachlist;
var attachmode;
var uploaddiv;
var docattach = new Array();
var doclist;
var timer;
var templatebox;
var addrbox;
var privcontent;
var pubcontent;
var cursorfocus;
var ckeditor;

window.onresize = setFrameSize;
document.onkeyup = handleKeyUp;

/*************************************************************
	FUNCTION:	loadPage
	PURPOSE:	loads our initial page view when the module loads
*************************************************************/
function loadPage() {

	setupToolbar();
	loadAttachments();
	showAttachList();
	loadEditor();

  setFrameSize();
  clearSiteStatus();

	//set an interval to save a draft every 5 minutes
	//setInterval("saveDraft()","300000");

	//if there's an objectPath specified, use it
	if (ge("objectId").value.length > 0) {
		mbSelectObject('',ge("objectType").value,'',ge("objectId").value);
	}			

}

/***********************************************
  FUNCTION: handleKeyUp
  PURPOSE:  deactivates the control key setting
************************************************/
function handleKeyUp(evt) {

  if (!evt) evt = window.event;

  if (evt.keyCode=="9" && showsuggest=="1")
  {
    pickFirstSuggest();
  }

}


/*******************************************************************
  FUNCTION: loadEditor
  PURPOSE:  load the actual editor.
  INPUTS:   curval -> html we'll populate the editor with
*******************************************************************/
function loadEditor(curval) {

   //create a new one
  ckeditor = CKEDITOR.replace('editor_content',
              {
                toolbar: 'Email',
                fullPage: true,
                on: 
                {   
                  instanceReady: function (ev) { setFrameSize();}
                }

              });

  if (curval) ckeditor.setData(curval);
  
	clearSiteStatus();
 
}


function setupToolbar() {

	var et = ge("emailToolbar");	
	et.appendChild(siteToolbarCell("Send","sendEmail()","send_email.png"));
	et.appendChild(siteToolbarCell("Address Book","loadAddressBook()","mail_generic.png"));
	et.appendChild(siteToolbarCell("Load From Template","loadTemplate()","load_template.png"));

}


//send the email
function sendEmail() {

	//if upload in progress, bail
	if (siteFileUpload==1) {
		alert("There is currently a file upload in progress.  Please wait until it is complete before sending your message");
		return false;
	}

	//make sure we have everything we need
	if (ge("to").value.length==0) {
		alert("You did not specify anyone to send your message to");
		return false;
	}

	if (ge("subject").value.length==0) {
		if (!confirm("You did not enter a subject.  Do you still wish to continue?")) return false;
	}

	//copy the docmgr attachments to the submit form
	ge("docmgrAttachments").value = docattach.join(",");

	//if we get to here send the message
	updateSiteStatus("Sending Email");
	clearFrame();

	ge("action").value = "sendEmail";
	document.pageForm.submit();

	//monitor the iframe for the upload to finish
	timer = setInterval("checkSendEmail()","100");

}

function checkSendEmail() {

	var txt = uploadframe.document.body.innerHTML;

	if (txt.length > 0) {

		clearSiteStatus();
		clearInterval(timer);

		if (txt=="emailsuccess") {
			updateSiteStatus("Email sent successfully");
			setTimeout("loadPrevPage()","1000");

		}
		else alert(txt);
	}

}

//send the email
function saveDraft() {

	//bail if a file upload is in progress
	if (siteFileUpload==1) return false;

	//bail if nothing is different
	if (!ckeditor.checkDirty()) return false;

	//if we get to here send the message
	updateSiteStatus("Saving Draft");

	ge("action").value = "saveDraft";
	clearFrame();
	document.pageForm.submit();

	//monitor the iframe for the upload to finish
	timer = setInterval("checkSaveDraft()","100");

}

function checkSaveDraft() {

	var txt = uploadframe.document.body.innerHTML;

	if (txt.length > 0) {
		clearInterval(timer);

		if (txt.indexOf("draftsuccess")!=-1) {

			updateSiteStatus("Draft saved successfully");

			//pull the draft uid out for later use
			var draftuid = txt.substr(12);	//the text without "draftsuccess"
			ge("uid").value = draftuid;			//store for later saves

			setTimeout("clearSiteStatus()","2000");

		}
		else {
			alert(txt);
			clearSiteStatus();
		}

	}

}

function loadPrevPage() {

	var url;

	//if a popup, refresh the parent.  
	if (ge("siteTemplate").value=="solo") {

		url = window.opener.location.href;
		window.opener.location.href = url;
		self.close();

	} else if (ge("taskId").value.length > 0) {

		url = "index.php?module=managetasks";
		location.href = url;

	} else if (ge("contactId").value.length > 0) {

		var arr = ge("contactId").value.split(",");
		var url = "index.php?module=editcontact&contactId=" + arr[0];
		location.href = url;		

	} else {

		url = "index.php?module=docmgr";
		location.href = url;

	}

}

function setFocus(e) {
	cursorfocus = e;
}

function clearFrame() {

  uploadframe.document.open();
  uploadframe.document.write("");
  uploadframe.document.close();

}

/*******************************************************************
  FUNCTION: setFrameSize
  PURPOSE:  sets the fckeditor to fill the window when resized
  INPUTS:   none
*******************************************************************/
function setFrameSize() {

  var ref = ge("cke_contents_editor_content");
  var base = 320;

  if (ref) ref.style.height = (getWinHeight() - base) + "px";
 
}

