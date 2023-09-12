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
 * Class to generate the bitmap version of a map
 *
 * @internal
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2006-2007 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU GPL version 2
 * @package   Kaart
 */

class Bitmap extends Image
{
  /**
   * @var resource GD image resource voor bitmapversie van de kaart
   */
  public $gd_image;
  /**
   * @var float de dikte van de dunste lijnen in de bitmap (afhankelijk van de grootte)
   */
  private $_bitmap_linewidth;
  /**
   * @var bool boolean die aangeeft of er een <map> met de plaatsnamen in "title" attributen gemaakt moet worden
   */
  private $_interactive = FALSE;
  /**
   * @var string HTML met de <area> elementen voor de afgebeelde plaatsen, om in <map> op te nemen
   */
  private $_imagemap_areas = '';
  /**
   * @var int factor waarmee Rijksdriehoeksstelsel-coordinaten, of andere op dezelfde fijnmazigheid
   *         gebaseerde SVG-coordinaten, naar pixel-coordinaten worden omgezet. Afhankelijk van de grootte
   *         van de kaart.
   */
  private $_bitmap_factor;
  /**
   * @var int de legenda begint op 1/20ste van de bovenkant van het plaatje (als er geen titel is)
   */
  private $_legend_symbol_y_factor = 20;
  /**
   * @var int de legenda begint op 1/15de van de zijkant van het plaatje
   */
  private $_legend_symbol_x_factor = 15;
  /**
   * @var float hulpgetal om de x-coordinaat van de legendatekst uit de fontgrootte af te leiden
   */
  private $_legend_text_x_factor = 1.5;
  /**
   * @var float hulpgetal om de initi�le y-coordinaat van de legendatekst uit de fontgrootte af te leiden
   */
  private $_legend_text_y_factor = 0.5;
  /**
   * @var int aantal pixels dat bij de y-coordinaten opgeteld moet worden als de
   *         titel boven de kaart staat
   */
  private $_extra_pixels = 0;
  /**
   * @var int hulpgetal om $this->_extra_pixels uit te rekenen
   */
  private $_extra_pixels_factor = 9;
  /**
   * @var int hulpgetal om de fontgrootte in GD-termen af te leiden uit de fontgrootte
   *         zoals uitgedrukt in SVG-fontgrootte in Rijksdriehoeksfijnmazigheid
   */
  private $_fontsize_factor;
  /**
   * @var int
   */
  private $_title_y_factor;
  /**
   * @var array array met kleuren die standaard gealloceerd moeten worden
   */
  private $_default_colors = array('blue', 'brown', 'yellow', 'green', 'red', 'black', 'gray', 'dodgerblue');
  /**
   * @var array array voor de integers van de gealloceerde kleuren
   */
  private $_colors = array();
  /**
   * @var int fontgrootte in Rijksdriehoeksfijnmazigheid voor de titel
   */
  private $_svg_title_fontsize;
  /**
   * @var float hulpgetal om de fontgrootte voor de titel mee uit te rekenen
   */
  private $_svg_title_fontsize_factor = 1.5;
  /**
   * @var int fontgrootte in GD-termen voor de legenda
   */
  private $_fontsize;
  /**
   * @var int x-coordinaat voor het legendasymbool
   */
  private $_legend_symbol_x;
  /**
   * @var int y-coordinaat voor het legendasymbool
   */
  private $_legend_symbol_y;
  /**
   * @var int x-coordinaat voor de legendatekst
   */
  private $_legend_text_x;
  /**
   * @var int y-coordinaat voor de legendatekst
   */
  private $_legend_text_y;
  /**
   * @var int om de y-coordinaat van de volgende regel in de legenda uit te rekenen
   */
  private $_regelafstand_factor = 2;
  /**
   * @var int needed for conversion of rd-coordinates to pixels
   */
  private $_rd2pixel_x;
  /**
   * @var int needed for conversion of rd-coordinates to pixels
   */
  private $_rd2pixel_y;

