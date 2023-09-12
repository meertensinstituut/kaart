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

namespace Meertens\Kaart;
use Image_Color;
use Meertens\Kaart\Output\Choropleth;
use Meertens\Kaart\Output\DutchLanguageArea;

/**
 * Central configuration file
 */
require('Kaart.config.inc.php');

/**
 * This class can be used to create two kinds of maps:
 *
 * 1. A map of the dutch language area (Netherlands, Belgian Flanders, French Flanders),
 * showing, if desired, also provincial borders and major rivers. One or more series of
 * placemarks indicating cities or villages, using various symbols and colors, can be drawn
 * on this base map.
 *
 * 2. A choropleth map of either the municipalities of The Netherlands (borders as of 2007,
 * 443 municipalities), the municipalities of Flanders (308 municipalities), the
 * municipalities of The Netherlands and Flanders combined, the 40 COROP areas (regions) of
 * The Netherlands (see {@link http://nl.wikipedia.org/w/index.php?title=COROP&oldid=31809700
 * COROP} (Dutch) or {@link http://en.wikipedia.org/w/index.php?title=COROP&oldid=482861432
 * COROP} (English)), the twelve provinces of The Netherlands, or the 28 dialect areas of
 * Daan/Blok (1969) mapped on municipality borders. Areas can be assigned colors, typically
 * to denote the relative frequency of some phenomenon. Some predefined combinations are also
 * possible, at the moment: municipalities with COROP areas, municipalities with provinces,
 * municipalities with dialect areas.
 *
 * Output formats are: SVG (default), PNG, GIF, JPG, KML, GeoJSON.
 *
 * The software can be installed with the PEAR installer:
 *
 * <kbd>$ pear install Kaart3-3.0.0.tgz</kbd>
 *
 * Summary: basic usage.
 *
 * To mark the cities of Amsterdam and Rotterdam with a medium-sized yellow square, and
 * create a PNG image:
 *
 * <code>
 * require('Kaart3/Kaart.class.php');
 *
 * $codes = array('E109p', 'K005p');
 *
 * $kaart = new Kaart();
 * $kaart->addData($codes);
 * $kaart->setFullSymbol('square', 6000, 'fill:yellow; stroke:black; stroke-width:500;');
 * $kaart->show('png');
 * </code>
 *
 * The code numbers for the cities are so-called <i>Kloeke codes</i>. Kloeke codes (named
 * after the Dutch dialect geographer G.G. Kloeke) are unique identifiers for a few thousand
 * cities, towns and villages in the Netherlands, Belgian Flanders, French Flanders and the
 * northwest of Germany. A (Dutch language) web interface for the system is available at
 * {@link http://www.meertens.knaw.nl/kloeke/}. Possible Kloeke codes can be requested from
 * the Kaart class by calling <kbd>$kaart->getPossiblePlacemarks()</kbd>.
 *
 * To color the municipality areas of Amsterdam and Rotterdam with red and green,
 * respectively, and create an SVG image:
 *
 * <code>
 * require('Kaart.class.php');
 *
 * $municipalities = array('g_0363' => '#FF0000', 'g_0599' => '#00FF00');
 *
 * $kaart = new Kaart('municipalities');
 * $kaart->addData($municipalities);
 * $kaart->show('svg');
 * </code>
 *
 * The code numbers for the municipalities are the official Dutch municipality codes,
 * available at {@link http://www.cbs/nl}, prefixed by <tt>g_</tt> so that they can be used
 * as values of id attributes in HTML.
 *
 * Possible choropleth map types are: 'municipalities', 'gemeentes', 'corop', 'provincies',
 * 'provinces', 'municipalities_nl_flanders',      'municipalities_flanders', 'dialectareas',
 * 'daan_blok_1969'. For each of these types, the possible values for areas can be requested
 * from the Kaart class by calling <kbd>$kaart->getPossibleAreas()</kbd>.
 *
 * Some more examples
 *
 * Draw two series of places, with legend texts:
 *
 * <code>
 * $kaart = new Kaart();
 * $series[0]['codes'] = array('A001q', 'E179p');
 * $series[1]['codes'] = array('O275p','O286p');
 * $series[0]['legend'] = 'phenomenon this';
 * $series[1]['legend'] = 'phenomenon that';
 * foreach($series as $s) {
 *     $kaart->addData($s['codes']);
 *     $kaart->setLegend($s['legend']);
 * }
 * </code>
 *
 * There are eleven different symbols available:
 * 'circle', 'square', 'triangle', 'bar_horizontal', 'bar_vertical', 'line_horizontal',
 * 'line_vertical', 'slash_left', 'slash_right', 'plus', 'star'.
 *
 * If the symbols are not assigned by the user they get assigned in the order above, with
 * styling taken from the <tt>Netherlands_Flanders.ini</tt> file (section 'symbol_styles').
 * This order is designed so that the stacking order of symbols makes sense, with later
 * symbols visible on top of earlier symbols.
 *
 * Symbols can be styled as follows.
 * In one go (fourth parameter, index number of the series, is optional, default is "the
 * current series"):
 * <code>
 * $kaart->setFullSymbol('square', 6000, 'fill:yellow; stroke:black; stroke-width:500;');
 * </code>
 * Or the elements one by one (second parameter, index number, is again optional):
 * <code>
 * $kaart->setSymbol('square');
 * $kaart->setSize(6000);
 * $kaart->setStyle('fill:yellow; stroke:black; stroke-width:500;');
 * </code>
 * $kaart->setSymbol(string $symbol) can be used for KML-maps if $symbol is a valid URL to a
 * Google Earth-internal or external image file.
 * Instead of $kaart->setStyle() it is also possible to use $kaart->setColor(string $color),
 * where $color can be either an HTML color name,
 * an #RRGGBB hex string, or an AABBGGRR hex string. The AA (opacity) part only as an effect
 * on KML maps.
 *
 * If a particular series of Kloeke codes should be displayed on the map, but should not be a
 * part of the legend (meaning, it is actually a part of the background), you can accomplish
 * that like this:
 *
 * <code>
 * $kaart->addData($background_kloekecodes); // add series (default: visible in legend)
 * $kaart->moveDataToBackground(); // $background_kloekecodes series invisible for legend
 * $kaart->addData($foreground_kloekecodes); // othersseries which stays visible in legend
 * </code>
 *
 *
 * If you want the numbers of placemarks which are combined across series visible in the
 * legend, you must turn that on explicitly:
 * <code>
 * $kaart->setCombinations();
 * </code>
 * Now the last part of the legend is overlapping symbols and their numbers.
 *
 * It is possible to show the frequency of duplicate Kloeke codes with the size of the
 * symbol:
 *
 * <code>
 * $kaart->setMapType('frequency');
 * </code>
 *
 * A placemark (Kloeke code) which occurs twice is now drawn twice as big, three times
 * becomes three times as big, etcetera. Note: this does not work yet for KML maps.
 *
 * The map can have a title at the top:
 *
 * <code>
 * $kaart->setTitle('an interesting title');
 * </code>
 *
 * If you call the method <kbd>$kaart->setInteractive()</kbd>, the symbols (for symbol maps)
 * or areas (for choropleth maps) will show, in SVG maps, "onmouseover" the placename and
 * Kloeke code at the bottom of the map. In bitmap maps this is done with an image map with
 * <area> elements and "title" attributes, meaning that you need the picture and some HTML to
 * accomplish the same effect with tooltips:
 *
 * <code>
 * $kaart->setInteractive();
 * $kaart->saveAsFile('/path/to/name_of_map.png', 'png');
 *  echo '<img src="path/from/point_of_view/of/webserver/name_of_map.png"
 *  width="' . $kaart->getPixelWidth() .
 * '" height="' . $kaart->getPixelHeight() . '" usemap="#mapname" />';
 *    echo '<map name="mapname">';
 *  // contains <area> elements for the relevant features of the map
 *  echo $kaart->getImagemap();
 *  echo '</map>';
 * </code>
 *
 * It is possible to make features of the map clickable, so that you can use that to run an
 * external script:
 * <code>
 * $kaart->setLink('script.php?code=%s');
 * </code>
 *
 * This code provides the symbols in SVG with <a xlink:href="script.php?kloeke_code=...">
 * links. '%s' is automatically replaced with the applicable Kloeke code or area code (using
 * <kbd>sprintf()</kbd>).
 * It is possible to add a 'target' as a second parameter to open the links in another frame
 * or window. The third parameter can be the index number of the series (default is, again,
 * the current series).
 *
 * In bitmap maps the href and target attributes are added to the <area> elements of
 * <kbd>$kaart->getImagemap()</kbd>.
 *
 * It is possible to add JavaScript events to placemarks on a map:
 *
 * <code>
 * $kaart->setJavaScript("parent.frames['framename'].location='script.php?code=%s';return
 * false;", 'onclick');
 * </code>
 *
 * The second parameter can be 'onclick', 'onmouseover' or 'onmouseout'. Events can be
 * combined. The default is 'onclick'. The third parameter, index number of the series, is
 * again optional, default is "the current series".
 * The %s placeholder gets automatically replaced by the code of the are.
 * In SVG maps the events are added to <g> elements; in bitmap maps the events are added to
 * the <area> elements of <kbd>$kaart->getImagemap()</kbd>.
 *
 * Note that, for bitmap maps at least, this kind of inline JavaScript is not the most
 * efficient or elegant way to create interactive maps. A better way to add events to maps is
 * to do this client-side using e.g. jQuery.
 *
 * The included basemap for symbol maps has the following possible parts: national and
 * provincial borders, large rivers, and provinces as separate units of The Netherlands and
 * Flanders. The parts to be drawn can be set like this:
 *
 * <code>
 * $kaart->setParts($array_with_parts);
 * </code>
 *
 * Legal part names for the symbol map can be found by calling
 * <kbd>$kaart->getPossibleParts()</kbd>.
 * If you don't use <kbd>setParts()</kbd> the complete basemap is drawn.
 *
 * For choropleth maps, the complete base map (of the requested type) is always drawn.
 *
 * @package   Kaart
 * @version   3.0
 * <tt>
 * $LastChangedDate: 2020-01-13 11:27:14 +0100 (ma, 13 jan 2020) $
 * $LastChangedRevision: 385 $
 * </tt>
 * @author    originally based on code by Ilse van Gemert
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2006-2012 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU GPL version 2
 *
 * @method addData
 * @method addPostalCodes
 * @method setLegend
 * @method setSize
 * @method setCombinations (bool $combinations)
 * @method setRemoveDuplicates (bool $removeduplicates)
 * @method setFullSymbol
 * @method setSymbol
 * @method setColor
 * @method setStyle
 * @method setOutlinedColor
 * @method setJavaScript
 * @method setParts (array $parts)
 * @method moveDataToBackground
 * @method getCombinations
 * @method setAltitudeDifference (int $altitudedifference)
 * @method getInvalidKloekeCodes
 * @method setLink (string $link)
 * @method addLinks
 * @method setMapType (string $maptype)
 * @method addTooltips
 * @method setLinkHighlighted (string $link)
 * @method setBackground (string $background)
 */
