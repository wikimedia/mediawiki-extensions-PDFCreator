<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use MediaWiki\Extension\PDFCreator\Utility\UrlHelper;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\UrlHelper
 */
class UrlHelperTest extends \MediaWikiIntegrationTestCase {

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Utility\UrlHelper::getTitleFromUrl
	 * @dataProvider provideUrls
	 */
	public function testGetTitleFromUrl( $url, $expected ) {
		$services = MediaWikiServices::getInstance();

		$urlHelper = new UrlHelper(
			$services->getMainConfig(), $services->getTitleFactory()
		);

		$this->overrideConfigValues( [
			MainConfigNames::Server => 'http://example.test',
			MainConfigNames::ScriptPath => '/w',
			MainConfigNames::ArticlePath => '/wiki/$1',
		] );

		$title = $urlHelper->getTitleFromUrl( $url );

		if ( !$expected ) {
			$this->assertEquals( $expected, $title );
		} else {
			$this->assertEquals( $expected, $title->getText() );
		}
	}

	public function provideUrls() {
		return [
			'normal url' => [
				'/wiki/Sandbox',
				'Sandbox'
			],
			'normal url with namespace' => [
				'/wiki/MediaWiki:Sandbox',
				'Sandbox'
			],
			'normal url with namespace and subpages' => [
				'/wiki/MediaWiki:Sandbox/Test/Subpage',
				'Sandbox/Test/Subpage'
			],
			'full url' => [
				'http://example.test/wiki/Sandbox',
				'Sandbox'
			],
			'full url with fragment and query' => [
				'http://example.test/wiki/Sandbox#test?abc&def',
				'Sandbox'
			],
			'index.php url' => [
				'/wiki/index.php?title=Sandbox',
				'Sandbox'
			],
			'full index.php url' => [
				'http://example.test/wiki/index.php?title=Sandbox',
				'Sandbox'
			],
			'full index.php url with fragment and query' => [
				'http://example.test/wiki/index.php?title=Sandbox#test&abc',
				'Sandbox'
			],
			'image from script' => [
				'/w/nsfr_img_auth.php/b/bf/Sandbox.svg',
				'Sandbox.svg'
			],
			'file from script' => [
				'/w/nsfr_img_auth.php/b/bf/Sandbox.pdf',
				'Sandbox.pdf'
			],
			'empty url' => [
				'',
				null
			],
			'invalid url' => [
				'foobar',
				null
			],
		];
	}
}
