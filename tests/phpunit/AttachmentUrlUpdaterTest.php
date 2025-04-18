<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use DOMDocument;
use MediaWiki\Extension\PDFCreator\Utility\AttachmentUrlUpdater;
use MediaWiki\Extension\PDFCreator\Utility\ExportPage;
use MediaWiki\Extension\PDFCreator\Utility\WikiFileResource;
use MediaWiki\MainConfigNames;
use MediaWikiLangTestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\AttachmentUrlUpdater
 */
class AttachmentUrlUpdaterTest extends MediaWikiLangTestCase {

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Utility\AttachmentUrlUpdater::execute
	 */
	public function testExecute() {
		$this->overrideConfigValues( [
			MainConfigNames::ScriptPath => '/pdfcreator',
		] );

		$pages = $this->getPages();
		$imagePathUpdater = new AttachmentUrlUpdater();
		$imagePathUpdater->execute(
			$pages,
			$this->getImages()
		);

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
	 * @return FileResoruce[]
	 */
	private function getImages(): array {
		return [
			new WikiFileResource(
				[
					'/pdfcreator/images/a/a9/Example.jpg',
					'/pdfcreator/images/thumb/a/a9/Example.jpg/300px-Example.jpg'
				],
				'/var/www/pdfcreator/images/a/a9/Example.jpg',
				'Example.jpg'
			),
		];
	}

	/**
	 * @return DOMDocument
	 */
	private function getDOMDocument(): DOMDocument {
		$html = file_get_contents( __DIR__ . '/data/AttachmentUrlUpdaterTest-input.html' );
		$dom = new DOMDocument();

		$dom->loadXML( $html );

		return $dom;
	}

	/**
	 * @return string
	 */
	private function getExpectedHtml(): string {
		return file_get_contents( __DIR__ . '/data/AttachmentUrlUpdaterTest-output.html' );
	}
}
