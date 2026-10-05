<?php

/**
 * Coding standard of the GLPI project (PER-CS 2.0), used for this plugin.
 * Run: composer cs / composer cs-fix
 */

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'dist'])
    ->ignoreDotFiles(false)
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules(['@PER-CS2.0' => true])
    ->setFinder($finder)
    ->setCacheFile(sys_get_temp_dir() . '/assetqr.php-cs-fixer.cache');
