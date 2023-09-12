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
 * Class to generate the SVG version of a map
 *
 * @internal
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2006-2007 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU GPL version 2
 * @package   Kaart
 */
class SVG extends Image
{
  /**
   * @var object XML_SVG_Document <svg> element, contains the generated map
   */
  public $svg;
  /**
   * @var object XML_SVG_Group the <g> element containing the National Triangulation transform, first child of <svg>
   */
  private $_transformer;
  /**
   * @var bool should placenames be shown onmouseover()?
   */
  private $_interactive = FALSE;
  /**
   * @var int initial y-coordinate for symbol in legend
   */
  private $_legend_symbol_y;
  /**
   * @var int x-coordinate for symbol in legend
   */
  private $_legend_symbol_x;
  /**
   * @var int x-coordinate for legend text
   */
  private $_legend_text_x;
  /**
   * @var int y-coordinate for legend text
   */
  private $_legend_text_y;
  /**
   * @var float factor to derive x-coordinate of legend text from the font size
   */
  private $_legend_text_x_factor = 1.5;
  /**
   * @var float factor to derive initial y-coordinate of legend text from the font size
   */
  private $_legend_text_y_factor = 0.5;
  /**
   * @var int  x-coordinat for the title, also used for dynamic tooltip
   */
  private $_title_x;
  /**
   * @var int y-coordinate for the title
   */
  private $_title_y;
  /**
   * @var int font size for title and tooltip
   */
  private $_title_fontsize;
  /**
   * @var string default style for the title
   */
  private $_title_style = array('fill' => 'black', 'font-weight' => 'bold', 'text-anchor' => 'middle');
  /**
   * @var float factor to derive y-coordinate of the next line of the legend
   */
  private $_regelafstand_factor = 1.2;
  /**
   * @var int font size of the legend
   */
  private $_fontsize;

  /**
   * @param $parameters array
   */
  public function __construct($parameters)
  {
    parent::__construct($parameters);

    $map_definitions = $parameters['map_definitions'];
    $width = $parameters['width'];
    $height = $parameters['height'];
    $this->_interactive = $parameters['interactive'];
    $this->_fontsize = $parameters['fontsize'];
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
    $translate_x = $map_definitions['map_settings']['svg_translate_x'];
    $translate_y = $map_definitions['map_settings']['svg_translate_y'];
    $scale_x = $map_definitions['map_settings']['svg_scale_x'];
    $scale_y = $map_definitions['map_settings']['svg_scale_y'];
    $viewbox_width = $map_definitions['map_settings']['svg_viewbox_width'];
    $viewbox_height = $map_definitions['map_settings']['svg_viewbox_height'];
    $this->_legend_symbol_x = $map_definitions['map_settings']['svg_legend_symbol_x'];
    $this->_legend_symbol_y = $map_definitions['map_settings']['svg_legend_symbol_y'];
    $title_extra_space = $map_definitions['map_settings']['svg_title_extra_space'];
    $this->_title_x = $map_definitions['map_settings']['svg_title_x'];
    $this->_title_y = $map_definitions['map_settings']['svg_title_y'];
    $this->_title_fontsize = $map_definitions['map_settings']['svg_title_fontsize'];
    $tooltip_x = $map_definitions['map_settings']['svg_tooltip_x'];
    $tooltip_y = $map_definitions['map_settings']['svg_tooltip_y'];
    $tooltip_text_anchor = $map_definitions['map_settings']['svg_tooltip_text-anchor'];

    if (!empty($this->title)) {
      // make space above the map for the title
      $viewbox_height += $title_extra_space;
      $translate_y += $title_extra_space;
      $this->_legend_symbol_y += $title_extra_space;
      $tooltip_y += $title_extra_space;
    }

    $this->_legend_text_x = $this->_legend_symbol_x + ($this->_fontsize * $this->_legend_text_x_factor);
    $this->_legend_text_y = $this->_legend_symbol_y + ($this->_fontsize * $this->_legend_text_y_factor);

    $this->svg = new XML_SVG_Document(array('width' => $width, 'height' => $height));
    $this->svg->setParam('viewBox', '0 0 ' . $viewbox_width . ' ' . $viewbox_height);
    $this->svg->setParam('preserveAspectRatio', 'xMidYMid');
    $this->svg->setParam('onload', 'init(evt)');

    if (array_key_exists('picturebackground', $parameters)) {
      $this->svg->addChild($parameters['picturebackground']);
    }

    if ($this->_interactive || !empty($tooltips)) {
      // add ECMAscript for placenames onmouseover
      $defs = new \XML_SVG_Defs;
      $script = new XML_SVG_Script(array('type' => 'text/ecmascript'));
      $javascript = KAART_ONMOUSEOVER_ECMASCRIPT;
      $cdata = new XML_SVG_CData($javascript);
      $script->addChild($cdata);
      $defs->addChild($script);
      $this->svg->addChild($defs);
      $g = new XML_SVG_Group(array('id' => 'tooltip'));
      $text = new XML_SVG_Text(array(
        'id' => 'ttt',
        'text' => 'tooltip',
        'x' => $tooltip_x,
        'y' => $tooltip_y,
        'display' => 'none',
        'fill' => 'blue',
        'font-size' => $this->_title_fontsize,
        'font-weight' => 'bold',
        'text-anchor' => $tooltip_text_anchor
      ));
      $g->addChild($text);
      $this->svg->addChild($g);
    }

    $this->_transformer = new XML_SVG_Group(array(
      'transform' =>
      'translate(' . $translate_x . ',' . $translate_y . ') scale(' . $scale_x . ',' . $scale_y . ')'
    ));
    $this->svg->addChild($this->_transformer);

    if (!empty($parameters['backgrounds'])) {
      $this->_drawBackgrounds($parameters['backgrounds']);
    }

    $this->drawBasemap($map_definitions, $parameters['parts'], $highlighted, $links, $tooltips);
    $this->_drawTitle();
  }

