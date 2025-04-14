<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use DOMDocument;
use MediaWiki\Extension\PDFCreator\Utility\BookmarksXMLBuilder;
use MediaWiki\Extension\PDFCreator\Utility\ExportPage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\BookmarksXMLBuilder
 */
class BookmarksXMLBuilderTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Utility\BookmarksXMLBuilder::execute
	 */
	public function testExecute() {
		$xmlBuilder = new BookmarksXMLBuilder();
		$input = $this->getPages();
		$actual = $xmlBuilder->execute( $input );
		$expected = $this->getExpected();

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @return ExportPage[]
	 */
	private function getPages(): array {
		$htmlOpen = '<html><head></head><body>';
		$htmlClose = '</body></html>';

		$dom1 = new DOMDocument();
		$dom1->loadXML(
			implode( '', [
				$htmlOpen,
				'<div class="pdfcreator-content-page">',
				'<h1 class="firstHeading" id="test-id-1">Test 1</h1>',
				'</div>',
				$htmlClose
			] )
		);
		$dom2 = new DOMDocument();
		$dom2->loadXML(
			implode( '', [
				$htmlOpen,
				'<div class="pdfcreator-content-page">',
				'<h1 class="firstHeading" id="test-id-2">Test 2</h1>',
				'</div>',
				$htmlClose
			] )
		);
		return [
			new ExportPage( 'raw', $dom1, 'Content 1' ),
			new ExportPage( 'raw', $dom2, 'Content 2' ),
		];
	}

	/**
	 * @return string
	 */
	private function getExpected(): string {
		$xml = [
			'<bookmarks>',
			'<bookmark name="Content 1" href="#test-id-1"/>',
			'<bookmark name="Content 2" href="#test-id-2"/>',
			'</bookmarks>'
		];
		return implode( '', $xml );
	}
}
