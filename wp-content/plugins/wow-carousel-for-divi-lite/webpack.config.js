const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		admin: './src/admin/index.js',
		builder: './src/divi4/index.js',
		frontend: './src/divi4/frontend.js',
		'frontend-styles': './src/divi4/frontend.scss',
		'fs-override': './src/fs-override.scss',
	},

	output: {
		path: path.resolve(__dirname, 'dist'),
		filename: '[name].js',
	},

	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve.alias,
			'@Dependencies': path.resolve(__dirname, 'src/divi4/dependencies'),
		},
	},

	externals: {
		...defaultConfig.externals,
		$: 'jQuery',
		jquery: 'jQuery',
	},
};
