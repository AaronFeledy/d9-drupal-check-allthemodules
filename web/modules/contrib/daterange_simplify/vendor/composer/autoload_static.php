<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3d564ac7e5067d0ec4b3b19c6f8602dc
{
    public static $files = array (
        '6a47392539ca2329373e0d33e1dba053' => __DIR__ . '/..' . '/symfony/polyfill-intl-icu/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\Intl\\' => 23,
        ),
        'O' => 
        array (
            'OpenPsa\\Ranger\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\Intl\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/intl',
        ),
        'OpenPsa\\Ranger\\' => 
        array (
            0 => __DIR__ . '/..' . '/openpsa/ranger/src',
        ),
    );

    public static $classMap = array (
        'Collator' => __DIR__ . '/..' . '/symfony/intl/Resources/stubs/Collator.php',
        'IntlDateFormatter' => __DIR__ . '/..' . '/symfony/intl/Resources/stubs/IntlDateFormatter.php',
        'Locale' => __DIR__ . '/..' . '/symfony/intl/Resources/stubs/Locale.php',
        'NumberFormatter' => __DIR__ . '/..' . '/symfony/intl/Resources/stubs/NumberFormatter.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3d564ac7e5067d0ec4b3b19c6f8602dc::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3d564ac7e5067d0ec4b3b19c6f8602dc::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit3d564ac7e5067d0ec4b3b19c6f8602dc::$classMap;

        }, null, ClassLoader::class);
    }
}
