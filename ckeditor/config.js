/*
Copyright (c) 2003-2009, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

//define some toolbars
CKEDITOR.config.toolbar_Docmgr = [
  ['Source','-','SpellChecker','Preview'],
    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'Scayt'],
    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
    '/',
    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
    ['Link','Unlink','Anchor'],
    ['CustomImage','Flash','Table','HorizontalRule','Smiley','SpecialChar'],
    '/',
    ['Styles','Format','Font','FontSize'],
    ['TextColor','BGColor'],
    ['Maximize', 'ShowBlocks','-','About']
] ;

CKEDITOR.config.toolbar_Email = [
  ['Source','SpellChecker'],
    ['Cut','Copy','Paste','PasteText','PasteFromWord','-', 'Scayt'],
    ['Undo','Redo','-','SelectAll','RemoveFormat'],
    ['Link','Unlink','Anchor'],
    ['HorizontalRule','Smiley','SpecialChar'],
		['Subscript','Superscript'],
    '/',
    ['Bold','Italic','Underline','Strike']
    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
    ['Styles','Format','Font','FontSize'],
    ['TextColor','BGColor']
] ;

//enable table resizing
CKEDITOR.config.extraPlugins = 'tableresize';   

// Finally, register the dialog.
CKEDITOR.dialog.add( 'CustomImage', 'dialogs/customimage/customimage.js' );

function addDialogs(editor)
{

	// Register the command used to open the dialog.
	editor.addCommand( 'customImageCmd', new CKEDITOR.dialogCommand( 'CustomImage' ) );

	// Add the a custom toolbar buttons, which fires the above
	// command..
	editor.ui.addButton( 'CustomImage',
		{
			label : 'Image',	 
			command : 'customImageCmd',
			icon: 'dialogs/customimage/image.png'
		} );

}

