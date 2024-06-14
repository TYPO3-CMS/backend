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

namespace TYPO3\CMS\Backend\Preview;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Check if a Fluid-based preview template was defined for a given
 * CType and render it via Fluid. Also works for list_type / plugins.
 *
 * Example in page TSconfig:
 * mod.web_layout.tt_content.preview.textmedia = EXT:site_mysite/Resources/Private/Templates/Preview/Textmedia.html
 *
 * @internal not part of the TYPO3 Core API
 */
final class FluidBasedContentPreviewRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected readonly FlexFormService $flexFormService
    ) {}

    #[AsEventListener('typo3-backend/fluid-preview/content')]
    public function __invoke(PageContentPreviewRenderingEvent $event): void
    {
        $previewContent = $this->renderContentElementPreviewFromFluidTemplate(
            $event->getRecord(),
            $event->getTable(),
            $event->getRecordType()
        );
        if ($previewContent !== null) {
            $event->setPreviewContent($previewContent);
        }
    }

    protected function renderContentElementPreviewFromFluidTemplate(array $row, string $table, string $recordType): ?string
    {
        $tsConfig = BackendUtility::getPagesTSconfig($row['pid'])['mod.']['web_layout.'][$table . '.']['preview.'] ?? [];
        $fluidTemplateFile = '';

        if (
            $table === 'tt_content'
            && $recordType === 'list'
            && !empty($row['list_type'])
            && !empty($tsConfig['list.'][$row['list_type']])
        ) {
            $fluidTemplateFile = $tsConfig['list.'][$row['list_type']];
        } elseif (!empty($tsConfig[$recordType])) {
            $fluidTemplateFile = $tsConfig[$recordType];
        }

        if ($fluidTemplateFile === '') {
            return null;
        }

        $fluidTemplateFileAbsolutePath = GeneralUtility::getFileAbsFileName($fluidTemplateFile);
        if ($fluidTemplateFileAbsolutePath === '') {
            return null;
        }
        try {
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename($fluidTemplateFileAbsolutePath);
            $view->assignMultiple($row);
            if ($table === 'tt_content' && !empty($row['pi_flexform'])) {
                $view->assign('pi_flexform_transformed', $this->flexFormService->convertFlexFormContentToArray($row['pi_flexform']));
            }
            return $view->render();
        } catch (\Exception $e) {
            $this->logger->warning('The backend preview for content element {uid} can not be rendered using the Fluid template file "{file}"', [
                'uid' => $row['uid'],
                'file' => $fluidTemplateFileAbsolutePath,
                'exception' => $e,
            ]);

            if ($this->getBackendUser()->shallDisplayDebugInformation()) {
                $view = GeneralUtility::makeInstance(StandaloneView::class);
                $view->assign('error', [
                    'message' => str_replace(Environment::getProjectPath(), '', $e->getMessage()),
                    'title' => 'Error while rendering FluidTemplate preview using ' . str_replace(Environment::getProjectPath(), '', $fluidTemplateFileAbsolutePath),
                ]);
                $view->setTemplateSource('<f:be.infobox title="{error.title}" state="2">{error.message}</f:be.infobox>');
                return $view->render();
            }
            return null;
        }
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
