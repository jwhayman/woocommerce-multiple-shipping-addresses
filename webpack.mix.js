let mix = require('laravel-mix');

mix.disableNotifications();
mix.setPublicPath('dist/');

mix.js('src/scripts/main.js', '')
    .sass('src/styles/main.scss', '')
    .sourceMaps()
    .version();