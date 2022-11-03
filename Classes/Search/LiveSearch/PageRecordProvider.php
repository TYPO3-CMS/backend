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

namespace TYPO3\CMS\Backend\Search\LiveSearch;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Search\Event\ModifyQueryForLiveSearchEvent;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Search provider to query pages from database
 *
 * @internal
 */
final class PageRecordProvider implements SearchProviderInterface
{
    private const RECURSIVE_PAGE_LEVEL = 99;

    protected LanguageService $languageService;
    protected string $userPermissions;
    protected array $pageIdList = [];

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly IconFactory $iconFactory,
        protected readonly LanguageServiceFactory $languageServiceFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly QueryParser $queryParser,
        protected readonly SiteFinder $siteFinder,
    ) {
        $this->languageService = $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser());
        $this->userPermissions = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
    }

    public function getRendererComponent(): string
    {
        return '@typo3/backend/live-search/element/provider/page-provider-result-item.js';
    }

    public function getFilterLabel(): string
    {
        return $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:liveSearch.pageRecordProvider.filterLabel');
    }

    /**
     * @return ResultItem[]
     */
    public function find(SearchDemand $searchDemand): array
    {
        $this->pageIdList = $this->getPageIdList();

        $result = [];

        if ($this->queryParser->isValidPageJump($searchDemand->getQuery())) {
            $commandQuery = $this->queryParser->getCommandForPageJump($searchDemand->getQuery());
            $extractedQueryString = $this->queryParser->getSearchQueryValue($commandQuery);
            $tableName = $this->queryParser->getTableNameFromCommand($commandQuery);

            if ($tableName !== 'pages') {
                return [];
            }

            return $this->findByTable($extractedQueryString, $searchDemand->getLimit());
        }

        $tableResult = $this->findByTable($searchDemand->getQuery(), $searchDemand->getLimit());

        $result[] = $tableResult;

        return array_merge([], ...$result);
    }

    /**
     * @return ResultItem[]
     */
    protected function findByTable(string $queryString, int $limit): array
    {
        if (!$this->getBackendUser()->check('tables_select', 'pages')) {
            return [];
        }

        $fieldsToSearchWithin = $this->extractSearchableFieldsFromTable();
        if ($fieldsToSearchWithin === []) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);

        $constraints = $this->makeQuerySearchByTable($queryString, $queryBuilder, $fieldsToSearchWithin);
        if ($constraints === []) {
            return [];
        }

        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->or(...$constraints)
            )
            ->setMaxResults($limit);

        if ($this->pageIdList !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($this->pageIdList, Connection::PARAM_INT_ARRAY)
                )
            );
        }

        if ($this->userPermissions) {
            $queryBuilder->andWhere($this->userPermissions);
        }

        $queryBuilder->addOrderBy('uid', 'DESC');
        /** @var ModifyQueryForLiveSearchEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyQueryForLiveSearchEvent($queryBuilder, 'pages'));

        $items = [];
        $result = $event->getQueryBuilder()->executeQuery();
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('pages', $row);
            if (!is_array($row)) {
                continue;
            }

            $flagIconData = [];
            try {
                $site = $this->siteFinder->getSiteByPageId($row['l10n_source'] > 0 ? $row['l10n_source'] : $row['uid']);
                $siteLanguage = $site->getLanguageById($row['sys_language_uid']);
                $flagIconData = [
                    'identifier' => $siteLanguage->getFlagIdentifier(),
                    'title' => $siteLanguage->getTitle(),
                ];
            } catch (SiteNotFoundException|\InvalidArgumentException) {
                // intended fall-thru, perhaps broken data in database or pages without (=deleted) site config
            }

            $icon = $this->iconFactory->getIconForRecord('pages', $row, Icon::SIZE_SMALL);
            $items[] = (new ResultItem(self::class))
                ->setItemTitle(BackendUtility::getRecordTitle('pages', $row))
                ->setTypeLabel($this->languageService->sL($GLOBALS['TCA']['pages']['ctrl']['title']))
                ->setIcon($icon)
                ->setActionUrl($this->getShowLink($row))
                ->setExtraData([
                    'breadcrumb' => BackendUtility::getRecordPath($row['pid'], 'AND ' . $this->userPermissions, 0),
                    'flagIcon' => $flagIconData,
                ])
            ;
        }

        return $items;
    }

    /**
     * List of available page uids for user, empty array for admin users.
     *
     * @return int[]
     */
    protected function getPageIdList(): array
    {
        if ($this->getBackendUser()->isAdmin()) {
            return [];
        }
        $mounts = $this->getBackendUser()->returnWebmounts();
        $pageList = $mounts;
        $repository = GeneralUtility::makeInstance(PageTreeRepository::class);
        $repository->setAdditionalWhereClause($this->userPermissions);
        $pages = $repository->getFlattenedPages([$mounts], self::RECURSIVE_PAGE_LEVEL);
        foreach ($pages as $page) {
            $pageList[] = (int)$page['uid'];
        }
        return $pageList;
    }

    /**
     * Get all fields from given table where we can search for.
     *
     * @return string[]
     */
    protected function extractSearchableFieldsFromTable(): array
    {
        // Get the list of fields to search in from the TCA, if any
        if (isset($GLOBALS['TCA']['pages']['ctrl']['searchFields'])) {
            $fieldListArray = GeneralUtility::trimExplode(',', $GLOBALS['TCA']['pages']['ctrl']['searchFields'], true);
        } else {
            $fieldListArray = [];
        }
        // Add special fields
        if ($this->getBackendUser()->isAdmin()) {
            $fieldListArray[] = 'uid';
            $fieldListArray[] = 'pid';
        }
        return $fieldListArray;
    }

    /**
     * @return CompositeExpression[]
     */
    protected function makeQuerySearchByTable(string $queryString, QueryBuilder $queryBuilder, array $fieldsToSearchWithin): array
    {
        $constraints = [];

        // If the search string is a simple integer, assemble an equality comparison
        if (MathUtility::canBeInterpretedAsInteger($queryString)) {
            foreach ($fieldsToSearchWithin as $fieldName) {
                if ($fieldName !== 'uid'
                    && $fieldName !== 'pid'
                    && !isset($GLOBALS['TCA']['pages']['columns'][$fieldName])
                ) {
                    continue;
                }
                $fieldConfig = $GLOBALS['TCA']['pages']['columns'][$fieldName]['config'] ?? [];
                $fieldType = $fieldConfig['type'] ?? '';

                // Assemble the search condition only if the field is an integer, or is uid or pid
                if ($fieldName === 'uid'
                    || $fieldName === 'pid'
                    || ($fieldType === 'number' && ($fieldConfig['format'] ?? 'integer') === 'integer')
                    || ($fieldType === 'datetime' && !in_array($fieldConfig['dbType'] ?? '', QueryHelper::getDateTimeTypes(), true))
                ) {
                    $constraints[] = $queryBuilder->expr()->eq(
                        $fieldName,
                        $queryBuilder->createNamedParameter($queryString, Connection::PARAM_INT)
                    );
                } elseif ($this->fieldTypeIsSearchable($fieldType)) {
                    // Otherwise and if the field makes sense to be searched, assemble a like condition
                    $constraints[] = $queryBuilder->expr()->like(
                        $fieldName,
                        $queryBuilder->createNamedParameter(
                            '%' . $queryBuilder->escapeLikeWildcards($queryString) . '%'
                        )
                    );
                }
            }
        } else {
            $like = '%' . $queryBuilder->escapeLikeWildcards($queryString) . '%';
            foreach ($fieldsToSearchWithin as $fieldName) {
                if (!isset($GLOBALS['TCA']['pages']['columns'][$fieldName])) {
                    continue;
                }
                $fieldConfig = $GLOBALS['TCA']['pages']['columns'][$fieldName]['config'] ?? [];
                $fieldType = $fieldConfig['type'] ?? '';

                // Check whether search should be case-sensitive or not
                $searchConstraint = $queryBuilder->expr()->and(
                    $queryBuilder->expr()->comparison(
                        'LOWER(' . $queryBuilder->quoteIdentifier($fieldName) . ')',
                        'LIKE',
                        $queryBuilder->createNamedParameter(mb_strtolower($like))
                    )
                );

                if (is_array($fieldConfig['search'] ?? false)) {
                    if (in_array('case', $fieldConfig['search'], true)) {
                        // Replace case insensitive default constraint
                        $searchConstraint = $queryBuilder->expr()->and(
                            $queryBuilder->expr()->like(
                                $fieldName,
                                $queryBuilder->createNamedParameter($like)
                            )
                        );
                    }
                    // Apply additional condition, if any
                    if ($fieldConfig['search']['andWhere'] ?? false) {
                        $searchConstraint = $searchConstraint->with(
                            QueryHelper::stripLogicalOperatorPrefix(QueryHelper::quoteDatabaseIdentifiers($queryBuilder->getConnection(), $fieldConfig['search']['andWhere']))
                        );
                    }
                }
                // Assemble the search condition only if the field makes sense to be searched
                if ($this->fieldTypeIsSearchable($fieldType) && $searchConstraint->count() !== 0) {
                    $constraints[] = $searchConstraint;
                }
            }
        }

        return $constraints;
    }

    protected function fieldTypeIsSearchable(string $fieldType): bool
    {
        $searchableFieldTypes = [
            'input',
            'text',
            'flex',
            'email',
            'link',
            'color',
            'slug',
        ];

        return in_array($fieldType, $searchableFieldTypes, true);
    }

    /**
     * Build a backend edit link based on given record.
     *
     * @param array $row Current record row from database.
     * @return string Link to open an edit window for record.
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess()
     */
    protected function getShowLink(array $row): string
    {
        $backendUser = $this->getBackendUser();
        $showLink = '';
        // "View" link - Only with proper permissions
        if ($backendUser->isAdmin()
            || (
                $this->hasPermissionToView($row)
                && !($GLOBALS['TCA']['pages']['ctrl']['adminOnly'] ?? false)
                && $backendUser->check('tables_select', 'pages')
            )
        ) {
            $showLink = (string)$this->uriBuilder->buildUriFromRoute('web_list', ['id' => $row['pid']]);
        }
        return $showLink;
    }

    protected function hasPermissionToView(array $row): bool
    {
        $localCalcPerms = new Permission($this->getBackendUser()->calcPerms(BackendUtility::getRecord('pages', $row['uid']) ?? []));

        return $localCalcPerms->showPagePermissionIsGranted();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
