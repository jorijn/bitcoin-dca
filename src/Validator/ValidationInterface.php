<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Validator;

interface ValidationInterface
{
    /**
     * @param mixed $input
     */
    public function validate($input): void;
}
