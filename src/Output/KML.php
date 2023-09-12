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
 * Class to generate the KML version of a map
 *
 * @internal
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2006-2007 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU GPL version 2
 * @package   Kaart
 */
class KML extends Image
{

  /**
   * @var object DOM object dat de KML bevat
   * @access public
   */
  public $dom;
  /**
   * @var object DOMNode object voor het Document element
   * @access private
   */
  private $_document;
  /**
   * @var array <Folder> elementen
   * @access private
   */
  private $_folders = array();
  /**
   * @var array <LookAt> elementen
   * @access private
   */
  private $_lookat = array();
  /**
   * @var boolean is there a basemap drawn on top of Google Maps?
   * @access private
   */
  private $_basemap = FALSE;

  private $_default_polygon_style_name;
  private $_default_linestyle_linewidth;
  private $_default_linestyle_color;
  private $_default_polystyle_color;

  /**
   * De constructor
   *
   * Maakt de grondkaart in KML
   *
   * @access public
   *
   * @param array array with parameters for map construction
   */
  public function __construct($parameters)
  {
    parent::__construct($parameters);

    $this->_lookat = $parameters['kml_lookat'];
    $this->_default_polygon_style_name = $parameters['kml_defaults']['default_polygon_style_name'];
    $this->_default_linestyle_linewidth = $parameters['kml_defaults']['default_linestyle_linewidth'];
    $this->_default_linestyle_color = $parameters['kml_defaults']['default_linestyle_color'];
    $this->_default_polystyle_color = $parameters['kml_defaults']['default_polystyle_color'];
    if (isset($parameters['basemap']) && is_bool($parameters['basemap'])) {
      $this->_basemap = $parameters['basemap'];
    }
    if (array_key_exists('highlighted', $parameters)) {
      $highlighted = $parameters['highlighted'];
    } else {
      $highlighted = array();
    }
    if (array_key_exists('links', $parameters)) {
      $links = $parameters['links'];
    } else {
      $links = array();
    }
    if (array_key_exists('tooltips', $parameters)) {
      $tooltips = $parameters['tooltips'];
    } else {
      $tooltips = array();
    }
    $this->dom = new \DomDocument('1.0', 'UTF-8');
    $this->dom->formatOutput = TRUE;
    $kml = $this->dom->createElementNS('http://earth.google.com/kml/2.1', 'kml');
    $this->_document = $this->dom->createElement('Document');
    $this->dom->appendChild($kml);
    $kml->appendChild($this->_document);

    $this->_setTitle();
    $this->_setLookAt();
    if ($this->_basemap) {
      $this->_drawBasemapKML($highlighted, $links, $tooltips, $parameters['map_definitions']);
    }
  }

  /**
   * Maakt een <Folder> element
   *
   * Om verschillende reeksen in onder te brengen: een reeks per Folder
   *
   * @param $offset int   offset van de reeks die in de Folder moet komen
   * @param $color  mixed kleur voor het ikoon, default indien NULL
   * @param $shape  mixed vorm voor het ikoon, default indien NULL
   * @param $size
   */
  public function createFolder($offset, $color, $shape, $size)
  {
    $this->_folders[$offset] = $this->dom->createElement('Folder');

    $normal_style_id = $this->_createStyleElement($offset, 'normal', $color, $shape, $size);
    $highlight_style_id = $this->_createStyleElement($offset, 'highlight', $color, $shape, $size);

    $stylemap = $this->dom->createElement('StyleMap');
    $stylemap->appendChild($this->_createPair($normal_style_id, 'normal'));
    $stylemap->appendChild($this->_createPair($highlight_style_id, 'highlight'));

    $stylemap->setAttribute('id', 'stylemap_' . $offset);

    $this->_document->appendChild($stylemap);
    $this->_document->appendChild($this->_folders[$offset]);
  }