  /**
   * De constructor
   *
   * Maakt de grondkaart als GD image resource
   *   *
   *
   * @param $parameters
   */
  public function __construct($parameters)
  {
    parent::__construct($parameters);

    $map_definitions = $parameters['map_definitions'];
    $width = $parameters['width'];
    $height = $parameters['height'];
    $this->_interactive = $parameters['interactive'];
    $fontsize = $parameters['fontsize'];
    if (array_key_exists('highlighted', $parameters)) {
      $highlighted = $parameters['highlighted'];
    } else {
      $highlighted = array();
    }
    if (array_key_exists('tooltips', $parameters)) {
      $tooltips = $parameters['tooltips'];
    } else {
      $tooltips = array();
    }
    if (array_key_exists('links', $parameters)) {
      $links = $parameters['links'];
    } else {
      $links = array();
    }
    $this->_rd2pixel_x = $map_definitions['map_settings']['bitmap_rd2pixel_x'];
    $this->_rd2pixel_y = $map_definitions['map_settings']['bitmap_rd2pixel_y'];

    $bitmap_size_factor = $map_definitions['map_settings']['bitmap_size_factor'];
    $smaller_bitmap_factor = $map_definitions['map_settings']['bitmap_smaller_bitmap_factor'];
    $this->_fontsize_factor = $map_definitions['map_settings']['bitmap_fontsize_factor'];
    $this->_title_y_factor = $map_definitions['map_settings']['bitmap_title_y_factor'];

    $this->_legend_symbol_x = round($width / $this->_legend_symbol_x_factor);
    $this->_legend_symbol_y = round($height / $this->_legend_symbol_y_factor);

    if (!empty($this->title)) {
      // kaart iets kleiner
      $bitmap_size_factor += ($bitmap_size_factor / $smaller_bitmap_factor);
      // en naar beneden geschoven
      $this->_extra_pixels = round($height / $this->_extra_pixels_factor) - $this->_legend_symbol_y;
      $this->_legend_symbol_y += $this->_extra_pixels;
    }

    $this->_bitmap_linewidth = $width / $bitmap_size_factor;
    $this->_bitmap_factor = round($bitmap_size_factor / $this->_bitmap_linewidth);
    $this->_svg_title_fontsize = $fontsize * $this->_svg_title_fontsize_factor;
    $this->_fontsize = ($fontsize * $this->_fontsize_factor) / $this->_bitmap_factor;

    $this->_legend_text_x = $this->_legend_symbol_x + ($this->_fontsize * $this->_legend_text_x_factor);
    $this->_legend_text_y = $this->_legend_symbol_y + ($this->_fontsize * $this->_legend_text_y_factor);

    $this->gd_image = imagecreate($width, $height);
    // achtergroundkleur = eerste gealloceerde kleur
    $this->_colors[$map_definitions['map_settings']['background_color']] = \Image_Color::allocateColor(
      $this->gd_image, $map_definitions['map_settings']['background_color']
    );


    // om symbolen zonder fill te maken
    $this->_colors['none'] = FALSE;

    foreach ($this->_default_colors as $color) {
      $this->_colors[$color] = \Image_Color::allocateColor($this->gd_image, $color);
    }
    $this->_colors['grey'] = $this->_colors['gray']; // Image_Color gebruikt spelling 'gray'

    if ($map_definitions['map_settings']['bitmap_outline']) {
      imagerectangle($this->gd_image, 0, 0, $width - 1, $height - 1, $this->_colors['black']);
    }

    if (!empty($parameters['backgrounds'])) {
      $this->_drawBackgrounds($parameters['backgrounds']);
    }
    $this->drawBasemap($map_definitions, $parameters['parts'], $highlighted, $links, $tooltips);
    $this->_drawTitle($width, $height);
  }

