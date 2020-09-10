<?php
spl_autoload_register(function ($class) {
    // project-specific namespace prefix
    // base directory for the namespace prefix
    $base_dir =  plugin_dir_path(__FILE__) . 'src/TwoCheckout/';
    // separators with directory separators in the relative class name, append with .php
    $file = $base_dir . str_replace('\\', '/', str_replace("_","",$class)) . '.php';
    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
