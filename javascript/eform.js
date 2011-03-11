
/********************************************************************
	FILENAME: forms.js
	PURPOSE:  contains javascript form functions for our module

*********************************************************************/

//globals
var formfile;
var editBtn;
var formdata;
var formhandler;
var datahandler;
var changehandler;

/****************************************************************
	FUNCTION: loadForms
	PURPOSE:  loads all forms into memory from our main xml file
	INPUT:	  file -> the xml file containing our form
						deffile -> xml file containg form definitions (optional)
						fhandler -> the function we pass our created div
												with all our form content to.  that
												function will be responsible for placing
												the data on the page
						dhandler -> the function we call to get data for
												the current object.  it must return
												it in array format, with the keys
												of the array matching the value_field
												entry for the form
						chandler -> the function we call when a form value
												is changed.
	RETURN:		none;
*****************************************************************/	

function loadForms(file,deffile,fhandler,dhandler,chandler) {

	//store our form in a global for later
	formfile = file;

	//load our forms from the url into an array
	var url = site_url + "index.php?module=eform&file=" + formfile;
	if (deffile) url += "&deffile=" + deffile;

	formhandler = fhandler;

	if (dhandler) datahandler = dhandler;
	else datahandler = "";

	if (chandler) changehandler = chandler;
	else changehandler = "";

	var p = new PROTO("XML");
	p.setDecode(false);
	p.post(url,"writeForms");

}

/****************************************************************
	FUNCTION: writeForms
	PURPOSE:  handler for results of loadForms.  Creates our form
						and calls the result function by passing the
						created div to it
	INPUT:	  resp -> xml response from loadForms
	RETURN:		none
*****************************************************************/	

function writeForms(xml) {

	//get our data first from our data handler function.  we will
	//then use this data to populate the forms we create
	if (datahandler) 
	{
		var func = eval(datahandler);
		var ret = func();
		if (ret) formdata = ret;
		else formdata = "";
	}

	//proccess our response
	var cont = processElements(xml);

	//call the handler function for our resulting div
	formhandler = eval(formhandler);
	formhandler(cont);

}



/****************************************************************
	FUNCTION: processElements
	PURPOSE:  processes all elements from our div xml file
	INPUT:	  dataNode -> the firstChild of the data xmlf ile
	RETURN:		html element that can be appended to another element
*****************************************************************/	

function processElements(dataNode) {

	var i = 0;
	var mydiv = ce("div");

	while (dataNode.childNodes[i]) 
	{

    var objNode = dataNode.childNodes[i];

    if (objNode.nodeType!=1) 
		{
			i++;
			continue;
		}

		//process div container
		if (objNode.nodeName=="div") {

			var div = ce("div");

			//transfer id and class elements over to our new div
			var objid = objNode.getAttribute("id");
			var objclass = objNode.getAttribute("class");
			var objclick = objNode.getAttribute("onclick");

			if (objid) div.setAttribute("id",objid);						
			if (objclass) setClass(div,objclass);
			if (objclick) setClick(div,objclick);

			//if there are more elements under this one, process them
			if (XML.hasChildNodes(objNode)) {
				var ret = processElements(objNode);
				if (ret) div.appendChild(ret);
			} else if (objNode.firstChild && objNode.firstChild.nodeValue) {
				div.appendChild(ctnode(objNode.firstChild.nodeValue));
			}

			mydiv.appendChild(div);

		//process form
		} else if (objNode.nodeName=="form") {

			//convert the form data into an array
			var curform = XML.decode(objNode);
			var curdiv;

			//call the appropriate form creator based on the returned type
			//the function should be called "_<formname>Form"
	
			if (curform.type) {
				var funcname = "_" + curform.type + "Form";
				func = eval(funcname);
				curdiv = func(curform);
			}

			if (curdiv) mydiv.appendChild(curdiv);

		}

		i++;

	}

	return mydiv;

}


