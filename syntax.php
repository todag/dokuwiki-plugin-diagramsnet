<?php
/**
 * DokuWiki Plugin diagramsnet (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author https://github.com/todag 
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

class syntax_plugin_diagramsnet extends DokuWiki_Syntax_Plugin
{
    /**
     * @return string Syntax mode type
     */
    public function getType() {
        return 'substition';
    }

    private $fileMatchPattern;
    function __construct() {	   
	    /** 
	     * Get the file suffix from configuration and set in in class variable.
	     * If this is done in connectTo it will poll the settings multiple times, which may or may not be an issue...
	     */
	    global $conf;	  	    	    	    
	    $this->fileMatchPattern = '\{\{[^\}]+?' . str_replace('.', '\.', $this->getConf('file_match_suffix')) . '[^\}]*?\}\}';	    	               
    }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() {
        return 319;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {	    	    	    
	    $this->Lexer->addSpecialPattern($this->fileMatchPattern, $mode, 'plugin_diagramsnet');	  
    }

    /**
     * Handle matches of the diagramsnet plugin syntax
     *
     * @param string       $match   The match of the syntax
     * @param int          $state   The state of the handler
     * @param int          $pos     The position in the document
     * @param Doku_Handler $handler The handler
     *
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {                
        $params = Doku_Handler_Parse_Media($match);	
	return $params;
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string        $mode     Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer $renderer The renderer
     * @param array         $data     The data from the handler() function
     *
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data) {    
	if ($mode !== 'xhtml') {
            return false;
        }	

	global $conf;
        
	/**
	 * Check ACL and show either editor or viewer
	 * 
	 */
	if(auth_quickaclcheck(cleanID($data['src'])) >= AUTH_DELETE) { 
	    // If permissions >= 16 return full editor
	    if($this->getConf('app_source_type') == 'external') {		
		$externalUrl = $this->getConf('external_url').$this->getConf('editor_parameters');
	    } else {	
                $externalUrl = DOKU_URL.'lib/plugins/diagramsnet/lib/'.$this->getConf('editor_parameters');
	    }
	} else { 
	    // If permissions < 16 return viewer
	    if($this->getConf('app_source_type') == 'external'){
                $externalUrl = $this->getConf('external_url').$this->getConf('viewer_parameters');	       
	    } else {
		$externalUrl = DOKU_URL.'lib/plugins/diagramsnet/lib/'.$this->getConf('viewer_parameters');                
	    } 
	}	
	if(auth_quickaclcheck(cleanID($data['src'])) >= AUTH_READ) {	    
	    if(!file_exists(mediaFN($data['src']))) {
	        $data['title'] = 'Click to create new diagram file ['.$data['src'].']';                
	    }
    	     
	    $attr = array(
                'class'   => 'media',
		'id'      => $data['src'],		
		'onclick' => "diagramsnetEdit(this,'$externalUrl','".$data['src']."')",
		'style'   => 'cursor:pointer;',
                'src'     => ml($data['src']),
	    	'width'   => $data['width'],
                'height'  => $data['height'],
            	'align'   => $data['align'],
		'title'   => $data['title']			    	
	    );

            $renderer->doc .= '<img '.buildAttributes($attr).'/>';
	} else {	    
            $renderer->doc .= "<font color='red'>** plugin response: Permission denied for file: '".$data['src']."' **</font>";
	}
        return true;
    }
}
