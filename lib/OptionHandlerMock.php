<?php

namespace mauricerenck\IndieConnector;

class OptionHandlerMock extends OptionHandler
{
    protected array $defaultOptions;
    protected array $values;

    public function __construct(array $values = [])
    {
        $this->values = $values;
        $this->defaultOptions = $values;
        // no parent::__construct() needed, no Kirby/YAML access
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $default = null): void
    {
        $this->values[$key] = $default;
    }

    public function reset(): void
    {
        $this->values = $this->defaultOptions;
    }
}
