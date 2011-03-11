<?php

function buildMenu()
{

  $str = null;

  if (PERM::check(INSERT_OBJECTS))
  {
  
    $str .= "

          <div class=\"toolbarSubRow\" onClick=\"uploadFile()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/letter.png\" border=\"0\"> Upload File
          </div>
          <div class=\"toolbarSubRow\" onClick=\"multiUpload()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/letter.png\" border=\"0\"> Upload Multiple Files
          </div>
          <div class=\"toolbarSubRow\" onClick=\"createFolder()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/folder.png\" border=\"0\"> Create Collection
          </div>
          <div class=\"toolbarSubRow\" onClick=\"openEditor('web')\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/document.png\" border=\"0\"> Create DocMGR Document
          </div>
          ";

    if (BROWSER=="ie" && DSOFRAMER_ENABLE==1) 
    {
      $str .= "
          <div class=\"toolbarSubRow\" onClick=\"openEditor('msoffice')\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/letter.png\" border=\"0\"> Create Office Document
          </div>
          ";
    }

    $str .= "
          <div class=\"toolbarSubRow\" onClick=\"createURL()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/url.png\" border=\"0\"> Add Website
          </div>
          ";

  }
          
  if (defined("USE_TRASH"))
  {

    $str .= "
            <div class=\"toolbarSubRow\" onClick=\"emptyTrash()\">
              <img align=\"left\" src=\"".THEME_PATH."/images/icons/trash.png\" border=\"0\"> Empty Trash
            </div>
            <div class=\"toolbarSubRow\" onClick=\"deleteObjects()\">
              <img align=\"left\" src=\"".THEME_PATH."/images/icons/delete.png\" border=\"0\"> Delete Permanently
            </div>
          ";
          
  }

  if (PERM::check(INSERT_OBJECTS))
  {
  

    $str .= "
          <div class=\"toolbarSubRow\" onClick=\"massConvertWin()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/docmgr/icons/convert.png\" border=\"0\"> Convert Files
          </div>
          <div class=\"toolbarSubRow\" onClick=\"createWorkflow()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/merge.png\" border=\"0\"> Create Workflow
          </div>
          <div class=\"toolbarSubRow\" onClick=\"importObjects()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/import.png\" border=\"0\"> Import Files
          </div>
          <div class=\"toolbarSubRow\" onClick=\"mergePDF()\">
            <img align=\"left\" src=\"".THEME_PATH."/images/icons/merge.png\" border=\"0\"> Merge PDFs
          </div>
          ";
          
  }

  return $str;
  
}

