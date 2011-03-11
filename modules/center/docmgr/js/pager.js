

/****************************************************************
	FILE:			pager.js
	PURPOSE:	contains functions for displaying and manipulating
						browse/search results pager
****************************************************************/
var maxpagenum;
var minpagerange;
var maxpagerange;

/********************************************************
	FUNCTION:	createPager
	PURPOSE:	creates a pager to page through our message
						list results
********************************************************/
function createPager() {

	//setup all our images for later
	var active_first = ce("img");
	active_first.setAttribute("src","themes/" + site_theme + "/images/active_firstpage.gif");
	setClick(active_first,"firstPage()");

	var active_prev = ce("img");
	active_prev.setAttribute("src","themes/" + site_theme + "/images/active_prevpage.gif");
	setClick(active_prev,"prevPage()");

	var active_next = ce("img");
	active_next.setAttribute("src","themes/" + site_theme + "/images/active_nextpage.gif");
	setClick(active_next,"nextPage()");

	var active_last = ce("img");
	active_last.setAttribute("src","themes/" + site_theme + "/images/active_lastpage.gif");
	setClick(active_last,"lastPage()");

	//inactive images
	var inactive_first = ce("img");
	inactive_first.setAttribute("src","themes/" + site_theme + "/images/inactive_firstpage.gif");
	var inactive_prev = ce("img");
	inactive_prev.setAttribute("src","themes/" + site_theme + "/images/inactive_prevpage.gif");
	var inactive_next = ce("img");
	inactive_next.setAttribute("src","themes/" + site_theme + "/images/inactive_nextpage.gif");
	var inactive_last = ce("img");
	inactive_last.setAttribute("src","themes/" + site_theme + "/images/inactive_lastpage.gif");

	//div for storage
	var mydiv = ge("browsePager");
	var lc = ce("div","leftColumn");
	var rc = ce("div","rightColumn","searchPagerText");

	clearElement(mydiv);

	mydiv.appendChild(lc);
	mydiv.appendChild(rc);

	//save search button
	if (browsemode=="search")
	{
		var cell = siteToolbarCell("Save Search","saveSearch()","save.png");
		cell.id = "saveSearchCell";
		rc.appendChild(cell);
	}

	//make sure we are dealing with numbers
	searchOffset = parseInt(searchOffset);
	searchTotal = parseInt(searchTotal);
	searchLimit = parseInt(searchLimit);
	searchOffset = parseInt(searchOffset);

	//if there are results
	if (searchTotal > 0) {

		//create our <num> out of <total> string
		var txt = ce("span");
		var max;
		var min;

		//set our min/max display for the user
		if (searchOffset==0) min = 1;
		else min = searchOffset;

		if (currentTotal < searchLimit) max = parseInt(currentTotal) + parseInt(searchOffset);
		else max = parseInt(searchLimit) + parseInt(searchOffset);

		rc.appendChild(ctnode("Viewing Results " + min + "-" + max + " of " + searchTotal));

		//numbers
		var pagenum = searchTotal / searchLimit;
		if (pagenum != parseInt(pagenum)) pagenum++;			//increment if we have a remainder
		maxpagenum = parseInt(pagenum);

		//min range is always half the result limit minus current page
		if (maxpagenum > PAGE_RESULT_LIMIT) {

			var half = parseInt(PAGE_RESULT_LIMIT/2);

			if ( (searchPage-half) <=1) {

				var minrange = 1;
				var maxrange = PAGE_RESULT_LIMIT;

			} else if (searchPage==maxpagenum) {

				var minrange = parseInt(maxpagenum) - parseInt(PAGE_RESULT_LIMIT);
				var maxrange = maxpagenum;

			} else {
				var minrange = parseInt(searchPage) - parseInt(half);
				var maxrange = parseInt(searchPage) + parseInt(half);				
			}

		} else {

			var minrange = 1;
			var maxrange = maxpagenum;

		}

		//displayed page range.  Figure out what range we are operating in
		//if pages * per/page > limit, we need an offset
		if (searchPage > PAGE_RESULT_LIMIT) {

			

		}

		for (var i=minrange;i<=maxrange;i++) {

			var link = ce("a");
			link.setAttribute("href","javascript:setPage('" + i + "')");
			link.appendChild(ctnode(i));

			if (i==searchPage) setClass(link,"curSearchPage");

			txt.appendChild(link);
		}


		//on the first page
		if (searchOffset==0 && searchTotal > searchLimit) {
			lc.appendChild(inactive_first);
			lc.appendChild(inactive_prev);
			lc.appendChild(txt);
			lc.appendChild(active_next);
			lc.appendChild(active_last);			
		}
		//on the last page
		else if (searchOffset>0 && searchOffset>=(searchTotal-currentTotal)) {
			lc.appendChild(active_first);
			lc.appendChild(active_prev);
			lc.appendChild(txt);
			lc.appendChild(inactive_next);
			lc.appendChild(inactive_last);			
		}
		else if (searchOffset==0 && searchTotal < searchLimit) {
			lc.appendChild(inactive_first);
			lc.appendChild(inactive_prev);
			lc.appendChild(txt);
			lc.appendChild(inactive_next);
			lc.appendChild(inactive_last);			
		}
		else {
			lc.appendChild(active_first);
			lc.appendChild(active_prev);
			lc.appendChild(txt);
			lc.appendChild(active_next);
			lc.appendChild(active_last);			
		}

	}

}

//for changing pages
/********************************************************
	FUNCTION:	nextPage
	PURPOSE:	cycles to the next page of msg list results
********************************************************/
function nextPage() 
{

	searchOffset = parseInt(searchOffset) + parseInt(searchLimit);
	searchPage++;

	useLast = 1;

	if (browsemode=="browse") browsePath();
	else objectSearch();

}

/********************************************************
	FUNCTION:	prevPage
	PURPOSE:	cycles to the previous page of msg list results
********************************************************/
function prevPage() 
{

	searchOffset = searchOffset - searchLimit;
	searchPage--;

	useLast = 1;

	if (browsemode=="browse") browsePath();
	else objectSearch();

}

/********************************************************
	FUNCTION:	firstPage
	PURPOSE:	change to the first page of results
********************************************************/
function firstPage() {

	searchOffset = 0;
	searchPage = 1;

	useLast = 1;

	if (browsemode=="browse") browsePath();
	else objectSearch();

}

/********************************************************
	FUNCTION:	lastPage
	PURPOSE:	change to the last page of results
********************************************************/
function lastPage() {

	//calculate our final offset
	var rem = searchTotal % searchLimit;

	searchPage = maxpagenum;

	useLast = 1;

	if (rem==0) searchOffset = searchTotal - searchLimit;
	else searchOffset = searchTotal - rem;

	if (browsemode=="browse") browsePath();
	else objectSearch();
		
}

/********************************************************
	FUNCTION:	setPage
	PURPOSE:	change to the last page of results
********************************************************/
function setPage(num) {

	searchPage = num;
	searchOffset = searchLimit * num - searchLimit;

	useLast = 1;

	if (browsemode=="browse") browsePath();
	else objectSearch();
		
}

