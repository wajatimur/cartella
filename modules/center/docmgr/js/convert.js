/********************************************************************
	FILENAME: convert.js
	PURPOSE:  contains docmgr browsing functions

*********************************************************************/

var convert_src;			//dom reference to the row of the object we are converting
var convert_mass;			//letting us know if we are converting more than one obj at a time

/**********************************************************
	CONVERSION TOOL
**********************************************************/

/*********************************************************
	regular conversion setup
*********************************************************/

/***********************************************************
	FUNCTION:	convertObjectWin
	PURPOSE:	called by interface.  sets up convert and
						loads extension xml file
***********************************************************/
function convertObjectWin(e)
{

	convert_mass = "";

	var ref = getEventSrc(e).parentNode.parentNode.parentNode;
	convert_src = ref;

	updateSiteStatus("Loading conversion utility");

	protoReq("config/extensions.xml","writeConvertObjectWin");	

}

/***********************************************************
	FUNCTION:	writeConvertObjectWin
	PURPOSE:	sets up our convert window using the allowed
						destinations for our current object
***********************************************************/
function writeConvertObjectWin(data)
{

	var ext = fileExtension(convert_src.getAttribute("object_name"));
	var ot = getOOType(data,ext);

	//if we found a custom type for this extension, continue
	if (ot.length > 0 || convert_src.getAttribute("object_type")=="document")
	{

		var toext = getOODest(data,ot);
		loadConvertWin(toext);

	}
	else
	{
		alert("Unable to convert file of this type");
	}

}

/****************************************************
	mass convert setup
****************************************************/

/***********************************************************
	FUNCTION:	massConvertWin
	PURPOSE:	sets up our convert window for mass conversion
***********************************************************/
function massConvertWin()
{

	convert_mass = 1;

	//get references to all checked rows
	var arr = getChecked('',1);

	//make sure something is checked
	if (arr.length==0)
	{
		alert("You must check at least one object to convert");
		return false;
	}

	var ret = checkConvertTypes(arr);

	if (!ret)
	{
		alert("All objects must be of the same type in order to convert");
		return false;
	}
	else
	{

		convert_src = arr[0];

		protoReq("config/extensions.xml","writeMassConvertObjectWin");	

	}

}

/***********************************************************
	FUNCTION:	writeMassConvertObjectWin
	PURPOSE:	sets up our convert window for mass conversion
***********************************************************/

function writeMassConvertObjectWin(data)
{

	//make sure all checked fiels are of the same openoffice type
	if (!checkConvertExtensions(data))
	{
		alert("You cannot convert files of mixed openoffice types");
		return false;
	}

	//use the name of our first file to figure out what types
	//we can convert to
	var ext = fileExtension(convert_src.getAttribute("object_name"));
	var ot = getOOType(data,ext);

	//if we found a custom type for this extension, continue
	if (ot.length > 0 || convert_src.getAttribute("object_type")=="document")
	{

		var toext = getOODest(data,ot);
		loadConvertWin(toext);

	}
	else
	{
		alert("Unable to convert file of this type");
	}

}

/************************************************************
	load convert window and process conversion
************************************************************/

/***********************************************************
	FUNCTION:	loadConvertWin
	PURPOSE:	creates the actual popup for conversion
***********************************************************/
function loadConvertWin(toext)
{

	var ref = openSitePopup("350","150");

	//setup our output format cell
	var cell = ce("div","sitePopupCell");
	cell.appendChild(ce("div","formHeader","","Convert To"));
	
	var sel = createSelect("toOption");
	setChange(sel,"handleToOption()");
	
	for (var i=0;i<toext.length;i++)
	{
		sel[i] = new Option(toext[i][0] + " - " + toext[i][1],toext[i][0]);
	}

	cell.appendChild(sel);
	ref.appendChild(cell);

	//setup our output option cell
	var cell = ce("div","sitePopupCell");
	cell.appendChild(ce("div","formHeader","","Save To"));
	
	var sel = createSelect("returnOption");
	sel[0] = new Option("My Computer","download");
	sel[1] = new Option("DocMGR - This Collection","docmgr");
	sel[2] = new Option("DocMGR - Other Collection","other");

	var btn = createBtn("convertBtn","Convert File","runConvertObject()");
	btn.style.float = "right";

	cell.appendChild(btn);
	cell.appendChild(sel);
	ref.appendChild(cell);

	clearSiteStatus();

	handleToOption();

}

/***********************************************************
	FUNCTION:	handleToOption
	PURPOSE:	sets up the convert destination based on
						our source object type or mass conversion
***********************************************************/
function handleToOption()
{

	var ro = ge("returnOption");

	if (ge("toOption").value == "docmgr" || convert_mass)
	{

		while (ro.options.length > 0) ro.options.remove(0);

		ro[0] = new Option("DocMGR - This Collection","docmgr");
		ro[1] = new Option("DocMGR - Other Collection","other");

	}
	else
	{
		ro[0] = new Option("My Computer","download");
		ro[1] = new Option("DocMGR - This Collection","docmgr");
		ro[2] = new Option("DocMGR - Other Collection","other");
	}

}

