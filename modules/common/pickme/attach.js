
//add a file to our queue
function addFile() {

  //now create a text indicator to show the file
  newfile = document.createElement("li");    

  //get our form containing the file we want to upload
  filediv = document.getElementById("uploadFileForm"); 
  var ufarr = filediv.getElementsByTagName("input");
  var uf = ufarr[0];

  if (!uf || !uf.value) return false;

  //add a new hidden form element and the
  var txtdiv = document.getElementById("uploadFileText");
  var curform = document.pageForm;

  //this is kind of awkward, but I couldn't get a file input to clone
  //properly in i.e.  Basically we move the file object used to select files
  //down and change it's name, then create a new one in it's place

  //copy the current one and change its name
  uf.setAttribute("name","uploadfile[]");
  uf.style.visibility="hidden";
  uf.style.position="absolute";
  uf.style.left="0";
  uf.style.top="0";
  newfile.appendChild(uf);

  //create a new one
  var uploadfile = document.createElement("input");
  uploadfile.type = "file";
  setChange(uploadfile,"addFile()");
  filediv.appendChild(uploadfile);

  //the link for clearing the file
  var cleardiv = document.createElement("div");
  setFloat(cleardiv,"right");
  cleardiv.style.textAlign="right";

  var clearStr = escapeBackslash(uf.value);

  var clearlink = document.createElement("a");
  clearlink.setAttribute("href","javascript:clearUpload('" + clearStr + "')");
  clearlink.appendChild(document.createTextNode("[Remove]"));
  cleardiv.appendChild(clearlink);
  newfile.appendChild(cleardiv);

  var lbldiv = document.createElement("div");
  if (uf.value.indexOf("/") != -1) var stArr = uf.value.split("/");
  else var stArr = uf.value.split("\\");
  var len = stArr.length - 1;
  lbldiv.appendChild(document.createTextNode(stArr[len]));
  newfile.appendChild(lbldiv);


  //add to the parent
  txtdiv.appendChild(newfile);

  //empty out the original
  //uf.value = "";

}

function uploadFiles() {

  //stop any background processes from running
  siteFileUpload = 1;

  //again, ie...
  if (document.all) {
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
  var url = DOCMGR_API + "?apidata=" + p.encodeData();

  //append a timestamp for ie to stop the god damn caching
  if (document.all) {
    var d = new Date();
    url += "&timestamp=" + d.getTime();
  }

  document.pageForm.action = url;
  document.pageForm.target = "uploadframe";
  document.pageForm.submit();

  var mydiv = document.getElementById("resultList");
  mydiv.innerHTML = "<div class=\"successMessage\">Please Wait...</div>\n";
  uploadStat = setInterval("checkUpload()","100");

}

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

    clearInterval(uploadStat);
    document.getElementById("uploadFileText").innerHTML = "";
		clearElement(ge("resultList"));
    loadPage();

    //allow background processes
    siteFileUpload = 0;

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

function clearUpload(fp) {

  //get all bullets in our area
  var txtdiv = document.getElementById("uploadFileText");
  var liarr = txtdiv.getElementsByTagName("li");

  var num = liarr.length;
  var i;

  //cycle thru the bullets
  for (i=0;i<num;i++) {

    //find the hidden input file field.  If it's value matches our file pointer
    //then remove it from the list
    var curli = liarr[i];
    var filearr = curli.getElementsByTagName("input");
    var curfile = filearr[0];

    //we have a match, remove this node
    if (curfile.value==fp) txtdiv.removeChild(curli);

  }
}


function rotateImage(id,dir) {

  var mydiv = document.getElementById("resultList");

  mydiv.innerHTML = "<div class=\"successMessage\">Please Wait...</div>\n";

  //setup the xml
	var p = new PROTO();
  p.add("command","docmgr_file_rotate");
  p.add("direction",dir);
  p.add("object_id",id);
	p.post(DOCMGR_API,"writeDocmgrEdit");

}

function removeImage(id) {

  var mydiv = document.getElementById("resultList");

  if (confirm("Are you sure you want to remove this file?")) {

    mydiv.innerHTML = "<div class=\"successMessage\">Please Wait...</div>\n";

    //setup the xml
		var p = new PROTO();
    p.add("command","docmgr_object_delete");
    p.add("object_id",id);
		p.post(DOCMGR_API,"writeDocmgrEdit");

  }

}

function writeDocmgrEdit(data) {

  loadPage();

}
