<?php

namespace MediaWiki\Extension\PDFCreator\Tests\Unit;

use DOMDocument;
use MediaWiki\Extension\PDFCreator\Utility\ExportPage;
use MediaWiki\Extension\PDFCreator\Utility\TableHeadsDuplicator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\PDFCreator\Utility\TableHeadsDuplicator
 */
class TableHeadsDuplicatorTest extends TestCase {

	public function testRowSpansAreNotMovedIntoSeparateTableSection(): void {
		$path = dirname( __DIR__ ) . '/data/TableHeadsDuplicatorTest-input-1.html';
		$input = file_get_contents( $path );
		$page = $this->newPage( $input );

		( new TableHeadsDuplicator() )->execute( [ $page ] );

		$table = $page->getDOMDocument()->getElementsByTagName( 'table' )->item( 0 );
		$this->assertSame( 1, $table->getElementsByTagName( 'thead' )->length );
		$this->assertSame( '2', $table->getElementsByTagName( 'th' )->item( 0 )->getAttribute( 'rowspan' ) );
		$this->assertSame( '2', $table->getElementsByTagName( 'th' )->item( 1 )->getAttribute( 'colspan' ) );
		$this->assertSame(
			2,
			$table->getElementsByTagName( 'thead' )->item( 0 )->getElementsByTagName( 'tr' )->length
		);
	}

	public function testSimpleHeaderIsMovedIntoTableHead(): void {
		$path = dirname( __DIR__ ) . '/data/TableHeadsDuplicatorTest-input-2.html';
		$input = file_get_contents( $path );
		$page = $this->newPage( $input );

		( new TableHeadsDuplicator() )->execute( [ $page ] );

		$table = $page->getDOMDocument()->getElementsByTagName( 'table' )->item( 0 );
		$this->assertSame( 1, $table->getElementsByTagName( 'thead' )->length );
		$headers = $table->getElementsByTagName( 'thead' )->item( 0 )->getElementsByTagName( 'th' );
		$this->assertSame( 2, $headers->length );
		$this->assertSame( 'A', $headers->item( 0 )->textContent );
		$this->assertSame( 'B', $headers->item( 1 )->textContent );
	}

	private function newPage( string $table ): ExportPage {
		$dom = new DOMDocument();
		$dom->loadXML( '<html><body>' . $table . '</body></html>' );
		return new ExportPage( 'raw', $dom, 'Test' );
	}
}
