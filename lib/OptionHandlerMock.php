<?php

namespace mauricerenck\IndieConnector;

class OptionHandlerMock extends OptionHandler
{
    protected array $values;

    public function __construct(array $values = [])
    {
        $this->values = $values;
        // no parent::__construct() needed, no Kirby/YAML access
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }
}
