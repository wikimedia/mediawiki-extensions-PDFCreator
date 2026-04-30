<?php

namespace MediaWiki\Extension\PDFCreator\Tag;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\GenericTagHandler\ClientTagSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;
use MWStake\MediaWiki\Component\GenericTagHandler\WrapperTag;

class PDFExcludeTag extends WrapperTag {

	/**
	 * @return string[]
	 */
	public function getTagNames(): array {
		return [ 'pdf-exclude' ];
	}

	/**
	 * @param MediaWikiServices $services
	 * @return ITagHandler
	 */
	public function getHandler( MediaWikiServices $services ): ITagHandler {
		return new PDFExcludeHandler();
	}

	/**
	 * @return bool
	 */
	public function shouldParseInput(): bool {
		return true;
	}

	/**
	 * @return array|null
	 */
	public function getParamDefinition(): ?array {
		return null;
	}

	public function getClientTagSpecification(): ClientTagSpecification|null {
		return new ClientTagSpecification(
			classname: 'PDFExclude',
			description: Message::newFromKey( 'pdfcreator-exclude-export-tool-title' ),
			formSpecification: null,
			menuMessage: Message::newFromKey( 'pdfcreator-exclude-export-tool-title' ),
			icon: 'close'
		);
	}

	/**
	 * @return string|null
	 */
	public function getContainerElementName(): ?string {
		return 'div';
	}

	/**
	 * @return string[]|null
	 */
	public function getResourceLoaderModules(): ?array {
		return null;
	}
}
