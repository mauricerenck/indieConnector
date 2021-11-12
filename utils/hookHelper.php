<?php

namespace mauricerenck\IndieConnector;

use Kirby;

class HookHelper
{
    /**
     * Helper function to trigger custom hooks in both Kirby 3.3 and 3.4 syntax;
     * translates vars array into variables for <v3.4, hands on array for v3.4+
     */
    public function triggerHook(string $hook, array $vars)
    {
        if (version_compare(\Kirby\Cms\App::version(), '3.4.0-rc.1', '<') === true) {
            kirby()->trigger($hook, ...array_values($vars));
        } else {
            kirby()->trigger($hook, $vars);
        }
    }
}