  /**
   * Maakt een <Pair> element
   *
   * Maakt een <Pair> element, verwijzend naar ofwel de <Style> voor de
   * 'normal' versie van een Placemark, ofwel de <Style> voor de 'highlight' (rollover) versie
   *
   * @param $style_id string id van de <Style> waarnaar verwezen moet worden
   * @param $keyname  string 'normal' of 'higlight' (gereserveerde woorden)
   *
   * @return DOMnode reference naar het gemaakte <Pair> element
   */
  private function _createPair($style_id, $keyname = 'normal')
  {
    $pair = $this->dom->createElement('Pair');
    $key = $this->dom->createElement('key');
    $key->appendChild($this->dom->createTextNode($keyname));
    $styleurl = $this->dom->createElement('styleUrl');
    $styleurl->appendChild($this->dom->createTextNode('#' . $style_id));
    $pair->appendChild($key);
    $pair->appendChild($styleurl);

    return $pair;
  }

  /**
   * Maakt een <Style> element
   *
   * Maakt een <Style> element, bedoeld voor ofwel
   * 'normal' versie van een Placemark, ofwel 'highlight' (rollover) versie
   * in de 'highlight' wordt het label (de plaatsnaam) getoond
   *
   * @param $offset    int    offset van de reeks waarvoor de stijl bedoeld is
   * @param $key       string 'normal' of 'higlight' (gereserveerde woorden)
   * @param $iconcolor mixed  kleur voor het ikoon, default indien NULL
   * @param $iconshape mixed  vorm voor het ikoon, default indien NULL
   * @param $iconscale mixed  grootte voor het ikoon, default indien NULL
   *
   * @return string id van het <Style> element
   */
  private function _createStyleElement($offset, $key, $iconcolor, $iconshape, $iconscale)
  {
    $style = $this->dom->createElement('Style');
    $style_id = 'style_' . $offset . '_' . $key;

    $style->setAttribute('id', $style_id);

    $iconstyle = $this->dom->createElement('IconStyle');

    $color = $this->dom->createElement('color');
    $color->appendChild($this->dom->createTextNode($iconcolor));
    $iconstyle->appendChild($color);

    $icon = $this->dom->createElement('Icon');
    $href = $this->dom->createElement('href');
    $icon->appendChild($href);
    $iconstyle->appendChild($icon);

    $scale = $this->dom->createElement('scale');
    $scale->appendChild($this->dom->createTextNode($iconscale));
    $iconstyle->appendChild($scale);

    $href->appendChild($this->dom->createTextNode($iconshape));

    $style->appendChild($iconstyle);

    // Labels niet zichtbaar op de kaart zelf
    $labelstyle = $this->dom->createElement('LabelStyle');
    $color = $this->dom->createElement('color');

    if ($key == 'normal') {
      $color->appendChild($this->dom->createTextNode('00000000'));
    } elseif ($key == 'higlight') {
      $color->appendChild($this->dom->createTextNode('ffffffff'));
    }

    $labelstyle->appendChild($color);
    $style->appendChild($labelstyle);

    $this->_document->appendChild($style);

    return $style_id;
  }

  /**
   * Voegt een <name> toe aan Folder nummer $offset
   *
   * @param $string string titel van de reeks
   * @param $offset int volgnummer van de reeks
   */
  public function setFolderName($string, $offset)
  {
    $name = $this->dom->createElement('name');
    $name->appendChild($this->dom->createTextNode($string));
    /** @noinspection PhpUndefinedMethodInspection */
    $this->_folders[$offset]->appendChild($name);
  }

  /**
   * Adds a series of Kloeke codes (in a <Folder> element) to a KML map
   *
   * @param $offset    int    integer with offset (index number) of the series
   * @param $gegevens  array  array with Kloeke codes
   * @param $color     string string with color of the symbol for the series
   * @param $shape     string string for shape (name) of the symbol for the series
   * @param $size
   * @param $link
   * @param $legend
   * @param $altitudedifference
   * @param $db_connection
   *
   * @return array
   */
  public function drawSeriesKML(
    $offset, $gegevens, $color, $shape, $size, $link, $legend, $altitudedifference, $db_connection
  )
  {
    $invalid_kloekecodes = array();
    // create a <Folder>
    $this->createFolder($offset, $color, $shape, $size);
    // Add legend text as the name of the <Folder>
    if ($legend !== FALSE) {
      $this->setFolderName(
        $legend . ' (' . count($gegevens) . ')', $offset
      );
    }
    foreach ($gegevens as $kloeke_nr) {
      list($x, $y) = Kaart::getCoordinates($kloeke_nr, $db_connection);

      // invalid Kloeke code, ignore silently and save for error report
      if ($x == 0 && $y == 0) {
        $invalid_kloekecodes[] = $kloeke_nr;
        continue;
      }

      $plaatsnaam = Kaart::getPlaatsnaam($kloeke_nr, $db_connection);
      $this->setPlacemark(
        array($x, $y), $kloeke_nr, $plaatsnaam, $offset, $altitudedifference, $link
      );
    }
    return $invalid_kloekecodes;
  }

