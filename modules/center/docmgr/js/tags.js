var tagdiv;
var tagdata;


/***************************************
	tag management
***************************************/

function loadTags() {

	clearElement(tagdiv);
	tagdiv.appendChild(ce("div","statusMessage","","Loading Tags"));

	//setup the xml
	var str = "<data>";
	str += cdata("command","gettags");
	str += "</data>";   

	var url = DOCMGR_API + "?apidata=" + str;
	protoReq(url,"writeTags");

}

function writeTags(data) {

  tagdata = data;
  clearElement(tagdiv);

  if (tagdata.error) alert(tagdata.error);
  else if (!tagdata.tag) tagdiv.appendChild(ce("div","errorMessage","","No tags found"));
  else {

    //var tv = String(getStoreValue("tagId[]"));
    var tvarr = new Array();
    //if (tv.length > 0) tvarr = tv.split(",");

    tagdiv.appendChild(ce("div","tagHeader","","Tags"));

    for (var i=0;i<tagdata.tag.length;i++) {

      var row = ce("div","tagRow");
      var cb = createCheckbox("tagId[]",tagdata.tag[i].id);
      setClick(cb,"objectSearch()");
      row.appendChild(cb);
      row.appendChild(ctnode(tagdata.tag[i].name));
      tagdiv.appendChild(row);

      //see if it's checked
      if (arraySearch(tagdata.tag[i].id,tvarr)!=-1) cb.checked = true;

    }

    tagdiv.appendChild(createCleaner());

  }

}


function applyTags() {

	var carr = getSelectedObjects();
	if (carr.length==0) {
		alert("You must select contacts to edit their tags");
		return false;
	}

	var tarr = getSelectedTags();

	//open the popupwindow
	popupref = openSitePopup(300,300,"closeTagPopup()");
	var cell = ce("div","sitePopupCell");
	popupref.appendChild(cell);

	cell.appendChild(ce("div","popupFormHeader","","Select the tags to apply"));
	var tl = ce("div","popupTagDiv");
	cell.appendChild(tl);

	if (tagdata.tag.length==0) tl.appendChild(ce("div","errorMessage","","No tags found"));
	else {

		for (var i=0;i<tagdata.tag.length;i++) {

			var row = ce("div","popupTagRow");
			var cb = createCheckbox("tagId[]",tagdata.tag[i].id);
			setClick(cb,"sendApplyTag(event)");
			row.appendChild(cb);
			row.appendChild(ctnode(tagdata.tag[i].name));
			tl.appendChild(row);

			//make sure whatever is checked in the browse list is checked here
			if (arraySearch(tagdata.tag[i].id,tarr)!=-1) cb.checked = true;

		}
	

	}

}

function sendApplyTag(e) {

	var ref = getEventSrc(e);
	var carr = getSelectedObjects();

	if (ref.checked==true) var mode = "addtag";
	else var mode = "removetag";

	//setup the xml
	var str = "<data>";
	str += cdata("command",mode);
	str += cdata("tag_id",ref.value);
	str += div2xml(ge("searchResultContent"));
	str += "</data>";   

	var url = DOCMGR_API + "?apidata=" + str;
	//protoReq(url,"writeSendApplyTag");

	alert(url);

}

function writeSendApplyTag(data) {

	 
	if (data.error) alert(data.error);

}


function closeTagPopup() {

	closeSitePopup();
	newSearch();

}


function manageTags() {

	//open the popupwindow
	popupref = openSitePopup(400,300,"closeTagPopup()");
	popupref.appendChild(ce("div","sitePopupHeader","","Tag Manager"));

	var cell = ce("div","sitePopupCell","manageTagCell");


	popupref.appendChild(cell);


	var btn = siteToolbarBtn("New Tag","newTag()","new.png");
	cell.appendChild(btn);
	cell.appendChild(ce("div","popupFormHeader","","Tags"));

	var tl = ce("div","popupTagDiv");
	cell.appendChild(tl);

	if (tagdata.tag.length==0) tl.appendChild(ce("div","errorMessage","","No tags found"));
	else {

		for (var i=0;i<tagdata.tag.length;i++) {

			var row = ce("div","popupTagRow");

			row.appendChild(createImg(theme_path + "/images/icons/delete.png","deleteTag('" + tagdata.tag[i].id + "')"));
			row.appendChild(createImg(theme_path + "/images/icons/edit.png","editTag('"+ tagdata.tag[i].id + "',\"" + tagdata.tag[i].name + "\")"));

			row.appendChild(ctnode(tagdata.tag[i].name));
			tl.appendChild(row);

		}
	

	}

}

function deleteTag(id) {

	if (confirm("Are you sure you want to remove this tag?")) {

		//setup the xml
		var str = "<data>";
		str += cdata("command","deletetag");
		str += cdata("tag_id",id);
		str += "</data>";   

		updateSiteStatus("Please wait");
		var url = DOCMGR_API + "?apidata=" + str;
		protoReq(url,"writeEditTag");

	}

}

function editTag(id,name) {

	var str = prompt("Enter the new name for the tag",name);
	if (str && str.length > 0) {

		//setup the xml
		var xml = "<data>";
		p.add("command","savetag");
		p.add("name",str);
		p.add("tag_id",id);
		xml += "</data>";   

		updateSiteStatus("Please wait");
		var url = DOCMGR_API + "?apidata=" + xml;
		protoReq(url,"writeEditTag");

	}

}

function newTag() {

	var str = prompt("Enter the name for the new tag");
	if (str && str.length > 0) {

		//setup the xml
		var xml = "<data>";
		p.add("command","savetag");
		p.add("name",str);
		xml += "</data>";   

		updateSiteStatus("Please wait");
		var url = DOCMGR_API + "?apidata=" + xml;
		protoReq(url,"writeEditTag");

	}

}

function writeEditTag(data) {

	 
	if (data.error) alert(data.error);
	else {
		loadTags();
		endReq("closeSitePopup();manageTags()");
		clearSiteStatus();
	}

}


function getSelectedObjects() {

	var ref = ge("browseContent");
	var arr = ref.getElementsByTagName("input");
	var carr = new Array();

	for (var i=0;i<arr.length;i++) {
	
		if (arr[i].id=="objectId[]" &&  arr[i].checked == true) carr.push(arr[i].value);
	
	}

	return carr;

}

function getSelectedTags() {

	var arr = tagdiv.getElementsByTagName("input");
	var tarr = new Array();

	for (var i=0;i<arr.length;i++) {
	
		if (arr[i].id=="tagId[]" &&  arr[i].checked == true) tarr.push(arr[i].value);
	
	}

	return tarr;

}