  /**
   * Draw a symbol on the map or in the legend
   *
   * @param array  $coordinaten x and y-coordinate
   * @param string $kloeke_nr   Kloeke code of the place
   * @param string $plaatsnaam  name of the place
   * @param string $symbol      name of the symbol
   * @param int    $size        size of the symbol
   * @param string $style       style (SVG "style" attribute) of the symbol
   * @param array  $link_array  array with url and optionally target
   * @param bool   $rd          are the coordinates National Triangulation or not (not if the symbol is part of the legend)
   */


  public function drawSymbol($coordinaten, $kloeke_nr, $plaatsnaam, $symbol, $size, $style, $link_array, $rd = TRUE)
  {
    list($x, $y) = $coordinaten;
    $legenda = !$rd; // if $rd, not $legenda and vice versa

    $helft = $size / 2;
    $params = array();

    switch ($symbol) {
    case 'circle':
      $symbol = 'XML_SVG_Circle';
      $params = array(
        'cx' => $x,
        'cy' => $y,
        'r' => $helft,
        'style' => $style
      );
      break;
    case 'square':
      $symbol = 'XML_SVG_Rect';
      $params = array(
        'x' => $x - $helft,
        'y' => $y - $helft,
        'width' => $size,
        'height' => $size,
        'style' => $style
      );
      break;
    case 'triangle':
      $symbol = 'XML_SVG_Polygon';
      // legend: not mirrored
      if ($legenda) {
        $a = strval($x - $helft) . ',' . strval($y + $helft);
        $b = strval($x + $helft) . ',' . strval($y + $helft);
        $c = strval($x) . ',' . strval($y - $helft);
        // map: within the transform, so mirrored
      } else {
        $a = strval($x - $helft) . ',' . strval($y - $helft);
        $b = strval($x + $helft) . ',' . strval($y - $helft);
        $c = strval($x) . ',' . strval($y + $helft);
      }
      $params = array(
        'points' => "$a $b $c",
        'style' => $style
      );
      break;
    case 'bar_horizontal':
      $symbol = 'XML_SVG_Rect';
      $kwart = $helft / 2;
      $params = array(
        'x' => $x - $helft,
        'y' => $y - $kwart,
        'width' => $size,
        'height' => $helft,
        'style' => $style
      );
      break;
    case 'bar_vertical':
      $symbol = 'XML_SVG_Rect';
      $kwart = $helft / 2;
      $params = array(
        'x' => $x - $kwart,
        'y' => $y - $helft,
        'width' => $helft,
        'height' => $size,
        'style' => $style
      );
      break;
    case 'line_horizontal':
      $symbol = 'XML_SVG_Line';
      $params = array(
        'x1' => $x - $helft,
        'y1' => $y,
        'x2' => $x + $helft,
        'y2' => $y,
        'style' => $style
      );
      break;
    case 'line_vertical':
      $symbol = 'XML_SVG_Line';
      $params = array(
        'x1' => $x,
        'y1' => $y - $helft,
        'x2' => $x,
        'y2' => $y + $helft,
        'style' => $style
      );
      break;
    case 'slash_left':
      $symbol = 'XML_SVG_Line';
      // legend: not mirrored
      if ($legenda) {
        $params = array(
          'x1' => $x - $helft,
          'y1' => $y - $helft,
          'x2' => $x + $helft,
          'y2' => $y + $helft,
          'style' => $style
        );
        // map: within the transform, so mirrored
      } else {
        $params = array(
          'x1' => $x - $helft,
          'y1' => $y + $helft,
          'x2' => $x + $helft,
          'y2' => $y - $helft,
          'style' => $style
        );
      }
      break;
    case 'slash_right':
      $symbol = 'XML_SVG_Line';
      // legend: not mirrored
      if ($legenda) {
        $params = array(
          'x1' => $x - $helft,
          'y1' => $y + $helft,
          'x2' => $x + $helft,
          'y2' => $y - $helft,
          'style' => $style
        );
        // map: within the transform, so mirrored
      } else {
        $params = array(
          'x1' => $x - $helft,
          'y1' => $y - $helft,
          'x2' => $x + $helft,
          'y2' => $y + $helft,
          'style' => $style
        );
      }
      break;
    case 'plus':
      $symbol = 'XML_SVG_Path';
      $a = strval($x) . ' ' . strval($y - $helft);
      $b = strval($x) . ' ' . strval($y + $helft);
      $c = strval($x - $helft) . ' ' . strval($y);
      $d = strval($x + $helft) . ' ' . strval($y);
      $params = array(
        'd' => "M $a L $b M $c L $d",
        'style' => $style
      );
      break;
    case 'star':
      $symbol = 'XML_SVG_Polygon';
      if ($legenda) {
        $a = strval($x) . ',' . strval($y - $helft);
        $b = strval($x + ($size / 8.21)) . ',' . strval($y - ($size / 7.21));
        $c = strval($x + $helft) . ',' . strval($y - ($size / 7.21));
        $d = strval($x + ($size / 5.06)) . ',' . strval($y + ($size / 11.33));
        $e = strval($x + ($size / 3.26)) . ',' . strval($y + ($size / 2.22));
        $f = strval($x) . ',' . strval($y + ($size / 4.25));
        $g = strval($x - ($size / 3.26)) . ',' . strval($y + ($size / 2.22));
        $h = strval($x - ($size / 5.06)) . ',' . strval($y + ($size / 11.33));
        $i = strval($x - $helft) . ',' . strval($y - ($size / 7.21));
        $j = strval($x - ($size / 8.21)) . ',' . strval($y - ($size / 7.21));
      } else {
        $a = strval($x) . ',' . strval($y + $helft);
        $b = strval($x + ($size / 8.21)) . ',' . strval($y + ($size / 7.21));
        $c = strval($x + $helft) . ',' . strval($y + ($size / 7.21));
        $d = strval($x + ($size / 5.06)) . ',' . strval($y - ($size / 11.33));
        $e = strval($x + ($size / 3.26)) . ',' . strval($y - ($size / 2.22));
        $f = strval($x) . ',' . strval($y - ($size / 4.25));
        $g = strval($x - ($size / 3.26)) . ',' . strval($y - ($size / 2.22));
        $h = strval($x - ($size / 5.06)) . ',' . strval($y - ($size / 11.33));
        $i = strval($x - $helft) . ',' . strval($y + ($size / 7.21));
        $j = strval($x - ($size / 8.21)) . ',' . strval($y + ($size / 7.21));
      }
      $params = array(
        'points' => "$a $b $c $d $e $f $g $h $i $j",
        'style' => $style
      );
      break;
    }

    $plaats = new $symbol($params);

    $a_params = array();

    if (array_key_exists('href', $link_array)) {
      $a_params['xlink:href'] = $this->escapeXMLString(sprintf($link_array['href'], $kloeke_nr));
    }

    if (array_key_exists('target', $link_array) && !is_null($link_array['target'])) {
      $a_params['target'] = $this->escapeXMLString($link_array['target']);
    }

    if (array_key_exists('onclick', $link_array)) {
      $a_params['onclick'] = $this->escapeXMLString(sprintf($link_array['onclick'], $kloeke_nr));
    }

    if (array_key_exists('onmouseover', $link_array)) {
      $a_params['onmouseover'] = $this->escapeXMLString(sprintf($link_array['onmouseover'], $kloeke_nr));
    }

    if (array_key_exists('onmouseout', $link_array)) {
      $a_params['onmouseout'] = $this->escapeXMLString(sprintf($link_array['onmouseout'], $kloeke_nr));
    }


    if ($legenda) {
      $this->svg->addChild($plaats);
    }

    if (!$legenda && $this->_interactive) {
      $g = new XML_SVG_Group();
      $g->setParam('onmouseover', "ShowTooltip('" . htmlspecialchars($plaatsnaam) . " ($kloeke_nr)')");
      $g->setParam('onmouseout', 'HideTooltip()');
      if (array_key_exists('xlink:href', $a_params)) {
        $a = new XML_SVG_A($a_params);
        $g->addChild($a);
        $a->addChild($plaats);
      } else {
        $g->addChild($plaats);
      }
      if (array_key_exists('onclick', $a_params)) {
        $g->setParam('onclick', $a_params['onclick']);
      }
      $this->_transformer->addChild($g);
    }

    if (!$legenda && !$this->_interactive) {
      if (!empty($a_params)) {
        $g = new XML_SVG_Group();
        if (array_key_exists('xlink:href', $a_params)) {
          $a = new XML_SVG_A($a_params);
          $g->addChild($a);
          $a->addChild($plaats);
        }
        if (array_key_exists('onclick', $a_params)) {
          $g->setParam('onclick', $a_params['onclick']);
        }
        if (array_key_exists('onmouseover', $a_params)) {
          $g->setParam('onmouseover', $a_params['onmouseover']);
        }
        if (array_key_exists('onmouseout', $a_params)) {
          $g->setParam('onmouseout', $a_params['onmouseout']);
        }
        $this->_transformer->addChild($g);
      } else {
        $this->_transformer->addChild($plaats);
      }
    }
  }

