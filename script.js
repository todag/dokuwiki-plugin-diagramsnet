// DokuWiki Plugin diagramsnet (JavaScript Component)
// 
// @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
// @author https://github.com/todag
 

var diagramsnetEdit = function(image, diagramsnetUrl , data, anonymize_xml)
{        
    var iframe = document.createElement('iframe');
    iframe.setAttribute('frameborder', '0');
    iframe.setAttribute('class', 'dokudiagramsnet');

    var close = function()
    {
        window.removeEventListener('message', receive);
        document.body.removeChild(iframe);
    };

    var receive = function(evt)
    {	
        if (evt.data.length > 0)
        {
            var msg = JSON.parse(evt.data);

            if (msg.event == 'init')
            {                            
	        // Read from AJAX
                jQuery.post(
                    DOKU_BASE + 'lib/exe/ajax.php',
                    {
                        call: 'plugin_diagramsnet',
                        data: data,			   
                        action: 'get'
                    },
                    function(result)
		    {		        
                        if(result.message == 'success')
		        {
			    iframe.contentWindow.postMessage(JSON.stringify({action: 'load',  autosave: 1, xmlpng: result.content}), '*');
	                }
		        else
		        {
                            alert('An error occured while opening the file.\nError message: ' + result.message);
		            close();
			}
                    }
                );
            }            
	    // Received if the user clicks save. This will send a request to export the diagram
	    else if (msg.event == 'save')
	    {                
		xmlData = msg.xml;
		if(anonymize_xml) {
		    xmlData = xmlData.replace(new RegExp('^<mxfile host=".*?"'), '<mxfile host="hostname"');
		    xmlData = xmlData.replace(new RegExp('agent=".*?"'), 'agent="anonymous browser agent"');
		}   
		iframe.contentWindow.postMessage(JSON.stringify({action: 'export', format: 'xmlpng', xml: xmlData, spin: 'Updating page'}), '*');
	    }
	    // This will capture the export event called above
            else if (msg.event == 'export')
            {
                // Save into dokuwiki
                jQuery.post(
                    DOKU_BASE + 'lib/exe/ajax.php',
                    {
                        call: 'plugin_diagramsnet',
                        data: data,
                        content: msg.data,
                        action: 'save'		            			    
                    },
	            function(result) 
		    {
			// Check if the save was successful, and if so update the img data to immediately reflect changes.
			// If save failed, alert with error message.		        			
			if (result.message == 'success')
			{			    
			    image.setAttribute('src', msg.data);
                            alert('File saved to Wiki successfully!');
			}
			else
			{
			    alert("An error occured while saving the file to the Wiki. You can export and save the file locally.\n Error message: " + result.message);			    
			}		        
		    }
		);
            }                      
            else if (msg.event == 'exit')
            {	
	        close();
            }
        }
    };

    window.addEventListener('message', receive);
    iframe.setAttribute('src', diagramsnetUrl);
    document.body.appendChild(iframe);
};

// Create a simple toolbar button.
if (typeof window.toolbar !== 'undefined') 
{	
    toolbar[toolbar.length] =
    {
        "type":"picker",
        "title": "Add diagrams.net diagram",	
        "icon": "../../plugins/diagramsnet/toolbar_icon.png",
	"list":[{
	 	"type":"format",
		"title":"No alignment",
		"icon":"../../images/media_align_noalign.png",
		"open":"{{",
		"sample": JSINFO['namespace'] + ":filename.diagram.png",
		"close":"|Diagram Title}}"
	}, {
	        "type":"format",
        	"title":"Align left",
	        "icon":"../../images/media_align_left.png",
        	"open":"{{",
		"sample": JSINFO['namespace'] + ":filename.diagram.png",
	        "close":" |Diagram Title}}"
	}, {
		"type":"format",
                "title":"Align right",
                "icon":"../../images/media_align_right.png",
                "open":"{{ ",
		"sample": JSINFO['namespace'] + ":filename.diagram.png",
                "close":"|Diagram Title}}"
	}, {
		"type":"format",
                "title":"Align center",
                "icon":"../../images/media_align_center.png",
                "open":"{{ ",
		"sample": JSINFO['namespace'] + ":filename.diagram.png",
                "close":" |Diagram Title}}"

	}]        
    };
};



