
var shareobj;

function shareObjects()
{

	keyupmode = "share";

	//return a reference to the parent row
	var arr = getChecked('',1);

	if (arr.length==0)
	{
		alert("You must select which objects you wish to share");
		return false;
	}
	else if (arr.length > 1)
	{
		alert("You may only adjust share settings for one object at a time");
		return false;
	}

	//make sure we have permissions
	if (arr[0].getAttribute("object_perm")!="admin")
	{
		alert("You do not have permission to share this object with another user");
		return false;
	}

	//store the id
	shareobj = arr[0].getElementsByTagName("input")[0].value;

	//create the popup
	var ref = openSitePopup(550,300,"closeShareWindow()");

	ref.appendChild(ce("div","sitePopupHeader","","\"" + arr[0].getAttribute("object_name") + "\" Share Settings"));

	var cell = ce("div","sitePopupCell");

	//header
	var row = ce("div","shareHeaderRow");
	row.appendChild(ce("div","","","Account Name"));
	row.appendChild(ce("div","","","Shared Settings"));
	row.appendChild(createCleaner());
	cell.appendChild(row);
	cell.appendChild(createCleaner());

	//results body
	var cont = ce("div","","shareBody");
	cell.appendChild(cont);
	ref.appendChild(cell);

	ref.appendChild(createShareAccountSearch());

	getShareList();
	

}

function closeShareWindow()
{

	closeSitePopup();
	browsePath();

}

function getShareList()
{

	//fetch the body information
	var p = new PROTO();
	p.add("command","docmgr_share_getlist");
	p.add("object_id",shareobj);
	p.post(DOCMGR_API,"writeGetList");

}

function writeGetList(data)
{

	var tbd = ge("shareBody");
	clearElement(tbd);

	if (data.error) alert(data.error);
	else if (!data.share) 
	{
		tbd.appendChild(ce("div","errorMessage","","Object has not been shared"));
	}
	else
	{

		for (var i=0;i<data.share.length;i++)
		{

			var row = ce("div","shareBodyRow");
			row.setAttribute("account_id",data.share[i].share_account_id);
			row.setAttribute("account_name",data.share[i].share_account_name);

			row.appendChild(ce("div","shareRowName","",data.share[i].share_account_name));

			var cell = ce("div","shareRowSetting");

			var img = createImg(THEME_PATH + "/images/icons/delete.png");
			setClick(img,"saveShareAccount(event)");
			cell.appendChild(img);


			var sel = createSelect("shareSettings[]");
			setChange(sel,"saveShareAccount(event)");
	
			sel[0] = new Option("None","none");
			sel[1] = new Option("View","view");
			sel[2] = new Option("Edit","edit");

			//set the default value of the dropdown
			cell.appendChild(sel);
			sel.value = data.share[i].bitmask_text;

			row.appendChild(cell);			

			row.appendChild(createCleaner());
	
			tbd.appendChild(row);

		}


	}

}


function createShareAccountSearch()
{

	//attach the piece for adding new sharers
	var cell = ce("div","sitePopupCell","addSharedAccount");
	cell.appendChild(ctnode("Add User: "));

	var tb = createTextbox("search_string");
	setKeyUp(tb,"shareAccountSearch()");
	cell.appendChild(tb);

	cell.appendChild(ctnode("With Setting: "));

	var sel = createSelect("share_setting");
	sel[0] = new Option("View","view");
	sel[1] = new Option("Edit","edit");
	cell.appendChild(sel);

	//save button
	var btn = createBtn("saveNewShare","Add User");
	setClick(btn,"saveShareAccount(event)");
	cell.appendChild(btn);

	//the dropdown for search results
	var mydiv = ce("div","","shareSearchResults");
	if (document.all)
	{
		mydiv.style.marginTop = "23px";
		mydiv.style.marginLeft = "-388px";
	}

	cell.appendChild(mydiv);

	return cell;

}

function shareAccountSearch()
{

	var p = new PROTO();
	p.add("command","docmgr_share_getaccounts");
	p.addDOM(ge("addSharedAccount"));
	p.post(DOCMGR_API,"writeShareAccountSearch");

}

function writeShareAccountSearch(data)
{

	var ref = ge("shareSearchResults");

	if (data.error) alert(data.error);
	else 
	{

		ref.style.display = "block";
		clearElement(ref);

		if (!data.account) ref.appendChild(ce("div","errorMessage","","No matches found"));
		else
		{

			for (var i=0;i<data.account.length;i++)
			{

				var row = ce("div","shareAccountRow","",data.account[i].name);
				row.setAttribute("account_id",data.account[i].id);
				row.setAttribute("account_name",data.account[i].name);

				setClick(row,"pickShareAccount(event)");
				ref.appendChild(row);

			}

		}

	}


}

function pickFirstShare(e)
{

  var ref = ge("shareSearchResults");
  var arr = ref.getElementsByTagName("div");
  pickShareAccount(arr[0]);

}


function pickShareAccount(e)
{

	//this happens if called from pickFirstShare
	if (e.tagName=="DIV")
		var ref = e;
	else 
		var ref = getEventSrc(e);

	var aid = ref.getAttribute("account_id");
	var aname = ref.getAttribute("account_name");

	//store the values in our fields
	var cont = ref.parentNode.parentNode;

	//didn't have to do it this way, but I was on an attribute role
	cont.setAttribute("account_id",aid);
	cont.setAttribute("account_name",aname);

	cont.getElementsByTagName("input")[0].value = aname;

	//hide the dropdown
	ge("shareSearchResults").style.display = "none";

}

function saveShareAccount(e)
{

	var ref = getEventSrc(e);

	//updating something already in the list
	if (ref.name=="shareSettings[]")
	{

		var aid = ref.parentNode.parentNode.getAttribute("account_id");
		var aname = ref.parentNode.parentNode.getAttribute("account_name");
		var settings = ref.value;
	}
	else if (ref.tagName=="IMG")
	{
		var aid = ref.parentNode.parentNode.getAttribute("account_id");
		var aname = ref.parentNode.parentNode.getAttribute("account_name");
		var settings = "none";
	}
	else
	{

		var aid = ref.parentNode.getAttribute("account_id");
		var aname = ref.parentNode.getAttribute("account_name");
		var settings = ge("share_setting").value;

	}

	//bail if nothing to do
	if (!aid) return false;

	//save these results
	var p = new PROTO();
	p.add("command","docmgr_share_save");
	p.add("object_id",shareobj);
	p.add("share_account_id",aid);
	p.add("share_level",settings);
	p.post(DOCMGR_API,"writeShareSave");



}

function writeShareSave(data)
{

	if (data.error) alert(data.error);
	else getShareList();

}


