<?php
$modJs .= "modules/center/docmgr/js/browse.js;";
$modJs .= "modules/center/docmgr/js/convert.js;";
$modJs .= "modules/center/docmgr/js/upload.js;";
$modJs .= "modules/center/docmgr/js/search.js;";
$modJs .= "modules/center/docmgr/js/pager.js;";
$modJs .= "modules/center/docmgr/js/tree.js;";
$modJs .= "modules/center/docmgr/js/pdf.js;";
$modJs .= "modules/center/docmgr/js/share.js;";
$modJs .= "modules/center/docmgr/js/tags.js;";
$modJs .= "modules/center/docmgr/js/managebookmarks.js;";
$modJs .= "modules/center/docmgr/js/subscriptions.js;";

$modCss .= "modules/center/docmgr/css/browse.css;";
$modCss .= "modules/center/docmgr/css/tag.css;";
$modCss .= "modules/center/docmgr/css/pdf.css;";
$modCss .= "modules/center/docmgr/css/share.css;";
$modCss .= "modules/center/docmgr/css/search.css;";
$modCss .= "modules/center/docmgr/css/managebookmarks.css;";
$modCss .= THEME_PATH."/css/toolbar.css;";

$onPageLoad = "loadPage();";

if (defined("USE_TRASH")) 
{
  $delFunc = "trashObjects()";
  $delIcon = "trash.png";
}
else 
{
  $delFunc = "deleteObjects()";
  $delIcon = "delete.png";
}

$content = "

  <form name=\"pageForm\" method=\"post\" enctype=\"multipart/form-data\" action=\"\">
  <input type=\"hidden\" name=\"sessId\" id=\"sessId\" value=\"".$_SESSION["storedata"]["docmgr"]."\">
  <input type=\"hidden\" name=\"ceilingPath\" id=\"ceilingPath\" value=\"".$_SESSION["browseCeiling"]."\">
  <input type=\"hidden\" name=\"objectPath\" id=\"objectPath\" value=\"".$_REQUEST["objectPath"]."\">
  
  <div id=\"uploadContainer\" style=\"display:none\"></div>
  <div id=\"popupContainer\"></div>
  <div id=\"browseContainer\">
    <div id=\"toolbar\" class=\"toolbar\">

";

if (!PERM::check(GUEST_ACCOUNT,1))
{

  $content .= "
      <div class=\"toolbarCell\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/action.png\" border=\"0\"> Action
        <div class=\"toolbarSub\" id=\"actionSub\">
          ".buildMenu()."
        </div>
      </div>
      ";
      
}

$content .= "
      <div class=\"toolbarCell\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/share.png\" border=\"0\"> Share
        <div class=\"toolbarSub\" id=\"shareSub\">
          <div class=\"toolbarSubRow\" onClick=\"shareObjects()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/share.png\" border=\"0\"> Sharing Settings
          </div>
          <div class=\"toolbarSubRow\" onClick=\"emailObjects()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/email.png\" border=\"0\"> Email As Attachment
          </div>
          <div class=\"toolbarSubRow\" onClick=\"createViewLink()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/url.png\" border=\"0\"> Email Time-Limited View Link 
          </div>
          <div class=\"toolbarSubRow\" onClick=\"createPropLink()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/email.png\" border=\"0\"> Email DocMGR Link 
          </div>
          <div class=\"toolbarSubRow\" onClick=\"showSharedObjects()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/share.png\" border=\"0\"> Show All Shared Objects
          </div>
        </div>
      </div>
      ";
      
if (!PERM::check(GUEST_ACCOUNT,1))
{

  $content .= "
      <div class=\"toolbarCell\" onClick=\"moveObjects()\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/move.png\" border=\"0\"> Move
      </div>
      <div class=\"toolbarCell\" onClick=\"".$delFunc."\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/".$delIcon."\" border=\"0\"> Delete
      </div>
      <div class=\"toolbarDivider\">|</div>
    ";
    
}

$content .= "
      <div class=\"toolbarCell\" onClick=\"changeView('list')\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/listview.png\" border=\"0\"> List
      </div>
      <div class=\"toolbarCell\" onClick=\"changeView('thumb')\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/thumbview.png\" border=\"0\"> Thumbnails
      </div>
      <div class=\"toolbarDivider\">|</div>
      <div class=\"toolbarCell\" onClick=\"cycleSearchView()\" id=\"searchBtn\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/search-bar.png\" border=\"0\">
        Search
      </div>
      <div class=\"toolbarCell\" onClick=\"keywordView()\" id=\"keywordShow\" style=\"display:block\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/search-bar.png\" border=\"0\">
        Keywords
      </div>
      <div class=\"toolbarCell\" onClick=\"manageBookmarks()\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/bookmark.png\" border=\"0\">
        Bookmarks
      </div>
      <!--
      <div class=\"toolbarCell\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/tag.png\" border=\"0\"> Tags
        <div class=\"toolbarSub\" id=\"tagSub\">
        <div class=\"toolbarSubRow\" onClick=\"applyTags()\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/edit.png\" border=\"0\"> Apply/Remove Tag For Object
        </div>
        <div class=\"toolbarSubRow\" onClick=\"manageTags();\">
        <img align=\"left\" src=\"".THEME_PATH."/images/icons/letter.png\" border=\"0\"> Manage Tags
        </div>
      </div>
      -->

    </div>

      <div class=\"cleaner\">&nbsp;</div>
    </div>
    <div id=\"searchFilters\" style=\"display:none\"></div>
    <div class=\"cleaner\"></div>
    <div id=\"browsePager\"></div>
    <div id=\"browseContent\"></div>
  </form>
  <iframe id=\"uploadframe\" name=\"uploadframe\" style=\"display:none;width:200px;height:200px\"></iframe>
";

$siteContent = $content;
