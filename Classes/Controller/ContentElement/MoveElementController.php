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

namespace TYPO3\CMS\Backend\Controller\ContentElement;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Tree\View\ContentMovingPagePositionMap;
use TYPO3\CMS\Backend\Tree\View\PageMovingPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for rendering the move-element wizard display
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class MoveElementController
{
    /**
     * @var int
     */
    protected $sys_language = 0;

    /**
     * @var int
     */
    protected $page_id;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $R_URI;

    /**
     * @var int
     */
    protected $input_moveUid;

    /**
     * @var int
     */
    protected $moveUid;

    /**
     * @var int
     */
    protected $makeCopy;

    /**
     * Pages-select clause
     *
     * @var string
     */
    protected $perms_clause;

    /**
     * Content for module accumulated here.
     *
     * @var string
     */
    protected $content;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->init($request);
        $this->renderContent();
        return new HtmlResponse($this->content);
    }

    /**
     * Constructor, initializing internal variables.
     *
     * @param ServerRequestInterface $request
     */
    protected function init(ServerRequestInterface $request)
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Setting internal vars:
        $this->sys_language = (int)($parsedBody['sys_language'] ?? $queryParams['sys_language'] ?? 0);
        $this->page_id = (int)($parsedBody['uid'] ?? $queryParams['uid'] ?? 0);
        $this->table = $parsedBody['table'] ?? $queryParams['table'] ?? null;
        $this->R_URI = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        $this->input_moveUid = $parsedBody['moveUid'] ?? $queryParams['moveUid'] ?? null;
        $this->moveUid = $this->input_moveUid ?: $this->page_id;
        $this->makeCopy = $parsedBody['makeCopy'] ?? $queryParams['makeCopy'] ?? 0;
        // Select-pages where clause for read-access:
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
    }

    /**
     * Creating the module output.
     */
    protected function renderContent(): void
    {
        $lang = $this->getLanguageService();

        if ($this->page_id) {
            $assigns = [];
            $backendUser = $this->getBackendUser();
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
            // Get record for element:
            $elRow = BackendUtility::getRecordWSOL($this->table, $this->moveUid);
            // Headerline: Icon, record title:
            $assigns['table'] = $this->table;
            $assigns['elRow'] = $elRow;
            $assigns['recordTooltip'] = BackendUtility::getRecordToolTip($elRow, $this->table);
            $assigns['recordTitle'] = BackendUtility::getRecordTitle($this->table, $elRow, true);
            // Make-copy checkbox (clicking this will reload the page with the GET var makeCopy set differently):
            $assigns['makeCopyChecked'] = $this->makeCopy ? ' checked="checked"' : '';
            $assigns['makeCopyUrl'] = GeneralUtility::linkThisScript(['makeCopy' => !$this->makeCopy]);
            // IF the table is "pages":
            if ((string)$this->table === 'pages') {
                // Get page record (if accessible):
                $pageInfo = BackendUtility::readPageAccess($this->page_id, $this->perms_clause);
                if (is_array($pageInfo) && $backendUser->isInWebMount($pageInfo['pid'], $this->perms_clause)) {
                    // Initialize the position map:
                    $posMap = GeneralUtility::makeInstance(PageMovingPagePositionMap::class);
                    $posMap->moveOrCopy = $this->makeCopy ? 'copy' : 'move';
                    $posMap->moveUid = $this->moveUid;
                    // Print a "go-up" link IF there is a real parent page (and if the user has read-access to that page).
                    if ($pageInfo['pid']) {
                        $pidPageInfo = BackendUtility::readPageAccess($pageInfo['pid'], $this->perms_clause);
                        if (is_array($pidPageInfo)) {
                            if ($backendUser->isInWebMount($pidPageInfo['pid'], $this->perms_clause)) {
                                $assigns['pages']['goUpUrl'] = GeneralUtility::linkThisScript([
                                    'uid' => (int)$pageInfo['pid'],
                                    'moveUid' => $this->moveUid
                                ]);
                            } else {
                                $assigns['pages']['pidPageInfo'] = $pidPageInfo;
                            }
                            $assigns['pages']['pidRecordTitle'] = BackendUtility::getRecordTitle('pages', $pidPageInfo, true);
                        }
                    }
                    // Create the position tree:
                    $assigns['pages']['positionTree'] = $posMap->positionTree($this->page_id, $pageInfo, $this->perms_clause, $this->R_URI);
                }
            }
            // IF the table is "tt_content":
            if ((string)$this->table === 'tt_content') {
                // First, get the record:
                $tt_content_rec = BackendUtility::getRecord('tt_content', $this->moveUid);
                // ?
                if (!$this->input_moveUid) {
                    $this->page_id = $tt_content_rec['pid'];
                }
                // Checking if the parent page is readable:
                $pageInfo = BackendUtility::readPageAccess($this->page_id, $this->perms_clause);
                if (is_array($pageInfo) && $backendUser->isInWebMount($pageInfo['pid'], $this->perms_clause)) {
                    // Initialize the position map:
                    $posMap = GeneralUtility::makeInstance(ContentMovingPagePositionMap::class);
                    $posMap->moveOrCopy = $this->makeCopy ? 'copy' : 'move';
                    $posMap->moveUid = $this->moveUid;
                    $posMap->cur_sys_language = $this->sys_language;
                    // Headerline for the parent page: Icon, record title:
                    $assigns['ttContent']['pageInfo'] = $pageInfo;
                    $assigns['ttContent']['recordTooltip'] = BackendUtility::getRecordToolTip($pageInfo, 'pages');
                    $assigns['ttContent']['recordTitle'] = BackendUtility::getRecordTitle('pages', $pageInfo, true);
                    $colPosArray = GeneralUtility::callUserFunction(BackendLayoutView::class . '->getColPosListItemsParsed', $this->page_id, $this);
                    $colPosIds = [];
                    foreach ($colPosArray as $colPos) {
                        $colPosIds[] = $colPos[1];
                    }
                    // Removing duplicates, if any
                    $colPosList = implode(',', array_unique($colPosIds));
                    // Adding parent page-header and the content element columns from position-map:
                    $assigns['ttContent']['contentElementColumns'] = $posMap->printContentElementColumns($this->page_id, $this->moveUid, $colPosList, 1, $this->R_URI);
                    // Print a "go-up" link IF there is a real parent page (and if the user has read-access to that page).
                    if ($pageInfo['pid']) {
                        $pidPageInfo = BackendUtility::readPageAccess($pageInfo['pid'], $this->perms_clause);
                        if (is_array($pidPageInfo)) {
                            if ($backendUser->isInWebMount($pidPageInfo['pid'], $this->perms_clause)) {
                                $assigns['ttContent']['goUpUrl'] = GeneralUtility::linkThisScript([
                                    'uid' => (int)$pageInfo['pid'],
                                    'moveUid' => $this->moveUid
                                ]);
                            } else {
                                $assigns['ttContent']['pidPageInfo'] = $pidPageInfo;
                            }
                            $assigns['ttContent']['pidRecordTitle'] = BackendUtility::getRecordTitle('pages', $pidPageInfo, true);
                        }
                    }
                    // Create the position tree (for pages):
                    $assigns['ttContent']['positionTree'] = $posMap->positionTree($this->page_id, $pageInfo, $this->perms_clause, $this->R_URI);
                }
            }
            // Rendering of the output via fluid
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);
            $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
                'EXT:backend/Resources/Private/Templates/ContentElement/MoveElement.html'
            ));
            $view->assignMultiple($assigns);
            $this->content .= $view->render();
        }

        // Setting up the buttons and markers for docheader
        $this->getButtons();
        // Build the <body> for the module
        $this->moduleTemplate->setTitle($lang->getLL('movingElement'));
        $this->moduleTemplate->setContent($this->content);

        $this->content = $this->moduleTemplate->renderContent();
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if ($this->page_id) {
            if ((string)$this->table === 'pages') {
                $cshButton = $buttonBar->makeHelpButton()
                    ->setModuleName('xMOD_csh_corebe')
                    ->setFieldName('move_el_pages');
                $buttonBar->addButton($cshButton);
            } elseif ((string)$this->table === 'tt_content') {
                $cshButton = $buttonBar->makeHelpButton()
                    ->setModuleName('xMOD_csh_corebe')
                    ->setFieldName('move_el_cs');
                $buttonBar->addButton($cshButton);
            }

            if ($this->R_URI) {
                $backButton = $buttonBar->makeLinkButton()
                    ->setHref($this->R_URI)
                    ->setTitle($this->getLanguageService()->getLL('goBack'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
                $buttonBar->addButton($backButton);
            }
        }
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
