<?php

$onPageLoad = "loadPage()";

$content = "
  <!-- Begin search results -->  
  <div class=\"toolbar\">
    <div class=\"toolbarCell\" onClick=\"location.href = 'index.php?module=editcontact'\">
      <img align=\"left\" src=\"".THEME_PATH."/images/icons/new.png\" border=\"0\"> Add Contact
    </div>
    <div class=\"toolbarCell\" onClick=\"emailContact()\">
      <img align=\"left\" src=\"".THEME_PATH."/images/icons/email.png\" border=\"0\"> Email
    </div>
    <div class=\"toolbarCell\" onClick=\"deleteContact()\">
      <img align=\"left\" src=\"".THEME_PATH."/images/icons/delete.png\" border=\"0\"> Delete
    </div>
    <div class=\"cleaner\">&nbsp;</div>                 
  </div>
  <div id=\"searchResults\">
    <table id=\"searchResultTable\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
    <thead id=\"searchResultHeader\">
    <tr> 
      <th class=\"selectHeaderColumn\">
        <img src=\"".THEME_PATH."/images/icons/checkbox.png\" border=\"0\">
      </th>
      <th class=\"nameHeaderColumn\"> 
        <a href=\"javascript:sortResults('name');\">Name</a>
      </th>
      <th class=\"phoneHeaderColumn\"> 
        Email
      </th>
      <th class=\"phoneHeaderColumn\"> 
        Work Phone
      </th>
      <th class=\"phoneHeaderColumn\"> 
        Home Phone
      </th>
      <th class=\"phoneHeaderColumn\"> 
        Mobile
      </th>
    </tr>
    </thead>
    <tbody id=\"searchResultContent\">
    </tbody>
    </table>
    <!-- spacer to keep results from touching bottom of the page -->
    <br><br>

  <!-- end search result div -->
  </div> 

";

$siteContent = $content;