  /**
   * Tekent een symbool op de kaart of de legenda
   *
   * @access public
   *
   * @param $coordinaten array  x en y-coördinaat
   * @param $kloeke_nr   string Kloekenummer
   * @param $plaatsnaam  string plaatsnaam
   * @param $symbol      string naam van het symbool
   * @param $size        int    grootte van het te tekenen symbool
   * @param $style       string  stijl (SVG "style" attribuut) van het te tekenen symbool
   * @param $link_array  array href- en target-attributen voor de plaats
   * @param $rd          bool   of de coördinaten in het Rijksdriehoeksstelsel zijn of niet (niet als het voor de legenda is)
   */
  public function drawSymbol($coordinaten, $kloeke_nr, $plaatsnaam, $symbol, $size, $style, $link_array, $rd = TRUE)
  {
    if ($rd) { // als het rijksdriehoekscoordinaten zijn (dus niet een symbool in de legenda)
      $this->_rd2pixels($coordinaten);
    }

    if ($this->_interactive || !empty($link_array)) {
      $area_element = TRUE;
    } else {
      $area_element = FALSE;
    }

    // symbool in de legenda
    if (!$rd) {
      $area_element = FALSE;
    }

    list($x, $y) = $coordinaten;

    $size = round($size / $this->_bitmap_factor);
    $half = round($size / 2);
    $quarter = round($half / 2);
    list($fill, $stroke) = $this->_parseSVGstyle($style);

    switch ($symbol) {

    case 'circle':
      imagesetthickness($this->gd_image, 1); // 1 pixel dik randje om symbolen is het mooiste
      if ($fill !== FALSE) {
        imagefilledellipse($this->gd_image, (int) $x, (int) $y, (int) $size, (int) $size, $fill);
      }
      if ($stroke !== FALSE) {
        imageellipse($this->gd_image, (int) $x, (int) $y, (int) $size, (int) $size, $stroke);
      }
      imagesetthickness($this->gd_image, (int) $this->_bitmap_linewidth);
      if ($area_element) {
        $this->_create_area_element(
          'circle', array($x, $y, $half), "$plaatsnaam ($kloeke_nr)", '', $kloeke_nr, $link_array
        );
      }
      break;
    case 'square':
      imagesetthickness($this->gd_image, 1); // 1 pixel dik randje om symbolen is het mooiste
      $x1 = $x - $half;
      $y1 = $y - $half;
      $x2 = $x + $half;
      $y2 = $y + $half;
      if ($fill !== FALSE) {
        imagefilledrectangle($this->gd_image, (int) $x1, (int) $y1, (int) $x2, (int) $y2, $fill);
      }
      if ($stroke !== FALSE) {
        imagerectangle($this->gd_image, (int) $x1, (int) $y1, (int) $x2, (int) $y2, $stroke);
      }
      imagesetthickness($this->gd_image, (int) $this->_bitmap_linewidth);
      if ($area_element) {
        $this->_create_area_element(
          'rect', array($x1, $y1, $x2, $y2), "$plaatsnaam ($kloeke_nr)", '', $kloeke_nr, $link_array
        );
      }
      break;
    case 'triangle':
      imagesetthickness($this->gd_image, 1); // 1 pixel dik randje om symbolen is het mooiste
      $points = array(
        $x - $half,
        $y + $half,
        $x,
        $y - $half,
        $x + $half,
        $y + $half
      );
      if ($fill !== FALSE) {
        imagefilledpolygon($this->gd_image, $points, $fill);
      }
      if ($stroke !== FALSE) {
        imagepolygon($this->gd_image, $points, $stroke);
      }
      imagesetthickness($this->gd_image, (int) $this->_bitmap_linewidth);
      if ($area_element) {
        $this->_create_area_element('poly', $points, "$plaatsnaam ($kloeke_nr)", '', $kloeke_nr, $link_array);
      }
      break;
    case 'bar_horizontal':
      imagesetthickness($this->gd_image, 1); // 1 pixel dik randje om symbolen is het mooiste
      $x1 = $x - $half;
      $y1 = $y - $quarter;
      $x2 = $x + $half;
      $y2 = $y + $quarter;
      if ($fill !== FALSE) {
        imagefilledrectangle($this->gd_image, $x1, $y1, $x2, $y2, $fill);
      }
      if ($stroke !== FALSE) {
        imagerectangle($this->gd_image, $x1, $y1, $x2, $y2, $stroke);
      }
      imagesetthickness($this->gd_image, $this->_bitmap_linewidth);
      if ($area_element) {
        $this->_create_area_element(
          'rect', array($x1, $y1, $x2, $y2), "$plaatsnaam ($kloeke_nr)", '', $kloeke_nr, $link_array
        );
      }
      break;
    case 'bar_vertical':
      imagesetthickness($this->gd_image, 1); // 1 pixel dik randje om symbolen is het mooiste
      $x1 = $x - $quarter;
      $y1 = $y - $half;
      $x2 = $x + $quarter;
      $y2 = $y + $half;
      if ($fill !== FALSE) {
        imagefilledrectangle($this->gd_image, $x1, $y1, $x2, $y2, $fill);
      }
      if ($stroke !== FALSE) {
        imagerectangle($this->gd_image, $x1, $y1, $x2, $y2, $stroke);
      }
      imagesetthickness($this->gd_image, $this->_bitmap_linewidth);
      if ($area_element) {
        $this->_create_area_element(
          'rect', array($x1, $y1, $x2, $y2), "$plaatsnaam ($kloeke_nr)", '', $kloeke_nr, $link_array
        );
      }
      break;
    case 'line_horizontal':
      imagesetthickness($this->gd_image, $this->_bitmap_linewidth * 2);
      $x1 = $x - $half;
      $y1 = $y - $quarter; // aanklikbaar gedeelte onzichtbaar groter
      $x2 = $x + $half;
      $y2 = $y + $quarter; // aanklikbaar gedeelte onzichtbaar groter
      if ($fill !== FALSE) {
        imageline($this->gd_image, $x1, $y, $x2, $y, $fill);
      } else {
        // noodzakelijk om te emuleren wat er in de SVG-versie gebeurt bij 'color="none"' (1 pixel dik grijs streepje)
        imageline($this->gd_image, $x1, $y, $x2, $y, $this->_colors['grey']);
      }
      imagesetthickness($this->gd_image, $this->_bitmap_linewidth);
      if ($area_element) {
        $this->_create_area_element(
          'rect', array($x1, $y1, $x2, $y2), "$plaatsnaam ($kloeke_nr)", '', $kloeke_nr, $link_array
        );
      }
      break;
    case 'line_vertical':
      imagesetthickness($this->gd_image, $this->_bitmap_linewidth * 2);
      $x1 = $x - $quarter; // aanklikbaar gedeelte onzichtbaar groter
      $y1 = $y - $half;
      $x2 = $x + $quarter; // aanklikbaar gedeelte onzichtbaar groter
      $y2 = $y + $half;
      if ($fill !== FALSE) {
        imageline($this->gd_image, $x, $y1, $x, $y2, $fill);
      } else {
        // noodzakelijk om te emuleren wat er in de SVG-versie gebeurt bij 'color="none"' (1 pixel dik grijs streepje)
        imageline($this->gd_image, $x, $y1, $x, $y2, $this->_colors['grey']);
      }
      if ($area_element) {
        $this->_create_area_element(
          'rect', array($x1, $y1, $x2, $y2), "$plaatsnaam ($kloeke_nr)", '', $kloeke_nr, $link_array
        );
      }
      imagesetthickness($this->gd_image, $this->_bitmap_linewidth);
      break;
    case 'slash_left':
      imagesetthickness($this->gd_image, $this->_bitmap_linewidth * 4);
      // aanklikbaar gedeelte onzichtbaar groter
      $x1 = $x - $half;
      $y1 = $y - $half;
      $x2 = $x - $half;
      $y2 = $y - $quarter;
      $x3 = $x + $quarter;
      $y3 = $y + $half;
      $x4 = $x + $half;
      $y4 = $y + $half;
      $x5 = $x + $half;
      $y5 = $y + $quarter;
      $x6 = $x - $quarter;
      $y6 = $y - $half;
      if ($fill !== FALSE) {
        imageline($this->gd_image, $x1, $y1, $x4, $y4, $fill);
      } else {
        // noodzakelijk om te emuleren wat er in de SVG-versie gebeurt bij 'color="none"' (1 pixel dik grijs streepje)
        imageline($this->gd_image, $x1, $y1, $x4, $y4, $this->_colors['grey']);
      }
      imagesetthickness($this->gd_image, $this->_bitmap_linewidth);
      if ($area_element) {
        $this->_create_area_element(
          'poly', array($x1, $y1, $x2, $y2, $x3, $y3, $x4, $y4, $x5, $y5, $x6, $y6),
          "$plaatsnaam ($kloeke_nr)", '', $kloeke_nr, $link_array
        );
      }
      break;
    case 'slash_right':
      imagesetthickness($this->gd_image, $this->_bitmap_linewidth * 4);
      // aanklikbaar gedeelte onzichtbaar groter
      $x1 = $x - $half;
      $y1 = $y + $half;
      $x2 = $x - $half;
      $y2 = $y + $quarter;
      $x3 = $x + $quarter;
      $y3 = $y - $half;
      $x4 = $x + $half;
      $y4 = $y - $half;
      $x5 = $x + $half;
      $y5 = $y - $quarter;
      $x6 = $x - $quarter;
      $y6 = $y + $half;
      if ($fill !== FALSE) {
        imageline($this->gd_image, $x1, $y1, $x4, $y4, $fill);
      } else {
        // noodzakelijk om te emuleren wat er in de SVG-versie gebeurt bij 'color="none"' (1 pixel dik grijs streepje)
        imageline($this->gd_image, $x1, $y1, $x4, $y4, $this->_colors['grey']);
      }
      imagesetthickness($this->gd_image, $this->_bitmap_linewidth);
      if ($area_element) {
        $this->_create_area_element(
          'poly', array($x1, $y1, $x2, $y2, $x3, $y3, $x4, $y4, $x5, $y5, $x6, $y6),
          "$plaatsnaam ($kloeke_nr)", '', $kloeke_nr, $link_array
        );
      }
      break;
    case 'plus':
      // streep verticaal en horizontaal over elkaar
      imagesetthickness($this->gd_image, intval($this->_bitmap_linewidth * 2));
      $x1 = $x - $half;
      $y1 = $y;
      $x2 = $x;
      $y2 = $y + $half;
      $x3 = $x + $half;
      $y3 = $y;
      $x4 = $x;
      $y4 = $y - $half;
      if ($fill !== FALSE) {
        imageline($this->gd_image, $x, $y4, $x, $y2, $fill);
        imageline($this->gd_image, $x1, $y, $x3, $y, $fill);
      } else {
        // noodzakelijk om te emuleren wat er in de SVG-versie gebeurt bij 'color="none"' (1 pixel dik grijs streepje)
        imageline($this->gd_image, $x, $y4, $x, $y2, $this->_colors['grey']);
        imageline($this->gd_image, $x1, $y, $x3, $y, $this->_colors['grey']);
      }
      imagesetthickness($this->gd_image, (int) $this->_bitmap_linewidth);
      if ($area_element) {
        $this->_create_area_element(
          'poly', array($x1, $y1, $x2, $y2, $x3, $y3, $x4, $y4), "$plaatsnaam ($kloeke_nr)", '', $kloeke_nr,
          $link_array
        );
      }
      break;
    case 'star':
      imagesetthickness($this->gd_image, 1); // 1 pixel dik randje om symbolen is het mooiste
      $points = array(
        $x, $y - $half,
        $x + ($size / 8.21), $y - ($size / 7.21),
        $x + $half, $y - ($size / 7.21),
        $x + ($size / 5.06), $y + ($size / 11.33),
        $x + ($size / 3.26), $y + ($size / 2.22),
        $x, $y + ($size / 4.25),
        $x - ($size / 3.26), $y + ($size / 2.22),
        $x - ($size / 5.06), $y + ($size / 11.33),
        $x - $half, $y - ($size / 7.21),
        $x - ($size / 8.21), $y - ($size / 7.21)
      );
      if ($fill !== FALSE) {
        imagefilledpolygon($this->gd_image, $points, $fill);
      }
      if ($stroke !== FALSE) {
        imagepolygon($this->gd_image, $points, $stroke);
      }
      imagesetthickness($this->gd_image, (int) $this->_bitmap_linewidth);
      if ($area_element) {
        $this->_create_area_element('poly', $points, "$plaatsnaam ($kloeke_nr)", '', $kloeke_nr, $link_array);
      }
      break;
    }

  }

