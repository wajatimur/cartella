
var curres;
var curfield;
var showsuggest = "";

function suggestAddress(e) 
{

	if (timer) clearTimeout(timer);

	//get the calling field
	var ref = getEventSrc(e);
	curfield = ref;

	//figure out what our search string is
	var arr = ref.value.split(",");
	var ss = arr[arr.length - 1];

	//set the field we'll write our results to
	curres = ge(ref.id + "suggest");

	//if we have a string, proceed
	if (ss.length > 0) 
	{

		if (!showsuggest) runSuggestAddress(ss);
		else timer = setTimeout("runSuggestAddress('" + ss + "')",250);

	} else {

		clearElement(curres);
		curres.style.display = "none";
		showsuggest = "";

	}

}

function runSuggestAddress(ss) {

  var url = "index.php?module=emailsuggest&addressbook=both&limit=10&searchString=" + ss;
  protoReq(url,"writeSuggestResults");

}

function writeSuggestResults(data) {
 
   

  if (data.error) alert(data.error);
  else {

    clearElement(curres);

		if (!data.contact) 
		{

			curres.style.display = "none";
			showsuggest = "";

		} else {			

			curres.style.display = "block";
			showsuggest = "1";

	    for (var i=0;i<data.contact.length;i++) {

				if (data.contact[i].email) 
				{
	      	curres.appendChild(suggestEntry(data.contact[i]));
				}

	    }

		}

  }

}

function suggestEntry(data) {

	var val = data.first_name + " " + data.last_name + " <" + data.email + ">";

	var mydiv = ce("div","","",val);
	mydiv.setAttribute("email",val);

	setClick(mydiv,"useSuggestEntry(event)");
	return mydiv;

}

function useSuggestEntry(e) {

	var ref = getEventSrc(e);
	var data = ref.getAttribute("email");

	//replace the last entry with this value
	var arr = curfield.value.split(",");
	var key = arr.length - 1;
	arr[key] = data;

	curfield.value = arr.join(", ") + ", ";

	clearElement(curres);
	curres.style.display = "none";
	showsuggest = "";

	setCaretPosition(curfield,curfield.value.length);

}

function pickFirstSuggest() {

	var arr = curres.getElementsByTagName("div");

	var data = arr[0].getAttribute("email");

	//replace the last entry with this value
	var arr = curfield.value.split(",");
	var key = arr.length - 1;
	arr[key] = data;

	curfield.value = arr.join(", ");

	clearElement(curres);
	curres.style.display = "none";
	showsuggest = "";

}

