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

/**
 * Central configuration file
 *
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2006-2007 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU GPL version 2
 * @package   Kaart
 */

/**
 * Include directory for database connection details
 *
 * This should be a path outside the web server's document root
 */
define('KAART_SAFE_INCLUDE_PATH', realpath(dirname(__DIR__)));
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . KAART_SAFE_INCLUDE_PATH);

define('KAART_VENDOR_INCLUDE_PATH', realpath(dirname(dirname(KAART_SAFE_INCLUDE_PATH)) . DIRECTORY_SEPARATOR . 'vendor'));
// require(KAART_VENDOR_INCLUDE_PATH . DIRECTORY_SEPARATOR . 'autoload.php');

/**
 * Include directory for .ini files with map information
 *
 * Default is the PEAR data directory
 */
define('KAART_DATADIR', KAART_SAFE_INCLUDE_PATH . DIRECTORY_SEPARATOR . 'ini');
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . KAART_DATADIR);


/**
 * Include directory for files with coordinates
 */
define('KAART_COORDSDIR', KAART_SAFE_INCLUDE_PATH . DIRECTORY_SEPARATOR . 'coords');
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . KAART_COORDSDIR);


/**
 * Include directory for customized path and ini files, specific to particular projects
 */
if (isset($_ENV['KAART_CUSTOM_INCLUDE_PATHS'])) {
    foreach($_ENV['KAART_CUSTOM_INCLUDE_PATHS'] as $path) {
        ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $path);
    }
}

/**
 * URL where the KML version of the map can find its default icons
 */
define('KAART_KML_DEFAULT_ICON_URL', 'http://www.meertens.knaw.nl/kaart/xmlrpc/img');

/**
 * File with database connection details
 *
 * Place this file in KAART_SAFE_INCLUDE_PATH
 */
require_once('geo_db_connect.inc.php');
/**
 * Template for SQL query to fetch coordinates of a Kloeke code
 */
define('KAART_COORDINATEN_QUERY', 'SELECT TRUNCATE(RD_x,0), TRUNCATE(RD_y,0) FROM' . ' ' . KAART_GEO_DB
  . ".kloeke WHERE kloeke_code1='##KLOEKE_PLACEHOLDER##'");
/**
 * Template for SQL query to fetch placename of a Kloeke code
 */
define('KAART_PLAATSNAAM_QUERY',
  'SELECT plaats FROM' . ' ' . KAART_GEO_DB . ".kloeke WHERE kloeke_code1='##KLOEKE_PLACEHOLDER##'");
/**
 * Template for SQL-query to find the Kloeke code which contains a given four-digit postal code
 */
define('KAART_POSTCODE_QUERY',
  'SELECT DISTINCT k.kloeke_code1 FROM' . ' ' . KAART_GEO_DB . '.kloeke AS k JOIN ' . KAART_GEO_DB
  . '.postcode AS p ON (p.kloeke_id=k.kloeke_id) WHERE p.postcode IN (##POSTCODE_PLACEHOLDER##)');

/**
 * JavaScript for SVG maps to show 'onmouseover' placename and Kloeke code of _symbols on the map
 */
define('KAART_ONMOUSEOVER_ECMASCRIPT',
'
var svgdoc;

function init(event) {
	svgdoc = event.target.ownerDocument;
}

function ShowTooltip(txt) {
	var tttelem;
	tttelem=svgdoc.getElementById("ttt");
	tttelem.childNodes.item(0).data = txt;
	tttelem.setAttribute("display", "inherit");
}

function HideTooltip() {
	var tttelem;
	tttelem=svgdoc.getElementById("ttt");
	tttelem.childNodes.item(0).data = "";
}
');


/**
 * PEAR Packages
 */
//require_once('XML/SVG.php');
//require_once('XML/Util.php');

/**
 * Should be Truetype font that can be used by the GD Library
 */
define('KAART_BITMAP_DEFAULTFONT', KAART_SAFE_INCLUDE_PATH . DIRECTORY_SEPARATOR . 'fonts/luxisr.ttf');

/**
 * Should be Truetype font that can be used by the GD Library
 */
define('KAART_BITMAP_TITLEFONT', KAART_SAFE_INCLUDE_PATH . DIRECTORY_SEPARATOR . 'fonts/luxisb.ttf');

/**
 * PEAR Package
 */
//require_once('Image/Color.php');

/**
 * Abstract base class
 *
 * Not relevant for the end user of this package
 */
// require_once('Abstract.php');

/**
 * Abstract base for the map images
 *
 * Not relevant for the end user of this package
 */
// require_once('Image.php');

/**
 * Class to create the bitmap versions of the map (GIF, PNG, JPEG)
 *
 * Not relevant for the end user of this package
 */
// require_once('Bitmap.php');

/**
 * Class to create the SVG version of the map
 *
 * Not relevant for the end user of this package
 */
// require_once('SVG.php');

/**
 * Class to create the KML (Google Earth) version of the map
 *
 * Not relevant for the end user of this package
 */
// require_once('KML.php');

/**
 * Class to create the JSON version of the map
 *
 * Not relevant for the end user of this package
 */
// require_once('JSON.php');
