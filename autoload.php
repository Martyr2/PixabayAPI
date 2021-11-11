<?php

/**
 * Simple autoloader for loading Pixabay API classes
 *
 * @author Martyr2
 * @copyright 2021 Martyr2
 * @link https://www.coderslexicon.com
 */

spl_autoload_register(function($class) {
    $prefix = 'pixabayapi\\';

    // Base directory for the namespace
    $base_dir = __DIR__ . "\\";

    // Check if class uses prefix. If not, skip this loader.
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
