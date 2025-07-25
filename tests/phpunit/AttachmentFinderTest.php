<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use DOMDocument;
use File;
use MediaWiki\Extension\PDFCreator\Utility\AttachmentFinder;
use MediaWiki\Extension\PDFCreator\Utility\ExportPage;
use MediaWiki\Extension\PDFCreator\Utility\WikiFileResource;
use MediaWiki\MainConfigNames;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use RepoGroup;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\AttachmentFinder::execute
 */
class AttachmentFinderTest extends MediaWikiIntegrationTestCase {

	public function testExecute() {
		$this->overrideConfigValues( [
			MainConfigNames::UploadPath => '/pdfcreator/images',
			MainConfigNames::ScriptPath => '/pdfcreator',
		] );

		$services = $this->getServiceContainer();
		$titleFactory = $services->getTitleFactory();
		$config = $services->getMainConfig();
		$repoGroup = $this->mockRepoGroup();
		$fileFinder = new AttachmentFinder( $titleFactory, $config, $repoGroup );

		$actual = $fileFinder->execute( $this->getPages() );

		$expected = [
			new WikiFileResource(
				[
					'/pdfcreator/images/a/a9/Example.jpg'
				],
				'/app/d/pdfcreator/images/a/a9/Example.jpg',
				'Example.jpg'
			)
		];
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @return array
	 */
	private function getPages(): array {
		$pages = [];
		foreach ( $this->getDefinitions() as $definition ) {
			$dom = new DOMDocument();
			$dom->loadXML( $definition[1] );

			$pages[] = new ExportPage( $definition[0], $dom, $definition[2], $definition[3] );
		}
		return $pages;
	}

	/**
	 * @return array
	 */
	private function getDefinitions(): array {
		$html1 = file_get_contents( __DIR__ . '/data/AttachmentFinderTest-input-1.html' );
		$html2 = file_get_contents( __DIR__ . '/data/AttachmentFinderTest-input-2.html' );
		return [
			[
				'page',
				$html1,
				'Page 1',
				'Test:Page_1'
			], [
				'page',
				$html2,
				'Page 2',
				'Test:Page_2'
			]
		];
	}

	/**
	 * @return MockObject|RepoGroup&MockObject
	 */
	private function mockRepoGroup() {
		$imageInfo = $this->getImages();
		$localRepoMock = $this->getMockBuilder( RepoGroup::class )
			->disableOriginalConstructor()
			->getMock();
		$localRepoMock->method( 'findFile' )->willReturnCallback( function ( $fileTitle )  use ( $imageInfo ) {
			$name = $fileTitle->getDbKey();
			$image = $imageInfo[$name] ?? null;
			if ( !$image ) {
				return null;
			}
			$revId = max( array_keys( $image ) );
			$image = $image[$revId];
			$imageMock = $this->getMockBuilder( File::class )
				->disableOriginalConstructor()
				->getMock();
			$imageMock->method( 'getTitle' )->willReturnCallback( function () use ( $revId ) {
				$titleMock = $this->getMockBuilder( Title::class )
					->disableOriginalConstructor()
					->getMock();
				$titleMock->method( 'getLatestRevID' )->willReturn( $revId );
				return $titleMock;
			} );
			$imageMock->method( 'getName' )->willReturn( $name );
			$imageMock->method( 'getTimestamp' )->willReturn( $image['timestamp'] );
			$imageMock->method( 'getSha1' )->willReturn( $image['sha1'] );
			$imageMock->method( 'getLocalRefPath' )->willReturn( $image['localRefPath'] );
			return $imageMock;
		} );

		return $localRepoMock;
	}

	private function getImages(): array {
		return [
			'Example.jpg' => [
				1 => [
					'timestamp' => '20210101000000',
					'sha1' => 'sha1:1234567890abcdef1234567890abcdef12345678',
					'localRefPath' => '/app/d/pdfcreator/images/a/a9/Example.jpg',
				],
			],
		];
	}
}
