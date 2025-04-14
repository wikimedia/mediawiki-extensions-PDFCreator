<?php

namespace MediaWiki\Extension\PDFCreator\PageParams;

use MediaWiki\Extension\PDFCreator\IPageParamsProvider;
use MediaWiki\Extension\PDFCreator\Utility\ParamDesc;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\User\UserIdentity;

class Title implements IPageParamsProvider {

	/**
	 * @inheritDoc
	 */
	public function execute( ?PageIdentity $pageIdentity, ?UserIdentity $userIdentity ): array {
		// This param is set in the html provider because it can be overwritten by a given label
		return [];
	}

	/**
	 * @return ParamDesc[]
	 */
	public function getParamsDescription(): array {
		return [
			new ParamDesc(
				'title',
				Message::newFromKey( 'pdfcreator-pageparam-title' )
			)
		];
	}
}
