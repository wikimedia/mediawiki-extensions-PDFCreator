<?php

namespace MediaWiki\Extension\PDFCreator\Factory;

use MediaWiki\Config\Config;
use MediaWiki\Extension\PDFCreator\Utility\BoolValueGet;
use MediaWiki\Extension\PDFCreator\Utility\PageSpec;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\PageProps;
use MediaWiki\Page\RedirectLookup;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class PageSpecFactory implements LoggerAwareInterface {

	/** @var LoggerInterface */
	private $logger = null;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var RedirectLookup */
	private $redirectLookup;

	/** @var PageProps */
	private $pageProps;

	/** @var Config */
	private $config;

	/**
	 * @param TitleFactory $titleFactory
	 * @param RedirectLookup $redirectLookup
	 * @param PageProps $pageProps
	 * @param Config $config
	 */
	public function __construct(
		TitleFactory $titleFactory, RedirectLookup $redirectLookup,
		PageProps $pageProps, Config $config
	) {
		$this->titleFactory = $titleFactory;
		$this->redirectLookup = $redirectLookup;
		$this->pageProps = $pageProps;
		$this->config = $config;
	}

	/**
	 * @param LoggerInterface $logger
	 * @return void
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @param string $type
	 * @param string $label
	 * @param string|null $target
	 * @param int|null $revId
	 * @param array $params
	 * @return PageSpec
	 */
	public function new(
		string $type, string $label, ?string $target = null,
		?int $revId = null, array $params = []
	): PageSpec {
		return new PageSpec( $type, $label, $target, $revId, $params );
	}

	/**
	 * Value from export specification:
	 * Example:
	 * [
	 *      'type' => '',
	 *      'target' => '',
	 *      'label' => '',
	 *      'rev-id' => '',
	 * ]
	 *
	 * @param array $data
	 * @param array $options
	 * @return PageSpec|null
	 */
	public function newFromSpec( array $data, array $options ): ?PageSpec {
		if ( !isset( $data['params'] ) || $data['params'] === '' ) {
			$params = [];
		} else {
			$params = $data['params'];
		}

		if ( !isset( $data['type'] ) ) {
			$type = '';
		} else {
			$type = $data['type'];
		}

		if ( !isset( $data['target'] ) || $data['target'] === '' ) {
			$target = null;
		} else {
			$title = $this->titleFactory->newFromDBKey( $data['target'] );
			if ( !$title ) {
				$target = null;
			}
			$title = $this->getRedirectTarget( $title, $options );
			$target = $title->getPrefixedDBkey();
		}

		$revId = null;
		if ( isset( $data['rev-id'] ) && is_numeric( $data['rev-id'] ) ) {
			$revId = $data['rev-id'];
		}

		$label = '';
		if ( isset( $data['label'] ) && $data['label'] !== '' ) {
			$label = $data['label'];
		} elseif ( $title instanceof Title ) {
			$label = $this->getLabelFromTitle( $title, $options );
		}
		if ( $label === '' ) {
			return null;
		}

		return new PageSpec( $type, $label, $target, $revId, $params );
	}

	/**
	 * @param Title $title
	 * @param array $options
	 * @return string
	 */
	private function getLabelFromTitle( Title $title, array $options ): string {
		$props = $this->pageProps->getProperties( $title, 'displaytitle' );
		$id = $title->getId();
		if ( isset( $props[$id] ) ) {
			return $props[$id];
		}
		if ( $this->doSuppressNamespace( $options ) ) {
			return $title->getText();
		}
		return $title->getPrefixedText();
	}

	/**
	 * @param array $options
	 * @return bool
	 */
	private function doSuppressNamespace( array $options ): bool {
		if ( isset( $options['suppress-namespace'] ) ) {
			return BoolValueGet::from( $options['suppress-namespace'] );
		}
		return $this->config->get( 'PDFCreatorSuppressNamespace' );
	}

	/**
	 * @param PageIdentity $title
	 * @param array $options
	 * @return title|null
	 */
	private function getRedirectTarget( PageIdentity $title, array $options ): ?title {
		if ( isset( $options['no-redirect'] ) && BoolValueGet::from( $options['no-redirect'] ) ) {
			return $title;
		}
		$redirectTarget = $this->redirectLookup->getRedirectTarget( $title );
		if ( $redirectTarget instanceof LinkTarget ) {
			$title = $this->titleFactory->newFromLinkTarget( $redirectTarget );
		}
		return $title;
	}
}