  /**
   * Voegt een <area> element toe aan $this->_imagemap_areas om later in een <map> element te stoppen
   *
   * @param string $shape  circle, rectangle or polygon
   * @param array  $coords in pixels
   * @param string $default_title
   * @param string $custom_title
   * @param string $area_id
   * @param array  $link_array
   * @param bool   $highlightedpath
   * @param bool   $sprintf
   * @param bool   $id_attribute
   */
  private function _create_area_element(
    $shape, $coords, $default_title, $custom_title, $area_id, $link_array, $highlightedpath = FALSE,
    $sprintf = TRUE, $id_attribute = FALSE
  )
  {
    $href = $target = $onclick = $onmouseover = $onmouseout = '';

    // specific link for this area
    if (!empty($link_array)) {
      if (array_key_exists('href', $link_array)) {
        if ($sprintf) {
          $href = sprintf($link_array['href'], $area_id);
        } else {
          $href = $link_array['href'];
        }
      }
      if (array_key_exists('target', $link_array) && !is_null($link_array['target'])) {
        $target = $link_array['target'];
      }
      if (array_key_exists('onclick', $link_array)) {
        if ($sprintf) {
          $onclick = sprintf($link_array['onclick'], $area_id);
        } else {
          $onclick = $link_array['onclick'];
        }
      }
      if (array_key_exists('onmouseover', $link_array)) {
        if ($sprintf) {
          $onmouseover = sprintf($link_array['onmouseover'], $area_id);
        } else {
          $onmouseover = $link_array['onmouseover'];
        }
      }
      if (array_key_exists('onmouseout', $link_array)) {
        if ($sprintf) {
          $onmouseout = sprintf($link_array['onmouseout'], $area_id);
        } else {
          $onmouseout = $link_array['onmouseout'];
        }
      }
      // general link for all areas
    } elseif (!empty($this->link)) {
      if (!$this->linkhighlightedonly || ($this->linkhighlightedonly && $highlightedpath)) {
        $href = sprintf($this->link, $area_id);
        if (!empty($this->target)) {
          $target = $this->target;
        }
      }
    }

    if (is_array($coords[0])) {
      $area = '';
      foreach ($coords as $i => $subset) {
        $area .= $this->_add_area_html(
          $shape, $subset, $default_title, $custom_title, $area_id . '_' . $i, $id_attribute, $href, $target,
          $onclick, $onmouseover, $onmouseout
        );
      }
    } else {
      $area = $this->_add_area_html(
        $shape, $coords, $default_title, $custom_title, $area_id, $id_attribute, $href, $target, $onclick,
        $onmouseover, $onmouseout
      );
    }

    $this->_imagemap_areas .= $area;
  }

