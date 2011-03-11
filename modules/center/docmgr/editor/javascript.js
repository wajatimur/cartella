
var objtype;
var cureditor;
var EDITOR;
var curobj;
var objtype;
var objpath;
var objname;
var parentpath;
var remotesaved;
var readonly;
var ceiling;
var savecontent = "";
var mbmode;
var timer;

function loadPage()
{

	setTimeout("runLoadPage()","10");

}

function runLoadPage()
{

	objtype = ge("objectType").value;
  curobj = ge("objectId").value;
  objtype = ge("objectType").value;
  objpath = ge("objectPath").value;
  objname = ge("objectName").value;
  parentpath = ge("parentPath").value;
	cureditor = ge("editor").value;

	//if we have an object, try to get the editor from that
	if (curobj)
	{

		cureditor = getEditorType(objtype,fileExtension(objname));		

	}

	//fallback checking
	if (cureditor=="dsoframer" && (!document.all || DSOFRAMER_ENABLE!='1' || SETTING["editor"]!="msoffice"))
	{
		cureditor = "dmeditor";
	}

	loadEditor();
	loadToolbar();

}

function loadEditor()
{

	if (cureditor=="dsoframer")
		EDITOR = new DSOFRAMER();				//msoffice dsoframer
	else if (cureditor=="text")
		EDITOR = new TEXTEDIT();						//textarea box
	else 
		EDITOR = new DMEDITOR();				//ckeditor
			
	EDITOR.load();

}

function loadToolbar()
{

	var tb = ge("editorToolbar");

	tb.appendChild(ce("div","toolbarTitle","readonlyCell"));


	//setup the main dropdown
	var mydiv = siteToolbarCell("Document","","letter.png");

	if (cureditor=="dsoframer")
	{
		//we need the special setup for 
		setMouseEnter(mydiv,"EDITOR.setupDD(event)");
		setMouseLeave(mydiv,"EDITOR.hideDD(event)");
	}

	var sub = ce("div","toolbarSub","documentSub");
	sub.appendChild(subToolbarRow("Create New Document","new.png","createNew()"));

	if (cureditor=="dsoframer") sub.appendChild(subToolbarRow("Open Local File","letter.png","openLocalFile()"));

	sub.appendChild(subToolbarRow("Open DocMGR File","document.png","openServerObject()"));

	if (cureditor=="dsoframer") sub.appendChild(subToolbarRow("Save To Computer","save.png","saveLocalFile()"));

	sub.appendChild(subToolbarRow("Save To DocMGR","save.png","saveServer()"));
	sub.appendChild(subToolbarRow("Save To DocMGR With Notes","save.png","saveWithNotes()"));
	sub.appendChild(subToolbarRow("Save Copy To DocMGR","save.png","saveServerCopy()"));

	if (cureditor=="dsoframer") sub.appendChild(subToolbarRow("Print","print.png","printDocument()"));
	sub.appendChild(subToolbarRow("Print Preview","print.png","printPreview()"));
	if (cureditor=="dsoframer") sub.appendChild(subToolbarRow("Change Page Layout","print.png","pageLayout()"));

	//adjust margins for ie
	if (document.all)
	{
		sub.style.marginLeft = "-90px";
		sub.style.marginTop = "18px";
	}

	mydiv.appendChild(sub);
	
	tb.appendChild(mydiv);
	
}

function subToolbarRow(txt,img,func)
{

	var div = ce("div","toolbarSubRow");

	if (cureditor=="dsoframer")
	{
		div.setAttribute("onMouseEnter","setRow()");
		div.setAttribute("onMouseLeave","unsetRow()");
	}

	setClick(div,func);

	var img = createImg(THEME_PATH + "/images/icons/" + img);
	img.setAttribute("align","left");
	div.appendChild(img);

	div.appendChild(ctnode(txt));

	return div;

}


/*

function mergeTB($dso = null) {

	if (!defined("TEA_ENABLE")) return false;
	
	if ($dso) {
		$entry = "onMouseEnter=\"setupDD('mergeSub')\" onMouseLeave=\"hideDD('mergeSub')\"";
		$subentry = "onMouseEnter=\"setRow()\" onMouseLeave=\"unsetRow()\"";
	} 
	else {
		$entry = null;
		$subentry = null;
	}
	
	$str = file_get_contents("config/mergefields.xml");
	$arr = parseGenericXml("merge",$str);
	
	$merge = "<td class=\"toolbarCell\" ".$entry.">
	          <div class=\"toolbarBtn\"><img src=\"".THEME_PATH."/images/icons/letter.png\"> Insert Merge Field</div>
            <div class=\"toolbarSub\" id=\"mergeSub\">
	";
	
	$num = count($arr["name"]);
	
	for ($i=0;$i<$num;$i++) {

	  $name = $arr["name"][$i];
	  $val = $arr["value"][$i];
	  $merge .= "<div class=\"toolbarSubRow\" ".$subentry." onClick=\"insertMergeField('".$val."');\">".$name."</div>\n";

	} 

	$merge .= "</div>\n</td>\n";
	
	return $merge;
	
}
*/

