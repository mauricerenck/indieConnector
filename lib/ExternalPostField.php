<?php

namespace mauricerenck\IndieConnector;

use Kirby\Form\Field\InputField;
use Kirby\Reflection\Attributes\Prop;

class ExternalPostField extends InputField
{
    #[Prop]
    protected array $serviceItems = [];

    protected mixed $value = null;
}
