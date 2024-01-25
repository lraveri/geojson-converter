<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1b88a0b778c2fad0c25364cb075e9876
{
    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'Lraveri\\GeojsonConverter\\' => 25,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Lraveri\\GeojsonConverter\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1b88a0b778c2fad0c25364cb075e9876::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1b88a0b778c2fad0c25364cb075e9876::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit1b88a0b778c2fad0c25364cb075e9876::$classMap;

        }, null, ClassLoader::class);
    }
}