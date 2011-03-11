/***********************************************
	file for editing object properties
***********************************************/

var reptopicbtn;
var popupwin;
var curtopic;
var edittopicid;
var editTopicContent;
var curthread;

var topiclist;
var threadlist;

function loadDiscussion() {

	var cont = ge("content");
	clearElement(cont);

	//topic list div
	var topiclistdiv = ce("div","topicList");
	topiclistdiv.appendChild(ce("div","topicContHeader","","Available Topics"));

	topiclist = ce("div","topicContData");
	topiclistdiv.appendChild(topiclist);

	//thread list
	threadlist = ce("div","threadList");

	cont.appendChild(topiclistdiv);
	cont.appendChild(threadlist);
	cont.appendChild(createCleaner());	

	clearSiteStatus();

	loadDiscussToolbar();
	loadTopicList();

}


function loadDiscussToolbar() {

  clearElement(tbBtns);
  clearElement(tbTitle);

  //setup our buttons
  tbBtns.appendChild(siteToolbarCell("Post New Topic","newTopic()","new.png"));

	if (curtopic)
	{

		reptopicbtn = siteToolbarCell("Reply To Topic","replyTopic()","save.png");
		deltopicbtn = siteToolbarCell("Delete Topic","deleteTopic()","delete.png");

		tbBtns.appendChild(reptopicbtn);
		tbBtns.appendChild(deltopicbtn);

	}

}


function loadTopicList() {

  updateSiteStatus("Loading Discussion Topics");

  //load our logs
	var p = new PROTO();
  p.add("command","docmgr_discussion_getlist");
  p.add("object_id",object);
	p.post(DOCMGR_API,"writeTopicList");

}

function writeTopicList(data) {
	
	clearSiteStatus();
	clearElement(topiclist);

	//make our container for holding the topic list

	 
	if (data.error) alert(data.error);
	else if (!data.topic) topiclist.appendChild(ce("div","errorMessage","","No topics found for object"));
	else {

		for (var i=0;i<data.topic.length;i++) {

			var t = data.topic[i];

			var mydiv = ce("div","topicRow");

			var name = ce("div","topicHeader");
			var link = ce("a","","",t.header);
			link.setAttribute("href","javascript:viewTopic('" + t.id + "')");

			name.appendChild(link);
			mydiv.appendChild(name);

			var topstat = ce("div","topicStats");
			mydiv.appendChild(topstat);
			topstat.appendChild(ce("div","","","Created: " + t.time_stamp_view));
			topstat.appendChild(ce("div","","","Created By: " + t.account_name));
			if (isData(t.reply_time_stamp)) topstat.appendChild(ce("div","","","Last Reply: " + t.reply_time_stamp_view));

			topiclist.appendChild(mydiv);

		}

	}


}


function viewTopic(id) {

	curtopic = id;
	loadDiscussToolbar();

  updateSiteStatus("Loading Discussion Topics");

  //load our logs
	var p = new PROTO();
  p.add("command","docmgr_discussion_getthread");
  p.add("object_id",object);
	p.add("topic_id",id);
	p.post(DOCMGR_API,"writeTopicThread");

}

function writeTopicThread(data) {

	clearSiteStatus();
	clearElement(threadlist);

	 
	if (data.error) alert(data.error);
	else if (!data.topic) threadlist.appendChild(ce("div","errorMessage","","No responses found for topic"));
	else {

		//store for later
		curthread = data;
		var tbl = createTable("threadTable","","100%");
		var tbd = ce("tbody");
		tbl.appendChild(tbd);
		threadlist.appendChild(tbl);

		//setup the header
		var row = ce("tr");
		row.appendChild(ce("td","threadHeader","","Author"));
		row.appendChild(ce("td","threadHeader","","Topic: " + data.thread_name));
		tbd.appendChild(row);

		for (var i=0;i<data.topic.length;i++) {

			var t = data.topic[i];

			if (i%2==0) var cn = "threadRowOne";
			else var cn = "threadRowTwo";

			var row = ce("tr",cn);
			var row2 = ce("tr",cn);

			//author information
			if (document.all) var author = ce("<td rowspan=\"2\ class=\"authorCell\" valign=\"top\">","","",t.account_name);
			else {
				var author = ce("td","authorCell","",t.account_name);
				author.setAttribute("rowspan","2");
				author.setAttribute("valign","top");
			}

			//post date and content
			var post = ce("td","postCell");

			post.appendChild(ctnode("Posted: " + t.time_stamp_view));
			post.appendChild(ce("hr"));

			var con = ce("div");
			con.innerHTML = t.content;
			post.appendChild(con);


			post.appendChild(ce("br"));
			post.appendChild(ce("br"));

			//post actions
			var actions = ce("td","threadActions");

			if (t.account_id==USER_ID) 
			{
				var editlink = ce("a","","","[Edit Post]");
				editlink.setAttribute("href","javascript:editPost('" + t.id + "')");	
				actions.appendChild(editlink);
			}

			if (t.account_id==USER_ID || perm_check(ADMIN)) 
			{
				var dellink = ce("a","","","[Delete Post]");
				dellink.setAttribute("href","javascript:deletePost('" + t.id + "')");	
				actions.appendChild(dellink);
			}
			
			row.appendChild(author);
			row.appendChild(post);
			row2.appendChild(actions);

			tbd.appendChild(row);
			tbd.appendChild(row2);

		}

	}


}