class Kaart
{

    /**
     * @internal
     * @var $kaartobject DutchLanguageArea|Choropleth
     */
    public $kaartobject;
    private $_choroplethtypes
        = array(
            'municipalities', 'gemeentes', 'corop', 'provincies', 'provinces', 'municipalities_nl_flanders',
            'municipalities_flanders', 'dialectareas', 'daan_blok_1969'
        );

    /**
     * The constructor. For each map type, an alternative paths file or ini file can be given, to be used instead of
     * the default.
     *
     * Path files contain coordinates and names for the features of the map; ini files contain details
     * for the styling and settings for different map formats. As an example of a paths file, see e.g.
     * ./Kaart/municipalities.inc.php for a paths file. Examples of .ini files can be found in the data directory.
     *
     * @param string $map
     * @param null|string $paths_file
     * @param null|string $ini_file
     */
    public function __construct($map = 'dutchlanguagearea', $paths_file = NULL, $ini_file = NULL)
    {
        if ($paths_file !== NULL && stream_resolve_include_path($paths_file) === FALSE) {
            $paths_file = NULL;
        }
        if ($ini_file !== NULL && stream_resolve_include_path($ini_file) === FALSE) {
            $ini_file = NULL;
        }
        if ($map == 'dutchlanguagearea') {
//            require_once('DutchLanguageArea.php');
            $this->kaartobject = new DutchLanguageArea($paths_file, $ini_file);
        }

        if (in_array($map, $this->_choroplethtypes)) {
//            require_once('Choropleth.php');
            if ($map == 'municipalities_nl_flanders') {
                $map = 'municipalities';
                $additionalpathsfiles = array('municipalities_flanders.inc.php', 'border_nl_be.inc.php');
                $ini_file = 'municipalities_netherlands_flanders.ini';
            }
            if ($map == 'municipalities_flanders') {
                $ini_file = 'municipalities_flanders.ini';
            }
            $this->kaartobject = new Choropleth($map, $paths_file, $ini_file);
            if (isset($additionalpathsfiles)) {
                $this->setAdditionalPathsFiles($additionalpathsfiles);
            }
        }
    }

