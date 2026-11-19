'use strict';

const { defineConfig, globalIgnores } = require( 'eslint/config' );
const wikimedia = require( 'eslint-config-wikimedia/flat' );

// Directories with their own config, which other configs don't apply to.
// These were directories with `"root": true` in .eslintrc.json.
const rootDirs = {
	modules: [ 'modules/**' ]
};

module.exports = defineConfig( [
	globalIgnores( [
		'vendor/',

		// Language files written automatically by TranslateWiki
		'**/i18n/**/*.json',
		'!**/i18n/**/en.json',
		'!**/i18n/**/qqq.json'
	] ),

	// Gruntfile.js, etc.
	{
		ignores: Object.values( rootDirs ).flat(),
		extends: [ wikimedia( 'common', 'mediawiki' ) ]
	},
	{
		// Was set by an `eslint-env es6` comment in Gruntfile.js
		files: [ '*.js' ],
		languageOptions: {
			ecmaVersion: 6
		}
	},

	// Code which runs in MediaWiki
	{
		files: rootDirs.modules,
		extends: [ wikimedia( 'client', 'jquery', 'mediawiki' ) ],
		rules: {
			'no-jquery/no-done-fail': 'warn'
		}
	},
	{
		files: [ 'modules/ve-wikihiero/**' ],
		languageOptions: {
			globals: {
				ve: 'writable'
			}
		}
	}
] );
