/**
 * ContentEditable MediaWiki hieroglyphics node.
 *
 * @class
 * @extends ve.ce.MWBlockExtensionNode
 *
 * @constructor
 * @param {ve.dm.MWHieroNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWHieroNode = function VeCeMWHieroNode() {
	// Parent constructor
	ve.ce.MWHieroNode.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-mwHieroNode' );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWHieroNode, ve.ce.MWBlockExtensionNode );

/* Static Properties */

ve.ce.MWHieroNode.static.name = 'mwHiero';

ve.ce.MWHieroNode.static.tagName = 'div';

ve.ce.MWHieroNode.static.primaryCommandName = 'hiero';

ve.ce.MWHieroNode.static.iconWhenInvisible = 'hiero';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWHieroNode );
