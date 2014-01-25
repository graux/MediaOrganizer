<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Fran
 * Date: 6/3/13
 * Time: 7:52 PM
 * To change this template use File | Settings | File Templates.
 */

class OpenSubtitlesMetadataManagerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var OpenSubtitlesMetadataManager null
     */
    private static $subManager = null;

    public static function setUpBeforeClass()
    {
        self::$subManager = OpenSubtitlesMetadataManager::getInstance();
    }

    /**
     * @covers OpenSubtitlesMetadataManager::createFileHash
     */
    public function testCreateFileHash()
    {
        $filePath = dirname(__FILE__) . '/breakdance.avi';
        $hashExpected = '8e245d9679d31e12';
        $hashCalculated = self::$subManager->createFileHash($filePath);

        $this->assertEquals($hashExpected, $hashCalculated);
        return $hashCalculated;
    }

    public function testGetFileSize()
    {
        $filePath = dirname(__FILE__) . '/breakdance.avi';
        $expected = 12909756;
        $actual = self::$subManager->getFileSize($filePath);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers OpenSubtitlesMetadataManager::logIn
     */
    public function testLogIn()
    {
        $token = self::$subManager->logIn();
        $this->assertNotNull($token);
        return $token;
    }

    /**
     * @covers  OpenSubtitlesMetadataManager::fetchMediaItemSubtitle
     * @depends testLogIn
     */
    public function testGetFileSubtitles()
    {
        $filePath = dirname(__FILE__) . '/breakdance.avi';
        $subtitle = self::$subManager->fetchMediaItemSubtitle($filePath, 'en');
        $this->assertNotEmpty($subtitle);
    }
}
