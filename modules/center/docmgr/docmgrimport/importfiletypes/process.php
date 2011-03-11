<?php

if ($_REQUEST["action"]=="listcontract") {

  $sql = "SELECT * FROM config.contract_file_type ORDER BY name";
  $typeList = $DB->fetch($sql);
  
} else if ($_REQUEST["action"]=="listmembership") {

  $sql = "SELECT * FROM config.membership_file_type ORDER BY name";
  $typeList = $DB->fetch($sql);

} else if ($_REQUEST["action"]=="listcompany") {

  $sql = "SELECT * FROM config.ewp_company ORDER BY name";
  $typeList = $DB->fetch($sql);

}
