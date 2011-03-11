
function loadObjSub() 
{
        
  //setup the xml
	var p = new PROTO();
	p.add("command","docmgr_object_getallsubscriptions");
	p.post(DOCMGR_API,"writeObjSubResults");

}
 
function writeObjSubResults(data) 
{
	 
	subdiv = ge("objSubList");

	clearElement(subdiv);

	if (data.error) alert(data.error);
	else if (!data.subscribe) subdiv.appendChild(ce("div","errorMessage","","No subscriptions to display"));
	else if (data.subscribe) {

		for (var i=0;i<data.subscribe.length;i++) 
		{

			var cont = ce("div","subDiv");
			var a = data.subscribe[i];

			//figure out the property module to load
			if (a.object_type=="collection") var mod = "colprop";
			else if (a.object_type=="url") var mod = "urlprop";
			else if (a.object_type=="document") var mod = "docprop";
			else var mod = "fileprop";

			var linkdest = "index.php?module=" + mod + "&objectId=" + a.object_id;

			var link = ce("a","","",a.name);
			link.setAttribute("href",linkdest);
			cont.appendChild(link);

			subdiv.appendChild(cont);

		}

	}

}


