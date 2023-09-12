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
 * Abstract class with properties and methods shared by more than one map format
 * @todo nadenken over class hierarchieën!! probleem: child class heeft niet alle abstract methods uit de parent nodig.
 * http://stackoverflow.com/questions/10277317/abstract-class-children-type
 * Multiple Inheritance vs. Composition
 * http://propelorm.org/blog/2011/03/03/don-t-copy-code-oh-and-inheritance-and-composition-are-bad-too.html
 *
 * @internal
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2006-2007 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU GPL version 2
 * @package   Kaart
 *            
 */
abstract class Image
{
  /** @var string @todo annotate */
  protected $datasource;
  protected $tablename;
  private $_db_connection;
  /**
   * @var string filename containing the paths for the current map
   */
  protected $kaart_paths_file;
  /**
   * @var array additional files with paths
   */
  protected $additional_paths_files;
  /**
   * @var string title of the map
   */
  protected $title = '';
  /**
   * @var string link to be used for all paths (or, optionally, only highlighted paths), if set
   */
  protected $link = '';
  /**
   * @var string target to be used for all links, if set
   */
  protected $target = '';
  /**
   * @var boolean whether the general link above, if set, applies to all municipalities or only highlighted ones
   */
  protected $linkhighlightedonly = FALSE;
  /**
   * @var array copyright information for paths file(s)
   */
  protected $map_copyright_strings = array();

  abstract protected function drawPath($path, $coords, $pathtype, $map_definitions, $highlighted, $links, $map_names, $tooltips);
  abstract public function drawSymbol($coordinaten, $kloeke_nr, $plaatsnaam, $symbol, $size, $style, $link_array, $rd = TRUE);

  public function __construct($parameters)
  {
    $this->datasource = $parameters['datasource'];
    $this->tablename = $parameters['tablename'];
    $this->kaart_paths_file = $parameters['paths_file'];
    $this->additional_paths_files = $parameters['additional_paths_files'];
    $this->title = $parameters['title'];
    if (array_key_exists('link', $parameters)) {
      $this->link = $parameters['link'];
    }
    if (array_key_exists('target', $parameters)) {
      $this->target = $parameters['target'];
    }
    if (array_key_exists('linkhighlightedonly', $parameters)) {
      $this->linkhighlightedonly = $parameters['linkhighlightedonly'];
    }
  }

  /**
   * Escape string for use as argument for Javascript function
   *
   * @param string string to be escaped
   *
   * @return string escaped string
   */
  protected function escapeJSString($string)
  {
    return addslashes($string);
  }


  /**
   * Escape string for use as value of XML attribute
   *
   * @param string string to be escaped
   *
   * @return string escaped string
   */
  protected function escapeXMLString($string)
  {
    return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
  }

  /**
   * Draws the basemap, depending on which parts are requested
   *
   * @param $map_definitions
   * @param $parts
   * @param $highlighted
   * @param $links
   * @param $tooltips
   */
  protected function drawBasemap($map_definitions, $parts, $highlighted, $links, $tooltips)
  {
    /** @var $map_lines array associative array path name => coordinates, defined in $paths_file */
    /** @var $map_names array associative array path code => path name, defined in $paths_file */

    $map_lines_higlighted = array();
    $map_names_highlighted = array();

    if ($this->datasource == 'file') {
      require($this->kaart_paths_file);

      /** @var $map_copyright_string string defined in $paths_file  */
      $this->map_copyright_strings[] = $map_copyright_string;
    } elseif ($this->datasource == 'db') {
      /** @noinspection PhpUnusedLocalVariableInspection */
      list($map_lines, $map_names) = $this->_getMapDataFromDB();
      print_r($map_lines);
      die;
    }

    foreach (array_keys($highlighted) as $path) {
      if (array_key_exists($path, $map_lines)) {
        $map_lines_higlighted[$path] = $map_lines[$path];
        unset($map_lines[$path]);
      }
      if (array_key_exists($path, $map_names)) {
        $map_names_highlighted[$path] = $map_names[$path];
        unset($map_names[$path]);
      }
    }
    // draw highlighted paths on top of non-highlighted paths
    $this->_drawPaths(array($map_lines, $map_names), $map_definitions, $parts, array(), $links, $tooltips);
    $this->_drawPaths(
      array($map_lines_higlighted, $map_names_highlighted), $map_definitions, $parts, $highlighted, $links, $tooltips
    );

    foreach ($this->additional_paths_files as $file) {

      $map_lines_higlighted = array();
      $map_names_highlighted = array();

      require($file);

      /** @var $map_copyright_string string defined in $paths_file  */
      $this->map_copyright_strings[] = $map_copyright_string;

      foreach (array_keys($highlighted) as $path) {
        if (array_key_exists($path, $map_lines)) {
          $map_lines_higlighted[$path] = $map_lines[$path];
          unset($map_lines[$path]);
        }
        if (array_key_exists($path, $map_names)) {
          $map_names_highlighted[$path] = $map_names[$path];
          unset($map_names[$path]);
        }
      }
      // draw highlighted paths on top of non-highlighted paths
      $this->_drawPaths(array($map_lines, $map_names), $map_definitions, $parts, array(), $links, $tooltips);
      $this->_drawPaths(
        array($map_lines_higlighted, $map_names_highlighted), $map_definitions, $parts, $highlighted, $links, $tooltips
      );
    }
  }

