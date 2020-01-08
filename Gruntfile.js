/* eslint-env node, es6 */
module.exports = function ( grunt ) {
	var conf = grunt.file.readJSON( 'extension.json' );

	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		eslint: {
			options: {
				extensions: [ '.js', '.json' ],
				cache: true
			},
			all: '.'
		},
		stylelint: {
			all: 'modules/**/*.css'
		},
		watch: {
			files: [
				'.eslintrc.json',
				'.stylelintrc.json',
				'<%= eslint.all %>',
				'<%= stylelint.all %>'
			],
			tasks: 'lint'
		},
		banana: Object.assign( { options: { requireLowerCase: false } }, conf.MessagesDirs )
	} );

	grunt.registerTask( 'lint', [ 'eslint', 'stylelint', 'banana' ] );
	grunt.registerTask( 'test', 'lint' );
	grunt.registerTask( 'default', 'test' );
};
