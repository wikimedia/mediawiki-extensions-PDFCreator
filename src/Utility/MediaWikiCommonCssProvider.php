<?php

namespace MediaWiki\Extension\PDFCreator\Utility;

use MediaWiki\Content\CssContent;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\TitleFactory;

class MediaWikiCommonCssProvider {

	/**
	 * @param TitleFactory $titleFactory
	 * @param RevisionLookup $revisionLookup
	 */
	public function __construct(
		private readonly TitleFactory $titleFactory,
		private readonly RevisionLookup $revisionLookup ) {
	}

	/**
	 * @return string
	 */
	public function getStyles(): string {
		$title = $this->titleFactory->newFromText( 'Common.css', NS_MEDIAWIKI );
		$revision = $this->revisionLookup->getRevisionByTitle( $title );
		if ( !$revision ) {
			return '';
		}

		$css = '';
		$content = $revision->getContent( SlotRecord::MAIN );
		if ( $content instanceof CssContent ) {
			$css = $content->getText();
		}

		return $this->sanitizeCSS( $css );
	}

	/**
	 * ERM43013
	 *
	 * Removes font face declarations from common css because
	 * absolute urls in font face breaks the pdf service.
	 *
	 * To use custom fonts in pdf export use css in pdf templates
	 *
	 * @param string $css
	 *
	 * @return string
	 */
	private function sanitizeCSS( string $css ): string {
		return preg_replace( '/@font-face\s*{.*?}/si', '', $css );
	}
}