  private function _drawPaths($mapinfo, $map_definitions, $parts, $highlighted, $links, $tooltips)
  {
    $map_lines = $mapinfo[0];
    $map_names = $mapinfo[1];

    $paths_partial = array();
    if (!empty($parts)) {
      foreach ($parts as $part) {
        $paths_partial = array_merge($paths_partial, $map_definitions[$part]);
      }
    }

    /** @var $map_lines array associative array path name => coordinates, defined in $paths_file */
    foreach ($map_lines as $path => $coords) {

      // this path is not part of the empty basemap, yet it is defined in the ini file: continue
      if (empty($parts)
        && array_key_exists('kaart_empty', $map_definitions)
        && !array_key_exists($path, $map_definitions['kaart_empty'])
        && array_key_exists('kaart_all', $map_definitions)
        && array_key_exists($path, $map_definitions['kaart_all'])
      ) {
      } // no empty basemap defined, path defined in ini file
      elseif (empty($parts)
        && !array_key_exists('kaart_empty', $map_definitions)
        && array_key_exists('kaart_all', $map_definitions)
        && array_key_exists($path, $map_definitions['kaart_all'])
      ) {
        /** @var $map_names array associative array path code => path name, defined in $paths_file */
        $this->drawPath(
          $path, $coords, $map_definitions['kaart_all'][$path], $map_definitions, $highlighted, $links,
          $map_names, $tooltips
        );
      } // path not predefined, not excluded; draw with default settings
      elseif (empty($parts)
        && array_key_exists('kaart_empty', $map_definitions)
        && !array_key_exists($path, $map_definitions['kaart_empty'])
        && array_key_exists('kaart_all', $map_definitions)
        && !array_key_exists($path, $map_definitions['kaart_all'])
      ) {
        /** @var $map_names array associative array path code => path name, defined in $paths_file */
        $this->drawPath(
          $path, $coords, 'path_type_default', $map_definitions, $highlighted, $links, $map_names, $tooltips
        );
      } // path is part of the empty map
      elseif (empty($parts)
        && array_key_exists('kaart_empty', $map_definitions)
        && array_key_exists($path, $map_definitions['kaart_empty'])
      ) {
        /** @var $map_names array associative array path code => path name, defined in $paths_file */
        $this->drawPath(
          $path, $coords, $map_definitions['kaart_empty'][$path], $map_definitions, $highlighted, $links,
          $map_names, $tooltips
        );
      } // no predefined paths, draw with default settings
      elseif (empty($parts)
        && (!array_key_exists('kaart_empty', $map_definitions)
          || !array_key_exists('kaart_all', $map_definitions))
      ) {
        /** @var $map_names array associative array path code => path name, defined in $paths_file */
        $this->drawPath(
          $path, $coords, 'path_type_default', $map_definitions, $highlighted, $links, $map_names, $tooltips
        );
      } // path is part of the partial map to be drawn
      elseif (!empty($parts)
        && array_key_exists($path, $paths_partial)
      ) {
        /** @var $map_names array associative array path code => path name, defined in $paths_file */
        $this->drawPath(
          $path, $coords, $paths_partial[$path], $map_definitions, $highlighted, $links, $map_names, $tooltips
        );
      } // path is not part of the partial map to be drawn, continue
      elseif (!empty($parts)
        && !array_key_exists($path, $paths_partial)
      ) {
        continue;
      }
    }

  }


