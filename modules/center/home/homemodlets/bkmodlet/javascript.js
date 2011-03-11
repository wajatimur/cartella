
function loadBookmarks() 
{
	
	//setup the xml
	var p = new PROTO();
	p.add("command","docmgr_bookmark_get");
	p.post(DOCMGR_API,"writeBKResults");

}
 
function writeBKResults(data) 
{

	bmdiv = ge("bkList");

	clearElement(bmdiv);

	if (data.error) alert(data.error);
	else if (data.bookmark) 
	{

		for (var i=0;i<data.bookmark.length;i++) 
		{

			var cont = ce("div","bookmark");

			var img = ce("img");
			img.setAttribute("src",theme_path + "/images/closed_folder.png");

			cont.appendChild(img);
			var link = ce("a","","",data.bookmark[i].name);
			link.setAttribute("href","index.php?module=docmgr&objectPath=" + data.bookmark[i].object_path);
			cont.appendChild(link);

			img.style.marginRight = "3px";
			img.style.paddingLeft = "13px";

			bmdiv.appendChild(cont);

		}

	}

}


