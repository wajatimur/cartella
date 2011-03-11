<?php

//setup forwarded or replied content so it shows correctly in the browser
function formatEmailContent($content) {

  //remove any html or head tags
  $content = str_ireplace("<html>","",$content);
  $content = str_ireplace("</html>","",$content);
  $content = str_ireplace("<head>","",$content);
  $content = str_ireplace("</head>","",$content);

  //replace body tags with div tags
  $content = str_ireplace("<body","<div",$content);
  $content = str_ireplace("</body","</div",$content);

  return $content;

}
