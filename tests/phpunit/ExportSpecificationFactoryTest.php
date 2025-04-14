<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use MediaWiki\Extension\PDFCreator\Factory\ExportSpecificationFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Factory\ExportSpecificationFactory
 */
class ExportSpecificationFactoryTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Factory\ExportSpecificationFactory::newFromJson
	 */
	public function testNewFromJson() {
		$factory = new ExportSpecificationFactory();

		$json = $this->getInput();

		$specification = $factory->newFromJson( $json );

		$this->assertEquals( 'batch', $specification->getModule() );
		$this->assertEquals( 'open-html-2-pdf', $specification->getBackend() );
	}

	private function getInput(): string {
		$data = [
			"module" => "batch",
			"template-provider" => "wiki",
			"target" => "filesystem",
			"backend" => "open-html-2-pdf",
			"params" => [
				"template" => "default",
				"title" => "Test export",
				"filename" => "Test.pdf"
			],
			"pages" => [
				[
					"type" => "page",
					"target" => "Test_Text"
				],
				[
					"type" => "page",
					"target" => "Test_Text",
					"rev-id" => "113"
				],
				[
					"type" => "raw",
					"target" => "",
					"label" => "Testseite 2"
				],
				[
					"type" => "raw",
					"target" => "",
					"label" => "Test Content elements"
				],
				[
					"type" => "raw",
					"target" => "",
					"label" => "Headings"
				],
				[
					"type" => "raw",
					"target" => "",
					"label" => "Text"
				],
				[
					"type" => "raw",
					"target" => "",
					"label" => "Tables"
				],
				[
					"type" => "raw",
					"target" => "",
					"label" => "Images"
				]
			]
		];
		return json_encode( $data );
	}
}
