<?php

namespace MediaWiki\Extension\PDFCreator\Tests\Integration;

use BlueSpice\CloudClient\PDFTemplatePlaceholderParams\Title;
use DOMDocument;
use DOMElement;
use File;
use MediaWiki\Config\Config;
use MediaWiki\Extension\PDFCreator\Utility\FileResolver;
use MediaWiki\MainConfigNames;
use MediaWikiLangTestCase;
use RepoGroup;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\FileResolver::execute
 * @covers \MediaWiki\Extension\PDFCreator\Utility\FileResolver::resolveFromThumbScript
 */
class FileResolverTest extends MediaWikiLangTestCase {

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\Utility\FileResolver::execute
	 */
	public function testGetFilename() {
		$this->overrideConfigValues( [
			MainConfigNames::ScriptPath => '/pdfcreator',
		] );

		$services = $this->getServiceContainer();
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
	 * @covers \MediaWiki\Extension\PDFCreator\Utility\FileResolver::resolveFromThumbScript
	 * @dataProvider provideThumbScriptSrcs
	 *
	 * @param string $src
	 * @param string|null $expectedName null means the file should not be resolved
	 */
	public function testResolveFromThumbScript( string $src, ?string $expectedName ): void {
		$config = $this->createMock( Config::class );
		$config->method( 'get' )->willReturnCallback( static function ( string $key ): string {
			return match ( $key ) {
				'ThumbnailScriptPath' => '/w/thumb.php',
				'Server'              => 'https://example.com',
				'UploadPath'          => '/images',
				'ScriptPath'          => '/w',
				default               => '',
			};
		} );

		$repoGroup = $this->mockRepoGroup();
		$titleFactory = $this->getServiceContainer()->getTitleFactory();

		$dom = new DOMDocument();
		$dom->loadXML( '<html><body><img src="' . htmlspecialchars( $src ) . '"/></body></html>' );
		$img = $dom->getElementsByTagName( 'img' )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $img );

		$fileResolver = new FileResolver( $config, $repoGroup, $titleFactory );
		$file = $fileResolver->execute( $img );

		if ( $expectedName !== null ) {
			$this->assertNotNull( $file, "Expected file to be resolved from: $src" );
			$this->assertEquals( $expectedName, $file->getName() );
		} else {
			$this->assertNull( $file, "Expected no file to be resolved from: $src" );
		}
	}

	/**
	 * @return array[]
	 */
	public function provideThumbScriptSrcs(): array {
		return [
			'relative thumb.php URL resolves file' => [
				'/w/thumb.php?f=Example.jpg&width=300',
				'Example.jpg',
			],
			'absolute thumb.php URL resolves file' => [
				'https://example.com/w/thumb.php?f=Example.jpg&width=120',
				'Example.jpg',
			],
			'thumb.php URL with unknown file returns null' => [
				'/w/thumb.php?f=NoSuchFile.jpg&width=300',
				null,
			],
			'thumb.php URL without f param returns null' => [
				'/w/thumb.php?width=300',
				null,
			],
			'non-matching script path is not handled by resolveFromThumbScript' => [
				'/w/other.php?f=Example.jpg',
				null,
			],
		];
	}

	/**
	 * @return string
	 */
	private function getHtml(): string {
		return file_get_contents( __DIR__ . '/../data/FileResolverTest-input.html' );
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
			$imageMock->method( 'exists' )->willReturn( true );
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
