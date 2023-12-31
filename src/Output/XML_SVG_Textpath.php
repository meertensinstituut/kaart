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
 * Work around Firefox 1.5 bugs: added 'font-size' and 'fill' as possible attributes.
 * FF 1.5 does not show text if that information is in a 'style' attribute.
 *
 * @internal
 * @package Kaart
 */
class XML_SVG_Textpath extends \XML_SVG_Element
{

    var $_text;
    var $_x;
    var $_y;
    var $_dx;
    var $_dy;
    var $_rotate;
    var $_textLength;
    var $_lengthAdjust;

    public function printElement($element = 'textpath')
    {
        echo '<' . $element;
        $this->printParams(
            'id', 'x', 'y', 'dx', 'dy', 'rotate',
            'textLength', 'lengthAdjust', 'style',
            'transform', 'font-size', 'fill', 'font-weight',
            'display', 'text-anchor'
        );
        echo '>' . htmlspecialchars($this->_text);
        parent::printElement();
        echo "</$element>\n";
    }

    public function setShape($x, $y, $text)
    {
        $this->_x = $x;
        $this->_y = $y;
        $this->_text = $text;
    }

}