  /**
   * @param $gegevens
   * @param $symbol
   * @param $style
   * @param $size
   * @param $link
   * @param $legend
   * @param $default_fontsize
   * @param $db_connection
   *
   * @return array
   */
  public function drawSeries($gegevens, $symbol, $style, $size, $link, $legend, $default_fontsize, $db_connection)
  {
    $seriescount = count($gegevens);
    $invalid_kloekecodes = array();

    foreach ($gegevens as $kloeke_nr) {

      // with 'frequency' maps (see Kaart::setMapType())
      if (is_array($size)) {
        $kloeke_size = $size[$kloeke_nr];
      } else {
        $kloeke_size = $size;
      }

      list($x, $y) = Kaart::getCoordinates($kloeke_nr, $db_connection);

      // invalid Kloeke code, ignore silently and save for error report
      if ($x == 0 && $y == 0) {
        // subtract one from number of places so that number of places in legend is correct
        // (same as number of places on the map)
        $invalid_kloekecodes[] = $kloeke_nr;
        $seriescount--;
        continue;
      }

      $plaatsnaam = Kaart::getPlaatsnaam($kloeke_nr, $db_connection);
      $this->drawSymbol(
        array($x, $y), $kloeke_nr, $plaatsnaam, $symbol, intval($kloeke_size), $style, $link
      );
    }
    // if no valid Kloeke codes in legend ($seriescount == 0), do not draw legend
    if ($legend !== FALSE && $seriescount > 0) {
      list($x, $y) = $this->getLegendSymbolXY();
      $this->setNextLegendSymbolY();
      // FALSE parameter 8 means FALSE that the coordinates (parameter 1) are National Triangulation coordinates
      // _symbols in the legend are drawn outside the National Triangulation system
      $this->drawSymbol(
        array($x, $y), '', '', $symbol, $default_fontsize * 0.9, $style, array(), FALSE
      );
      $this->drawLegendText($legend . ' (' . count($gegevens) . ')');
    }

    return $invalid_kloekecodes;
  }

  /**
   * @param $reeksnummers
   * @param $aantal
   * @param $sizes
   * @param $default_fontsize
   * @param $symbols
   * @param $styles
   */
  public function drawCombination($reeksnummers, $aantal, $sizes, $default_fontsize, $symbols, $styles)
  {
    $reeksnummers = explode(',', $reeksnummers);
    list($x, $y) = $this->getLegendSymbolXY();
    $this->setNextLegendSymbolY();
    foreach ($reeksnummers as $nummer) {
      if (array_key_exists($nummer, $sizes)) {
        // so that _symbols in the legend appear in the same scale relative to each other
        // as they do on the map
        $size = $sizes[$nummer] * 1.9;
      } else {
        $size = $default_fontsize * 0.9;
      }
      $this->drawSymbol(
        array($x, $y), '', '', $symbols[$nummer], $size, $styles[$nummer], array(), FALSE
      );
    }
    $this->drawLegendText("($aantal)");
  }


