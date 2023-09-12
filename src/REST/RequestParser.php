<?php

namespace Meertens\Kaart\REST;

class RequestParser
{

    private $_errors = array();
    private $_allowed_formats = array('gif', 'png', 'jpg', 'jpeg', 'svg', 'kml', 'json');
    private $_allowed_types
        = array(
            'dutchlanguagearea', 'gemeentes', 'municipalities', 'corop', 'provincies', 'provinces',
            'municipalities_nl_flanders', 'municipalities_flanders', 'dialectareas', 'daan_blok_1969'
        );
    private $_allowed_additional_data = array('provincies', 'provinces', 'corop', 'dialectareas', 'daan_blok_1969');
    private $_allowed_maptypes = array('standard', 'frequency');
    private $_parameters = array();
    private $_raw_request_array = array();
    /**
     * @var string $datakey_regexp : regular expression to match municipalities, corop, provinces, dialectareas
     */
    private $_datakey_regexp = '/g_\d|corop_\d|p_\d|dial_\d/';
    public $error = FALSE;

    public function __construct()
    {
        $rawpostdata = file_get_contents("php://input");

        if (!empty($rawpostdata)) {
            $this->_parseRawPostData($rawpostdata);
        }

        // returns the first uploaded file, irrespective of the name chosen in the form
        // FALSE if no file uploaded
        $uploaded_files = array_values($_FILES);
        $uploaded_file = reset($uploaded_files);
        if (is_array($uploaded_file)) {
            $this->_parseCSVFile($uploaded_file['tmp_name']);
        }

        $request_array = array_merge($_GET, $_POST);

        if (!empty($request_array)) {
            $this->_raw_request_array = $this->_getRequestArray();
        }

        if (!empty($this->_raw_request_array)) {
            // no map creation requested, map type implicit, so no need to check map parameters
            if (isset($this->_raw_request_array['possibleparts']) && (!isset($this->_raw_request_array['type']))) {
                $this->_checkBooleanTrue('possibleparts');
            } elseif (isset($this->_raw_request_array['possibleplacemarks']) && (!isset($this->_raw_request_array['type']))) {
                $this->_checkBooleanTrue('possibleplacemarks');
                $this->_checkParameter('complete');
                // implicit: municipalities
            } elseif (
                isset($this->_raw_request_array['possiblemunicipalities']) && (!isset($this->_raw_request_array['type']))
            ) {
                $this->_checkBooleanTrue('possiblemunicipalities');
            } else {
                $this->_checkParameter('type');
                $this->_checkParameter('format');
                $this->_checkParameter('width');
                $this->_checkParameter('height');
                $this->_checkParameter('imagemap');
                $this->_checkParameter('interactive');
                $this->_checkParameter('title');
                $this->_checkParameter('altitudedifference');
                $this->_checkParameter('removeduplicates');
                $this->_checkParameter('parts');
                $this->_checkParameter('maptype');
                $this->_checkParameter('showcombinations');
                $this->_checkParameter('background');
                $this->_checkParameter('target');
                $this->_checkParameter('link');
                $this->_checkParameter('linkhighlightedonly');
                $this->_checkParameter('base64');
                $this->_checkParameter('data');
                $this->_checkParameter('possibleplacemarks');
                $this->_checkParameter('possibleparts');
                $this->_checkParameter('possiblemunicipalities');
                $this->_checkParameter('possibleareas'); // can apply to either municipalities, COROP or provinces
                $this->_checkParameter('additionaldata');
                $this->_checkParameter('pathsfile');
                $this->_checkParameter('drawlegend');
            }
        }

        if (!empty($this->_errors)) {
            $this->error = TRUE;
        }
    }

    public function getParameters()
    {
        return $this->_parameters;
    }

    public function getError()
    {
        return join("\n", $this->_errors);
    }

