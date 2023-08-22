const mix = require('laravel-mix');
require('laravel-mix-workbox');

mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')
   .workbox({
     swDest: 'public/service-worker.js',
     clientsClaim: true,
     skipWaiting: true,
     runtimeCaching: [
       {
         urlPattern: new RegExp('^https://fonts.(?:googleapis|gstatic).com/'),
         handler: 'CacheFirst'
       },
       {
         urlPattern: /^https?.*/,
         handler: 'StaleWhileRevalidate',
         options: {
           cacheName: 'offlineCache',
           expiration: {
             maxAgeSeconds: 60 * 60 * 24 * 7
           }
         }
       }
     ]
   });
