<?php
//  Copyright (C) 2006-2007 Meertens Instituut / KNAW
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
 * Class to create the GeoJSON version of a map
 *
 * @internal
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2012 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU GPL version 2
 * @package   Kaart
 */
class JSON extends Image
{

  /**
   * @var array array containing the points to be drawn on the map
   */
  private $_map_array = array();
  /**
   * @var array array containing the points to be drawn on the map, divided into _subseries
   */
  private $_subseries = array();
  /**
   * @var array array containing the _symbols for the series of points
   */
  private $_symbols = array();
  /**
   * @var array array containing the sizes for the series of points
   */
  private $_sizes = array();
  /**
   * @var array array containing the links for the series of points
   */
  private $_links = array();
  /**
   * @var array array containing the legends for the series of points
   */
  private $_legends = array();
  /**
   * @var array array containing the styles for the _symbols
   */
  private $_styles = array();
  /**
   * @var array array containing alternate texts for the municipalities
   */
  private $_tooltips = array();
  /**
   * @var array array containing the different features (points, polygons) of the map
   */
  private $_features = array();
  /**
   * @var resource mysql-connection to the geo-database
   */
  private $_db_connection = NULL;
  /**
   * @var array
   */
  private $_highlighted = array();


  /**
   * @param array array with parameters for map construction
   */
  public function __construct($parameters)
  {
    parent::__construct($parameters);

    if (isset($parameters['db_connection'])) {
      $this->_db_connection = $parameters['db_connection'];
    }
    $map_definitions = $parameters['map_definitions'];
    if (isset($parameters['highlighted'])) {
      $this->_highlighted = $parameters['highlighted'];
    }
    if (array_key_exists('tooltips', $parameters)) {
      $this->_tooltips = $parameters['tooltips'];
    }
    if (isset($parameters['map_array'])) {
      $this->_map_array = $parameters['map_array'];
    }
    if (isset($parameters['_symbols'])) {
      $this->_symbols = $parameters['_symbols'];
    }
    if (isset($parameters['styles'])) {
      $this->_styles = $parameters['styles'];
    }
    if (isset($parameters['links'])) {
      $this->_links = $parameters['links'];
    }
    if (isset($parameters['sizes'])) {
      $this->_sizes = $parameters['sizes'];
    }
    if (isset($parameters['legends'])) {
      $this->_legends = $parameters['legends'];
    }
    if (isset($parameters['_subseries'])) {
      $this->_subseries = $parameters['_subseries'];
    }
    $this->drawBasemap($map_definitions, $parameters['parts'], $this->_highlighted, $this->_links, $this->_tooltips);
    if (!empty($this->_map_array)) {
      foreach ($this->_map_array as $offset => $gegevens) {
        $seriesparams['symbol'] = $this->_symbols[$offset];
        $seriesparams['style'] = $this->_parseSVGstyle($this->_styles[$offset]);
        $seriesparams['size'] = $this->_sizes[$offset];
        if (isset($this->_legends[$offset]) && $this->_legends[$offset] !== FALSE) {
          $seriesparams['legend'] = $this->_legends[$offset];
        }
        if (isset($this->_links[$offset]) && !empty($this->_links[$offset])) {
          $seriesparams['link'] = $this->_links[$offset];
        }
        $this->_drawSeriesJSON($offset, $gegevens, $seriesparams);
      }
    }
    if (!empty($this->_subseries)) {
      foreach ($this->_subseries as $reeksen) {
        $seriesparams = array();
        foreach ($reeksen as $offset => $gegevens) {
          $seriesparams['symbol'] = $gegevens['symbol'];
          $seriesparams['style'] = $this->_parseSVGstyle($gegevens['style']);
          $seriesparams['size'] = $gegevens['size'];
          if (isset($gegevens['legend']) && $gegevens['legend'] !== FALSE) {
            $seriesparams['legend'] = $gegevens['legend'];
          }
          if (isset($gegevens['link']) && !empty($gegevens['link'])) {
            $seriesparams['link'] = $gegevens['link'];
          }
          $this->_drawSeriesJSON($offset, $gegevens['data'], $seriesparams);
        }
      }
    }
  }

