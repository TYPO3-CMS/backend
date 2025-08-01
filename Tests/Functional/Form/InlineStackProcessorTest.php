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

namespace TYPO3\CMS\Backend\Tests\Functional\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class InlineStackProcessorTest extends FunctionalTestCase
{
    public static function structureStringIsParsedDataProvider(): array
    {
        return [
            'simple 1-level table structure' => [
                'data-pageId-childTable',
                [
                    'unstable' => [
                        'table' => 'childTable',
                    ],
                ],
                [
                    'form' => '',
                    'object' => '',
                ],
            ],
            'simple 1-level table-uid structure' => [
                'data-pageId-childTable-childUid',
                [
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                    ],
                ],
                [
                    'form' => '',
                    'object' => '',
                ],
            ],
            'simple 1-level table-uid-field structure' => [
                'data-pageId-childTable-childUid-childField',
                [
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                        'field' => 'childField',
                    ],
                ],
                [
                    'form' => '',
                    'object' => '',
                ],
            ],
            'simple 2-level table structure' => [
                'data-pageId-parentTable-parentUid-parentField-childTable',
                [
                    'stable' => [
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-parentTable-parentUid-parentField',
                ],
            ],
            'simple 2-level table-uid structure' => [
                'data-pageId-parentTable-parentUid-parentField-childTable-childUid',
                [
                    'stable' => [
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-parentTable-parentUid-parentField',
                ],
            ],
            'simple 2-level table-uid-field structure' => [
                'data-pageId-parentTable-parentUid-parentField-childTable-childUid-childField',
                [
                    'stable' => [
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                        'field' => 'childField',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-parentTable-parentUid-parentField',
                ],
            ],
            'simple 3-level table structure' => [
                'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField-childTable',
                [
                    'stable' => [
                        [
                            'table' => 'grandParentTable',
                            'uid' => 'grandParentUid',
                            'field' => 'grandParentField',
                            'config' => [],
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField',
                ],
            ],
            'simple 3-level table-uid structure' => [
                'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField-childTable-childUid',
                [
                    'stable' => [
                        [
                            'table' => 'grandParentTable',
                            'uid' => 'grandParentUid',
                            'field' => 'grandParentField',
                            'config' => [],
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField',
                ],
            ],
            'simple 3-level table-uid-field structure' => [
                'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField-childTable-childUid-childField',
                [
                    'stable' => [
                        [
                            'table' => 'grandParentTable',
                            'uid' => 'grandParentUid',
                            'field' => 'grandParentField',
                            'config' => [],
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                        'field' => 'childField',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField',
                ],
            ],
            'flexform 3-level table-uid structure' => [
                'data-pageId-grandParentTable-grandParentUid-grandParentField---data---sDEF---lDEF---grandParentFlexForm---vDEF-parentTable-parentUid-parentField-childTable-childUid',
                [
                    'stable' => [
                        [
                            'table' => 'grandParentTable',
                            'uid' => 'grandParentUid',
                            'field' => 'grandParentField',
                            'flexform' => [
                                'data', 'sDEF', 'lDEF', 'grandParentFlexForm', 'vDEF',
                            ],
                            'config' => [],
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField---data---sDEF---lDEF---grandParentFlexForm---vDEF-parentTable-parentUid-parentField',
                ],
            ],
        ];
    }

    #[DataProvider('structureStringIsParsedDataProvider')]
    #[Test]
    public function getStructureFromStringParsesStructureString(string $string, array $expectedInlineStructure, array $_): void
    {
        $subject = new InlineStackProcessor();
        self::assertEquals($expectedInlineStructure, $subject->getStructureFromString($string));
    }

    #[DataProvider('structureStringIsParsedDataProvider')]
    #[Test]
    public function getFormPrefixFromStructureReturnsExpectedString(string $string, array $_, array $expectedFormName): void
    {
        $subject = new InlineStackProcessor();
        $structure = $subject->getStructureFromString($string);
        self::assertEquals($expectedFormName['form'], $subject->getFormPrefixFromStructure($structure));
    }

    #[DataProvider('structureStringIsParsedDataProvider')]
    #[Test]
    public function getDomObjectIdPrefixFromStructureReturnsExpectedString(string $string, array $_, array $expectedFormName): void
    {
        $subject = new InlineStackProcessor();
        $structure = $subject->getStructureFromString($string);
        self::assertEquals($expectedFormName['object'], $subject->getDomObjectIdPrefixFromStructure($structure, 'pageId'));
    }
}
