<?php

include("config/version.php");

$f = $_GET["f"];
$t = $_GET["t"];

?>

<html>
<head>

<script language="javascript">
function hello(){
  parent.document.title = "<?php echo SITE_TITLE." - ".$t;?>";
  parent.historyFunc("<?php echo $f; ?>");
}
</script>

</head>
<body onload=hello()></body>
</html>

