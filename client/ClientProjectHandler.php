<?php
/**
 * Class and function needed to handle projects on client
 * @package Client
 * @version $Id$
 */

/**
 * Project handler
 */
require_once(CARTOCLIENT_HOME . 'common/ProjectHandler.php');

/**
 * Project handler for the client
 * @package Client
 */
class ClientProjectHandler extends ProjectHandler {

    /**
     * @var Logger
     */
    private $log;

    /**
     * Used for caching the project name.
     * @var string
     */
    private $projectName = false;

    /**
     * Environment variable which contains project name
     */
    const PROJECT_ENV_VAR = 'CW3_PROJECT';

    /**
     * Request name which contains the project name
     */
    const PROJECT_REQUEST = 'project';
    
    /**
     * @var ClientProjectHandler singleton
     */
    static private $instance;
    
    /**
     * Constructor
     */
    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        self::$instance = $this;
    }
    
    static function getInstance() {
        return self::$instance;
    }
    
    /**
     * @see ProjectHandler::getRootPath()
     */
    function getRootPath() {
        return CARTOCLIENT_HOME;
    }
    
    /**
     * Returns project name
     *
     * Tries to find project name in:
     * - GET variable 'project'
     * - Root directory, file current_project.txt
     * - $_ENV, variable CW3_PROJECT
     * - $_SERVER, variable CW3_PROJECT
     * - $_SERVER, variable REDIRECT_CW3_PROJECT (CGI redirect)
     * @return string project name
     */
    function getProjectName() {
        if ($this->projectName === false) {
            $projectFileName = CARTOCLIENT_HOME . 'current_project.txt';
            
            if (array_key_exists(self::PROJECT_REQUEST, $_REQUEST))
                $this->projectName = $_REQUEST[self::PROJECT_REQUEST];

            else if (is_readable($projectFileName))
                $this->projectName = rtrim(file_get_contents($projectFileName));

            else if (array_key_exists(self::PROJECT_ENV_VAR, $_ENV))
                $this->projectName = $_ENV[self::PROJECT_ENV_VAR];

            else if (array_key_exists(self::PROJECT_ENV_VAR, $_SERVER))
                $this->projectName = $_SERVER[self::PROJECT_ENV_VAR];

            else if (array_key_exists('REDIRECT_' . self::PROJECT_ENV_VAR, $_SERVER))
                $this->projectName = $_SERVER['REDIRECT_' . self::PROJECT_ENV_VAR];

            else $this->projectName = NULL;
            
            $this->log->debug("current project is " . $this->projectName);
        }
        return $this->projectName;
    }

}

/**
 * Smarty block function for resources
 *
 * Transforms {r type=css plugin=myplugin}toto.css{/r} to 
 * myplugin/css/toto.css or currentproject/myplugin/css/toto.css .
 * @package Client
 * @param array block parameters
 * @param string block text
 * @param Smarty Smarty engine
 * @return string resource path
 */
function smartyResource($params, $text, &$smarty) {
    
    $text = stripslashes($text);
    
    if (isset($params['type'])) {
        $type = $params['type'];
        unset($params['type']);       
    }
    
    if (isset($params['plugin'])) {
        $plugin = $params['plugin'];
        unset($params['plugin']);        
    }

    if (isset($type)) {
        $text = $type . '/' . $text;
    }   
    if (isset($plugin)) {
        $text = $plugin . '/' . $text;
    }

    $projectHandler = ClientProjectHandler::getInstance();
    if (is_null($projectHandler))
        throw new CartoclientException('Project handler not yet initialized');
    $text = $projectHandler->getWebPath($text);

    return $text;
}

?>
