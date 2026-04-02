<?php

namespace MediaWiki\Extension\PDFCreator\MediaWiki\Hook;

use BlueSpice\Discovery\Hook\BlueSpiceDiscoveryTemplateDataProviderAfterInit;
use BlueSpice\Discovery\ITemplateDataProvider;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\NamespaceInfo;
use SkinTemplate;

class AddExportAction implements
	SkinTemplateNavigation__UniversalHook,
	BlueSpiceDiscoveryTemplateDataProviderAfterInit
	{

	/**
	 *
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
	 * // phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
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
	 *
	 * @param ITemplateDataProvider $registry
	 * @return void
	 */
	public function onBlueSpiceDiscoveryTemplateDataProviderAfterInit( $registry ): void {
		$registry->register( 'panel/share', 'pdfcreator-export-page' );
	}
}
