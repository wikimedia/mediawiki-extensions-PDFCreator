<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use MediaWiki\Extension\PDFCreator\Factory\PageSpecFactory;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\PageSpecFactory
 */
class PageSpecFactoryTest extends TestCase {

	/** @var PageSpecFactory */
	private PageSpecFactory $factory;

	public function setUp(): void {
		$services = MediaWikiServices::getInstance();

		$this->factory = new PageSpecFactory(
			$services->getTitleFactory(),
			$services->getRedirectLookup(),
			$services->getPageProps(),
			$services->getMainConfig()
		);
	}

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Factory\PageSpecFactory::newFromSpec
	 */
	public function testNewFromSpec() {
		$value = [
			'type' => 'raw',
			'target' => '',
			'label' => 'test',
		];

		$pageSpec = $this->factory->newFromSpec( $value, [] );

		$this->assertEquals( 'raw', $pageSpec->getType() );
		$this->assertEquals( 'test', $pageSpec->getLabel() );
		$this->assertNull( $pageSpec->getPrefixedDBKey() );
	}

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Factory\PageSpecFactory::newFromSpec
	 */
	public function testShowNamespace(): void {
		$pageSpec = $this->factory->newFromSpec( [ 'target' => null, ], [] );
		$this->assertNull( $pageSpec );

		$pageSpec = $this->factory->newFromSpec( [ 'target' => 'Mediawiki:bar' ], [ 'nsPrefix' => true ] );
		$this->assertEquals( 'MediaWiki:Bar', $pageSpec->getLabel() );

		$pageSpec = $this->factory->newFromSpec( [ 'target' => 'bar' ], [ 'nsPrefix' => true ] );
		$this->assertEquals( 'Bar', $pageSpec->getLabel() );

		$pageSpec = $this->factory->newFromSpec( [ 'target' => 'Mediawiki:bar' ], [ 'nsPrefix' => false ] );
		$this->assertEquals( 'Bar', $pageSpec->getLabel() );

		$pageSpec = $this->factory->newFromSpec( [ 'target' => 'bar' ], [ 'nsPrefix' => false ] );
		$this->assertEquals( 'Bar', $pageSpec->getLabel() );

		$pageSpec = $this->factory->newFromSpec( [ 'target' => 'Mediawiki:bar' ], [] );
		$this->assertEquals( 'Bar', $pageSpec->getLabel() );

		$pageSpec = $this->factory->newFromSpec( [ 'target' => 'bar' ], [] );
		$this->assertEquals( 'Bar', $pageSpec->getLabel() );
	}
}
