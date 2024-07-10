<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit84944e4200b9284446d234824c8e9dcc
{
    public static $files = array (
        '757772e28a0943a9afe83def8db95bdf' => __DIR__ . '/..' . '/mf2/mf2/Mf2/Parser.php',
    );

    public static $prefixLengthsPsr4 = array (
        'm' => 
        array (
            'mauricerenck\\IndieConnector\\' => 28,
        ),
        'c' => 
        array (
            'cjrasmussen\\BlueskyApi\\' => 23,
        ),
        'K' => 
        array (
            'Kirby\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'mauricerenck\\IndieConnector\\' => 
        array (
            0 => __DIR__ . '/../..' . '/lib',
        ),
        'cjrasmussen\\BlueskyApi\\' => 
        array (
            0 => __DIR__ . '/..' . '/cjrasmussen/bluesky-api/src',
        ),
        'Kirby\\' => 
        array (
            0 => __DIR__ . '/..' . '/getkirby/composer-installer/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'I' => 
        array (
            'IndieWeb' => 
            array (
                0 => __DIR__ . '/..' . '/indieweb/mention-client/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'IndieWeb\\MentionClient' => __DIR__ . '/..' . '/indieweb/mention-client/src/IndieWeb/MentionClient.php',
        'IndieWeb\\MentionClientTest' => __DIR__ . '/..' . '/indieweb/mention-client/src/IndieWeb/MentionClientTest.php',
        'Kirby\\ComposerInstaller\\CmsInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/CmsInstaller.php',
        'Kirby\\ComposerInstaller\\Installer' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Installer.php',
        'Kirby\\ComposerInstaller\\Plugin' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Plugin.php',
        'Kirby\\ComposerInstaller\\PluginInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/PluginInstaller.php',
        'cjrasmussen\\BlueskyApi\\BlueskyApi' => __DIR__ . '/..' . '/cjrasmussen/bluesky-api/src/BlueskyApi.php',
        'mauricerenck\\IndieConnector\\BlueskySender' => __DIR__ . '/../..' . '/lib/BlueskySender.php',
        'mauricerenck\\IndieConnector\\IndieConnectorDatabase' => __DIR__ . '/../..' . '/lib/Database.php',
        'mauricerenck\\IndieConnector\\MastodonSender' => __DIR__ . '/../..' . '/lib/MastodonSender.php',
        'mauricerenck\\IndieConnector\\Microformats' => __DIR__ . '/../..' . '/lib/Microformats.php',
        'mauricerenck\\IndieConnector\\Migrations' => __DIR__ . '/../..' . '/lib/Migrations.php',
        'mauricerenck\\IndieConnector\\PageChecks' => __DIR__ . '/../..' . '/lib/PageChecks.php',
        'mauricerenck\\IndieConnector\\QueueHandler' => __DIR__ . '/../..' . '/lib/QueueHandler.php',
        'mauricerenck\\IndieConnector\\Receiver' => __DIR__ . '/../..' . '/lib/receiver.php',
        'mauricerenck\\IndieConnector\\Sender' => __DIR__ . '/../..' . '/lib/Sender.php',
        'mauricerenck\\IndieConnector\\TestCaseMocked' => __DIR__ . '/../..' . '/lib/TestCaseMocked.php',
        'mauricerenck\\IndieConnector\\UrlChecks' => __DIR__ . '/../..' . '/lib/UrlChecks.php',
        'mauricerenck\\IndieConnector\\WebmentionIo' => __DIR__ . '/../..' . '/lib/WebmentionIo.php',
        'mauricerenck\\IndieConnector\\WebmentionReceiver' => __DIR__ . '/../..' . '/lib/WebmentionReceiver.php',
        'mauricerenck\\IndieConnector\\WebmentionSender' => __DIR__ . '/../..' . '/lib/WebmentionSender.php',
        'mauricerenck\\IndieConnector\\WebmentionStats' => __DIR__ . '/../..' . '/lib/WebmentionStats.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit84944e4200b9284446d234824c8e9dcc::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit84944e4200b9284446d234824c8e9dcc::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit84944e4200b9284446d234824c8e9dcc::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit84944e4200b9284446d234824c8e9dcc::$classMap;

        }, null, ClassLoader::class);
    }
}
