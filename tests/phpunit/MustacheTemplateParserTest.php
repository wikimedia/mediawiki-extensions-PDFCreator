<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use MediaWiki\Extension\PDFCreator\Utility\MustacheTemplateParser;
use MediaWikiLangTestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\MustacheTemplateParser
 */
class MustacheTemplateParserTest extends MediaWikiLangTestCase {

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Utility\MustacheTemplateParser::execute
	 */
	public function testExecute() {
		$parser = new MustacheTemplateParser();

		$actual = $parser->execute(
			__DIR__ . '/data',
			'content',
			$this->getParams()
		);

		$this->assertEquals( $this->getExpected(), $actual );
	}

	/**
	 * @return array
	 */
	private function getParams(): array {
		return [
			'content' => '<span>lorem ipsum</span>'
		];
	}

	private function getExpected(): string {
		return '<div>Main Page</div><div><span>lorem ipsum</span></div>';
	}
}
