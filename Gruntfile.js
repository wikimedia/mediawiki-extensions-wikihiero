/*jshint node:true */
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-contrib-csslint' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-jscs' );

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
		csslint: {
			options: {
				csslintrc: '.csslintrc'
			},
			all: 'modules/**/*.css'
		},
		watch: {
			files: [
				'.{jscsrc,jshintignore,jshintrc,csslintrc}',
				'<%= jshint.all %>',
				'<%= csshint.all %>'
			],
			tasks: 'lint'
		},
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**'
			]
		}
	} );

	grunt.registerTask( 'lint', [ 'jshint', 'jscs', 'jsonlint', 'csslint' ] );
	grunt.registerTask( 'test', 'lint' );
	grunt.registerTask( 'default', 'test' );
};
