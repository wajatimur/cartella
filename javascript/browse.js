createObjectHandler = new Array();

function dateHideAll() {

        var fieldArray = new Array("fromText","onText","date1Form","beforeText","afterText","toText");
        var num = 6;

        for (row=0;row<num;row++) {

                temp = fieldArray[row];
		hideObject(temp);

               // document.getElementById(temp).style.position="absolute";
                //document.getElementById(temp).style.visibility="hidden";

        }


}


function swapDate() {

        dateHideAll();

	action = document.getElementById("date_option").value;

        if (action!="any") showObject("date1Form");
	if (action=="single") showObject("onText");
	else if (action=="period") {
		showObject("fromText");
		showObject("toText");
	} 
	else if (action=="before") showObject("beforeText");
	else if (action=="after") showObject("afterText");

}


function checkBoxes() {

	var formName = document.pageForm;

	var boxes = formName.getElementsByTagName("input");

	for (i=0;i<boxes.length;i++) {

		if (boxes[i].checked == true) return true;

	}

	return false;

}

function zipCollection(id) {

	parm = centerParms(500,500) + ",width=500,height=500,status=yes";

	url = "index.php?module=zipcollection&" + "&objectId=" + id;

	window.open(url,"_blank",parm);

}

function selectObjects(action) {

	var formName = document.pageForm;

	var boxes = formName.getElementsByTagName("input");

	if (action=="file") {

		for (i=0;i<boxes.length;i++) {
	
			var testName = boxes[i].name;
			if (testName.indexOf("fileAction")!="-1") boxes[i].checked = true;
			else boxes[i].checked = false;

		}

	} else if (action=="category") {

		for (i=0;i<boxes.length;i++) {
	
			var testName = boxes[i].name;
			if (testName.indexOf("catAction")!="-1") boxes[i].checked = true;
			else boxes[i].checked = false;

		}


	} else {

		if (boxes[0].checked==true) {
			for (i=0;i<boxes.length;i++) boxes[i].checked = false;
		}
		else {
			for (i=0;i<boxes.length;i++) boxes[i].checked = true;
		}

	}

}

function catProperties(id) {

	var url = "index.php?module=collection&objectId=" + id;
	location.href = url;

}

function urlProperties(id) {

	var url = "index.php?module=url&objectId=" + id;
	location.href = url;

}

function searchProperties(id) {

	var url = "index.php?module=savesearch&objectId=" + id;
	location.href = url;

}

function fileAction() {

	action = document.getElementById("fileTool").value;

	if (action=="delete") return deleteObjects();
	else if (action=="move") return moveCategory();
	else if (action=="upload") return uploadObjects();
	else if (action=="createCategory") return createCategory();

}

function switchView(type) {

     	document.pageForm.pageView.value = type;
    	document.pageForm.submit();

}

function createNewObject(id) {

	var obj = document.pageForm.createObject.value;

	var func = eval(createObjectHandler[obj]);
	func(id);

	//set our dropdown back to the default setting
	document.pageForm.createObject[0].selected = true;

}

function checkinObject(id) {

        url = "index.php?module=file&includeModule=filecheckin&objectId=" + id;
	location.href = url;

}

function swapSearchType() {

	var type = document.pageForm.searchType.value;

	if (type=="keyword") {

		hideObject("normalOption");
		showObject("keywordOption");

	}
	else {

		hideObject("keywordOption");
		showObject("normalOption");

	}

}

function selectCollection() {

	curVal = document.getElementById("colFilterId").value;

	url = "index.php?module=selectcollection&curVal=" + curVal;
	parms = centerParms(400,400) + ",width=400,height=400,scrollbars=yes";
	window.open(url,"_selectcol",parms);

}

function selectAccount() {

	curVal = document.getElementById("accountFilterId").value;

	url = "index.php?module=selectaccount&curVal=" + curVal;
	parms = centerParms(400,400) + ",width=400,height=400,scrollbars=yes";
	window.open(url,"_selectaccount",parms);

}


function moveObject() {

	if (!checkBoxes()) {

		alert(I18N["object_select_error"]);
		return false;

	}

	var objId;

	//pass the parentId to the move module if there is one
	if (document.pageForm.view_parent) objId = document.pageForm.view_parent.value;

	parm = centerParms(400,500) + ",width=400,height=500,scrollbars=yes,status=yes";
	url = "index.php?module=moveobject";

	if (objId) url += "&curValue=" + objId;

	window.open(url,'_blank',parm);


}