    /**
     * Used to transparently call methods on the $kaartobject property of this class.
     * Depending on the map type created, $kaartobject is of a different class (Kaart_DutchLanguageArea
     * or Kaart_Choropleth).
     *
     * @param $method
     * @param $arguments
     *
     * @return mixed|null
     * @internal
     *
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->kaartobject, $method)) {
            return call_user_func_array(array($this->kaartobject, $method), $arguments);
        } else {
            return null;
        }
    }

    /**
     * @param $array
     * @param $value
     *
     * @return array
     * @internal
     *
     */
    public static function array_fill_keys($array, $value)
    {
        // PHP > 5.2
        if (function_exists('array_fill_keys')) {
            return array_fill_keys($array, $value);
        }

        // PHP < 5.2
        $returnvalue = array();
        foreach ($array as $key) {
            $returnvalue[$key] = $value;
        }

        return $returnvalue;
    }

    /**
     * Creates a connection to the database with coordinates (MySQL-specific at the moment)
     *
     * @param $dbname
     *
     * @return \mysqli | bool FALSE (connection failed) or mysqli object (connection successful)
     */
    public static function createDBConnection($dbname): bool|\mysqli
    {
        $db_connection = mysqli_connect(KAART_GEO_DB_HOST, KAART_GEO_DB_USER, KAART_GEO_DB_PASSWORD);
        if (!$db_connection)
            return FALSE;

        mysqli_select_db($db_connection, $dbname);
        return $db_connection;
    }


