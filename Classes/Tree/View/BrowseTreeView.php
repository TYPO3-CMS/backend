<?php

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

namespace TYPO3\CMS\Backend\Tree\View;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generate a page-tree, browsable.
 */
class BrowseTreeView extends AbstractTreeView
{
    /**
     * @var array
     */
    public $fieldArray = [
        'uid',
        'pid',
        'title',
        'doktype',
        'nav_title',
        'mount_pid',
        'php_tree_stop',
        't3ver_state',
        'hidden',
        'starttime',
        'endtime',
        'fe_group',
        'module',
        'extendToSubpages',
        'nav_hide',
        't3ver_wsid',
        't3ver_move_id',
        'is_siteroot'
    ];

    /**
     * override to use this treeName
     * @var string
     */
    public $treeName = 'browsePages';

    /**
     * override to use this table
     * @var string
     */
    public $table = 'pages';

    /**
     * override to use this domIdPrefix
     * @var string
     */
    public $domIdPrefix = 'pages';

    /**
     * @var bool
     */
    public $ext_showNavTitle = false;

    /**
     * Initialize, setting what is necessary for browsing pages.
     * Using the current user.
     *
     * @param string $clause Additional clause for selecting pages.
     * @param string $orderByFields record ORDER BY field
     */
    public function init($clause = '', $orderByFields = '')
    {
        $backendUser = $this->getBackendUser();
        // This will hide records from display - it has nothing to do with user rights!!
        $clauseExcludePidList = '';
        $pidList = $backendUser->getTSConfig()['options.']['hideRecords.']['pages'] ?? '';
        if (!empty($pidList)) {
            if ($pidList = implode(',', GeneralUtility::intExplode(',', $pidList))) {
                $clauseExcludePidList = ' AND pages.uid NOT IN (' . $pidList . ')';
            }
        }
        // This is very important for making trees of pages: Filtering out deleted pages, pages with no access to and sorting them correctly:
        parent::init(' AND deleted=0 AND sys_language_uid=0 AND ' . $backendUser->getPagePermsClause(Permission::PAGE_SHOW) . ' ' . $clause . $clauseExcludePidList, 'sorting');
        $this->title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $this->MOUNTS = $backendUser->returnWebmounts();
        if ($pidList) {
            // Remove mountpoint if explicitly set in options.hideRecords.pages (see above)
            $hideList = explode(',', $pidList);
            $this->MOUNTS = array_diff($this->MOUNTS, $hideList);
        }
    }

    /**
     * Creates title attribute content for pages.
     * Uses API function in \TYPO3\CMS\Backend\Utility\BackendUtility which will retrieve lots of useful information for pages.
     *
     * @param array $row The table row.
     * @return string
     */
    public function getTitleAttrib($row)
    {
        return BackendUtility::titleAttribForPages($row, '1=1 ' . $this->clause, false);
    }

    /**
     * Wrapping the image tag, $icon, for the row, $row (except for mount points)
     *
     * @param string $icon The image tag for the icon
     * @param array $row The row for the current element
     * @return string The processed icon input value.
     * @internal
     */
    public function wrapIcon($icon, $row)
    {
        // Wrap icon in click-menu link.
        $theIcon = '';
        if (!$this->ext_IconMode) {
            $theIcon = BackendUtility::wrapClickMenuOnIcon($icon, $this->treeName, $this->getId($row), '0');
        } elseif ($this->ext_IconMode === 'titlelink') {
            $aOnClick = 'return jumpTo(' . GeneralUtility::quoteJSvalue($this->getJumpToParam($row)) . ',this,'
                        . GeneralUtility::quoteJSvalue($this->domIdPrefix . $this->getId($row)) . ',' . $this->bank . ');';
            $theIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $icon . '</a>';
        }
        return $theIcon;
    }

    /**
     * Returns the title for the input record. If blank, a "no title" label (localized) will be returned.
     * Do NOT htmlspecialchar the string from this function - has already been done.
     *
     * @param array $row The input row array (where the key "title" is used for the title)
     * @param int $titleLen Title length (30)
     * @return string The title.
     */
    public function getTitleStr($row, $titleLen = 30)
    {
        if ($this->ext_showNavTitle && isset($row['nav_title']) && trim($row['nav_title']) !== '') {
            $title = parent::getTitleStr(['title' => $row['nav_title']], $titleLen);
        } else {
            $title = parent::getTitleStr($row, $titleLen);
        }
        if (!empty($row['is_siteroot'])
            && $this->getBackendUser()->getTSConfig()['options.']['pageTree.']['showDomainNameWithTitle'] ?? false
        ) {
            $pageId = (int)$row['uid'];
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            try {
                $site = $siteFinder->getSiteByRootPageId($pageId);
                $title .= ' [' . (string)$site->getBase() . ']';
            } catch (SiteNotFoundException $e) {
                // No site found
            }
        }
        return $title;
    }
}
