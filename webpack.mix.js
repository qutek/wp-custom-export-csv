let mix = require('laravel-mix');

mix.webpackConfig({
	externals: {
		"jquery": "jQuery"
	},
	resolve: {
		alias: {}
	}
});

mix.setPublicPath('dist')
	.sass('src/sass/admin.scss', 'admin/css').options({ processCssUrls: false })
	.js('src/js/admin.js', 'admin/js')
	.version();