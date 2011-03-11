<?php

$objectPath = stripsan($_REQUEST["objectPath"]);

if ($objectPath=="/" || !$objectPath) {

  $objectType = "collection";

} else {

  $d = new DOCMGR_OBJECT($objectPath);
  $objinfo = $d->getInfo();
  $objectType = $objinfo["object_type"];

}