  /**
   * @param $coordinaten
   * @param $kloeke_nr
   * @param $plaatsnaam
   * @param $offset
   * @param $altitudedifference
   * @param $link_array
   */
  public function setPlacemark($coordinaten, $kloeke_nr, $plaatsnaam, $offset, $altitudedifference, $link_array)
  {
    list($x, $y) = $coordinaten;
    list($noorderbreedte, $oosterlengte) = $this->rd2latlong($x, $y);
    $placemark = $this->dom->createElement('Placemark');
    $name = $this->dom->createElement('name');
    $placemark->appendChild($name);
    $name->appendChild($this->dom->createTextNode("$plaatsnaam ($kloeke_nr)"));

    $point = $this->dom->createElement('Point');
    $placemark->appendChild($point);
    $altitudemode = $this->dom->createElement('altitudeMode');
    $altitudemode->appendChild($this->dom->createTextNode('relativeToGround'));
    $point->appendChild($altitudemode);

    $coordinates_element = $this->dom->createElement('coordinates');
    $point->appendChild($coordinates_element);
    $icon_altitude = $offset * $altitudedifference;
    $coordinates_element->appendChild(
      $this->dom->createTextNode("{$oosterlengte},{$noorderbreedte},{$icon_altitude}")
    );

    $styleurl = $this->dom->createElement('styleUrl');
    $styleurl->appendChild($this->dom->createTextNode('#stylemap_' . $offset));
    $placemark->appendChild($styleurl);

    $descriptiontext = '';

    if (!empty($link_array)) {
      $href = sprintf($link_array['href'], $kloeke_nr);
      if (isset($link_array['target'])) {
        $target = $link_array['target'];
        $descriptiontext
          .= '<a href="' . $this->escapeXMLString($href) . '" target="' . $this->escapeXMLString($target) . '">'
          . $this->escapeXMLString($href) . '</a>';
      } else {
        $descriptiontext
          .= '<a href="' . $this->escapeXMLString($href) . '">' . $this->escapeXMLString($href) . '</a>';
      }
    }

    // legend text in description for each placemark
    $fc = $this->_folders[$offset]->firstChild;
    // there is a legend text for this series
    if ((!is_null($fc)) && $fc->nodeName == 'name') {
      $text = $fc->nodeValue;
      // count of items between parentheses trimmed from text
      $space_before_last_parenthesis_open = strrpos($text, '(') - 1;
      $descriptiontext .= '<p>' . htmlentities(substr($text, 0, $space_before_last_parenthesis_open)) . '</p>';
    }

    if (!empty($descriptiontext)) {
      $description = $this->dom->createElement('description');
      $cdata = $this->dom->createCDATASection($descriptiontext);
      $description->appendChild($cdata);
      $placemark->appendChild($description);
    }

    /** @noinspection PhpUndefinedMethodInspection */
    $this->_folders[$offset]->appendChild($placemark);
  }

