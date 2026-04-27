<?php

namespace MediaWiki\Extension\PDFCreator\Utility;

use DOMElement;
use File;
use MediaWiki\Config\Config;
use MediaWiki\Title\TitleFactory;
use RepoGroup;

/**
 * @stable to extend
 */
class FileResolver {

	/** @var Config */
	protected $config;

	/** @var RepoGroup */
	protected $repoGroup;

	/** @var TitleFactory */
	protected $titleFactory;

	/**
	 * @param Config $config
	 * @param RepoGroup $repoGroup
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		Config $config, RepoGroup $repoGroup, TitleFactory $titleFactory
	) {
		$this->config = $config;
		$this->repoGroup = $repoGroup;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param DOMElement $element
	 * @param string $attrSrc
	 * @return File|null
	 */
	public function execute( DOMElement $element, string $attrSrc = 'src' ): ?File {
		$src = $element->getAttribute( $attrSrc );

		// When wgThumbnailScriptPath is set, thumbnail src attributes are query-based:
		// e.g. /w/thumb.php?f=Image.png&width=120
		// The path-stripping logic below strips the query string early, making filename
		// extraction impossible. Handle these URLs explicitly before that happens.
		$file = $this->resolveFromThumbScript( $src );
		if ( $file !== null ) {
			return $file;
		}

		$pathsForRegex = [
			$this->config->get( 'Server' ),
			$this->config->get( 'UploadPath' ),
			$this->config->get( 'ScriptPath' )
		];

		if ( strpos( $src, '?' ) ) {
			$src = substr( $src, 0, strpos( $src, '?' ) );
		}

		// Extracting the filename
		foreach ( $pathsForRegex as $path ) {
			$src = preg_replace( "#" . preg_quote( $path, "#" ) . "#", '', $src );
			$src = preg_replace( '/(&.*)/', '', $src );
		}

		$srcUrl = urldecode( $src );
		$srcFilename = wfBaseName( $srcUrl );

		$thumbFilenameExtractor = new ThumbFilenameExtractor();
		$isThumb = $thumbFilenameExtractor->isThumb( $srcUrl );
		if ( $isThumb ) {
			// HINT: Thumbname-to-filename-conversion taken from includes/Upload/UploadBase.php
			// Check for filenames like 50px- or 180px-, these are mostly thumbnails
			$srcFilename = $thumbFilenameExtractor->extractFilename( $srcUrl );
		}
		$fileTitle = $this->titleFactory->newFromText( $srcFilename, NS_FILE );
		$file = $this->repoGroup->findFile( $fileTitle );

		// If not found, maybe its an archived file
		if ( !$file ) {
			$file = $this->findArchivedFile( $srcFilename );
		}

		if ( !$file || !$file->exists() ) {
			$file = null;
		}

		return $file;
	}

	/**
	 * Attempt to resolve a file from a ThumbnailScriptPath-style URL
	 * (e.g. /w/thumb.php?f=Image.png&width=120).
	 *
	 * When $wgThumbnailScriptPath is configured, all thumbnail src attributes
	 * are query-based rather than path-based, so the path-stripping logic in
	 * execute() cannot extract a filename from them. This method handles that
	 * case by reading the 'f' query parameter directly.
	 *
	 * @param string $src Raw value of the src/href attribute
	 * @return File|null
	 */
	protected function resolveFromThumbScript( string $src ): ?File {
		$thumbScriptPath = $this->config->get( 'ThumbnailScriptPath' );
		if ( !$thumbScriptPath || strpos( $src, '?' ) === false ) {
			return null;
		}

		// Compare only the path component so absolute URLs (with server prefix) also match.
		$srcPath = parse_url( $src, PHP_URL_PATH ) ?? '';
		$scriptPath = parse_url( $thumbScriptPath, PHP_URL_PATH ) ?? $thumbScriptPath;
		if ( $srcPath !== $scriptPath ) {
			return null;
		}

		$query = parse_url( $src, PHP_URL_QUERY ) ?? '';
		parse_str( $query, $params );
		$fileName = $params['f'] ?? '';
		if ( $fileName === '' ) {
			return null;
		}

		// Archived files have the format <timestamp>!<name> and need a different lookup.
		if ( !empty( $params['archived'] ) ) {
			return $this->findArchivedFile( $fileName );
		}

		$fileTitle = $this->titleFactory->newFromText( $fileName, NS_FILE );
		if ( $fileTitle === null ) {
			return null;
		}

		$file = $this->repoGroup->findFile( $fileTitle );
		return ( $file && $file->exists() ) ? $file : null;
	}

	/**
	 * @param string $filename
	 *
	 * @return File|null
	 */
	protected function findArchivedFile( string $filename ): ?File {
		$timestampPattern = '/^\d{14}!/';

		if ( !preg_match( $timestampPattern, $filename ) ) {
			return null;
		}

		$localGroup = $this->repoGroup->getLocalRepo();

		// Remove 14-digit timestamp and exclamation mark at the start
		$origFileName = preg_replace( $timestampPattern, '', $filename );

		$fileTitle = $this->titleFactory->makeTitle( NS_FILE, $origFileName );

		return $localGroup->newFromArchiveName( $fileTitle, $filename );
	}
}
