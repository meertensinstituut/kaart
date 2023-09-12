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
 * Details to create database connection
 *
 * Place this file outside the web server's document root (see Kaart.config.inc.php)
 *
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2006-2007 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU GPL version 2
 * @package   Kaart
 */

/**
 * Name of the database with geographical data
 */
define('KAART_GEO_DB', $_ENV['KAART_GEO_DB']);
/**
 * Name of the database with GIS data for historical municipalities (Boonstra)
 */
define('KAART_NLGIS_DB', $_ENV['KAART_NLGIS_DB']);

define('KAART_GEO_DB_HOST', $_ENV['KAART_GEO_DB_HOST']);
/**
 * Make sure that this is a user who has SELECT privileges on KAART_GEO_DB and KAART_NLGIS_DB
 */
define('KAART_GEO_DB_USER', $_ENV['KAART_GEO_DB_USER']);
define('KAART_GEO_DB_PASSWORD', $_ENV['KAART_GEO_DB_PASSWORD']);

