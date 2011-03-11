
/******************************************************************
  CLASS:  PROTO 
  PURPOSE:  wrapper for handling AJAX requests.  Can handle all
            the encoding and decoding of requests and responses.
            Also allows us to swap for a different javascript
            request library later
  INPUTS:   reqmode => "POST" or "GET".  mode to send request as
******************************************************************/

var XML = new function()
{

  /****************************************************************
    FUNCTION: encode
    PURPOSE:  converts an array to an xml formatted string
    INPUTS:   arr -> array to convert
  ****************************************************************/
	this.encode = function(arr)
	{

		var xml = "";

		for (var key in arr)
		{

			if (typeof(arr[key])=="object")
			{

				//this will only work for arrays (not objects) which is what we want
				for (var i=0;i<arr[key].length;i++)
				{

					//add regular string entry
					if (typeof(arr[key][i])=="string")
					{

						xml += this.entry(key,arr[key][i]);

					//add sub array
					} else 
					{

						xml += "<" + key + ">\n";
						xml += this.encode(arr[key][i]);
						xml += "</" + key + ">\n";

					}
				
				}

			} else if (typeof(arr[key])=="string")
			{

				xml += this.entry(key,arr[key]);

			}



		}

		return xml;

	};

  /****************************************************************
    FUNCTION: decode
    PURPOSE:  converts an xml formatted string to an array
    INPUTS:   str -> string to convert.  Must be encased in a root
										node, like "<data></data>"
  ****************************************************************/
	this.decode = function(dataNode)
	{

		if (!dataNode || !dataNode.childNodes) 
		{
			return false;
		}

		var len = dataNode.childNodes.length;
		var arr = new Array();
		var keyarr = new Array();
	
		var n=0;
		var i = 0;
	
		while (dataNode.childNodes[i]) 
		{
	
			var objNode = dataNode.childNodes[i];
	
			if (objNode.nodeType==1) {
	
				var keyname = objNode.nodeName;
	
				if (objNode.hasChildNodes()) 
				{
	
					//if the key does not exist in our key array, added it and reset its counter
					if (!keyarr[keyname]) 
					{
						keyarr[keyname] = 0;
						arr[keyname] = new Array();
					}
	
					n = keyarr[keyname];
	
					arr[keyname][n] = new Array();
	
					//store single length nodes here
					if (!this.hasChildNodes(objNode)) 
					{
		
						//if already exists, convert to an array and add new entry
						if (isData(arr[keyname])) 
						{
	
							if (typeof(arr[keyname])!="object") arr[keyname] = new Array(arr[keyname]);
							arr[keyname].push(objNode.firstChild.nodeValue);
	
						//just store regular entry
						} else 
						{
							 arr[keyname] = objNode.firstChild.nodeValue;
						}
	
					} else 
					{
	
						var c = 0;
						while (objNode.childNodes[c]) 
						{
	
							var curNode = objNode.childNodes[c];
							var curName = curNode.nodeName;
	
							//only continue on nodes that are elements
	 						if (curNode.nodeType==1) 
							{
	
								//there are nested tags here, get them
								if (this.hasChildNodes(curNode)) 
								{
	
									//what will our next iteration be
									if (!arr[keyname][n][curName]) arr[keyname][n][curName] = new Array();
	
									//add children to our new parent
									if (curNode.childNodes.length > 0) arr[keyname][n][curName].push(this.decode(curNode,curName));
			
								//just store as text
								} else 
								{
	
									//if already exists, convert to an array and store the new entry
									if (isData(arr[keyname][n][curName])) 
									{
	
										if (typeof(arr[keyname][n][curName])!="object") arr[keyname][n][curName] = new Array(arr[keyname][n][curName]);
										arr[keyname][n][curName].push(curNode.firstChild.nodeValue);
	
									//just store as single text
									} else arr[keyname][n][curName] = curNode.firstChild.nodeValue;
	
								}
	
							}
	
							c++;
	
						}
	
					}
	
					keyarr[keyname]++;
	
				}
	
			}
	
			i++;
	
		}
	
		return arr;
	
		
	};		
  /****************************************************************
    FUNCTION: entry
    PURPOSE:  converts key/value pair to a CDATA-wrapped
							xml entry
    INPUTS:   elementType -> key
							txt -> value
  ****************************************************************/
	this.entry = function(elementType,txt) 
	{
	 
  	var xml = "<" + elementType + "><![CDATA[" + escape(txt) + "]]></" + elementType + ">";
  	return xml
	
	};	

  /****************************************************************
    FUNCTION: hasChildNodes
		PURPOSE:	checks to see if the element has a text value, or if 
							there are children below it to go through
    INPUTS:   obj -> object to check
  ****************************************************************/
	this.hasChildNodes = function(obj) 
	{

		if (!obj) return false;

		if (document.all) 
		{

			if (obj.firstChild && obj.firstChild.nodeValue) return false;
			else return true;

		} else 
		{

			if (obj.childNodes.length==1) return false;
			else return true;

		}

	};
	
}
		
