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
    'version' => '9.5.22',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.22',
            'recordlist' => '9.5.22',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
