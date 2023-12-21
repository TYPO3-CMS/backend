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

namespace TYPO3\CMS\Backend\Tests\Functional\View\ValueFormatter;

use TYPO3\CMS\Backend\View\ValueFormatter\FlexFormValueFormatter;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FlexFormValueFormatterTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet('typo3/sysext/backend/Tests/Functional/Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    /**
     * @test
     */
    public function flexFormDataWillBeDisplayedHumanReadable(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config'] = $this->getFieldTcaConfig();
        $expectedOutput = trim(file_get_contents(__DIR__ . '/Fixtures/FlexFormValueFormatter/ValuePreview.txt'));
        $flexFormData = file_get_contents(__DIR__ . '/Fixtures/FlexFormValueFormatter/FlexFormValue.xml');

        $flexFormValueFormatter = new FlexFormValueFormatter();
        $actualOutput = $flexFormValueFormatter->format(
            'aTableName',
            'aFieldName',
            $flexFormData,
            0,
            $this->getFieldTcaConfig(),
        );

        self::assertSame($expectedOutput, $actualOutput);
    }

    /**
     * @test
     */
    public function nullResultsInEmptyString(): void
    {
        $flexFormValueFormatter = new FlexFormValueFormatter();
        $actualOutput = $flexFormValueFormatter->format(
            'aTableName',
            'aFieldName',
            null,
            0,
            [],
        );

        self::assertSame('', $actualOutput);
    }

    /**
     * @test
     */
    public function emptyStringResultsInEmptyString(): void
    {
        $flexFormValueFormatter = new FlexFormValueFormatter();
        $actualOutput = $flexFormValueFormatter->format(
            'aTableName',
            'aFieldName',
            '',
            0,
            [],
        );

        self::assertSame('', $actualOutput);
    }

    private function getFieldTcaConfig(): array
    {
        return [
            'ds' => [
                'default' => 'FILE:EXT:backend/Tests/Functional/View/ValueFormatter/Fixtures/FlexFormValueFormatter/FlexFormDataStructure.xml',
            ],
        ];
    }
}
