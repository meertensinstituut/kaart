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
 * Internal class for drawing choropleth maps. Its public methods are called via the __call method of the
 * Kaart object. This means that the methods listed below are all meant to be called on a Kaart object:
 *
 * <code>
 * $kaart = new Kaart('municipalities');
 * $codes = $kaart->getPossibleAreas();
 * // etc.
 * </code>
 *
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2008 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU GPL version 2
 * @package   Kaart
 */
class Choropleth extends KaartAbstract
{

  /**
   * @var array with tooltips for municipalities (municipality codes are keys, tooltip texts are values)
   */
  private $_tooltips = array();
  /**
   * @var int extra space to fit the title of the map into
   */
  private $_svg_title_extra_space = 38000;
  /**
   * @var array with links for (a subset of) the municipalities (municipality codes are keys, href values are values). Optional key: 'target'
   */
  private $_links = array();
  /**
   * @var string with %s placeholder which will be replaced with municipality code
   */
  private $_link = '';
  /**
   * @var string optional target to be used for $link above
   */
  private $_target = '';
  /**
   * @var boolean whether the general link above, if set, applies to all municipalities or only highlighted ones
   */
  private $_linkhighlightedonly = FALSE;
  /**
   * @var string 'municipalities', 'provinces', 'dialectareas' or 'corop'
   */
  private $_type = 'municipalities';
  /**
   * @var int the year for which the borders are requested
   */
  private $_year = 0;

  /**
   * The constructor
   *
   * @internal
   *
   * @param string $map        map type
   * @param mixed  $paths_file NULL or string with alternate paths file
   * @param mixed  $ini_file   NULL or string with alternate ini file
   */
  public function __construct($map, $paths_file = NULL, $ini_file = NULL)
  {
    if (is_array($map)) {
      /** @todo more robustness needed */
      $this->_type = $map['type'];
      $this->_year = intval($map['year']);
      $this->datasource = 'db';
      $this->tablename = $this->_setTableName();
    } elseif ($map == 'gemeentes') {
      $this->_type = 'municipalities';
    } elseif ($map == 'provincies') {
      $this->_type = 'provinces';
    } elseif ($map == 'daan_blok_1969') {
      $this->_type = 'dialectareas';
    } else {
      $this->_type = $map;
    }
    if (is_null($paths_file) && $this->datasource == 'file') {
      $this->kaart_paths_file = $this->_type . '.inc.php';
    } else {
      $this->kaart_paths_file = $paths_file;
    }
    if (is_null($ini_file)) {
      $this->kaart_ini_file = 'municipalities.ini';
    } else {
      $this->kaart_ini_file = $ini_file;
    }
    $this->_parseIniFile();
  }

  /**
   * Returns an array with possible municipalities for the basemap. Works only if map is of type 'municipalities'.
   *
   * @return array associative array with municipality code => municipality name
   */
  public function getPossibleMunicipalities()
  {
    if ($this->_type == 'municipalities') {
      return $this->getPossibleAreas();
    } else {
      return array();
    }
  }

  /**
   * Returns an array with possible areas for the basemap
   *
   * @return array associative array with area code => area name
   * @todo robuuster maken, naar datasource kijken
   */
  public function getPossibleAreas()
  {
    if ($this->_year === 0) {
      return $this->_getPossibleAreasFromPathsFile();
    } else {
      return $this->_getPossibleAreasFromDB();
    }
  }

  /**
   * Add areas to be highlighted
   *
   * @param $data array containing areas to be highlighted (area codes are keys, colors are values)
   */
  public function addData($data)
  {
    $this->map_array = $data;
  }

  /**
   * Add links to areas (potentially a different link for each area)
   *
   * @param array array containing links for areas (area codes are keys, href values are values)
   * @param mixed NULL or string with value of 'target' attribute for the links
   */
  public function addLinks($data, $target = NULL)
  {
    foreach ($data as $code => $link) {
      $this->_links[$code]['href'] = $link;
      if (!is_null($target)) {
        $this->_links[$code]['target'] = $target;
      }
    }
  }

