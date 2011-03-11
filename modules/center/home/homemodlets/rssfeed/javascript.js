
var editfeedwin;
var modletid;

function editFeed(mid) {

	modletid = mid;

	var ref = openSitePopup(300,200);

	//field for name of feed
	var namestore = "feedName" + modletid;
	var namediv = ce("div","rsseditcell");
	var nameheader = ce("div","formHeader","","RSS Feed Title");
	var namefield = createTextbox("feedName",ge(namestore).value);
	namediv.appendChild(nameheader);
	namediv.appendChild(namefield);

	//field for url path of feed
	var pathstore = "feedPath" + modletid;
	var pathdiv = ce("div","rsseditcell");
	var pathheader = ce("div","formHeader","","RSS Feed URL");
	var pathfield = createTextbox("feedPath",ge(pathstore).value);
	pathfield.setAttribute("size","30");
	pathdiv.appendChild(pathheader);
	pathdiv.appendChild(pathfield);

	//submit button
	var submitdiv = ce("div","rsseditcell");
	var btn = createBtn("rssUpdate","Update Feed","updateFeed()");
	submitdiv.appendChild(btn);

	ref.appendChild(namediv);
	ref.appendChild(pathdiv);
	ref.appendChild(submitdiv);

}

function updateFeed() {

	var fn = ge("feedName");
	var fp = ge("feedPath");

	if (fn.value.length==0) {
		alert("You must enter a name for the feed");
		fn.focus();
		return false;
	}
	if (fp.value.length==0) {
		alert("You must enter a path for the feed");
		fp.focus();
		return false;
	}

	var url = "index.php?module=saverssfeed&updateFeed=1&container=" + modletid + "&feedName=" + fn.value + "&feedPath=" + fp.value;
	protoReq(url,"writeUpdateFeed");

}

function writeUpdateFeed(data) {

	 

	if (data.error) alert(data.error);
	else {

		closeSitePopup();
		clearElement(ge(modletid));
		ge(modletid).innerHTML = "<div class=\"siteMessage\">Updating...</div>";
		loadModlet("rssfeed",modletid)

	}

}

function showFeed(link) {

	var parms = centerParms(800,600,1) + ",scrollbars=yes,menubar=yes,toolbar=yes,resizable=yes";
	var ref =window.open(link,"_rss",parms);
	ref.focus();

}
