<?php

//  Copyright (C) 2006-2008 Meertens Instituut / KNAW
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
 * Internal class for drawing symbol maps. Its public methods are called via the __call method of the
 * Kaart object. This means that the methods listed below are all meant to be called on a Kaart object:
 *
 * <code>
 * $kaart = new Kaart('dutchlanguagearea'); // or just new Kaart(); since dutchlanguagearea is the default
 * $codes = $kaart->getPossiblePlacemarks();
 * // etc.
 * </code>
 *
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2008-2012 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU GPL version 2
 * @package   Kaart
 */
class DutchLanguageArea extends KaartAbstract
{
  /**
   * @var array with index numbers of series which are not part of the legend (i.e. which are part of the background)
   */
  private $_background_data = array();
  /**
   * @var bool whether counts of overlapping placemarks should be shown in the legend
   */
  private $_combinations = FALSE;
  /**
   * @var int default size with which placemarks should be drawn
   */
  private $_default_symbolsize;
  /**
   * @var string type of the map. Two types are possible: 'standard' = all _symbols have the same size; 'frequency' = _symbols which occur more than once in a series are drawn larger
   */
  private $_type = 'standard';
  /**
   * @var array list of possible map types
   */
  private $_possible_types = array('standard', 'frequency');
  /**
   * @var array list of possible _symbols (taken from config file)
   */
  private $_possible_symbols;
  /**
   * @var int number of default _symbols
   */
  private $_max_default_symbols;
  /**
   * @var array list of _symbols for different series of placemarks
   */
  private $_symbols = array();
  /**
   * @var array array containing default _symbols, taken from .ini file
   */
  private $_symbol_defaults = array();
  /**
   * @var array associative array, keys = names of _symbols, values = default colors
   */
  private $_kml_symbols;
  /**
   * @var int property to hold the 'current' series when building a map
   */
  private $_current_series_offset = 0;
  /**
   * @var array array which holds subdivisions in _subseries of different series of placemarks. Unused if empty
   */
  private $_subseries = array();
  /**
   * To be translated to hex-colors in the constructor
   *
   * @var array lijst with colors for different series on KML maps
   */
  private $_kml_colors;
  /**
   * Default size for KML icons
   *
   * @var string grootte
   */
  private $_kml_default_icon_size;
  /**
   * @var array styles for different series of placemarks
   */
  private $_styles = array();
  /**
   * @var array extra colors for when there are more than $this->_max_default_symbols without explicitly set styles
   */
  private $_extra_colors = array('blue', 'red', 'green', 'yellow', 'white', 'grey', 'black', 'none');
  /**
   * @var array sizes for different series of placemarks
   */
  private $_sizes = array();
  /**
   * @var array legend texts for different series of placemarks
   */
  private $_legends = array();
  /**
   * @var array links for different series of placemarks (Kloeke codes represented by %s placeholders)
   */
  private $_links = array();
  /**
   * @var array array with possible user-supplied parts of the basemap, taken from .ini file
   */
  private $_possible_parts = array();
  /**
   * @var int only for KML maps: difference in altitude in meters above the earth surface between different series of placemarks. If the difference is less than 50, the stacking order is sometimes incorrect
   */
  private $_altitudedifference = 50;
  /**
   * @var bool whether, when drawing a standard map, duplicate placemarks in a series should be removed
   */
  private $_remove_duplicates = FALSE;
  /**
   * @var array array which holds invalid Kloeke codes which where submitted to the map (i.e. for which no coordinates could be found)
   */
  private $_invalid_kloekecodes = array();

  /**
   * @internal
   *
   * @param null $paths_file
   * @param null $ini_file
   */
  public function __construct($paths_file = NULL, $ini_file = NULL)
  {
    if (is_null($paths_file)) {
      $this->kaart_paths_file = 'Netherlands_Flanders.inc.php';
    } else {
      $this->kaart_paths_file = $paths_file;
    }
    if (is_null($ini_file)) {
      $this->kaart_ini_file = 'ini/Netherlands_Flanders.ini';
    } else {
      $this->kaart_ini_file = $ini_file;
    }
    $this->_parseIniFile();
  }

