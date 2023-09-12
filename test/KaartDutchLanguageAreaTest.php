<?php

namespace Meertens\Kaart;

use PHPUnit\Framework\TestCase;

class KaartDutchLanguageAreaTest extends TestCase
{

    /** @var $kaart Kaart */
    var $kaart; // contains the object handle of the Kaart class

    public static function getMethod($name)
    {
        $class = new \ReflectionClass('Meertens\Kaart\Output\DutchLanguageArea');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    function setUp(): void
    {
        // lege kaart (moet alleen grondkaart opleveren)
        $this->kaart = new Kaart();
    }

    function tearDown(): void
    {
        unset($this->kaart);
    }

    private function readAttribute($object, $property)
    {
        return $object->__get($property);
    }

    private function _saveFile($filename, $data)
    {
        file_put_contents(KAART_TESTDIRECTORY . '/' . $filename, $data);
        return file_get_contents(KAART_TESTDIRECTORY . '/' . $filename);
    }

    private function _fileExists($filename, $format)
    {
        $retval = FALSE;
        $message = '';
        @ unlink(KAART_TESTDIRECTORY . '/' . $filename);
        if (file_exists(KAART_TESTDIRECTORY . '/' . $filename)) {
            // weggooien oude file mislukt, dus test kan niet goed uitgevoerd worden
            $message = "could not delete file " . KAART_TESTDIRECTORY . '/' . $filename;
            return array($retval, $message);
        } else {
            $this->kaart->saveAsFile(KAART_TESTDIRECTORY . '/' . $filename, $format);
            $retval = file_exists(KAART_TESTDIRECTORY . '/' . $filename);
            if (!$retval) {
                $message = "could not save file " . KAART_TESTDIRECTORY . '/' . $filename;
            }
        }
        return array($retval, $message);
    }

    private function _addSubseries($filename, $format)
    {
        $this->kaart->addData(array('A001q', 'E109p'), 0, 0);
        $this->kaart->addData(array('M010p', 'B052a'), 1, 0);
        $this->kaart->addData(array('Q222p', 'K076a'), 2, 1);
        $this->kaart->addData(array('L377a', 'M009p'), 3, 1);
        return $this->_saveFile($filename, $this->kaart->fetch($format));
    }

    function testfetchSVG()
    {
        $filename = 'fetchSVG.svg';
        $expected = 'fc6990182bc6b65434fcdbc2e46aacb8';
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testfetchPNG()
    {
        $filename = 'fetchPNG.png';
        $expected = array('f97b7b50d77b2b40de3f0100bf385601', '8f772147c7bd0edd9465bd5cb72dbb0d');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testfetchGIF()
    {
        $filename = 'fetchGIF.gif';
        $expected = array('c4de8769bcd7086c50a7e6e34922b236', '9174573e06e6a51b46d28a063ab6d20a');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('gif')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testfetchJPEG()
    {
        $filename = 'fetchJPEG.jpg';
        $expected = array(
            'ce9e284e0912aba3ff89167f6b1f7ce3',
            '11d1ae11f80858eeb363eac96dd85eaa',
            '017766def2047e580e12de820dd1c564',
            '0f10f07c3b05ba375f6bad5fe8ff6963'
        );
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('jpeg')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testfetchKML()
    {
        $filename = 'fetchKML.kml';
        $expected = 'db6bfccdc79bfeb115cbb1f94f62adf6';
        $this->kaart->addData(array('A001q', 'E109p'));
        $this->kaart->setLegend('dinges');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testfetchJSON()
    {
        $filename = 'fetchJSON.json';
        $expected = array('f305c16e3d86599d606131f7993f45f2', '1a4873bc25cbc480813eada5e990f252');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('json')));
        $this->assertContains($actual, $expected, "check file $filename");
    }


    function testsaveAsFileSVG()
    {
        list($new_file_exists, $message) = $this->_fileExists('kaart.svg', 'svg');
        $this->assertTrue($new_file_exists, $message);
    }

    function testsaveAsFilePNG()
    {
        list($new_file_exists, $message) = $this->_fileExists('kaart.png', 'png');
        $this->assertTrue($new_file_exists, $message);
    }

    function testsaveAsFileGIF()
    {
        list($new_file_exists, $message) = $this->_fileExists('kaart.gif', 'gif');
        $this->assertTrue($new_file_exists, $message);
    }

    function testsaveAsFileJPEG()
    {
        list($new_file_exists, $message) = $this->_fileExists('kaart.jpeg', 'jpeg');
        $this->assertTrue($new_file_exists, $message);
    }

    function testsaveAsFileKML()
    {
        list($new_file_exists, $message) = $this->_fileExists('kaart.kml', 'kml');
        $this->assertTrue($new_file_exists, $message);
    }

    function testsaveAsFileJSON()
    {
        list($new_file_exists, $message) = $this->_fileExists('kaart.json', 'json');
        $this->assertTrue($new_file_exists, $message);
    }

    function testgetCoordinates()
    {
        $expected = array('149332', '600761');
        $this->kaart->addData(array('invalid')); // just to force a connection to the db
        $actual = Kaart::getCoordinates('A001p', $this->readAttribute($this->kaart->kaartobject, 'db_connection'));
        $this->assertEquals($expected, $actual);
    }

    function testaddData_simple()
    {
        $expected_map_array = array(0 => array('A001q', 'E109p'));
        $this->kaart->addData(array('A001q', 'E109p'));
        $actual_map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $this->assertEquals($expected_map_array, $actual_map_array);
    }

    function testaddData_complex()
    {
        $expected_map_array = array(0 => array('A001q'), 1 => array('E109p'));
        $this->kaart->addData(array('A001q'));
        $this->kaart->addData(array('E109p'));
        $actual_map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $this->assertEquals($expected_map_array, $actual_map_array);
    }


    function testaddData_offset()
    {
        $expected_map_array = array(0 => array('A001q', 'Q222p'), 1 => array('E109p'), 2 => array('L377a'));
        $this->kaart->addData(array('A001q'));
        $this->kaart->addData(array('E109p'));
        $this->kaart->addData(array('Q222p'), 0);
        $this->kaart->addData(array('L377a'));
        $actual_map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $this->assertEquals($expected_map_array, $actual_map_array);
    }


    function testaddData_offset_nonexisting_key()
    {
        $expected_map_array = array(0 => array('A001q', 'Q222p'), 1 => array('E109p'), 2 => array('L377a'));
        $this->kaart->addData(array('A001q'));
        $this->kaart->addData(array('E109p'));
        $this->kaart->addData(array('Q222p'), 0);
        $this->kaart->addData(array('L377a'), 4);
        $actual_map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $this->assertEquals($expected_map_array, $actual_map_array);
    }


    function testaddData_offset_string_key()
    {
        $expected_map_array = array(0 => array('A001q', 'Q222p'), 1 => array('E109p'));
        $this->kaart->addData(array('A001q'));
        $this->kaart->addData(array('E109p'));
        $this->kaart->addData(array('Q222p'), 0);
        $this->kaart->addData(array('L377a'), 'string');
        $actual_map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $this->assertEquals($expected_map_array, $actual_map_array);
    }


    function testaddData_series_offset_null()
    {

        $expected_map_array = array(
            0 => array('A001q', 'E109p'), 1 => array('M010p', 'E109p'), 2 => array('Q222p', 'K076a'),
            3 => array('L377a', 'M009p')
        );
        $expected_subseries_array = array(
            0 => array(0 => $expected_map_array[0], 1 => $expected_map_array[1]),
            1 => array(2 => $expected_map_array[2], 3 => $expected_map_array[3])
        );

        $this->kaart->addData(array('A001q', 'E109p'), NULL, 0);
        $this->kaart->addData(array('M010p', 'E109p'), NULL, 0);
        $this->kaart->addData(array('Q222p', 'K076a'), NULL, 1);
        $this->kaart->addData(array('L377a', 'M009p'), NULL, 1);

        $actual_map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $this->assertEquals($expected_map_array, $actual_map_array);
        $actual_subseries_array = $this->readAttribute($this->kaart->kaartobject, '_subseries');
        $this->assertEquals($expected_subseries_array, $actual_subseries_array);
    }


    function testaddData_series_offset_int()
    {

        $expected_map_array = array(
            0 => array('A001q', 'E109p', 'M010p', 'E109p'), 1 => array('Q222p', 'K076a'), 2 => array('L377a', 'M009p')
        );
        $expected_subseries_array = array(
            0 => array(0 => $expected_map_array[0]), 1 => array(1 => $expected_map_array[1], 2 => $expected_map_array[2])
        );

        $this->kaart->addData(array('A001q', 'E109p'), 0, 0);
        $this->kaart->addData(array('M010p', 'E109p'), 0, 0);
        $this->kaart->addData(array('Q222p', 'K076a'), 1, 1);
        $this->kaart->addData(array('L377a', 'M009p'), 2, 1);

        $actual_map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $this->assertEquals($expected_map_array, $actual_map_array);

        $actual_subseries_array = $this->readAttribute($this->kaart->kaartobject, '_subseries');
        $this->assertEquals($expected_subseries_array, $actual_subseries_array);
    }


    function testaddData_series_offset_mixed()
    {

        $expected_map_array = array(0 => array('A001q', 'E109p', 'K076a', 'L377a', 'M009p'), 1 => array('Q222p', 'M010p'));
        $expected_subseries_array = array(0 => array(0 => $expected_map_array[0]), 1 => array(1 => $expected_map_array[1]));

        $this->kaart->addData(array('A001q'), NULL, 0);
        $this->kaart->addData(array('E109p'), 0, 0);
        $this->kaart->addData(array('Q222p'), NULL, 1);
        $this->kaart->addData(array('M010p'), 1, 1);
        $this->kaart->addData(array('K076a', 'L377a', 'M009p'), 0); // komt vanzelf in de eerste subreeks

        $actual_map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $this->assertEquals($expected_map_array, $actual_map_array);

        $actual_subseries_array = $this->readAttribute($this->kaart->kaartobject, '_subseries');
        $this->assertEquals($expected_subseries_array, $actual_subseries_array);
    }


    function testaddPostalCodes()
    {
        $expected_map_array = array(0 => array('K076a', 'L377a', 'M009p'));
        $this->kaart->addPostalCodes(array('6111', '7120', '4152'));
        $actual_map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $this->assertEquals($expected_map_array, $actual_map_array);
    }

    function test_createMapSubseriesPNG()
    {
        $filename = 'createMapSubseries.png';
        $expected = array('105e8557c200a49f2b1e4b4f83569d1c', '36285e68bc2024fe4f38f6a168eb8019');
        $actual = md5($this->_addSubseries($filename, 'png'));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function test_createMapSubseriesKML()
    {
        $filename = 'createMapSubseries.kml';
        $expected = 'c6b418f459160994486bc31290cb4c45';
        $actual = md5($this->_addSubseries($filename, 'kml'));
        $this->assertEquals($expected, $actual, "check file $filename");
    }


    function test_createMapSubseriesSVG()
    {
        $filename = 'createMapSubseries.svg';
        $expected = 'c68abdbff1ab1eb766e7ad10f8fc9669';
        $actual = md5($this->_addSubseries($filename, 'svg'));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function test_createMapSubseriesJSON()
    {
        $filename = 'createMapSubseries.json';
        $expected = array('70114f9d23ae66177f55305a437de50e', 'ae3db36d0df4bdc4ca8499b00f76acfe');
        $actual = md5($this->_addSubseries($filename, 'json'));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function test_createMap()
    {

        $kloekenummers = array(array('A001p'), array('B052a'), array('Q222p'));
        $teksten = array('reeks 1', 'reeks 2', 'reeks 3');
        foreach ($kloekenummers as $offset => $reeks) {
            $this->kaart->addData($reeks);
            $this->kaart->setLegend($teksten[$offset]);
        }

        $svg = simplexml_load_string($this->kaart->fetch());

        // elementen op de kaart
        $expected = '146832';
        $actual = trim($svg->g[0]->rect[0]['x']);
        $this->assertEquals($expected, $actual, 'x-coordinaat A001p');

        $expected = '174958';
        $actual = trim($svg->g[0]->circle[0]['cx']);
        $this->assertEquals($expected, $actual, 'x-coordinaat B052a');

        $expected = '197285,307204 202285,307204 199785,312204';
        $actual = trim($svg->g[0]->polygon[0]['points']);
        $this->assertEquals($expected, $actual, 'driehoek Q222p');

        // elementen van de legenda
        $expected = '8824';
        $expected2 = '7284';
        $expected3 = '16784';
        $expected4 = 'reeks 1 (1)';

        $actual = trim($svg->rect[0]['x']);
        $actual2 = trim($svg->rect[0]['y']);
        $actual3 = trim($svg->text[0]['y']);
        $actual4 = trim(strval($svg->text[0]));

        $this->assertEquals($expected, $actual, 'x-coordinaat 1e legendaelement');
        $this->assertEquals($expected2, $actual2, 'y-coordinaat 1e legendaelement');
        $this->assertEquals($expected3, $actual3, 'y-coordinaat 1e legendatekst');
        $this->assertEquals($expected4, $actual4, '1e legendatekst');

        $expected = '13324';
        $expected2 = '23784';
        $expected3 = '28784';
        $expected4 = 'reeks 2 (1)';

        $actual = trim($svg->circle[0]['cx']);
        $actual2 = trim($svg->circle[0]['cy']);
        $actual3 = trim($svg->text[1]['y']);
        $actual4 = trim(strval($svg->text[1]));
        $this->assertEquals($expected, $actual, 'x-coordinaat 2e legendaelement');
        $this->assertEquals($expected2, $actual2, 'y-coordinaat 2e legendaelement');
        $this->assertEquals($expected3, $actual3, 'y-coordinaat 2e legendatekst');
        $this->assertEquals($expected4, $actual4, '2e legendatekst');

        $expected = '8824,40284 17824,40284 13324,31284';
        $expected2 = '40784';
        $expected3 = 'reeks 3 (1)';

        $actual = trim($svg->polygon[0]['points']);
        $actual2 = trim($svg->text[2]['y']);
        $actual3 = trim(strval($svg->text[2]));

        $this->assertEquals($expected, $actual, 'driehoek 3e legendaelement');
        $this->assertEquals($expected2, $actual2, 'y-coordinaat 3e legendaelement');
        $this->assertEquals($expected3, $actual3, '3e legendatekst');
    }

    function testsetPixelWidth()
    {
        $width = rand(1, 1000);
        $expected_width = $width;
        $expected_height = round($width * 0.9);
        $this->kaart->setPixelWidth($width);
        $actual_width = $this->kaart->getPixelWidth();
        $this->assertEquals($expected_width, $actual_width);
        $actual_height = $this->kaart->getPixelHeight();
        $this->assertEquals($expected_height, $actual_height);
    }

    function testsetPixelHeight()
    {
        $expected = 10;
        $this->kaart->setPixelHeight(10);
        $actual = $this->kaart->getPixelHeight();
        $this->assertEquals($expected, $actual);
    }

    function testgetPixelWidth()
    {
        $expected = strval(400);
        $actual = $this->kaart->getPixelWidth();
        $this->assertEquals($expected, $actual);
    }

    function testgetPixelHeight()
    {
        $expected = 400 * 0.9;
        $expected = strval($expected);
        $actual = strval($this->kaart->getPixelHeight());
        $this->assertEquals($expected, $actual);
    }

    function testsetMapType()
    {
        $expected = 'standard';
        $actual = $this->readAttribute($this->kaart->kaartobject, '_type');
        $this->assertEquals($expected, $actual);
        $expected = 'frequency';
        $this->kaart->setMapType('frequency');
        $actual = $this->readAttribute($this->kaart->kaartobject, '_type');
        $this->assertEquals($expected, $actual);
    }

    function test_getSymbolSizesStandard()
    {
        $expected = $this->readAttribute($this->kaart->kaartobject, '_default_symbolsize');
        $this->kaart->addData(array('A001q', 'A001q', 'E109p'));
        $map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $actual = self::getMethod('_getSymbolSizes')->invokeArgs($this->kaart->kaartobject, array($map_array[0], 0, 'svg'));
        $this->assertEquals($expected, $actual);
    }

    function test_getSymbolSizesFrequency()
    {
        $expected = $this->readAttribute($this->kaart->kaartobject, '_default_symbolsize') * 2;
        $this->kaart->addData(array('A001q', 'A001q', 'E109p'));
        $this->kaart->setMapType('frequency');
        $map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $groottes = self::getMethod('_getSymbolSizes')->invokeArgs(
            $this->kaart->kaartobject, array($map_array[0], 0, 'svg')
        );
        $actual = $groottes['A001q'];
        $this->assertEquals($expected, $actual);
    }

    function testsetLegend()
    {
        $expected = array('reeks 1', 'reeks 2', 'reeks 3');
        $kloekenummers = array(array('A001q'), array('A001q'), array('E109p'));
        $teksten = array('reeks 1', 'reeks 2', 'reeks 3');
        foreach ($kloekenummers as $offset => $reeks) {
            $this->kaart->addData($reeks);
            $this->kaart->setLegend($teksten[$offset]);
        }
        $actual = $this->readAttribute($this->kaart->kaartobject, '_legends');
        $this->assertEquals($expected, $actual);
    }

    function testsetSymbol()
    {
        $expected = 'line_horizontal';
        $this->kaart->addData(array('B052a'));
        $this->kaart->setSymbol('line_horizontal');
        $symbols = $this->readAttribute($this->kaart->kaartobject, '_symbols');
        $actual = $symbols[0];
        $this->assertEquals($expected, $actual);
    }

    function testsetSymbolKMLDefault()
    {
        $expected = 'http://www.meertens.knaw.nl/kaart/xmlrpc/img/line_horizontal.png';
        $this->kaart->addData(array('B052a'));
        $this->kaart->setSymbol('line_horizontal');
        $kml = simplexml_load_string($this->kaart->fetch('kml'));
        $actual = strval($kml->Document->Style[0]->IconStyle->Icon->href);
        $this->assertEquals($expected, $actual);
    }

    function testsetSymbolKMLCustom()
    {
        $expected = 'http://maps.google.com/mapfiles/kml/pushpin/red-pushpin.png';
        $this->kaart->addData(array('B052a'));
        $this->kaart->setSymbol($expected);
        $kml = simplexml_load_string($this->kaart->fetch('kml'));
        $actual = strval($kml->Document->Style[0]->IconStyle->Icon->href);
        $this->assertEquals($expected, $actual);
    }

    function testsetColorBitmapName()
    {
        $filename = 'colorbitmapname.png';
        $expected = array('680c67849988bbad147778bf170bef98', 'b0745b80f117f10b164d0dc451e510c1');
        $this->kaart->addData(array('B052a'));
        $this->kaart->setColor('salmon');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testsetColorBitmapAABBGGRRHex()
    {
        $filename = 'colorbitmapAABBGGRRHex.png';
        $expected = array('680c67849988bbad147778bf170bef98', 'b0745b80f117f10b164d0dc451e510c1');
        $this->kaart->addData(array('B052a'));
        $this->kaart->setColor('ff7280fa'); // salmon aabbggrr
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testsetColorKMLName()
    {
        $expected = 'ff7280fa';
        $this->kaart->addData(array('B052a'));
        $this->kaart->setColor('salmon');
        $kml = simplexml_load_string($this->kaart->fetch('kml'));
        $actual = strval($kml->Document->Style[0]->IconStyle->color);
        $this->assertEquals($expected, $actual);
    }


    function testsetColorKMLRRGGBBHex()
    {
        $expected = 'ff7280fa';
        $this->kaart->addData(array('B052a'));
        $this->kaart->setColor('#FA8072');
        $kml = simplexml_load_string($this->kaart->fetch('kml'));
        $actual = strval($kml->Document->Style[0]->IconStyle->color);
        $this->assertEquals($expected, $actual);
    }

    function testsetOutlinedColorBitmap()
    {
        $filename = 'OutlinedColorBitmap.png';
        $expected = array('80909b831fd21fca5a75c8ec84344a07', 'd03f881236007c574e308bd2830c1bb3');
        $this->kaart->addData(array('B052a'));
        $this->kaart->setOutlinedColor('#FA8072', 'black');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testsetOutlinedColorSVG()
    {
        $expected = 'fill:#FA8072; stroke:#000000; stroke-width:500;';
        $this->kaart->addData(array('B052a'));
        $this->kaart->setOutlinedColor('#FA8072', 'black');
        $svg = simplexml_load_string($this->kaart->fetch('svg'));
        $actual = strval($svg->g[0]->rect[0]['style']);
        $this->assertEquals($expected, $actual);
    }

    function testsetStyle()
    {
        $expected = 'fill:yellow; stroke:black; stroke-width:500;';
        $this->kaart->addData(array('B052a'));
        $this->kaart->setStyle('fill:yellow; stroke:black; stroke-width:500;');
        $styles = $this->readAttribute($this->kaart->kaartobject, '_styles');
        $actual = $styles[0];
        $this->assertEquals($expected, $actual);
    }

    function testsetStyleKML()
    {
        $expected = 'ff00ffff';
        $this->kaart->addData(array('B052a'));
        $this->kaart->setStyle('fill:yellow; stroke:black; stroke-width:500;');
        $kml = simplexml_load_string($this->kaart->fetch('kml'));
        $actual = strval($kml->Document->Style[0]->IconStyle->color);
        $this->assertEquals($expected, $actual);
    }

    function testsetSize()
    {

        $expected = array(5000, 10000);
        $this->kaart->addData(array('A001q'));
        $this->kaart->addData(array('E109p'));
        $this->kaart->setSymbol('square', 0);
        $this->kaart->setSymbol('square', 1);
        $this->kaart->setSize(5000, 0);
        $this->kaart->setSize(10000, 1);
        $actual = $this->readAttribute($this->kaart->kaartobject, '_sizes');
        $this->assertEquals($expected, $actual);
    }

    function testsetSizeSVGscale()
    {

        $this->kaart->addData(array('A001q'));
        $this->kaart->addData(array('E109p'));
        $this->kaart->setSymbol('square', 0);
        $this->kaart->setSymbol('square', 1);
        $this->kaart->setSize(5000, 0);
        $this->kaart->setSize(10000, 1);
        $expected = array('0.6', '1.2');
        $actual = array();
        $kml = simplexml_load_string($this->kaart->fetch('kml'));
        $actual[] = strval($kml->Document->Style[0]->IconStyle->scale);
        $actual[] = strval($kml->Document->Style[2]->IconStyle->scale);
        $this->assertEquals($expected, $actual);
    }

    function testsetSizeKMLscale()
    {
        $this->kaart->addData(array('A001q'));
        $this->kaart->addData(array('E109p'));
        $this->kaart->setSymbol('square', 0);
        $this->kaart->setSymbol('square', 1);
        $this->kaart->setSize(0.6, 0);
        $this->kaart->setSize(1.2, 1);
        $expected = array('5000', '10000');
        $actual = array();
        $svg = simplexml_load_string($this->kaart->fetch('svg'));
        $actual[] = strval($svg->g[0]->rect[0]['width']);
        $actual[] = strval($svg->g[0]->rect[1]['width']);
        $this->assertEquals($expected, $actual);
    }


    function testsetFullSymbol()
    {
        $expected = array('bar_horizontal', 6000, 'fill:yellow; stroke:black; stroke-width:500;');
        $this->kaart->addData(array('A001q'));
        $this->kaart->setFullSymbol('bar_horizontal', 6000, 'fill:yellow; stroke:black; stroke-width:500;');
        $symbols = $this->readAttribute($this->kaart->kaartobject, '_symbols');
        $actual[0] = $symbols[0];

        $sizes = $this->readAttribute($this->kaart->kaartobject, '_sizes');
        $actual[1] = $sizes[0];
        $styles = $this->readAttribute($this->kaart->kaartobject, '_styles');
        $actual[2] = $styles[0];
        $this->assertEquals($expected, $actual);
    }

    function testTooMuchData()
    {
        // bij gebruik van meer dan het gedefinieerde aantal defaultsymbolen
        $expected = ($this->readAttribute($this->kaart->kaartobject, '_max_default_symbols')) * 3;
        $db_conn = Kaart::createDBConnection(KAART_GEO_DB);
        for ($i = 0; $i < $expected; $i++) {
            $result = mysqli_query($db_conn,
                'SELECT kloeke_code1 FROM geo.kloeke WHERE land in (\'NL\',\'BE\') ORDER BY RAND() LIMIT 1'
            );
            $rij = mysqli_fetch_row($result);
            $kloeke = $rij[0];
            $this->kaart->addData(array($kloeke));
        }
        self::getMethod('createMap')->invokeArgs($this->kaart->kaartobject, array('svg'));
        $actual = count($this->readAttribute($this->kaart->kaartobject, '_symbols'));
        $this->assertEquals($expected, $actual);
        $actual = count($this->readAttribute($this->kaart->kaartobject, '_styles'));
        $this->assertEquals($expected, $actual);
    }

    function testsetTitle()
    {
        $expected = 'dit is een testtitel';
        $this->kaart->setTitle('dit is een testtitel');
        $actual = $this->readAttribute($this->kaart->kaartobject, 'title');
        $this->assertequals($expected, $actual);
        $svg = simplexml_load_string($this->kaart->fetch('svg'));
        $expected = '0 0 350000 400000';
        $actual = strval($svg->attributes()->viewBox);
        $this->assertEquals($expected, $actual);
        $actual = strval($svg->g[0]->attributes()->transform);
        $expected = 'translate(66000,665000) scale(1,-1)';
        $this->assertEquals($expected, $actual);
    }

    function testsetInteractiveSVG()
    {

        $this->kaart->setInteractive();
        $this->kaart->addData(array('F103p'));
        $expected = KAART_ONMOUSEOVER_ECMASCRIPT;
        $svg = simplexml_load_string($this->kaart->fetch());
        $actual = strval($svg->defs->script);
        $this->assertequals($expected, $actual);
        $expected = 'tooltip';
        $actual = trim($svg->g[0]->text);
        $this->assertequals($expected, $actual);
        $expected = 'tooltip';
        $actual = trim($svg->g[0]['id']);
        $this->assertequals($expected, $actual);
        $expected = "ShowTooltip('Hattem (F103p)')";
        $actual = trim($svg->g[1]->g[0]['onmouseover']);
        $this->assertequals($expected, $actual);
    }


    function testsetInteractiveBitmap()
    {
        $expected = '<area shape="rect" coords="310,137,316,143" title="Hattem (F103p)" alt="Hattem (F103p)" />';
        $this->kaart->setInteractive();
        $this->kaart->addData(array('F103p'));
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = trim($this->kaart->getImagemap());
        $this->assertequals($expected, $actual);
    }


    function test_getCombinations()
    {
        $expected = array('0,1' => 1, '0,2' => 1);
        $this->kaart->addData(array('A001q', 'E109p'));
        $this->kaart->addData(array('E109p'));
        $this->kaart->addData(array('A001q', 'A002p'));
        $actual = self::getMethod('_getCombinations')->invokeArgs($this->kaart->kaartobject, array());
        $this->assertEquals($expected, $actual);
    }

    function testsetLinkSVG()
    {
        $expected = 'script.php?kloeke_nr=Q222p';
        $this->kaart->addData(array('Q222p'));
        $this->kaart->setLink('script.php?kloeke_nr=%s', 'dinges');
        // om de een of andere reden kan simplexml attributen met een namespace niet zien.
        // daarom dit maar gedaan (ugly hack, maar goed, fuck it).
        $svg = simplexml_load_string(str_replace('xlink:href', 'href', $this->kaart->fetch()));
        $actual = trim($svg->g[0]->g[0]->a[0]['href']);
        $this->assertEquals($expected, $actual);
        $expected = 'dinges';
        $actual = trim($svg->g[0]->g[0]->a[0]['target']);
        $this->assertEquals($expected, $actual);
    }

    function testsetLinkKML()
    {
        $expected
            = '<a href="http://www.example.com/script.php?kloeke_nr=Q222p" target="dinges">http://www.example.com/script.php?kloeke_nr=Q222p</a>';
        $this->kaart->addData(array('Q222p'));
        $this->kaart->setLink('http://www.example.com/script.php?kloeke_nr=%s', 'dinges');
        $kml = simplexml_load_string($this->kaart->fetch('kml'));
        $actual = trim($kml->Document->Folder[0]->Placemark[0]->description);
        $this->assertEquals($expected, $actual);
    }

    function testsetLinkBitmap()
    {
        $expected = '<area shape="rect" coords="310,137,316,143" href="script.php?kloeke_nr=F103p" target="dinges" />';
        $this->kaart->setLink('script.php?kloeke_nr=%s', 'dinges');
        $this->kaart->addData(array('F103p'));
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = trim($this->kaart->getImagemap());
        $this->assertequals($expected, $actual);
    }


    function testsetLinkJSON()
    {
        $expected = array('58905c262e04943e4be4043e345c8837', '86eb2c617f8d15c2df442f66e48d7657');
        $filename = 'setLinkJSON.json';
        $this->kaart->setLink('script.php?kloeke_nr=%s', 'dinges');
        $this->kaart->addData(array('F103p'));
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('json')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testsetJavaScriptSVGOnclick()
    {
        $this->kaart->addData(array('Q222p'));
        $this->kaart->setJavaScript("alert('%s');");
        $svg = simplexml_load_string($this->kaart->fetch());
        $expected = "alert('Q222p');";
        $actual = trim($svg->g[0]->g[0]['onclick']);
        $this->assertEquals($expected, $actual);
    }

    function testsetJavaScriptJSONOnclick()
    {
        $filename = 'JavaScriptJSONOnclick.json';
        $expected = array('bcd804ce887a71c7fb73e9582d8e4f1d', '400d2c71a265517a52cd7760b9c9819e');
        $this->kaart->addData(array('Q222p'));
        $this->kaart->setJavaScript("alert('%s');");
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('json')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testsetJavaScriptSVGOnmouseover()
    {
        $this->kaart->addData(array('Q222p'));
        $this->kaart->setJavaScript("alert('%s');", 'onmouseover');
        $svg = simplexml_load_string($this->kaart->fetch());
        $expected = "alert('Q222p');";
        $actual = trim($svg->g[0]->g[0]['onmouseover']);
        $this->assertEquals($expected, $actual);
    }

    function testsetJavaScripBitmapOnmouseover()
    {
        $expected = '<area shape="rect" coords="310,137,316,143" onmouseover="alert(\'F103p\');" />';
        $this->kaart->addData(array('F103p'));
        $this->kaart->setJavaScript("alert('%s');", 'onmouseover');
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = trim($this->kaart->getImagemap());
        $this->assertEquals($expected, $actual);
    }

    function testsetParts()
    {
        $filename = 'setParts.svg';
        $expected = 'eb9c1e029091716a0d4d2e2e2224b801';
        $this->kaart->setParts(array('nederland', 'provincies_nederland', 'rivieren_nederland'));
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testmoveDataToBackground()
    {
        $expected = array('0,2' => 1);
        $this->kaart->addData(array('A001q', 'E109p'));
        $this->kaart->addData(array('E109p'));
        $this->kaart->moveDataToBackground();
        $this->kaart->addData(array('A001q', 'A002p'));
        $actual = self::getMethod('_getCombinations')->invokeArgs($this->kaart->kaartobject, array());
        $this->assertEquals($expected, $actual);
    }

    function testsetAltitudeDifference()
    {
        $expected = '5.3107646172604,53.398341823069,1000';
        $this->kaart->addData(array('E109p'));
        $this->kaart->addData(array('A001q', 'E109p'));
        $this->kaart->setAltitudeDifference(1000);
        $kml = simplexml_load_string($this->kaart->fetch('kml'));
        $actual = strval($kml->Document->Folder[1]->Placemark[0]->Point->coordinates);
        $this->assertEquals($expected, $actual);
    }

    function testsetRemoveDuplicates()
    {
        $expected = array(0 => array('A001q', 'E109p'), 1 => array('A001q', 'E109p'));
        $this->kaart->addData(array('A001q', 'E109p', 'A001q'));
        $this->kaart->addData(array('A001q', 'E109p', 'E109p'));
        $this->kaart->setRemoveDuplicates(TRUE);
        $this->kaart->fetch(); // gaat nergens heen, maar het verwijderen van de duplicaten gebeurt vlak voor het produceren
        $actual = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $this->assertEquals($expected, $actual);
    }

    function testsetBackgroundString()
    {
        $expected = array('daan_blok_1969');
        $this->kaart->setBackground('daan_blok_1969');
        $actual = $this->readAttribute($this->kaart->kaartobject, 'backgrounds');
        $this->assertEquals($expected, $actual);
        $this->kaart->setBackground('daan_blok_1969');
        $expected = array('daan_blok_1969', 'daan_blok_1969');
        $actual = $this->readAttribute($this->kaart->kaartobject, 'backgrounds');
        $this->assertEquals($expected, $actual);
        $this->kaart->setBackground('does_not_exist');
        $this->assertEquals($expected, $actual);
    }

    function testsetBackgroundArray()
    {
        $expected = array('daan_blok_1969');
        $this->kaart->setBackground(array('daan_blok_1969'));
        $actual = $this->readAttribute($this->kaart->kaartobject, 'backgrounds');
        $this->assertEquals($expected, $actual);
    }

    function testfetchCompleteMapBitmap()
    {
        $expected = array(
            '68d1e8aea26142e03d4ddba06dd88072',
            '64f79dc694d0a7fcee6d283d4c8b44d0',
            'bf80a316a521408bf52f77780b2f530f',
            'b844f3df1f8d4a1b9aaf880bdd620cca'
        );
        $filename = 'fetchCompleteMapBitmap.png';
        $this->kaart->addData(array('E109p'));
        $this->kaart->setLegend('een verschijnsel');
        $this->kaart->addData(array('A001q', 'E109p'));
        $this->kaart->setCombinations();
        $this->kaart->setLegend('een ander verschijnsel');
        $this->kaart->setTitle('kaart');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testInvalidKloekeCode()
    {
        $filename = 'InvalidKloekeCode.png';
        $expected = array('4c7109f808785e6020114c0bce546ba9', 'fc57088c0f61f0afc0cd88b06bdaf3ed');
        $this->kaart->addData(array('A001q', 'xxxxx', 'E109p'));
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testBitmapNoFill()
    {

        $expected = array('3484cd674d69e34fcb6e66e5b51edd55', '3acea206a3e42479b4d0834c66c1fde3');
        $filename = 'BitmapNoFill.png';
//		vreemd genoeg krijg ik verschillen in de gegenereerde file
//		op laptop en mac mini als ik de width op 800 zet
//		heeft msch. met verschillende PHP-versies te maken?
//		$this->kaart->setPixelWidth(800);
        $this->kaart->addData(array('A001q'));
        $this->kaart->setSymbol('plus');
        $this->kaart->setColor('red');
        $this->kaart->addData(array('A001q'));
        $this->kaart->setSymbol('square');
        $this->kaart->setStyle('fill:none; stroke:black; stroke-width:500;');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testKMLFillNone()
    {

        // 50% transparant wit
        $expected = '7fffffff';
        $this->kaart->addData(array('A001q'));
        $this->kaart->setSymbol('square');
        $this->kaart->setStyle('fill:none; stroke:black; stroke-width:500;');
        $kml = simplexml_load_string($this->kaart->fetch('kml'));
        $actual = strval($kml->Document->Style[0]->IconStyle->color[0]);
        $this->assertEquals($expected, $actual);
    }

    function testgetInvalidKloekeCodes()
    {

        $this->kaart->addData(array('A001q', 'invalid1', 'invalid2'));
        $expected = array('invalid1', 'invalid2');
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = $this->kaart->getInvalidKloekeCodes();
        $this->assertEquals($expected, $actual);
    }

    function testAlternatePathsFile()
    {
        $filename = 'fetchDutchLanguageAreaAlternatePaths_method.png';
        $expected = array('4a82553e1a569a32eb58b2c5a1e353ff', '3ebfb70ac35f5a6266ef8fd81f32e60c');
        $this->kaart->setPathsFile(realpath('./data/alternate_paths.inc.php'));
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
        $svg = simplexml_load_string($this->kaart->fetch('svg'));
        $expected = 'g_0003';
        $actual = trim($svg->g[0]->path[0]['id']);
        $this->assertEquals($expected, $actual);

        unset($this->kaart);
        $expected = array('4a82553e1a569a32eb58b2c5a1e353ff', '3ebfb70ac35f5a6266ef8fd81f32e60c');
        $this->kaart = new Kaart('dutchlanguagearea', realpath('./data/alternate_paths.inc.php'));
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }
}