  /**
   * Korte omschrijving
   *
   * lange omschrijving
   *
   * @tags
   */
  private function _add_area_html(
    $shape, $coords, $default_title, $custom_title, $area_id, $id_attribute, $href, $target, $onclick, $onmouseover,
    $onmouseout
  )
  {
    $area = '<area shape="' . $shape . '" coords="' . join(',', $coords) . '"';
    if (!empty($custom_title)) {
      $area
        .= ' title="' . $this->escapeXMLString($custom_title) . '" alt="' . $this->escapeXMLString($custom_title)
        . '"';
    } elseif ($this->_interactive) {
      $area .= ' title="' . $this->escapeXMLString($default_title) . '" alt="' . $this->escapeXMLString(
        $default_title
      ) . '"';
    }
    if (!empty($href)) {
      $area .= ' href="' . $this->escapeXMLString($href) . '"';
    }
    if (!empty($target)) {
      $area .= ' target="' . $this->escapeXMLString($target) . '"';
    }
    if (!empty($onclick)) {
      $area .= ' onclick="' . $this->escapeXMLString($onclick) . '"';
    }
    if (!empty($onmouseover)) {
      $area .= ' onmouseover="' . $this->escapeXMLString($onmouseover) . '"';
    }
    if (!empty($onmouseout)) {
      $area .= ' onmouseout="' . $this->escapeXMLString($onmouseout) . '"';
    }
    if ($id_attribute) {
      $area .= ' id="' . $this->escapeXMLString($area_id) . '"';
    }
    $area .= ' />' . "\n";

    return $area;
  }

