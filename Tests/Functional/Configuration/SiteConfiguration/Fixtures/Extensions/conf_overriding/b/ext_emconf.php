<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => '',
    'description' => '',
    'category' => 'example',
    'author' => '',
    'author_company' => '',
    'author_email' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '11.5.28',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.28',
            'a' => '11.5.28',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
