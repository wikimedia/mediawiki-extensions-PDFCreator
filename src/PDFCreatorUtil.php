<?php

namespace MediaWiki\Extension\PDFCreator;

use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;

class PDFCreatorUtil {

	/** @var string */
	public $templatePrefix = 'pdfcreator_template_';

	/** @var array */
	public $slots = [
		'header',
		'body',
		'footer',
		'intro',
		'outro',
		'styles',
		'options'
	];

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 *
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
	}

	/**
	 *
	 * @return array
	 */
	public function getAllWikiTemplates() {
		$pdfCreatorTitle = $this->titleFactory->newFromText(
			'PDFCreator',
			NS_MEDIAWIKI
		);
		$subpages = $pdfCreatorTitle->getSubpages();
		$templates = [];
		foreach ( $subpages as $subpage ) {
			$isTemplate = $this->isPDFTemplateTitle( $subpage );
			if ( !$isTemplate ) {
				continue;
			}
			$templates[] = $subpage->getSubpageText();
		}
		return $templates;
	}

	/**
	 *
	 * @param Title $title
	 * @return bool
	 */
	public function isPDFTemplateTitle( $title ) {
		$titleNS = $title->getNamespace();
		if ( $titleNS !== NS_MEDIAWIKI ) {
			return false;
		}
		$titleParts = explode( '/', $title->getText() );
		if ( $titleParts[0] !== 'PDFCreator' ) {
			return false;
		}
		if ( count( $titleParts ) !== 2 ) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * @param string $pageName
	 * @return Title
	 */
	public function createPDFTemplateTitle( $pageName ) {
		$title = $this->titleFactory->newFromText(
			'PDFCreator/' . $pageName,
			NS_MEDIAWIKI
		);

		if ( !$title ) {

		}
		return $title;
	}

	/**
	 *
	 * @return string
	 */
	public function buildTabPanelSkeleton() {
		$html = Html::openElement( 'div', [ 'id' => 'pdf-creator-skeleton-cnt' ] );
		$html .= Html::element( 'div', [ 'class' => 'pdf-creator-skeleton text' ] );
		// Tab panel header
		$html .= Html::openElement( 'div', [ 'class' => 'pdf-creator-skeleton tabheader' ] );
		for ( $i = 0; $i <= 3; $i++ ) {
			$html .= Html::element( 'div', [ 'class' => 'pdf-creator-skeleton text' ] );
		}
		$html .= Html::closeElement( 'div' );
		// Tab panel content
		$html .= Html::openElement( 'div', [ 'class' => 'pdf-creator-skeleton tabcontent' ] );
		for ( $i = 0; $i <= 7; $i++ ) {
			$html .= Html::element( 'div', [ 'class' => 'pdf-creator-skeleton text' ] );
		}
		$html .= Html::closeElement( 'div' );
		$html .= Html::closeElement( 'div' );

		return $html;
	}

}
