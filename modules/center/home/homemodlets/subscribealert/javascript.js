
function loadObjAlerts(data) 
{
        
	if (data)
	{

		if (data.error) alert(data.error);
		clearSiteStatus();

	}

	var p = new PROTO();
	p.add("command","docmgr_object_getalerts");
	p.post(DOCMGR_API,"writeObjAlertResults");

}
 
function writeObjAlertResults(data) 
{

	 
	alertdiv = ge("objAlertList");

	clearElement(alertdiv);

	if (data.error) alert(data.error);
	else if (!data.alert) alertdiv.appendChild(ce("div","errorMessage","","No alerts to display"));
	else if (data.alert) 
	{

		for (var i=0;i<data.alert.length;i++) 
		{

			var cont = ce("div","alertDiv");
			var a = data.alert[i];

			if (a.alert_type=="OBJ_COMMENT_POST_ALERT") var pl = "loadDiscussion";	
			else var pl = "loadProperties";

			//figure out the property module to load
			if (a.object_type=="collection") var mod = "colprop";
			else if (a.object_type=="url") var mod = "urlprop";
			else if (a.object_type=="document") var mod = "docprop";
			else var mod = "fileprop";

			var linkdest = "index.php?module=" + mod + "&objectId=" + a.object_id + "&pageLoad=" + pl;

			var cb = createCheckbox("alertId[]",a.id);
			setClick(cb,"clearObjAlert(event)");
			cont.appendChild(cb);

			var link = ce("a","","",a.name + ": " + a.description);
			link.setAttribute("href",linkdest);
			cont.appendChild(link);

			alertdiv.appendChild(cont);

		}

	}

}


function clearObjAlert(e)
{

	updateSiteStatus("Removing alert");
	var ref = getEventSrc(e);
	var aid = ref.value;	

  //setup the xml
	var p = new PROTO();
	p.add("command","docmgr_object_clearalert");
	p.add("alert_id",aid);
  p.post(DOCMGR_API,"loadObjAlerts");

}
