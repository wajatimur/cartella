/*******************************************************
	core.js
	Common functions used in our apps
	Created: 04/20/2006
*******************************************************/

var BROWSER;
var BROWSERPROG;

//set browser
if (navigator.userAgent.toLowerCase().indexOf("webkit")!=-1) 
{
	BROWSER = "webkit";
	if (navigator.userAgent.toLowerCase().indexOf("chrome")!=-1) BROWSERPROG = "chrome";
	else BROWSERPROG = "safari";
}
else if (document.all) 
{
	BROWSER = "ie";
	BROWSERPROG = "ie";
}
else 
{
	BROWSER = "mozilla";
	BROWSERPROG = "firefox";
}

//returns a string for opening a window in the center of the screen.
//bases position on width and height of the window
function centerParms(width,height,complete) {

	xPos = (screen.width - width) / 2;
	yPos = (screen.height - height) / 2;

	string = "left=" + xPos + ",top=" + yPos;

	//return the width & height portions too
	if (complete) string += ",width=" + width +",height=" + height;

	return string;
}

//return the key of the array which matches our needle
function arraySearch(str,arr) {

        arrlen = arr.length;

        for (c=0;c<arrlen;c++) {

                if (arr[c]==str) return c;

        }

        return -1;
}


//reduce an array to just those keys that has values.  The keys are
//resequenced as well
function arrayReduce(arr) {

    var newarr = new Array();
    var len = arr.length;
    var c = 0;

    for (i=0;i<len;i++) {

        if (arr[i].length > 0) {
            newarr[c] = arr[i];
            c++;
        }

    }

    return newarr;

}

//close a window and refresh the parent.
function selfClose() {

	var url = window.opener.location.href;
	window.opener.location.href = url;
	window.opener.focus();
	self.close();

}


function openModuleWindow(module,objectId,width,height) {

        if (!width) width = "600";
        if (!height) height = "500";

        parm = centerParms(width,height,1) + ",status=yes,scrollbars=yes";

        url = "index.php?module=" + module + "&objectId=" + objectId;
        nw = window.open(url,"_modulewin",parm);
        nw.focus();

}

function openModalWindow(module,objectId,width,height) {

        if (!width) width = "600";
        if (!height) height = "500";

        winparm = "toolbar=0,location=0,status=0,menubar=0,scrollbars=0,resizable=0";
        parm = centerParms(width,height,1) + "," + winparm;
        url = "index.php?module=" + module + "&objectId=" + objectId;
        nw = window.open(url,"_modalwin",parm);
        nw.focus();

}


//pause script execution for the specified milliseconds
function pause(numberMillis) {
    var now = new Date();
    var exitTime = now.getTime() + numberMillis;
    while (true) {
        now = new Date();
        if (now.getTime() > exitTime)
            return;
    }
}

//show our current site status
function updateSiteStatus(msg) {

	var ss = ge("siteStatus");

	var xPos = (getWinWidth() - 300) / 2;

  if (BROWSER=="safari") var st = window.scrollY;
  else var st = getScrollTop();

	ss.style.left = xPos + "px";
	ss.style.top = st + "px";
	ss.style.display = "block";
	ge("siteStatusMessage").innerHTML = msg;

}

//clear the site status
function clearSiteStatus() {
	ge("siteStatus").style.display = "none";
	ge("siteStatus").style.top = "0";
	ge("siteStatus").style.left = "0";
	ge("siteStatusMessage").innerHTML = "";
}


function tempSiteStatus(msg) {

  updateSiteStatus(msg);
  setTimeout("clearSiteStatus()","3000");

}

//sort a multi dimensional array by desired key
function arrayMultiSort(arr,sort_key) {

	var newarr = new Array();
	var sortkey = new Array();

	//split our array into those w/ keys and w/o keys
	var fullsort = new Array();
	var emptysort = new Array();

	for (var i=0; i<arr.length; i++) {

		if (arr[i][sort_key]) {
			fullsort.push(arr[i]);
			sortkey.push(arr[i][sort_key]);
		}
		else emptysort[i].push(arr[i]);

	}

	//sort the key array
	sortkey.sort();

	//recreate our new array with the sort elements
	for (var i=0;i<sortkey.length;i++) {

		//assemble in the correct order
		for (c=0;c<fullsort.length;c++) {

			if (fullsort[c][sort_key]==sortkey[i]) {
				newarr.push(fullsort[c]);
				break;
			}
	
		}

	}

	//now add the elements w/o keys
	for (var i=0; i<emptysort.length;i++) newarr.push(emptysort[i]);
	
	return newarr;

}