    /**
     * Fetches the coordinates of one Kloeke code from the database
     *
     * @param string 'new' (5-character) Kloeke code
     * @param res    resource for connection with the database
     *
     * @return array array with x- and y-coordinate (National Triangulation system) of the place in question
     * @internal
     *
     */
    public static function getCoordinates($kloeke_nr, $db_connection)
    {
        $query = str_replace('##KLOEKE_PLACEHOLDER##', mysqli_real_escape_string($db_connection, $kloeke_nr), KAART_COORDINATEN_QUERY);
        $result = mysqli_query($db_connection, $query);
        if (!$result || mysqli_num_rows($result) != 1) {
            return array(0, 0);
        } else {
            return mysqli_fetch_row($result);
        }
    }

    /**
     * Fetches the placename of one Kloeke code from the database
     *
     * @param string 'new' (5-character) Kloeke code
     * @param res    resource for connection with the database
     *
     * @return string placename
     * @internal
     *
     */
    public static function getPlaatsnaam($kloeke_nr, $db_connection)
    {
        $query = str_replace('##KLOEKE_PLACEHOLDER##', mysqli_real_escape_string($db_connection, $kloeke_nr), KAART_PLAATSNAAM_QUERY);
        $result = mysqli_query($db_connection, $query);
        if (!$result || mysqli_num_rows($result) != 1) {
            return '';
        } else {
            $rij = mysqli_fetch_row($result);
            $plaatsnaam = $rij[0];
            $encoding = mysqli_character_set_name($db_connection);
            if ($encoding != 'utf8') {
                $plaatsnaam = iconv('ISO-8859-1', 'UTF-8', $plaatsnaam);
            }
            return $plaatsnaam;
        }
    }