  /**
   * Add the same link to all areas. %s placeholder will be replaced with area code
   *
   * @param string string containing href value of the link
   * @param mixed  NULL or string with value of 'target' attribute for the link
   */
  public function setLink($link, $target = NULL)
  {
    $this->_link = $link;
    if (!is_null($target)) {
      $this->_target = $target;
    }
  }

  /**
   * Add the same link to all highlighted areas. %s placeholder will be replaced with area code
   *
   * @param string string containing href value of the link
   * @param mixed  NULL or string with value of 'target' attribute for the link
   */
  public function setLinkHighlighted($link, $target = NULL)
  {
    $this->_link = $link;
    if (!is_null($target)) {
      $this->_target = $target;
    }
    $this->_linkhighlightedonly = TRUE;
  }


  /**
   * Add tooltips to areas. These become 'title' attributes in imagemap <area>s or SVG <path>s, <description>
   * elements in KML, and 'name' properties in GeoJSON.
   *
   * @param array array containing tooltips for areas (area codes are keys, tooltip texts are values)
   */
  public function addToolTips($data)
  {
    $this->_tooltips = $data;
  }


  /**
   * Add JavaScript events to areas
   * Note: if setInteractive() is TRUE, the onmouseover and onmouseout events are already taken.
   *
   * @param array  array containing javascript for areas (area codes are keys, javascript code snippets are values)
   * @param string string containing event on which the Javascript should execute. Possible values: onclick, onmouseover, onmouseout
   */
  public function setJavaScript($data, $event = 'onclick')
  {
    foreach ($data as $code => $javascript) {
      $this->_links[$code][$event] = $javascript;
    }
  }


  /**
   * Draws a basemap with optional highlighted areas
   *
   * @internal
   * @param $format string containing format of the map (svg, png, gif, jpeg, kml, json)
   */
  public function createMap($format = 'svg')
  {
    $parameters = array(
      'datasource' => $this->datasource,
      'tablename' => $this->tablename,
      'paths_file' => $this->kaart_paths_file,
      'additional_paths_files' => $this->additional_paths_files,
      'width' => $this->width,
      'height' => $this->height,
      'interactive' => $this->interactive,
      'title' => $this->title,
      'fontsize' => $this->default_fontsize,
      'map_definitions' => $this->map_definitions,
      'parts' => $this->parts,
      'backgrounds' => $this->backgrounds,
      'highlighted' => $this->map_array,
      'tooltips' => $this->_tooltips,
      'links' => $this->_links,
      'link' => $this->_link,
      'target' => $this->_target,
      'linkhighlightedonly' => $this->_linkhighlightedonly
    );

    switch ($format) {

    case 'svg':

      if (!empty($parameters['title'])) {
        $parameters['map_definitions']['map_settings']['svg_viewbox_height'] += $this->_svg_title_extra_space;
        $picturebackgroundwidth = $parameters['map_definitions']['map_settings']['svg_viewbox_width'];
        $picturebackgroundheight = $parameters['map_definitions']['map_settings']['svg_viewbox_height'];
      } else {
        $picturebackgroundwidth = $parameters['map_definitions']['map_settings']['svg_viewbox_width'] - 13000;
        $picturebackgroundheight = $parameters['map_definitions']['map_settings']['svg_viewbox_height'] - 5000;
      }

      $parameters['picturebackground'] = new \XML_SVG_Rect(array(
        'x' => 15000,
        'y' => 3000,
        'width' => $picturebackgroundwidth,
        'height' => $picturebackgroundheight,
        'style' => 'fill:#eeeeff;stroke:#000000;stroke-width:200;'
      ));
      $parameters['highlighted'] = $this->_translateColors($parameters['highlighted'], 'html');
      $this->svg = new SVG($parameters);
      $this->svg->insertCopyrightStatement();
      break;

    case 'bitmap':
      $parameters['highlighted'] = $this->_translateColors($parameters['highlighted'], 'html');
      $this->bitmap = new Bitmap($parameters);
      break;

    case 'kml':
      $parameters['kml_lookat'] = $this->map_definitions['kml_lookat'];
      $parameters['kml_defaults'] = $this->map_definitions['kml_defaults'];
      $parameters['basemap'] = TRUE;
      $parameters['highlighted'] = $this->_translateColors($parameters['highlighted'], 'kml');
      $this->kml = new KML($parameters);
      $this->kml->insertCopyrightStatement();
      break;

    case 'json':
      $this->json = new JSON($parameters);
      break;

    }
  }

  /**
   * @param $highlighted array contaiing areas to be highlighted (area codes are keys, colors are values)
   * @param $format      string with color (name or code)
   *
   * @return array with the colors translated to hexadecimal color codes, either HTML or Google Earth
   */
  private function _translateColors($highlighted, $format)
  {
    $retval = array();
    foreach ($highlighted as $k => $v) {
      if (is_string($v)) {
        list($hex_color, $ge_color) = Kaart::translateColor($v);
        if ($v == 'none') {
          $hex_color = 'none';
          $ge_color = '00ffffff';
        }
        if ($format == 'kml') {
          $retval[$k] = $ge_color;
        } elseif ($format == 'html') {
          $retval[$k] = $hex_color;
        }
      } elseif (is_array($v)) {
        list($hex_color, $ge_color) = Kaart::translateColor($v['fill']);
        if ($v['fill'] == 'none') {
          $hex_color = 'none';
          $ge_color = '00ffffff';
        }
        if ($format == 'kml') {
          $retval[$k]['fill'] = $ge_color;
        } elseif ($format == 'html') {
          $retval[$k]['fill'] = $hex_color;
        }
        list($hex_color, $ge_color) = Kaart::translateColor($v['outline']);
        if ($format == 'kml') {
          $retval[$k]['outline'] = $ge_color;
        } elseif ($format == 'html') {
          $retval[$k]['outline'] = $hex_color;
        }
        $retval[$k]['strokewidth'] = $v['strokewidth'];
      }
    }

    return $retval;
  }

  /**
   * @return array
   */
  private function _getPossibleAreasFromPathsFile()
  {
    include($this->kaart_paths_file);
    if (empty($this->additional_paths_files)) {
      /** @var $map_names array with area code => area name, defined in $this->kaart_paths_file */
      return $map_names;
    } else {
      /** @var $map_names array with area code => area name, defined in $this->kaart_paths_file */
      $merged_names = $map_names;
      foreach ($this->additional_paths_files as $file) {
        include($file);
        $merged_names = array_merge($merged_names, $map_names);
      }
      return $merged_names;
    }
  }

  /** @todo robuuster maken, werkt nu niet voor provincies */
  private function _getPossibleAreasFromDB()
  {
    $retval = array();
    //$this->createDBConnection(KAART_NLGIS_DB);
    $this->db_connection = Kaart::createDBConnection(KAART_NLGIS_DB);
    $query = "SELECT CONCAT('a_', acode) AS acode, gm_naam FROM {$this->tablename}";
    $result = mysqli_query($this->db_connection, $query);
    if ($result) {
      while ($rij = mysqli_fetch_row($result)) {
        $retval[$rij[0]] = $rij[1];
      }
    }

    return $retval;
  }

  /** #@todo nader bekijken, eventueel refactoren */
  private function _setTableName()
  {
    if ($this->_type == 'provinces') {
      $prefix = 'prov_';
    } else {
      $prefix = 'nl_';

    }
    return $prefix . $this->_year;
  }

  /**
   * Initializes settings for the map based on the provided ini file
   */
  protected function _parseIniFile()
  {
    $this->map_definitions = parse_ini_file($this->kaart_ini_file, TRUE);
    $this->width = $this->map_definitions['map_settings']['width'];
    $this->height = $this->width * $this->map_definitions['map_settings']['height_factor'];
    $this->default_fontsize = $this->map_definitions['map_settings']['svg_default_fontsize'];
  }

  /**
   * @internal
   * @return bool boolean FALSE, this map type doesn't have the concept subseries
   */
  public function containsSubseries()
  {
    return FALSE;
  }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            return null;
        }
    }
}