/****************************************************************
	FUNCTION: _textboxForm
	PURPOSE:  creates a text input form
	INPUT:	  curform -> xml data in array form from the
											 xml config file
	RETURNS:	div containing the created div
*****************************************************************/	

function _textboxForm(curform) {

	var mydiv = ce("div");
	setClass(mydiv,"inputCell");

	var header = ce("div");

	//use a multiform class if set
	if (curform.display && curform.display=="multiform") {
		setClass(header,"multiformHeader");
	} else {
		setClass(header,"formHeader");
	}

	header.appendChild(ctnode(curform.title));

	var size;
	if (curform.size) size = curform.size;

	//see if there's data to populate this form
	var curval;
	if (formdata && isData(formdata[curform.data])) curval = formdata[curform.data];
	else if (isData(curform.defaultval)) curval = curform.defaultval;

	var form = createTextbox(curform.name,curval,size);

	//if we have a global change handler, pass that
	if (changehandler) curform.onchange += ";" + changehandler;

	//optional settings, set by corresponding xml tags for the form
	if (curform.disabled) form.disabled = true;
	if (curform.onkeyup) setKeyUp(form,curform.onkeyup);
	if (curform.onchange) setChange(form,curform.onchange);
	if (curform.onclick) setClick(form,curform.onclick);
	if (curform.autocomplete) form.setAttribute("autocomplete",curform.autocomplete);
	if (curform.onfocus) setFocus(form,curform.onfocus);
	if (curform.onblur) setBlur(form,curform.onblur);
	
	//put it together
	mydiv.appendChild(header);
	mydiv.appendChild(form);
	mydiv.appendChild(createCleaner());

	return mydiv;

}

/****************************************************************
	FUNCTION: _passwordForm
	PURPOSE:  creates a text input form
	INPUT:	  curform -> xml data in array form from the
											 xml config file
	RETURNS:	div containing the created div
*****************************************************************/	

function _passwordForm(curform) {

	var mydiv = ce("div");
	setClass(mydiv,"inputCell");

	var header = ce("div");

	//use a multiform class if set
	if (curform.display && curform.display=="multiform") {
		setClass(header,"multiformHeader");
	} else {
		setClass(header,"formHeader");
	}

	header.appendChild(ctnode(curform.title));

	var size;
	if (curform.size) size = curform.size;

	//see if there's data to populate this form
	var curval;
	if (formdata && isData(formdata[curform.data])) curval = formdata[curform.data];
	else if (isData(curform.defaultval)) curval = curform.defaultval;

	var form = createPassword(curform.name,curval,size);

	//if we have a global change handler, pass that
	if (changehandler) curform.onchange += ";" + changehandler;

	//optional settings, set by corresponding xml tags for the form
	if (curform.disabled) form.disabled = true;
	if (curform.onkeyup) setKeyUp(form,curform.onkeyup);
	if (curform.onchange) setChange(form,curform.onchange);
	if (curform.onclick) setClick(form,curform.onclick);
	if (curform.autocomplete) form.setAttribute("autocomplete",curform.autocomplete);
	if (curform.onfocus) setFocus(form,curform.onfocus);
	if (curform.onblur) setBlur(form,curform.onblur);
	
	//put it together
	mydiv.appendChild(header);
	mydiv.appendChild(form);
	mydiv.appendChild(createCleaner());

	return mydiv;

}

/****************************************************************
	FUNCTION: _textareaForm
	PURPOSE:  creates a text input form in disabled mode
	INPUT:	  curform -> xml data in array form from the
											 xml config file
	RETURNS:	div containing the created div
*****************************************************************/	