  /**
   * @param               $coordinates
   * @param               $name
   * @param               $id
   * @param               $link
   * @param               $tooltip
   * @param               $polygoncolor
   * @param string        $outline
   * @param string        $strokefactor
   * @param DOMNode       $folder
   */
  private function _createPolygon(
    $coordinates, $name, $id, $link, $tooltip, $polygoncolor, $outline, $strokefactor, $folder
  )
  {
    $coordinatestring = '';

    while ($coordinates) {
      $x = array_shift($coordinates);
      $y = array_shift($coordinates);
      list($noorderbreedte, $oosterlengte) = $this->rd2latlong($x, $y);
      $coordinatestring .= "{$oosterlengte},{$noorderbreedte},0 ";
    }

    if ($coordinatestring != '') {

      $placemark = $this->dom->createElement('Placemark');

      if ($polygoncolor != '') {
        $highlightedpath = TRUE;
        $style = $this->dom->createElement('Style');
        $linestyle = $this->dom->createElement('LineStyle');
        $width = $this->dom->createElement('width');
        $color = $this->dom->createElement('color');

        if ($outline == '') {
          $outline = $this->_default_linestyle_color;
        }
        if ($strokefactor == '') {
          $linewidth = $this->_default_linestyle_linewidth;
        } else {
          $linewidth = $this->_default_linestyle_linewidth * $strokefactor;
        }
        $width->appendChild($this->dom->createTextNode($linewidth));
        $color->appendChild($this->dom->createTextNode($outline));
        $linestyle->appendChild($width);
        $linestyle->appendChild($color);
        $style->appendChild($linestyle);

        $polystyle = $this->dom->createElement('PolyStyle');
        $color = $this->dom->createElement('color');
        $color->appendChild($this->dom->createTextNode($polygoncolor));
        $polystyle->appendChild($color);
        $style->appendChild($polystyle);
        $placemark->appendChild($style);
      } else {
        $highlightedpath = FALSE;
        $styleUrl = $this->dom->createElement('styleUrl');
        $styleUrl->appendChild($this->dom->createTextNode('#' . $this->_default_polygon_style_name));
        $placemark->appendChild($styleUrl);
      }

      $name_elem = $this->dom->createElement('name');
      $placemark->appendChild($name_elem);
      $name_elem->appendChild($this->dom->createTextNode($name));

      $polygon = $this->dom->createElement('Polygon');
      $outerboundaryis = $this->dom->createElement('outerBoundaryIs');
      $linearring = $this->dom->createElement('LinearRing');
      $tessellate = $this->dom->createElement('tessellate');
      $tessellate->appendChild($this->dom->createTextNode('0'));
      $coordinates_elem = $this->dom->createElement('coordinates');
      $coordinates_elem->appendChild($this->dom->createTextNode($coordinatestring));
      $linearring->appendChild($tessellate);
      $linearring->appendChild($coordinates_elem);
      $outerboundaryis->appendChild($linearring);
      $polygon->appendChild($outerboundaryis);
      $placemark->appendChild($polygon);

      $infotext = array();
      if (!empty($this->link)) {
        if (!$this->linkhighlightedonly || ($this->linkhighlightedonly && $highlightedpath)) {
          $href = sprintf($this->link, $id);
          $tmp = '<a href="' . $this->escapeXMLString($href) . '"';
          if (!empty($this->target)) {
            $tmp .= ' target="' . $this->escapeXMLString($this->target) . '"';
          }
          $tmp .= '>' . $this->escapeXMLString($href) . '</a>';
          $infotext[] = $tmp;
        }
      }
      if ($link != '') {
        $tmp = '<a href="' . $this->escapeXMLString($link['href']) . '"';
        if (array_key_exists('target', $link)) {
          $tmp .= ' target="' . $this->escapeXMLString($link['target']) . '"';
        }
        $tmp .= '>' . $this->escapeXMLString($link['href']) . '</a>';
        $infotext[] = $tmp;
      }
      if ($tooltip != '') {
        $infotext[] = $tooltip;
      }

      if (!empty($infotext)) {
        $descriptiontext = '<p> ' . join('<br />', $infotext) . '</p>';
      }

      if (!empty($descriptiontext)) {
        $description = $this->dom->createElement('description');
        $cdata = $this->dom->createCDATASection($descriptiontext);
        $description->appendChild($cdata);
        $placemark->appendChild($description);
      }

      $folder->appendChild($placemark);
    }
  }

  /**
   * Zet de titel bij de kaart
   *
   * @access private
   */
  private function _setTitle()
  {
    if (empty($this->title)) {
      return;
    }

    $name = $this->dom->createElement('name');
    $name->appendChild($this->dom->createTextNode($this->title));
    $this->_document->appendChild($name);
  }