function bitset_compare(bit1,bit2,admin) {

	return perm_check(bit2);

}

function perm_check(bitpos)
{

	var auth = perm_isset(BITSET,bitpos);

	if (!auth) auth = perm_isset(BITSET,ADMIN);

	return auth;

}

function perm_isset(bitmask,bitpos)
{

	var auth = false;

	var check = bitmask.reverse();

	//we are passed the position of the bit to check
	if (check.charAt(bitpos)=="1") auth = true;

	return auth;

}

function bit_comp(bit1,bit2)
{

  var auth = false;

  if ( parseInt(bit1) & parseInt(bit2) ) auth = true;

	return auth;

}


/*******************************************
	these two functions require mootools.js
*******************************************/
function getWinWidth() {

	var width = 0;

	if (window.getWidth) width = window.getWidth();
	else {

		if (document.all) width = document.body.offsetWidth;
		else width = window.innerWidth;

	}

	return width;

}

function getWinHeight() {

	var height = 0;

	if (window.getHeight) height = window.getHeight();
	else {

		if (document.all) height = document.body.offsetHeight;
		else height = window.innerHeight;

	}
	
	return height;

}

function microtime_calc() {
        list($msec, $sec) = explode(" ",microtime());
        return $msec + $sec;
}


function bitCal(limit) {

		limit = parseInt(limit);

    var num = 1;

    for (var i=0;i<limit;i++) {
        if (limit!=0) num = num * 2;
    }

    return num;
}

function revBitCal(limit) {

		limit = parseInt(limit);
    var counter = 0;

    while (limit!=1) {

        counter++;
        limit = limit/2;

    }

    return counter;

}

/******************************************************************
  FUNCTION: closeKeepAlive
  PURPOSE:  this is a hack to prevent uploads from hanging on
            webkit browsers.  apparently a bug in OS X
******************************************************************/
function closeKeepAlive() {
  if (/AppleWebKit|MSIE/.test(navigator.userAgent)) {
		protoReqSync(SITE_URL + "controls/ping.php"); 
  }
}

/********************************************************************
	Functions for creating or manipulating DOM objects

	created: 04/20/2006

********************************************************************/

//set a floatStyle for an object
function setFloat(myvar,floatVal) {

        if (document.all) myvar.style.styleFloat = floatVal;
        else myvar.setAttribute("style","float:" + floatVal);

        return myvar;
}

//set a className value for an object
function setClass(myvar,classVal) {

        if (document.all) myvar.setAttribute("className",classVal);
        else myvar.setAttribute("class",classVal);

        return myvar;
}

//get a className value for an object
function getObjClass(myvar) {

        if (document.all) return myvar.getAttribute("className");
        else return myvar.getAttribute("class");

}

//set an onclick event for an object
function setClick(myvar,click) {

        if (document.all) myvar.onclick = new Function(" " + click + " ");
        else myvar.setAttribute("onClick",click);

        return myvar;

}

//set an onclick event for an object
function setDblClick(myvar,click) {

        if (document.all) myvar.ondblclick = new Function(" " + click + " ");
        else myvar.setAttribute("onDblClick",click);

        return myvar;

}

//set an onclick event for an object
function setMouseDown(myvar,click) {

        if (document.all) myvar.onmousedown = new Function(" " + click + " ");
        else myvar.setAttribute("onMouseDown",click);

        return myvar;

}
//set an onclick event for an object
function setMouseUp(myvar,click) {

        if (document.all) myvar.onmouseup = new Function(" " + click + " ");
        else myvar.setAttribute("onMouseUp",click);

        return myvar;

}

//set an onclick event for an object
function setMouseOver(myvar,click) {

        if (document.all) myvar.onmouseover = new Function(" " + click + " ");
        else myvar.setAttribute("onMouseOver",click);

        return myvar;

}

//set an onclick event for an object
function setMouseOut(myvar,click) {

        if (document.all) myvar.onmouseout = new Function(" " + click + " ");
        else myvar.setAttribute("onMouseOut",click);

        return myvar;

}

// IE ONLY
function setMouseEnter(myvar,click) {

        if (document.all) myvar.onmouseenter = new Function(" " + click + " ");

        return myvar;

}

