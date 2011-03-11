<?php

$num = count($modletArr["link_name"]);

for ($i=0;$i<$num;$i++) 
{

  $output = array();
  $output["modlet"] = $modletArr["link_name"][$i];
  $output["link_name"] = $modletArr["link_name"][$i];
  $output["module_name"] = $modletArr["module_name"][$i];
  $output["module_description"] = $modletArr["module_description"][$i];
  $PROTO->add("modlet",$output);

}

$PROTO->output();
