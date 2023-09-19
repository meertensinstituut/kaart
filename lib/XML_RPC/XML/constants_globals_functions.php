<?php


if (!function_exists('xml_parser_create')) {
    include_once 'PEAR.php';
    PEAR::loadExtension('xml');
}

if (!function_exists('each')) {
    function each(array &$array) {
        $value = current($array);
        $key = key($array);

        if (is_null($key)) {
            return false;
        }

        // Move pointer.
        next($array);

        return array(1 => $value, 'value' => $value, 0 => $key, 'key' => $key);
    }
}

/**#@+
 * Error constants
 */
/**
 * Parameter values don't match parameter types
 */
define('XML_RPC_ERROR_INVALID_TYPE', 101);
/**
 * Parameter declared to be numeric but the values are not
 */
define('XML_RPC_ERROR_NON_NUMERIC_FOUND', 102);
/**
 * Communication error
 */
define('XML_RPC_ERROR_CONNECTION_FAILED', 103);
/**
 * The array or struct has already been started
 */
define('XML_RPC_ERROR_ALREADY_INITIALIZED', 104);
/**
 * Incorrect parameters submitted
 */
define('XML_RPC_ERROR_INCORRECT_PARAMS', 105);
/**
 * Programming error by developer
 */
define('XML_RPC_ERROR_PROGRAMMING', 106);
/**#@-*/


/**
 * Data types
 * @global string $GLOBALS['XML_RPC_I4']
 */
$GLOBALS['XML_RPC_I4'] = 'i4';

/**
 * Data types
 * @global string $GLOBALS['XML_RPC_Int']
 */
$GLOBALS['XML_RPC_Int'] = 'int';

/**
 * Data types
 * @global string $GLOBALS['XML_RPC_Boolean']
 */
$GLOBALS['XML_RPC_Boolean'] = 'boolean';

/**
 * Data types
 * @global string $GLOBALS['XML_RPC_Double']
 */
$GLOBALS['XML_RPC_Double'] = 'double';

/**
 * Data types
 * @global string $GLOBALS['XML_RPC_String']
 */
$GLOBALS['XML_RPC_String'] = 'string';

/**
 * Data types
 * @global string $GLOBALS['XML_RPC_DateTime']
 */
$GLOBALS['XML_RPC_DateTime'] = 'dateTime.iso8601';

/**
 * Data types
 * @global string $GLOBALS['XML_RPC_Base64']
 */
$GLOBALS['XML_RPC_Base64'] = 'base64';

/**
 * Data types
 * @global string $GLOBALS['XML_RPC_Array']
 */
$GLOBALS['XML_RPC_Array'] = 'array';

/**
 * Data types
 * @global string $GLOBALS['XML_RPC_Struct']
 */
$GLOBALS['XML_RPC_Struct'] = 'struct';


/**
 * Data type meta-types
 * @global array $GLOBALS['XML_RPC_Types']
 */
$GLOBALS['XML_RPC_Types'] = array(
    $GLOBALS['XML_RPC_I4']       => 1,
    $GLOBALS['XML_RPC_Int']      => 1,
    $GLOBALS['XML_RPC_Boolean']  => 1,
    $GLOBALS['XML_RPC_String']   => 1,
    $GLOBALS['XML_RPC_Double']   => 1,
    $GLOBALS['XML_RPC_DateTime'] => 1,
    $GLOBALS['XML_RPC_Base64']   => 1,
    $GLOBALS['XML_RPC_Array']    => 2,
    $GLOBALS['XML_RPC_Struct']   => 3,
);


/**
 * Error message numbers
 * @global array $GLOBALS['XML_RPC_err']
 */
$GLOBALS['XML_RPC_err'] = array(
    'unknown_method'      => 1,
    'invalid_return'      => 2,
    'incorrect_params'    => 3,
    'introspect_unknown'  => 4,
    'http_error'          => 5,
    'not_response_object' => 6,
    'invalid_request'     => 7,
);

/**
 * Error message strings
 * @global array $GLOBALS['XML_RPC_str']
 */
$GLOBALS['XML_RPC_str'] = array(
    'unknown_method'      => 'Unknown method',
    'invalid_return'      => 'Invalid return payload: enable debugging to examine incoming payload',
    'incorrect_params'    => 'Incorrect parameters passed to method',
    'introspect_unknown'  => 'Can\'t introspect: method unknown',
    'http_error'          => 'Didn\'t receive 200 OK from remote server.',
    'not_response_object' => 'The requested method didn\'t return an XML_RPC_Response object.',
    'invalid_request'     => 'Invalid request payload',
);


