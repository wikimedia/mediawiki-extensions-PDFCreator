<?php

namespace MediaWiki\Extension\PDFCreator\MediaWiki\Hook;

use MediaWiki\Extension\PDFCreator\Tag\PDFExcludeTag;
use MWStake\MediaWiki\Component\GenericTagHandler\Hook\MWStakeGenericTagHandlerInitTagsHook;

class RegisterTags implements MWStakeGenericTagHandlerInitTagsHook {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeGenericTagHandlerInitTags( array &$tags ) {
		$tags[] = new PDFExcludeTag();
	}
}