  /**
   * Returns the <area> elements for use in a <map> element for interactive bitmaps
   *
   * @access public
   * @return string <area> elements
   */
  public function getImagemapAreas()
  {
    return $this->_imagemap_areas;
  }

  /**
   * Vertaalt rijksdriehoeksco�rdinaten naar GD-co�rdinaten in pixels
   *
   * @param $coordinates array x- en y-coordinaat (passed by reference)
   */
  private function _rd2pixels(&$coordinates)
  {
    foreach ($coordinates as $key => $coordinate) {
      if ($key % 2 == 0) { // even: x-coordinaat
        $coordinates[$key] = round(($coordinate + $this->_rd2pixel_x) / $this->_bitmap_factor);
      } else { // oneven: y-coordinaat
        $coordinates[$key]
          = round(-(($coordinate - $this->_rd2pixel_y) / $this->_bitmap_factor)) + $this->_extra_pixels;
      } // $this->_extra_pixels == 0 als er geen titel boven de kaart staat
    }
  }

  /**
   * Emulatie van het SVG-element <path> door achter elkaar getekende lijnstukken
   *
   * @param $gd_image    resource GD image resource (passed by reference)
   * @param $coordinates array met coordinaten
   * @param $color       int GD-allocated kleur
   */
  private function _imagepath(&$gd_image, $coordinates, $color)
  {
    $x = array_shift($coordinates);
    $y = array_shift($coordinates);
    while (count($coordinates) > 0) {
      $x2 = array_shift($coordinates);
      $y2 = array_shift($coordinates);
      imageline($gd_image, $x, $y, $x2, $y2, $color);
      $x = $x2;
      $y = $y2;
    }
  }