function _textareaForm(curform) {

	var mydiv = ce("div");
	setClass(mydiv,"inputCell");

	var header = ce("div");
	if (curform.display && curform.display=="multiform") {
		setClass(header,"multiformHeader");
	} else {
		setClass(header,"formHeader");
	}

	header.appendChild(ctnode(curform.title));

	//see if there's data to populate this form
	var curval;
	if (formdata && isData(formdata[curform.data])) curval = formdata[curform.data];
	else if (isData(curform.defaultval)) curval = curform.defaultval;

	var form = createTextarea(curform.name,curval,curform.rows,curform.cols);

	//if we have a global change handler, pass that
	if (changehandler) curform.onchange += ";" + changehandler;

	//make it disabled
	if (curform.disabled) form.disabled = true;
	if (curform.onkeyup) setKeyUp(form,curform.onkeyup);
	if (curform.onchange) setChange(form,curform.onchange);
	if (curform.onclick) setClick(form,curform.onclick);
	if (curform.onfocus) setFocus(form,curform.onfocus);
	if (curform.onblur) setBlur(form,curform.onblur);

	//put it together
	mydiv.appendChild(header);
	mydiv.appendChild(form);
	mydiv.appendChild(createCleaner());

	return mydiv;

}


/****************************************************************
	FUNCTION: _selectForm
	PURPOSE:  creates a text input form in disabled mode
	INPUT:	  curform -> xml data in array form from the
											 xml config file
	RETURNS:	div containing the created div
*****************************************************************/	

function _selectForm(curform) {

	var mydiv = ce("div","inputCell");

	//if we have a global change handler, pass that
	if (changehandler) curform.onchange += ";" + changehandler;

	var sel = createSelect(curform.name);
	if (curform.disabled) sel.disabled = true;
	if (curform.onchange) setChange(sel,curform.onchange);
	if (curform.onclick) setClick(sel,curform.onclick);
	if (curform.onfocus) setFocus(sel,curform.onfocus);
	if (curform.onblur) setBlur(sel,curform.onblur);
  if (curform.multiple) sel.multiple = true;
  if (curform.size) sel.setAttribute("size",curform.size);

	var i;

	//if we returned records, insert them into the form
	if (curform.option) {
		for (i=0;i<curform.option.length;i++)
			sel[i] = new Option(curform.option[i].title,curform.option[i].data);
	}

	//see if there's data to populate this form
	var curval;
	if (formdata && isData(formdata[curform.data])) curval = formdata[curform.data];
	else if (isData(curform.defaultval)) curval = curform.defaultval;

	if (curval) sel.value = curval;

	//create header and append to container
	//use a multiform class if set
	var header = ce("div");
	if (curform.display && curform.display=="multiform") {
		setClass(header,"multiformHeader");
	} else {
		setClass(header,"formHeader");
	}
	header.appendChild(ctnode(curform.title));
	mydiv.appendChild(header);

	mydiv.appendChild(sel);	
	mydiv.appendChild(createCleaner());
	return mydiv;

}


/****************************************************************
	FUNCTION: _checkboxForm
	PURPOSE:  creates a checkbox form
	INPUT:	  curform -> xml data in array form from the
											 xml config file
	RETURNS:	div containing the created div
*****************************************************************/	

function _checkboxForm(curform) {

	var mydiv = ce("div","inputCell");

	var i;

	//create header and append to container
	var header = ce("div","multiformHeader");

	if (isData(curform.title)) header.appendChild(ctnode(curform.title));
	mydiv.appendChild(header);

	//which field to we use for data
	if (curform.data_node) var dataField = curform.data_node;
	else var dataField = curform.data;

	//get our current data.  tack a comma on for value checking later
	var dataStr = "";
	if (formdata && isData(formdata[dataField])) dataStr = "," + formdata[dataField] + ",";
	else if (isData(curform.defaultval)) dataStr = "," + curform.defaultval + ",";			//no current data, use default value if set

	//if we returned records, insert them into the form
	if (curform.option) {

		var num = parseInt(curform.option.length / 2);
		if (curform.option.length % 2 != 0) num++;

		//do the first column
		var lc = ce("div");
		setClass(lc,"multiformLeftColumn");

		for (i=0;i<num;i++) {
			lc.appendChild(_checkboxEntry(curform,i,dataStr));
		}

		//do the second column
		var rc = ce("div");
		setClass(rc,"multiformRightColumn");

		for (i=num;i<curform.option.length;i++) {
			rc.appendChild(_checkboxEntry(curform,i,dataStr));
		}

		mydiv.appendChild(lc);
		mydiv.appendChild(rc);
		mydiv.appendChild(createCleaner());

	}

	mydiv.appendChild(createCleaner());

	return mydiv;

}