// IE ONLY
function setMouseLeave(myvar,click) {

        if (document.all) myvar.onmouseleave = new Function(" " + click + " ");

        return myvar;

}

//create a new form
function createForm(formType,formName,checked) {

	var curform;

	if (checked) formCheck = " CHECKED ";
	else formCheck = "";

	//just don't ask...
	if (document.all) {
		fStr = "<input type=\"" + formType + "\" name=\"" + formName + "\" id=\"" + formName + "\" " + formCheck + ">";
		curform = document.createElement(fStr);
	}
	else {
		var curform = ce("input");
		curform.setAttribute("name",formName);
		curform.setAttribute("type",formType);
		curform.setAttribute("id",formName);
		if (checked) curform.checked = true;
	}

	return curform;

}

//create a new form
function createSelect(formName,change,dataArr,curVal) {

	var curform;

	//just don't ask...
	if (document.all) {

		if (change) onChange = "onChange=\"" + change + "\"";
		else onChange = "";

		fStr = "<select name=\"" + formName + "\" id=\"" + formName + "\" " + onChange + ">";
		curform = document.createElement(fStr);
	}
	else {
		var curform = document.createElement("select");
		curform.setAttribute("name",formName);
		curform.setAttribute("id",formName);
		if (change) curform.setAttribute("onChange",change);
	}

	//add data and a curvalue
	if (dataArr && dataArr.length > 0) {

		for (var i=0;i<dataArr.length;i++) {

			curform[i] = new Option(dataArr[i]["name"],dataArr[i]["value"]);

		}
	
	}

	if (curVal) curform.value = curVal;

	return curform;

}

//set an onclick event for an object
function setChange(myvar,click) {

        if (document.all) myvar.onchange = new Function(" " + click + " ");
        else myvar.setAttribute("onChange",click);

        return myvar;

}

//set an onclick event for an object
function setKeyUp(myvar,click) {

        if (document.all) myvar.onkeyup = new Function(" " + click + " ");
        else myvar.setAttribute("onkeyup",click);

        return myvar;

}

//set an onclick event for an object
function setFocus(myvar,click) {

        if (document.all) myvar.onfocus = new Function(" " + click + " ");
        else myvar.setAttribute("onfocus",click);

        return myvar;

}

//set an onclick event for an object
function setBlur(myvar,click) {

        if (document.all) myvar.onblur = new Function(" " + click + " ");
        else myvar.setAttribute("onblur",click);

        return myvar;

}

//shorthand for getElementbyId
function ge(element) {
	return document.getElementById(element);
}

//shorthand for creating an element
function ce(elementType,elementClass,elementId,txt) {

	var e = document.createElement(elementType);

	//add optional parameters
	if (elementId) e.setAttribute("id",elementId);
	if (elementClass) setClass(e,elementClass);

	//append extra text.  If passed an object, append with without the textnode wrapper
	if (isData(txt)) {
		if (typeof(txt)=="object") e.appendChild(txt);
		else e.appendChild(ctnode(txt));
	}

	return e;

}

function createCleaner() {

	var cleaner = document.createElement("div");
	setClass(cleaner,"cleaner");

	return cleaner;

}

//shorthand for creating a text node
function ctnode(str) {
	return document.createTextNode(str);
}
 
function changeClass(id,section) {
	document.getElementById(id).className = section;
}


//hide an object from view
function hideObject(obj) {

        document.getElementById(obj).style.position="absolute";
        document.getElementById(obj).style.visibility="hidden";
        document.getElementById(obj).style.zIndex="-10";
				document.getElementById(obj).style.display="none";

}

//show an object in the browser
function showObject(obj,zIndex) {

	if (!zIndex) zIndex = 1;

        document.getElementById(obj).style.position="static";
        document.getElementById(obj).style.visibility="visible";
				document.getElementById(obj).style.display="block";
        document.getElementById(obj).style.zIndex=zIndex;

}

//cycle between hide and show
function cycleObject(obj,zIndex) {

        var visib = document.getElementById(obj).style.visibility;

        if (visib=="visible") hideObject(obj);
        else showObject(obj,zIndex);

}

/******* some better versions of the above functions ********/
function showObj(obj) {
	ge(obj).style.display = "block";	
}

function hideObj(obj) {
	ge(obj).style.display = "none";	
}

function cycleObj(obj) {

	var visib = ge(obj).style.display;
	if (visib=="block") hideObj(obj);
	else showObj(obj);

}