  /**
   * Vertaalt Rijksdriehoekscoördinaten naar noorderbreedte/oosterlengte
   *
   * Gebaseerd op Javascript van Ed Stevenhagen en Frank Kissels ({@link http://www.xs4all.nl/~estevenh/})
   *
   * @param $rd_x float x-coördinaat (RD)
   * @param $rd_y float y-coördinaat (RD)
   *
   * @return array array met noorderbreedte en oosterlengte
   */
  protected function rd2latlong($rd_x, $rd_y)
  {
    // constanten
    $X0 = 155000.000;
    $Y0 = 463000.000;
    $F0 = 52.156160556;
    $L0 = 5.387638889;

    $A01 = 3236.0331637;
    $B10 = 5261.3028966;
    $A20 = -32.5915821;
    $B11 = 105.9780241;
    $A02 = -0.2472814;
    $B12 = 2.4576469;
    $A21 = -0.8501341;
    $B30 = -0.8192156;
    $A03 = -0.0655238;
    $B31 = -0.0560092;
    $A22 = -0.0171137;
    $B13 = 0.0560089;
    $A40 = 0.0052771;
    $B32 = -0.0025614;
    $A23 = -0.0003859;
    $B14 = 0.0012770;
    $A41 = 0.0003314;
    $B50 = 0.0002574;
    $A04 = 0.0000371;
    $B33 = -0.0000973;
    $A42 = 0.0000143;
    $B51 = 0.0000293;
    $A24 = -0.0000090;
    $B15 = 0.0000291;

    $dx = ($rd_x - $X0) * pow(10, -5);
    $dy = ($rd_y - $Y0) * pow(10, -5);

    $df = ($A01 * $dy) + ($A20 * pow($dx, 2)) + ($A02 * pow($dy, 2)) + ($A21 * pow($dx, 2) * $dy) + (
      $A03 * pow($dy, 3));
    $df += ($A40 * pow($dx, 4)) + ($A22 * pow($dx, 2) * pow($dy, 2)) + ($A04 * pow($dy, 4)) + (
      $A41 * pow($dx, 4) * $dy);
    $df += ($A23 * pow($dx, 2) * pow($dy, 3)) + ($A42 * pow($dx, 4) * pow($dy, 2)) + (
      $A24 * pow($dx, 2) * pow($dy, 4));

    $noorderbreedte = $F0 + ($df / 3600);

    $dl = ($B10 * $dx) + ($B11 * $dx * $dy) + ($B30 * pow($dx, 3)) + ($B12 * $dx * pow($dy, 2)) + (
      $B31 * pow($dx, 3) * $dy);
    $dl += ($B13 * $dx * pow($dy, 3)) + ($B50 * pow($dx, 5)) + ($B32 * pow($dx, 3) * pow($dy, 2)) + (
      $B14 * $dx * pow($dy, 4));
    $dl += ($B51 * pow($dx, 5) * $dy) + ($B33 * pow($dx, 3) * pow($dy, 3)) + ($B15 * $dx * pow($dy, 5));

    $oosterlengte = $L0 + ($dl / 3600);

    return array($noorderbreedte, $oosterlengte);
  }

  /**
   * @todo annotate, robuuster maken
   */
  private function _getMapDataFromDB()
  {
    $map_names = $map_lines = array();
    $this->_db_connection = Kaart::createDBConnection(KAART_NLGIS_DB);
    $query = "SELECT CONCAT('a_', acode) AS acode, gm_naam, ASWKT(shape) FROM {$this->tablename}";
    $result = mysqli_query($this->_db_connection, $query);
    if ($result) {
      while ($rij = mysqli_fetch_row($result)) {
        $map_names[$rij[0]] = $rij[1];
        $map_lines[$rij[0]] = $this->_parseWKT($rij[2]);
      }
    }

    return array($map_lines, $map_names);
  }

  /**
   * @param $text
   * @todo annotate
   * @return array
   */
  /** @noinspection PhpInconsistentReturnPointsInspection */
  private function _parseWKT($text) {
    if (strpos($text, 'MULTIPOLYGON') !== FALSE) {
      $text = str_replace('MULTIPOLYGON', '', $text);
      $polygons = explode('), (', $text);
      return array_map(array($this, '_cleanWKTString'), $polygons);
    } elseif (strpos($text, 'POLYGON') !== FALSE) {
      preg_match('/POLYGON\(\((.+?)\)\)/', $text, $matches);
      return $this->_cleanWKTString($matches[1]);
    } elseif (strpos($text, 'MULTILINESTRING') !== FALSE) {
      $text = str_replace(array('MULTILINESTRING',')', '('), '', $text);
      return $this->_cleanWKTString($text);
    } elseif (strpos($text, 'LINESTRING') !== FALSE) {
      preg_match('/LINESTRING\((.+?)\)/', $text, $matches);
      return $this->_cleanWKTString($matches[1]);
    }
  }

  /**
   * @param $elem
   * @todo annotate
   * @return array
   */
  private function _cleanWKTString($elem) {

    $elem = explode(',', str_replace(' ', ',', str_replace(', ', ',', trim($elem, ')('))));
    $retval = array();
    foreach($elem as $float) {
      $retval[] = round($float);
    }

    return $retval;
  }


}
