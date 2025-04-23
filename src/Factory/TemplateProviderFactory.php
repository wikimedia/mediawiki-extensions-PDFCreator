<?php

namespace MediaWiki\Extension\PDFCreator\Factory;

use MediaWiki\Config\Config;
use MediaWiki\Extension\PDFCreator\ITemplateProvider;
use MediaWiki\Registration\ExtensionRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Wikimedia\ObjectFactory\ObjectFactory;

class TemplateProviderFactory implements LoggerAwareInterface {

	/** @var LoggerInterface */
	private $logger;

	/** @var Config */
	private $config;

	/** @var ObjectFactory */
	private $objectFactory;

	/**
	 * @param Config $config
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct( Config $config, ObjectFactory $objectFactory ) {
		$this->config = $config;
		$this->objectFactory = $objectFactory;
	}

	/**
	 * @param string $name
	 * @return ITemplateProvider|null
	 */
	public function getTemplateProvider( string $name = '' ): ?ITemplateProvider {
		$registry = ExtensionRegistry::getInstance()->getAttribute(
			'PDFCreatorTemplateProvider'
		);

		// TODO: Is PDFCreatorTemplateProvider requried?
		if ( $name === '' ) {
			// use configured template
			$name = $this->config->get( 'PDFCreatorTemplateProvider' );
			if ( !isset( $registry[$name] ) ) {
				$this->logger->error(
					"PDFCreator: Can not find configured template provider named \"$name\"",
					[]
				);
				return null;
			}
		} else {
			// use injected template
			if ( !isset( $registry[$name] ) ) {
				$this->logger->error(
					"PDFCreator: Can not find injected template provider named \"$name\"",
					[]
				);

				// fallback to configured template
				$name = $this->config->get( 'PDFCreatorTemplateProvider' );
				if ( !isset( $registry[$name] ) ) {
					$this->logger->error(
						"PDFCreator: Can not find configured template provider named \"$name\"",
						[]
					);
					return null;
				}
			}
		}

		$spec = $registry[$name];
		$provider = $this->objectFactory->createObject( $spec, [] );

		if ( $provider instanceof ITemplateProvider === false ) {
			$this->logger->error(
				"PDFCreator: Template provider named \"$name\" is not valid",
				[]
			);
			return null;
		}

		return $provider;
	}

	/**
	 * @param LoggerInterface $logger
	 * @return void
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}
}
