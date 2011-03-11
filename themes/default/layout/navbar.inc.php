<?php
/************************************************************************************

	Create our links for the main Navigation Bar

************************************************************************************/

//always show the tab matching with the current module's top level parent
$modTabs = showModTabs("modules/center/",getTopLevelParent($module));

//show our navigation history as we go through the levels
$navHistory = modTreeMenu($module);

