<?php

$onPageLoad = "document.loginForm.login.focus()";

$siteContent .= "<!--EDEV LOGIN-->\n";
$siteContent .= "<div class=\"login_form\">\n";

//display any errors/warnings/messages
if ($_GET["timeout"] == "true") $siteContent .= "<p class=\"errorMessage\">Your session has timed out</p>\n";
else if ($loginFormError) $siteContent .= "<p class=\"errorMessage\">".$loginFormError."</p>\n";
else if ($show_login_form=="incorrect") $siteContent .= "<p class=\"errorMessage\">Your username and/or password was incorrect.</p>\n";
else if ($show_login_form=="expired") $siteContent .= "<p class=\"errorMessage\">Your session has expired</p>\n";

if (defined ("WARNING_BANNER")) $siteContent .= "<p class=\"errorMessage\">".WARNING_BANNER."</p>";

//display the main login form
$siteContent .= "<h1>Welcome to ".SITE_TITLE."</h1>\n";
$siteContent .= "<form name=\"loginForm\" method=\"post\">\n";
$siteContent .= "<input type=\"hidden\" name=\"module\" value=\"".$module."\">\n";
$siteContent .= "<input type=\"hidden\" name=\"queryString\" id=\"queryString\" value=\"".$queryString."\">\n";
$siteContent .= "<p style=\"margin-left:27px\" class=\"form_input\">Login: <input type=\"text\" id=\"login\" name=\"login\" class=\"input_text\"></p>\n";
$siteContent .= "<p class=\"form_input\">Password: <input type=\"password\" name=\"password\" class=\"input_text\"></p>\n";

//allow the user to save their session info in a cookie, if allowed in the site's config
if (defined("USE_COOKIES")) $siteContent .= "<p class=\"form_input\"><input type=\"checkbox\" name=\"savePassword\" id=\"savePassword\" value=\"yes\">Save my login information</p>\n";

//show the submit button
$siteContent .= "<p class=\"form_submit\"><input type=\"submit\" name=\"submitlogin\" value=\"Login\"></p>\n";

//additional content from the database
if ($loginTextValue) $siteContent .= $loginTextValue;

//allow the user to request a new account, if allowed in the site's config
if (defined("REQUEST_ACCOUNT")) $siteContent .= "<a class=\"login_request_account\" href=\"index.php?module=accountapply\">Apply for a User Name</a></b>";

//close the section out and put the focus on the username textbox
$siteContent .= "</form></div>\n";
