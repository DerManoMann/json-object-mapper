<?php

declare(strict_types=1);

/*
* This file is part of the ObjectMapper library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ObjectMapper\Tests;

use Radebatz\ObjectMapper\Naming\CamelCase;
use Radebatz\ObjectMapper\Naming\SnakeCase;

/**
 */
class NamingTest extends TestCase
{
    public function snakeNames()
    {
        return [
            ['startMIDDLELast', 'start_middle_last'],
            ['simpleXML', 'simple_xml'],
            ['PDFLoad', 'pdf_load'],
            ['simpleTest', 'simple_test'],
            ['easy', 'easy'],
            ['HTML', 'html'],
            ['AString', 'a_string'],
            ['Some4Numbers234', 'some4_numbers234'],
            ['TEST123String', 'test123_string'],
        ];
    }

    /**
     * @dataProvider snakeNames
     */
    public function testSnakeCase($from, $snake)
    {
        $namingMapper = new SnakeCase();
        $this->assertEquals(($snake === $from ? null : $snake), $namingMapper->resolve($from));
    }

    public function camelNames()
    {
        return [
            ['start_middle_last', 'startMiddleLast', ],
            ['simple_xml', 'simpleXml'],
            ['PDF_load', 'pdfLoad'],
            ['simple_TEST', 'simpleTest'],
            ['easy', 'easy'],
            ['A_String', 'aString'],
            ['Some4_Numbers_234', 'some4Numbers234'],
            ['TEST123_String', 'test123String'],
        ];
    }

    /**
     * @dataProvider camelNames
     */
    public function testCamelCase($from, $camel)
    {
        $namingMapper = new CamelCase();
        $this->assertEquals(($camel === $from ? null : $camel), $namingMapper->resolve($from));
    }
}