function deleteObjects() {

	if (!checkBoxes()) {

		alert(I18N["object_select_error"]);

	} else {

		openModalWindow("deleteobject",'',350,100);

	}

}

function jumpPage(page) {

	document.getElementById("curPage").value = page;
	document.pageForm.submit();

}

function deleteObject(id) {

	var message = I18N["delete_confirm"] + "?";

	if (!confirm(message)) return false;

	document.pageForm.pageAction.value = "deleteSingleObject";
	document.getElementById("objectId").value = id;

	document.pageForm.submit();	


}

function bookmarkCollection(id,name) {

  var title = prompt(I18N["enter_bookmark_name"],name);

  if (!title) return false;

  document.pageForm.bookmarkName.value = title;
  document.pageForm.pageAction.value = "bookmarkCollection";
  document.pageForm.objectId.value = id;
  document.pageForm.submit();

}
                        
function saveQuery() {

        parm = centerParms(400,500) + ",width=400,height=500,scrollbars=yes,status=yes";

        window.open('index.php?module=createsavesearch','_blank',parm);


}


function changeSort(field) {

	var sf = document.pageForm.sortField;
	var sd = document.pageForm.sortDir;
	var vp = document.pageForm.view_parent;
	var mod = document.pageForm.module;

	//set the new sort direction
	if (sd.value=="ascending") sd.value = "descending";
	else sd.value = "ascending";

	//set the sort field
	sf.value = field;

	if (mod.value=="browse") browseCollection(vp.value);		
	else document.pageForm.submit();

}

//create an absolute positioned div with a preview of our object
function showObjectPreview(modname,id,name,objDir,sessId) {

	//get the screen height for later
	if (document.all) var ih = document.body.offsetHeight;
	else var ih = window.innerHeight;
	
	var imgid = modname + id;
	var previewid = "preview" + id;
	var e = document.getElementById(imgid);	

	var ol = calculateOffsetLeft(e);
	var ot = calculateOffsetTop(e);
	var newleft = ol-400;
	if (newleft < 0) newleft = 0;

	if ( (ot+350) > ih) ot = ih - 350;

	//create our element to display the picture in
	var prewin = ce("div");
	prewin.style.position="absolute";
	prewin.style.left = newleft;
	prewin.style.top = ot;
	prewin.setAttribute("id",previewid);
	setClass(prewin,"previewWindow");

	var closeImg = ce("img");
	setClass(closeImg,"previewClose");
	closeImg.setAttribute("src",theme_path + "/images/icons/close.png");
	setClick(closeImg,"closePreview('" + previewid + "')");

	//create our title
	var title = ce("div");
	setClass(title,"previewTitle");
	title.appendChild(closeImg);
	title.appendChild(ctnode("Previewing " + name));
	
	//create the image that will hold the preview.  also append the timestamp to prevent caching
	var d = new Date();
	var timestr = d.getTime();
	var url = "app/showpreview.php?objectId=" + id + "&objDir=" + objDir + "&sessId=" + sessId + "&time=" + timestr;
	var img = ce("img");
	img.setAttribute("src",url);

	//put it all together
	prewin.appendChild(title);
	prewin.appendChild(img);
	
	ge("objectPreview").appendChild(prewin);	

}

function closePreview(id) {

	ge("objectPreview").removeChild(document.getElementById(id));

}

function showObjMenu(divid) {

	d = ge(divid);

	d.style.display = "block";
	d.style.zIndex = 300;
	d.style.marginTop = "15px";
	d.style.marginLeft = "-250px";
}

function hideObjMenu(divid) {

	//do nothing for now
	ge(divid).style.display = "none";

}

function colorRow(event) {

	//grr, for some reason changing classes doesn't work in i.e.
	var obj = event.srcElement;
	obj.style.backgroundColor="white";

}

function uncolorRow(event) {

	var obj = event.srcElement;
	obj.style.backgroundColor="lightblue";

}


//cycle between hide and show
function showNavMenu(obj) {

	var menuref = ge(obj + "Menu");
	var conref = ge(obj + "Container");

	menuref.style.zIndex = 10;
	menuref.style.display = "block";
	menuref.style.marginTop = "-4";
	menuref.style.marginLeft = "-2";

	var arrow = ge(obj + "Arrow");
	setClass(arrow,"menuArrowOver");
}

function hideNavMenu(obj) {

	var ref = ge(obj + "Menu");
	ref.style.display="none";
	ref.style.zIndex = -1;

	var arrow = ge(obj + "Arrow");
	setClass(arrow,"menuArrow");

}
