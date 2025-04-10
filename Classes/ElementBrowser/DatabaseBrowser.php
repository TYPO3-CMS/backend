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

namespace TYPO3\CMS\Backend\ElementBrowser;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\RecordList\ElementBrowserRecordList;
use TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\RecordSearchBoxComponent;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Showing a page tree and allows you to browse for records. This is the modal rendered
 * for type=group to add db relations to a group field.
 *
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
class DatabaseBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
{
    protected string $identifier = 'db';

    /**
     * When you click a page title/expand icon to see the content of a certain page, this
     * value will contain the ID of the expanded page.
     * If the value is NOT set by GET parameter, then it will be restored from the module session data.
     *
     * @var int|null
     */
    protected $expandPage;
    protected array $modTSconfig = [];

    protected function initialize(ServerRequestInterface $request)
    {
        parent::initialize($request);
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/browse-database.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tree/page-browser.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/column-selector-button.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/recordlist.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/record-search.js');
    }

    protected function initVariables(ServerRequestInterface $request)
    {
        parent::initVariables($request);
        $this->expandPage = $request->getParsedBody()['expandPage'] ?? $request->getQueryParams()['expandPage'] ?? null;
    }

    /**
     * Session data for this class can be set from outside with this method.
     *
     * @param mixed[] $data Session data array
     * @return array<int, array|bool> Session data and boolean which indicates that data needs to be stored in session because it's changed
     */
    public function processSessionData($data)
    {
        if ($this->expandPage !== null) {
            $data['expandPage'] = $this->expandPage;
            $store = true;
        } else {
            $this->expandPage = (int)($data['expandPage'] ?? 0);
            $store = false;
        }
        return [$data, $store];
    }

    /**
     * @return string HTML content
     */
    public function render()
    {
        $this->getBackendUser()->initializeWebmountsForElementBrowser();
        $this->modTSconfig = BackendUtility::getPagesTSconfig((int)$this->expandPage)['mod.']['web_list.'] ?? [];
        [, , , $allowedTables] = explode('|', $this->bparams);

        $withTree = true;
        if ($allowedTables !== '' && $allowedTables !== '*') {
            $tablesArr = GeneralUtility::trimExplode(',', $allowedTables, true);
            $onlyRootLevel = true;
            foreach ($tablesArr as $currentTable) {
                if ($this->tcaSchemaFactory->has($currentTable)) {
                    $schema = $this->tcaSchemaFactory->get($currentTable);
                    if ($schema->getCapability(TcaSchemaCapability::RestrictionRootLevel)->canExistOnPages()) {
                        $onlyRootLevel = false;
                        break;
                    }
                }
            }
            if ($onlyRootLevel) {
                $withTree = false;
                // page to work on is root
                $this->expandPage = 0;
            }
        }

        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);
        $renderedRecordList = $this->renderTableRecords($allowedTables);

