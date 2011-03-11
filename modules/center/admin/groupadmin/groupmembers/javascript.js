
function changeFilter() {

	var url = "index.php?module=groupadmin&includeModule=groupmembers&filter=" + ge("filter").value;
	location.href = url;

}