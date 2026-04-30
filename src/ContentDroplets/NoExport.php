<?php

namespace MediaWiki\Extension\PDFCreator\ContentDroplets;

use MediaWiki\Extension\ContentDroplets\Droplet\GenericDroplet;

class NoExport extends GenericDroplet {

	public function __construct() {
		parent::__construct(
			name: 'pdfcreator-droplet-no-export-name',
			description: 'pdfcreator-droplet-no-export-description',
			icon: 'droplet-pdf-no-export',
			content: '',
			categories: [ 'export' ],
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getVeCommand(): ?string {
		return 'PDFExcludeCommand';
	}
}
