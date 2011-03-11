
function loadGroup(id)
{

	window.opener.location.href = "index.php?module=groupadmin&groupId=" + id;
	self.close();

}

function formCheck() {

	if (document.getElementById("name").value == "") {
		alert("You must enter a name");
		document.getElementById("name").focus();
		return false;
	}

	return true;

}

function refreshParent() {

	//get our recent groupId if there is any
	var groupId = document.pageForm.groupId.value;

	//refresh the parent to show the recently added account
	if (groupId!="") {
		newUrl = "index.php?module=groupadmin&groupId=" + groupId; 
		window.opener.location.href = newUrl;
	}

}

function closeWindow() {

	//show the account in the parent window
	refreshParent();
	
	//close this window
	self.close();
	
}
