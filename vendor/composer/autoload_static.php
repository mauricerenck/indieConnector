<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd2f181dd341adfd58b96ccab3bed9890
{
    public static $files = array (
        '757772e28a0943a9afe83def8db95bdf' => __DIR__ . '/..' . '/mf2/mf2/Mf2/Parser.php',
    );

    public static $prefixLengthsPsr4 = array (
        'm' => 
        array (
            'mauricerenck\\IndieConnector\\' => 28,
        ),
        'K' => 
        array (
            'Kirby\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'mauricerenck\\IndieConnector\\' => 
        array (
            0 => __DIR__ . '/../..' . '/utils',
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
        'mauricerenck\\IndieConnector\\MastodonSender' => __DIR__ . '/../..' . '/utils/sendMastodon.php',
        'mauricerenck\\IndieConnector\\Microformats' => __DIR__ . '/../..' . '/utils/Microformats.php',
        'mauricerenck\\IndieConnector\\Migrations' => __DIR__ . '/../..' . '/utils/Migrations.php',
        'mauricerenck\\IndieConnector\\Receiver' => __DIR__ . '/../..' . '/utils/receiver.php',
        'mauricerenck\\IndieConnector\\Sender' => __DIR__ . '/../..' . '/utils/Sender.php',
        'mauricerenck\\IndieConnector\\TestCaseMocked' => __DIR__ . '/../..' . '/utils/TestCaseMocked.php',
        'mauricerenck\\IndieConnector\\WebmentionReceiver' => __DIR__ . '/../..' . '/utils/WebmentionReceiver.php',
        'mauricerenck\\IndieConnector\\WebmentionSender' => __DIR__ . '/../..' . '/utils/WebmentionSender.php',
        'mauricerenck\\IndieConnector\\WebmentionStats' => __DIR__ . '/../..' . '/utils/WebmentionStats.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd2f181dd341adfd58b96ccab3bed9890::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd2f181dd341adfd58b96ccab3bed9890::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitd2f181dd341adfd58b96ccab3bed9890::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitd2f181dd341adfd58b96ccab3bed9890::$classMap;

        }, null, ClassLoader::class);
    }
}
