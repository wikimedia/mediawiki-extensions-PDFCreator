<?php

namespace MediaWiki\Extension\PDFCreator\TemplateProvider;

use MediaWiki\Config\Config;
use MediaWiki\Extension\PDFCreator\ITemplateProvider;
use MediaWiki\Extension\PDFCreator\PDFCreatorUtil;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Extension\PDFCreator\Utility\LessVarsReplacer;
use MediaWiki\Extension\PDFCreator\Utility\Template;
use MediaWiki\Extension\PDFCreator\Utility\TemplateResources;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\TitleFactory;
use RepoGroup;

class Wiki implements ITemplateProvider {

	/** @var Config */
	private $config;

	/** @var PDFCreatorUtil */
	private $util;

	/** @var RevisionLookup */
	private $revisionLookup;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var RepoGroup */
	private $repoGroup;

	/** @var array */
	private $templateContent = [];

	/**
	 * @param Config $config
	 * @param PDFCreatorUtil $util
	 * @param RevisionLookup $revisionLookup
	 * @param TitleFactory $titleFactory
	 * @param RepoGroup $repoGroup
	 */
	public function __construct(
		Config $config,
		PDFCreatorUtil $util,
		RevisionLookup $revisionLookup,
		TitleFactory $titleFactory,
		RepoGroup $repoGroup
	) {
		$this->config = $config;
		$this->util = $util;
		$this->revisionLookup = $revisionLookup;
		$this->titleFactory = $titleFactory;
		$this->repoGroup = $repoGroup;
	}

	/**
	 * @return array
	 */
	public function getTemplateNames(): array {
		$templateNames = $this->util->getAllWikiTemplates();

		return $templateNames;
	}

	/**
	 * @param ExportContext $context
	 * @param string $name
	 *
	 * @return Template|null
	 */
	public function getTemplate( ExportContext $context, string $name = '' ): ?Template {
		$templateTitle = $this->util->createPDFTemplateTitle( $name );
		$revId = $templateTitle->getLatestRevID();

		$revision = $this->revisionLookup->getRevisionByTitle( $templateTitle, $revId );
		$this->templateContent = [];
		foreach ( $this->util->slots as $slot ) {
			$content = $revision->getContent( $this->util->templatePrefix . $slot );
			$this->templateContent[$slot][] = $content;
		}

		$intro = isset( $this->templateContent['intro'][0] ) ? $this->templateContent['intro'][0]->getText() : '';
		$outro = isset( $this->templateContent['outro'][0] ) ? $this->templateContent['outro'][0]->getText() : '';
		$header = isset( $this->templateContent['header'][0] ) ? $this->templateContent['header'][0]->getText() : '';
		$footer = isset( $this->templateContent['footer'][0] ) ? $this->templateContent['footer'][0]->getText() : '';
		$body = isset( $this->templateContent['body'][0] ) ? $this->templateContent['body'][0]->getText() : '';
		$optionsJson = isset( $this->templateContent['options'][0] ) ? $this->templateContent['options'][0]->getText()
			: ''; // phpcs:ignore Generic.Files.LineLength.TooLong
		$options = json_decode( $optionsJson, true );

		$template = new Template(
			$body, $header, $footer, $intro, $outro, $this->getResources( $name ), [], $options ? $options : []
		);

		return $template;
	}