  /**
   * Add a series of Kloeke codes to the basemap
   *
   * @param array    array of Kloeke codes to be displayed
   * @param mixed    NULL or integer for the index number of the series (NULL = current series)
   * @param mixed    NULL or integer of the _subseries for the Kloeke codes (NULL = no _subseries).
   *                 Subseries have different _symbols from each other, the placemarks within them
   *                 have different colors per series but all the same symbol.
   */
  public function addData($data, $series = NULL, $subseries = NULL)
  {
    $this->db_connection = Kaart::createDBConnection(KAART_GEO_DB);
/*    if (is_null($this->db_connection)) {
      $this->createDBConnection(KAART_GEO_DB);
    }*/

    if (!empty($this->map_array) && is_null($series)) {
      $this->_current_series_offset++;
    }

    if (is_numeric($series) && is_null($subseries)) {
      if (array_key_exists($series, $this->map_array)) {
        $this->map_array[$series] = array_merge($this->map_array[$series], $data);
      } else {
        if ($series > 0) {
          $this->_current_series_offset++;
        }
        $this->map_array[$this->_current_series_offset] = $data;
      }
    } elseif (is_null($series) && is_numeric($subseries)) {
      $this->map_array[$this->_current_series_offset] = $data;
      $this->_subseries[$subseries][$this->_current_series_offset]
        =& $this->map_array[$this->_current_series_offset];
    } elseif (is_null($series) && is_null($subseries)) {
      $this->map_array[$this->_current_series_offset] = $data;
    } elseif (is_numeric($series) && is_numeric($subseries)) {
      if (array_key_exists($series, $this->map_array)) {
        $this->map_array[$series] = array_merge($this->map_array[$series], $data);
        $this->_subseries[$subseries][$series] =& $this->map_array[$series];
      } else {
        if ($series > 0) {
          $this->_current_series_offset++;
        }
        $this->map_array[$this->_current_series_offset] = $data;
        $this->_subseries[$subseries][$this->_current_series_offset]
          =& $this->map_array[$this->_current_series_offset];
      }
    }
  }

  /**
   * Add a series of Dutch postal codes (only the four-digit part) to the basemap
   *
   * Postal codes are mapped to Kloeke codes, so the map is not more detailed.
   * The Kloeke code which contains the postal code is drawn
   *
   * @param array    array with four-digit postal codes
   * @param mixed    NULL or integer for the index number of the series (NULL = current series)
   * @param mixed    NULL or integer of the _subseries for the Kloeke codes (NULL = no _subseries)
   *                 Subseries have different _symbols from each other, the placemarks within them
   *                 have different colors per series but all the same symbol.
   */
  public function addPostalCodes($codes, $series = NULL, $subseries = NULL)
  {
    $this->db_connection = Kaart::createDBConnection(KAART_GEO_DB);
/*    if (is_null($this->db_connection)) {
      $this->createDBConnection(KAART_GEO_DB);
    }*/
    $kloeke_codes = $this->_postalcodes2kloekecodes($codes);
    $this->addData($kloeke_codes, $series, $subseries);
  }

  /**
   * Method to set symbol, style and size for a series of Kloeke codes
   *
   * @param string   string containing symbol (should be in $this->_possible_symbols)
   * @param string   string containing style (formatted as SVG "style" attribute)
   * @param int      integer containing size
   * @param mixed    NULL or integer for the index number of the series (NULL = current series)
   */
  public function setFullSymbol($symbol, $size, $style, $offset = NULL)
  {
    $this->setSymbol($symbol, $offset);
    $this->setStyle($style, $offset);
    $this->setSize($size, $offset);
  }

  /**
   * Set symbol for the current series of Kloeke codes
   *
   * Symbol can be set separately if not set with  setFullSymbol()
   *
   * @param string   string containing symbol (should be in $this->_possible_symbols)
   * @param mixed    NULL or integer for the index number of the series (NULL = current series)
   */
  public function setSymbol($symbol, $offset = NULL)
  {
    if (!in_array($symbol, $this->_possible_symbols)) {
      $this->_setSymbolKML($symbol, $offset);
      return;
    }

    // you only get here if $symbol is in $this->_possible_symbols
    if (is_null($offset)) {
      $this->_symbols[$this->_current_series_offset] = $symbol;
      $this->_kml_symbols[$this->_current_series_offset] = KAART_KML_DEFAULT_ICON_URL . '/' . $symbol . '.png';
    } else {
      $this->_symbols[$offset] = $symbol;
      $this->_kml_symbols[$offset] = KAART_KML_DEFAULT_ICON_URL . '/' . $symbol . '.png';
    }
  }

  /**
   * Set symbol for the current series of Kloeke codes for the KML map
   *
   *
   * @param string   string containing URL to symbol: content of <href> element under <Icon>
   * @param mixed    NULL or integer for the index number of the series (NULL = current series)
   */
  private function _setSymbolKML($symbol, $offset = NULL)
  {
    if (is_null($offset)) {
      $this->_kml_symbols[$this->_current_series_offset] = $symbol;
    } else {
      $this->_kml_symbols[$offset] = $symbol;
    }
  }

  /**
   * Set color for the current series of Kloeke codes for the KML map
   *
   *
   * @param string   string containing color (should be AABBGGRR hexadecimal)
   * @param mixed    NULL or integer for the index number of the series (NULL = current series)
   */
  private function _setColorKML($color, $offset = NULL)
  {
    if (is_null($offset)) {
      $this->_kml_colors[$this->_current_series_offset] = $color;
    } else {
      $this->_kml_colors[$offset] = $color;
    }
  }