  /**
   * Geeft op grond van de naam van een kleur de GD-allocated integer terug
   *
   * @access private
   *
   * @param $string string: naam van de kleur
   *
   * @return mixed integer (int GD-allocated kleur) of FALSE indien niet bestaand
   */
  private function _allocated_color($string)
  {
    if (array_key_exists($string, $this->_colors)) {
      return $this->_colors[$string];
    } else {
      $this->_colors[$string] = \Image_Color::allocateColor($this->gd_image, $string);
      return $this->_colors[$string];
    }
  }

  /**
   * Geeft op grond van een SVG "style" attribuut de gealloceerde kleuren voor vulling en omtrek terug
   *
   * @param $style string SVG "style" attribuut
   *
   * @return array array met twee integers voor resp. vulling en omtrek
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
    return array($this->_allocated_color($fill), $this->_allocated_color($stroke));
  }

  /**
   * Geeft de x-en y-coordinaat voor het huidige legendasymbool terug
   *
   * @access public
   * @return array x- en y-coordinaat voor het huidige legendasymbool
   */
  function getLegendSymbolXY()
  {
    return array($this->_legend_symbol_x, $this->_legend_symbol_y);
  }

  /**
   * Zet de y-coordinaat voor het volgende legendasymbool
   *
   * @access public
   */
  function setNextLegendSymbolY()
  {
    $this->_legend_symbol_y = $this->_legend_symbol_y + ($this->_fontsize * $this->_regelafstand_factor);
  }

  /**
   * Tekent de bij 1 symbool horende tekst bij de legenda
   *
   * @param $text string de af te beelden tekst
   */
  function drawLegendText($text)
  {
    imagefttext(
      $this->gd_image, $this->_fontsize, 0, (int) $this->_legend_text_x, (int) $this->_legend_text_y, $this->_colors['black'],
      KAART_BITMAP_DEFAULTFONT, $text
    );
    $this->_legend_text_y = $this->_legend_text_y + ($this->_fontsize * $this->_regelafstand_factor);
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

    $thicknessfactor = $map_definitions[$pathtype]['bitmap_thickness_factor'];
    imagesetthickness($this->gd_image, intval( $this->_bitmap_linewidth * $thicknessfactor));

    if (array_key_exists('bitmap_color', $map_definitions[$pathtype])) {
      // outline and fill the same, or polygon without fill
      $color = $fill = $this->_allocated_color($map_definitions[$pathtype]['bitmap_color']);
    } else {
      // outline and fill different
      $color = $this->_allocated_color($map_definitions[$pathtype]['bitmap_outline']);
      $fill = $this->_allocated_color($map_definitions[$pathtype]['bitmap_fill']);
    }

    if (array_key_exists($path, $highlighted)) {
      // only fill color defined
      if (is_string($highlighted[$path])) {
        $fill = $this->_allocated_color($highlighted[$path]);
        // fill, outline, strokewidth defined
      } elseif (is_array($highlighted[$path])) {
        $fill = $this->_allocated_color($highlighted[$path]['fill']);
        $color = $this->_allocated_color($highlighted[$path]['outline']);
        $thicknessfactor = $highlighted[$path]['strokewidth'];
        imagesetthickness($this->gd_image, (int) $this->_bitmap_linewidth * $thicknessfactor);
      }
      $highlightedpath = TRUE;
    } else {
      $highlightedpath = FALSE;
    }

    if (is_array($coords[0])) {
      $count = count($coords);
      for ($i = 0; $i < $count; $i++) {
        $this->_rd2pixels($coords[$i]);
      }
    } else {
      $this->_rd2pixels($coords);
    }

    switch ($map_definitions[$pathtype]['bitmap_function']) {

    case 'imagepolygon':
      if (is_array($coords[0])) {
        foreach ($coords as $lines) {
          imagepolygon($this->gd_image, $lines, $color);
        }
      } else {
        imagepolygon($this->gd_image, $coords, $color);
      }
      break;
    case 'imagepath':
      if (is_array($coords[0])) {
        foreach ($coords as $lines) {
          $this->_imagepath($this->gd_image, $lines, $color);
        }
      } else {
        $this->_imagepath($this->gd_image, $coords, $color);
      }
      break;
    case 'imagefilledpolygon':
      if (is_array($coords[0])) {
        foreach ($coords as $lines) {
          imagefilledpolygon($this->gd_image, $lines, $color);
        }
      } else {
        imagefilledpolygon($this->gd_image, $coords, $color);
      }
      break;
    case 'outlinedpolygon':
      if (is_array($coords[0])) {
        foreach ($coords as $lines) {
          imagefilledpolygon($this->gd_image, $lines,  $fill);
          imagepolygon($this->gd_image, $lines,  $color);
        }
      } else {
        imagefilledpolygon($this->gd_image, $coords, $fill);
        imagepolygon($this->gd_image, $coords, $color);
      }
      break;
    }

    $link_array = array();

    if (array_key_exists($path, $links)) {
      if (array_key_exists('href', $links[$path])) {
        $link_array['href'] = $links[$path]['href'];
      }
      if (array_key_exists('onclick', $links[$path])) {
        $link_array['onclick'] = $links[$path]['onclick'];
      }
      if (array_key_exists('onmouseover', $links[$path])) {
        $link_array['onmouseover'] = $links[$path]['onmouseover'];
      }
      if (array_key_exists('target', $links[$path])) {
        $link_array['target'] = $links[$path]['target'];
      }
    }
    $add_link = FALSE;
    if (!empty($link_array)) {
      $add_link = TRUE;
    }
    if (!empty($this->link) && !$this->linkhighlightedonly) {
      $add_link = TRUE;
    }
    if (!empty($this->link) && ($this->linkhighlightedonly && $highlightedpath)) {
      $add_link = TRUE;
    }

    // municipalities
    if ($map_definitions['map_settings']['basemap_interactive'] && array_key_exists($path, $map_names)) {
      if (array_key_exists($path, $tooltips)) {
        // FALSE: no 'sprintf' to put Kloekecodes into links
        // TRUE: use the fourth parameter as the "id" attribute of the element
        $this->_create_area_element(
          'poly', $coords, $map_names[$path], $tooltips[$path], $path, $link_array, $highlightedpath, FALSE,
          TRUE
        );
      } elseif ($this->_interactive || $add_link) {
        $this->_create_area_element(
          'poly', $coords, $map_names[$path], '', $path, $link_array, $highlightedpath, FALSE, TRUE
        );
      }
    }
  }

