<?php

namespace MediaWiki\Extension\PDFCreator\Module;

use MediaWiki\Config\Config;
use MediaWiki\Extension\PDFCreator\Factory\ExportBackendFactory;
use MediaWiki\Extension\PDFCreator\Factory\ExportPageFactory;
use MediaWiki\Extension\PDFCreator\Factory\ExportPostProcessorFactory;
use MediaWiki\Extension\PDFCreator\Factory\ExportPreProcessorFactory;
use MediaWiki\Extension\PDFCreator\Factory\ExportProcessorFactory;
use MediaWiki\Extension\PDFCreator\Factory\ExportTargetFactory;
use MediaWiki\Extension\PDFCreator\Factory\MetaDataFactory;
use MediaWiki\Extension\PDFCreator\Factory\PageSpecFactory;
use MediaWiki\Extension\PDFCreator\Factory\StyleBlocksFactory;
use MediaWiki\Extension\PDFCreator\Factory\StylesheetsFactory;
use MediaWiki\Extension\PDFCreator\Factory\TemplateProviderFactory;
use MediaWiki\Extension\PDFCreator\IExportBackend;
use MediaWiki\Extension\PDFCreator\IExportModule;
use MediaWiki\Extension\PDFCreator\IExportStatus;
use MediaWiki\Extension\PDFCreator\IExportTarget;
use MediaWiki\Extension\PDFCreator\ITargetResult;
use MediaWiki\Extension\PDFCreator\ITemplateProvider;
use MediaWiki\Extension\PDFCreator\Utility\BookmarksXMLBuilder;
use MediaWiki\Extension\PDFCreator\Utility\BoolValueGet;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Extension\PDFCreator\Utility\ExportHtmlBuilder;
use MediaWiki\Extension\PDFCreator\Utility\ExportPage;
use MediaWiki\Extension\PDFCreator\Utility\ExportResources;
use MediaWiki\Extension\PDFCreator\Utility\ExportResult;
use MediaWiki\Extension\PDFCreator\Utility\ExportSpecification;
use MediaWiki\Extension\PDFCreator\Utility\ExportStatus;
use MediaWiki\Extension\PDFCreator\Utility\MediaWikiCommonCssProvider;
use MediaWiki\Extension\PDFCreator\Utility\PageSpec;
use MediaWiki\Extension\PDFCreator\Utility\Template;
use MediaWiki\Extension\PDFCreator\Utility\TemplateResources;
use MediaWiki\Extension\PDFCreator\Utility\TocBuilder;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Page\PageProps;
use MediaWiki\Page\RedirectLookup;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class Batch implements IExportModule, LoggerAwareInterface {

	/** @var LoggerInterface */
	protected $logger;

	/** @var ExportTargetFactory */
	protected $exportTargetFactory;

	/** @var ExportBackendFactory */
	protected $exportBackendFactory;

	/** @var TemplateProviderFactory */
	protected $templateProviderFactory;

	/** @var PageSpecFactory */
	protected $pageSpecFactory;

	/** @var ExportPageFactory */
	protected $exportPageFactory;

	/** @var MetaDataFactory */
	protected $metaDataFactory;

	/** @var ExportHtmlBuilder */
	protected $exportHtmlBuilder;

	/** @var ExportPreProcessorFactory */
	protected $exportPreProcessorFactory;

	/** @var ExportProcessorFactory */
	protected $exportProcessorFactory;

	/** @var ExportPostProcessorFactory */
	protected $exportPostProcessorFactory;

	/** @var StylesheetsFactory */
	protected $stylesheetsFactory;

	/** @var StyleBlocksFactory */
	protected $styleBlocksFactory;

	/** @var MediaWikiCommonCssProvider */
	protected $mediaWikiCommonCssProvider;

	/** @var Config */
	protected $config;

	/** @var TitleFactory */
	protected $titleFactory;

	/** @var PageProps */
	protected $pageProps;

	/** @var RedirectLookup */
	protected $redirectLookup;

	/** @var string */
	protected $docTitle;

	/**
	 * @param PageSpecFactory $pageSpecFactory
	 * @param ExportPageFactory $exportPageFactory
	 * @param TemplateProviderFactory $templateProviderFactory
	 * @param ExportBackendFactory $exportBackendFactory
	 * @param ExportTargetFactory $exportTargetFactory
	 * @param MetaDataFactory $metaDataFactory
	 * @param ExportHtmlBuilder $exportHtmlBuilder
	 * @param ExportPreProcessorFactory $exportPreProcessorFactory
	 * @param ExportProcessorFactory $exportProcessorFactory
	 * @param ExportPostProcessorFactory $exportPostProcessorFactory
	 * @param StylesheetsFactory $stylesheetsFactory
	 * @param StyleBlocksFactory $styleBlocksFactory
	 * @param MediaWikiCommonCssProvider $mediaWikiCommonCssProvider
	 * @param Config $config
	 * @param TitleFactory $titleFactory
	 * @param RedirectLookup $redirectLookup
	 */
	public function __construct(
		PageSpecFactory $pageSpecFactory, ExportPageFactory $exportPageFactory,
		TemplateProviderFactory $templateProviderFactory, ExportBackendFactory $exportBackendFactory,
		ExportTargetFactory $exportTargetFactory, MetaDataFactory $metaDataFactory,
		ExportHtmlBuilder $exportHtmlBuilder, ExportPreProcessorFactory $exportPreProcessorFactory,
		ExportProcessorFactory $exportProcessorFactory, ExportPostProcessorFactory $exportPostProcessorFactory,
		StylesheetsFactory $stylesheetsFactory, StyleBlocksFactory $styleBlocksFactory,
		MediaWikiCommonCssProvider $mediaWikiCommonCssProvider,	Config $config,
		TitleFactory $titleFactory, RedirectLookup $redirectLookup
	) {
		$this->pageSpecFactory = $pageSpecFactory;
		$this->exportPageFactory = $exportPageFactory;
		$this->templateProviderFactory = $templateProviderFactory;
		$this->exportBackendFactory = $exportBackendFactory;
		$this->exportTargetFactory = $exportTargetFactory;
		$this->metaDataFactory = $metaDataFactory;
		$this->exportHtmlBuilder = $exportHtmlBuilder;
		$this->exportPreProcessorFactory = $exportPreProcessorFactory;
		$this->exportProcessorFactory = $exportProcessorFactory;
		$this->exportPostProcessorFactory = $exportPostProcessorFactory;
		$this->stylesheetsFactory = $stylesheetsFactory;
		$this->styleBlocksFactory = $styleBlocksFactory;
		$this->mediaWikiCommonCssProvider = $mediaWikiCommonCssProvider;
		$this->config = $config;
		$this->titleFactory = $titleFactory;
		$this->redirectLookup = $redirectLookup;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'batch';
	}

	/**
	 * @param ExportSpecification $specification
	 * @param ExportContext $context
	 * @return IExportStatus
	 */
	public function execute( ExportSpecification $specification, ExportContext $context ): ExportResult {
		// relevant title
		$relevantTitle = $this->getRelevantTitle( $specification->getPageSpecs(), $context );
		if ( !$relevantTitle ) {
			$exportStatus = new ExportStatus( false, 'Invalid relevant title' );
			return $this->getExportResult( $exportStatus );
		}
		if ( $context->getPageIdentity() === null ) {
			$context = new ExportContext( $context->getUserIdentity(), $relevantTitle );
		}

		/** workspace for temporary files */
		$workspace = $this->getWorkspace( $relevantTitle->getPrefixedDBkey() );

		$template = $this->getTemplate( $specification, $context );
		if ( $template instanceof Template === false ) {
			$this->logger->error( 'Invalid template' );

			$exportStatus = new ExportStatus( false, 'Invalid template' );
			return $this->getExportResult( $exportStatus );
		}

		$templateResources = $template->getResources();
		$stylesheets = $this->getStylesheets( $templateResources, $context );
		$styleblocks = $this->getStyleblocks( $templateResources, $context );
		$images = $templateResources->getImagePaths();
		$attachments = [];

		$optionParams = array_merge(
			$template->getOptions(),
			$specification->getOptions()
		);

		// prepare pages
		/** @var ExportPage[] */
		$pages = $this->getPages(
			$specification->getPageSpecs(), $optionParams, $template, $context, $workspace
		);

		// add toc page
		$embedPageToc = false;
		if ( isset( $config['embed-page-toc'] )
			&& BoolValueGet::from( $config['embed-page-toc'] ) === true
		) {
			$embedPageToc = true;
		}
		$this->addTocPage( $pages, $context, $embedPageToc );

		// add intro page
		if ( $template->getIntro() !== '' ) {
			$intro = new PageSpec( 'intro' );
			$introPage = $this->exportPageFactory->getPageFromSpec( $intro, $template, $context, $workspace );
			array_unshift( $pages, $introPage );
		}

		// add outro page
		if ( $template->getOutro() !== '' ) {
			$outro = new PageSpec( 'outro' );
			$outroPage = $this->exportPageFactory->getPageFromSpec( $outro, $template, $context, $workspace );
			$pages[] = $outroPage;
		}

		// process pages
		$this->preProcessPages(
			$specification->getModule(), $pages, $images, $attachments, $context, $optionParams
		);
		$this->processPages(
			$specification->getModule(), $pages, $images, $attachments, $context, $optionParams
		);

		$html = $this->getHtml( $pages, $stylesheets, $styleblocks, $context, $specification->getParams() );

		$this->postProcessHtml(
			$specification->getModule(), $html, $context, $optionParams
		);

		// do export
		$backend = $this->exportBackendFactory->getBackend( $specification->getBackend() );
		if ( $backend instanceof IExportBackend === false ) {
			$this->logger->error( 'Invalid export backend' );

			$exportStatus = new ExportStatus( false, 'Invalid backend' );
			return $this->getExportResult( $exportStatus );
		}

		$exportStylesheets = array_merge( $stylesheets, $templateResources->getFontPaths() );
		$exportResources = new ExportResources(
			$html, $exportStylesheets, $images, $attachments
		);

		$pdfData = $backend->create(
			$exportResources,
			$optionParams
		);

		$target = $this->exportTargetFactory->getExportTarget( $specification->getTarget() );
		if ( $target instanceof IExportTarget === false ) {
			$exportStatus = new ExportStatus( false, 'Invalid target' );
			return $this->getExportResult( $exportStatus );
		}

		$targetResult = $target->execute( $pdfData, $specification->getParams() );
		$status = rmdir( $workspace );

		$exportStatus = new ExportStatus( true );
		return $this->getExportResult( $exportStatus, $targetResult );
	}

	/**
	 * @param LoggerInterface $logger
	 * @return void
	 */
	public function setLogger( LoggerInterface $logger ): void {
		$this->logger = $logger;
	}

	/**
	 * @param ExportStatus $status
	 * @param ITargetResult|null $result
	 * @return ExportResult
	 */
	protected function getExportResult( ExportStatus $status, ?ITargetResult $result = null ): ExportResult {
		if ( !$status->isGood() ) {
			$this->logger->error( $status->getText() );
		}

		return new ExportResult( $status, $result );
	}

	/**
	 * @param PageSpec[] $pages
	 * @param ExportContext $context
	 * @return Title|null
	 */
	protected function getRelevantTitle( array $pages, ExportContext $context ): ?Title {
		$title = null;
		$label = '';
		if ( $context->getPageIdentity() !== null ) {
			$title = $this->titleFactory->newFromPageIdentity( $context->getPageIdentity() );
		} else {
			foreach ( $pages as $page ) {
				if ( !isset( $page['target'] ) ) {
					continue;
				}
				$title = $this->titleFactory->newFromDBkey( $page['target'] );
				if ( $title && $title->exists() ) {
					// Use the first existing title in array as relevant title
					if ( isset( $page['label'] ) ) {
						$label = $page['label'];
					}
					break;
				}
			}
		}

		if ( !$title ) {
			return null;
		}

		$redirectTarget = $this->redirectLookup->getRedirectTarget( $title );
		if ( $redirectTarget instanceof LinkTarget ) {
			$title = $this->titleFactory->newFromLinkTarget( $redirectTarget );
		}

		if ( $label !== '' ) {
			$this->docTitle = $label;
		} else {
			$this->docTitle = $title->getText();
		}

		return $title;
	}

	/**
	 * @param Title $relevantTitle
	 * @param array $params
	 * @return string
	 */
	protected function getDocumentTitle( Title $relevantTitle, array $params ): string {
		$docTitle = $relevantTitle->getPrefixedText();
		if ( isset( $params['title'] ) ) {
			$docTitle = $params['title'];
		}
		return $docTitle;
	}

	/**
	 * @param string $filename
	 * @return string
	 */
	protected function getWorkspace( string $filename ): string {
		$token = md5( wfTimestampNow() . $filename );
		$uploadDirectory = $this->config->get( 'UploadDirectory' );
		$workspace = $this->ensureFileSystemPath( "$uploadDirectory/cache/PDFCreator/$token" );
		return $workspace;
	}

	/**
	 * @param ExportSpecification $specification
	 * @param ExportContext $context
	 * @return Template|null
	 */
	protected function getTemplate( ExportSpecification $specification, ExportContext $context ): ?Template {
		$templateProvider = $this->templateProviderFactory->getTemplateProvider(
			$specification->getTemplateProvider()
		);

		if ( $templateProvider instanceof ITemplateProvider === false ) {
			// TODO: Add log
			// TODO: Throw exception
			return null;
		}

		$supportedTemplateNames = $templateProvider->getTemplateNames();
		if ( $specification->getTemplateName()
			&& in_array( $specification->getTemplateName(), $supportedTemplateNames )
		) {
			$name = $specification->getTemplateName();
		} elseif ( count( $supportedTemplateNames ) > 0 ) {
			$name = $supportedTemplateNames[0];
		} else {
			return null;
		}

		$template = $templateProvider->getTemplate( $context, $name );
		return $template;
	}

	/**
	 * @param array $pageSpecs
	 * @param array $options
	 * @param Template $template
	 * @param ExportContext $context
	 * @param string $workspace
	 * @return array
	 */
	protected function getPages( array $pageSpecs, array $options, Template $template,
		ExportContext $context, string $workspace ): array {
		$pageSpecs = $this->getPageSpecObjects( $pageSpecs, $options );

		$pages = [];
		/** @var PageSpec */
		foreach ( $pageSpecs as $pageSpec ) {
			$pages[] = $this->exportPageFactory->getPageFromSpec( $pageSpec, $template, $context, $workspace );
		}

		return $pages;
	}

	/**
	 * @param array $specs
	 * @param array $options
	 * @return array
	 */
	protected function getPageSpecObjects( array $specs, array $options ): array {
		$pageSpecs = [];
		foreach ( $specs as $spec ) {
			$pageSpec = $this->pageSpecFactory->newFromSpec( $spec, $options );
			if ( $pageSpec instanceof PageSpec === false ) {
				// TODO: Log issue
				continue;
			}
			$pageSpecs[] = $pageSpec;
		}
		return $pageSpecs;
	}

	/**
	 * @param string $module
	 * @param array &$pages
	 * @param array &$images
	 * @param array &$attachments
	 * @param ExportContext $context
	 * @param array $params
	 * @return void
	 */
	protected function preProcessPages(
		string $module, array &$pages, array &$images, array &$attachments, ExportContext $context, array $params = []
	): void {
		$preProcessors = $this->exportPreProcessorFactory->getProcessors( $module );
		foreach ( $preProcessors as $processor ) {
			$processor->execute( $pages, $images, $attachments, $context, $this->getName(), $params );
		}
	}

	/**
	 * @param string $module
	 * @param array &$pages
	 * @param array &$images
	 * @param array &$attachments
	 * @param ExportContext $context
	 * @param array $params
	 * @return void
	 */
	protected function processPages(
		string $module, array &$pages, array &$images, array &$attachments, ExportContext $context, array $params = []
	): void {
		$processors = $this->exportProcessorFactory->getProcessors( $module );
		foreach ( $processors as $processor ) {
			$processor->execute( $pages, $images, $attachments, $context, $this->getName(), $params );
		}
	}

	/**
	 * @param string $module
	 * @param string &$html
	 * @param ExportContext $context
	 * @param array $params
	 * @return void
	 */
	protected function postProcessHtml(
		string $module, string &$html, ExportContext $context, array $params = []
	): void {
		$postProcessors = $this->exportPostProcessorFactory->getProcessors( $module );
		foreach ( $postProcessors as $processor ) {
			$processor->execute( $html, $context, $this->getName(), $params );
		}
	}

	/**
	 * @param array $pages
	 * @param array $stylesheets
	 * @param array $styleblocks
	 * @param ExportContext $context
	 * @param array $params
	 * @return string
	 */
	protected function getHtml(
		array $pages, array $stylesheets, array $styleblocks, ExportContext $context, array $params
	): string {
		$meta = $this->getMeta( $context );
		$bookmarksXML = $this->getBookmarksXML( $pages, $context );
		$docTitle = $this->docTitle;
		if ( isset( $params['title'] ) ) {
			$docTitle = $params['title'];
		}
		$html = $this->exportHtmlBuilder->execute(
			$pages, $stylesheets, $styleblocks, $meta, $docTitle,
			$bookmarksXML, $this->getName(), $this->logger
		);
		return $html;
	}

	/**
	 * @param TemplateResources $resources
	 * @param ExportContext $context
	 * @return array
	 */
	protected function getStylesheets( TemplateResources $resources, ExportContext $context ): array {
		$templateStyles = $resources->getStylesheetPaths();
		$externalStylesheets = $this->stylesheetsFactory->getStylesheets( $this->getName(), $context );
		ksort( $externalStylesheets );
		$stylesheets = array_merge(
			$externalStylesheets,
			$templateStyles
		);

		return $stylesheets;
	}

	/**
	 * @param TemplateResources $resources
	 * @param ExportContext $context
	 * @return array
	 */
	protected function getStyleblocks( TemplateResources $resources, ExportContext $context ): array {
		$templateBlocks = $resources->getStyleBlocks();
		$externalBlocks = $this->styleBlocksFactory->getStyleBlocks( $this->getName(), $context );
		$commonCssBlocks = $this->mediaWikiCommonCssProvider->getStyles();

		$styleblocks = array_merge(
			$externalBlocks,
			[ 'MediaWiki:Common.css' => $commonCssBlocks ],
			$templateBlocks
		);
		return $styleblocks;
	}

	/**
	 * @param ExportContext $context
	 * @return array
	 */
	protected function getMeta( ExportContext $context ): array {
		$meta = $this->metaDataFactory->getMetaData( $this->getName(), $context );
		return $meta;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function ensureFileSystemPath( string $path ): string {
		if ( !file_exists( $path ) ) {
			mkdir( $path, 0755, true );
		}
		return $path;
	}

	/**
	 * @param array &$pages
	 * @param ExportContext $context
	 * @param bool $embedPageToc
	 * @return void
	 */
	protected function addTocPage( array &$pages, ExportContext $context, bool $embedPageToc = false ): void {
		if ( count( $pages ) > 1 ) {
			$tocPageBuilder = new TocBuilder( $this->titleFactory );
			$pages = $tocPageBuilder->execute( $pages, $embedPageToc );
		}
	}

	/**
	 * @param array $pages
	 * @param ExportContext $context
	 * @return string
	 */
	protected function getBookmarksXML( array $pages, ExportContext $context ): string {
		if ( count( $pages ) > 1 ) {
			$xmlBuilder = new BookmarksXMLBuilder();
			return $xmlBuilder->execute( $pages );
		}
		return '';
	}
}
