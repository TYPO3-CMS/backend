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

namespace TYPO3\CMS\Backend\Tests\Functional\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendUtilityTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DK' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'dk_DA.UTF8'],
        'DE' => ['id' => 2, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();
    }

    /**
     * @test
     */
    public function givenPageIdCanBeExpanded(): void
    {
        $backendUser = $this->getBackendUser();
        $backendUser->groupData['webmounts'] = '1';

        BackendUtility::openPageTree(5, false);

        $expectedSiteHash = [
            '1_5' => '1',
            '1_1' => '1',
            '1_0' => '1',
        ];
        $actualSiteHash = $backendUser->uc['BackendComponents']['States']['Pagetree']['stateHash'];
        self::assertSame($expectedSiteHash, $actualSiteHash);
    }

    /**
     * @test
     */
    public function otherBranchesCanBeClosedWhenOpeningPage(): void
    {
        $backendUser = $this->getBackendUser();
        $backendUser->groupData['webmounts'] = '1';

        BackendUtility::openPageTree(5, false);
        BackendUtility::openPageTree(4, true);

        //the complete branch of uid => 5 should be closed here
        $expectedSiteHash = [
            '1_4' => '1',
            '1_3' => '1',
            '1_2' => '1',
            '1_1' => '1',
            '1_0' => '1',
        ];
        $actualSiteHash = $backendUser->uc['BackendComponents']['States']['Pagetree']['stateHash'];
        self::assertSame($expectedSiteHash, $actualSiteHash);
    }

    /**
     * @test
     */
    public function getProcessedValueForLanguage(): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DK', '/dk/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ]
        );

        self::assertEquals(
            'Dansk',
            BackendUtility::getProcessedValue(
                'pages',
                'sys_language_uid',
                '1',
                0,
                false,
                false,
                1
            )
        );

        self::assertEquals(
            'German',
            BackendUtility::getProcessedValue(
                'tt_content',
                'sys_language_uid',
                '2',
                0,
                false,
                false,
                1
            )
        );
    }

    /**
     * @test
     */
    public function getRecordTitleForUidLabel(): void
    {
        $GLOBALS['TCA']['tt_content']['ctrl']['label'] = 'uid';
        unset($GLOBALS['TCA']['tt_content']['ctrl']['label_alt']);

        self::assertEquals(
            '1',
            BackendUtility::getRecordTitle('tt_content', BackendUtility::getRecord('tt_content', 1))
        );
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
