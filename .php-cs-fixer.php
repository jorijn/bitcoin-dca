<?php

declare(strict_types=1);

$header = <<<'EOF'
This file is part of the Bitcoin-DCA package.

(c) Jorijn Schrijvershof <jorijn@jorijn.com>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('var');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules(
        [
            '@PHP71Migration:risky' => true,
            '@PHPUnit75Migration:risky' => true,
            '@Symfony' => true,
            '@Symfony:risky' => true,
            '@PhpCsFixer' => true,
            '@PhpCsFixer:risky' => true,
            'general_phpdoc_annotation_remove' => ['annotations' => ['expectedDeprecation']],
            'header_comment' => ['header' => $header],
            'array_syntax' => ['syntax' => 'short'],
            'method_argument_space' => [
                'on_multiline' => 'ensure_fully_multiline'
            ],
        ]
    )
    ->setFinder($finder);