    /**
     * @param $format
     *
     * @return string
     */
    private function _getFormat($format)
    {

        if ($format == 'svg' || $format == 'kml' || $format == 'json') {
            $type = $format;
        } else {
            $type = 'bitmap';
        }

        return $type;
    }

    /**
     * Returns the map as string or binary stream
     *
     * @param string string indicating format of the map (svg, png, gif, jpeg, kml, json)
     *
     * @return string document containing the map
     */
    public function fetch($format = 'svg')
    {
        $retval = null;
        $type = $this->_getFormat($format);

        if ($this->kaartobject->containsSubseries()) {
            $this->kaartobject->createMapSubseries($type);
        } else {
            $this->kaartobject->createMap($type);
        }

        switch ($format) {
            case 'svg':
                $retval = $this->kaartobject->svg->svg->bufferObject();
                break;
            case 'png':
                ob_start();
                imagepng($this->kaartobject->bitmap->gd_image);
                $retval = ob_get_contents();
                ob_end_clean();
                imagedestroy($this->kaartobject->bitmap->gd_image);
                break;
            case 'gif':
                ob_start();
                imagegif($this->kaartobject->bitmap->gd_image);
                $retval = ob_get_contents();
                ob_end_clean();
                imagedestroy($this->kaartobject->bitmap->gd_image);
                break;
            case 'jpeg':
                ob_start();
                imagejpeg($this->kaartobject->bitmap->gd_image);
                $retval = ob_get_contents();
                ob_end_clean();
                imagedestroy($this->kaartobject->bitmap->gd_image);
                break;
            case 'kml':
                $retval = $this->kaartobject->kml->dom->saveXML();
                break;
            case 'json':
                $retval = $this->kaartobject->json->toJSON();
                break;
        }
        return $retval;
    }

    /**
     * Hands the map over to a web browser for further handling. Depending on the capabilities and
     * setting of the browser, the map will be embedded on the page, handed to another application, or
     * downloaded.
     *
     * @param string string indicating format of the map (svg, png, gif, jpeg, kml, json)
     */
    public function show($format = 'svg')
    {
        $type = $this->_getFormat($format);

        if ($this->kaartobject->containsSubseries()) {
            $this->kaartobject->createMapSubseries($type);
        } else {
            $this->kaartobject->createMap($type);
        }

        switch ($format) {
            case 'svg':
                header('Content-type: image/svg+xml');
                $this->kaartobject->svg->svg->printElement();
                break;
            case 'png':
                header('Content-type: image/png');
                imagepng($this->kaartobject->bitmap->gd_image);
                imagedestroy($this->kaartobject->bitmap->gd_image);
                break;
            case 'gif':
                header('Content-type: image/gif');
                imagegif($this->kaartobject->bitmap->gd_image);
                imagedestroy($this->kaartobject->bitmap->gd_image);
                break;
            case 'jpeg':
                header('Content-type: image/jpeg');
                imagejpeg($this->kaartobject->bitmap->gd_image);
                imagedestroy($this->kaartobject->bitmap->gd_image);
                break;
            case 'kml':
                header('Content-type: application/vnd.google-earth.kml+xml');
                header('Content-Disposition: attachment; filename="map.kml"');
                echo $this->kaartobject->kml->dom->saveXML();
                break;
            case 'json':
                header('Content-type: application/json');
                echo $this->kaartobject->json->toJSON();
                break;
        }
    }

