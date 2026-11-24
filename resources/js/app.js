/*
 * Kosmos WMS · JavaScript de la web (Vite)
 *
 * Cumple An. §3.1: interactividad ligera con Alpine.js (menús, ventanas modales).
 * Alpine se empaqueta con Vite (sin CDN): un solo archivo, cacheable y versionado.
 *
 * Instalación: npm install alpinejs
 * Si tu app.js ya importa './bootstrap', conserva esa línea.
 */

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