/***********************************************************
	FUNCTION:	runConvertObject
	PURPOSE:	passed convert options to the API
***********************************************************/
function runConvertObject()
{

	var returnOpt = ge("returnOption").value;

	//if the files are supposed to go somewhere else, launch special processing
	if (returnOpt=="other")
	{

		//launch the destination window
		mbmode = "convert";

   	//launch our selector to pick where to save the file
		openMiniB("open","","collection");

	}
	else
	{

		var p = new PROTO();

		//mass conversion options	
		if (convert_mass)
		{
	
			p.add("command","docmgr_object_massconvert");
			p.add("object_id",getChecked());
	
		}
		//single conversion options
		else
		{
	
			p.add("command","docmgr_object_convert");
			p.add("object_id",convert_src.getAttribute("object_id"));
	
		}
	
		//converting to docmgr documents
		if (ge("toOption").value == "docmgr")
		{
			p.add("to","html");
			p.add("convert_type","document");
			p.add("return","docmgr");
		}
		//handle normal conversion
		else
		{
			p.add("return",returnOpt);
			p.add("to",ge("toOption").value);	
		}

		//download result to the browser		
		if (returnOpt=="download")
		{
			p.redirect(DOCMGR_API);
		}
		//store in current docmgr collection
		else
		{
			updateSiteStatus("Converting file");
			p.post(DOCMGR_API,"writeConvertResults");
		}

	}

}

/***********************************************************
	FUNCTION:	writeConvertResults
	PURPOSE:	handle api response
***********************************************************/
function writeConvertResults(data)
{

	clearSiteStatus();
	if (data.error) alert(data.error);
	else 
	{
		closeSitePopup();
		browsePath();
	}

}

/***********************************************************
	FUNCTION:	convertObjProcess
	PURPOSE:	handler of callback from the alternate destination
						popup
***********************************************************/
function convertObjProcess(parent_path)
{

	var returnOpt = ge("returnOption").value;

	var p = new PROTO();
	p.add("parent_path",parent_path);

	if (convert_mass)
	{
	
		p.add("command","docmgr_object_massconvert");
		p.add("object_id",getChecked());
	
	}
	else
	{
	
		p.add("command","docmgr_object_convert");
		p.add("object_id",convert_src.getAttribute("object_id"));
	
	}

	p.add("return","docmgr");
	
	if (ge("toOption").value == "docmgr")
	{
		p.add("to","html");
		p.add("convert_type","document");
	}
	else
	{
	
		p.add("to",ge("toOption").value);	
	
	}
		
	updateSiteStatus("Converting file");
	p.post(DOCMGR_API,"writeConvertResults");

}


/***********************************************************
	FUNCTION:	getOOType
	PURPOSE:	get the openoffice type of the passed extension
***********************************************************/
function getOOType(data,ext)
{

	//make docmgr documents html
	if (!ext && convert_src.getAttribute("object_type")=="document") ext = "html";

	var ot = "";

	for (var i=0;i<data.object.length;i++)
	{

		if (ext==data.object[i].extension)
		{
			ot = data.object[i].openoffice;
			break;
		}

	}

	return ot;

}

/***********************************************************
	FUNCTION:	getOODest
	PURPOSE:	get all extensions this file can be
						converted to
***********************************************************/
function getOODest(data,ot)
{

	var toext = new Array();

		//always put pdf
		var arr = new Array();
		arr.push("pdf");
		arr.push("Adobe PDF Document");
		toext.push(arr);

		//if not a docmgr document, add an option to convert to one
		if (!convert_src.getAttribute("object_type")!="document")
		{
			var arr = new Array();
			arr.push("docmgr");
			arr.push("DocMGR Document");
			toext.push(arr);
		}
	
		//now find all matches of this custom type
		for (var i=0;i<data.object.length;i++)
		{

			//skip disabled ones
			if (!isData(data.object[i].openoffice_convert) || data.object[i].openoffice_convert!=1)
			{
				continue;
			}

			if (data.object[i].openoffice==ot)
			{
				var arr = new Array();
				arr.push(data.object[i].extension);
				arr.push(data.object[i].proper_name);

				toext.push(arr);

			}

		}

	return toext;

}

/***********************************************************
	FUNCTION:	checkConvertTypes
	PURPOSE:	checks to make sure all objs to convert are
						of the same docmgr object_type
***********************************************************/
function checkConvertTypes(arr)
{

	var ret = true;
	var ot = "";

	//make sure they all have the same object type
	for (var i=0;i<arr.length;i++)
	{

		//initial setup
		if (!ot) 
		{
			ot = arr[i].getAttribute("object_type");
			continue;
		}

		if (ot!=arr[i].getAttribute("object_type"))
		{
			ret = false;
			break;
		}

	}

	return ret;

}

/***********************************************************
	FUNCTION:	checkConvertExtension
	PURPOSE:	checks to make sure all objs to convert are
						of the same openoffice type (like all writer
						or calc)
***********************************************************/
function checkConvertExtensions(data)
{

	var ret = true;

	//loop through and make sure all files are of either openoffice writer or cal
	//we can't mix it though
	var arr = getChecked('',1);

	var oo = "";
	var ext = "";
	var ootype = "";

	for (var i=0;i<arr.length;i++)
	{

		var n = arr[i].getAttribute("object_name");
		ext = fileExtension(n);

		ootype = getOOType(data,ext);

		//first one, just use it
		if (!oo) 
		{
			oo = ootype;
		}
		else
		{

			//openoffice types don't match, conversion can't continue
			if (oo!=ootype)
			{
				ret = false;
				break;
			}

		}				

	}

	return ret;

}
