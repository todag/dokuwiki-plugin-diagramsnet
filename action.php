<?php
/**
 * DokuWiki Plugin diagramsnet (Action Component)
 * 
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author https://github.com/todag
 *
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) {
	die();
}

class action_plugin_diagramsnet extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler $controller) {
            $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, '_ajax_call');
    }

    /**
      * handle ajax requests
      */
    function _ajax_call(Doku_Event $event, $param) {
        if ($event->data !== 'plugin_diagramsnet') {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        //e.g. access additional request variables
        global $INPUT;
	global $conf;
	$data = $INPUT->str('data');
        $action = $INPUT->str('action');
            
	/**
	 * Check ACL before writing any data.
	 * AUTH_DELETE (int 16) is needed for editing.
	 * According DokuWiki AUTH_UPLOAD (int 8) is enough to upload
	 * a *new* file. This permission level is not supported in
	 * this plugin currently.		
	 */ 
	if ($action == 'save' && auth_quickaclcheck(cleanID($data)) >= AUTH_DELETE) {
	    /**	
	     * Check ACL and make sure namespace has a media directory before trying to save.
	     * 
	     */
	    $content = $INPUT->str('content');
            $base64data = explode(",", $content)[1];	

	    $continue = true;

            // Check that the media directory exists
	    if($continue == true && !file_exists(dirname(mediaFN($data))) && !mkdir(dirname(mediaFN($data)), 0755, true)) {
                $result->message = 'Unable to create namespace media directory!';
                $continue = false;
	    }
            
	    // If attic is enabled, save old revision
	    if($continue == true && $this->getConf('enable_attic') == 1) {	    	    
	        media_saveOldRevision($data);
	    }
            
	    // Open file for writing
	    if($continue == true && !$whandle = fopen(mediaFN($data),'w')) {
		$result->message = 'Unable to open file handle!';
                $continue = false;
	    }

	    // Write data to file
	    if($continue == true && !fwrite($whandle, base64_decode($base64data))) {
	        $result->message = 'Unable to write data to file!';
	    } else {
	        $result->message = 'success';
	    }

	    // Close and finish up
	    fclose($whandle);
	    header('Content-Type: application/json');
            echo json_encode($result);	    

	} elseif ($action == 'save') {
	    $result->message = 'Permission denied!';
            header('Content-Type: application/json');
            echo json_encode($result);
	}

	if($action == 'get' && auth_quickaclcheck(cleanID($data)) >= AUTH_READ) {	    	
	    if(file_exists(mediaFN($data)) && $fc = file_get_contents(mediaFN($data))) {
                // File read successfully
	        $result->message = 'success';
		$result->content = "data:image/png;base64,".base64_encode($fc);		    		    
	    } elseif (file_exists(mediaFN($data)) && !$fc = file_get_contents(mediaFN($data))) {
		// Failed to read existing file    
                $result->message = 'Failed to read data from file!';               
	    } else {	    
		// No existing file, assume user is creating new file    
	        $result->message = 'success';
	        $result->content = "data:image/png;base64,";
	    }

	    // Return result
	    header('Content-Type: application/json');
	    echo json_encode($result); 	    
	} elseif ($action == 'get') {
	    $result->message = 'Permission denied!';
	    header('Content-Type: application/json');
            echo json_encode($result);
	}
    }    
}
?>