//calculates the left offset of an object
function calculateOffsetLeft(r){
        return Ya(r,"offsetLeft");
}

//calcuates teh top offset of an object
function calculateOffsetTop(r){
        return Ya(r,"offsetTop");
}

//does the legwork on offset calcuation
function Ya(r,attr) {
        var kb=0;
        while(r){
                kb+=r[attr];
                r=r.offsetParent;
        }
        return kb;
}

//returns the value of a radio form
function getRadioValue(name,obj) {

        if (!obj) return "";

				var setval = "";

				//loop through area, get form with right name and see if it's checked at all
				var arr = obj.getElementsByTagName("input");

				for (var i=0;i<arr.length;i++) {

					if (arr[i].type=="radio" && arr[i].id==name && arr[i].checked==true) {
						setval = arr[i].value;
						break;
					}

				}

				return setval;

}

function createTextbox(formName,curVal,formLen) {

        //create the base form
        curform = createForm("text",formName);

        //set our attributes
        if (curVal) curform.setAttribute("value",curVal); 
        if (formLen) curform.setAttribute("size",formLen);

        return curform;
 
}

function createHidden(formName,curVal) {

        //create the base form
        curform = createForm("hidden",formName);

        //set our attributes
        if (curVal) curform.setAttribute("value",curVal); 

        return curform;
 
}

function createPassword(formName,curVal,formLen) {

        //create the base form
        curform = createForm("password",formName);

        //set our attributes
        if (curVal) curform.setAttribute("value",curVal); 
        if (formLen) curform.setAttribute("size",formLen);

        return curform;
 
}

function createRadio(formName,formVal,curVal,clicker) {

        var checked;

        if (curVal && curVal == formVal) checked = 1;
        else checked = null;

        curform = createForm("radio",formName,checked);
        curform.setAttribute("value",formVal);

				if (clicker) setClick(curform,clicker);

        return curform;
 
}

function createRadioDiv(formName,formVal,curVal,txt,cn,oc) {

        var checked;

				var mydiv = ce("div");
				if (cn) setClass(mydiv,cn);

        curform = createRadio(formName,formVal,curVal);
				if (oc) setClick(curform,oc);

				mydiv.appendChild(curform);
				if (txt) mydiv.appendChild(ctnode(txt));

        return mydiv;
 
}

function createCheckbox(formName,formVal,curVal,clicker) {

        var checked;

        if (curVal && curVal == formVal) checked = 1;
        else checked = null;

        curform = createForm("checkbox",formName,checked);
        curform.setAttribute("value",formVal);

				if (clicker) setClick(curform,clicker);

        return curform;

}
 
function createTextarea(formName,curVal,rows,cols) {

        //just don't ask...
        if (document.all) {
                fStr = "<textarea name=\"" + formName + "\" id=\"" + formName + "\"></textarea>";
                curform = document.createElement(fStr);
        }
        else {
                var curform = document.createElement("textarea");
                curform.setAttribute("name",formName);
                curform.setAttribute("id",formName);  
        }

        if (rows) curform.setAttribute("rows",rows);
        if (cols) curform.setAttribute("cols",cols);

        if (curVal) curform.value = curVal;

        return curform;

}

function createBtn(formName,val,oc) {

        var btn = createForm("button",formName);
        btn.setAttribute("value",val);

        if (oc) setClick(btn,oc);

        return btn;

}

function setZIndex(objName,zIndex) {

        var obj = ge(objName);
        obj.style.zIndex = zIndex;
 
}

//create a table
function createTable(tableName,className,width,border,cellpadding,cellspacing) {

	if (!border) border = "0";
	if (!cellpadding) cellpadding = "0";
	if (!cellspacing) cellspacing = "0";

	//create the element
	if (document.all) {
		var str = "<table ";
		if (tableName) str += "id=\"" + tableName + "\" ";
		if (className) str += "class=\"" + className + "\" ";
		str += "border=\"" + border + "\" cellpadding=\"" + cellpadding + "\" cellspacing=\"" + cellspacing + "\">";

		var tbl = ce(str);

	} else {

		var tbl = ce("table",className,tableName);

		//set our main attributes
		tbl.setAttribute("border",border);
		tbl.setAttribute("cellpadding",cellpadding);
		tbl.setAttribute("cellspacing",cellspacing);

	}

	if (width) tbl.setAttribute("width",width);

	return tbl;

}

