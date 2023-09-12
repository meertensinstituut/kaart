<?php

namespace Meertens\Kaart\REST;

use Meertens\Kaart\Kaart;

define('REST_DEFAULT_ALLOWED_CLIENT', $_SERVER['SERVER_ADDR']);

class Webservice
{
    // add to this array as needed
    private static $_allowed_rest_clients = array(REST_DEFAULT_ALLOWED_CLIENT);
    private static $_allowed_choropleth_types
        = array(
            'gemeentes', 'municipalities', 'corop', 'provincies', 'provinces', 'municipalities_nl_flanders',
            'municipalities_flanders', 'dialectareas', 'daan_blok_1969'
        );
    private static $_linking_denied_message = 'Adding links is not allowed from your location. You will need to install the software on your own server.';
    private static $_javascript_denied_message = 'Adding javascript is not allowed from your location. You will need to install the software on your own server.';

    public static function createMap($kaart, $type, $data, $parameters)
    {

        if ($type == 'dutchlanguagearea') {
            if (empty($data)) {
                self::_createSimpleMap($kaart, $data, $parameters);
            } else {
                if (!is_array($data[0])) {
                    self::_createSimpleMap($kaart, $data, $parameters);
                } elseif (is_array($data[0])) {
                    self::_createComplexMap($kaart, $data, $parameters);
                }
            }
        } elseif (in_array($type, self::$_allowed_choropleth_types)) {
            self::_createChoroplethMap($kaart, $parameters);
        }
    }

    /**
     * Function for creating a simple map from one array
     *
     * @param $kaart      Kaart | Kaart_DutchLanguageArea
     * @param $data       array with Kloeke codes or postal codes
     * @param $parameters array parameters for the map as a whole
     */
    private static function _createSimpleMap($kaart, $data, $parameters)
    {

        // quick-n-dirty check of de lijst postcodes of kloekenummers bevat
        // nog geen rekening gehouden met door elkaar postcodes en kloekenummers
        // komt wel
        if (!empty($data)) {
            if (is_numeric($data[0])) {
                $kaart->addPostalCodes($data);
            } else {
                $kaart->addData($data);
            }
        }
        self::_handleParameters($kaart, $parameters);
    }

    /**
     * Function for creating a complex map from more than one array, possible extra parameters
     *
     * @param $kaart      Kaart | Kaart_DutchLanguageArea
     * @param $data       array with Kloeke codes or postal codes
     * @param $parameters array parameters for the map as a whole
     */
    private static function _createComplexMap($kaart, $data, $parameters)
    {
        // order by offset
        $volgnummer = array();
        foreach ($data as $key => $set) {
            $volgnummer[$key] = $set['offset'];
        }
        array_multisort($volgnummer, SORT_ASC, $data);

        foreach ($data as $set) {

            $series = NULL;

            // 'series' is what is called '_subseries' in the Kaart object -- a subdivision of a series (element of the legend)
            if (array_key_exists('series', $set)) {
                $series = $set['series'];
            } else {
                $series = NULL;
            }

            if (array_key_exists('kloekecodes', $set)) {
                $kaart->addData($set['kloekecodes'], NULL, $series);
            } elseif (array_key_exists('postalcodes', $set)) {
                $kaart->addPostalCodes($set['postalcodes'], NULL, $series);
            }

            if (!isset($parameters['drawlegend']) || $parameters['drawlegend'] == TRUE) {
                if (array_key_exists('name', $set)) {
                    $kaart->setLegend($set['name']);
                } else {
                    $kaart->setLegend('series ' . $set['offset']);
                }
            }

            if (array_key_exists('color', $set)) {
                $color = trim($set['color']);
                $kaart->setColor($color);
            }

            if (array_key_exists('outlinedcolor', $set)) {
                list($fill, $outline) = $set['outlinedcolor'];
                $kaart->setOutlinedColor(trim($fill), trim($outline));
            }

            if (array_key_exists('size', $set)) {
                $size = trim($set['size']);
                $kaart->setSize($size);
            }

            if (array_key_exists('link', $set)) {

                if (! in_array($_SERVER['REMOTE_ADDR'], self::$_allowed_rest_clients)) {
                    $link = 'javascript:alert("' . self::$_linking_denied_message . '");';
                    $target = NULL;
                } else {
                    $link = trim($set['link']);
                    $target = NULL;
                    if (array_key_exists('target', $set)) {
                        $target = trim($set['target']);
                    }
                }

                $kaart->setLink($link, $target);
            }

            if (array_key_exists('onclick', $set)) {
                if (! in_array($_SERVER['REMOTE_ADDR'], self::$_allowed_rest_clients)) {
                    $onclick = 'javascript:alert("' . self::$_javascript_denied_message . '");';
                } else {
                    $onclick = trim($set['onclick']);
                }
                $kaart->setJavaScript($onclick, 'onclick');
            }

            if (array_key_exists('onmouseover', $set)) {
                if (! in_array($_SERVER['REMOTE_ADDR'], self::$_allowed_rest_clients)) {
                    $onmouseover = 'javascript:alert("' . self::$_javascript_denied_message . '");';
                } else {
                    $onmouseover = trim($set['onmouseover']);
                }
                $kaart->setJavaScript($onmouseover, 'onmouseover');
            }

            if (array_key_exists('onmouseout', $set)) {
                if (! in_array($_SERVER['REMOTE_ADDR'], self::$_allowed_rest_clients)) {
                    $onmouseout = 'javascript:alert("' . self::$_javascript_denied_message . '");';
                } else {
                    $onmouseout = trim($set['onmouseout']);
                }
                $kaart->setJavaScript($onmouseout, 'onmouseout');
            }

            if (array_key_exists('symbol', $set)) {
                $symbol = trim($set['symbol']);
                $kaart->setSymbol($symbol);
            }
        }
        self::_handleParameters($kaart, $parameters);
    }