/****************************************************************
	FUNCTION: _checkboxEntry
	PURPOSE:  creates a checkbox entry for _checkboxForm
	INPUT:	  curform -> xml data in array form from the
						key -> key in curform we're on
						dataStr -> string of selected data
	RETURNS:	div containing the created div
*****************************************************************/	

function _checkboxEntry(curform,key,dataStr) {

			var div = ce("div");
			setClass(div,"multiformInputCell");

			//grrr
			if (document.all) {

			} else {


			}

			//check it if selected
			if (dataStr.indexOf("," + curform.option[key].data + ",")!=-1) var check = 1;
			else var check = "";

			var cb = createForm(curform.type,curform.name,check);

			div.appendChild(cb);
			div.appendChild(ctnode(curform.option[key].title));
			div.appendChild(createCleaner());

			//if we have a global change handler, pass that
			if (changehandler) curform.onchange += ";" + changehandler;

			//optional actions
			cb.value = curform.option[key].data;
			if (curform.disabled) cb.disabled = true;
			if (curform.onclick) setClick(cb,curform.onclick);
			if (curform.onchange) setChange(cb,curform.onchange);

			return div;

}

/****************************************************************
	FUNCTION: _radioForm
	PURPOSE:  creates a radio form.  basically a pointer to the
						_checkboxForm
	INPUT:	  curform -> xml data in array form from the
											 xml config file
	RETURNS:	div containing the created div
*****************************************************************/	

function _radioForm(data) {

	return _checkboxForm(data);

}

/****************************************************************
	FUNCTION: _dateselectForm
	PURPOSE:  creates a date select form
	INPUT:	  curform -> xml data in array form from the
											 xml config file
	RETURNS:	html object containing the created div
*****************************************************************/	

function _dateselectForm(curform) {

	var mydiv = ce("div");
	setClass(mydiv,"inputCell");

	var header = ce("div");
	setClass(header,"formHeader");
	header.appendChild(ctnode(curform.title));

	var curval = "";
	if (formdata && isData(formdata[curform.data])) curval = formdata[curform.data];
	else if (isData(curform.defaultval)) {
		if (curform.defaultval=="NOW") {
			var d = new Date();
			curval = (d.getMonth() + 1) + "/" + d.getDate() + "/" + d.getFullYear();
		} else curval = curform.defaultval;
	}

	//create a begin and end form
	var dform = createDateForm(curform,curval);

	mydiv.appendChild(header);
	mydiv.appendChild(dform);
	mydiv.appendChild(createCleaner());
	return mydiv;
	

}


/****************************************************************
	FUNCTION: _daterangeForm
	PURPOSE:  creates a date select form
	INPUT:	  curform -> xml data in array form from the
											 xml config file
	RETURNS:	html object containing the created div
*****************************************************************/	

function _daterangeForm(curform) {

	var mydiv = ce("div");
	setClass(mydiv,"inputCell");

	var header = ce("div");
	setClass(header,"formHeader");
	header.appendChild(ctnode(curform.title));

	var basename = curform.name;

	//create a begin and end form
	curform.name = basename + "Begin";
	var begin = createDateForm(curform);

	curform.name = basename + "End";
	var end = createDateForm(curform);

	mydiv.appendChild(header);
	mydiv.appendChild(ctnode("From "));
	mydiv.appendChild(begin);
	if (curform.separator) mydiv.appendChild(ce(curform.separator));
	mydiv.appendChild(ctnode(" To "));
	mydiv.appendChild(end);

	return mydiv;
	

}

