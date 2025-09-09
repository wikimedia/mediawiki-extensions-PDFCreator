<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use DOMDocument;
use MediaWiki\Extension\PDFCreator\Utility\UniqueHtmlIdMaker;
use MediaWiki\MainConfigNames;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\UniqueHtmlIdMaker
 */
class UniqueHtmlIdMakerTest extends \MediaWikiIntegrationTestCase {

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Utility\UniqueHtmlIdMaker::execute
	 */
	public function testExecute() {
		$uniqueHtmlIdMaker = new UniqueHtmlIdMaker();

		$prefix = '12345';

		$input = file_get_contents( __DIR__ . '/data/UniqueHtmlIdMakerTest-input.html' );
		$expected = file_get_contents( __DIR__ . '/data/UniqueHtmlIdMakerTest-output.html' );

		$dom = new DOMDocument();
		$dom->loadXML( $input );

		$uniqueHtmlIdMaker->execute(
			$dom,
			$prefix,
			[
				'12345' => [
					'/wiki/Test',
				]
			]
		);

		$this->assertEquals( $expected, $dom->saveXML( $dom->documentElement ) );
	}

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->overrideConfigValues( [
			MainConfigNames::Server => 'http://example.test',
			MainConfigNames::ArticlePath => '/wiki/$1',
		] );
	}
}