    /**
     * @static
     *
     * @param $kaart Kaart| Kaart_Choropleth
     * @param $parameters
     */
    private static function _createChoroplethMap($kaart, $parameters)
    {

        if (isset($parameters['data'])) {
            $kaart->addData($parameters['data']);
        }
        if (isset($parameters['links'])) {
            if (! in_array($_SERVER['REMOTE_ADDR'], self::$_allowed_rest_clients)) {
                $redacted_links = array();
                foreach($parameters['links'] as $code => $link) {
                    $redacted_links[$code] = 'javascript:alert("' . self::$_linking_denied_message . '");';
                }
                $parameters['links'] = $redacted_links;
                if (isset($parameters['target'])) {
                    unset($parameters['target']);
                }
            }
            if (isset($parameters['target'])) {
                $kaart->addLinks($parameters['links'], $parameters['target']);
            } else {
                $kaart->addLinks($parameters['links']);
            }
        }
        if (isset($parameters['tooltips'])) {
            $kaart->addTooltips($parameters['tooltips']);
        }
        self::_handleParameters($kaart, $parameters);
    }

    /**
     * @static
     *
     * @param $kaart Kaart
     * @param $parameters
     */
    private static function _handleParameters($kaart, $parameters)
    {

        if (array_key_exists('linkhighlightedonly', $parameters) && $parameters['linkhighlightedonly'] != TRUE) {
            unset($parameters['linkhighlightedonly']);
        }


        if (array_key_exists('altitudedifference', $parameters)) {
            $kaart->setAltitudeDifference(intval($parameters['altitudedifference']));
        }

        if (array_key_exists('removeduplicates', $parameters) && $parameters['removeduplicates'] == TRUE) {
            $kaart->setRemoveDuplicates(TRUE);
        }

        // backwards compatible with v. 2
        if (array_key_exists('remove_duplicates', $parameters) && $parameters['remove_duplicates'] == TRUE) {
            $kaart->setRemoveDuplicates(TRUE);
        }

        if (array_key_exists('interactive', $parameters) && $parameters['interactive'] == TRUE) {
            $kaart->setInteractive(TRUE);
        }

        if (array_key_exists('showcombinations', $parameters) && $parameters['showcombinations'] == TRUE) {
            $kaart->setCombinations(TRUE);
        }

        // backwards compatible with v. 2
        if (array_key_exists('show_combinations', $parameters) && $parameters['show_combinations'] == TRUE) {
            $kaart->setCombinations(TRUE);
        }

        // backwards compatible with v. 2
        if (array_key_exists('pixelwidth', $parameters)) {
            $kaart->setPixelWidth(intval($parameters['pixelwidth']));
        }

        if (array_key_exists('width', $parameters)) {
            $kaart->setPixelWidth(intval($parameters['width']));
        }

        if (array_key_exists('title', $parameters)) {
            $kaart->setTitle(trim($parameters['title']));
        }

        if (array_key_exists('maptype', $parameters)) {
            $kaart->setMapType(trim($parameters['maptype']));
        }

        if (array_key_exists('background', $parameters)) {
            $kaart->setBackground($parameters['background']);
        }

        if (array_key_exists('link', $parameters)) {
            if (! in_array($_SERVER['REMOTE_ADDR'], self::$_allowed_rest_clients)) {
                $parameters['link'] = 'javascript:alert("' . self::$_linking_denied_message . '");';
            }
            if (!array_key_exists('linkhighlightedonly', $parameters)) {
                $kaart->setLink($parameters['link']);
            } elseif (array_key_exists('linkhighlightedonly', $parameters)) {
                $kaart->setLinkHighlighted($parameters['link']);
            }
        }

        if (array_key_exists('parts', $parameters)) {
            $kaart->setParts($parameters['parts']);
        }

        if (array_key_exists('additionaldata', $parameters)) {
            if ($parameters['type'] == 'gemeentes' || $parameters['type'] == 'municipalities') {
                $additionalpathsfiles = array();
                if (in_array('corop', $parameters['additionaldata'])) {
                    $additionalpathsfiles[] = 'corop.inc.php';
//          $additionalpathsfiles[] = 'borders_inner_corop.inc.php';
                }
                if (
                    in_array('provincies', $parameters['additionaldata']) || in_array('provinces', $parameters['additionaldata'])
                ) {
                    $additionalpathsfiles[] = 'provinces.inc.php';
//          $additionalpathsfiles[] = 'borders_inner_provinces.inc.php';
                }
                if (in_array('dialectareas', $parameters['additionaldata']) || in_array('daan_blok_1969', $parameters['additionaldata'])) {
                    $additionalpathsfiles[] = 'dialectareas.inc.php';
                }
                $kaart->setAdditionalPathsFiles($additionalpathsfiles);
                $kaart->setIniFile('municipalities_extra.ini');
            } elseif (in_array('daan_blok_1969', $parameters['additionaldata'])) {
                $kaart->setBackground('daan_blok_1969');
            }
        }
    }
}

