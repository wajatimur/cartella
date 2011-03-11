/*************************************************
	FILENAME: sitesearch.js
	PURPOSE:  contains javascript functions for
						operating the site search
*************************************************/

var siteSearchTimer;

/*************************************************
	FUNCTION: ajaxSiteSearch
	PURPOSE:  runs our site search using a typing
						delay
	INPUTS:		none
*************************************************/

function ajaxSiteSearch() {

	//reset the timer   
	clearTimeout(siteSearchTimer);
	var sr = ge("siteSearchResults");

	if (MODULE=="docmgr") {

		updateSiteStatus("Searching...");
		siteSearchTimer = setTimeout("objectSearch()","500");

	} else if (MODULE=="managetasks") {

		updateSiteStatus("Searching...");
		siteSearchTimer = setTimeout("ajaxSearch()","500");

	} else if (MODULE=="searchcontacts") {

		updateSiteStatus("Searching...");
		siteSearchTimer = setTimeout("contactSearch()","500");

	} else if (MODULE=="accounts") {

		updateSiteStatus("Searching...");
		siteSearchTimer = setTimeout("accountSearch()","250");

	} else if (MODULE=="email") {

		updateSiteStatus("Searching...");
		siteSearchTimer = setTimeout("showMbox('','1')","250");

	} else {

	  	//set it again.  when it times out, it will run.  this method keeps fast typers from querying the database a lot
		if (ge("msgSiteSearchString").value.length=="0") {
			sr.style.display = "none";
		}
		else {

			//make sure we can see our search results
			if (sr.style.display=="none") sr.style.display = "block";

			siteSearchTimer = setTimeout("siteSearch()",500);

		}

	}

}

function createSiteSearch() {

	var s = new SETUP_SITE_SEARCH();
	s.create();

}


function SETUP_SITE_SEARCH() {


	this.create = function() {

		/*********************************************
			message search
		*********************************************/
		var msgsearch = ge("messageSiteSearch");       //search for messages
	
		var searchtxt = createTextbox("msgSiteSearchString");     //textbox for entering a search string
		searchtxt.setAttribute("autocomplete","off");
		setKeyUp(searchtxt,"ajaxSiteSearch()");
	
		//setup generic search options
		searchtxt.style.marginLeft = "24px";											
	
		//add the box
	  	msgsearch.appendChild(searchtxt);

		var sr = ce("div","","siteSearchResults");
      		sr.style.display = "none";
      		if (BROWSER=="ie") sr.style.marginLeft = "-330px";
  
      		msgsearch.appendChild(sr);

	};

}

function siteSearch() {

	var util = new SITESEARCH();
	util.search();

}

function SITESEARCH() {

	this.search = function() {

		var ss = escape(ge("msgSiteSearchString").value);

		//setup the xml
		var p = new PROTO();
		p.add("command","docmgr_search_search"); 
		p.add("sort_field","name");
		p.add("limit","20");
		p.add("offset","0");
		p.add("search_string",ss);   
		p.post(DOCMGR_API,createMethodReference(this,"writeSearch"));

	};

	this.writeSearch = function(data) {
		 
		var ref = ge("siteSearchResults");
		clearElement(ref);

		for (var i=0;i<data.object.length;i++) {

			var row = ce("div","siteSearchResultRow");

			var img = createImg(THEME_PATH + "/images/docmgr/fileicons/" + data.object[i].icon);

			var name = ce("div","siteSearchResultRowName","",data.object[i].name);
			var path = ce("div","siteSearchResultRowPath","",data.object[i].object_path);

			row.appendChild(img);
			row.appendChild(name);
			row.appendChild(createCleaner());
			row.appendChild(path);

			row.setAttribute("object_id",data.object[i].id);
			row.setAttribute("object_type",data.object[i].object_type);
			row.setAttribute("object_path",data.object[i].object_path);
			row.onclick = createMethodReference(this,"viewObject");
			ref.appendChild(row);

		}

	};

	this.viewObject = function(e) {

		var ref = getEventSrc(e);
		var id = ref.getAttribute("object_id");
		var type = ref.getAttribute("object_type"); 
		var path = ref.getAttribute("object_path"); 

		if (type=="collection")
		{
			location.href = "index.php?module=docmgr&objectPath=" + path;
		} else {

			var ext = fileExtension(path);
			siteViewFile(id);
		}

	};

}
