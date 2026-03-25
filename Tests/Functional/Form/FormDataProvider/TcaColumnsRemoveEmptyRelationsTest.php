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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveEmptyRelations;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaBuilder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaColumnsRemoveEmptyRelationsTest extends FunctionalTestCase
{
    private TcaColumnsRemoveEmptyRelations $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->subject = new TcaColumnsRemoveEmptyRelations();
    }

    #[Test]
    public function categoryFieldIsRemovedWhenNoCategoriesExist(): void
    {
        $input = $this->buildCategoryInput([]);
        $result = $this->createTcaCategory()->addData($input);
        $result = $this->subject->addData($result);

        self::assertArrayNotHasKey('categories', $result['processedTca']['columns']);
    }

    #[Test]
    public function categoryFieldIsReadOnlyInDebugModeWhenNoCategoriesExist(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = true;

        $input = $this->buildCategoryInput([]);
        $result = $this->createTcaCategory()->addData($input);
        $result = $this->subject->addData($result);

        self::assertArrayHasKey('categories', $result['processedTca']['columns']);
        self::assertTrue($result['processedTca']['columns']['categories']['config']['readOnly']);
        self::assertArrayHasKey('noSelectableItemsAvailable', $result['processedTca']['columns']['categories']['config']['fieldInformation']);
    }

    #[Test]
    public function categoryFieldIsKeptWhenCategoriesExist(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/CategoryRelations.csv');
        $input = $this->buildCategoryInput([]);

        $result = $this->createTcaCategory()->addData($input);
        $result = $this->subject->addData($result);

        self::assertArrayHasKey('categories', $result['processedTca']['columns']);
        self::assertArrayNotHasKey('readOnly', $result['processedTca']['columns']['categories']['config'] ?? []);
    }

    #[Test]
    public function categoryFieldOptOutPreventsRemoval(): void
    {
        $input = $this->buildCategoryInput([]);
        $input['processedTca']['columns']['categories']['config']['showIfEmpty'] = true;
        $result = $this->createTcaCategory()->addData($input);
        $result = $this->subject->addData($result);

        self::assertArrayHasKey('categories', $result['processedTca']['columns']);
    }

    #[Test]
    public function categoryFieldIsRemovedWhenTsconfigRemoveItemsRemovesAllCategories(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/CategoryRelations.csv');
        $input = $this->buildCategoryInput([]);
        $input['pageTsConfig'] = [
            'TCEFORM.' => [
                'tt_content.' => [
                    'categories.' => [
                        'removeItems' => '28,29,30,31',
                    ],
                ],
            ],
        ];

        $result = $this->createTcaCategory()->addData($input);
        $result = $this->subject->addData($result);

        self::assertArrayNotHasKey('categories', $result['processedTca']['columns']);
    }

    #[Test]
    public function categoryFieldIsKeptWhenTsconfigRemoveItemsRemovesSomeCategories(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/CategoryRelations.csv');
        $input = $this->buildCategoryInput([]);
        $input['pageTsConfig'] = [
            'TCEFORM.' => [
                'tt_content.' => [
                    'categories.' => [
                        'removeItems' => '28,31',
                    ],
                ],
            ],
        ];

        $result = $this->createTcaCategory()->addData($input);
        $result = $this->subject->addData($result);

        self::assertArrayHasKey('categories', $result['processedTca']['columns']);
        self::assertArrayNotHasKey('readOnly', $result['processedTca']['columns']['categories']['config'] ?? []);
    }

    #[Test]
    public function categoryFieldIsRemovedWhenTsconfigKeepItemsIsEmpty(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/CategoryRelations.csv');
        $input = $this->buildCategoryInput([]);
        $input['pageTsConfig'] = [
            'TCEFORM.' => [
                'tt_content.' => [
                    'categories.' => [
                        'keepItems' => '',
                    ],
                ],
            ],
        ];

        $result = $this->createTcaCategory()->addData($input);
        $result = $this->subject->addData($result);

        self::assertArrayNotHasKey('categories', $result['processedTca']['columns']);
    }

    #[Test]
    public function categoryFieldIsKeptWhenTsconfigKeepItemsAllowsSome(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/CategoryRelations.csv');
        $input = $this->buildCategoryInput([]);
        $input['pageTsConfig'] = [
            'TCEFORM.' => [
                'tt_content.' => [
                    'categories.' => [
                        'keepItems' => '29',
                    ],
                ],
            ],
        ];

        $result = $this->createTcaCategory()->addData($input);
        $result = $this->subject->addData($result);

        self::assertArrayHasKey('categories', $result['processedTca']['columns']);
        self::assertArrayNotHasKey('readOnly', $result['processedTca']['columns']['categories']['config'] ?? []);
    }

    #[Test]
    public function selectFieldWithForeignTableIsRemovedWhenNoRecordsExist(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'usergroup' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'usergroup' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'fe_groups',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('usergroup', $result['processedTca']['columns']);
    }

    private function buildCategoryInput(array $databaseCategories): array
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => implode(',', $databaseCategories),
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => [
                            'type' => 'category',
                            'relationship' => 'oneToOne',
                            'foreign_table' => 'sys_category',
                            'foreign_table_where' => ' AND {#sys_category}.{#sys_language_uid} IN (-1, 0)',
                            'size' => 20,
                            'default' => 0,
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
            'rootline' => [],
            'site' => null,
        ];

        $tca = $GLOBALS['TCA'];
        $tca['tt_content'] = $input['processedTca'];
        $input['tcaSchemata'] = $this->get(TcaSchemaBuilder::class)->buildFromStructure($tca);

        return $input;
    }

    private function createTcaCategory(): TcaCategory
    {
        $category = new TcaCategory();
        $category->injectConnectionPool($this->get(ConnectionPool::class));
        $category->injectIconFactory($this->get(IconFactory::class));
        return $category;
    }
}
