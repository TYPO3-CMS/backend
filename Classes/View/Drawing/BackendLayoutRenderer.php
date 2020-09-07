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

namespace TYPO3\CMS\Backend\View\Drawing;

use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\ContentFetcher;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\LanguageColumn;
use TYPO3\CMS\Backend\View\BackendLayout\RecordRememberer;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Backend Layout Renderer
 *
 * Draws a page layout - essentially, behaves as a wrapper for a view
 * which renders the Resources/Private/PageLayout/PageLayout template
 * with necessary assigned template variables.
 *
 * - Initializes the clipboard used in the page layout
 * - Inserts an encoded paste icon as JS which is made visible when clipboard elements are registered
 *
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
 */
class BackendLayoutRenderer
{
    use LoggerAwareTrait;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var PageLayoutContext
     */
    protected $context;

    /**
     * @var ContentFetcher
     */
    protected $contentFetcher;

    /**
     * @var Clipboard
     */
    protected $clipboard;

    /**
     * @var TemplateView
     */
    protected $view;

    public function __construct(PageLayoutContext $context)
    {
        $this->context = $context;
        $this->contentFetcher = GeneralUtility::makeInstance(ContentFetcher::class, $context);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->initializeClipboard();
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $controllerContext = $objectManager->get(ControllerContext::class);
        $request = $objectManager->get(Request::class);
        $controllerContext->setRequest($request);
        $this->view = GeneralUtility::makeInstance(TemplateView::class);
        $this->view->getRenderingContext()->setControllerContext($controllerContext);
        $this->view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName('backend');
        $this->view->getRenderingContext()->setControllerName('PageLayout');
        $this->view->assign('context', $context);
    }

    public function getGridForPageLayoutContext(PageLayoutContext $context): Grid
    {
        $grid = GeneralUtility::makeInstance(Grid::class, $context);
        $recordRememberer = GeneralUtility::makeInstance(RecordRememberer::class);
        if ($context->getDrawingConfiguration()->getLanguageMode()) {
            $languageId = $context->getSiteLanguage()->getLanguageId();
        } else {
            $languageId = $context->getDrawingConfiguration()->getSelectedLanguageId();
        }
        foreach ($context->getBackendLayout()->getStructure()['__config']['backend_layout.']['rows.'] ?? [] as $row) {
            $rowObject = GeneralUtility::makeInstance(GridRow::class, $context);
            foreach ($row['columns.'] as $column) {
                $columnObject = GeneralUtility::makeInstance(GridColumn::class, $context, $column);
                $rowObject->addColumn($columnObject);
                if (isset($column['colPos'])) {
                    $records = $this->contentFetcher->getContentRecordsPerColumn((int)$column['colPos'], $languageId);
                    $recordRememberer->rememberRecords($records);
                    foreach ($records as $contentRecord) {
                        $columnItem = GeneralUtility::makeInstance(GridColumnItem::class, $context, $columnObject, $contentRecord);
                        $columnObject->addItem($columnItem);
                    }
                }
            }
            $grid->addRow($rowObject);
        }
        return $grid;
    }

    /**
     * @return LanguageColumn[]
     */
    public function getLanguageColumnsForPageLayoutContext(PageLayoutContext $context): iterable
    {
        $languageColumns = [];
        foreach ($context->getLanguagesToShow() as $siteLanguage) {
            $localizedLanguageId = $siteLanguage->getLanguageId();
            if ($localizedLanguageId === -1) {
                continue;
            }
            if ($localizedLanguageId > 0) {
                $localizedContext = $context->cloneForLanguage($siteLanguage);
                if (!$localizedContext->getLocalizedPageRecord()) {
                    continue;
                }
            } else {
                $localizedContext = $context;
            }
            $translationInfo = $this->contentFetcher->getTranslationData(
                $this->contentFetcher->getFlatContentRecords($localizedLanguageId),
                $localizedContext->getSiteLanguage()->getLanguageId()
            );
            $languageColumnObject = GeneralUtility::makeInstance(
                LanguageColumn::class,
                $localizedContext,
                $this->getGridForPageLayoutContext($localizedContext),
                $translationInfo
            );
            $languageColumns[] = $languageColumnObject;
        }
        return $languageColumns;
    }