function createDateForm(curform,curval) {

	var myspan = ce("span");
	var form = createTextbox(curform.name);
	var btn = createBtn(curform.name + "_btn","...");

	//if we have a global change handler, pass that
	if (changehandler) curform.onchange += ";" + changehandler;

	//make it readonly and disabled if necessary
	if (curform.disabled) form.disabled = true;
	if (curform.readonly) form.readonly = true;
	if (curform.disabled) btn.disabled = true;
	if (curform.size) form.setAttribute("size",curform.size);
	if (curform.onclick) setClick(form,curform.onclick);
	if (curform.onchange) setChange(form,curform.onchange);
	if (curform.onkeyup) setChange(form,curform.onkeyup);

	if (curval) form.value = curval;

	if (!window.Calendar) {
		alert("You must include the calendar javascript file");
	}

 	Calendar.setup({
          inputField      :    form,
					ifFormat				:		"%m/%d/%Y",
          button          :    btn,
          singleClick     :    true,           // double-click mode
          step            :    1                // show all years in drop-down boxes (instead of every other year as default)
      });

	//put it together
	myspan.appendChild(form);
	myspan.appendChild(btn);

	return myspan;

}

/****************************************************************
	FUNCTION: _timeForm
	PURPOSE:  creates a date select form
	INPUT:	  curform -> xml data in array form from the
											 xml config file
	RETURNS:	html object containing the created div
*****************************************************************/	

function _timeForm(curform) {

	var mydiv = ce("div");
	setClass(mydiv,"inputCell");

	var header = ce("div");
	setClass(header,"formHeader");
	header.appendChild(ctnode(curform.title));

	var curval = "";
	if (formdata && isData(formdata[curform.data])) curval = formdata[curform.data];
	else if (isData(curform.defaultval)) {
		if (curform.defaultval=="NOW") {
			var d = new Date();
			curval = d.getHours() + ":" + d.getMinutes();
		} else curval = curform.defaultval;
	}

	//create our hours and minutes.  the forms will have the form name
	//+ "Hour" or + "Minute"

	var hform = createTextbox(curform.name + "Hour");
	var mform = createTextbox(curform.name + "Minute");
	hform.size = 2;
	hform.setAttribute("maxlength","2");
	mform.size = 2;
	mform.setAttribute("maxlength","2");

	var period = createSelect(curform.name + "Period");
	period[0] = new Option("A.M.","am");
	period[1] = new Option("P.M.","pm");

	//figure out the values
	if (curval) {

		var valarr = curval.split(":");
		var hour = parseInt(valarr[0]);
		var min = parseInt(valarr[1]);

		//reformat our time to normalcy
		if (hour==0) {
			hour = 12;
			pval = "am";
		} else if (hour>12) {
			hour = hour - 12;
			pval = "pm";
		} else {
			pval = "am";
		}

		//put our values 
		hform.value = hour;
		mform.value = min;
		period.value = pval;
	}


	//create a begin and end form
	var dform = createDateForm(curform,curval);

	mydiv.appendChild(header);
	mydiv.appendChild(hform);
	mydiv.appendChild(ce("div","timeSep","",":"));
	mydiv.appendChild(mform);
	mydiv.appendChild(period);
	mydiv.appendChild(createCleaner());
	return mydiv;
	

}



/****************************************************************
	FUNCTION: _yesnoForm
	PURPOSE:  creates the yesno select form
	INPUT:	  data -> data for the current prospect
*****************************************************************/	