  /**
   * Tekent een of meer extra achtergronden tussen grondkaart en punten
   *
   * @access private
   *
   * @param $backgrounds array namen van af te beelden achtergronden
   */
  private function _drawBackgrounds($backgrounds)
  {
    if (in_array('daan_blok_1969', $backgrounds)) {

      /** @noinspection PhpIncludeInspection */
      require('Daan_Blok.defs.inc.php');

      /** @var $daan_en_blok_1969 array with coordinates of the dialect areas from Daan & Blok 1969, defined in Daan_Blok.defs.inc.php */
      foreach ($daan_en_blok_1969 as $id => $coordinaten) {

        $this->_rd2pixels($coordinaten);

        /** @var $daan_en_blok_1969_fills array with fill colors of the dialect areas from Daan & Blok 1969, defined in Daan_Blok.defs.inc.php */
        if (array_key_exists($id, $daan_en_blok_1969_fills)) {
          list($r, $g, $b) = \Image_Color::hex2rgb($daan_en_blok_1969_fills[$id]);
          $color = imagecolorallocate($this->gd_image, $r, $g, $b);
          imagefilledpolygon($this->gd_image, $coordinaten, $color);
        } else {
          imagepolygon($this->gd_image, $coordinaten, count($coordinaten) / 2, $this->_colors['grey']);
        }
      }
    }
  }


  /**
   * Tekent de titel boven de kaart
   *
   * @access private
   */
  private function _drawTitle($width, $height)
  {
    if (empty($this->title)) {
      return;
    }

    $title_fontsize = ($this->_svg_title_fontsize * $this->_fontsize_factor) / $this->_bitmap_factor;

    // Truuk om text te centreren binnen plaatje
    // van http://nl3.php.net/manual/en/function.imageftbbox.php gehaald
    $details = imageftbbox($title_fontsize, 0, KAART_BITMAP_TITLEFONT, $this->title);
    $title_x = ($width - $details[4]) / 2;
    $title_y = round($height / $this->_title_y_factor);

    imagefttext(
      $this->gd_image, $title_fontsize, 0, $title_x, $title_y, $this->_colors['black'], KAART_BITMAP_TITLEFONT,
      $this->title
    );
  }
}
