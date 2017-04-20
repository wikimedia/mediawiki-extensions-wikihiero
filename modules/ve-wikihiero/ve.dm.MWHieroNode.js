/**
 * DataModel MediaWiki hieroglyphics node.
 *
 * @class
 * @extends ve.dm.MWBlockExtensionNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWHieroNode = function VeDmMWHieroNode() {
	// Parent constructor
	ve.dm.MWHieroNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWHieroNode, ve.dm.MWBlockExtensionNode );

/* Static members */

ve.dm.MWHieroNode.static.name = 'mwHiero';

ve.dm.MWHieroNode.static.tagName = 'table';

ve.dm.MWHieroNode.static.extensionName = 'hiero';

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWHieroNode );
