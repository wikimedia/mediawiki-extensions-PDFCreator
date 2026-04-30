'use strict';

window.ext = window.ext || {};

ext.pdfcreator = ext.pdfcreator || {};
ext.pdfcreator.ve = ext.pdfcreator.ve || {};
ext.pdfcreator.ve.ui = ext.pdfcreator.ve.ui || {};

ext.pdfcreator.ve.ui.ExcludeExportTool = function ( toolGroup, config ) {
	ext.pdfcreator.ve.ui.ExcludeExportTool.super.call( this, toolGroup, config );
};

OO.inheritClass( ext.pdfcreator.ve.ui.ExcludeExportTool, ve.ui.FragmentWindowTool );

ext.pdfcreator.ve.ui.ExcludeExportTool.static.name = 'excludeExportTool';
ext.pdfcreator.ve.ui.ExcludeExportTool.static.group = 'insert';
ext.pdfcreator.ve.ui.ExcludeExportTool.static.icon = 'close';
ext.pdfcreator.ve.ui.ExcludeExportTool.static.title = mw.message( 'pdfcreator-exclude-export-tool-title' ).text();

ext.pdfcreator.ve.ui.ExcludeExportTool.static.annotation = { name: 'excludeExport' };

ext.pdfcreator.ve.ui.ExcludeExportTool.static.commandName = 'PDFExcludeCommand';

ve.ui.toolFactory.register( ext.pdfcreator.ve.ui.ExcludeExportTool );
