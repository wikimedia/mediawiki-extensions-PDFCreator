<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use DOMDocument;
use MediaWiki\Extension\PDFCreator\Utility\ExportHtmlBuilder;
use MediaWiki\Extension\PDFCreator\Utility\ExportPage;
use MediaWiki\Extension\PDFCreator\Utility\HtmlMetaItem;
use MediaWiki\Language\Language;
use MediaWikiLangTestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\ExportHtmlBuilder
 */
class ExportHtmlBuilderTest extends MediaWikiLangTestCase {

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Utility\ExportHtmlBuilder::execute
	 */
	public function testExecute() {
		$languageMock = $this->mockLanguage();
		$ExportHtmlBuilder = new ExportHtmlBuilder( $languageMock );

		$actual = $ExportHtmlBuilder->execute(
			$this->getPages(),
			[],
			[
				'Test style block 1' => '.firstHeading { color: blue; }',
				'Test style block 2' => '.toc { background-color: lightgrey; }',
			],
			[
				new HtmlMetaItem( 'name 1', 'test meta item 1' ),
				new HtmlMetaItem( 'name 2', 'test meta item 2' ),
				new HtmlMetaItem( 'name 3', '', 'httpEquiv meta item 3' ),
				new HtmlMetaItem( 'name 4', 'test meta item 4', 'httpEquiv meta item 4' ),
			],
			'Test title',
			'',
			'batch'
		);

		$this->assertXmlStringEqualsXmlString(
			$this->getExpected(),
			$actual
		);
	}

	/**
	 * @return string
	 */
	private function getExpected(): string {
		$xml = file_get_contents( __DIR__ . '/data/ExportHtmlBuilderTest-output.html' );
		$dom = new DOMDocument();
		$dom->loadXML( $xml );
		return $dom->saveXML( $dom->documentElement );
	}

	/**
	 * @return array
	 */
	private function getPages(): array {
		$htmlOpen = '<html><head></head><body>';
		$htmlClose = '</body></html>';

		$dom1 = new DOMDocument();
		$dom1->loadXML(
			$htmlOpen . '<div class="pdfcreator-content-page ns-0 page-Dbkey">page content 1</div>' . $htmlClose
		);
		$dom2 = new DOMDocument();
		$dom2->loadXML( $htmlOpen . '<div class="pdfcrator-raw-page">page content 2</div>' . $htmlClose );
		return [
			new ExportPage( 'raw', $dom1, 'Content 1' ),
			new ExportPage( 'raw', $dom2, 'Content 2' ),
		];
	}

	/**
	 * @return MockObject|Language&MockObject
	 */
	private function mockLanguage() {
		$languageMock = $this->getMockBuilder( Language::class )
			->disableOriginalConstructor()
			->getMock();
		$languageMock->method( 'getHtmlCode' )->willReturn( 'en' );
		return $languageMock;
	}
}
