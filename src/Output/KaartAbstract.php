<?php

//  Copyright (C) 2008 Meertens Instituut / KNAW
// 
//  This program is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
// 
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
// 
//  You should have received a copy of the GNU General Public License along
//  with this program; if not, write to the Free Software Foundation, Inc.,
//  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

namespace Meertens\Kaart\Output;
use Meertens\Kaart\Kaart;

/**
 * Abstract base class
 *
 * Not relevant for the Kaart end-user; contains properties and methods shared between maps of different kinds
 *
 * @internal
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2008-2012 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU GPL version 2
 * @package   Kaart
 *
 */

abstract class KaartAbstract
{
 /** @var string @todo annotate */
  protected $tablename = '';
  /** @var string @todo annotate */
  protected $datasource = 'file';
  /**
   * @var resource mysql-connection to the geo-database
   */
  protected $db_connection = NULL;
  /**
   * @var SVG SVG version of the map
   */
  public $svg;
  /**
   * @var Bitmap bitmap version of the map
   */
  public $bitmap;
  /**
   * @var KML KML version of the map
   */
  public $kml;
  /**
   * @var JSON JSON version of the map
   */
  public $json;
  /**
   * @var int width of the map in pixels
   */
  public $width;
  /**
   * @var int height of the map in pixels
   */
  public $height;
  /**
   * @var string title of the map
   */
  public $title = '';
  /**
   * @var bool whether placename + Kloeke-code should be displayed 'onmouseover' on placemarks
   */
  public $interactive = FALSE;
  /**
   * @var array list with extra background layers
   */
  public $backgrounds = array();
  /**
   * @var array list with possible background maps
   */
  public $possible_backgrounds = array();
  /**
   * @var array array with paths and styles for the base map, taken from .ini file
   */
  public $map_definitions = array();
  /**
   * Default file resides in the Kaart subdirectory, can be overruled by parameter with alternate file
   *
   * @var string File with arrays with coordinates which form the basemap
   */
  public $kaart_paths_file;
  /**
   * Contains definitions of styles and allowed parts for a particular basemap, can be overruled
   * by parameter with alternate file. Default file resides in the Kaart subdirectory
   *
   * @var $kaart_ini_file string
   */
  public $kaart_ini_file;
  /**
   * @var array additional files with paths
   */
  public $additional_paths_files = array();
  /**
   * @var bool $width_manually_changed if TRUE, setIniFile() should restore the manually changed width
   */
  public $width_manually_changed = FALSE;
  /**
   * @var array to hold the data to be displayed on the map
   */
  protected $map_array = array();
  /**
   * @var int default fontsize for the legend
   */
  protected $default_fontsize;
  /**
   * @var array list of parts of the basemap which should be drawn. If empty, draw complete basemap
   */
  protected $parts = array();

  abstract protected function _parseIniFile();
  /**
   * @param $ini_file
   */
  public function setIniFile($ini_file)
  {
    if (stream_resolve_include_path($ini_file)) {
      // keep changed height and width (if any)
      $original_width = $this->width;
      $original_height = $this->height;
      $this->kaart_ini_file = $ini_file;
      $this->_parseIniFile();
      if ($this->width_manually_changed) {
        $this->width = $original_width;
        $this->height = $original_height;
      }
    }
  }
}