function newTopic() {

	popupwin = openSitePopup(600,400);

	var cell = ce("div","topicCell");
	cell.appendChild(ce("div","formHeader","","Subject"));
	cell.appendChild(createTextbox("message_subject"));
	popupwin.appendChild(cell);		

	var cell = ce("div","topicCell");
	cell.appendChild(ce("div","formHeader","","Message"));
	cell.appendChild(createTextarea("editor_content"));
	popupwin.appendChild(cell);		

	var cell = ce("div","topicCell");
	cell.appendChild(createBtn("submit","Post New Topic","postNewTopic()"));
	popupwin.appendChild(cell);		

	loadEditor();

}

function postNewTopic() {

	updateSiteStatus("Posting new topic");

	CKEDITOR.instances.editor_content.updateElement();

  //load our logs
	var p = new PROTO();
  p.add("command","docmgr_discussion_newtopic");
  p.add("object_id",object);
	p.addDOM(popupwin);

	p.post(DOCMGR_API,"writeNewTopic");

}

function writeNewTopic(data) {
	 
	clearSiteStatus();

	if (data.error) alert(data.error);
	else {
		closeSitePopup();
		loadTopicList();
	}

}

function replyTopic() {

	popupwin = openSitePopup(600,350);

	var cell = ce("div","topicCell");
	cell.appendChild(ce("div","formHeader","","Message"));
	cell.appendChild(createTextarea("editor_content"));
	popupwin.appendChild(cell);		

	var cell = ce("div","topicCell");
	cell.appendChild(createBtn("submit","Post Reply","postReplyTopic()"));
	popupwin.appendChild(cell);		

	loadEditor();

}

function postReplyTopic() {

	updateSiteStatus("Posting reply to topic");

	CKEDITOR.instances.editor_content.updateElement();

  //load our logs
	var p = new PROTO();
  p.add("command","docmgr_discussion_reply");
  p.add("object_id",object);
	p.add("topic_id",curtopic);
	p.addDOM(popupwin);
	p.post(DOCMGR_API,"writeReloadTopic");

}

function writeReloadTopic(data) {
	 
	clearSiteStatus();

	if (data.error) alert(data.error);
	else {

		closeSitePopup();

		if (curtopic) viewTopic(curtopic);
		else 
		{
			loadTopicList();
			clearElement(threadlist);
		}

	}

}

function deletePost(id) {

	if (confirm("Are you sure you want to remove this post?")) {

		updateSiteStatus("Removing post");

	  //load our logs
		var p = new PROTO();
	  p.add("command","docmgr_discussion_delete");
		p.add("topic_id",id);
		p.post(DOCMGR_API,"writeReloadTopic");

	}

}

function deleteTopic() {

	deletePost(curtopic);
	curtopic = "";

}

function editPost(id) {

	popupwin = openSitePopup(600,350);
	edittopicid = id;

	//get the current value
	var curval = "";
	for (var i=0;i<curthread.topic.length;i++) {
		if (curthread.topic[i].id==id) {
			curval = curthread.topic[i].content;
			break;
		}
	}

	var cell = ce("div","topicCell");
	cell.appendChild(ce("div","formHeader","","Message"));
	cell.appendChild(createTextarea("editor_content",curval));
	popupwin.appendChild(cell);

	var cell = ce("div","topicCell");
	cell.appendChild(createBtn("submit","Post Reply","postEditTopic()"));
	popupwin.appendChild(cell);		

	loadEditor();

}

function postEditTopic() {

	updateSiteStatus("Posting edit to topic");

	CKEDITOR.instances.editor_content.updateElement();

  //load our logs
	var p = new PROTO();
  p.add("command","docmgr_discussion_edit");
  p.add("topic_id",edittopicid);
	p.addDOM(popupwin);
	p.post(DOCMGR_API,"writeReloadTopic");

}

/*******************************************************************
  FUNCTION: loadEditor
  PURPOSE:  load the actual editor.  
  INPUTS:   curval -> html we'll populate the editor with
*******************************************************************/
function loadEditor(ed) {

	var ed = ge("editor_content");

	if (CKEDITOR.instances.editor_content) 
	{
		CKEDITOR.remove(CKEDITOR.instances.editor_content);
	}
	
	//create a new one
	var f = CKEDITOR.replace(ed);
	CKEDITOR.config.toolbar = 'Basic';

}
	