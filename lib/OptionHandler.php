<?php

namespace mauricerenck\IndieConnector;

use Kirby\Data\Data;

class OptionHandler
{
    protected const PREFIX = 'mauricerenck.indieConnector.';

    protected ?array $yamlOptions = null;
    protected string $yamlPath;

    public function __construct(?string $yamlPath = null)
    {
        $this->yamlPath = $yamlPath ?? kirby()->root('content') . '/indie-connector-options.yml';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $yaml = $this->loadYamlOptions();

        if (array_key_exists($key, $yaml)) {
            return $yaml[$key];
        }

        return option(self::PREFIX . $key, $default);
    }

    protected function loadYamlOptions(): array
    {
        if ($this->yamlOptions !== null) {
            return $this->yamlOptions;
        }

        if (!file_exists($this->yamlPath)) {
            return $this->yamlOptions = [];
        }

        try {
            return $this->yamlOptions = Data::read($this->yamlPath, 'yaml');
        } catch (\Throwable) {
            return $this->yamlOptions = [];
        }
    }
}
