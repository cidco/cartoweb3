<?
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * @package Plugins
 */
class ServerOutline extends ServerPlugin {
    private $log;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function getType() {
        return ServerPlugin::TYPE_DRAWING;
    }

    private function getLayer($msMapObj, $layerName) {
        
        $outlineLayer = @$msMapObj->getLayerByName($layerName);
        if (!$outlineLayer) {
            if ($layerName) {
                throw new CartoserverException('Outline layer ' . $layerName . 
                                               ' is not defined in mapfile');
            } else {
                throw new CartoserverException('No outline layer defined in config file');
            }
        }
        return $outlineLayer;
    }

    private function drawPoint($msMapObj, $point) {

        $layerName = $this->getConfig()->pointLayer;
        if (!$layerName) {
            $layerName = $this->getConfig()->polygonLayer;
        }
        
        $outlineLayer = $this->getLayer($msMapObj, $layerName);
        $class = $outlineLayer->getClass(0);

        $line = ms_newLineObj();

        $line->addXY($point->x, $point->y);

        $p = ms_newShapeObj(MS_SHAPE_POLYGON);
        $p->add($line);

        $outlineLayer->set('status', MS_ON);
        $class->set('status', MS_ON);
        
        $outlineLayer->addFeature($p);
        
    }

    private function drawRectangle($msMapObj, $rectangle, $maskMode) {

        $points = array();       
        $points[] = new Point($rectangle->minx, $rectangle->miny);
        $points[] = new Point($rectangle->minx, $rectangle->maxy);
        $points[] = new Point($rectangle->maxx, $rectangle->maxy);
        $points[] = new Point($rectangle->maxx, $rectangle->miny);

        $polygon = new Polygon();
        $polygon->points = $points;
        
        $this->drawPolygon($msMapObj, $polygon, $maskMode);
    }

    private function convertPolygon($polygon) {
        $line = ms_newLineObj();

        foreach ($polygon->points as $point) {
            $line->addXY($point->x, $point->y);
        }
        $line->addXY($polygon->points[0]->x, $polygon->points[0]->y);
    
        $p = ms_newShapeObj(MS_SHAPE_POLYGON);
        $p->add($line);
        
        return $p;
    }

    private function drawPolygon($msMapObj, $polygon, $maskMode) {

        if (!$maskMode) { 
            $outlineLayer = $this->getLayer($msMapObj, $this->getConfig()->polygonLayer);
            $class = $outlineLayer->getClass(0);

            $outlineLayer->set('status', MS_ON);
            $class->set('status', MS_ON);
        
            $outlineLayer->addFeature($this->convertPolygon($polygon));            
        } else {
        
            $image2 = $msMapObj->prepareimage();
            $rectangle = ms_newRectObj();
            $rectangle->setExtent($this->serverContext->maxExtent->minx,
                                  $this->serverContext->maxExtent->miny,
                                  $this->serverContext->maxExtent->maxx,
                                  $this->serverContext->maxExtent->maxy);

            $maskLayer = ms_newLayerObj($msMapObj);
            $maskLayer->set("type", MS_LAYER_POLYGON);
            $maskLayer->set("status", 1);
            $maskClass = ms_newClassObj($maskLayer);
            $maskStyle = ms_newStyleObj($maskClass);
            $color = $this->getConfig()->maskColor;
            if (!$color) {
                $color = '255 255 255';
            }
            list($red, $green, $blue) = explode(' ', $color);
            $maskStyle->color->setRGB($red, $green, $blue);

            $rectangle->draw($msMapObj, $maskLayer, $image2, 0, "");
            
            $maskStyle->color->setRGB(255, 0, 0);
            $maskStyle->outlinecolor->setRGB(255, 0, 0);
                                          
            $p = $this->convertPolygon($polygon);
            $p->draw($msMapObj, $maskLayer, $image2, 0, "");
                       
            $this->serverContext->msMainmapImage->pasteImage($image2, 0xff0000);
            
        }

    }

    function getResultFromRequest($requ) {

        $msMapObj = $this->serverContext->msMapObj;
        if ($requ->maskMode) {
            $this->serverContext->msMainmapImage = $msMapObj->draw();
            $msMapObj->labelcache->free();
        }

        foreach ($requ->shapes as $shape) {
            switch (get_class($shape)) {
            case 'Point':
                $this->drawPoint($msMapObj, $shape, $requ->maskMode);
                break;
            case 'Rectangle':
                $this->drawRectangle($msMapObj, $shape, $requ->maskMode);
                break;
            case 'Polygon':
                $this->drawPolygon($msMapObj, $shape, $requ->maskMode);
                break;
            default:
                throw new CartoserverException("unknown shape type " . get_class($shape));
            }
        }
        
        if (!$requ->maskMode) {
            $this->serverContext->msMainmapImage = $msMapObj->draw();
        }
    }
}
?>