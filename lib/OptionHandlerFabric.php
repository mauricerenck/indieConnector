<?php

namespace mauricerenck\IndieConnector;

class PluginOptions
{
    protected static ?OptionHandler $instance = null;

    public static function handler(): OptionHandler
    {
        return static::$instance ??= new OptionHandler();
    }

    public static function setHandler(?OptionHandler $handler): void
    {
        static::$instance = $handler;
    }

    /** Shortcut, spart in Routes/Page-Methods das ->handler()->get() */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::handler()->get($key, $default);
    }
}
