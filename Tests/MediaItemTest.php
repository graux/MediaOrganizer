<?php

/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-10-12 at 10:15:44.
 */
class MediaItemTest extends PHPUnit_Framework_TestCase
{

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    /**
     * @covers MediaItem::createMediaItem
     */
    public function testCreateMediaItemSerie()
    {
        $mItemSeries = MediaItem::createMediaItem('/tmp/The.Big.Bang.Theory_s06e01_DIMENSION_[720p].mkv');
        $this->assertInstanceOf('MediaItemSeries', $mItemSeries);

        $tvdb = TvDbMetadataManager::getInstance();
        $tvdb->fetchMediaItemData($mItemSeries);
        $this->assertNotNull($mItemSeries->posterUrl);

        return $mItemSeries;
    }

    public function testCreateMediaItemSerieSegmentationFault()
    {
        $mItemSeries = MediaItem::createMediaItem('/tmp/Arrow.S02E01.720p.HDTV.X264-DIMENSION.mkv');
        $this->assertInstanceOf('MediaItemSeries', $mItemSeries);

        $tvdb = TvDbMetadataManager::getInstance();
        $tvdb->fetchMediaItemData($mItemSeries);
        $this->assertNotNull($mItemSeries->posterUrl);

        return $mItemSeries;
    }

    public function testCreateMediaItemMovie()
    {
        $mItemMovie = MediaItem::createMediaItem('/var/tmp/test.new/North By Northwest [50th Anniversary SE].1959.BRRip.XviD-VLiS.avi');
        $this->assertInstanceOf('MediaItemMovie', $mItemMovie);
        $this->assertEquals(strtolower($mItemMovie->name), 'north by northwest');

        $mItemMovie = MediaItem::createMediaItem('/var/tmp/test.new/El secreto de sus ojos [BDrip m-720p].mkv');
        $this->assertInstanceOf('MediaItemMovie', $mItemMovie);
        $this->assertEquals(strtolower($mItemMovie->name), 'el secreto de sus ojos');
        $tmdb = MovieDbMetadataManager::getInstance();
        $tmdb->fetchMediaItemData($mItemMovie);
        $this->assertEquals(strtolower($mItemMovie->originalTitle), 'el secreto de sus ojos');

        $mItemMovie = MediaItem::createMediaItem('/var/tmp/test.new/The.Dark.Knight.Rises-(2012)-[1080p].RELEASE.mkv');
        $this->assertInstanceOf('MediaItemMovie', $mItemMovie);

        $tmdb = MovieDbMetadataManager::getInstance();
        $tmdb->fetchMediaItemData($mItemMovie);
        $this->assertNotNull($mItemMovie->posterUrl);

        return $mItemMovie;
    }


    /**
     * @covers MediaItem::getYear
     * @depends testCreateMediaItemMovie
     * @param MediaItem $mediaItem
     */
    public function testGetYear($mediaItem)
    {
        $expected = '2012';
        $actual = $mediaItem->getYear();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers MediaItemMovie::getMetadata
     * @depends testCreateMediaItemMovie
     * @param MediaItem $mediaItem
     */
    public function testGetMetadataMovie($mediaItem)
    {
        $xmlRaw = $mediaItem->getMetadata();

        $xml = simplexml_load_string($xmlRaw);
        $this->assertEquals('The Dark Knight Rises', (string)$xml->title);
        $this->assertContains('Christian Bale', $xmlRaw);
        $this->assertContains('Christopher Nolan', (string)$xml->director);
    }
}
