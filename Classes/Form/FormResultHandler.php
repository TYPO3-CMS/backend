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

namespace TYPO3\CMS\Backend\Form;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Bridges FormEngine rendering and the PageRenderer by registering all
 * scripts, styles, and language labels that FormEngine elements declared
 * as dependencies, so the backend page can execute the rendered form.
 *
 * @internal This class may change any time or vanish altogether
 */
final readonly class FormResultHandler
{
    public function __construct(
        private PageRenderer $pageRenderer,
        private UriBuilder $uriBuilder,
    ) {}

    public function addAssets(FormResult|FormResultCollection $formResult): void
    {
        if ($formResult instanceof FormResultCollection) {
            $javaScriptModules = $formResult->getJavaScriptModules();
            $stylesheetFiles = $formResult->getStylesheetFiles();
            $additionalInlineLanguageLabelFiles = $formResult->getAdditionalInlineLanguageLabelFiles();
            $inlineData = $formResult->getInlineData();
        } else {
            $javaScriptModules = $formResult->javaScriptModules;
            $stylesheetFiles = $formResult->stylesheetFiles;
            $additionalInlineLanguageLabelFiles = $formResult->additionalInlineLanguageLabelFiles;
            $inlineData = $formResult->inlineData;
        }

        foreach ($stylesheetFiles as $stylesheetFile) {
            $this->pageRenderer->addCssFile($stylesheetFile);
        }

        // load the main module for FormEngine with all important JS functions
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/form-engine.js')
            ->invoke(
                'initialize',
                (string)$this->uriBuilder->buildUriFromRoute('wizard_element_browser')
            )
        );

        foreach ($javaScriptModules as $module) {
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction($module);
        }

        foreach ($additionalInlineLanguageLabelFiles as $additionalInlineLanguageLabelFile) {
            $this->pageRenderer->addInlineLanguageLabelFile($additionalInlineLanguageLabelFile);
        }

        // Add JS required for inline fields
        if ($inlineData !== []) {
            $this->pageRenderer->addInlineSettingArray('FormEngineInline', $inlineData);
        }
    }
}