        $this->pageRenderer->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:recordSelector'));
        $view = $this->view;
        $view->assignMultiple([
            'treeEnabled' => $withTree,
            'treeActions' => $allowedTables === 'pages' ? ['select'] : [],
            'activePage' => $this->expandPage,
            'initialNavigationWidth' => $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250,
            'content' => $renderedRecordList,
            'contentOnly' => $contentOnly,
        ]);
        $content = $this->view->render('ElementBrowser/Page');
        if ($contentOnly) {
            return $content;
        }
        $this->pageRenderer->setBodyContent('<body ' . $this->getBodyTagParameters() . '>' . $content);
        return $this->pageRenderer->render();
    }

    /**
     * This lists all content elements for the given list of tables
     *
     * @param string $tables Comma separated list of tables. Set to "*" if you want all tables.
     * @return string HTML code
     */
    protected function renderTableRecords($tables)
    {
        $request = $this->getRequest();
        $backendUser = $this->getBackendUser();
        if ($this->expandPage === null || $this->expandPage < 0 || !$backendUser->isInWebMount($this->expandPage)) {
            return '';
        }
        // Set array with table names to list:
        if (trim($tables) === '*') {
            $tablesArr = $this->tcaSchemaFactory->all()->getNames();
        } else {
            $tablesArr = GeneralUtility::trimExplode(',', $tables, true);
        }

        $out = '';
        // Create the header, showing the current page for which the listing is.
        // Includes link to the page itself, if pages are amount allowed tables.
        $titleLen = (int)$backendUser->uc['titleLen'];
        $mainPageRecord = BackendUtility::getRecordWSOL('pages', $this->expandPage);
        if (is_array($mainPageRecord)) {
            $pText = htmlspecialchars(GeneralUtility::fixed_lgd_cs($mainPageRecord['title'], $titleLen));

            $out .= '<p>' . $this->iconFactory->getIconForRecord('pages', $mainPageRecord, IconSize::SMALL)->render() . '&nbsp;';
            if (in_array('pages', $tablesArr, true)) {
                $out .= '<span data-uid="' . htmlspecialchars((string)$mainPageRecord['uid']) . '" data-table="pages" data-title="' . htmlspecialchars($mainPageRecord['title']) . '">';
                $out .= '<a href="#" data-close="0">'
                    . $this->iconFactory->getIcon('actions-plus', IconSize::SMALL)->render()
                    . '</a>'
                    . '<a href="#" data-close="1">'
                    . $pText
                    . '</a>';
                $out .= '</span>';
            } else {
                $out .= $pText;
            }
            $out .= '</p>';
        }

        $permsClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $pageInfo = BackendUtility::readPageAccess($this->expandPage, $permsClause);
        $existingModuleData = $backendUser->getModuleData('web_list');
        $moduleData = new ModuleData('web_list', is_array($existingModuleData) ? $existingModuleData : []);

        $dbList = GeneralUtility::makeInstance(ElementBrowserRecordList::class);
        $dbList->setRequest($request);
        $dbList->setModuleData($moduleData);
        $dbList->setOverrideUrlParameters($this->getUrlParameters([]), $request);
        $dbList->setIsEditable(false);
        $dbList->calcPerms = new Permission($backendUser->calcPerms($pageInfo));
        $dbList->noControlPanels = true;
        $dbList->clickMenuEnabled = false;
        $dbList->displayRecordDownload = false;
        $dbList->tableList = implode(',', $tablesArr);

        // a string like "data[pages][79][storage_pid]"
        [$fieldPointerString] = explode('|', $this->bparams);
        // parts like: data, pages], 79], storage_pid]
        $fieldPointerParts = explode('[', $fieldPointerString);

        $relatingTableName = substr(($fieldPointerParts[1] ?? ''), 0, -1);
        $relatingFieldName = substr(($fieldPointerParts[3] ?? ''), 0, -1);

        if ($relatingTableName && $relatingFieldName) {
            $dbList->setRelatingTableAndField($relatingTableName, $relatingFieldName);
        }

        $selectedTable = (string)($request->getParsedBody()['table'] ?? $request->getQueryParams()['table'] ?? '');
        $searchWord = (string)($request->getParsedBody()['searchTerm'] ?? $request->getQueryParams()['searchTerm'] ?? '');
        $searchLevels = (int)($request->getParsedBody()['search_levels'] ?? $request->getQueryParams()['search_levels'] ?? $this->modTSconfig['searchLevel.']['default'] ?? 0);
        $pointer = (int)($request->getParsedBody()['pointer'] ?? $request->getQueryParams()['pointer'] ?? 0);

        $dbList->start(
            $this->expandPage,
            $selectedTable,
            MathUtility::forceIntegerInRange($pointer, 0, 100000),
            $searchWord,
            $searchLevels
        );

        $tableList = $dbList->generateList();

        $out .= $this->renderSearchBox($request, $dbList, $searchWord, $searchLevels);

        // Add the HTML for the record list to output variable:
        $out .= $tableList;

        return $out;
    }

    protected function renderSearchBox(ServerRequestInterface $request, ElementBrowserRecordList $dblist, string $searchWord, int $searchLevels): string
    {
        return GeneralUtility::makeInstance(RecordSearchBoxComponent::class)
            ->setAllowedSearchLevels((array)($this->modTSconfig['searchLevel.']['items.'] ?? []))
            ->setSearchWord($searchWord)
            ->setSearchLevel($searchLevels)
            ->render($request, $dblist->listURL('', '-1', 'pointer,searchTerm'));
    }

    /**
     * @param array $values Array of values to include into the parameters
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values): array
    {
        $pid = $values['pid'] ?? $this->expandPage;
        return [
            'mode' => 'db',
            'expandPage' => $pid,
            'bparams' => $this->bparams,
        ];
    }
}