/**
 * Default XML encoding (ISO-8859-1, UTF-8 or US-ASCII)
 * @global string $GLOBALS['XML_RPC_defencoding']
 */
$GLOBALS['XML_RPC_defencoding'] = 'UTF-8';

/**
 * User error codes start at 800
 * @global int $GLOBALS['XML_RPC_erruser']
 */
$GLOBALS['XML_RPC_erruser'] = 800;

/**
 * XML parse error codes start at 100
 * @global int $GLOBALS['XML_RPC_errxml']
 */
$GLOBALS['XML_RPC_errxml'] = 100;


/**
 * Compose backslashes for escaping regexp
 * @global string $GLOBALS['XML_RPC_backslash']
 */
$GLOBALS['XML_RPC_backslash'] = chr(92) . chr(92);


/**
 * Should we automatically base64 encode strings that contain characters
 * which can cause PHP's SAX-based XML parser to break?
 * @global boolean $GLOBALS['XML_RPC_auto_base64']
 */
$GLOBALS['XML_RPC_auto_base64'] = false;


/**
 * Valid parents of XML elements
 * @global array $GLOBALS['XML_RPC_valid_parents']
 */
$GLOBALS['XML_RPC_valid_parents'] = array(
    'BOOLEAN' => array('VALUE'),
    'I4' => array('VALUE'),
    'INT' => array('VALUE'),
    'STRING' => array('VALUE'),
    'DOUBLE' => array('VALUE'),
    'DATETIME.ISO8601' => array('VALUE'),
    'BASE64' => array('VALUE'),
    'ARRAY' => array('VALUE'),
    'STRUCT' => array('VALUE'),
    'PARAM' => array('PARAMS'),
    'METHODNAME' => array('METHODCALL'),
    'PARAMS' => array('METHODCALL', 'METHODRESPONSE'),
    'MEMBER' => array('STRUCT'),
    'NAME' => array('MEMBER'),
    'DATA' => array('ARRAY'),
    'FAULT' => array('METHODRESPONSE'),
    'VALUE' => array('MEMBER', 'DATA', 'PARAM', 'FAULT'),
);


/**
 * Stores state during parsing
 *
 * quick explanation of components:
 *   + ac     = accumulates values
 *   + qt     = decides if quotes are needed for evaluation
 *   + cm     = denotes struct or array (comma needed)
 *   + isf    = indicates a fault
 *   + lv     = indicates "looking for a value": implements the logic
 *               to allow values with no types to be strings
 *   + params = stores parameters in method calls
 *   + method = stores method name
 *
 * @global array $GLOBALS['XML_RPC_xh']
 */
$GLOBALS['XML_RPC_xh'] = array();

$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents("php://input");

/**
 * Start element handler for the XML parser
 *
 * @return void
 */
