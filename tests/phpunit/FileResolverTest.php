<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use BlueSpice\CloudClient\PDFTemplatePlaceholderParams\Title;
use DOMDocument;
use DOMElement;
use File;
use MediaWiki\Extension\PDFCreator\Utility\FileResolver;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWikiLangTestCase;
use RepoGroup;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\FileResolver::execute
 */
class FileResolverTest extends MediaWikiLangTestCase {

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Utility\FileResolver::execute
	 */
	public function testGetFilename() {
		$this->overrideConfigValues( [
			MainConfigNames::ScriptPath => '/pdfcreator',
		] );

		$services = MediaWikiServices::getInstance();
		$config = $services->getMainConfig();
		$repoGroup = $this->mockRepoGroup();
		$titleFactory = $services->getTitleFactory();

		$dom = new DOMDocument();
		$dom->loadXML( $this->getHtml() );

		$imgs = $dom->getElementsByTagName( 'img' );
		for ( $index = 0; $index < count( $imgs ); $index++ ) {
			$img = $imgs[$index];
			if ( $img instanceof DOMElement === false ) {
				continue;
			}

			$fileResolver = new FileResolver( $config, $repoGroup, $titleFactory );
			$file = $fileResolver->execute( $img );

			$this->assertEquals( 'Example.jpg', $file->getName() );
		}
	}

	/**
	 * @return string
	 */
	private function getHtml(): string {
		return file_get_contents( __DIR__ . '/data/FileResolverTest-input.html' );
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
				],
			],
		];
	}
}
