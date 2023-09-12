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
 * @internal
 * @package Kaart
 */
class XML_SVG_Text extends XML_SVG_Textpath
{

    public function printElement($element = 'text')
    {
        parent::printElement($element);
    }

    public function setShape($x, $y, $text)
    {
        $this->_x = $x;
        $this->_y = $y;
        $this->_text = $text;
    }

}
