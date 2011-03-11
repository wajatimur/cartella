var winref;

function cycleSection(id) {

	var img = ge("img" + id);
	var sect = ge("sub" + id);

	if (sect.style.display=="block") {
		img.setAttribute("src",theme_path + "/images/plusbox.gif");
		sect.style.display = "none";
	} else {
		img.setAttribute("src",theme_path + "/images/dashbox.gif");
		sect.style.display = "block";
	}

}

function viewData(e) {

	winref = openSitePopup(500,300);
	var data = getEventSrc(e).parentNode.getElementsByTagName("div")[0].innerHTML;

	winref.innerHTML = data;

}	

function setFormVals() {

	var arr = new Array("category","account","show");

	for (var i=0;i<arr.length;i++) {

		var fn = arr[i];
		var savefn = "save" + ucfirst(fn);

		ge(fn).value = ge(savefn).value;

	}

}