  /**
   * @param $path
   * @param $coords
   * @param $pathtype
   * @param $map_definitions
   * @param $highlighted
   * @param $links
   * @param $map_names
   * @param $tooltips
   */
  protected function drawPath($path, $coords, $pathtype, $map_definitions, $highlighted, $links, $map_names, $tooltips)
  {
    $highlightedpath = FALSE;
    $feature = array();
    $feature['type'] = 'Feature';
    $feature['properties'] = array();
    // tooltips overrule map_names
    if (array_key_exists($path, $tooltips)) {
      $feature['properties']['name'] = $tooltips[$path];
    } elseif (array_key_exists($path, $map_names)) {
      $feature['properties']['name'] = $map_names[$path];
    }
    $feature['properties']['id'] = $path;
    $feature['properties']['style'] = $this->_parseSVGstyle($map_definitions[$pathtype]['svg_path_style']);
    if (array_key_exists($path, $highlighted)) {
      $feature['properties']['style']['fill'] = $highlighted[$path];
      $highlightedpath = TRUE;
    }

    if (!empty($this->link)) {
      if (!$this->linkhighlightedonly || ($this->linkhighlightedonly && $highlightedpath)) {
        $feature['properties']['href'] = $this->escapeJSString(sprintf($this->link, $path));
        if (!empty($this->target)) {
          $feature['target'] = $this->escapeJSString($this->target);
        }
      }
    }

    if (array_key_exists($path, $links)) {
      foreach ($links[$path] as $key => $value) {
        $feature['properties'][$key] = $this->escapeJSString($value);
      }
    }

    $feature['geometry'] = array();
    if (is_array($coords[0])) {
      $coordinates = array();
      $feature['geometry']['type'] = 'MultiPolygon';
      foreach ($coords as $subcoords) {
        $coordinates[] = array($this->_createCoordinates($subcoords));
      }
    } else {
      $feature['geometry']['type'] = $map_definitions[$pathtype]['json_geometry_type'];
      if ($feature['geometry']['type'] == 'Polygon') {
        $coordinates = array($this->_createCoordinates($coords));
      } else {
        $coordinates = $this->_createCoordinates($coords);
      }
    }
    $feature['geometry']['coordinates'] = $coordinates;
    $this->_features[] = $feature;
  }

  /**
   * @param $coords
   *
   * @return array
   */
  private function _createCoordinates($coords)
  {

    $coordinates = array();
    while ($coords) {
      $x = array_shift($coords);
      $y = array_shift($coords);
      list($noorderbreedte, $oosterlengte) = $this->rd2latlong($x, $y);
      $coordinates[] = array($oosterlengte, $noorderbreedte);
    }
    return $coordinates;
  }

  /**
   * @param $offset
   * @param $gegevens
   * @param $seriesparams
   */
  private function _drawSeriesJSON($offset, $gegevens, $seriesparams)
  {

    $feature = array();
    $feature['type'] = 'Feature';
    $feature['properties'] = array();
    $feature['properties']['offset'] = $offset;
    $feature['properties']['symbol'] = $seriesparams['symbol'];
    $feature['properties']['style'] = $seriesparams['style'];
    $feature['properties']['size'] = $seriesparams['size'];
    if (isset($seriesparams['legend'])) {
      $feature['properties']['legend'] = $seriesparams['legend'];
    }
    $feature['geometry'] = array();
    $feature['geometry']['type'] = 'GeometryCollection';
    $feature['geometry']['geometries'] = array();

    foreach ($gegevens as $kloeke_nr) {
      if (isset($seriesparams['link'])) {
        $link = $seriesparams['link'];
      } else {
        $link = FALSE;
      }
      $feature['geometry']['geometries'][] = $this->_drawKloeke($kloeke_nr, $link);
    }
    $this->_features[] = $feature;
  }

  /**
   * @param $kloeke_nr
   * @param $link
   *
   * @return array
   */
  private function _drawKloeke($kloeke_nr, $link)
  {
    list($x, $y) = Kaart::getCoordinates($kloeke_nr, $this->_db_connection);
    list($noorderbreedte, $oosterlengte) = $this->rd2latlong($x, $y);
    $point = array('type' => 'Point', 'coordinates' => array($oosterlengte, $noorderbreedte));
    $point['properties'] = array('kloekenr' => $kloeke_nr);
    if (is_array($link)) {
      foreach ($link as $key => $value) {
        if ($key == 'target') {
          $value = $this->escapeJSString($value);
        } else {
          $value = $this->escapeJSString(sprintf($value, $kloeke_nr));
        }
        $point['properties'][$key] = $value;
      }
    }
    return $point;
  }


  /**
   * @param string string with SVG "style" attribute
   *
   * @return array array with three members describing the style
   */
  private function _parseSVGstyle($style)
  {
    $fill = $stroke = $stroke_width = '';

    if (preg_match('/fill:\s*?(.+?);/', $style, $matches)) {
      $fill = $matches[1];
    }
    if (preg_match('/stroke:\s*?(.+?);/', $style, $matches)) {
      $stroke = $matches[1];
    }
    if (preg_match('/stroke-width:\s*?(.+?);/', $style, $matches)) {
      $stroke_width = $matches[1];
    }
    return array('fill' => $fill, 'stroke' => $stroke, 'stroke-width' => $stroke_width);
  }

  /**
   * @return string
   */
  public function toJSON()
  {
    $crs = array('type' => 'name', 'properties' => array('name' => 'urn:ogc:def:crs:OGC:1.3:CRS84'));
    $geojson = array('type' => 'FeatureCollection');
    $geojson['features'] = $this->_features;
    $geojson['crs'] = $crs;
    if (!empty($this->map_copyright_strings)) {
      $geojson['properties']['copyright'] = array();
      foreach ($this->map_copyright_strings as $string) {
        $geojson['properties']['copyright'][] = $string;
      }
    }

    return json_encode($geojson);
  }

  public function drawSymbol($coordinaten, $kloeke_nr, $plaatsnaam, $symbol, $size, $style, $link_array, $rd = TRUE) {}

}
