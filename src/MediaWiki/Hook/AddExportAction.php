<?php

namespace MediaWiki\Extension\PDFCreator\MediaWiki\Hook;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\NamespaceInfo;
use SkinTemplate;

class AddExportAction implements SkinTemplateNavigation__UniversalHook {

	/**
	 * @param PermissionManager $permissionManager
	 * @param NamespaceInfo $namespaceInfo
	 */
	public function __construct( private readonly PermissionManager $permissionManager,
		private readonly NamespaceInfo $namespaceInfo ) {
	}

	/**
	 * @param SkinTemplate $sktemplate
	 * @return bool
	 */
	protected function skipProcessing( SkinTemplate $sktemplate ) {
		if ( !is_a( $sktemplate->getSkin(), 'SkinBlueSpiceEclipseSkin', true ) ) {
			return true;
		}
		$title = $sktemplate->getTitle();

		if ( !$title ) {
			return true;
		}
		if ( $title->getArticleId() < 1 ) {
			return true;
		}
		if ( !$this->namespaceInfo->isContent( $title->getNamespace() ) ) {
			return true;
		}
		if ( !$this->permissionManager->userCan( 'read', $sktemplate->getUser(), $title ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void { // phpcs:ignore MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName, Generic.Files.LineLength.TooLong
		if ( $this->skipProcessing( $sktemplate ) ) {
			return;
		}
		$links['actions']['pdfcreator-export-page'] = [
			'text' => $sktemplate->msg( 'pdfcreator-export-action-text' )->text(),
			'title' => $sktemplate->msg( 'pdfcreator-dialog-button-title' )->text(),
			'href' => '',
			'class' => 'pdfcreator-export-page',
			'id' => 'pdfcreator-export-page',
			'position' => 1,
		];

		$sktemplate->getOutput()->addModules( 'ext.pdfcreator.export' );
	}

	/**
	 * @param \BlueSpice\Discovery\ITemplateDataProvider $registry
	 * @return void
	 */
	public function onBlueSpiceDiscoveryTemplateDataProviderAfterInit( $registry ): void {
		$registry->register( 'panel/share', 'pdfcreator-export-page' );
	}
}