function createTableCell(cellName,className,rowspan,colspan) {

	if (document.all) {

		var str = "<td ";
		if (cellName) str += "id=\"" + cellName + "\" ";
		if (className) str += "class=\"" + className + "\" ";
		if (rowspan) str += "rowspan=\"" + rowspan + "\" ";
		if (colspan) str += "colspan=\"" + colspan + "\" ";
		str += ">";
		var cell = ce(str);

	} else {

		var cell = ce("td");
		if (cellName) cell.setAttribute("id",cellName);
		if (className) setClass(cell,className);
		if (rowspan) cell.setAttribute("rowspan",rowspan);
		if (colspan) cell.setAttribute("colspan",colspan);

	}

	return cell;

}

//removes all child nodes within an element
function clearElement(el) {

		if (!el) return false;

		if (!el.hasChildNodes) return false;

		while (el.hasChildNodes()) {
			el.removeChild(el.firstChild);
		}

}

//replaces content of an element with passed data
function setElement(e,data) {

	//clear out the insides
	clearElement(e);

	//append extra text.  If passed an object, append with without the textnode wrapper
	if (isData(data)) {
		if (typeof(data)=="object") e.appendChild(data);
		else e.appendChild(ctnode(data));
	}

}

//css
function loadStylesheet(csspath) {
	var oLink = document.createElement("link");
	oLink.href = csspath;
	oLink.rel = "stylesheet";
	oLink.type = "text/css";
	document.getElementsByTagName("head")[0].appendChild(oLink);
}

//javascript
function loadJavascript(jspath) {
	
	var e = document.createElement("script");
	e.src = jspath;
	e.type="text/javascript";
	document.getElementsByTagName("head")[0].appendChild(e); 

}

function createDateSelect(name,title,val) {

	var divname = name + "Div";
	var cell = ce("div","popupCell",divname);
  var head = ce("div","formHeader","",title);
  var form = createTextbox(name);
  form.setAttribute("size","10");
	if (val) form.value = val;

  var btn = createBtn(name + "_btn","...");

  if (!window.Calendar) {
    alert("You must include the calendar javascript file");
  }
   
  Calendar.setup({
          inputField      :    form,
          ifFormat        :   "%m/%d/%Y",
          button          :    btn,
          singleClick     :    true,           // double-click mode
          step            :    1                // show all years in drop-down boxes (instead of every other year as default)
      });


  cell.appendChild(head);
  cell.appendChild(form);
  cell.appendChild(btn); 

  return cell;

}

function getEventSrc(e) {
  e = e || window.event;
  return e.target || e.srcElement;
}


function createMethodReference(object, methodName) {
    return function () {
        //object[methodName](params);   
				object[methodName].apply(object, arguments);
    };
};

function getScrollTop() {

	if (document.documentElement) return document.documentElement.scrollTop;
	else return document.body.scrollTop;

}

function getScrollLeft() {

	if (document.documentElement) return document.documentElement.scrollLeft;
	else return document.body.scrollLeft;

}

function createImg(src,clicker,title) {

	var img = ce("img");
	img.setAttribute("src",src);

	if (clicker) setClick(img,clicker);
	if (title) img.setAttribute("title",title);

	return img;	

}

function createLink(txt,dest) { 

  var link = ce("a","","",txt);
  link.setAttribute("href",dest);

  return link;

}

function createNbsp()
{
  return ctnode(String.fromCharCode(160));
}



/*************************************************************
	generic code for processing ajax requests
*************************************************************/



//makes sure there's data in the field
function isData(data) {

	if (!data) return false;
	var data = data.toString();		//cast it as a string
	data = data.trim();				//remove any whitespace

	if (data && data.length > 0) return true;
	else return false;

}

//this function enables all forms in the area
function enableForms(mydiv,ignore) {

	var str = "";
	var ignorestr = ",";

	//get our supported form types
	var sel = mydiv.getElementsByTagName("select");
	var input = mydiv.getElementsByTagName("input");
	var ta = mydiv.getElementsByTagName("textarea");
	var i;


	//convert ignore into a string
	if (ignore) for (i=0;i<ignore.length;i++) ignorestr += ignore[i] + ",";

	//process selects
	for (i=0; i<sel.length;i++) {

		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + sel[i].name + ",")!=-1) continue;
		sel[i].disabled=false;

	}

	//process textarea
	for (i=0;i<ta.length;i++) {
		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + ta[i].name + ",")!=-1) continue;
		ta[i].disabled = false;
	}

	//process the rest
	for (i=0;i<input.length;i++) {

		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + input[i].name + ",")!=-1) continue;

		if (input[i].type=="button" || input[i].type=="submit") input[i].disabled = false;
		else input[i].disabled = false;

	}

}