  /**
   * LookAt
   *
   * @access private
   */
  private function _setLookAt()
  {
    $lookat = $this->dom->createElement('LookAt');

    $longitude = $this->dom->createElement('longitude');
    $longitude->appendChild($this->dom->createTextNode($this->_lookat['longitude']));
    $lookat->appendChild($longitude);

    $latitude = $this->dom->createElement('latitude');
    $latitude->appendChild($this->dom->createTextNode($this->_lookat['latitude']));
    $lookat->appendChild($latitude);

    $altitude = $this->dom->createElement('altitude');
    $altitude->appendChild($this->dom->createTextNode($this->_lookat['altitude']));
    $lookat->appendChild($altitude);

    $range = $this->dom->createElement('range');
    $range->appendChild($this->dom->createTextNode($this->_lookat['range']));
    $lookat->appendChild($range);

    $tilt = $this->dom->createElement('tilt');
    $tilt->appendChild($this->dom->createTextNode($this->_lookat['tilt']));
    $lookat->appendChild($tilt);

    $heading = $this->dom->createElement('heading');
    $heading->appendChild($this->dom->createTextNode($this->_lookat['heading']));
    $lookat->appendChild($heading);

    $this->_document->appendChild($lookat);
  }

  /**
   * Draws the basemap
   *
   * @access private
   */
  private function _drawBasemapKML($highlighted, $links, $tooltips, $map_definitions)
  {

    $this->_setDefaultPolygonStyle();

    $map_lines_highlighted = array();
    $map_names_highlighted = array();

    /** @noinspection PhpIncludeInspection */
    require($this->kaart_paths_file);
    /** @var $map_copyright_string string defined in $paths_file */
    $this->map_copyright_strings[] = $map_copyright_string;


    /** @var $map_lines array associative array area code => area coordinates, defined in $this->paths_file */
    /** @var $map_names array associative array path code => path name, defined in $paths_file */
    /** @var $map_name string name of the collection in  $paths_file */
    foreach (array_keys($highlighted) as $path) {
      if (array_key_exists($path, $map_lines)) {
        $map_lines_highlighted[$path] = $map_lines[$path];
        unset($map_lines[$path]);
      }
      if (array_key_exists($path, $map_names)) {
        $map_names_highlighted[$path] = $map_names[$path];
        unset($map_names[$path]);
      }
    }
    $this->_createPolygons(array($map_lines, $map_names), array(), $links, $tooltips, $map_definitions, $map_name);
    if (!empty($map_lines_highlighted)) {
      $this->_createPolygons(
        array($map_lines_highlighted, $map_names_highlighted), $highlighted, $links, $tooltips, $map_definitions,
        $map_name . ' (highlighted)'
      );
    }

    foreach ($this->additional_paths_files as $file) {

      $map_lines_highlighted = array();
      $map_names_highlighted = array();

      /** @noinspection PhpIncludeInspection */
      require($file);
      /** @var $map_copyright_string string defined in $paths_file */
      $this->map_copyright_strings[] = $map_copyright_string;

      foreach (array_keys($highlighted) as $path) {
        if (array_key_exists($path, $map_lines)) {
          $map_lines_highlighted[$path] = $map_lines[$path];
          unset($map_lines[$path]);
        }
        if (array_key_exists($path, $map_names)) {
          $map_names_highlighted[$path] = $map_names[$path];
          unset($map_names[$path]);
        }
      }
      $this->_createPolygons(array($map_lines, $map_names), array(), $links, $tooltips, $map_definitions, $map_name);
      if (!empty($map_lines_highlighted)) {
        $this->_createPolygons(
          array($map_lines_highlighted, $map_names_highlighted), $highlighted, $links, $tooltips, $map_definitions,
          $map_name . ' (highlighted)'
        );
      }

    }
  }

