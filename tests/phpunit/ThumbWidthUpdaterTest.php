<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use DOMDocument;
use MediaWiki\Extension\PDFCreator\Utility\ExportPage;
use MediaWiki\Extension\PDFCreator\Utility\ThumbWidthUpdater;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\ThumbWidthUpdater
 */
class ThumbWidthUpdaterTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Utility\ThumbWidthUpdater::execute
	 */
	public function testExecute() {
		$pages = $this->getPages();
		$imagePathUpdater = new ThumbWidthUpdater();
		$imagePathUpdater->execute( $pages );

		$dom = $pages[0]->getDOMDocument();
		$actual = $dom->saveXML( $dom->documentElement );

		$this->assertEquals(
			$this->getExpectedHtml(),
			$actual
		);
	}

	/**
	 * @return ExportPage[]
	 */
	private function getPages(): array {
		return [
			new ExportPage(
				'page',
				$this->getDOMDocument(),
				'Test page 1',
				'Test_page_1'
			)
		];
	}

	/**
	 * @return DOMDocument
	 */
	private function getDOMDocument(): DOMDocument {
		$html = file_get_contents( __DIR__ . '/data/ThumbWidthUpdater-input.html' );
		$dom = new DOMDocument();

		$dom->loadXML( $html );

		return $dom;
	}

	/**
	 * @return string
	 */
	private function getExpectedHtml(): string {
		return file_get_contents( __DIR__ . '/data/ThumbWidthUpdater-output.html' );
	}
}