  /**
   * Set color for the current series of Kloeke codes
   *
   * @param string   string containing color (AABBGGRR hex, #RRGGBB hex, or HTML color name)
   * @param mixed    NULL or integer for the index number of the series (NULL = current series)
   */
  public function setColor($color, $offset = NULL)
  {
    list($hex_color, $ge_color) = Kaart::translateColor(
      $color, $this->_symbol_defaults[$this->_symbols[$this->_current_series_offset]]['type']
    );
    $this->_setColorKML($ge_color, $offset);
    $this->setStyle(
      "fill:{$hex_color}; stroke:{$hex_color}; stroke-width:{$this->map_definitions['stroke_widths']['line']};",
      $offset
    );
  }

  /**
   * Set fill color with separate outline color for the current series of Kloeke codes
   *
   * The outline color has no effect on KML maps
   *
   * @param string   string containing fill color (AABBGGRR hex, #RRGGBB hex, or HTML color name)
   * @param string   string containing outline color (AABBGGRR hex, #RRGGBB hex, or HTML color name)
   * @param mixed    NULL or integer for the index number of the series (NULL = current series)
   */
  public function setOutlinedColor($fill, $outline, $offset = NULL)
  {
    list($fillcolor, $ge_color) = Kaart::translateColor(
      $fill, $this->_symbol_defaults[$this->_symbols[$this->_current_series_offset]]['type']
    );
    $this->_setColorKML($ge_color, $offset);
    $colors = Kaart::translateColor(
      $outline, $this->_symbol_defaults[$this->_symbols[$this->_current_series_offset]]['type']
    );
    $this->setStyle(
      "fill:{$fillcolor}; stroke:{$colors[0]}; stroke-width:{$this->map_definitions['stroke_widths']['filled']};",
      $offset
    );
  }


  /**
   * Set style for the current series of Kloeke codes
   *
   * Style can be set separately if not set with setFullSymbol()
   *
   * @param string   string containing style (formatted as SVG "style" attribute)
   * @param mixed    NULL or integer for the index number of the series (NULL = current series)
   */
  public function setStyle($style, $offset = NULL)
  {
    if (is_null($offset)) {
      $this->_styles[$this->_current_series_offset] = $style;
    } else {
      $this->_styles[$offset] = $style;
    }
    if (preg_match('/fill:\s*?(.+?);/', $style, $matches)) {
      $color = $matches[1];
      $colors = Kaart::translateColor(
        $color, $this->_symbol_defaults[$this->_symbols[$this->_current_series_offset]]['type']
      );
      $this->_setColorKML($colors[1], $offset);
    }
  }

  /**
   * Set size for the current series of Kloeke codes
   *
   * Size can be set separately if not set with setFullSymbol()
   *
   * @param int      integer containing size
   * @param mixed    NULL or integer for the index number of the series (NULL = current series)
   */
  public function setSize($size, $offset = NULL)
  {
    if (is_null($offset)) {
      $this->_sizes[$this->_current_series_offset] = $size;
    } else {
      $this->_sizes[$offset] = $size;
    }
  }

  /**
   * Set legend text for the current series of Kloeke codes
   *
   * @param string   string to be shown in the legend
   * @param mixed    NULL or integer for the index number of the series (NULL = current series)
   */
  public function setLegend($text, $offset = NULL)
  {
    if (is_null($offset)) {
      $this->_legends[$this->_current_series_offset] = $text;
    } else {
      $this->_legends[$offset] = $text;
    }
  }

  /**
   * Set fontsize for the legend
   *
   * @param int integer for desired fontsize (default is 10000)
   */
  public function setLegendFontsize($size)
  {
    $this->default_fontsize = intval($size);
  }

  /**
   * Moves a series of Kloeke codes to the background
   *
   * If a series of Kloeke codes should not be part of the legend it is part of the background.
   * With this method it is possible to move a particular series to the background. It will then no
   * longer be referred to in the legend.
   *
   * @param mixed    NULL or integer for index number of the series (NULL = current series)
   */
  public function moveDataToBackground($offset = NULL)
  {
    if (is_null($offset)) {
      $this->_background_data[] = $this->_current_series_offset;
    } else {
      $this->_background_data[] = $offset;
    }
  }

  /**
   * Add links to placemarks
   *
   * @param string string containing value of 'href' attribute for placemark. '%s' placeholder will be replaced with the Kloeke code of the placemark.
   * @param mixed  NULL or string containing value of 'target' attribute, if not empty
   * @param mixed  NULL or integer for index number of the series, if different from the default
   */
  public function setLink($href, $target = NULL, $offset = NULL)
  {
    if (is_null($offset)) {
      $this->_links[$this->_current_series_offset]['href'] = $href;
      $this->_links[$this->_current_series_offset]['target'] = $target;
    } else {
      $this->_links[$offset]['href'] = $href;
      $this->_links[$offset]['target'] = $target;
    }
  }