    /**
     * Saves the map as a file
     *
     * @param string string containing path to file where the map should be written to
     * @param string string indicating format of the map (svg, png, gif, jpeg, kml, json)
     *
     * @return bool TRUE if saving was successful, FALSE otherwise
     */
    public function saveAsFile($filename, $format = 'svg')
    {
        $type = $this->_getFormat($format);

        if (file_exists($filename)) {
            @unlink($filename);
        }

        if (@!$fp = fopen($filename, 'w')) {
            return FALSE;
        } else {

            if ($this->kaartobject->containsSubseries()) {
                $this->kaartobject->createMapSubseries($type);
            } else {
                $this->kaartobject->createMap($type);
            }

            switch ($format) {
                case 'svg':
                    fwrite($fp, $this->kaartobject->svg->svg->bufferObject());
                    break;
                case 'png':
                    imagepng($this->kaartobject->bitmap->gd_image, $filename);
                    imagedestroy($this->kaartobject->bitmap->gd_image);
                    break;
                case 'gif':
                    imagegif($this->kaartobject->bitmap->gd_image, $filename);
                    imagedestroy($this->kaartobject->bitmap->gd_image);
                    break;
                case 'jpeg':
                    imagejpeg($this->kaartobject->bitmap->gd_image, $filename);
                    imagedestroy($this->kaartobject->bitmap->gd_image);
                    break;
                case 'kml':
                    $this->kaartobject->kml->dom->save($filename);
                    break;
                case 'json':
                    fwrite($fp, $this->kaartobject->json->toJSON());
                    break;
            }
            return TRUE;
        }
    }

    /**
     * For bitmap maps only: returns a string of <area> elements for features of the map, to use in an imagemap
     * HTML element. Must be called after creating a map!
     *
     * @return mixed string of <area> elements or FALSE if not a bitmap map
     */
    public function getImagemap()
    {
        if (isset($this->kaartobject->bitmap)) {
            return $this->kaartobject->bitmap->getImagemapAreas();
        } else {
            return FALSE;
        }
    }

    /**
     * Set an alternate file with paths for the map
     *
     * Warning: file should not be user-submitted as it is included as-is. Format in the same way as
     * Kaart/municipalities.inc.php or Kaart/Netherlands_Flanders.inc.php
     *
     * @param string string containing file name or path to file with alternate paths
     */
    public function setPathsFile($paths_file)
    {
        if (stream_resolve_include_path($paths_file)) {
            $this->kaartobject->kaart_paths_file = $paths_file;
        }
    }

    /**
     * Set an alternative .ini file with map settings. See the .ini files in the data directory as examples.
     *
     * @param $ini_file string containing file name or path to file
     */
    public function setIniFile($ini_file)
    {
        if (stream_resolve_include_path($ini_file)) {
            $this->kaartobject->setIniFile($ini_file);
        }
    }

    /**
     * Add more layers on top of the base map. Typical use case: combining different choropleth type in one map,
     * e.g. municipalities as the base with borders of larger areas drawn on top of them.
     *
     * Example:
     * <code>
     * $kaart = new Kaart('municipalities'); // base map with municipalities
     * $kaart->setAdditionalPathsFiles(array('corop.inc.php')); // add COROP areas on top of it
     * $kaart->setIniFile('municipalities_extra.ini'); // existing .ini file for correct styling of this combination
     * </code>
     *
     * @param array $paths_files
     */
    public function setAdditionalPathsFiles($paths_files)
    {
        foreach ($paths_files as $file) {
            if (stream_resolve_include_path($file)) {
                $this->kaartobject->additional_paths_files[] = $file;
            }
        }
    }


    /**
     * Get the title of the map
     *
     * @return string the title
     */
    public function getTitle()
    {
        return $this->kaartobject->title;
    }

    /**
     * Set the title of the map
     *
     * @param $text string containing the title
     */
    public function setTitle($text)
    {
        $this->kaartobject->title = $text;
    }

