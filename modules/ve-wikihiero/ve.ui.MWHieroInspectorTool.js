/*global ve, OO */

/**
 * MediaWiki UserInterface hieroglyphics tool.
 *
 * @class
 * @extends ve.ui.InspectorTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWHieroInspectorTool = function VeUiMWHieroInspectorTool( toolGroup, config ) {
	ve.ui.InspectorTool.call( this, toolGroup, config );
};
OO.inheritClass( ve.ui.MWHieroInspectorTool, ve.ui.InspectorTool );
ve.ui.MWHieroInspectorTool.static.name = 'hiero';
ve.ui.MWHieroInspectorTool.static.group = 'object';
ve.ui.MWHieroInspectorTool.static.icon = 'hiero';
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