	/**
	 * @param string $name
	 *
	 * @return TemplateResources
	 */
	private function getResources( string $name ): TemplateResources {
		$styles = isset( $this->templateContent['styles'][0] ) ? $this->templateContent['styles'][0]->getText() : '';
		$lessVarReplacer = new LessVarsReplacer();
		$styles = $lessVarReplacer->replaceLessVars( $styles );
		$imagePaths = [];
		$logos = $this->config->get( 'Logos' );
		if ( isset( $logos['1x'] ) ) {
			$logoUrl = $logos['1x'];
			$logoPathParts = explode( '/', $logoUrl );
			$count = count( $logoPathParts );
			$logoName = $logoPathParts[$count - 1];
			$fileTitle = $this->titleFactory->newFromText( $logoName, NS_FILE );
			if ( !$fileTitle->exists() ) {
				$scriptPath = $this->config->get( 'ScriptPath' );
				$imagePath = str_replace( $scriptPath, '', $logoUrl );
				$url = MW_INSTALL_PATH . $imagePath;
				$imagePaths[$logoName] = $url;
			}
		}

		$commonDir = MW_INSTALL_PATH . '/extensions/PDFCreator/data/common';

		$defaultFontPaths = [
			'DejaVuSans-Bold.ttf' => $commonDir . '/fonts/DejaVuSans-Bold.ttf',
			'DejaVuSans-BoldOblique.ttf' => $commonDir . '/fonts/DejaVuSans-BoldOblique.ttf',
			'DejaVuSans-Oblique.ttf' => $commonDir . '/fonts/DejaVuSans-Oblique.ttf',
			'DejaVuSans.ttf' => $commonDir . '/fonts/DejaVuSans.ttf',
			'DejaVuSansMono-Bold.ttf' => $commonDir . '/fonts/DejaVuSansMono-Bold.ttf',
			'DejaVuSansMono-BoldOblique.ttf' => $commonDir . '/fonts/DejaVuSansMono-BoldOblique.ttf',
			'DejaVuSansMono-Oblique.ttf' => $commonDir . '/fonts/DejaVuSansMono-Oblique.ttf',
			'DejaVuSansMono.ttf' => $commonDir . '/fonts/DejaVuSansMono.ttf',
		];

		$customFontPaths = $this->findCustomFontPaths( $styles );
		if ( $customFontPaths ) {
			$styles = $this->rewriteFontUrls( $styles, $customFontPaths );
		}
		$styleBlocks = ( $styles !== '' ) ? [ $name => $styles ] : [];

		return new TemplateResources(
			array_merge( $defaultFontPaths, $customFontPaths ), [
				'mediawiki.css' => $commonDir . '/stylesheets/mediawiki.css',
				'geshi-php.css' => $commonDir . '/stylesheets/geshi-php.css',
				'bluespice.css' => $commonDir . '/stylesheets/bluespice.css',
				'tables.css' => $commonDir . '/stylesheets/tables.css',
				'fonts.css' => $commonDir . '/stylesheets/fonts.css',
				'mediawiki.action.history.diff.css' => $commonDir . '/stylesheets/mediawiki.action.history.diff.css',
				'page.css' => $commonDir . '/stylesheets/page.css',
			], $styleBlocks, $imagePaths
		);
	}

	/**
	 * Find font files referenced via @font-face src URLs in the given CSS and
	 * resolve them to absolute filesystem paths via the file repository.
	 *
	 * Only URLs routed through MediaWiki's image auth scripts
	 * (img_auth.php / nsfr_img_auth.php) are handled; the font filename is
	 * derived from the last path segment, e.g.:
	 *   nsfr_img_auth.php/0/04/ImperialScript-Regular.ttf → ImperialScript-Regular.ttf
	 *
	 * @param string $styles CSS text to scan
	 *
	 * @return array [ filename => absPath ]
	 */
	private function findCustomFontPaths( string $styles ): array {
		if ( $styles === '' ) {
			return [];
		}

		$fontPaths = [];

		if ( !preg_match_all( '/@font-face\s*\{([^}]+)\}/i', $styles, $blocks ) ) {
			return $fontPaths;
		}

		foreach ( $blocks[1] as $block ) {
			if ( !preg_match( '/src\s*:\s*([^;]+)/i', $block, $srcMatch ) ) {
				continue;
			}

			preg_match_all( '/url\(\s*[\'"]?([^\'")\s]+)[\'"]?\s*\)/i', $srcMatch[1], $urlMatches );

			foreach ( $urlMatches[1] as $url ) {
				if ( !str_contains( $url, 'img_auth.php' ) ) {
					continue;
				}

				$parts = parse_url( urldecode( $url ) );
				$filename = basename( $parts['path'] ?? $url );

				if ( $filename === '' ) {
					continue;
				}

				$fileTitle = $this->titleFactory->newFromText( $filename, NS_FILE );
				if ( !$fileTitle ) {
					continue;
				}

				$file = $this->repoGroup->findFile( $fileTitle );
				if ( !$file || !$file->exists() ) {
					continue;
				}

				$absPath = $file->getLocalRefPath();
				if ( $absPath ) {
					$fontPaths[$file->getName()] = $absPath;
				}
			}
		}

		return $fontPaths;
	}

	/**
	 * Replace img_auth.php font URLs in @font-face blocks with the PDF-internal
	 * stylesheets/ path so the renderer can resolve the already-uploaded file.
	 *
	 * @param string $styles CSS text
	 * @param array $fontPaths [ filename => absPath ] from findCustomFontPaths()
	 *
	 * @return string Updated CSS text
	 */
	private function rewriteFontUrls( string $styles, array $fontPaths ): string {
		foreach ( array_keys( $fontPaths ) as $filename ) {
			$styles = preg_replace(
				'/url\(\s*[\'"]?[^\'")\s]*img_auth\.php[^\'")\s]*\/' . preg_quote( $filename, '/' ) . '[\'"]?\s*\)/i',
				'url(stylesheets/' . $filename . ')',
				$styles
			);
		}

		return $styles;
	}

}
