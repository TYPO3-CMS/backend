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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EditDocumentControllerTest extends FunctionalTestCase
{
    protected EditDocumentController $subject;

    protected NormalizedParams $normalizedParams;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->subject = $this->get(EditDocumentController::class);
        $this->normalizedParams = new NormalizedParams([], [], '', '');
    }

    #[Test]
    public function processedDataTakesOverDefaultValues(): void
    {
        $request = (new ServerRequest('https://www.example.com/', 'POST'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $defaultValues = [
            'colPos' => 123,
            'CType' => 'bullets',
        ];

        $queryParams = $this->getQueryParamsWithDefaults($defaultValues);
        $parsedBody = $this->getParsedBody();

        $request = $request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->mainAction($request);

        $newRecord = BackendUtility::getRecord('tt_content', 2);
        self::assertEquals(
            [$defaultValues['colPos'], $defaultValues['CType']],
            [$newRecord['colPos'], $newRecord['CType']]
        );
        // Redirect to GET is applied after processing
        self::assertEquals(302, $response->getStatusCode());
    }

    #[Test]
    public function processedDataDoesNotOverridePostWithDefaultValues(): void
    {
        $request = (new ServerRequest('https://www.example.com/', 'POST'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $defaultValues = [
            'colPos' => 123,
            'CType' => 'bullets',
        ];

        $queryParams = $this->getQueryParamsWithDefaults($defaultValues);
        $parsedBody = $this->getParsedBody(['colPos' => 0, 'CType' => 'text']);
        $request = $request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->mainAction($request);

        $newRecord = BackendUtility::getRecord('tt_content', 2);
        self::assertEquals(
            [0, 'text'],
            [$newRecord['colPos'], $newRecord['CType']],
        );
        // Redirect to GET is applied after processing
        self::assertEquals(302, $response->getStatusCode());
    }

    protected function getParsedBody(array $additionalData = []): array
    {
        return [
            'data' => [
                'tt_content' => [
                    'NEW123456' => array_replace_recursive([
                        'sys_language_uid' => 0,
                        'header' => 'Test header',
                        'pid' => -1,
                    ], $additionalData),
                ],
            ],
            'doSave' => true,
        ];
    }

    protected function getQueryParamsWithDefaults(array $defaultValues): array
    {
        return [
            'edit' => [
                'tt_content' => [
                    -1 => 'new',
                ],
            ],
            'defVals' => [
                'tt_content' => $defaultValues,
            ],
        ];
    }
}