function XML_RPC_se($parser_resource, $name, $attrs)
{
    global $XML_RPC_xh, $XML_RPC_valid_parents;

    $parser = strlen(get_class($parser_resource));

    // if invalid xmlrpc already detected, skip all processing
    if ($XML_RPC_xh[$parser]['isf'] >= 2) {
        return;
    }

    // check for correct element nesting
    // top level element can only be of 2 types
    if (count($XML_RPC_xh[$parser]['stack']) == 0) {
        if ($name != 'METHODRESPONSE' && $name != 'METHODCALL') {
            $XML_RPC_xh[$parser]['isf'] = 2;
            $XML_RPC_xh[$parser]['isf_reason'] = 'missing top level xmlrpc element';
            return;
        }
    } else {
        // not top level element: see if parent is OK
        if (!in_array($XML_RPC_xh[$parser]['stack'][0], $XML_RPC_valid_parents[$name])) {
            $name = preg_replace('@[^a-zA-Z0-9._-]@', '', $name);
            $XML_RPC_xh[$parser]['isf'] = 2;
            $XML_RPC_xh[$parser]['isf_reason'] = "xmlrpc element $name cannot be child of {$XML_RPC_xh[$parser]['stack'][0]}";
            return;
        }
    }

    switch ($name) {
        case 'STRUCT':
            $XML_RPC_xh[$parser]['cm']++;

            // turn quoting off
            $XML_RPC_xh[$parser]['qt'] = 0;

            $cur_val = array();
            $cur_val['value'] = array();
            $cur_val['members'] = 1;
            array_unshift($XML_RPC_xh[$parser]['valuestack'], $cur_val);
            break;

        case 'ARRAY':
            $XML_RPC_xh[$parser]['cm']++;

            // turn quoting off
            $XML_RPC_xh[$parser]['qt'] = 0;

            $cur_val = array();
            $cur_val['value'] = array();
            $cur_val['members'] = 0;
            array_unshift($XML_RPC_xh[$parser]['valuestack'], $cur_val);
            break;

        case 'NAME':
            $XML_RPC_xh[$parser]['ac'] = '';
            break;

        case 'FAULT':
            $XML_RPC_xh[$parser]['isf'] = 1;
            break;

        case 'PARAM':
            $XML_RPC_xh[$parser]['valuestack'] = array();
            break;

        case 'VALUE':
            $XML_RPC_xh[$parser]['lv'] = 1;
            $XML_RPC_xh[$parser]['vt'] = $GLOBALS['XML_RPC_String'];
            $XML_RPC_xh[$parser]['ac'] = '';
            $XML_RPC_xh[$parser]['qt'] = 0;
            // look for a value: if this is still 1 by the
            // time we reach the first data segment then the type is string
            // by implication and we need to add in a quote
            break;

        case 'I4':
        case 'INT':
        case 'STRING':
        case 'BOOLEAN':
        case 'DOUBLE':
        case 'DATETIME.ISO8601':
        case 'BASE64':
            $XML_RPC_xh[$parser]['ac'] = ''; // reset the accumulator

            if ($name == 'DATETIME.ISO8601' || $name == 'STRING') {
                $XML_RPC_xh[$parser]['qt'] = 1;

                if ($name == 'DATETIME.ISO8601') {
                    $XML_RPC_xh[$parser]['vt'] = $GLOBALS['XML_RPC_DateTime'];
                }

            } elseif ($name == 'BASE64') {
                $XML_RPC_xh[$parser]['qt'] = 2;
            } else {
                // No quoting is required here -- but
                // at the end of the element we must check
                // for data format errors.
                $XML_RPC_xh[$parser]['qt'] = 0;
            }
            break;

        case 'MEMBER':
            $XML_RPC_xh[$parser]['ac'] = '';
            break;

        case 'DATA':
        case 'METHODCALL':
        case 'METHODNAME':
        case 'METHODRESPONSE':
        case 'PARAMS':
            // valid elements that add little to processing
            break;
    }


    // Save current element to stack
    array_unshift($XML_RPC_xh[$parser]['stack'], $name);

    if ($name != 'VALUE') {
        $XML_RPC_xh[$parser]['lv'] = 0;
    }
}

/**
 * End element handler for the XML parser
 *
 * @return void
 */
