<?php

//  Copyright (C) 2006-2007 Meertens Instituut / KNAW
//
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.

/**
 * Licensed under the LGPL for compatibility with PEAR's XML_SVG package
 *
 * @internal
 * @author    Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright 2006-2007 Meertens Instituut / KNAW
 * @license   http://www.gnu.org/licenses/old-licenses/lgpl-2.0.txt GNU LGPL version 2
 * @package   Kaart
 */

namespace Meertens\Kaart\Output;

/**
 * Needed because in the XML_SVG package XML_SVG_Document extends XML_SVG_Fragment, and that has too few parameters.
 * Also useful because header(); can now be moved to the Kaart object.
 *
 * @internal
 * @package Kaart
 */
class XML_SVG_Document extends XML_SVG_Fragment
{

    public function printElement()
    {
        print('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        print('<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN"
	        "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">' . "\n");

        parent::printElement();
    }

}