function _yesnoForm(curform) {

	var mydiv = ce("div");
	setClass(mydiv,"inputCell");

	var header = ce("div");

	//use a multiform class if set
	if (curform.display && curform.display=="multiform") {
		setClass(header,"multiformHeader");
	} else {
		setClass(header,"formHeader");
	}

	header.appendChild(ctnode(curform.title));

	//if we have a global change handler, pass that
	if (changehandler) curform.onchange += ";" + changehandler;

	var yescheck = "";
	var nocheck = "";

	if (formdata && (formdata[curform.data]=='t' || formdata[curform.data]=='1')) yescheck = "1";
	else nocheck = "0";

	//check yes or no
	var yesdiv = ce("div");
	var yesbtn = createRadio(curform.name,"1",yescheck);
	if (curform.onchange) setChange(yesbtn,curform.onchange);

	yesdiv.appendChild(yesbtn);
	yesdiv.appendChild(ce("div","","","Yes"));

	var nodiv = ce("div");
	var nobtn = createRadio(curform.name,"0",nocheck);
	if (curform.onchange) setChange(nobtn,curform.onchange);

	nodiv.appendChild(nobtn);
	nodiv.appendChild(ce("div","","","No"));

	var choice = ce("div","yesnoform");
	choice.appendChild(yesdiv);
	choice.appendChild(nodiv);
	choice.appendChild(createCleaner());

	mydiv.appendChild(header);
	mydiv.appendChild(choice);
	mydiv.appendChild(createCleaner());


	return mydiv;

}

/****************************************************************
	FUNCTION: _pricerangeForm
	PURPOSE:  creates a checkbox form
	INPUT:	  curform -> xml data in array form from the
											 xml config file
	RETURNS:	div containing the created div
*****************************************************************/	

function _pricerangeForm(curform) {

	var mydiv = ce("div");
	setClass(mydiv,"inputCell");

	var i;

	//create header and append to container
	var header = ce("div");
	setClass(header,"multiformHeader");

	if (curform.title) header.appendChild(ctnode(curform.title));
	mydiv.appendChild(header);

	var range = "";
	if (formdata && isData(formdata["price_range"])) range = formdata["price_range"].toString();
	var min;
	var max;

	if (isData(range) && range.length > 0) {
		var arr = range.split(",");
		min = arr[0];
		max = arr[1];
	}

	//if we returned records, insert them into the form
	if (curform.option) 
	{

		var num = parseInt(curform.option.length / 2);
		if (curform.option.length % 2 != 0) num++;

		//do the first column
		var lc = ce("div");
		setClass(lc,"multiformLeftColumn");

		for (i=0;i<num;i++) {
			lc.appendChild(_pricerangeEntry(curform,i,min,max));
		}

		//do the second column
		var rc = ce("div");
		setClass(rc,"multiformRightColumn");

		for (i=num;i<curform.option.length;i++) {
			rc.appendChild(_pricerangeEntry(curform,i,min,max));
		}

		mydiv.appendChild(lc);
		mydiv.appendChild(rc);
		mydiv.appendChild(createCleaner());

	}

	mydiv.appendChild(createCleaner());

	return mydiv;

}

/****************************************************************
	FUNCTION: _pricerangeEntry
	PURPOSE:  creates a checkbox entry for _checkboxForm
	INPUT:	  curform -> xml data in array form from the
						key -> key in curform we're on
						min -> min selected range
						max -> max in selected range
	RETURNS:	div containing the created div
*****************************************************************/	
function _pricerangeEntry(curform,key,min,max) {

			var div = ce("div");
			setClass(div,"multiformInputCell");

			var cb = createForm("checkbox",curform.name);
			cb.value = curform.option[key].data;

			div.appendChild(cb);
			div.appendChild(ctnode(curform.option[key].title));
			div.appendChild(createCleaner());

			//check it if selected
			if (min && max) {
				if (parseInt(curform.option[key].data)>=parseInt(min) && parseInt(curform.option[key].data)<=parseInt(max)) {
					cb.checked = true;
				}
			}

			//optional actions
			if (curform.disabled) cb.disabled = true;
			if (curform.onclick) setClick(cb,curform.onclick);

			return div;

}


