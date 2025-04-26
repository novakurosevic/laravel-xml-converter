<?php

namespace Noki\XmlConverter\Tests;

use Noki\XmlConverter\Convert;
use PHPUnit\Framework\TestCase;

class ConvertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        libxml_use_internal_errors(true);
    }

    public function testBasicXmlToJsonConversion()
    {
        $xml = <<<XML
        <note>
            <to>User</to>
            <from>Admin</from>
            <message>Hello</message>
        </note>
        XML;

        $json = Convert::xmlToJson($xml);
        $array = json_decode($json, true);

        $this->assertEquals('User', $array['to']);
        $this->assertEquals('Admin', $array['from']);
        $this->assertEquals('Hello', $array['message']);
    }

    public function testXmlWithAttributesAndNestedElements()
    {
        $xml = <<<XML
        <book id="123" genre="fiction">
            <title>1984</title>
            <author>
                <name>George Orwell</name>
            </author>
        </book>
        XML;

        $array = Convert::xmlToArray($xml);


        $this->assertEquals('123', $array['@attributes']['id']);
        $this->assertEquals('fiction', $array['@attributes']['genre']);
        $this->assertEquals('1984', $array['title']);
        $this->assertEquals('George Orwell', $array['author']['name']);
    }

    public function testInvalidXmlThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        Convert::xmlToArray('<note><to>User</note>');
    }

    public function testCdataIsPreserved()
    {
        $xml = <<<XML
        <message><![CDATA[Some <b>bold</b> text]]></message>
        XML;

        $array = Convert::xmlToArray($xml, false, true);
        $this->assertEquals('Some <b>bold</b> text', $array['value']);
    }

    public function testNamespaceTagging()
    {
        $xml = <<<XML
        <root xmlns:h="http://www.w3.org/TR/html4/">
            <h:title>Header</h:title>
        </root>
        XML;

        $array = Convert::xmlToArray($xml, true);

        $this->assertArrayHasKey('h:title', $array);
        $this->assertEquals('Header', $array['h:title']);
    }

    public function testXsdValidationFailure()
    {
        $this->expectException(\InvalidArgumentException::class);

        $xml = <<<XML
        <person><name>John</name><age>thirty</age></person>
        XML;

        $xsd = <<<XSD
        <?xml version="1.0"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="person">
                <xs:complexType>
                    <xs:sequence>
                        <xs:element name="name" type="xs:string"/>
                        <xs:element name="age" type="xs:int"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:element>
        </xs:schema>
        XSD;

        $tmpFile = tempnam(sys_get_temp_dir(), 'schema') . '.xsd';
        file_put_contents($tmpFile, $xsd);

        Convert::xmlToArray($xml, false, false, $tmpFile);

        unlink($tmpFile); // Clean up
    }

    public function testAutoCastValues()
    {
        $xml = <<<XML
        <root>
            <active>true</active>
            <disabled>false</disabled>
            <count>10</count>
            <float>10.5</float>
            <string>010</string>
            <empty></empty>
        </root>
        XML;

        $array = Convert::xmlToArray($xml);

        $this->assertTrue($array['active']);
        $this->assertFalse($array['disabled']);
        $this->assertSame(10, $array['count']);
        $this->assertSame(10.5, $array['float']);
        $this->assertSame('010', $array['string']);
        $this->assertNull($array['empty']);
    }

    public function testXsdValidationSuccess()
    {
        $xml = <<<XML
        <person xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <name>John</name>
            <age>30</age>
        </person>
        XML;

        $xsd = <<<XSD
        <?xml version="1.0"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="person">
                <xs:complexType>
                    <xs:sequence>
                        <xs:element name="name" type="xs:string"/>
                        <xs:element name="age" type="xs:int"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:element>
        </xs:schema>
        XSD;

        $tmpFile = tempnam(sys_get_temp_dir(), 'schema') . '.xsd';
        file_put_contents($tmpFile, $xsd);

        $array = Convert::xmlToArray($xml, false, false, $tmpFile);

        $this->assertEquals('John', $array['name']);
        $this->assertEquals(30, $array['age']);

        unlink($tmpFile); // Clean up
    }

}