  /**
   * Add JavaScript events to placemarks
   *
   * Note: if setInteractive() is TRUE, the onmouseover and onmouseout events are already taken.
   *
   * @param string  string with Javascript code, value for onclick, onmouseover or onmouseout attribute. '%s' placeholder will be replaced with the Kloeke code of the placemark.
   * @param string  string with onclick, onmouseover or onmouseout event
   * @param mixed   NULL or ingeger for index number of the series, if different from the default
   */
  public function setJavaScript($string, $event = 'onclick', $offset = NULL)
  {
    if (is_null($offset)) {
      $this->_links[$this->_current_series_offset][$event] = $string;
    } else {
      $this->_links[$offset][$event] = $string;
    }
  }

  /**
   * Set which parts of the basemap should be drawn
   *
   * If this is not used, the complete basemap is drawn. See for legal part names Kaart::getPossibleParts().
   * Everything else will be ignored.
   *
   * @param array array with parts (strings).
   */
  public function setParts($parts)
  {
    foreach ($parts as $part) {
      if (in_array($part, $this->_possible_parts)) {
        $this->parts[] = $part;
      }
    }
  }

  /**
   * Returns an array with possible parts of the basemap (taken from config file)
   *
   * @return array array with parts
   */
  public function getPossibleParts()
  {
    return $this->_possible_parts;
  }

  /**
   * Returns an array with possible placemarks (Kloeke codes) for the basemap (taken from database)
   *
   * @param mixed $complete
   *
   * @return array associative array with kloeke code => placename
   */
  public function getPossiblePlacemarks($complete = NULL)
  {
    $retval = '';
    $this->db_connection = Kaart::createDBConnection(KAART_GEO_DB);
    mysqli_query($this->db_connection,'SET NAMES utf8');
    if (is_null($complete)) {
      $query = 'SELECT CONVERT(kloeke_code1 USING utf8), CONVERT(plaats USING utf8) FROM geo.kloeke';
      $result = mysqli_query($this->db_connection, $query);
      if ($result) {
        $retval = array();
        while ($rij = mysqli_fetch_row($result)) {
          $retval[$rij[0]] = $rij[1];
        }
      }
    } else {
      $query = 'SELECT CONVERT(kloeke_code1 USING utf8) AS kloekecode, CONVERT(plaats USING utf8) AS placename, RD_x AS rd_x, RD_y AS rd_y, lat, lng FROM geo.kloeke';
      $result = mysqli_query( $this->db_connection, $query);
      if ($result) {
        $retval = array();
        while ($rij = mysqli_fetch_assoc($result)) {
          $retval[] = $rij;
        }
      }
    }
    return $retval;
  }

  /**
   * Set the desired map type
   *
   * Two types are possible: 'standard', the default: all _symbols are drawn
   * at the same size; 'frequency': placemarks which occur more than once in a series
   * are drawn larger
   *
   * @param string string with desired type
   */
  public function setMapType($type)
  {
    if (in_array($type, $this->_possible_types)) {
      $this->_type = $type;
    }
  }

  /**
   * Whether counts of overlapping placemarks between different series should be shown in the legend
   *
   * @param bool boolean TRUE or FALSE = combinations on or off
   */
  public function setCombinations($value = TRUE)
  {
    $this->_combinations = (boolean)$value;
  }

  /**
   * For KML maps only: set the desired vertical distance in meters between _symbols from different series
   *
   * So: if input is '1000' the first series of placemarks is drawn at altitude 0 (directly on earth surface),
   * the second one at an altitude of 1000 meters, the third at 2000 meters, etc. Default is 50 meters
   *
   * @param int integer with vertical distance in meters between _symbols from different series
   */
  public function setAltitudeDifference($difference)
  {
    $this->_altitudedifference = abs(intval($difference));
  }

  /**
   * Whether duplicates within the same series of Kloeke codes should be removed or not
   *
   * @param bool boolean to remove duplicates or not. Default is FALSE
   */
  public function setRemoveDuplicates($bool)
  {
    $this->_remove_duplicates = (boolean)$bool;
  }

  /**
   * Return the Kloeke codes for which no coordinates were found
   *
   * @return array array with invalid Kloeke codes
   */
  public function getInvalidKloekeCodes()
  {
    return $this->_invalid_kloekecodes;
  }

  /**
   * Possibility to insert an extra background (overlay) between basemap and Kloeke codes
   *
   * Must be one of $this->possible_backgrounds, taken from config file
   *
   * @param mixed string (name of legal background) or array (array of names of possible backgrounds)
   */
  public function setBackground($background)
  {
    if (is_string($background) && in_array($background, $this->possible_backgrounds)) {
      $this->backgrounds[] = $background;
    } elseif (is_array($background)) {
      foreach ($background as $bg) {
        if (is_string($bg) && in_array($bg, $this->possible_backgrounds)) {
          $this->backgrounds[] = $bg;
        }
      }
    }
  }

