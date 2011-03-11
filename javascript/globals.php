<?php

echo "

<script type=\"text/javascript\">
var SETTING = new Array();
SETTING[\"editor\"] = \"".$_SESSION["accountSettings"]["editor"]."\";
var tmp_dir = \"".TMP_DIR."\";
var TMP_DIR = \"".TMP_DIR."\";
var FILE_DIR = \"".FILE_DIR."\";
var HOME_DIR = \"".HOME_DIR."\";
var SITE_PATH = \"".SITE_PATH."\";
var site_theme = \"".SITE_THEME."\";
var theme_path = \"".THEME_PATH."\";
var site_url = \"".SITE_URL."\";
var THEME_PATH = \"".THEME_PATH."\";
var SITE_URL = \"".SITE_URL."\";
var DOCMGR_API = \"".DOCMGR_API."\";
var DOCMGR_URL = \"".DOCMGR_URL."\";
var USER_ID = \"".USER_ID."\";
var USER_FN = \"".USER_FN."\";
var USER_LN = \"".USER_LN."\";
var USER_EMAIL = \"".USER_EMAIL."\";
var USER_LOGIN = \"".USER_LOGIN."\";
var USER_PASSWORD = \"".USER_PASSWORD."\";
var BITSET = \"".BITSET."\";
var TOPMODULE = \"".getTopLevelParent($module)."\";
var MODULE = \"".$module."\";
var DOCMGR_KEEPALIVE = \"".DOCMGR_KEEPALIVE."\";
var DOCMGR_AUTHORIZE = \"".$_SESSION["api"]["authorize"]."\";
var RESULTS_PER_PAGE = \"".RESULTS_PER_PAGE."\";
var PAGE_RESULT_LIMIT = \"".PAGE_RESULT_LIMIT."\";
var BROWSE_PAGINATE = \"".BROWSE_PAGINATE."\";
var SESSION_ID = \"".session_id()."\";
var BROWSE_CEILING = \"".$_SESSION["browseCeiling"]."\";
var DSOFRAMER_ENABLE = \"".DSOFRAMER_ENABLE."\";
var TEA_ENABLE = \"".TEA_ENABLE."\";
var IMPORT_DIR = \"".IMPORT_DIR."\";
var PROTO_DEFAULT = \"".PROTO_DEFAULT."\";
var ROOT_NAME = \"".ROOT_NAME."\";
var USE_LDAP = \"".USE_LDAP."\";
var USE_TRASH = \"".USE_TRASH."\";
var DMEDITOR_DEFAULT_SAVE = \"".DMEDITOR_DEFAULT_SAVE."\";
";

$str = file_get_contents("config/permissions.xml");
$arr = XML::decode($str);
  
for ($i=0;$i<count($arr["perm"]);$i++) 
{
  echo "var ".$arr["perm"][$i]["define_name"]." = \"".$arr["perm"][$i]["bitpos"]."\";\n";
}
     
echo "
</script>
";
