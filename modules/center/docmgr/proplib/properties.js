/***********************************************
	file for editing object properties
***********************************************/

var objpage = "";

function loadProperties() {

	clearSiteStatus();
	loadPOToolbar();

	clearElement(content);
	content.appendChild(objName());
	content.appendChild(objDesc());
	content.appendChild(objOwner());
	content.appendChild(objLastMod());
	content.appendChild(objModOwner());

	if (objinfo.object_type=="collection") content.appendChild(objView());

	content.appendChild(ce("div","propCell","keywordList"));

	loadKeywordInfo();

	//disable the form if necessary
	//if we have view only properties, bail
	if(objinfo.bitmask_text!="admin")
	{
		disableForms(content);
	}

}

function loadPOToolbar() {

	clearElement(tbBtns);
	clearElement(tbTitle);

	//if we have view only properties, bail
	if(objinfo.bitmask_text=="admin")
	{

		//setup our buttons
		tbBtns.appendChild(siteToolbarCell("Save","saveProperties()","save.png"));

	} 

	//show an unlock button if user can unlock
	if (objinfo.locked=="t" && (objinfo.lock_owner==USER_ID || objinfo.bitmask_text=="admin"))
	{

	  //setup our buttons
	  tbBtns.appendChild(siteToolbarCell("Clear Lock","unlock()","checkin.png"));

	}

}

function loadKeywordInfo() {

  //setup command to get object info
	var p = new PROTO();
  p.add("command","docmgr_keyword_getlist");
  p.add("object_id",objinfo.id);

	//if we are in a collection, use the object id as the parent id, otherwise use the parent id
	//if (objinfo.object_type=="collection") p.add("parent_id",object);
  //else p.add("parent_id",ge("parentId").value);

	p.post(DOCMGR_API,"writeKeywordInfo");

}

function writeKeywordInfo(data) {

  var keycont = ge("keywordList");

	if (data.error) alert(data.error);
	else if (data.keyword && data.keyword.length > 0)
  {

		var keywords = data.keyword;

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

			if(objinfo.bitmask_text!="admin") tb.disabled = true;

			//if there is a value for this keyword for this object, set it
			if (isData(curkey.object_value)) tb.value = curkey.object_value;

      tbd.appendChild(row);

    }

  }

  return keycont;

}



function unlock() {

	if (confirm("Are you sure you want to unlock this object?")) {

		updateSiteStatus("Unlocking object");

		//unlock our document
		var p = new PROTO();

		if (perm_check(ADMIN)) p.add("command","docmgr_lock_clearall");
		else p.add("command","docmgr_lock_clear");

		p.add("object_id",object);

		//make sure the unlock goes through for an admin
		//if (perm_check(ADMIN)) p.add("force","1");

		p.post(DOCMGR_API,"writeUnlock");

	}

}

function writeUnlock(data) {

	if (data.error) alert(data.error);
	else 
	{
		objpage = "loadProperties";
		loadPage();
	}

}

function objName() {

	var cell = ce("div","propCell");
	cell.appendChild(ce("div","formHeader","","Name"));
	var tb = createTextbox("name",objinfo.name);
	cell.appendChild(tb);

	return cell;

}

function objOwner() {

	var cell = ce("div","propCell");
	cell.appendChild(ce("div","formHeader","","Owner"));
	cell.appendChild(ctnode(objinfo.owner_name));

	return cell;

}

function objDesc() {

	var cell = ce("div","propCell");
	cell.appendChild(ce("div","formHeader","","Description"));
	var ta = createTextarea("summary",objinfo.summary);
	cell.appendChild(ta);

	return cell;
}


function objLastMod() {

	var cell = ce("div","propCell");
	cell.appendChild(ce("div","formHeader","","Last Modified"));
	var ta = ctnode(objinfo.view_last_modified);
	cell.appendChild(ta);

	return cell;
}

function objModOwner() {

	var cell = ce("div","propCell");
	cell.appendChild(ce("div","formHeader","","Modified By"));
	var ta = ctnode(objinfo.view_modified_by);
	cell.appendChild(ta);

	return cell;
}

function objView() {

	var cont = ce("div");

	if (objinfo.bitmask_text=="admin")
	{

		var cell = ce("div","propCell");

		cell.appendChild(ce("div","formHeader","","Default browse view for all users"));

		var sel = createSelect("default_view");
		sel[0] = new Option("List","list");
		sel[1] = new Option("Thumbnails","thumbnail");

		cell.appendChild(sel);
		cont.appendChild(cell);

		if (isData(objinfo.default_view)) sel.value = objinfo.default_view;

	}

	return cont;

}

function saveProperties() {

	if (!checkRequiredKeywords())
	{

		alert("Not all required keywords have values");
		return false;

	}

	updateSiteStatus("Saving properties");

	var p = new PROTO();
	p.add("command","docmgr_object_save");
	p.add("object_id",object);
	p.addDOM(content);
	p.post(DOCMGR_API,"writeSaveProp");

}

function writeSaveProp(data) {

	clearSiteStatus();

	if (data.error) alert(data.error);
	else {
	
		updateSiteStatus("Properties updated successfully");
		loadObjInfo();
		setTimeout("clearSiteStatus()","3000");

	}

}

/******************************************************************
  FUNCTION: checkRequiredKeywords
  PURPOSE:  make sure all required keywords have been filled out
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

function writePropObjInfo(data) {

  if (data.error) alert(data.error);
  else {

    objinfo = data.object[0];
		loadProperties();

  }

}


function viewFile()
{

  clearSiteStatus();
  siteViewFile(objinfo.id,objinfo.object_type,objinfo.extension);

}

function propManageSubscription()
{

  clearSiteStatus();
  manageSubscription(objinfo.id,objinfo.object_type);

}

function loadObjInfo() 
{

  //setup command to get object info
  var p = new PROTO();
  p.add("command","docmgr_object_getinfo");
  p.add("object_id",object);
  p.post(DOCMGR_API,"writeObjInfo");

}
 
function writeObjInfo(data) 
{

  clearSiteStatus();

  if (data.error) 
	{
    alert(data.error);
    return false;
  } 
	else
	{

    objinfo = data.object[0];
    loadMenu();

		if (objpage) 
		{
			var func = eval(objpage + "()");
			if (func) func();
		}
		else
		{
			loadProperties();
		}

  }

}  