  /**
   * Translates postal codes to Kloeke codes
   *
   * @param $codes array with four-digit postal codes
   *
   * @return array with Kloeke codes
   */
  private function _postalcodes2kloekecodes($codes)
  {
    $kloeke_codes = array();
    $codes = array_map('intval', $codes);
    $postcodestring = join(',', $codes);
    $query = str_replace('##POSTCODE_PLACEHOLDER##', $postcodestring, KAART_POSTCODE_QUERY);
    $result = mysqli_query($this->db_connection, $query);
    while ($rij = mysqli_fetch_row($result)) {
      $kloeke_codes[] = $rij[0];
    }

    return $kloeke_codes;
  }

  /**
   * Creates a map with the different series of placemarks ordered into _subseries.
   *
   * Called after the show(), fetch() or saveAsFile() method from Kaart is called.
   * Subseries have different _symbols from each other, the placemarks within them
   * have different colors per series but all the same symbol
   *
   * @internal
   *
   * @param string string containng format of the map (svg, png, gif, jpeg, kml)
   */
  public function createMapSubseries($format = 'svg')
  {
    $colors = $this->_extra_colors;

    // count from 0 without holes
    $this->_subseries = array_values($this->_subseries);

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
      'links' => $this->_links,
      'backgrounds' => $this->backgrounds
    );
    switch ($format) {

    case 'svg':
      $this->svg = new SVG($parameters);
      if (!empty($this->map_array)) {
        foreach ($this->_subseries as $subseries => $reeksen) {
          $symbol = $this->_possible_symbols[$subseries];
          $color_index = 0;
          foreach ($reeksen as $offset => $gegevens) {
            $style = $this->_getStyle($colors, $symbol, $color_index);
            $size = $this->_getSymbolSizes($gegevens, $offset, 'svg');
            list($link, $legend) = $this->_getSeriesParameters($offset);
            $invalid_kloekes = $this->svg->drawSeries(
              $gegevens, $this->_symbols[$offset], $style, $size, $link, $legend, $this->default_fontsize,
              $this->db_connection
            );
            $this->_invalid_kloekecodes = array_merge($this->_invalid_kloekecodes, $invalid_kloekes);
            $color_index++;
          }
        }
      }
      $this->svg->insertCopyrightStatement();
      break;

    case 'bitmap':
      $this->bitmap = new Bitmap($parameters);
      if (empty($this->map_array)) {
        return;
      }

      foreach ($this->_subseries as $subseries => $reeksen) {

        $symbol = $this->_possible_symbols[$subseries];
        $color_index = 0;

        foreach ($reeksen as $offset => $gegevens) {
          $style = $this->_getStyle($colors, $symbol, $color_index);
          $size = $this->_getSymbolSizes($gegevens, $offset, 'bitmap');
          list($link, $legend) = $this->_getSeriesParameters($offset);
          $invalid_kloekes = $this->bitmap->drawSeries(
            $gegevens, $symbol, $style, $size, $link, $legend, $this->default_fontsize,
            $this->db_connection
          );
          $this->_invalid_kloekecodes = array_merge($this->_invalid_kloekecodes, $invalid_kloekes);
          $color_index++;
        }
      }
      break;

    case 'kml':
      $parameters['kml_lookat'] = $this->map_definitions['kml_lookat'];
      $parameters['kml_defaults'] = $this->map_definitions['kml_defaults'];
      $this->kml = new KML($parameters);
      if (!empty($this->map_array)) {
        $shape_index = 0;
        foreach ($this->_subseries as $reeksen) {
          $color_index = 0;
          foreach ($reeksen as $offset => $gegevens) {
            $size = $this->_getSymbolSizes($gegevens, $offset, 'kml');
            list($link, $legend) = $this->_getSeriesParameters($offset);
            $invalid_kloekes = $this->kml->drawSeriesKML(
              $offset, $gegevens, $this->_kml_colors[$color_index], $this->_kml_symbols[$shape_index], $size,
              $link, $legend, $this->_altitudedifference, $this->db_connection
            );
            $this->_invalid_kloekecodes = array_merge($this->_invalid_kloekecodes, $invalid_kloekes);
            $color_index++;
          }
          $shape_index++;
        }
      }
      break;

    case 'json':

      $parameters['db_connection'] = $this->db_connection;
      $parameters['_subseries'] = array();

      foreach ($this->_subseries as $subseries => $reeksen) {
        $symbol = $this->_possible_symbols[$subseries];
        $color_index = 0;
        foreach ($reeksen as $offset => $gegevens) {
          $style = $this->_getStyle($colors, $symbol, $color_index);
          $size = $this->_getSymbolSizes($gegevens, $offset, 'svg');
          list($link, $legend) = $this->_getSeriesParameters($offset);
          $parameters['_subseries'][$subseries][$offset]['data'] = $gegevens;
          $parameters['_subseries'][$subseries][$offset]['symbol'] = $symbol;
          $parameters['_subseries'][$subseries][$offset]['style'] = $style;
          $parameters['_subseries'][$subseries][$offset]['size'] = $size;
          $parameters['_subseries'][$subseries][$offset]['link'] = $link;
          $parameters['_subseries'][$subseries][$offset]['legend'] = $legend;
          $color_index++;
        }
      }
      $this->json = new JSON($parameters);
      break;
    }

  }

  /**
   * Draws, successively, a basemap and the placemarks (with KML only the placemarks)
   * Called after the show(), fetch() or saveAsFile() method from Kaart is called.
   *
   * @internal
   *
   * @param string string with format of the map (svg, png, gif, jpeg, kml, json)
   */
  public function createMap($format = 'svg')
  {
    // so that no errors occur if you want to draw more than $this->_max_default_symbols default _symbols
    foreach ($this->map_array as $offset => $gegevens) {
      if (!array_key_exists($offset, $this->_symbols)) {

        // smaller symbol (is drawn on top of the previous one)
        // offsets 0 - 10: 1
        // offset 11 - 21: 0.95
        // offset 22 - 32: 0.9
        // etc.
        $factor = 1 - ((floor($offset / $this->_max_default_symbols) / 2) / 10);
        $this->_sizes[$offset] = $this->_default_symbolsize * $factor;

        // same symbol as the series $this->_max_default_symbols lower
        // in other words, the list of _symbols repeats in the same order
        $this->_symbols[$offset] = $this->_symbols[$offset - $this->_max_default_symbols];
        $this->_kml_symbols[$offset] = $this->_kml_symbols[$offset - $this->_max_default_symbols];

        // but with a changed color
        $this->_styles[$offset] = $this->_changed_style($this->_symbols[$offset]);
        $this->_kml_colors[$offset] = $this->_changed_color($this->_kml_symbols[$offset]);
      }
    }

    if ($this->_remove_duplicates) {
      foreach ($this->map_array as $offset => $gegevens) {
        $this->map_array[$offset] = array_unique($gegevens);
      }
    }

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
      'links' => $this->_links,
      'backgrounds' => $this->backgrounds
    );

    switch ($format) {

    case 'svg':
      $this->svg = new SVG($parameters);
      if (!empty($this->map_array)) {
        foreach ($this->map_array as $offset => $gegevens) {
          $size = $this->_getSymbolSizes($gegevens, $offset, 'svg');
          list($link, $legend) = $this->_getSeriesParameters($offset);
          $invalid_kloekes = $this->svg->drawSeries(
            $gegevens, $this->_symbols[$offset], $this->_styles[$offset], $size, $link, $legend,
            $this->default_fontsize, $this->db_connection
          );
          $this->_invalid_kloekecodes = array_merge($this->_invalid_kloekecodes, $invalid_kloekes);
        }
        if ($this->_combinations) {
          foreach ($this->_getCombinations() as $reeksnummers => $aantal) {
            $this->svg->drawCombination(
              $reeksnummers, $aantal, $this->_sizes, $this->default_fontsize, $this->_symbols, $this->_styles
            );
          }
        }
      }
      $this->svg->insertCopyrightStatement();
      break;

    case 'bitmap':
      $this->bitmap = new Bitmap($parameters);
      if (empty($this->map_array)) {
        return;
      }

      foreach ($this->map_array as $offset => $gegevens) {
        $size = $this->_getSymbolSizes($gegevens, $offset, 'bitmap');
        list($link, $legend) = $this->_getSeriesParameters($offset);
        $invalid_kloekes = $this->bitmap->drawSeries(
          $gegevens, $this->_symbols[$offset], $this->_styles[$offset], $size, $link, $legend,
          $this->default_fontsize, $this->db_connection
        );
        $this->_invalid_kloekecodes = array_merge($this->_invalid_kloekecodes, $invalid_kloekes);
      }

      if ($this->_combinations) {
        foreach ($this->_getCombinations() as $reeksnummers => $aantal) {
          $this->bitmap->drawCombination(
            $reeksnummers, $aantal, $this->_sizes, $this->default_fontsize, $this->_symbols, $this->_styles
          );
        }
      }
      break;

    case 'kml':
      $parameters['kml_lookat'] = $this->map_definitions['kml_lookat'];
      $parameters['kml_defaults'] = $this->map_definitions['kml_defaults'];
      $this->kml = new KML($parameters);
      if (empty($this->map_array)) {
        return;
      }

      foreach ($this->map_array as $offset => $gegevens) {
        $size = $this->_getSymbolSizes($gegevens, $offset, 'kml');
        list($link, $legend) = $this->_getSeriesParameters($offset);
        $invalid_kloekes = $this->kml->drawSeriesKML(
          $offset, $gegevens, $this->_kml_colors[$offset], $this->_kml_symbols[$offset], $size, $link,
          $legend, $this->_altitudedifference, $this->db_connection
        );
        $this->_invalid_kloekecodes = array_merge($this->_invalid_kloekecodes, $invalid_kloekes);
      }
      break;

    case 'json':
      $parameters['db_connection'] = $this->db_connection;
      $parameters['map_array'] = $this->map_array;
      $parameters['_symbols'] = $this->_symbols;
      $parameters['styles'] = $this->_styles;
      $parameters['sizes'] = array();
      $parameters['links'] = array();
      $parameters['legends'] = array();
      foreach ($this->map_array as $offset => $gegevens) {
        $parameters['sizes'][$offset] = $this->_getSymbolSizes($gegevens, $offset, 'svg');
        list($link, $legend) = $this->_getSeriesParameters($offset);
        $parameters['links'][$offset] = $link;
        $parameters['legends'][$offset] = $legend;
      }
      $this->json = new JSON($parameters);
      break;
    }
  }

  private function _getSeriesParameters($offset)
  {
    if (!array_key_exists($offset, $this->_links)) {
      $this->_links[$offset] = array();
    }
    if (!array_key_exists($offset, $this->_legends)) {
      $this->_legends[$offset] = FALSE;
    }
    return array($this->_links[$offset], $this->_legends[$offset]);
  }

  private function _getStyle($colors, $symbol, $color_index)
  {
    if ($this->_symbol_defaults[$symbol]['type'] == 'filled') {
      $stroke = 'black';
    } else {
      $stroke = $colors[$color_index];
    }

    return "fill:{$colors[$color_index]}; stroke:{$stroke}; stroke-width:{$this->_symbol_defaults[$symbol]['stroke-width']};";
  }


  /**
   * Returns the size with which a placemark should be drawn
   *
   * @param array  array with Kloeke codes
   * @param int    integer with index number of the series of Kloeke code
   * @param string string with format of the map (svg, png, gif, jpeg, kml)
   *
   * @return mixed associative array with Kloeke codes as keys and sizes as values (for frequency maps) or int (uniform size of the current series, for standard maps)
   */
  private function _getSymbolSizes($kloeke_array, $offset, $format)
  {
    if (array_key_exists($offset, $this->_sizes)) {
      $size = $this->_sizes[$offset];
    } else {
      if ($format == 'kml') {
        $size = $this->_kml_default_icon_size;
      } else {
        $size = $this->_default_symbolsize;
      }
    }

    // default SVG/Bitmap symbol size == 5000
    // default KML icon scale == 0.6
    if ($format == 'kml' && $size > 100) {
      // can never be right, so conversion is needed
      // 1 digit after decimal point
      $size = number_format($size / 8333, 1);

    } elseif ($format != 'kml' && $size < 100) {
      // can never be right, so conversion is needed
      $size = round(($size * 8333) / 1000) * 1000;
    }

    // KML doesn't have a frequency type yet
    if ($this->_type == 'standard' || $format == 'kml') {
      return $size;
    }

    // if $this->_type == 'frequency'
    $groottes = array();
    $aantallen = array_count_values($kloeke_array);
    foreach ($kloeke_array as $kloeke_nr) {
      $groottes[$kloeke_nr] = $aantallen[$kloeke_nr] * $size;
    }
    return $groottes;
  }

  /**
   * Return an SVG style attribute with changed colors for use in SVG and bitmap maps
   *
   * @param string string with symbol which needs a different color
   *
   * @return string SVG style attribute with changed colors (first unused color with input symbol)
   */
  private function _changed_style($inputsymbol)
  {
    $used_colors = array();
    $colors = array();
    foreach ($this->_symbols as $offset => $symbol) {
      if (array_key_exists($offset, $this->_styles)) {
        preg_match('/fill:\s*?(.+?);/', $this->_styles[$offset], $matches);
        $colors[] = $matches[1];
      }
      if ($symbol == $inputsymbol && array_key_exists($offset, $this->_styles)) {
        preg_match('/fill:\s*?(.+?);/', $this->_styles[$offset], $matches);
        $used_colors[] = $matches[1];
      }
    }

    $unused_colors = array_values(array_diff(array_unique($colors), $used_colors));

    if ($this->_symbol_defaults[$inputsymbol]['type'] == 'fill') {
      $stroke = 'black';
    } else {
      $stroke = $unused_colors[0];
    }

    return "fill:{$unused_colors[0]}; stroke:{$stroke}; stroke-width:{$this->_symbol_defaults[$inputsymbol]['stroke-width']};";
  }

  /**
   * Return a changed color for use in KML maps
   *
   * @param string string with symbol which needs a different color
   *
   * @return string changed color for input symbol (first unused color with input symbol)
   */
  private function _changed_color($inputsymbol)
  {
    $used_colors = array();
    foreach ($this->_kml_symbols as $offset => $symbol) {
      if ($symbol == $inputsymbol && array_key_exists($offset, $this->_kml_colors)) {
        $used_colors[] = $this->_kml_colors[$offset];
      }
    }
    $unused_colors = array_values(array_diff($this->_kml_colors, $used_colors));
    return $unused_colors[0];
  }

  /**
   * Returns an array with as keys comma-separated index numbers of series and as values the number of overlapping placemarks
   *
   * @return array array with counts
   */
  private function _getCombinations()
  {
    // series which belong to the background are not part of the legend
    $offsets = array();
    foreach (array_keys($this->map_array) as $offset) {
      if (!in_array($offset, $this->_background_data)) {
        $offsets[] = $offset;
      }
    }

    $possible_combinations = $this->_pc_array_power_set($offsets);
    $combinations = array();

    foreach ($possible_combinations as $combination) { // $combination is an array with a possible combination
      $key = join(',', $combination);
      $map_array_onderdelen = array();
      foreach ($combination as $offset) {
        // reference to a list of Kloeke codes from $this->map_array
        $map_array_onderdelen[] =& $this->map_array[$offset];
      }
      // call_user_func_array: so that I can call array_intersect with a variable number of parameters
      $doorsnede = call_user_func_array('array_intersect', $map_array_onderdelen);
      $aantal = count($doorsnede);
      if ($aantal > 0) {
        $combinations[$key] = $aantal;
      }
    }

    return $combinations;
  }

  /**
   * Returns all possible combinations (of at least two elements) of all elements from an array
   *
   * Based on code from: Sklar, David and Adam Trachtenberg. PHP Cookbook. O'Reilly, 2002, ISBN 1-56592-681-1, p. 109
   *
   * @param array
   *
   * @return array array with all possible combinations of elements >= 2
   */
  private function _pc_array_power_set($array)
  {
    // initialize by adding the empty set
    $results = array(array());

    foreach ($array as $element) {
      foreach ($results as $combination) {
        array_push($results, array_merge(array($element), $combination));
      }
    }

    // inserted by Jan Pieter Kunst: all members with less than two elements
    // can be removed again
    foreach ($results as $key => $value) {
      unset($results[$key]);
      if (count($value) >= 2) {
        $results[$key] = array_reverse(
          $value
        ); // array_reverse(): otherwise the output is the reverse of the input
      }
    }

    return array_values($results); // numeric array counting from 0 without holes
  }

  /**
   * Initializes settings for the map based on the provided ini file
   */
  protected function _parseIniFile()
  {
    $this->map_definitions = parse_ini_file($this->kaart_ini_file, TRUE);
    $this->_possible_parts = array_keys($this->map_definitions['kaart_parts']);
    $this->possible_backgrounds = array_keys($this->map_definitions['kaart_backgrounds']);
    $this->_possible_symbols = array_keys($this->map_definitions['symbol_styles']);
    $this->default_fontsize = $this->map_definitions['map_settings']['svg_default_fontsize'];
    $this->_default_symbolsize = $this->map_definitions['map_settings']['svg_default_symbolsize'];
    $this->width = $this->map_definitions['map_settings']['width'];
    $this->height = $this->width * $this->map_definitions['map_settings']['height_factor'];
    $this->_symbols = $this->_possible_symbols;
    $this->_max_default_symbols = count($this->_possible_symbols);
    $this->_styles = array_values($this->map_definitions['symbol_styles']);
    foreach (array_keys($this->map_definitions['symbol_styles']) as $symbol) {
      // 'line' or 'filled'
      $type = $this->map_definitions['symbol_types'][$symbol];
      $this->_symbol_defaults[$symbol]['type'] = $type;
      $this->_symbol_defaults[$symbol]['stroke-width'] = $this->map_definitions['stroke_widths'][$type];
    }
    $this->_kml_default_icon_size = $this->map_definitions['map_settings']['kml_default_icon_size'];
    $this->_kml_symbols = array_keys($this->map_definitions['kml_icons']);
    $this->_kml_colors = array_values($this->map_definitions['kml_icons']);
    foreach ($this->_kml_symbols as $offset => $symbol) {
      $this->_kml_symbols[$offset] = KAART_KML_DEFAULT_ICON_URL . '/' . $symbol . '.png';
    }
    foreach ($this->_kml_colors as $offset => $color) {
        $colors = Kaart::translateColor($color);
        $this->_setColorKML($colors[1], $offset);
    }
  }

  /**
   * @internal
   * @return bool
   */
  public function containsSubseries()
  {
    if (empty($this->_subseries)) {
      return FALSE;
    } else {
      return TRUE;
    }
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
