<?php
/**
 * Routing plugin Serializable objects
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2005 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

/**
 * Request
 * @package Plugins
 */
class RoutingRequest extends Serializable {
    
    /**
     * Where to stop, including start and end
     *
     * Array of string, each one identifying a point. If null, server will only
     * display path on map.
     * @var array
     */
    public $stops;

    /**
     * Key-value array of parameters
     * @var array
     */
    public $parameters;

    /**
     * Array of StyledShape needed by Outline server plugin
     * @var array
     */
    public $path;
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->stops      = self::unserializeArray($struct, 'stops');
        $this->parameters = self::unserializeArray($struct, 'parameters');
        $this->path       = self::unserializeObjectMap($struct, 'path',
                                                       'StyledShape');
    }    
}

/**
 * Step for roadmap
 * @package Plugins
 */
abstract class Step extends Serializable {
  
    /**
     * Key-value attributes
     * @var array
     */
    public $attributes;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->attributes = self::unserializeArray($struct, 'attributes');
    }
}

/**
 * Node
 * @package Plugins
 */
class Node extends Step {
}

/**
 * Edge
 * @package Plugins
 */
class Edge extends Step {
}

/**
 * Result
 * @package Plugins
 */
class RoutingResult extends Serializable {

    /**
     * Geometric path
     *
     * Array of StyledShape needed by Outline server plugin, to be stored
     * in client session.
     * @var array
     */
    public $path;

    /**
     * Logical path
     * 
     * Attributes for display of nodes/edges information. May include data
     * to be displayed in the roadmap.
     * @var array
     */
    public $steps;

    /**
     * Attributes global to path (e.g. total distance)
     * @var array
     */
    public $attributes;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->path  = self::unserializeObjectMap($struct, 'path',
                                                  'StyledShape');
        $this->steps = self::unserializeObjectMap($struct, 'steps');
        $this->attributes = self::unserializeArray($struct, 'attributes');
    }
}

?>