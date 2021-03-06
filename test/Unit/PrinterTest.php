<?php

declare(strict_types=1);

/**
 * Copyright (c) 2018 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/json-printer
 */

namespace Localheinz\Json\Printer\Test\Unit;

use Localheinz\Json\Printer\Printer;
use Localheinz\Json\Printer\PrinterInterface;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

final class PrinterTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsPrinterInterface()
    {
        $this->assertClassImplementsInterface(PrinterInterface::class, Printer::class);
    }

    public function testPrintRejectsInvalidJson()
    {
        $json = $this->faker()->realText();

        $printer = new Printer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            '"%s" is not valid JSON.',
            $json
        ));

        $printer->print($json);
    }

    /**
     * @dataProvider providerInvalidIndent
     *
     * @param string $indent
     */
    public function testPrintRejectsInvalidIndent(string $indent)
    {
        $json = <<<'JSON'
["Andreas M\u00f6ller","🤓","https:\/\/localheinz.com"]
JSON;

        $printer = new Printer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            '"%s" is not a valid indent.',
            $indent
        ));

        $printer->print(
            $json,
            $indent
        );
    }

    public function providerInvalidIndent(): \Generator
    {
        $values = [
            'not-whitespace' => $this->faker()->sentence,
            'contains-line-feed' => " \n ",
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    public function testPrintPrintsPretty()
    {
        $json = <<<'JSON'
{"name":"Andreas M\u00f6ller","emoji":"🤓","urls":["https:\/\/localheinz.com","https:\/\/github.com\/localheinz","https:\/\/twitter.com\/localheinz"]}
JSON;

        $expected = <<<'JSON'
{
    "name": "Andreas M\u00f6ller",
    "emoji": "🤓",
    "urls": [
        "https:\/\/localheinz.com",
        "https:\/\/github.com\/localheinz",
        "https:\/\/twitter.com\/localheinz"
    ]
}
JSON;

        $printer = new Printer();

        $printed = $printer->print($json);

        $this->assertSame($expected, $printed);
    }

    public function testPrintPrintsPrettyWithIndent()
    {
        $json = <<<'JSON'
{"name":"Andreas M\u00f6ller","emoji":"🤓","urls":["https:\/\/localheinz.com","https:\/\/github.com\/localheinz","https:\/\/twitter.com\/localheinz"]}
JSON;
        $indent = '  ';

        $expected = <<<'JSON'
{
  "name": "Andreas M\u00f6ller",
  "emoji": "🤓",
  "urls": [
    "https:\/\/localheinz.com",
    "https:\/\/github.com\/localheinz",
    "https:\/\/twitter.com\/localheinz"
  ]
}
JSON;

        $printer = new Printer();

        $printed = $printer->print(
            $json,
            $indent
        );

        $this->assertSame($expected, $printed);
    }

    public function testPrintPrintsPrettyButDoesNotUnEscapeUnicodeCharactersAndSlashes()
    {
        $json = <<<'JSON'
{"name":"Andreas M\u00f6ller","emoji":"🤓","urls":["https:\/\/localheinz.com","https:\/\/github.com\/localheinz","https:\/\/twitter.com\/localheinz"]}
JSON;

        $expected = <<<'JSON'
{
    "name": "Andreas M\u00f6ller",
    "emoji": "🤓",
    "urls": [
        "https:\/\/localheinz.com",
        "https:\/\/github.com\/localheinz",
        "https:\/\/twitter.com\/localheinz"
    ]
}
JSON;

        $printer = new Printer();

        $printed = $printer->print($json);

        $this->assertSame($expected, $printed);
    }

    public function testPrintPrintsPrettyButDoesNotEscapeUnicodeCharactersAndSlashes()
    {
        $json = <<<'JSON'
{"name":"Andreas Möller","emoji":"🤓","urls":["https://localheinz.com","https://github.com/localheinz","https://twitter.com/localheinz"]}
JSON;

        $expected = <<<'JSON'
{
    "name": "Andreas Möller",
    "emoji": "🤓",
    "urls": [
        "https://localheinz.com",
        "https://github.com/localheinz",
        "https://twitter.com/localheinz"
    ]
}
JSON;

        $printer = new Printer();

        $printed = $printer->print($json);

        $this->assertSame($expected, $printed);
    }

    public function testPrintPrintsPrettyIdempotently()
    {
        $json = <<<'JSON'
{
    "name": "Andreas M\u00f6ller",
    "emoji": "🤓",
    "urls": [
        "https:\/\/localheinz.com",
        "https:\/\/github.com\/localheinz",
        "https:\/\/twitter.com\/localheinz"
    ]
}
JSON;

        $printer = new Printer();

        $printed = $printer->print($json);

        $this->assertSame($json, $printed);
    }

    public function testPrintCollapsesEmptyArray()
    {
        $json = <<<'JSON'
[



        ]
JSON;

        $expected = <<<'JSON'
[]
JSON;

        $printer = new Printer();

        $printed = $printer->print($json);

        $this->assertSame($expected, $printed);
    }

    public function testPrintCollapsesEmptyObject()
    {
        $json = <<<'JSON'
{



        }
JSON;

        $expected = <<<'JSON'
{}
JSON;

        $printer = new Printer();

        $printed = $printer->print($json);

        $this->assertSame($expected, $printed);
    }

    public function testPrintCollapsesEmptyComplex()
    {
        $json = <<<'JSON'
{
            "foo":          {
    
    
}   ,
    "bar": [                                ]
        }
JSON;

        $expected = <<<'JSON'
{
    "foo": {},
    "bar": []
}
JSON;

        $printer = new Printer();

        $printed = $printer->print($json);

        $this->assertSame($expected, $printed);
    }

    /**
     * @see https://github.com/zendframework/zend-json/pull/37
     */
    public function testPrintDoesNotRemoveSpaceAroundCommaInStringValue()
    {
        $json = <<<'JSON'
{"after":"Level is greater than 9000, maybe even 9001!","around":"Really , nobody does that.","in-array":["Level is greater than 9000, maybe even 9001!","Really , nobody does that."]}
JSON;

        $expected = <<<'JSON'
{
    "after": "Level is greater than 9000, maybe even 9001!",
    "around": "Really , nobody does that.",
    "in-array": [
        "Level is greater than 9000, maybe even 9001!",
        "Really , nobody does that."
    ]
}
JSON;

        $printer = new Printer();

        $printed = $printer->print($json);

        $this->assertSame($expected, $printed);
    }

    /**
     * @see https://github.com/zendframework/zend-json/blob/release-3.0.0/test/JsonTest.php#L964-L975
     */
    public function testPrintDoesNotConsiderDoubleQuoteFollowingEscapedBackslashAsEscapedInArray()
    {
        $json = \json_encode([1, '\\', 3]);

        $expected = <<<'JSON'
[
    1,
    "\\",
    3
]
JSON;

        $printer = new Printer();

        $printed = $printer->print($json);

        $this->assertSame($expected, $printed);
    }

    /**
     * @see https://github.com/zendframework/zend-json/blob/release-3.0.0/test/JsonTest.php#L964-L975
     */
    public function testPrintDoesNotConsiderDoubleQuoteFollowingEscapedBackslashAsEscapedInObject()
    {
        $json = \json_encode(['a' => '\\']);

        $expected = <<<'JSON'
{
    "a": "\\"
}
JSON;

        $printer = new Printer();

        $printed = $printer->print($json);

        $this->assertSame($expected, $printed);
    }
}
