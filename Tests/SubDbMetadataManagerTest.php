<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Fran
 * Date: 6/3/13
 * Time: 7:52 PM
 * To change this template use File | Settings | File Templates.
 */

class SubDbMetadataManagerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var SubDbMetadataManager null
     */
    private static $subManager = null;

    public static function setUpBeforeClass()
    {
        self::$subManager = SubDbMetadataManager::getInstance();
    }

    /**
     * @covers SubDbMetadataManagerTest::createFileHash
     */
    public function testCreateFileHash()
    {
        $filePath = dirname(__FILE__) . '/dexter.mp4';
        $hashExpected = 'ffd8d4aa68033dc03d1c8ef373b9028c';
        $hashCalculated = self::$subManager->createFileHash($filePath);

        $this->assertEquals($hashExpected, $hashCalculated);
        return $hashCalculated;
    }

    /**
     * @covers  SubDbMetadataManagerTest::searchMediaSubtitle
     * @depends testCreateFileHash
     */
    public function testSearchMediaSubtitle($hash)
    {
        $searchResult = self::$subManager->searchMediaSubtitle($hash);
        $this->assertNotNull($searchResult);
        return $hash;
    }

    /**
     * @covers  SubDbMetadataManagerTest::downloadMediaSubtitle
     * @depends testSearchMediaSubtitle
     */
    public function testDownloadMediaSubtitle($hash)
    {
        $subtitle = self::$subManager->downloadMediaSubtitle($hash, 'en');
        $this->assertNotNull($subtitle);
    }
}