    private function _checkParameter($param)
    {

        switch ($param) {

            case 'type':
                if (!isset($this->_raw_request_array['type'])) {
                    $this->_errors[] = 'Parameter "type" (one of ' . join(', ', $this->_allowed_types) . ') missing';
                } elseif (!in_array($this->_raw_request_array['type'], $this->_allowed_types)) {
                    $this->_errors[]
                        = 'Parameter type ' . $this->_raw_request_array['type'] . ' not one of ' . join(', ', $this->_allowed_types);
                } else {
                    $this->_parameters['type'] = $this->_raw_request_array['type'];
                }
                break;
            case 'format':
                if (!isset($this->_raw_request_array['format'])) {
                    $this->_parameters['format'] = 'png';
                } elseif (!in_array($this->_raw_request_array['format'], $this->_allowed_formats)) {
                    $this->_errors[] = 'Parameter format ' . $this->_raw_request_array['format'] . ' not one of ' . join(
                            ', ', $this->_allowed_formats
                        );
                } else {
                    $this->_parameters['format'] = $this->_raw_request_array['format'];
                }
                break;
            case 'title':
                if (isset($this->_raw_request_array['title'])) {
                    $this->_parameters['title'] = $this->_raw_request_array['title'];
                }
                break;
            case 'target':
                if (isset($this->_raw_request_array['target'])) {
                    $this->_parameters['target'] = $this->_raw_request_array['target'];
                }
                break;
            case 'link':
                if (isset($this->_raw_request_array['link'])) {
                    $this->_parameters['link'] = $this->_raw_request_array['link'];
                }
                break;
            case 'width':
            case 'height':
            case 'altitudedifference':
                $this->_checkInteger($param);
                break;
            case 'parts':
                if (isset($this->_raw_request_array['parts'])) {
                    $ini = parse_ini_file('Netherlands_Flanders.ini', TRUE);
                    $possibleparts = array_keys($ini['kaart_parts']);
                    $impossibleparts = array();
                    if (is_string($this->_raw_request_array['parts'])) {
                        // assume comma-separated string
                        $this->_raw_request_array['parts'] = explode(',', $this->_raw_request_array['parts']);
                    }
                    foreach ($this->_raw_request_array['parts'] as $part) {
                        if (!in_array($part, $possibleparts)) {
                            $impossibleparts[] = $part;
                        }
                    }
                    if (!empty($impossibleparts)) {
                        $this->_errors[]
                            = 'Requested map part(s) ' . join(', ', $impossibleparts) . ' not one of ' . join(', ', $possibleparts);
                    } else {
                        $this->_parameters['parts'] = $this->_raw_request_array['parts'];
                    }
                }
                break;
            case 'maptype':
                if (isset($this->_raw_request_array['maptype'])) {
                    if (!in_array($this->_raw_request_array['maptype'], $this->_allowed_maptypes)) {
                        $this->_errors[]
                            = 'Parameter maptype ' . $this->_raw_request_array['maptype'] . ' not one of ' . join(
                                ', ', $this->_allowed_maptypes
                            );
                    } else {
                        $this->_parameters['maptype'] = $this->_raw_request_array['maptype'];
                    }
                }
                break;
            case 'complete':
            case 'imagemap':
            case 'interactive':
            case 'removeduplicates':
            case 'showcombinations':
            case 'linkhighlightedonly':
            case 'possibleparts':
            case 'possibleplacemarks':
            case 'possiblemunicipalities':
            case 'possibleareas':
            case 'base64':
                $this->_checkBooleanTrue($param);
                break;
            case 'drawlegend':
                $this->_checkBooleanFalse($param);
                break;
            case 'background':
                if (isset($this->_raw_request_array['background'])) {
                    $ini = parse_ini_file('Netherlands_Flanders.ini', TRUE);
                    $possiblebackgrounds = array_keys($ini['kaart_backgrounds']);
                    if (!in_array($this->_raw_request_array['background'], $possiblebackgrounds)) {
                        $this->_errors[]
                            = 'Requested map background ' . $this->_raw_request_array['background'] . ' not one of ' . join(
                                ', ', $possiblebackgrounds
                            );
                    } else {
                        $this->_parameters['background'] = $this->_raw_request_array['background'];
                    }
                }
                break;
            case 'data':
                if (isset($this->_raw_request_array['data'])) {
                    if (is_array($this->_raw_request_array['data'])) {
                        $this->_parameters['data'] = $this->_raw_request_array['data'];
                        // assume comma-separated string
                    } elseif (is_string($this->_raw_request_array['data'])) {
                        $this->_parameters['data'] = explode(',', $this->_raw_request_array['data']);
                    }
                }
                break;
            case 'additionaldata':
                if (isset($this->_raw_request_array['additionaldata'])) {
                    $additional_data = explode(',', $this->_raw_request_array['additionaldata']);
                    foreach ($additional_data as $d) {
                        if (!in_array($d, $this->_allowed_additional_data)) {
                            $this->_errors[]
                                = 'Parameter additionaldata ' . $d . ' not one of ' . join(
                                    ', ', $this->_allowed_additional_data
                                );
                        } else {
                            $this->_parameters['additionaldata'][] = $d;
                        }
                    }
                }
                break;
            case 'pathsfile':
                // leave it to the Kaart class to see if this file can be included
                if (isset($this->_raw_request_array['pathsfile'])) {
                    $this->_parameters['pathsfile'] = $this->_raw_request_array['pathsfile'];
                }
                break;
        }
    }

