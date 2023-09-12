<?php

namespace Meertens\Kaart;

use Meertens\Kaart\Output\Choropleth;
use PHPUnit\Framework\TestCase;

class KaartChoroplethTest extends TestCase
{

    /** @var $kaart Kaart | Choropleth */
    var $kaart; // contains the object handle of the Kaart class

    function setUp(): void
    {
        $this->kaart = new Kaart('gemeentes');
    }

    function tearDown(): void
    {
        unset($this->kaart);
    }

    private function readAttribute($object, $property)
    {
        return $object->__get($property);
    }

    public static function getMethod($name)
    {
        $class = new \ReflectionClass('Meertens\Kaart\Output\Choropleth');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
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

    function testfetchSVG()
    {
        $filename = 'fetchMunicipalitiesSVG.svg';
        $expected = '671fbcd55b90505bc762911c6f9d0882';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testfetchPNG()
    {
        $filename = 'fetchMunicipalitiesPNG.png';
        $expected = array(
            'ee1b6668ffa8a8dd7a96d5e3f23d2c48',
            '3e7cf3664edccb874e4c96c3c49c2274'
        );
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testfetchGIF()
    {
        $filename = 'fetchMunicipalitiesGIF.gif';
        $expected = array(
            '803ab0df5f3fcd8c55206b11a5a7d562',
            '75757bd470bc41bab3c4e79f8e9278ce'
        );
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('gif')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testfetchJPEG()
    {
        $filename = 'fetchMunicipalitiesJPEG.jpg';
        $expected = array(
            '391e36f916c8aa43ba17c76220a61e42',
            '2e236e27211d8fa40e53cf274dc1bb50',
            '4f0e722b905386681fe22ed670bb6ccb',
            'd9ae5f9b3ee7223dbade308a207c8806'
        );
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('jpeg')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testfetchKML()
    {
        $filename = 'fetchMunicipalitiesKML.kml';
        $expected = 'e910e8cab2e8e78688424f2aabb288fc';
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testfetchJSON()
    {
        $filename = 'fetchMunicipalitiesJSON.json';
        $expected = array(
            'c3eed5b8be752caaa0ca3cc5ddf454de',
            'b5163b08392d0a9ba282f0a5cb4d0048'
        );
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('json')));
        $this->assertContains($actual, $expected, "check file $filename");
    }


    function testHighlightColorsKML()
    {
        $filename1 = 'fetchMunicipalitiesKMLHighlightColorsHTML.kml';
        $filename2 = 'fetchMunicipalitiesKMLHighlightColorsKML.kml';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $actual1 = md5($this->_saveFile($filename1, $this->kaart->fetch('kml')));
        $gemeentes = array('g_0534' => 'FF13C5FF');
        $this->kaart->addData($gemeentes);
        $actual2 = md5($this->_saveFile($filename2, $this->kaart->fetch('kml')));
        $this->assertEquals($actual1, $actual2, "check files $filename1 en $filename2");
    }

    function testHighlightColorsSVG()
    {
        $filename1 = 'fetchSVGHighlightColorsHTML.svg';
        $filename2 = 'fetchSVGHighlightColorsKML.svg';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $actual1 = md5($this->_saveFile($filename1, $this->kaart->fetch('svg')));
        $gemeentes = array('g_0534' => 'FF13C5FF');
        $this->kaart->addData($gemeentes);
        $actual2 = md5($this->_saveFile($filename2, $this->kaart->fetch('svg')));
        $this->assertEquals($actual1, $actual2, "check files $filename1 en $filename2");
    }

    function testHighlightColorsBitmap()
    {

        $filename1 = 'fetchPNGHighlightColorsHTML.png';
        $filename2 = 'fetchPNGHighlightColorsKML.png';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $actual1 = md5($this->_saveFile($filename1, $this->kaart->fetch('png')));
        $gemeentes = array('g_0534' => 'FF13C5FF');
        $this->kaart->addData($gemeentes);
        $actual2 = md5($this->_saveFile($filename2, $this->kaart->fetch('png')));
        $this->assertEquals($actual1, $actual2, "check files $filename1 en $filename2");
    }

    function testHighlightJSON()
    {

        $filename = 'fetchHighlightJSONMunicipalities.json';
        $expected = array(
            '918373346794d0b37b5fcf0bdde1ddad',
            'd3c9206201e12eb6b4628aeab27fdb96'
        );
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('json')));
        $this->assertContains($actual, $expected, "check file $filename");
    }


    function testsaveAsFileSVG()
    {
        list($new_file_exists, $message) = $this->_fileExists('kaartMunicipalities.svg', 'svg');
        $this->assertTrue($new_file_exists, $message);
    }

    function testsaveAsFilePNG()
    {
        list($new_file_exists, $message) = $this->_fileExists('kaartMunicipalities.png', 'png');
        $this->assertTrue($new_file_exists, $message);
    }

    function testsaveAsFileGIF()
    {
        list($new_file_exists, $message) = $this->_fileExists('kaartMunicipalities.gif', 'gif');
        $this->assertTrue($new_file_exists, $message);
    }

    function testsaveAsFileJPEG()
    {
        list($new_file_exists, $message) = $this->_fileExists('kaartMunicipalities.jpeg', 'jpeg');
        $this->assertTrue($new_file_exists, $message);
    }

    function testaddData()
    {
        $expected_map_array = array('g_0534' => '#FFC513', 'g_1740' => '#E30000');
        $this->kaart->addData(array('g_0534' => '#FFC513', 'g_1740' => '#E30000'));
        $actual_map_array = $this->readAttribute($this->kaart->kaartobject, 'map_array');
        $this->assertEquals($expected_map_array, $actual_map_array);
    }

    function testsetPixelWidth()
    {
        $width = rand(1, 1000);
        $expected_width = $width;
        $expected_height = round($width * 1.1);
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
        $expected = strval(640);
        $actual = $this->kaart->getPixelWidth();
        $this->assertEquals($expected, $actual);
    }

    function testgetPixelHeight()
    {
        $expected = 640 * 1.1;
        $expected = strval($expected);
        $actual = strval($this->kaart->getPixelHeight());
        $this->assertEquals($expected, $actual);
    }

    function testaddTooltipsInteractiveBitmap()
    {
        $filenaam = 'imagemap_tooltips_interactive.html';
        $expected = '0853281c518a3fe81067d148b9fda5c8';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->setInteractive();
        $this->kaart->addData($gemeentes);
        $this->kaart->addTooltips(array('g_0534' => 'Juinen'));
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = md5($this->_saveFile($filenaam, $this->kaart->getImagemap()));
        $this->assertEquals($expected, $actual, "check file $filenaam");
    }

    function testaddTooltipsNonInteractiveBitmap()
    {
        $filenaam = 'imagemap_tooltips_non_interactive.html';
        $expected = '08ef7858115efc9d0794d3fd4183e360';
        $gemeentes = array('g_0534' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $this->kaart->addTooltips(array('g_0534' => 'Juinen'));
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = md5($this->_saveFile($filenaam, $this->kaart->getImagemap()));
        $this->assertEquals($expected, $actual, "check file $filenaam");
    }

    function testaddTooltipsNonInteractiveSVG()
    {
        $filenaam = 'TooltipsNonInteractive.svg';
        $expected = '45873d3c09699c313aa50e84883d4ed9';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $this->kaart->addTooltips(array('g_0363' => 'Juinen'));
        $actual = md5($this->_saveFile($filenaam, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filenaam");
    }

    function testaddTooltipsInteractiveSVG()
    {
        $filenaam = 'TooltipsInteractive.svg';
        $expected = '00e24437e7293c4c86d7c140bfeda848';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->setInteractive();
        $this->kaart->addData($gemeentes);
        $this->kaart->addTooltips(array('g_0363' => 'Juinen'));
        $actual = md5($this->_saveFile($filenaam, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filenaam");
    }

    function testaddTooltipsNonInteractiveKML()
    {
        $filenaam = 'TooltipsNonInteractive.kml';
        $expected = 'dfeced06e4823b62e2a0aa222d264658';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $this->kaart->addTooltips(array('g_0363' => 'Juinen'));
        $actual = md5($this->_saveFile($filenaam, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filenaam");
    }


    function testaddTooltipsJSON()
    {
        $filename = 'TooltipsJSON.json';
        $expected = array(
            'f64d376d77d81fc2b3b41a8f2397ae07',
            '43b74442d9889549edda21b46db399e9'
        );
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $this->kaart->addTooltips(array('g_0363' => 'Juinen'));
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('json')));
        $this->assertContains($actual, $expected, "check file $filename");
    }


    function testsetTitleSVG()
    {

        $expected = 'dit is een testtitel';
        $this->kaart->setTitle('dit is een testtitel');

        $actual = $this->kaart->getTitle();
        $this->assertequals($expected, $actual);
        self::getMethod('createMap')->invokeArgs($this->kaart->kaartobject, array());
        $svg = simplexml_load_string($this->kaart->fetch('svg'));
        $expected = '0 0 288051 400430';
        $actual = strval($svg->attributes()->viewBox);
        $this->assertEquals($expected, $actual);

        $expected = 288051;
        $actual = intval($svg->rect[0]->attributes()->width);
        $this->assertEquals($expected, $actual);

        $expected = 360430;
        $actual = intval($svg->rect[0]->attributes()->height);
        $this->assertEquals($expected, $actual);
    }


    function testsetTitlePNG()
    {
        $filename = 'ChoroplethsetTitle.png';
        $this->kaart->setTitle('dit is een testtitel');
        $expected = array(
            '9fa348a211822f22f34b6c07e3c4ed80',
            '7b35e8dc39539ed760f5f66bb0f12040',
            'a6139251e63c1a425e1b38f042f4356d'
        );
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }


    function testsetInteractiveSVG()
    {

        $this->kaart->setInteractive();
        $expected = KAART_ONMOUSEOVER_ECMASCRIPT;
        $svg = simplexml_load_string($this->kaart->fetch('svg'));

        $actual = strval($svg->defs->script);
        $this->assertequals($expected, $actual);
        $expected = 'tooltip';
        $actual = trim($svg->g[0]->text);
        $this->assertequals($expected, $actual);
        $expected = 'tooltip';
        $actual = trim($svg->g[0]['id']);
        $this->assertequals($expected, $actual);
        $expected = "ShowTooltip('Appingedam')";
        $actual = trim($svg->g[1]->path[0]['onmouseover']);
        $this->assertequals($expected, $actual);
    }


    function testImagemap()
    {
        $filenaam = 'imagemap.html';
        $expected = 'b54a15fa2e2fd3c32abcadedcaa637a8';
        $this->kaart->setInteractive();
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = md5($this->_saveFile($filenaam, $this->kaart->getImagemap()));
        $this->assertEquals($expected, $actual, "check file $filenaam");
    }

    function testsetLinkSVG()
    {
        $gemeentes = array('g_0003' => '#FFC513');
        $links = array('g_0003' => 'http://www.meertens.knaw.nl/');
        $this->kaart->addData($gemeentes);
        $this->kaart->addLinks($links, '_blank');
        $expected = 'http://www.meertens.knaw.nl/';
        // om de een of andere reden kan simplexml attributen met een namespace niet zien.
        // daarom dit maar gedaan (ugly hack, maar goed, fuck it).
        $svg = simplexml_load_string(str_replace('xlink:href', 'href', $this->kaart->fetch()));
        $actual = trim($svg->g[0]->g[21]->a[0]['href']);
        $this->assertEquals($expected, $actual);
        $expected = 'g_0003';
        $actual = trim($svg->g[0]->g[21]->a[0]->path[0]->attributes()->id);
        $this->assertEquals($expected, $actual);
        $expected = '_blank';
        $actual = trim($svg->g[0]->g[21]->a[0]['target']);
        $this->assertEquals($expected, $actual);
    }

    function testsetLinksBitmap()
    {
        $links = array('g_0003' => 'http://www.meertens.knaw.nl/');
        $this->kaart->addLinks($links, '_blank');
        $expected
            = '<area shape="poly" coords="572,57,572,58,572,59,572,61,572,61,572,63,572,63,572,65,572,66,573,66,573,66,573,69,572,69,569,70,569,69,567,69,567,71,565,71,565,71,564,71,564,69,562,70,562,69,562,69,561,67,561,67,560,65,560,65,561,65,561,65,561,63,561,62,561,62,562,62,563,62,564,62,565,62,565,62,565,62,565,61,565,61,565,59,565,58,565,58,565,57,566,57,567,57,569,58,569,58,570,58,571,57,572,57" href="http://www.meertens.knaw.nl/" target="_blank" id="g_0003" />';
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $imagemap = trim($this->kaart->getImagemap());
        $imagemap_array = explode("\n", $imagemap);
        $actual = trim($imagemap_array[0]);
        $this->assertequals($expected, $actual);
    }


    function testsetLinksKML()
    {
        $filenaam = 'municipalitiesAddLinksKML.kml';
        $expected = '6db65ca88d74a3aa5bced21ba414ecaf';
        $links = array('g_0003' => 'http://www.meertens.knaw.nl/');
        $this->kaart->addLinks($links, '_blank');
        $actual = md5($this->_saveFile($filenaam, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filenaam");
    }


    function testsetLinksJSON()
    {
        $filename = 'municipalitiesAddLinksJSON.json';
        $expected = array(
            '7b31020f99106210a9b6dbfccaf3f858',
            '1324475e47a7a405b83e261cbc49a72e'
        );
        $links = array('g_0003' => 'http://www.meertens.knaw.nl/');
        $this->kaart->addLinks($links, '_blank');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('json')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testsetJavaScriptSVGOnclick()
    {
        $filenaam = 'setJavaScriptSVGOnclick.svg';
        $this->kaart->addData(array('g_0003' => '#FFC513'));
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'));
        $expected = "8c5673e09c0ad38ff039504003e0c7bf";
        $actual = md5($this->_saveFile($filenaam, $this->kaart->fetch()));
        $this->assertEquals($expected, $actual, "check file $filenaam");
    }

    function testsetJavaScriptSVGOnmouseover()
    {
        $filenaam = 'setJavaScriptSVGOnmouseover.svg';
        $this->kaart->addData(array('g_0003' => '#FFC513'));
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'), 'onmouseover');
        $expected = "7b5a5171ae647e4c4fc484ab42d5eabf";
        $actual = md5($this->_saveFile($filenaam, $this->kaart->fetch()));
        $this->assertEquals($expected, $actual, "check file $filenaam");
    }


    function testsetJavaScriptJSONOnclick()
    {
        $filename = 'JavaScriptJSONOnclickMunicipalities.json';
        $expected = array(
            '49edc5136cc8e51fd2d42542dc070ac2',
            'fc03d1eb4bad5c9a7a79f46034c24956'
        );
        $this->kaart->addData(array('g_0003' => '#FFC513'));
        $this->kaart->setJavaScript(array('g_0003' => "alert('g_0003');"));
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('json')));
        $this->assertContains($actual, $expected, "check file $filename");
    }


    function testsetJavaScripBitmapOnclick()
    {
        $expected
            = '<area shape="poly" coords="572,57,572,58,572,59,572,61,572,61,572,63,572,63,572,65,572,66,573,66,573,66,573,69,572,69,569,70,569,69,567,69,567,71,565,71,565,71,564,71,564,69,562,70,562,69,562,69,561,67,561,67,560,65,560,65,561,65,561,65,561,63,561,62,561,62,562,62,563,62,564,62,565,62,565,62,565,62,565,61,565,61,565,59,565,58,565,58,565,57,566,57,567,57,569,58,569,58,570,58,571,57,572,57" onclick="alert(\'g_0003\');" id="g_0003" />';
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'));
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $imagemap_array = explode("\n", trim($this->kaart->getImagemap()));
        $actual = $imagemap_array[0];
        $this->assertEquals($expected, $actual);
    }

    function testsetJavaScripBitmapOnmouseover()
    {
        $expected
            = '<area shape="poly" coords="572,57,572,58,572,59,572,61,572,61,572,63,572,63,572,65,572,66,573,66,573,66,573,69,572,69,569,70,569,69,567,69,567,71,565,71,565,71,564,71,564,69,562,70,562,69,562,69,561,67,561,67,560,65,560,65,561,65,561,65,561,63,561,62,561,62,562,62,563,62,564,62,565,62,565,62,565,62,565,61,565,61,565,59,565,58,565,58,565,57,566,57,567,57,569,58,569,58,570,58,571,57,572,57" onmouseover="alert(\'g_0003\');" id="g_0003" />';
        $this->kaart->setJavaScript(array('g_0003' => 'alert(\'g_0003\');'), 'onmouseover');
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $imagemap_array = explode("\n", trim($this->kaart->getImagemap()));
        $actual = $imagemap_array[0];
        $this->assertEquals($expected, $actual);
    }

    function testAlternatePathsFile()
    {
        $filename = 'fetchMunicipalitiesAlternatePaths_method.png';
        $expected = array(
            '6abdac35e19e2822e2a778a0e83c7e76',
            '278a2ed363d38a696f3651914a7860f5'
        );
        $this->kaart->setPathsFile(realpath('./data/alternate_paths.inc.php'));
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");

        unset($this->kaart);
        $filename = 'fetchMunicipalitiesAlternatePaths_constructor.png';
        $this->kaart = new Kaart('gemeentes', realpath('./data/alternate_paths.inc.php'));
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testSetGeneralLinkSVG()
    {
        $filename = 'municipalitiesSetLink.svg';
        $expected = '523824dab5871980c101d8d6752467bb';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $this->kaart->setLink('http://www.example.com/?code=%s');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testSetGeneralLinkBitmap()
    {
        $filenaam = 'municipalitiesImagemapSetLink.html';
        $expected = '8f9f0e079af7c1daae242fbd1716df63';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $this->kaart->setLink('http://www.example.com/?code=%s');
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = md5($this->_saveFile($filenaam, $this->kaart->getImagemap()));
        $this->assertEquals($expected, $actual, "check file $filenaam");
    }

    function testSetGeneralLinkKML()
    {
        $filename = 'municipalitiesKMLWithLink.kml';
        $expected = '06f5cc53b9ac870fe7d08c7833796983';
        $this->kaart->setLink('http://www.example.com/?gemeente=%s');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testSetGeneralLinkJSON()
    {
        $filename = 'municipalitiesJSONWithLink.json';
        $expected = array(
            '1fa7bafdeb026d29e2d9b8b432c63974',
            '632b392e88a343dc5f1080027b1fa743'
        );
        $this->kaart->setLink('http://www.example.com/?gemeente=%s');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('json')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testSetGeneralLinkSVGHighlightedOnly()
    {
        $filename = 'municipalitiesSetLinkHighlightedOnly.svg';
        $expected = 'a904c758e793a32464bb887f76145a69';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $this->kaart->setLinkHighlighted('http://www.example.com/?code=%s');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testSetGeneralLinkBitmapHighlightedOnly()
    {
        $filename = 'municipalitiesImagemapSetLinkHighlightedOnly.html';
        $expected = 'dcf566bd1f2bb3dfc614a0fa88a1fe3b';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $this->kaart->setLinkHighlighted('http://www.example.com/?code=%s');
        $this->kaart->fetch('png'); // gaat nergens heen, maar zonder deze wordt er geen kaart gemaakt
        $actual = md5($this->_saveFile($filename, $this->kaart->getImagemap()));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testSetGeneralLinkKMLHighlightedOnly()
    {
        $filename = 'municipalitiesWithLinkHighlightedOnly.kml';
        $expected = '229a95d86928ade93f186cbc66c5d71e';
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $this->kaart->setLinkHighlighted('http://www.example.com/?code=%s');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testSetGeneralLinkJSONHighlightedOnly()
    {
        $filename = 'municipalitiesWithLinkHighlightedOnly.json';
        $expected = array(
            '54078b0fcf3b2fec5e2fdf9bba5c7f52',
            '1df4ed9fed166a961f020f4519844c43'
        );
        $gemeentes = array('g_0363' => '#FFC513');
        $this->kaart->addData($gemeentes);
        $this->kaart->setLinkHighlighted('http://www.example.com/?code=%s');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('json')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testCorop()
    {
        $filename = 'coroptest.png';
        $expected = array(
            '453149a4e77f1e7bd36885c263d1dcdd',
            '59ee17dafa49f5d2e91ab6b4db9c5ccf'
        );
        $data = array('corop_22' => '#FFC513');
        unset($this->kaart);
        $this->kaart = new Kaart('corop');
        $this->kaart->addData($data);
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testDialectAreas()
    {
        $filename = 'dialectareas.png';
        $expected = array(
            'd7849c3f9fbaedf6428c84a161749f05',
            'dd9fb314fdd652e7a1a748d80581b2ba'
        );
        $data = array('dial_07' => '#FFC513');
        unset($this->kaart);
        $this->kaart = new Kaart('dialectareas');
        $this->kaart->addData($data);
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testfetchKMLCorop()
    {
        $filename = 'fetchKMLCorop.kml';
        $expected = 'a6a34cd45b99ae6c3e28ea8593d70ded';
        unset($this->kaart);
        $this->kaart = new Kaart('corop');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testfetchKMLProvincies()
    {
        $filename = 'fetchKMLProvincies.kml';
        $expected = 'b369986c1ba41ae4aa1bd64cb1e3ee0e';
        unset($this->kaart);
        $this->kaart = new Kaart('provincies');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testsetAdditionalPathsFiles()
    {
        $filename = 'municipalities_nl_vl.png';
        $expected = array(
            '2abc71152c1f44ff7eb30d7156866633',
            '284a54f562eff6c6928043c784ae4459'
        );
        $this->kaart->setAdditionalPathsFiles(array('municipalities_flanders.inc.php', 'border_nl_be.inc.php'));
        $this->kaart->setIniFile('municipalities_netherlands_flanders.ini');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");

        unset($this->kaart);
        $this->kaart = new Kaart('gemeentes');
        $this->kaart->setAdditionalPathsFiles(array('municipalities_flanders.inc.php', 'border_nl_be.inc.php'));
        $this->kaart->setIniFile('municipalities_netherlands_flanders.ini');
        $expected = 800;
        $actual = $this->kaart->getPixelWidth();
        $this->assertEquals($expected, $actual);

        unset($this->kaart);
        $this->kaart = new Kaart('gemeentes');
        $this->kaart->setAdditionalPathsFiles(array('municipalities_flanders.inc.php', 'border_nl_be.inc.php'));
        $this->kaart->setPixelWidth(400);
        $this->kaart->setIniFile('municipalities_netherlands_flanders.ini');
        $expected = 400;
        $actual = $this->kaart->getPixelWidth();
        $this->assertEquals($expected, $actual);

        unset($this->kaart);
        $this->kaart = new Kaart('gemeentes');
        $filename = 'gemeentes_plus_dialectareas.png';
        $expected = array(
            '9413c79a69118cc929a8cf88503e577b',
            '8588df8c894deab13512a156c2554005'
        );
        $this->kaart->setAdditionalPathsFiles(array('dialectareas.inc.php'));
        $this->kaart->setIniFile('municipalities_extra.ini');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testMunicipalitiesDutchlanguagearea()
    {
        $filename = 'municipalities_dutchlanguagearea.png';
        $expected = array(
            '2abc71152c1f44ff7eb30d7156866633',
            '284a54f562eff6c6928043c784ae4459'
        );
        unset($this->kaart);
        $this->kaart = new Kaart('municipalities_nl_flanders');
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }


    function testgetPossibleAreas()
    {
        unset($this->kaart);
        $this->kaart = new Kaart('provincies');
        $actual = $this->kaart->getPossibleAreas();
        $expected = array(
            'p_20' => 'Groningen',
            'p_21' => 'Friesland',
            'p_22' => 'Drenthe',
            'p_23' => 'Overijssel',
            'p_24' => 'Flevoland',
            'p_25' => 'Gelderland',
            'p_26' => 'Utrecht',
            'p_27' => 'Noord-Holland',
            'p_28' => 'Zuid-Holland',
            'p_29' => 'Zeeland',
            'p_30' => 'Noord-Brabant',
            'p_31' => 'Limburg'
        );
        $this->assertEquals($expected, $actual);

        unset($this->kaart);
        $this->kaart = new Kaart('municipalities_nl_flanders');
        $expected = 'd60db7de5ae7d288cb92f52eb3275e46';
        $actual = md5(join(',', $this->kaart->getPossibleAreas()));
        $this->assertEquals($expected, $actual);
    }

    function testaddDataMunicipalitiesNLFlanders()
    {
        unset($this->kaart);
        $expected = array(
            'fb13d126168b5ba134d39493cbbef964',
            'c37721f4815949bf9842e4eb91a32158'
        );
        $this->kaart = new Kaart('municipalities_nl_flanders');
        $filename = 'addDataMunicipalitiesNLFlanders.svg';
        $gemeentes = array(
            'g_0432' => '#FFE680',
            'g_0420' => '#FFDD55',
            'g_0448' => '#FFD42A',
            'g_0476' => '#FFCC00',
            'g_0373' => '#D4AA00',
            'g_0400' => '#AA8800',
            'g_0366' => '#806600',
            'g_0463' => '#FFCC00',
            'g_0462' => '#FFEEAA',
            'g_12029' => '#FFE680',
            'g_13036' => '#FFDD55',
            'g_23103' => '#FFD42A',
            'g_23094' => '#FFCC00',
            'g_24107' => '#D4AA00',
            'g_24109' => '#AA8800',
            'g_73042' => '#806600'
        );
        $this->kaart->addData($gemeentes);
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testCustomHighlightOutlinePNG()
    {
        $gemeentes = array('g_0171' => array('fill' => '#FFC513', 'outline' => 'black', 'strokewidth' => '2'));
        $this->kaart->addData($gemeentes);

        $filename = 'CustomHighlightOutlineGemeente.png';
        $expected = array(
            '9b1fee969cdbf0a91e49e7751897917f',
            '7ab777ff177b425cb564af6e8bdbaac3'
        );
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");

    }

    function testCustomHighlightOutlineSVG()
    {
        $gemeentes = array('g_0171' => array('fill' => '#FFC513', 'outline' => 'black', 'strokewidth' => '2'));
        $this->kaart->addData($gemeentes);

        $filename = 'CustomHighlightOutlineGemeente.svg';
        $expected = 'e9a8a33953aaec522aa7b619110b679d';
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testCustomHighlightOutlineKML()
    {
        $gemeentes = array('g_0171' => array('fill' => '#FFC513', 'outline' => 'black', 'strokewidth' => '2'));
        $this->kaart->addData($gemeentes);

        $filename = 'CustomHighlightOutlineGemeente.kml';
        $expected = 'd4d871bf8fdeae12c0c4c441df9a4e70';
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }

    function testCustomOutlineProvinciePNG()
    {
        $extra = array('p_20' => array('fill' => 'none', 'outline' => 'red', 'strokewidth' => '2'));
        $this->kaart->addData($extra);
        $this->kaart->setAdditionalPathsFiles(array('provinces.inc.php'));
        $this->kaart->setIniFile('municipalities_extra.ini');

        $filename = 'CustomOutlineProvincie.png';
        $expected = array(
            '91d9d1276b2860fd03cfda9c36c57c55',
            '6d20d003b916020149e7ec13899b7943'
        );
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('png')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testCustomOutlineProvincieSVG()
    {
        $extra = array('p_20' => array('fill' => 'none', 'outline' => 'red', 'strokewidth' => '2'));
        $this->kaart->addData($extra);
        $this->kaart->setAdditionalPathsFiles(array('provinces.inc.php'));
        $this->kaart->setIniFile('municipalities_extra.ini');

        $filename = 'CustomOutlineProvincie.svg';
        $expected = array(
            'ade8cee42f1cd16903d1dc0b76f0a52c',
            '1586297f6d7c958eb0674427f9ac89a2'
        );
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('svg')));
        $this->assertContains($actual, $expected, "check file $filename");
    }

    function testCustomOutlineProvincieKML()
    {
        $extra = array('p_20' => array('fill' => 'none', 'outline' => 'red', 'strokewidth' => '2'));
        $this->kaart->addData($extra);
        $this->kaart->setAdditionalPathsFiles(array('provinces.inc.php'));
        $this->kaart->setIniFile('municipalities_extra.ini');

        $filename = 'CustomOutlineProvincie.kml';
        $expected = '1201f65455ef8074adad7c05a69f6a0a';
        $actual = md5($this->_saveFile($filename, $this->kaart->fetch('kml')));
        $this->assertEquals($expected, $actual, "check file $filename");
    }


}