function XML_RPC_ee($parser_resource, $name)
{
    global $XML_RPC_xh;

    $parser = strlen(get_class($parser_resource));

    if ($XML_RPC_xh[$parser]['isf'] >= 2) {
        return;
    }

    // push this element from stack
    // NB: if XML validates, correct opening/closing is guaranteed and
    // we do not have to check for $name == $curr_elem.
    // we also checked for proper nesting at start of elements...
    $curr_elem = array_shift($XML_RPC_xh[$parser]['stack']);

    switch ($name) {
        case 'STRUCT':
        case 'ARRAY':
            $cur_val = array_shift($XML_RPC_xh[$parser]['valuestack']);
            $XML_RPC_xh[$parser]['value'] = $cur_val['value'];
            $XML_RPC_xh[$parser]['vt'] = strtolower($name);
            $XML_RPC_xh[$parser]['cm']--;
            break;

        case 'NAME':
            $XML_RPC_xh[$parser]['valuestack'][0]['name'] = $XML_RPC_xh[$parser]['ac'];
            break;

        case 'BOOLEAN':
            // special case here: we translate boolean 1 or 0 into PHP
            // constants true or false
            if ($XML_RPC_xh[$parser]['ac'] == '1') {
                $XML_RPC_xh[$parser]['ac'] = 'true';
            } else {
                $XML_RPC_xh[$parser]['ac'] = 'false';
            }

            $XML_RPC_xh[$parser]['vt'] = strtolower($name);
        // Drop through intentionally.

        case 'I4':
        case 'INT':
        case 'STRING':
        case 'DOUBLE':
        case 'DATETIME.ISO8601':
        case 'BASE64':
            if ($XML_RPC_xh[$parser]['qt'] == 1) {
                // we use double quotes rather than single so backslashification works OK
                $XML_RPC_xh[$parser]['value'] = $XML_RPC_xh[$parser]['ac'];
            } elseif ($XML_RPC_xh[$parser]['qt'] == 2) {
                $XML_RPC_xh[$parser]['value'] = base64_decode($XML_RPC_xh[$parser]['ac']);
            } elseif ($name == 'BOOLEAN') {
                $XML_RPC_xh[$parser]['value'] = $XML_RPC_xh[$parser]['ac'];
            } else {
                // we have an I4, INT or a DOUBLE
                // we must check that only 0123456789-.<space> are characters here
                if (!preg_match("@^[+-]?[0123456789 \t\.]+$@", $XML_RPC_xh[$parser]['ac'])) {
                    XML_RPC_Base::raiseError('Non-numeric value received in INT or DOUBLE',
                        XML_RPC_ERROR_NON_NUMERIC_FOUND);
                    $XML_RPC_xh[$parser]['value'] = XML_RPC_ERROR_NON_NUMERIC_FOUND;
                } else {
                    // it's ok, add it on
                    $XML_RPC_xh[$parser]['value'] = $XML_RPC_xh[$parser]['ac'];
                }
            }

            $XML_RPC_xh[$parser]['ac'] = '';
            $XML_RPC_xh[$parser]['qt'] = 0;
            $XML_RPC_xh[$parser]['lv'] = 3; // indicate we've found a value
            break;

        case 'VALUE':
            if ($XML_RPC_xh[$parser]['vt'] == $GLOBALS['XML_RPC_String']) {
                if (strlen($XML_RPC_xh[$parser]['ac']) > 0) {
                    $XML_RPC_xh[$parser]['value'] = $XML_RPC_xh[$parser]['ac'];
                } elseif ($XML_RPC_xh[$parser]['lv'] == 1) {
                    // The <value> element was empty.
                    $XML_RPC_xh[$parser]['value'] = '';
                }
            }

            $temp = new XML_RPC_Value($XML_RPC_xh[$parser]['value'], $XML_RPC_xh[$parser]['vt']);

            $cur_val = array_shift($XML_RPC_xh[$parser]['valuestack']);
            if (is_array($cur_val)) {
                if ($cur_val['members']==0) {
                    $cur_val['value'][] = $temp;
                } else {
                    $XML_RPC_xh[$parser]['value'] = $temp;
                }
                array_unshift($XML_RPC_xh[$parser]['valuestack'], $cur_val);
            } else {
                $XML_RPC_xh[$parser]['value'] = $temp;
            }
            break;

        case 'MEMBER':
            $XML_RPC_xh[$parser]['ac'] = '';
            $XML_RPC_xh[$parser]['qt'] = 0;

            $cur_val = array_shift($XML_RPC_xh[$parser]['valuestack']);
            if (is_array($cur_val)) {
                if ($cur_val['members']==1) {
                    $cur_val['value'][$cur_val['name']] = $XML_RPC_xh[$parser]['value'];
                }
                array_unshift($XML_RPC_xh[$parser]['valuestack'], $cur_val);
            }
            break;

        case 'DATA':
            $XML_RPC_xh[$parser]['ac'] = '';
            $XML_RPC_xh[$parser]['qt'] = 0;
            break;

        case 'PARAM':
            $XML_RPC_xh[$parser]['params'][] = $XML_RPC_xh[$parser]['value'];
            break;

        case 'METHODNAME':
        case 'RPCMETHODNAME':
            $XML_RPC_xh[$parser]['method'] = preg_replace("@^[\n\r\t ]+@", '',
                $XML_RPC_xh[$parser]['ac']);
            break;
    }

    // if it's a valid type name, set the type
    if (isset($GLOBALS['XML_RPC_Types'][strtolower($name)])) {
        $XML_RPC_xh[$parser]['vt'] = strtolower($name);
    }
}

/**
 * Character data handler for the XML parser
 *
 * @return void
 */
function XML_RPC_cd($parser_resource, $data)
{
    global $XML_RPC_xh, $XML_RPC_backslash;

    $parser = strlen(get_class($parser_resource));

    if ($XML_RPC_xh[$parser]['lv'] != 3) {
        // "lookforvalue==3" means that we've found an entire value
        // and should discard any further character data

        if ($XML_RPC_xh[$parser]['lv'] == 1) {
            // if we've found text and we're just in a <value> then
            // turn quoting on, as this will be a string
            $XML_RPC_xh[$parser]['qt'] = 1;
            // and say we've found a value
            $XML_RPC_xh[$parser]['lv'] = 2;
        }

        // replace characters that eval would
        // do special things with
        if (!isset($XML_RPC_xh[$parser]['ac'])) {
            $XML_RPC_xh[$parser]['ac'] = '';
        }
        $XML_RPC_xh[$parser]['ac'] .= $data;
    }
}
