
var winref;
var sublist;
var subcont;
var savetimer;
var alerts;
var cursubscribe;
var subobject;
var subtype;

/****************************************************************
  FUNCTION: manageSubscriptions
  PURPOSE:  main function for loading our edittask page.
  INPUT:    none
*****************************************************************/
function manageSubscription(objid,objtype) {

	alerts = new Array();
	cursubscribe = new Array();
	subobject = objid;
	subtype = objtype;

	updateSiteStatus("Loading subscription information");

	//first get our subscription list
	var p = new PROTO("XML");
	p.get("config/alerts.xml","writeAlerts");

	//now get what we are currently subscribed to for this object
	//setup the xml
	var p = new PROTO();
	p.add("command","docmgr_object_getsubscription");
	p.add("object_id",objid);
	p.post(DOCMGR_API,"writeSubList");

	endReq("subscriptionEdit()");

}

function writeAlerts(data) {

	if (data.error) alert(data.error);
	if (!data.alert) alert("No available alerts found");
	else 
	{

		for (var i=0;i<data.alert.length;i++) 
		{

			if (data.alert[i].type) 
			{

				if (subtype=="collection" && data.alert[i].type=="colsubscription") alerts.push(data.alert[i]);
				else if (data.alert[i].type=="subscription") alerts.push(data.alert[i]);

			}

		}

	}

}

function writeSubList(data) 
{

	if (data.error) alert(data.error);
	else if (data.subscribe) cursubscribe = data.subscribe;
	else cursubscribe = new Array();

}

function subscriptionEdit() 
{

	clearSiteStatus();

	winref = openSitePopup(350,250);
	sublist = ce("div","sitePopupCell");
	sublist.appendChild(ce("div","formHeader","","Notify me when:"));

	//create toolbar
	winref.appendChild(ce("div","sitePopupHeader","","Manage Subscriptions"));

	for (var i=0;i<alerts.length;i++) 
	{

		var row = ce("div");
		var cb = createCheckbox("type[]",alerts[i].link_name);
		row.appendChild(cb);

		//see if it's checked
		for (var c=0;c<cursubscribe.length;c++) {
			if (alerts[i].link_name==cursubscribe[c].event_type) {
				cb.checked = true;
				break;
			}
		}

		row.appendChild(ctnode(alerts[i].name));

		sublist.appendChild(row);

	}

	var emailcell = ce("div","sitePopupCell");
	
	var emaildiv = ce("div","formHeader");
	var cb = createCheckbox("send_email","1");
	if (cursubscribe.length>0 && cursubscribe[0].send_email=="t") cb.checked = true;
	emaildiv.appendChild(cb);
	emaildiv.appendChild(ctnode("Send email notification"));
	emailcell.appendChild(emaildiv);

	var filediv = ce("div","formHeader");
	var cb = createCheckbox("send_file","1");
	if (cursubscribe.length>0 && cursubscribe[0].send_file=="t") cb.checked = true;
	filediv.appendChild(cb);
	filediv.appendChild(ctnode("Send file with email notification"));
	emailcell.appendChild(filediv);

	var btndiv = ce("div","sitePopupCell");
	var btn = createBtn("submit","Update Settings");
	setClick(btn,"updateSubscriptions()");	
	btndiv.appendChild(btn);

	winref.appendChild(sublist);
	winref.appendChild(emailcell);
	winref.appendChild(btndiv);

}

function updateSubscriptions() 
{

	updateSiteStatus("Updating subscriptions");

	var p = new PROTO();
	p.add("command","docmgr_object_updatesubscription");
	p.add("object_id",subobject);
	p.addDOM(winref);
	p.post(DOCMGR_API,"writeUpdateSubscriptions");

}

function writeUpdateSubscriptions(data) 
{

	clearSiteStatus();

	if (data.error) alert(data.error);
	else closeSitePopup();

}
