<?php

namespace MediaWiki\Extension\PDFCreator\Tests\Unit;

use MediaWiki\Config\Config;
use MediaWiki\Extension\PDFCreator\Factory\PageSpecFactory;
use MediaWiki\Extension\PDFCreator\Utility\PageSpec;
use MediaWiki\Page\PageProps;
use MediaWiki\Page\RedirectLookup;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Factory\PageSpecFactory
 */
class PageSpecFactoryTest extends TestCase {

	/**
	 * @param string $prefixedDBKey
	 * @param string $prefixedText
	 * @param string $text
	 * @return Title
	 */
	private function makeTitle(
		string $prefixedDBKey, string $prefixedText, string $text, string $subpageText
	): Title {
		$title = $this->createMock( Title::class );
		$title->method( 'getPrefixedDBkey' )->willReturn( $prefixedDBKey );
		$title->method( 'getPrefixedText' )->willReturn( $prefixedText );
		$title->method( 'getText' )->willReturn( $text );
		$title->method( 'getSubpageText' )->willReturn( $subpageText );
		$title->method( 'getId' )->willReturn( 1 );
		return $title;
	}

	/**
	 * @param array<string, Title> $titlesByDBKey
	 * @return PageSpecFactory
	 */
	private function makeFactory( array $titlesByDBKey ): PageSpecFactory {
		$titleFactory = $this->createMock( TitleFactory::class );
		$titleFactory->method( 'newFromDBKey' )->willReturnCallback(
			static function ( string $key ) use ( $titlesByDBKey ) {
				return $titlesByDBKey[$key] ?? null;
			}
		);

		$redirectLookup = $this->createMock( RedirectLookup::class );
		$redirectLookup->method( 'getRedirectTarget' )->willReturn( null );

		$pageProps = $this->createMock( PageProps::class );
		$pageProps->method( 'getProperties' )->willReturn( [] );

		$config = $this->createMock( Config::class );

		return new PageSpecFactory( $titleFactory, $redirectLookup, $pageProps, $config );
	}

	public static function provideNewFromSpecShowNamespace(): array {
		return [
			'main namespace, show-namespace true' => [
				'target' => 'Testpage',
				'prefixedText' => 'Testpage',
				'text' => 'Testpage',
				'subpageText' => 'Testpage',
				'showNamespace' => true,
				'expectedLabel' => 'Testpage',
			],
			'main namespace, show-namespace false' => [
				'target' => 'Testpage',
				'prefixedText' => 'Testpage',
				'text' => 'Testpage',
				'subpageText' => 'Testpage',
				'showNamespace' => false,
				'expectedLabel' => 'Testpage',
			],
			'demo namespace, show-namespace true' => [
				'target' => 'Demo:Testpage',
				'prefixedText' => 'Demo:Testpage',
				'text' => 'Testpage',
				'subpageText' => 'Testpage',
				'showNamespace' => true,
				'expectedLabel' => 'Demo:Testpage',
			],
			'demo namespace, show-namespace false' => [
				'target' => 'Demo:Testpage',
				'prefixedText' => 'Demo:Testpage',
				'text' => 'Testpage',
				'subpageText' => 'Testpage',
				'showNamespace' => false,
				'expectedLabel' => 'Testpage',
			],
			'main namespace, subpage, show-namespace true' => [
				'target' => 'Testpage/Subpage',
				'prefixedText' => 'Testpage/Subpage',
				'text' => 'Testpage/Subpage',
				'subpageText' => 'Subpage',
				'showNamespace' => true,
				'expectedLabel' => 'Testpage/Subpage',
			],
			'main namespace, subpage, show-namespace false' => [
				'target' => 'Testpage/Subpage',
				'prefixedText' => 'Testpage/Subpage',
				'text' => 'Testpage/Subpage',
				'subpageText' => 'Subpage',
				'showNamespace' => false,
				'expectedLabel' => 'Subpage',
			],
			'demo namespace, subpage, show-namespace true' => [
				'target' => 'Demo:Testpage/Subpage',
				'prefixedText' => 'Demo:Testpage/Subpage',
				'text' => 'Testpage/Subpage',
				'subpageText' => 'Subpage',
				'showNamespace' => true,
				'expectedLabel' => 'Demo:Testpage/Subpage',
			],
			'demo namespace, subpage, show-namespace false' => [
				'target' => 'Demo:Testpage/Subpage',
				'prefixedText' => 'Demo:Testpage/Subpage',
				'text' => 'Subpage',
				'subpageText' => 'Subpage',
				'showNamespace' => false,
				'expectedLabel' => 'Subpage',
			],
		];
	}

	/**
	 * @dataProvider provideNewFromSpecShowNamespace
	 * @covers \MediaWiki\Extension\PDFCreator\Factory\PageSpecFactory::newFromSpec
	 */
	public function testNewFromSpecRespectsShowNamespaceOption(
		string $target, string $prefixedText, string $text, string $subpageText, bool $showNamespace,
		string $expectedLabel
	) {
		$title = $this->makeTitle( $target, $prefixedText, $text, $subpageText );
		$factory = $this->makeFactory( [ $target => $title ] );

		$pageSpec = $factory->newFromSpec(
			[ 'target' => $target ],
			[ 'show-namespace' => $showNamespace ]
		);

		$this->assertInstanceOf( PageSpec::class, $pageSpec );
		$this->assertSame( $expectedLabel, $pageSpec->getLabel() );
		$this->assertSame( $target, $pageSpec->getPrefixedDBKey() );
	}
}
