<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use DOMDocument;
use MediaWiki\Extension\PDFCreator\Processor\ObjectProcessor;
use MediaWiki\Extension\PDFCreator\Utility\ExportPage;
use MediaWikiLangTestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Processor\ObjectProcessor
 */
class ObjectProcessorTest extends MediaWikiLangTestCase {

	public function testObjectToImgConversion() {
		$dom = $this->getDOMDocument();
		$page = new ExportPage( 'content', $dom, 'testPage' );
		$pages = [ $page ];
		$images = [];
		$attachments = [];

		$processor = new ObjectProcessor();
		$processor->execute( $pages, $images, $attachments );
		$processedHtml = $page->getDOMDocument()->saveHTML();

		$this->assertEquals(
			$this->getExpectedHtml(),
			rtrim( $processedHtml, "\n" )
		);
	}

	/**
	 * @return DOMDocument
	 */
	private function getDOMDocument(): DOMDocument {
		$html = file_get_contents( __DIR__ . '/data/ObjectProcessorTest-input.html' );
		$dom = new DOMDocument();
		$dom->loadXML( $html );

		return $dom;
	}

	/**
	 * @return string
	 */
	private function getExpectedHtml(): string {
		return file_get_contents( __DIR__ . '/data/ObjectProcessorTest-output.html' );
	}
}
