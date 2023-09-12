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
 * Addon to implement system.describeMethods in PEAR's XML_RPC implementation
 *
 * Licensed under the LGPL for compatibility with PEAR's XML_RPC package
 * @package    Kaart
 * @subpackage xml-rpc
 * @author     Jan Pieter Kunst <jan.pieter.kunst@meertens.knaw.nl>
 * @copyright  2006-2007 Meertens Instituut / KNAW
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt GNU LGPL version 2.1
 * @todo       return more info for signatures (think of a place to store this info first)
 * @todo       fully implement RFC from xmlrpc-epi.sourceforge.net
 */

/**
 * signature for system.describeMethods: return = struct, parameters = an array or nothing
 * @global array $GLOBALS['XML_RPC_Server_describeMethods_sig']
 */
$GLOBALS['XML_RPC_Server_describeMethods_sig'] = array(
    array   (   $GLOBALS['XML_RPC_Struct'],
                $GLOBALS['XML_RPC_Array']
            ),
    array   (   $GLOBALS['XML_RPC_Struct'])
);

/**
 * Add a key to the XML_RPC_Server_dmap (dispatch map) global array for the system.describeMethods method
 * @global array $GLOBALS['XML_RPC_Server_dmap']['system.describeMethods']
 */
$GLOBALS['XML_RPC_Server_dmap']['system.describeMethods'] = array(
        'function'  => 'Meertens_XML_RPC_Server_describeMethods',
        'signature' => $GLOBALS['XML_RPC_Server_describeMethods_sig'],
        'docstring' => 'Fully describes the methods implemented by this XML-RPC server'
    );

/**
 * Returns a description of all the methods implemented by the XML-RPC server
 *
 * Partial implementation of the system.describeMethods RFC: currently basically
 * a combination of the system.listMethods, system.methodHelp and system.methodSignature
 * methods, which are already implemented by the PEAR XML-RPC implementation.
 *
 * @link http://xmlrpc-epi.sourceforge.net/specs/rfc.system.describeMethods.php
 * @param obj the XML-RPC server (an XML_RPC_Server object)
 * @param obj an XML_RPC_Message object
 * @return obj a new XML_RPC_Response object
 */
function Meertens_XML_RPC_Server_describeMethods($server, $m) {

    if ($m->getNumParams() == 0) {
        $response = XML_RPC_Server_listMethods($server, $m);
        $methods = XML_RPC_decode($response->value());
    } else {
        $methods = XML_RPC_decode($m->getParam(0)); // parameter 0 = array of method names
    }
    
    $methoddescriptions = array();
    
    foreach($methods as $method) {
    
        $msg = new XML_RPC_Message('system.methodHelp', array(new XML_RPC_Value($method, $GLOBALS['XML_RPC_String'])));
        $response = XML_RPC_Server_methodHelp($server, $msg);
        $methoddescriptions[$method]['purpose'] = $response->value();
        
        $msg =  new XML_RPC_Message('system.methodSignature', array(new XML_RPC_Value($method, $GLOBALS['XML_RPC_String'])));
        $response = XML_RPC_Server_methodSignature($server, $msg);
        $sigs = XML_RPC_decode($response->value());
        
        $returntypes = array();
        $parameters = array();
        foreach($sigs as $signature) {
            $returntypes[] = array_shift($signature); // string with XML-RPC type of return value
            $parameters[] = $signature;
        }
        
        $signatures = array();
        foreach($returntypes as $index => $returntype) {
            $valuedescription['type'] = new XML_RPC_Value($returntype, $GLOBALS['XML_RPC_String']);
            $returnstruct = new XML_RPC_Value($valuedescription, $GLOBALS['XML_RPC_Struct']);
            
            // this one is superfluous in my opinion, an XML-RPC method always has one return value.
            // however, http://xmlrpc-epi.sourceforge.net/specs/rfc.system.describeMethods.php
            // specifies an array for 'returns', so I return an array with always one element
            // in this implementation.
            $returnarray = new XML_RPC_Value(array($returnstruct), $GLOBALS['XML_RPC_Array']);
            
            if (! empty($parameters[$index])) {
                $parameterlist = $parameters[$index];
                $paramarray = array();
                foreach($parameterlist as $paramtype) {
                    $valuedescription['type'] = new XML_RPC_Value($paramtype, $GLOBALS['XML_RPC_String']);
                    $paramarray[] = new XML_RPC_Value($valuedescription, $GLOBALS['XML_RPC_Struct']);
                }
                $paramarray =  new XML_RPC_Value($paramarray, $GLOBALS['XML_RPC_Array']);
                $sig_array = array('params' => $paramarray, 'returns' => $returnarray);
            } else {
                $sig_array = array('returns' => $returnarray);
            }
            
            $signatures[] = new XML_RPC_Value($sig_array, $GLOBALS['XML_RPC_Struct']);
        }

        $methoddescriptions[$method]['signatures'] = new XML_RPC_Value($signatures, $GLOBALS['XML_RPC_Array']);
    }
    
    $response_array = array();
    foreach($methoddescriptions as $method => $info) {
        $response_array[] = new XML_RPC_Value(array('name' => new XML_RPC_Value($method, $GLOBALS['XML_RPC_String']), 'purpose' => $info['purpose'], 'signatures' => $info['signatures']), $GLOBALS['XML_RPC_Struct']);
    }
    
    $val = new XML_RPC_Value($response_array, $GLOBALS['XML_RPC_Array']);
    
    return new XML_RPC_Response($val);
}

?>