function disableForms(mydiv,ignore) {

	var str = "";
	var ignorestr = ",";

	//get our supported form types
	var sel = mydiv.getElementsByTagName("select");
	var input = mydiv.getElementsByTagName("input");
	var ta = mydiv.getElementsByTagName("textarea");
	var i;


	//convert ignore into a string
	if (ignore) for (i=0;i<ignore.length;i++) ignorestr += ignore[i] + ",";

	//process selects
	for (i=0; i<sel.length;i++) {

		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + sel[i].name + ",")!=-1) continue;
		sel[i].disabled=true;

	}

	//process textarea
	for (i=0;i<ta.length;i++) {
		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + ta[i].name + ",")!=-1) continue;
		ta[i].disabled = true;
	}

	//process the rest
	for (i=0;i<input.length;i++) {

		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + input[i].name + ",")!=-1) continue;

		if (input[i].type=="button" || input[i].type=="submit") input[i].disabled = true;
		else input[i].disabled = true;

	}

}


//runs a function when all the ajax requests are finished
function endReq(func,ms) 
{

	if (!ms) ms = "250";
	reqCheckTimer = setInterval("checkAjaxStatus()",ms);	
	reqEndFunc = func;

}

function checkAjaxStatus() 
{

	//if requests = 0, we're done
	if (ajaxReqNum==0) 
	{
		clearInterval(reqCheckTimer);
		eval(reqEndFunc);		
	}

}

//this function will load an external javascript file and parse it.  Generally, this
//is done when a page originally loads, but this allows us to load external
//scripts on the fly
function loadScript(fullUrl) 
{

        // Mozilla and alike load like this
        if (window.XMLHttpRequest) {
                req = new XMLHttpRequest();
                //FIXXXXME if there are network errors the loading will hang, since it is not done asynchronous since
                // we want to work with the script right after having loaded it
                req.open("GET",fullUrl,false); // true= asynch, false=wait until loaded
                req.send(null);
        } else if (window.ActiveXObject) {
                req = new ActiveXObject((navigator.userAgent.toLowerCase().indexOf('msie 5') != -1) ? "Microsoft.XMLHTTP" : "Msxml2.XMLHTTP");
                if (req) {
                        req.open("GET", fullUrl, false);
                        req.send();
                }
        }

        if (req!==false) {
                if (req.status==200) {
                        // eval the code in the global space (man this has cost me time to figure out how to do it grrr)
												return req.responseText;
                } else if (req.status==404) {
                        // you can do error handling here
												alert("Page not found");
                }

        }

}


/*********************************************************************
	PROTO Shortcut Functions
*********************************************************************/

//handles our xml requests for getting data
function protoReq(url,callback,reqMode) 
{

	var p = new PROTO("QUERY");

	if (reqMode=="POST") p.post(url,callback);
	else p.get(url,callback);

}

function protoReqSync(url,reqMode) 
{

	var p = new PROTO("QUERY");
	p.setAsync(false);

	if (reqMode=="POST") var ret = p.post(url);
	else var ret = p.get(url);

	return ret;

}

//handles our xml requests for getting data
function postReq(url,callback) 
{

	protoReq(url,callback,"POST");

}

//handles our xml requests for getting data
function getReq(url,callback) 
{

	protoReq(url,callback,"GET");

}

function protoRedirect(url) 
{

	var p = new PROTO("QUERY");
	p.redirect(url,reqMode);

}

function dom2Query(cont,ignore) 
{

	var p = new PROTO("QUERY");
	return p.encodeDOM(cont,ignore);

}

function dom2Array(cont,ignore) 
{

  var p = new PROTO();
  return p.traverse(cont,ignore);

}

/*************************************************************************
	end PROTO shortcut functions
*************************************************************************/

/********************************************************************
	legacy functions
********************************************************************/

function loadReq(url,callback,reqMode)
{
	protoReq(url,callback,reqMode);
}

function loadReqSync(url,reqMode)
{
	return protoReqSync(url,reqMode);
}

//data is already converted, just return as is
function parseXML(resp)
{
	return resp;
}

function loadXMLReq()
{

	alert("This function has been depreciated.  Please use protoReq(url,callbackFuncName,requestMode)");

}

