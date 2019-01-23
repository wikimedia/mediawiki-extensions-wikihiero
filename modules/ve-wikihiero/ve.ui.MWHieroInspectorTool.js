/**
 * MediaWiki UserInterface hieroglyphics tool.
 *
 * @class
 * @extends ve.ui.FragmentInspectorTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWHieroInspectorTool = function VeUiMWHieroInspectorTool() {
	ve.ui.MWHieroInspectorTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWHieroInspectorTool, ve.ui.FragmentInspectorTool );
ve.ui.MWHieroInspectorTool.static.name = 'hiero';
ve.ui.MWHieroInspectorTool.static.group = 'object';
ve.ui.MWHieroInspectorTool.static.icon = 'hieroglyph';
ve.ui.MWHieroInspectorTool.static.title =
	OO.ui.deferMsg( 'wikihiero-visualeditor-mwhieroinspector-title' );
ve.ui.MWHieroInspectorTool.static.modelClasses = [ ve.dm.MWHieroNode ];
ve.ui.MWHieroInspectorTool.static.commandName = 'hiero';

ve.ui.toolFactory.register( ve.ui.MWHieroInspectorTool );
ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'hiero', 'window', 'open',
		{ args: [ 'hiero' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextHiero', 'hiero', '<hiero', 6 )
);

ve.ui.commandHelpRegistry.register( 'insert', 'hiero', {
	sequences: [ 'wikitextHiero' ],
	label: OO.ui.deferMsg( 'wikihiero-visualeditor-mwhieroinspector-title' )
} );
