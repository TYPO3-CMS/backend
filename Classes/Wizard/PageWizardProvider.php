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

namespace TYPO3\CMS\Backend\Wizard;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Wizard\DTO\Configuration;
use TYPO3\CMS\Backend\Wizard\DTO\Finisher;
use TYPO3\CMS\Backend\Wizard\DTO\Step;
use TYPO3\CMS\Backend\Wizard\DTO\SubmissionResult;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

#[AsTaggedItem(index: 'page_wizard')]
class PageWizardProvider implements WizardProviderInterface
{
    public function __construct(
        private UriBuilder $uriBuilder,
        private PageWizardStepBuilder $stepFactory,
    ) {}

    public function getConfiguration(ServerRequestInterface $serverRequest): Configuration
    {
        if (!isset($serverRequest->getQueryParams()['data']['doktype'])) {
            return Configuration::create([
                Step::create('@typo3/backend/page-wizard/steps/form-engine-step.js')
                    ->withConfigurationData([
                        'title' => 'Error',
                        'key' => 'error',
                        'html' => 'Invalid wizard submission!',
                    ]),
            ]);
        }

        $doktype = $serverRequest->getQueryParams()['data']['doktype'];
        $pageUid = (int)$serverRequest->getQueryParams()['data']['position']['pageUid'];
        $steps = $this->stepFactory->getStepsForDokType($doktype, $pageUid, $serverRequest);
        return Configuration::create($steps);
    }

    public function handleSubmit(ServerRequestInterface $serverRequest): SubmissionResult
    {
        $params = $serverRequest->getParsedBody();
        $newPageUid = StringUtility::getUniqueId('NEW_');

        $dataMap = [
            'pages' => [
                $newPageUid => [
                    'pid' => (int)$params['position']['pageUid'],
                    'doktype' => (string)$params['doktype'],
                ],
            ],
        ];

        // TODO: either clean up the data we get passed beforehand
        //       or make this code cleaner. ('data[pages'...)
        foreach ($params['fields'] ?? [] as $stepData) {
            foreach ($stepData['data[pages'] ?? [] as $randomKey => $fields) {
                foreach ($fields as $fieldName => $fieldValue) {
                    $dataMap['pages'][$newPageUid][$fieldName] = $fieldValue;
                }
            }
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();

        if ($dataHandler->errorLog !== []) {
            return SubmissionResult::createErrorResult(
                $dataHandler->errorLog,
            );
        }
        $newPageUid = $dataHandler->substNEWwithIDs[$newPageUid] ?? null;

        $redirectUrl = (string)$this->uriBuilder->buildUriFromRoute('web_layout', [
            'id' => $newPageUid,
        ]);

        return SubmissionResult::createSuccessResult(
            Finisher::createRedirectFinisher(
                $redirectUrl,
                $this->getLanguageService()->translate('page_wizard.success.title', 'backend.wizards.page'),
                $this->getLanguageService()->translate('page_wizard.success.description', 'backend.wizards.page'),
            )->withResetButton($this->getLanguageService()->translate('page_wizard.button.create_another_page', 'backend.wizards.page'))
        );
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
