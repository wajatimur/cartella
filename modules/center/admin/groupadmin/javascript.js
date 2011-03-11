
function selectGroup() {

	var id = document.searchForm.groupId.value;
	if (id!="select") location.href = "index.php?module=groupadmin&groupId=" + id;

}

function createGroup() {

  url = "index.php?module=creategroup";
  config = centerParms("300","150") + ",width=300,height=150";
  
  window.open(url,"_blank",config);

}

