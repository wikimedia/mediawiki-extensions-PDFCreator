<?php

namespace MediaWiki\Extension\PDFCreator\Processor;

use MediaWiki\Extension\PDFCreator\IProcessor;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Extension\PDFCreator\Utility\LinkUpdater;
use MediaWiki\Extension\PDFCreator\Utility\PageLinker;
use MediaWiki\Title\TitleFactory;

class PageLinkerProcessor implements IProcessor {

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param ExportPage[] &$pages
	 * @param array &$images
	 * @param array &$attachments
	 * @param ExportContext|null $context
	 * @param string $module
	 * @param array $params
	 * @return void
	 */
	public function execute(
		array &$pages, array &$images, array &$attachments,
		?ExportContext $context = null, string $module = '', $params = []
	): void {
		$pageLinker = new PageLinker( $this->titleFactory );
		$pages = $pageLinker->execute( $pages );

		$linkUpdater = new LinkUpdater( $this->titleFactory );
		$pages = $linkUpdater->execute( $pages );
	}

	/**
	 * @inheritDoc
	 */
	public function getPosition(): int {
		return 80;
	}
}