/****************************************************************
	FUNCTION: _ageForm
	PURPOSE:  creates a checkbox form
	INPUT:	  curform -> xml data in array form from the
											 xml config file
	RETURNS:	div containing the created div
*****************************************************************/	

function _ageForm(curform) {

	var mydiv = ce("div");
	setClass(mydiv,"inputCell");

	var i;

	//create header and append to container
	var header = ce("div");
	setClass(header,"multiformHeader");

	if (curform.title) header.appendChild(ctnode(curform.title));
	mydiv.appendChild(header);

	//if we returned records, insert them into the form
	if (curform.option) {

		var num = parseInt(curform.option.length / 2);
		if (curform.option.length % 2 != 0) num++;

		//do the first column
		var lc = ce("div");
		setClass(lc,"multiformLeftColumn");

		var curdata = "";
		if (formdata && isData(formdata[curform.data_node])) curdata = "," + formdata[curform.data_node] + ",";

		for (i=0;i<num;i++) {
			lc.appendChild(_ageEntry(curform,i,curdata));
		}

		//do the second column
		var rc = ce("div");
		setClass(rc,"multiformRightColumn");

		for (i=num;i<curform.option.length;i++) {
			rc.appendChild(_ageEntry(curform,i,curdata));
		}

		mydiv.appendChild(lc);
		mydiv.appendChild(rc);
		mydiv.appendChild(createCleaner());

	}

	mydiv.appendChild(createCleaner());

	return mydiv;

}

/****************************************************************
	FUNCTION: _ageEntry
	PURPOSE:  creates a checkbox entry for _checkboxForm
	INPUT:	  curform -> xml data in array form from the
						key -> key in curform we're on
						curdata -> data string of selected data
	RETURNS:	div containing the created div
*****************************************************************/	
function _ageEntry(curform,key,curdata) {

			var div = ce("div");
			setClass(div,"multiformInputCell");

			//create form and add options
			var sel = createSelect(curform.name + curform.option[key].data);
			for (var c=0;c<=5;c++) sel[c] = new Option(c);

			//find this option in the curdata to see if it's checked
			if (curdata) {
				var opt = curform.option[key].data + ":";
				var pos = curdata.indexOf(opt);
				if (pos!=-1) {
					var val = curdata.substr(pos + opt.length,1);
					sel.value = val;
				}
			}	

			div.appendChild(sel);
			div.appendChild(ctnode(curform.option[key].title));
			div.appendChild(createCleaner());

			return div;

}


/****************************************************************
  FUNCTION: _datedueForm
  PURPOSE:  creates a date due form
  INPUT:    curform -> xml data in array form from the
                       xml config file
  RETURNS:  html object containing the created div
*****************************************************************/  

function _datedueForm(curform) {

  var mydiv = ce("div");
  setClass(mydiv,"inputCell");

  var header = ce("div");
  setClass(header,"formHeader");
  header.appendChild(ctnode(curform.title));

  var curval = "";
	var check = "";
  if (formdata && isData(formdata[curform.data]) && formdata[curform.data].length > 0) {
		curval = formdata[curform.data];
		check = 1;
	}
  else if (isData(curform.defaultval)) {
    if (curform.defaultval=="NOW") {
      var d = new Date();
      curval = (d.getMonth() + 1) + "/" + d.getDate() + "/" + d.getFullYear();
    } else curval = curform.defaultval;
  }

	//create a checkbox to preceed the form.  If not checked, we hide the form
	//if checked, we show the form
	var cb = createCheckbox("due","1",check);
	setClick(cb,"cycleObject('datedueform')");
   
  //create a begin and end form
  var dform = createDateForm(curform,curval);
	dform.setAttribute("id","datedueform");
	if (check==1) dform.style.visibility = "visible";
	else dform.style.visibility = "hidden";

  mydiv.appendChild(header);
	mydiv.appendChild(cb);
  mydiv.appendChild(dform); 
  mydiv.appendChild(createCleaner());
  return mydiv;
  
} 
