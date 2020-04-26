<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Validator;

interface ValidationInterface
{
    /**
     * @param mixed $input
     */
    public function validate($input): void;
}
