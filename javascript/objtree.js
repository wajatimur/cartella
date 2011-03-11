/**********************************************************************************
	this code is way complicated and bigger than it should be, but it works.
	It desperately needs to be consolidated and split out to work better
**********************************************************************************/

function loadTreeHandlers() {

	ajaxRH["objtree"] = "writeObjTree";
	ajaxRH["singleobjtree"] = "expandObjTree";

}

//this function creates the tree from scratch
function writeObjTree(data) {

	//get out if no div to write to
	if (!data.divName) return false;

        var mydiv = document.getElementById(data.divName);
        mydiv.innerHTML = "";

        //nothing to display if the list is empty
        if (!data.object) {
                mydiv.innerHTML = "<div class=\"errorMessage\">No results to display</div>";
                return false;
        }
	else mydiv.innerHTML = "<div class=\"successMessage\">Loading Tree...</div>";

	//if no form is passed, create a link tree
	tree = createTree(data);

	if (tree) {
		mydiv.innerHTML = "";
		mydiv.appendChild(tree);
	} else {
		mydiv.innerHTML = "<div class=\"errorMessage\">Error loading tree</div>";
	} 

}

function expandObject(id,keepOpen,formName,mode) {

	if (formName)
		tgtdiv = formName + "_" + id;
	else
		tgtdiv = "_subcol" + id;

	var pb = "box" + tgtdiv;
	var pf = "folder" + tgtdiv;
	if (!mode) mode = "link";

	var td = document.getElementById(tgtdiv);
	boximg = document.getElementById(pb);
	foldimg = document.getElementById(pf);

	//reset all folders to closed on this level
	if (td.parentNode.parentNode) {
		var fi = td.parentNode.parentNode.getElementsByTagName("img");
		var len = fi.length;
		for (i=0;i<len;i++) {	
			if (fi[i].src.indexOf("open_folder") != -1) 
					fi[i].src = "themes/default/images/closed_folder.png";
		}
	}

	//ie work around for the extra space it puts at the bottom of the div
	if (document.all) td.style.borderBottom = "1px solid white";

	//this is if we are processing a child that's already open, and it's
	//not part of the initial display of the tree
	if (td.firstChild && !keepOpen) { 

		if (boximg) boximg.src = "themes/default/images/plusbox.gif";				
		if (mode=="link" && foldimg) foldimg.src = "themes/default/images/closed_folder.png";

		//this seems to work better than removing the childNodes
		td.innerHTML = "";
		return false;
	}
	else {

		if (boximg) boximg.src = "themes/default/images/dashbox.gif";				
		if (mode=="link" && foldimg) foldimg.src = "themes/default/images/open_folder.png";
		url = "index.php?module=objtree&curValue=" + id + "&expandSingle=" + tgtdiv;

		//tack on the formname and mode for parsing forms
		if (formName) {
			url += "&formName=" + formName;
			url += "&mode=" + mode;
		}
		loadXMLReq(url);
	
	}

}

//this function expands a single level of a tree
function expandObjTree(data) {

	if (!resp) return false;

	var curchild = data;
	var i = 0;
	var subdiv = document.getElementById(curchild.expandSingle);
	
	subdiv.innerHTML = "";
	subdiv.appendChild(createTree(curchild));

}

//this function returns all collections at a level.  If there are some
//below the level, it calls itself again to display them
function createTree(data) {

	var i = 0;
	var container = document.createElement("div");
	var formName = data.formName;
	var checkName = "curTreeVal";
	var mode = data.mode;
	var curVal = data.curValue;

	var checkForm = document.getElementById(checkName);

	//our values are stored in a comma delimited format in a hidden field.
	if (checkForm) {
		var checkVal = checkForm.value;
		var checkArr = checkVal.split(",");
	} 

	//get out if there's nothing to report
	if (!data.object) return container;
	var len = data.object.length;

	for (i=0;i<len;i++) {

		var curobj = data.object[i];

		//create our div, images, and text
		var curdiv = document.createElement("div");
		var idName = formName + "_" + curobj.id;

		//the children div
		if (curobj.children) {
			//make sure the form name gets passed again
			curobj.children.formName = formName;
			curobj.children.curValue = curVal;
			curobj.children.mode = mode;
			var childTree = createTree(curobj.children);
		}
		else var childTree = document.createElement("div");

		childTree.setAttribute("id",idName);
		childTree.style.marginLeft=13;

		//the form
		formType = "checkbox";

		var formChecked = null;

		//is the form checked
		if (checkArr) {
			var test = arraySearch(curobj.id,checkArr);
			if (test != "-1") formChecked = "yes";
		}

		//just don't ask...
		if (document.all) {
			if (formChecked) var fStr = "<input name=\"" + formName + "\" CHECKED>";
			else var fStr = "<input name=\"" + formName + "\">";
			var curform = document.createElement(fStr);
		}
		else {
			var curform = document.createElement("input");
			curform.setAttribute("name",formName);
			if (formChecked) curform.setAttribute("checked","true");
		}

		curform.setAttribute("type",formType);
		curform.setAttribute("id",formName);
		curform.setAttribute("value",curobj.id);
		curform.setAttribute("title",curobj.name);

		//the plus box
		if (curobj.child_count > 0) {

			var pbox = document.createElement("img");
			pbox.setAttribute("id","box" + idName);			
			pbox.style.marginRight = 3;
			setClick(pbox,"expandObject('" + curobj.id + "','','" + formName + "','" + mode + "')");

			if (curobj.children) 		
				pbox.setAttribute("src","themes/default/images/dashbox.gif");
			else 
				pbox.setAttribute("src","themes/default/images/plusbox.gif");

		}			
		else {
			var pbox = null;
			if (document.all) curform.style.marginLeft = 12;
			else curform.style.marginLeft = 16;
		}

		//it's icon
		var img = ce("img");
		img.style.paddingLeft = 2;
		img.style.paddingRight = 3;
		img.setAttribute("src",curobj.icon);

		//the collection name and link
		var txt = document.createTextNode(curobj.name);

		//put it all together
		if (pbox) curdiv.appendChild(pbox);
		curdiv.appendChild(curform);
		curdiv.appendChild(img);
		curdiv.appendChild(txt);
		curdiv.appendChild(childTree);
		container.appendChild(curdiv);

	}	

	return container;

}

