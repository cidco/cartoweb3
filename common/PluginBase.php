<?php

abstract class PluginBase {
    private $log;

    private $basePath;
    private $name;

    function __construct() {
        //parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    abstract function initialize($initArgs);
    
    protected function getBasePath() {
        if (!$this->basePath)
            throw new CartoclientException("Base path not defined");
        return $this->basePath;
    }

    function setBasePath($basePath) {
        $this->basePath = $basePath;
    }

    function setName($name) {
        $this->name = $name;
    }

    function getName() {
        return $this->name;
    }
}

?>