<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaRadioItemsTest extends UnitTestCase
{
    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfRadioItemsNotDefined(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438594829);
        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataKeepExistingItems(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    'foo',
                                    'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $expected = $input;
        self::assertSame($expected, (new TcaRadioItems())->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfItemsAreNoArray(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => 'aoeu',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438607163);
        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfItemLabelIsNotSet(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    'funnyKey' => 'funnyValue',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438607164);
        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfItemValueIsNotSet(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438607165);
        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataTranslatesItemLabels(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                    1 => 'aValue',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;

        $languageService->expects(self::atLeastOnce())->method('sL')->with('aLabel')->willReturn('translated');

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'translated';

        self::assertSame($expected, (new TcaRadioItems())->addData($input));
        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataCallsItemsProcFunc(): void
    {
        $input = [
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => [],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [],
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                $parameters['items'] = [
                                    'foo' => 'bar',
                                ];
                            },
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config'] = [
            'type' => 'radio',
            'items' => [
                'foo' => 'bar',
            ],
        ];
        self::assertSame($expected, (new TcaRadioItems())->addData($input));
    }

    /**
     * @test
     */
    public function addDataItemsProcFuncReceivesParameters(): void
    {
        $input = [
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => ['config' => 'someValue'],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'itemsProcFunc.' => [
                                'itemParamKey' => 'itemParamValue',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'aKey' => 'aValue',
                            'items' => [
                                0 => [
                                    'foo',
                                    'bar',
                                ],
                            ],
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                if ($parameters['items'] !== [ 0 => [ 'foo', 'bar'] ]
                                    || $parameters['config']['aKey'] !== 'aValue'
                                    || $parameters['TSconfig'] !== [ 'itemParamKey' => 'itemParamValue' ]
                                    || $parameters['table'] !== 'aTable'
                                    || $parameters['row'] !== [ 'aField' => 'aValue' ]
                                    || $parameters['field'] !== 'aField'
                                    || $parameters['inlineParentUid'] !== 1
                                    || $parameters['inlineParentTableName'] !== 'aTable'
                                    || $parameters['inlineParentFieldName'] !== 'aField'
                                    || $parameters['inlineParentConfig'] !== ['config' => 'someValue']
                                    || $parameters['inlineTopMostParentUid'] !== 1
                                    || $parameters['inlineTopMostParentTableName'] !== 'topMostTable'
                                    || $parameters['inlineTopMostParentFieldName'] !== 'topMostField'
                                ) {
                                    throw new \UnexpectedValueException('broken', 1476109434);
                                }
                            },
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);
        $flashMessage = $this->createMock(FlashMessage::class);
        GeneralUtility::addInstance(FlashMessage::class, $flashMessage);
        $flashMessageService = $this->createMock(FlashMessageService::class);
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService);
        $flashMessageQueue = $this->createMock(FlashMessageQueue::class);
        $flashMessageService->method('getMessageQueueByIdentifier')->with(self::anything())->willReturn($flashMessageQueue);

        // itemsProcFunc must NOT have raised an exception
        $flashMessageQueue->expects(self::never())->method('enqueue')->with($flashMessage);

        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataItemsProcFuncEnqueuesFlashMessageOnException(): void
    {
        $input = [
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => [],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'itemsProcFunc.' => [
                                'itemParamKey' => 'itemParamValue',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'aKey' => 'aValue',
                            'items' => [
                                0 => [
                                    'foo',
                                    'bar',
                                ],
                            ],
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                throw new \UnexpectedValueException('anException', 1476109435);
                            },
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->with(self::anything())->willReturn('');
        $GLOBALS['LANG'] = $languageService;
        $flashMessage = $this->createMock(FlashMessage::class);
        GeneralUtility::addInstance(FlashMessage::class, $flashMessage);
        $flashMessageService = $this->createMock(FlashMessageService::class);
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService);
        $flashMessageQueue = $this->createMock(FlashMessageQueue::class);
        $flashMessageService->method('getMessageQueueByIdentifier')->with(self::anything())->willReturn($flashMessageQueue);

        $flashMessageQueue->expects(self::atLeastOnce())->method('enqueue')->with($flashMessage);

        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataTranslatesItemLabelsFromPageTsConfig(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                    1 => 'aValue',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'altLabels.' => [
                                0 => 'labelOverride',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->expects(self::atLeastOnce())->method('sL')
            ->withConsecutive(['aLabel'], ['labelOverride'])->willReturnArgument(0);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'labelOverride';

        self::assertSame($expected, (new TcaRadioItems())->addData($input));
        (new TcaRadioItems())->addData($input);
    }
}
