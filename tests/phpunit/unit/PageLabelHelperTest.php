<?php

namespace MediaWiki\Extension\PDFCreator\Tests\Unit;

use MediaWiki\Extension\PDFCreator\Utility\PageLabelHelper;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\PageLabelHelper
 */
class PageLabelHelperTest extends TestCase {

	/**
	 * @dataProvider provideGetTitleText
	 * @covers \MediaWiki\Extension\PDFCreator\Utility\PageLabelHelper::getTitleText
	 */
	public function testGetTitleText(
		string $text, string $prefixedText, string $subpageText, string $titleText,
		bool $showNamespace, string $expected
	) {
		$title = $this->createMock( Title::class );
		$title->method( 'getText' )->willReturn( $text );
		$title->method( 'getPrefixedText' )->willReturn( $prefixedText );
		$title->method( 'getSubpageText' )->willReturn( $subpageText );

		$PageLabelHelper = new PageLabelHelper();

		$this->assertSame(
			$expected,
			$PageLabelHelper->getTitleText( $title, $titleText, $showNamespace )
		);
	}

	public static function provideGetTitleText(): array {
		return [
			'main namespace, show-namespace false' => [
				'text' => 'Testpage',
				'prefixedText' => 'Testpage',
				'subpageText' => 'Testpage',
				'titleText' => 'Testpage',
				'showNamespace' => false,
				'expected' => 'Testpage',
			],
			'main namespace, show-namespace true' => [
				'text' => 'Testpage',
				'prefixedText' => 'Testpage',
				'subpageText' => 'Testpage',
				'titleText' => 'Testpage',
				'showNamespace' => true,
				'expected' => 'Testpage',
			],
			'demo namespace, show-namespace false' => [
				'text' => 'Testpage',
				'prefixedText' => 'Demo:Testpage',
				'subpageText' => 'Testpage',
				'titleText' => 'Demo:Testpage',
				'showNamespace' => false,
				'expected' => 'Testpage',
			],
			'demo namespace, show-namespace true' => [
				'text' => 'Testpage',
				'prefixedText' => 'Demo:Testpage',
				'subpageText' => 'Testpage',
				'titleText' => 'Demo:Testpage',
				'showNamespace' => true,
				'expected' => 'Demo:Testpage',
			],
			'demo namespace, subpage, show-namespace false' => [
				'text' => 'Testpage/Subpage',
				'prefixedText' => 'Demo:Testpage/Subpage',
				'subpageText' => 'Testpage/Subpage',
				'titleText' => 'Demo:Testpage/Subpage',
				'showNamespace' => false,
				'expected' => 'Testpage/Subpage',
			],
			'demo namespace, subpage, show-namespace true' => [
				'text' => 'Testpage/Subpage',
				'prefixedText' => 'Demo:Testpage/Subpage',
				'subpageText' => 'Testpage/Subpage',
				'titleText' => 'Demo:Testpage/Subpage',
				'showNamespace' => true,
				'expected' => 'Demo:Testpage/Subpage',
			],
		];
	}

}
