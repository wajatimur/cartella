function loadUtility() {

	var gid = ge("groupId").value;

	if (gid) {
		var url = "index.php?module=groupdashboardadmin&groupId=" + gid + "&saveModule=home";
		window.open(url);
	}

}