    /**
     * @param bool $renderUnused If true, renders the bottom column with unused records
     * @return string
     */
    public function drawContent(bool $renderUnused = true): string
    {
        $this->view->assign('hideRestrictedColumns', (bool)(BackendUtility::getPagesTSconfig($this->context->getPageId())['mod.']['web_layout.']['hideRestrictedCols'] ?? false));
        $this->view->assign('newContentTitle', $this->getLanguageService()->getLL('newContentElement'));
        $this->view->assign('newContentTitleShort', $this->getLanguageService()->getLL('content'));
        $this->view->assign('allowEditContent', $this->getBackendUser()->check('tables_modify', 'tt_content'));

        if ($this->context->getDrawingConfiguration()->getLanguageMode()) {
            $this->view->assign('languageColumns', $this->getLanguageColumnsForPageLayoutContext($this->context));
        } else {
            $this->view->assign('grid', $this->getGridForPageLayoutContext($this->context));
        }

        $rendered = $this->view->render('PageLayout');
        if ($renderUnused) {
            $unusedRecords = $this->contentFetcher->getUnusedRecords();

            if (!empty($unusedRecords)) {
                $unusedElementsMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $this->getLanguageService()->getLL('staleUnusedElementsWarning'),
                    $this->getLanguageService()->getLL('staleUnusedElementsWarningTitle'),
                    FlashMessage::WARNING
                );
                $service = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $service->getMessageQueueByIdentifier();
                $queue->addMessage($unusedElementsMessage);

                $unusedGrid = GeneralUtility::makeInstance(Grid::class, $this->context);
                $unusedRow = GeneralUtility::makeInstance(GridRow::class, $this->context);
                $unusedColumn = GeneralUtility::makeInstance(GridColumn::class, $this->context, ['colPos' => false, 'name' => 'unused']);

                $unusedGrid->addRow($unusedRow);
                $unusedRow->addColumn($unusedColumn);

                foreach ($unusedRecords as $unusedRecord) {
                    $item = GeneralUtility::makeInstance(GridColumnItem::class, $this->context, $unusedColumn, $unusedRecord);
                    $unusedColumn->addItem($item);
                }

                $this->view->assign('grid', $unusedGrid);
                $rendered .= $this->view->render('UnusedRecords');
            }
        }
        return $rendered;
    }

    /**
     * Initializes the clipboard for generating paste links
     *
     * @see \TYPO3\CMS\Backend\Controller\ContextMenuController::clipboardAction()
     * @see \TYPO3\CMS\Filelist\Controller\FileListController::indexAction()
     */
    protected function initializeClipboard(): void
    {
        $this->clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $this->clipboard->initializeClipboard();
        $this->clipboard->lockToNormal();
        $this->clipboard->cleanCurrent();
        $this->clipboard->endClipboard();

        $elFromTable = $this->clipboard->elFromTable('tt_content');
        if (!empty($elFromTable) && $this->isContentEditable()) {
            $pasteItem = (int)substr(key($elFromTable), 11);
            $pasteRecord = BackendUtility::getRecord('tt_content', (int)$pasteItem);
            $pasteTitle = (string)($pasteRecord['header'] ?: $pasteItem);
            $copyMode = $this->clipboard->clipData['normal']['mode'] ? '-' . $this->clipboard->clipData['normal']['mode'] : '';
            $addExtOnReadyCode = '
                     top.pasteIntoLinkTemplate = '
                . $this->drawPasteIcon($pasteItem, $pasteTitle, $copyMode, 't3js-paste-into', 'pasteIntoColumn')
                . ';
                    top.pasteAfterLinkTemplate = '
                . $this->drawPasteIcon($pasteItem, $pasteTitle, $copyMode, 't3js-paste-after', 'pasteAfterRecord')
                . ';';
        } else {
            $addExtOnReadyCode = '
                top.pasteIntoLinkTemplate = \'\';
                top.pasteAfterLinkTemplate = \'\';';
        }
        GeneralUtility::makeInstance(PageRenderer::class)->addJsInlineCode('pasteLinkTemplates', $addExtOnReadyCode);
    }

    /**
     * Draw a paste icon either for pasting into a column or for pasting after a record
     *
     * @param int $pasteItem ID of the item in the clipboard
     * @param string $pasteTitle Title for the JS modal
     * @param string $copyMode copy or cut
     * @param string $cssClass CSS class to determine if pasting is done into column or after record
     * @param string $title title attribute of the generated link
     *
     * @return string Generated HTML code with link and icon
     */
    private function drawPasteIcon(int $pasteItem, string $pasteTitle, string $copyMode, string $cssClass, string $title): string
    {
        $pasteIcon = json_encode(
            ' <a data-content="' . htmlspecialchars((string)$pasteItem) . '"'
            . ' data-title="' . htmlspecialchars($pasteTitle) . '"'
            . ' data-severity="warning"'
            . ' class="t3js-paste t3js-paste' . htmlspecialchars($copyMode) . ' ' . htmlspecialchars($cssClass) . ' btn btn-default btn-sm"'
            . ' title="' . htmlspecialchars($this->getLanguageService()->getLL($title)) . '">'
            . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render()
            . '</a>'
        );
        return $pasteIcon;
    }

    protected function isContentEditable(): bool
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }

        $pageRecord = $this->context->getPageRecord();
        return !$pageRecord['editlock']
            && $this->getBackendUser()->check('tables_modify', 'tt_content')
            && $this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