    /**
     * Translates color names to hex codes and vice versa
     *
     * @param $color               string indicating color (AABBGGRR hex, #RRGGBB hex, or HTML color name)
     * @param $current_symbol_type mixed
     *
     * @return array met respectievelijk de #RRGGBB en de AABBGGRR hex representatie
     * @internal
     *
     */
    public static function translateColor($color, $current_symbol_type = NULL)
    {
        if (preg_match('/^[0-9a-fA-F]{8}$/', $color)) {
            // Google Earth AABBGGRR hex
            $bbggrr = substr($color, -6);
            $rr = substr($bbggrr, 4, 2);
            $gg = substr($bbggrr, 2, 2);
            $bb = substr($bbggrr, 0, 2);
            $ge_color = strtolower($color);
            $hex_color = '#' . $rr . $gg . $bb;
        } elseif (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            // #RRGGBB hex
            $rrggbb = substr($color, -6);
            $bb = substr($rrggbb, 4, 2);
            $gg = substr($rrggbb, 2, 2);
            $rr = substr($rrggbb, 0, 2);
            // opacity set to fully opaque
            $ge_color = 'ff' . strtolower($bb . $gg . $rr);
            $hex_color = $color;
        } else {
            // presumably color name, or "none"
            if ($color != 'none') {
                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                list($r, $g, $b) = Image_Color::namedColor2RGB($color);
                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                $rrggbb = Image_Color::rgb2hex(array($r, $g, $b));
                $bb = substr($rrggbb, 4, 2);
                $gg = substr($rrggbb, 2, 2);
                $rr = substr($rrggbb, 0, 2);
                $hex_color = '#' . $rrggbb;
            } else {
                $hex_color = 'none';
            }
            // GE _symbols can't have "fill:none"!
            // if the current color == 'none' && the current symbol is of type 'filled',
            // emulated empty fill with a 25% transparent white fill in KML
            if ($color == 'none' && $current_symbol_type == 'filled') {
                $ge_color = '7fffffff';
            } elseif (isset($rrggbb)) {
                // opacity set to fully opaque
                $ge_color = 'ff' . strtolower($bb . $gg . $rr);
            } else {
                $ge_color = 'ffffffff';
            }
        }

        return array($hex_color, $ge_color);
    }


    /**
     * Adds JavaScript or title attributes to show information about places or areas onmouseover()
     *
     * Format is 'Placename (Kloeke code)' for symbol maps, or 'Area name' for chorpleth maps.
     * Embedded, using JavaScript, in the map itself with SVG maps;
     * a list of <area> tags with title attributes is used to achieve the same effect in bitmap maps
     *
     * @param bool boolean TRUE or FALSE, interactive on or off
     */
    public function setInteractive($value = TRUE)
    {
        $this->kaartobject->interactive = $value;
    }

    /**
     * Set the height of the map in pixels
     *
     * Default height is 0.9 times the width, if you don't want that you can overrule it with this method.
     * Probably you shouldn't, though. Note that this method should always be called after setPixelWidth().
     *
     * @param int integer for the desired height
     */
    public function setPixelHeight($height)
    {
        $this->kaartobject->height = $height;
    }

    /**
     * Set the width of the map in pixels
     *
     * If not used, width is the default value, defined in the .ini file for the map type
     * Default height is a factor in the .ini file times the width
     *
     * @param int integer for the desired width
     */
    public function setPixelWidth($width)
    {
        $this->kaartobject->width = $width;
        $this->kaartobject->height = round(
            $this->kaartobject->width * $this->kaartobject->map_definitions['map_settings']['height_factor']
        );
        $this->kaartobject->width_manually_changed = TRUE;
    }

    /**
     * Returns the width of the map in pixels
     *
     * @return int width of the map
     */
    public function getPixelWidth()
    {
        return $this->kaartobject->width;
    }

    /**
     * Returns the height of the map in pixels
     *
     * @return int height of the map
     */
    public function getPixelHeight()
    {
        return $this->kaartobject->height;
    }

}
