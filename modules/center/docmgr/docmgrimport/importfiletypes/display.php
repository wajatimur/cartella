<?php

$xml = createXmlHeader();

for ($i=0;$i<$typeList["count"];$i++) {

  $xml .= "<type>";
  $xml .= tableToXml($typeList[$i]);
  $xml .= "</type>";
  
}

$xml .= createXmlFooter();

die($xml);