  /**
   * Returns the coordinates for the current symbol in the legend
   *
   * @return array x and y-coordinate of the current symbol
   */
  public function getLegendSymbolXY()
  {
    return array($this->_legend_symbol_x, $this->_legend_symbol_y);
  }

  /**
   * Sets the y-coordinate for the next symbol in the legend
   */
  public function setNextLegendSymbolY()
  {
    $this->_legend_symbol_y = $this->_legend_symbol_y + ($this->_fontsize * $this->_regelafstand_factor);
  }

  /**
   * Draws the text belonging to one symbol in the legend
   *
   * @param string $text
   */
  public function drawLegendText($text)
  {
    // Om Firefox 1.5 bug heenwerken: geen 'style' attribuut maar 'font-size' en 'fill'.
    // Met 'style' is de tekst niet zichtbaar.
    $text = new XML_SVG_Text(array(
      'text' => $text,
      'x' => $this->_legend_text_x,
      'y' => $this->_legend_text_y,
      'font-size' => strval($this->_fontsize),
      'fill' => 'black'
    ));
    $this->svg->addChild($text);

    $this->_legend_text_y = $this->_legend_text_y + ($this->_fontsize * $this->_regelafstand_factor);
  }

  /**
   * Insert a copyright/license statement as an XML comment in the map, if applicable
   */
  public function insertCopyrightStatement()
  {
    foreach (array_unique($this->map_copyright_strings) as $string) {
        $comment = new XML_SVG_Comment($string);
        $this->svg->addChild($comment);
    }
  }

