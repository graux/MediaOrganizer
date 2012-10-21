<?php

class UtilsTest extends PHPUnit_Framework_TestCase
{

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        require_once '../Utils.php';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    /**
     * @covers \Utils::getFileExtension
     */
    public function testGetFileExtension()
    {
        $actual = Utils::getFileExtension('/var/tmp/test.new/new file.txt');
        $expected = 'txt';
        $this->assertEquals($expected, $actual);

        $actual2 = Utils::getFileExtension('/var/tmp/test.new/The.Dark.Knight.Rises-(2012)-[1080p].RELEASE.mkv');
        $expected2 = 'mkv';
        $this->assertEquals($expected2, $actual2);
    }

    /**
     * @covers \Utils::fixName
     * @todo
     */
    public function testFixName()
    {
        
    }
}

