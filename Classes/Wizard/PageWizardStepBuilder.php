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
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResult;
use TYPO3\CMS\Backend\Form\FormResultFactory;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Wizard\DTO\Step;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This is not a public API method, do not use in own extensions
 */
readonly class PageWizardStepBuilder
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
        private UriBuilder $uriBuilder,
        private DependencyOrderingService $dependencyOrderingService,
        private NodeFactory $nodeFactory,
        private FormResultFactory $formResultFactory,
        private FormDataCompiler $formDataCompiler,
    ) {}

    /**
     * @throws UndefinedSchemaException
     * @throws \UnexpectedValueException
     */
    public function getStepsForDokType(string $dokType, int $pageUid, ServerRequestInterface $serverRequest): array
    {
        $steps = [];
        $dokTypeSchema = $this->getSchemaForDokType($dokType);
        $requiredFields = $dokTypeSchema->getFields(fn(FieldTypeInterface $field) => $field->isRequired())->getNames();
        $sortedWizardConfiguration = $this->dependencyOrderingService->orderByDependencies($dokTypeSchema->getRawConfiguration()['wizardSteps'] ?? []);

        foreach ($sortedWizardConfiguration as $key => $configuration) {
            $fields = $configuration['fields'] ?? [];
            if ($fields === []) {
                throw new \UnexpectedValueException('Wizard step configuration is missing fields.', 1773741784);
            }

            $requiredFields = array_diff($requiredFields, $fields);
            $formResult = $this->getFormResultForStep($serverRequest, $dokType, $pageUid, $fields);
            $steps[] = $this->buildStep($key, $configuration['title'] ?? '', $fields, $formResult);
        }

        if ($requiredFields !== []) {
            $formResult = $this->getFormResultForStep($serverRequest, $dokType, $pageUid, $requiredFields);
            $steps[] = $this->buildStep('requiredFields', 'Required fields', $requiredFields, $formResult);
        }

        return $steps;
    }

    protected function buildStep(string $key, string $title, array $fields, FormResult $formResult): Step
    {
        return Step::create('@typo3/backend/page-wizard/steps/form-engine-step.js')
            ->withConfigurationData([
                'title' => $this->getLanguageService()->sL($title),
                'key' => $key,
                'html' => '<form name="editform">' . $formResult->html . implode(LF, $formResult->hiddenFieldsHtml) . '</form>',
                'modules' => [
                    JavaScriptModuleInstruction::create('@typo3/backend/form-engine.js')
                        ->invoke(
                            'initialize',
                            (string)$this->uriBuilder->buildUriFromRoute('wizard_element_browser')
                        ),
                    ...$formResult->javaScriptModules,
                ],
                'labels' => $this->getLabelsForFields($fields),
            ]);
    }

    protected function getFormResultForStep(ServerRequestInterface $serverRequest, string $doktype, int $pid, array $fields): FormResult
    {
        $fieldList = implode(',', $fields);

        $formDataCompilerInput = [
            'request' => $serverRequest,
            'tableName' => 'pages',
            'recordTypeValue' => $doktype,
            'command' => 'new',
            'vanillaUid' => $pid,
            'processedTca' => $GLOBALS['TCA']['pages'],
        ];
        $formDataCompilerInput['processedTca']['types'][$doktype]['showitem'] = $fieldList;

        $formData = $this->formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(TcaDatabaseRecord::class));
        $formData['renderType'] = 'listOfFieldsContainer';
        $formData['fieldListToRender'] = $fieldList;

        $formResult = $this->nodeFactory->create($formData)->render();
        return $this->formResultFactory->create($formResult);
    }

    protected function getLabelsForFields(array $fields): array
    {
        $languageService = $this->getLanguageService();
        $pageSchema = $this->tcaSchemaFactory->get('pages');
        $fields = $pageSchema->getFields(fn(FieldTypeInterface $field) => in_array($field->getName(), $fields));

        $labels = [];

        foreach ($fields as $field) {
            $labels[$field->getName()] = $languageService->sL($field->getLabel());
        }

        return $labels;
    }

    /**
     * @throws UndefinedSchemaException
     * @throws \RuntimeException
     */
    protected function getSchemaForDokType(string $dokType): TcaSchema
    {
        $tcaSchema = $this->tcaSchemaFactory->get('pages');
        if (!$tcaSchema->hasSubSchema($dokType)) {
            throw new \RuntimeException('Requested doktype is missing.', 1773673880);
        }
        return $tcaSchema->getSubSchema($dokType);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
