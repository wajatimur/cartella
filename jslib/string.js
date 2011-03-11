
String.prototype.trim = function() {
  return this.replace(/^\s+|\s+$/g,"");
};
String.prototype.ltrim = function() {
  return this.replace(/^\s+/,"");
};
String.prototype.rtrim = function() {
  return this.replace(/\s+$/,"");
};

//this function extracts all numbers from a string
function returnNumbers(str) {

	if (!str) return "";

	var arr = new Array(0,1,2,3,4,5,6,7,8,9);
	var len = str.length;
	var newstr = "";

	for (i=0;i<len;i++) {

		if (arraySearch(str.charAt(i),arr)!=-1 && str.charAt(i)!=" ") newstr += str.charAt(i);

	}

	return parseInt(newstr);
}

//this function extracts all numbers from a string
function returnNumeric(str) {

	if (!str) return "";

	var arr = new Array(".",0,1,2,3,4,5,6,7,8,9);
	var len = str.length;
	var newstr = "";

	for (i=0;i<len;i++) {

		if (arraySearch(str.charAt(i),arr)!=-1 && str.charAt(i)!=" ") newstr += str.charAt(i);

	}

	return newstr;
}

function isNumeric(str) {

	var checkstr = returnNumeric(str);

	if (checkstr==str) return true;
	else return false;

}

//this function extracts all numbers from a string
//need to rewrite using char conversion and ascii codes.
function returnChars(str) {

	if (!str) return "";

	var arr = new Array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
	var len = str.length;
	var newstr = "";

	for (i=0;i<len;i++) {

		if (arraySearch(str.charAt(i),arr)!=-1 && str.charAt(i)!=" ") newstr += str.charAt(i);

	}

	return newstr;
}

//generates a random string
function randomString() {
	var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
	var string_length = 8;
	var rstr = '';
	for (var i=0; i<string_length; i++) {
		var rnum = Math.floor(Math.random() * chars.length);
		rstr += chars.substring(rnum,rnum+1);
	}
	return rstr;
}

//escape backslashes in a string
function escapeBackslash(str) {

				if (str) {
	        var arr = str.split("\\");
	        var newstr = arr.join("\\\\");

	        return newstr;
				} else return "";

}

function str_replace(haystack,needle,newneedle) {

        return haystack.replace(/needle/g, newneedle);

}

function ucfirst(str) {
  str = str.substr(0, 1).toUpperCase() + str.substr(1);
	return str;
}


function dump(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;
	
	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";
	
	if(typeof(arr) == 'object') { //Array/Hashes/Objects 

		for(var item in arr) {

			if (item=="$family") continue;
			
			var value = arr[item];

			if (typeof(value) == "function") continue;

			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}

function str_pad(str,len,padstr,mode) {

	if (mode=="front") {

		var diff = parseInt(len) - str.length;
		for (var i=0;i<diff;i++) str = padstr + str;

	} else {

		var diff = parseInt(len) - str.length;
		for (var i=0;i<diff;i++) str += padstr;

	}

	return str;
	
}

function setCaretPosition(elem, caretPos) {  

    if(elem != null) {
        if(elem.createTextRange) {
            var range = elem.createTextRange();
            range.move('character', caretPos); 
            range.select();
        }
        else {
            if(elem.selectionStart) {
                elem.focus();
                elem.setSelectionRange(caretPos, caretPos);
            }
            else
                elem.focus();
        }
    }
}

function strip_tags(str) {

	var ref = ce("div","","",str);
  return ref.innerText.trim();

}

//convert an xml string into an xml object
function string2XML(xmlstr) {

  if (document.all) {

    var xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
    xmlDoc.async="false";
    xmlDoc.loadXML(xmlstr);

  } else {

    var parser = new DOMParser();
    var xmlDoc = parser.parseFromString(xmlstr,"text/xml")

  }

  //don't return the datanode
  return xmlDoc;

}

String.prototype.reverse = function()
{	
	splitext = this.split("");
	revertext = splitext.reverse();
	reversed = revertext.join("");
	return reversed;
}

String.prototype.isNumeric = function()
{ 

  var numstr = returnNumbers(this);

  if (numstr.length==this.length) return true;
  else return false;
   
}
