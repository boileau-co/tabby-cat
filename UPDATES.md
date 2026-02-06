GitHub Automatic Updates
=======================

This plugin can optionally use the YahnisElsts "plugin-update-checker" library to enable automatic updates from this GitHub repository.

Install (recommended via Composer)
---------------------------------
1. Change to the plugin directory (where `tabby-cat.php` lives).

```bash
cd /path/to/wp-content/plugins/tabby-cat
composer require yahnis-elsts/plugin-update-checker:^4.11
```

2. Commit the `vendor/` folder or run `composer install` on the server.

Alternative (no Composer)
--------------------------
- Download the library from: https://github.com/YahnisElsts/plugin-update-checker and include its `plugin-update-checker.php` (or the PSR-4 autoloader) inside `vendor/`.
- Ensure `vendor/autoload.php` is present or include the library file directly.

Notes
-----
- The plugin adds a small scaffold at the end of `tabby-cat.php` that will activate the updater if the `Puc_v4_Factory` class is available.
- The updater is configured to use the `main` branch of `https://github.com/boileau-co/tabby-cat/`.
- No updates will occur unless you publish a release or configure the repository to provide update metadata (the library supports GitHub releases and tags).

If you want, I can add a `composer.json` with the dependency and run a local `composer require` for you (you'll need Composer installed). 
