<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Backend',
    'description' => 'Classes for the TYPO3 backend.',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '8.7.31',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.2.99',
            'typo3' => '8.7.31',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
