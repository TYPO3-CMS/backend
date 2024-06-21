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

namespace TYPO3\CMS\Backend\Tests\Functional\Clipboard;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Routing\RequestContextFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ClipboardTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $request = new ServerRequest('https://localhost/typo3/');
        $requestContextFactory = $this->get(RequestContextFactory::class);
        $uriBuilder = $this->get(UriBuilder::class);
        $uriBuilder->setRequestContext($requestContextFactory->fromBackendRequest($request));
        $this->withDatabaseSnapshot(
            function () {
                $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
                $scenarioFile = __DIR__ . '/../Fixtures/CommonScenario.yaml';
                $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
                $writer = DataHandlerWriter::withBackendUser($this->backendUser);
                $writer->invokeFactory($factory);
                static::failIfArrayIsNotEmpty($writer->getErrors());
            },
            function () {
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
            }
        );
    }

    public static function localizationsAreResolvedDataProvider(): array
    {
        return [
            'live workspace with live & version localizations' => [
                1100,
                0,
                'pages',
                [
                    'FR: Welcome',
                    'FR-CA: Welcome',
                ],
            ],
            'draft workspace with live & version localizations' => [
                1100,
                1,
                'pages',
                [
                    'FR: Welcome',
                    'FR-CA: Welcome',
                    'ES: Bienvenido',
                ],
            ],
            'live workspace with live localizations only' => [
                1400,
                0,
                'pages',
                [
                    'FR: ACME in your Region',
                    'FR-CA: ACME in your Region',
                ],
            ],
            'draft workspace with live localizations only' => [
                1400,
                1,
                'pages',
                [
                    'FR: ACME in your Region',
                    'FR-CA: ACME in your Region',
                ],
            ],
            'live workspace with version localizations only' => [
                1500,
                0,
                'pages',
                [],
            ],
            'draft workspace with version localizations only' => [
                1500,
                1,
                'pages',
                [
                    'FR: Interne',
                ],
            ],
            'Record is not of currently selected table' => [
                1500,
                1,
                '_FILE',
                [
                    '<span class="text-body-secondary">FR: Interne</span>',
                ],
            ],
        ];
    }

    #[DataProvider('localizationsAreResolvedDataProvider')]
    #[Test]
    public function localizationsAreResolved(
        int $pageId,
        int $workspaceId,
        string $table,
        array $expectation
    ): void {
        $this->backendUser->workspace = $workspaceId;
        $subject = $this->get(Clipboard::class);
        $subject->clipData['normal']['el'] = ["pages|$pageId" => 'some value'];
        $subject->current = 'normal';
        $normalTab = $subject->getClipboardData($table)['tabs'][0];
        array_shift($normalTab['items']);
        $actualTitles = [];
        foreach ($normalTab['items'] as $item) {
            $actualTitles[] = $item['title'];
        }

        self::assertEqualsCanonicalizing($expectation, $actualTitles);
    }
}
