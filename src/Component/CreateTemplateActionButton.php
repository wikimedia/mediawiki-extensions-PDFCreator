<?php

namespace MediaWiki\Extension\PDFCreator\Component;

use MediaWiki\Context\IContextSource;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleLink;

class CreateTemplateActionButton extends SimpleLink {

	/**
	 * @param TitleFactory $titleFactory
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( private readonly TitleFactory $titleFactory,
		private readonly PermissionManager $permissionManager ) {
		return parent::__construct( [] );
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'create-template-btn';
	}

	/**
	 * @inheritDoc
	 */
	public function getSubComponents(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getClasses(): array {
		return [ 'new-template-action', 'ico-btn', 'bi-bs-create-page' ];
	}

	/**
	 * @inheritDoc
	 */
	public function getRole(): string {
		return 'button';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'pdfcreator-action-create-template-title' );
	}

	/**
	 * @inheritDoc
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'pdfcreator-action-create-template-text' );
	}

	/**
	 * @inheritDoc
	 */
	public function getHref(): string {
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function shouldRender( IContextSource $context ): bool {
		$user = $context->getUser();

		$title = $this->titleFactory->newFromText( 'PDFCreator', NS_MEDIAWIKI );
		$userCan = $this->permissionManager->userCan( 'edit', $user, $title );
		if ( $userCan ) {
			return true;
		}
		return false;
	}
}
