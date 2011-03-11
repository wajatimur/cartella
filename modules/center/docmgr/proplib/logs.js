/***********************************************
	file for editing object properties
***********************************************/

function loadObjLogs() {

	clearSiteStatus();
	loadLogToolbar();
	viewObjLogs();

}

function viewObjLogs() {

	updateSiteStatus("Loading Object Logs");

	//load our logs
	var p = new PROTO();
	p.add("command","docmgr_log_getlist");
	p.add("object_id",object);
	p.add("filter",ge("logFilter").value);
	p.post(DOCMGR_API,"writeViewLogs");

}

function writeViewLogs(data) {
	 
	var cont = ge("content");

	clearElement(cont);
	clearSiteStatus();

	if (data.error) alert(data.error);
	else if (!data.log) {

		cont.appendChild(ce("br"));
		cont.appendChild(ce("div","errorMessage","","No logs found for object"));
		
	} else {

		var tbl = createTable("logList");
		var tbd = ce("tbody");
		tbl.appendChild(tbd);
		cont.appendChild(tbl);

		//table header
		var row = ce("tr");
		row.appendChild(ce("td","logListHeader","","Date"));
		row.appendChild(ce("td","logListHeader","","Entry"));
		row.appendChild(ce("td","logListHeader","","Account"));
		tbd.appendChild(row);

		for (var i=0;i<data.log.length;i++) {

			var d = data.log[i];

			var row = ce("tr");
			row.appendChild(ce("td","logListData","",d.log_time_view));
			row.appendChild(ce("td","logListData","",d.log_type_view));
			row.appendChild(ce("td","logListData","",d.account_name));
			tbd.appendChild(row);

		}

	}	

}


function loadLogToolbar() {

  clearElement(tbBtns);
	clearElement(tbTitle);

	var sel = createSelect("logFilter");
	setChange(sel,"viewObjLogs()");
	sel[0] = new Option("Last Ten","lastten");
	sel[1] = new Option("My Entries","myentries");
	sel[2] = new Option("Virus Scans","virus");
	sel[3] = new Option("Emails","email");
	sel[4] = new Option("File Views","view");
	sel[5] = new Option("Checkins/Checkouts","checkin");
	sel[6] = new Option("All Entries","all");

	var div = ce("div","logFilterDiv","","Log Filter: ");
	div.appendChild(sel);
	tbBtns.appendChild(div);

}