  /**
   * Draws one or more backgrounds between the base map and the placemarks
   * Currently only 'daan_blok_1969' is valid
   *
   * @param array $backgrounds names of the backgrounds
   */
  private function _drawBackgrounds($backgrounds)
  {
    if (in_array('daan_blok_1969', $backgrounds)) {

      require('Daan_Blok.defs.inc.php');

      /** @var $daan_en_blok_1969 array with coordinates of the dialect areas from Daan & Blok 1969, defined in Daan_Blok.defs.inc.php */
      foreach ($daan_en_blok_1969 as $id => $coordinaten) {
        /** @var $daan_en_blok_1969_fills array with fill colors of the dialect areas from Daan & Blok 1969, defined in Daan_Blok.defs.inc.php */
        if (array_key_exists($id, $daan_en_blok_1969_fills)) {
          $fill = $daan_en_blok_1969_fills[$id];
        } else {
          $fill = 'none';
        }
        $path = new XML_SVG_Path(array(
          'id' => $id, 'd' => $this->_svgPathFromArray($coordinaten, 'gesloten'),
          'style' => 'stroke:' . $fill . '; fill:' . $fill . '; stroke-width:400'
        ));
        $this->_transformer->addChild($path);
        unset($path);
      }
    }
  }

  /**
   * Converts numeric array with x- and y-coordinates to a "d" attribute for a <path> element
   *
   * @param array $array    array with x- and y-coordinates
   * @param bool  $gesloten should the path be closed or not?
   *
   * @return string x- and y coordinates as value of a "d" attribute
   */
  private function _svgPathFromArray($array, $gesloten = FALSE)
  {
    $x = array_shift($array);
    $y = array_shift($array);
    $path = "M $x $y";
    while (count($array) > 0) {
      $x = array_shift($array);
      $y = array_shift($array);
      $path .= " L $x $y";
    }
    if ($gesloten !== FALSE) {
      $path .= ' z';
    }
    return $path;
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
  protected function drawPath(
    $path, $coords, $pathtype, $map_definitions, $highlighted, $links, $map_names, $tooltips
  )
  {

    $style = $map_definitions[$pathtype]['svg_path_style'];
    $closed = $map_definitions[$pathtype]['svg_path_closed'] == 1 ? TRUE : FALSE;

    if (array_key_exists($path, $highlighted)) {
      if (is_string($highlighted[$path])) {
        $style = preg_replace('/fill:.+?;/', "fill:{$highlighted[$path]};", $style);
      } elseif (is_array($highlighted[$path])) {
        $fill = $highlighted[$path]['fill'];
        $outline = $highlighted[$path]['outline'];
        $strokewidth = ($highlighted[$path]['strokewidth'] * $map_definitions[$pathtype]['svg_stroke_width']);
        $style = "stroke:{$outline}; fill:{$fill}; stroke-width:{$strokewidth};";
      }
      $highlightedpath = TRUE;
    } else {
      $highlightedpath = FALSE;
    }

    if (array_key_exists($path, $tooltips)) {
      $tooltip = $tooltips[$path];
    } else {
      $tooltip = '';
    }

    if (is_array($coords[0])) {
      $g = new XML_SVG_Group(array('id' => $path));
      foreach ($coords as $c) {
        $this->_addSVGPathToMap(
          $c, $path, $closed, $style, $links, $map_names, $map_definitions, $highlightedpath, $tooltip, $g
        );
      }
      $this->_transformer->addChild($g);
      unset($g);
    } else {
      $this->_addSVGPathToMap(
        $coords, $path, $closed, $style, $links, $map_names, $map_definitions, $highlightedpath, $tooltip
      );
    }
  }

  /**
   * @param      $coords
   * @param      $name
   * @param      $closed
   * @param      $style
   * @param      $links
   * @param      $map_names
   * @param      $map_definitions
   * @param      $highlightedpath
   * @param      $tooltip
   * @param mixed $enclosing_group
   */
  private function _addSVGPathToMap(
    $coords, $name, $closed, $style, $links, $map_names, $map_definitions, $highlightedpath, $tooltip,
    $enclosing_group = FALSE
  )
  {

    $parameters = array('d' => $this->_svgPathFromArray($coords, $closed), 'style' => $style);
    if (!$enclosing_group) {
      $parameters['id'] = $name;
    }
    $svgpath = new XML_SVG_Path($parameters);
    if (array_key_exists($name, $links)) {
      if (array_key_exists('href', $links[$name])) {
        $a_params['xlink:href'] = $this->escapeXMLString($links[$name]['href']);
        if (array_key_exists('target', $links[$name])) {
          $a_params['target'] = $this->escapeXMLString($links[$name]['target']);
        }
        $g = new XML_SVG_Group();
        $a = new XML_SVG_A($a_params);
        $g->addChild($a);
      }
      if (array_key_exists('onclick', $links[$name])) {
        $svgpath->setParam('onclick', $this->escapeXMLString($links[$name]['onclick']));
      }
      if (array_key_exists('onmouseover', $links[$name])) {
        $svgpath->setParam('onmouseover', $this->escapeXMLString($links[$name]['onmouseover']));
      }
    } elseif (!empty($this->link)) {
      if (!$this->linkhighlightedonly || ($this->linkhighlightedonly && $highlightedpath)) {
        $a_params['xlink:href'] = $this->escapeXMLString(sprintf($this->link, $name));
        if (!empty($this->target)) {
          $a_params['target'] = $this->escapeXMLString($this->target);
        }
        $g = new XML_SVG_Group();
        $a = new XML_SVG_A($a_params);
        $g->addChild($a);
      }
    }

    if ($map_definitions['map_settings']['basemap_interactive']) {
      // if the path is a municipality, the id is of the form 'g_[numerical code]'
      if (!empty($tooltip) && strpos($name, 'g_') === 0) {
        $svgpath->setParam('onmouseover', "ShowTooltip('" . $this->escapeJSString($tooltip) . "')");
        $svgpath->setParam('onmouseout', 'HideTooltip()');
      } elseif ($this->_interactive && strpos($name, 'g_') === 0) {
        $svgpath->setParam('onmouseover', "ShowTooltip('" . $this->escapeJSString($map_names[$name]) . "')");
        $svgpath->setParam('onmouseout', 'HideTooltip()');
      }
    }

    /** @var $enclosing_group XML_SVG_Group */
    if ($enclosing_group !== FALSE) {
      if (isset($g) && isset($a)) {
        $a->addChild($svgpath);
        $enclosing_group->addChild($g);
      } else {
        $enclosing_group->addChild($svgpath);
      }
    } else {
      if (isset($g) && isset($a)) {
        $a->addChild($svgpath);
        $this->_transformer->addChild($g);
      } else {
        $this->_transformer->addChild($svgpath);
      }
    }
  }

  /**
   * Draws the title above the map
   */
  private function _drawTitle()
  {
    if (empty($this->title)) {
      return;
    }

    $text = new XML_SVG_Text(array(
      'text' => $this->title,
      'x' => $this->_title_x,
      'y' => $this->_title_y,
      'font-size' => $this->_title_fontsize,
      'fill' => $this->_title_style['fill'],
      'font-weight' => $this->_title_style['font-weight'],
      'text-anchor' => $this->_title_style['text-anchor']
    ));
    // outside the National Triangulation transform
    $this->svg->addChild($text);
  }
}