    private function _parseRawPostData($rawpostdata)
    {
        $data = json_decode($rawpostdata);

        if (is_null($data)) {
            $this->_errors[] = 'Error parsing JSON input';
        } elseif (is_array($data)) {
            $this->_parameters['data'] = $data;
        } elseif (is_object($data)) {
            $tmp = $this->_object_to_array($data);
            $keys = array_keys($tmp);
            $key = array_shift($keys);
            if (preg_match($this->_datakey_regexp, $key)) {
                $this->_parameters['data'] = $tmp;
            } else {
                if (isset($tmp['data'])) {
                    $this->_parameters['data'] = $tmp['data'];
                    unset($tmp['data']);
                    // assume that it's a comma-seperated list
                    if (is_string($this->_parameters['data'])) {
                        $this->_parameters['data'] = explode(',', $this->_parameters['data']);
                    }
                }
                if (isset($tmp['links'])) {
                    $this->_parameters['links'] = $tmp['links'];
                    unset($tmp['links']);
                }
                if (isset($tmp['tooltips'])) {
                    $this->_parameters['tooltips'] = $tmp['tooltips'];
                    unset($tmp['tooltips']);
                }
                $this->_raw_request_array = $tmp;
            }
        }
    }

    private function _parseCSVFile($filename)
    {
        $tmp['data'] = array();
        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($data = fgetcsv($handle)) !== FALSE) {
                $tmp['data'][$data[0]] = $data[1];
            }
            fclose($handle);
        }

        $this->_parameters['data'] = $tmp['data'];
    }

    private function _getRequestArray()
    {
        return array_merge($this->_raw_request_array, $_GET, $_POST);
    }

    /**
     * http://codesnippets.joyent.com/posts/show/1641
     */
    private function _object_to_array($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = $this->_object_to_array($value);
            }
            return $result;
        }
        return $data;
    }

    /**
     * Accept parameter as boolean only if 1, true, on or yes
     *
     * @param string parameter
     */
    private function _checkBooleanTrue($key)
    {
        if (isset($this->_raw_request_array[$key])) {
            $value = strtolower($this->_raw_request_array[$key]);
            if (in_array($value, array('1', 'true', 'on', 'yes'))) {
                $this->_parameters[$key] = TRUE;
            }
        }
    }

    /**
     * Accept parameter as boolean only if 0, off, false or no
     *
     * @param string parameter
     */
    private function _checkBooleanFalse($key)
    {
        if (isset($this->_raw_request_array[$key])) {
            $value = strtolower($this->_raw_request_array[$key]);
            if (in_array($value, array('0', 'false', 'off', 'no'))) {
                $this->_parameters[$key] = FALSE;
            }
        }
    }


    /**
     * Accept parameter as integer only if of ctype_digit
     *
     * @param string parameter
     */
    private function _checkInteger($key)
    {
        if (isset($this->_raw_request_array[$key])) {
            if (!ctype_digit(strval($this->_raw_request_array[$key]))) {
                $this->_errors[] = 'Parameter ' . $key . ' ' . $this->_raw_request_array[$key] . ' is not an integer';
            } else {
                $this->_parameters[$key] = $this->_raw_request_array[$key];
            }
        }
    }

}

?>