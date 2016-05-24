/*jshint node:true */
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-jscs' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		jshint: {
			options: {
				jshintrc: true
			},
			all: [
				'*.js',
				'modules/*.js',
				'modules/ve-wikihiero/**/*.js'
			]
		},
		jscs: {
			src: '<%= jshint.all %>'
		},
		stylelint: {
			all: 'modules/**/*.css'
		},
		watch: {
			files: [
				'.{jscsrc,jshintignore,jshintrc,csslintrc}',
				'<%= jshint.all %>',
				'<%= stylelint.all %>'
			],
			tasks: 'lint'
		},
		banana: {
			all: 'i18n/'
		},
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**'
			]
		}
	} );

	grunt.registerTask( 'lint', [ 'jshint', 'jscs', 'stylelint', 'jsonlint', 'banana' ] );
	grunt.registerTask( 'test', 'lint' );
	grunt.registerTask( 'default', 'test' );
};
