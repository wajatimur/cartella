//globals
var sitepopupwin;

function openSitePopup(width,height,closehandle) {

	if (BROWSER=="safari") {
		var sl = window.scrollX;
		var st = window.scrollY;
	} else {
		var sl = getScrollLeft();
		var st = getScrollTop();
	}

	//try to center the popup if values are nto passed
  var xPos = (getWinWidth()/2) - (width/2) + sl;
  var yPos = (getWinHeight()/2) - (height/2) + st;

	sitepopupwin = ge("sitePopupWin");
	clearElement(sitepopupwin);

	var winhandle = ce("div","","sitePopupHandle");

	sitepopupwin.style.display = "block";
	sitepopupwin.style.left = xPos + "px";
	sitepopupwin.style.top = yPos + "px";
	sitepopupwin.style.width = width + "px";
	sitepopupwin.style.minHeight = height + "px";	

	//closebutton
	var close = ce("img","sitePopupCloseBtn");
	close.setAttribute("src",theme_path + "/images/icons/close.png");

	//use passed close handler if set
	if (closehandle) setClick(close,closehandle);
	else setClick(close,"closeSitePopup()");

	//start adding goodies
	var mydiv = ce("div","sitePopupContainer");

	winhandle.appendChild(close);
	winhandle.appendChild(createCleaner());
	sitepopupwin.appendChild(winhandle);
	sitepopupwin.appendChild(mydiv);

	if (window.Drag) {
		//work with older version of mootools for editreports
		if (MODULE=="editreport") new Drag.Base(sitepopupwin,{handle:winhandle});
		else new Drag(sitepopupwin,{handle:winhandle});
	}

	//return reference to the container for adding stuff
	return mydiv;

}

function closeSitePopup() {

	var ref = ge("sitePopupWin");
	clearElement(ref);
	ref.style.display = "none";

}

