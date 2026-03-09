<?php

namespace MediaWiki\Extension\PDFCreator\tests\phpunit;

use File;
use MediaWiki\Extension\PDFCreator\PDFCreatorUtil;
use MediaWiki\Extension\PDFCreator\TemplateProvider\Wiki;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentityValue;
use RepoGroup;
use TextContent;

/**
 * @group medium
 * @group BlueSpice
 * @group BlueSpiceExtensions
 * @covers \MediaWiki\Extension\PDFCreator\TemplateProvider\Wiki
 */
class WikiTemplateProviderTest extends \MediaWikiIntegrationTestCase {

	private const FONT_FILENAME = 'ImperialScript-Regular.ttf';
	private const FONT_ABS_PATH = '/srv/www/wiki/images/4/04/ImperialScript-Regular.ttf';
	private const FONT_URL = '/w/nsfr_img_auth.php/0/04/ImperialScript-Regular.ttf';

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\TemplateProvider\Wiki::getTemplate
	 */
	public function testGetTemplateSetsResolvedFontPath() {
		$wiki = $this->makeWiki( $this->makeFontFaceCss( self::FONT_URL ) );

		$template = $wiki->getTemplate( $this->makeContext(), 'TestTemplate' );

		$fontPaths = $template->getResources()->getFontPaths();
		$this->assertArrayHasKey( self::FONT_FILENAME, $fontPaths );
		$this->assertSame( self::FONT_ABS_PATH, $fontPaths[self::FONT_FILENAME] );
	}

	/**
	 * @covers \MediaWiki\Extension\PDFCreator\TemplateProvider\Wiki::getTemplate
	 */
	public function testGetTemplateRewritesFontUrlInStyleBlock() {
		$wiki = $this->makeWiki( $this->makeFontFaceCss( self::FONT_URL ) );

		$template = $wiki->getTemplate( $this->makeContext(), 'TestTemplate' );

		$styleBlocks = $template->getResources()->getStyleBlocks();
		$this->assertNotEmpty( $styleBlocks );
		$css = reset( $styleBlocks );
		$this->assertStringContainsString(
			'url(stylesheets/' . self::FONT_FILENAME . ')',
			$css
		);
		$this->assertStringNotContainsString( self::FONT_URL, $css );
	}

	/**
	 * Non-img_auth URLs (e.g. relative paths for bundled fonts) must be left untouched.
	 *
	 * @covers \MediaWiki\Extension\PDFCreator\TemplateProvider\Wiki::getTemplate
	 */
	public function testGetTemplateIgnoresNonImgAuthFontUrls() {
		$relativeUrl = 'fonts/DejaVuSans.ttf';
		$wiki = $this->makeWiki( $this->makeFontFaceCss( $relativeUrl ), false );

		$template = $wiki->getTemplate( $this->makeContext(), 'TestTemplate' );

		$styleBlocks = $template->getResources()->getStyleBlocks();
		$css = reset( $styleBlocks );

		$this->assertStringContainsString( "url('$relativeUrl')", $css );
	}

	/**
	 * With no @font-face at all the style block should pass through unchanged.
	 *
	 * @covers \MediaWiki\Extension\PDFCreator\TemplateProvider\Wiki::getTemplate
	 */
	public function testGetTemplateWithNoFontFaceReturnsCssUnchanged() {
		$css = 'body { color: red; }';
		$wiki = $this->makeWiki( $css, false );

		$template = $wiki->getTemplate( $this->makeContext(), 'TestTemplate' );
		$styleBlocks = $template->getResources()->getStyleBlocks();

		$this->assertSame( $css, reset( $styleBlocks ) );
	}

	private function makeFontFaceCss( string $url ): string {
		return "@font-face { font-family: 'Imperial'; src: url('$url') format('truetype'); }";
	}

	private function makeContext(): ExportContext {
		return new ExportContext( UserIdentityValue::newAnonymous( '127.0.0.1' ) );
	}

	/**
	 * Build a Wiki instance whose styles slot contains $styles.
	 *
	 * @param string $styles CSS for the styles slot
	 * @param bool $withResolvedFile Whether the RepoGroup mock should return a resolved file
	 */
	private function makeWiki( string $styles, bool $withResolvedFile = true ): Wiki {
		$services = $this->getServiceContainer();
		$config = $this->createMock( \MediaWiki\Config\Config::class );
		$config->method( 'get' )->willReturn( [] );

		return new Wiki(
			$config,
			$this->makePDFCreatorUtil(),
			$this->makeRevisionLookup( $styles ),
			$services->getTitleFactory(),
			$this->makeRepoGroup( $withResolvedFile )
		);
	}

	private function makePDFCreatorUtil(): PDFCreatorUtil {
		$templateTitle = $this->createMock( \MediaWiki\Title\Title::class );
		$templateTitle->method( 'getLatestRevID' )->willReturn( 1 );

		$util = $this->createMock( PDFCreatorUtil::class );
		$util->templatePrefix = 'pdfcreator_template_';
		$util->slots = [ 'header', 'body', 'footer', 'intro', 'outro', 'styles', 'options' ];
		$util->method( 'createPDFTemplateTitle' )->willReturn( $templateTitle );

		return $util;
	}

	private function makeRevisionLookup( string $styles ): RevisionLookup {
		$revision = $this->createMock( RevisionRecord::class );
		$revision->method( 'getContent' )->willReturnCallback(
			function ( string $slot ) use ( $styles ) {
				$text = ( $slot === 'pdfcreator_template_styles' ) ? $styles : '';
				$content = $this->createMock( TextContent::class );
				$content->method( 'getText' )->willReturn( $text );
				return $content;
			}
		);

		$lookup = $this->createMock( RevisionLookup::class );
		$lookup->method( 'getRevisionByTitle' )->willReturn( $revision );

		return $lookup;
	}

	private function makeRepoGroup( bool $withFile ): RepoGroup {
		$repoGroup = $this->createMock( RepoGroup::class );

		if ( $withFile ) {
			$file = $this->createMock( File::class );
			$file->method( 'exists' )->willReturn( true );
			$file->method( 'getName' )->willReturn( self::FONT_FILENAME );
			$file->method( 'getLocalRefPath' )->willReturn( self::FONT_ABS_PATH );
			$repoGroup->method( 'findFile' )->willReturn( $file );
		} else {
			$repoGroup->method( 'findFile' )->willReturn( null );
		}

		return $repoGroup;
	}
}
