<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use MediaWiki\Extension\PDFCreator\Factory\PageSpecFactory;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\PageSpecFactory
 */
class PageSpecFactoryTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Factory\PageSpecFactory::newFromSpec
	 */
	public function testNewFromSpec() {
		$services = MediaWikiServices::getInstance();

		$factory = new PageSpecFactory(
			$services->getTitleFactory(),
			$services->getRedirectLookup(),
			$services->getPageProps(),
			$services->getMainConfig()
		);

		$value = [
			'type' => 'raw',
			'target' => '',
			'label' => 'test',
		];

		$pageSpec = $factory->newFromSpec( $value, [] );

		$this->assertEquals( 'raw', $pageSpec->getType() );
		$this->assertEquals( 'test', $pageSpec->getLabel() );
		$this->assertNull( $pageSpec->getPrefixedDBKey() );
	}
}
