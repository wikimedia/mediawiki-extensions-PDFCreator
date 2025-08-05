<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use DOMDocument;
use MediaWiki\Extension\PDFCreator\Utility\ExcludeExportUpdater;
use MediaWiki\Extension\PDFCreator\Utility\ExportPage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\ExcludeExportUpdater
 */
class ExcludeExportUpdaterTest extends TestCase {

	/**
	 * Test that the ExcludeExportUpdater removes the content between
	 * the start and end markers correctly and also updates the TOC.
	 *
	 * @return void
	 */
	public function testExecute() {
		$result = file_get_contents( __DIR__ . '/data/ExcludeExportTest-output.html' );
		$definition = $this->getDefinition();
		$dom = new DOMDocument();
		$dom->loadXML( $definition[1] );
		$exportPage = new ExportPage( $definition[0], $dom, $definition[2], $definition[3] );
		$excludeExportUpdater = new ExcludeExportUpdater();
		$excludeExportUpdater->execute( [ $exportPage ] );
		$html = $exportPage->getDOMDocument()->saveHTML();
		$this->assertXmlStringEqualsXmlString( $result, $html );
	}

	/**
	 * @return array
	 */
	private function getDefinition(): array {
		$html = file_get_contents( __DIR__ . '/data/ExcludeExportTest-input.html' );

		return [
			'page',
			$html,
			'Page 1',
			'Test:Page_1'
		];
	}

}
