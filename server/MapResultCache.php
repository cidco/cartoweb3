<?php
/**
 * @package Server
 * @version $Id$
 */

/**
 * Class to manage the caching of the MapResults.
 * 
 * Cache cleaning is done by images cache cleaning script.
 *
 * @package Server
 * @author Yves Bolognini <yves.bolognini@camptocamp.com> 
 */
class MapResultCache {
    private $log;

    private $cartoserver;
    private $mapResultFile;

    function __construct(CartoServer $cartoserver) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->cartoserver = $cartoserver;
    }

    private function getDigest($mapRequest) {
    
        return md5(serialize($mapRequest));
    }

    private function getMapResultFromServer($mapRequest) {

        return $this->cartoserver->cacheGetMap($mapRequest);
    }

    private function getMapResultFile($mapRequest) {
        
        if (!$this->mapResultFile) {
            $this->mapResultFile = $this->cartoserver->getServerContext($mapRequest->mapId)->config->writablePath . 
                            'mapresult_cache/mapResult.' . $this->getDigest($mapRequest);
        }
        return $this->mapResultFile;    
    }

    private function cacheMapResult($mapRequest) {
         
        $mapResult = $this->getMapResultFromServer($mapRequest);
        $mapResultSerialized = serialize($mapResult);        
        $amount = file_put_contents($this->getMapResultFile($mapRequest), $mapResultSerialized);
        if ($amount != strlen($mapResultSerialized)) {
            throw new CartoserverException('could not write mapResult cache');
        }
        return $mapResult;
    }

    private function readMapResult($mapRequest) {

        $mapResultSerialized = file_get_contents($this->getMapResultFile($mapRequest));
        if ($mapResultSerialized === FALSE) {
            throw new CartoserverException('could not read cached mapResult'); 
        }
        return unserialize($mapResultSerialized);
    }

    private function skipCache($mapRequest) {
        return $this->cartoserver->getServerContext($mapRequest->mapId)->config->noMapResultCache;
    }

    public function getMap($mapRequest) {
    
        $mapResultFile = $this->getMapResultFile($mapRequest);
        
        if ($this->skipCache($mapRequest)) {
            $this->log->debug('not caching mapResult, calling server');
            return $this->getMapResultFromServer($mapRequest);
        }

        if (!file_exists($mapResultFile)) {
            $this->log->debug('first call, not caching mapResult, calling server');
            touch($mapResultFile);
            return $this->getMapResultFromServer($mapRequest);
        }
        
        if (filesize($mapResultFile) == 0) {
            $this->log->debug('second call, caching mapResult');            
            return $this->cacheMapResult($mapRequest);   
        }
        
        $this->log->debug('Returning cached mapResult');
        $mapResult = $this->readMapResult($mapRequest);   
        // FIXME: there is no config loaded there, messages are always sent.
        // PERFORMANCE: remove this if too much impact (time + network size)
        if (isset($mapResult->serverMessages) && is_array($mapResult->serverMessages)) {
            $mapResult->serverMessages[] = new Message('mapResult returned from cache', 
                                                       Message::CHANNEL_DEVELOPER);
        }
        return $mapResult;
    }   
}

?>