  /**
   * @param $mapinfo
   * @param $highlighted
   * @param $links
   * @param $tooltips
   * @param $map_definitions
   * @param $map_name
   */
  private function _createPolygons($mapinfo, $highlighted, $links, $tooltips, $map_definitions, $map_name)
  {
    $map_lines = $mapinfo[0];
    $map_names = $mapinfo[1];
    $folder = $this->dom->createElement('Folder');
    $foldername = $this->dom->createElement('name');
    $foldername->appendChild($this->dom->createTextNode($map_name));
    $folder->appendChild($foldername);
    foreach ($map_lines as $name => $coordinates) {
      $highlight = $outline = $strokefactor = $link = $tooltip = '';
      // starts with 'g_' means that it is a municipality code
      // with 'corop_' a COROP code, with 'p_' a province code, with 'dial_' a dialect area code
      if (strpos($name, 'g_') === 0 || strpos($name, 'corop_') === 0 || strpos($name, 'p_') === 0
        || strpos($name, 'dial_') === 0
      ) {
        if (array_key_exists($name, $highlighted)) {
          if (is_string($highlighted[$name])) {
            $highlight = $highlighted[$name];
          } elseif (is_array($highlighted[$name])) {
            $highlight = $highlighted[$name]['fill'];
            $outline = $highlighted[$name]['outline'];
            $strokefactor = $highlighted[$name]['strokewidth'];
          }
        } else {
          if (array_key_exists('kaart_empty', $map_definitions)
            && array_key_exists(
              $name, $map_definitions['kaart_empty']
            )
          ) {
            $pathtype = $map_definitions['kaart_empty'][$name];
            $strokefactor = $map_definitions[$pathtype]['kml_linestyle_linewidth'];
            $outline = $map_definitions[$pathtype]['kml_linestyle_color'];
            $highlight = $map_definitions[$pathtype]['kml_polystyle_color'];
          }
        }
        if (array_key_exists($name, $links)) {
          $link = $links[$name];
        }
        if (array_key_exists($name, $tooltips)) {
          $tooltip = $tooltips[$name];
        }
        if (is_array($coordinates[0])) {
          $subfolder = $this->dom->createElement('Folder');
          $foldername = $this->dom->createElement('name');
          $foldername->appendChild($this->dom->createTextNode($map_names[$name]));
          $subfolder->appendChild($foldername);
          foreach ($coordinates as $i => $subpolygon) {
            $counter = $i + 1;
            $this->_createPolygon(
              $subpolygon, $map_names[$name] . " ($counter)", $name, $link, $tooltip, $highlight, $outline,
              $strokefactor, $subfolder
            );
          }
          $folder->appendChild($subfolder);
        } else {
          $this->_createPolygon(
            $coordinates, $map_names[$name], $name, $link, $tooltip, $highlight, $outline, $strokefactor, $folder
          );
        }
      }
    }
    $this->_document->appendChild($folder);
  }

  private function _setDefaultPolygonStyle()
  {
    $id = $this->dom->createAttribute('id');
    $id->value = $this->_default_polygon_style_name;
    $style = $this->dom->createElement('Style');
    $style->appendChild($id);
    $linestyle = $this->dom->createElement('LineStyle');
    $width = $this->dom->createElement('width');
    $color = $this->dom->createElement('color');
    $color->appendChild($this->dom->createTextNode($this->_default_linestyle_color));
    $width->appendChild($this->dom->createTextNode($this->_default_linestyle_linewidth));
    $linestyle->appendChild($width);
    $linestyle->appendChild($color);
    $style->appendChild($linestyle);
    $polystyle = $this->dom->createElement('PolyStyle');
    $color = $this->dom->createElement('color');
    $color->appendChild($this->dom->createTextNode($this->_default_polystyle_color));
    $polystyle->appendChild($color);
    $style->appendChild($polystyle);
    $this->_document->appendChild($style);
  }

  public function insertCopyrightStatement()
  {
    foreach (array_unique($this->map_copyright_strings) as $string) {
      $this->dom->appendChild($this->dom->createComment($string));
    }
  }

  // @todo kijken of dit niet beter kan
  protected function drawPath($path, $coords, $pathtype, $map_definitions, $highlighted, $links, $map_names, $tooltips) {}
  public function drawSymbol($coordinaten, $kloeke_nr, $plaatsnaam, $symbol, $size, $style, $link_array, $rd = TRUE) {}

}
