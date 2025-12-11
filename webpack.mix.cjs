const mix =require('laravel-mix')
mix.browserSync({
    proxy: 'http://kke-sakip.test',
    open: 'external',
    notify: false,
    files: ['resources/views/**/*.php','public/**/*.{css,js}']
});
