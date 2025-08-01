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

namespace TYPO3\CMS\Backend\Utility;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Domain\Model\Element\ImmediateActionElement;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\ItemProcessingService;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\Capability\LanguageAwareSchemaCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\FileFieldType;
use TYPO3\CMS\Core\Schema\Field\JsonFieldType;
use TYPO3\CMS\Core\Schema\Field\NoneFieldType;
use TYPO3\CMS\Core\Schema\Field\PassthroughFieldType;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TypoScript\PageTsConfig;
use TYPO3\CMS\Core\TypoScript\PageTsConfigFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Standard functions available for the TYPO3 backend.
 * You are encouraged to use this class in your own applications (Backend Modules)
 * Don't instantiate - call functions with "\TYPO3\CMS\Backend\Utility\BackendUtility::" prefixed the function name.
 *
 * Call ALL methods without making an object!
 * Eg. to get a page-record 51 do this: '\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages',51)'
 */
class BackendUtility
{
    /*******************************************
     *
     * SQL-related, selecting records, searching
     *
     *******************************************/
    /**
     * Gets record with uid = $uid from $table
     * You can set $field to a list of fields (default is '*')
     * Additional WHERE clauses can be added by $where (fx. ' AND some_field = 1')
     * Will automatically check if records has been deleted and if so, not return anything.
     * $table must be available in Schema API
     *
     * @param string $table Table name, available in Schema API
     * @param int|string $uid UID of record
     * @param string $fields List of fields to select
     * @param string $where Additional WHERE clause, eg. ' AND some_field = 0'
     * @param bool $useDeleteClause Use the deleteClause to check if a record is deleted (default TRUE)
     * @return array|null Returns the row if found, otherwise NULL
     */
    public static function getRecord(string $table, $uid, $fields = '*', $where = '', $useDeleteClause = true): ?array
    {
        if (self::getTcaSchema($table) === null
            || !MathUtility::canBeInterpretedAsInteger($uid)
            || (int)$uid > 2147483647
            || (int)$uid < 1
        ) {
            // Return null early in case there is no TCA, the input is no integer, or an integer is
            // larger than (2^31) - 1. This needs to be increased when we switch to bigint uids.
            // Do not throw an exception because normal operation needs to
            // continue as DataHandler and friends expect 'null' records for these cases.
            return null;
        }
        $uid = (int)$uid;
        $queryBuilder = self::getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        if ($useDeleteClause) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
        $queryBuilder
            ->select(...GeneralUtility::trimExplode(',', $fields, true))
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)));
        if ($where) {
            // Add custom where clause
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($where));
        }
        if ($row = $queryBuilder->executeQuery()->fetchAssociative()) {
            return $row;
        }
        return null;
    }

    /**
     * Like getRecord(), but overlays workspace version if any.
     *
     * @param string $table Table name, available in Schema API
     * @param int $uid UID of record
     * @param string $fields List of fields to select
     * @param string $where Additional WHERE clause, eg. ' AND some_field = 0'
     * @param bool $useDeleteClause Use the deleteClause to check if a record is deleted (default TRUE)
     * @param bool $unsetMovePointers If TRUE the function does not return a "pointer" row for moved records in a workspace
     * @return array|null Returns the row if found, else NULL
     */
    public static function getRecordWSOL(
        $table,
        $uid,
        $fields = '*',
        $where = '',
        $useDeleteClause = true,
        $unsetMovePointers = false
    ): ?array {
        if ($fields !== '*') {
            $internalFields = StringUtility::uniqueList($fields . ',uid,pid');
            $row = self::getRecord($table, $uid, $internalFields, $where, $useDeleteClause);
            self::workspaceOL($table, $row, -99, $unsetMovePointers);
            if (is_array($row)) {
                foreach ($row as $key => $_) {
                    if (!GeneralUtility::inList($fields, $key) && $key[0] !== '_') {
                        unset($row[$key]);
                    }
                }
            }
        } else {
            $row = self::getRecord($table, $uid, $fields, $where, $useDeleteClause);
            self::workspaceOL($table, $row, -99, $unsetMovePointers);
        }
        return $row;
    }

    /**
     * Purges computed properties starting with underscore character ('_').
     *
     * @param array<string,mixed> $record
     * @return array<string,mixed>
     * @internal should only be used from within TYPO3 Core
     */
    public static function purgeComputedPropertiesFromRecord(array $record): array
    {
        return array_filter(
            $record,
            static function (string $propertyName): bool {
                return $propertyName[0] !== '_';
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Purges computed property names starting with underscore character ('_').
     *
     * @internal should only be used from within TYPO3 Core
     */
    public static function purgeComputedPropertyNames(array $propertyNames): array
    {
        return array_filter(
            $propertyNames,
            static function (string $propertyName): bool {
                return $propertyName[0] !== '_';
            }
        );
    }

    /**
     * Makes a backwards explode on the $str and returns an array with ($table, $uid).
     * Example: tt_content_45 => ['tt_content', 45]
     *
     * @param string $str [tablename]_[uid] string to explode
     * @return array
     * @internal should only be used from within TYPO3 Core
     */
    public static function splitTable_Uid($str)
    {
        $split = explode('_', strrev($str), 2);
        $uid = $split[0];
        $table = $split[1] ?? '';
        return [strrev($table), strrev($uid)];
    }

    /**
     * Backend implementation of enableFields()
     * Notice that "fe_groups" is not selected for - only disabled, starttime and endtime.
     * Notice that deleted-fields are NOT filtered - you must ALSO call deleteClause in addition.
     * $GLOBALS["SIM_ACCESS_TIME"] is used for date.
     *
     * @param string $table The table from which to return enableFields WHERE clause. Table name must have a valid configuration.
     * @param bool $inv Means that the query will select all records NOT VISIBLE records (inverted selection)
     * @return string WHERE clause part
     * @internal should only be used from within TYPO3 Core, but DefaultRestrictionHandler is recommended as alternative
     */
    public static function BEenableFields(string $table, $inv = false): string
    {
        $schema = self::getTcaSchema($table);
        if ($schema === null) {
            return '';
        }
        $expressionBuilder = self::getConnectionForTable($table)
            ->getExpressionBuilder();
        $query = $expressionBuilder->and();
        $invQuery = $expressionBuilder->or();

        if ($schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
            $field = $table . '.' . $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName();
            $query = $query->with($expressionBuilder->eq($field, 0));
            $invQuery = $invQuery->with($expressionBuilder->neq($field, 0));
        }
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionStartTime)) {
            $field = $table . '.' . $schema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName();
            $query = $query->with($expressionBuilder->lte($field, (int)$GLOBALS['SIM_ACCESS_TIME']));
            $invQuery = $invQuery->with(
                $expressionBuilder->and(
                    $expressionBuilder->neq($field, 0),
                    $expressionBuilder->gt($field, (int)$GLOBALS['SIM_ACCESS_TIME'])
                )
            );
        }
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionEndTime)) {
            $field = $table . '.' . $schema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName();
            $query = $query->with(
                $expressionBuilder->or(
                    $expressionBuilder->eq($field, 0),
                    $expressionBuilder->gt($field, (int)$GLOBALS['SIM_ACCESS_TIME'])
                )
            );
            $invQuery = $invQuery->with(
                $expressionBuilder->and(
                    $expressionBuilder->neq($field, 0),
                    $expressionBuilder->lte($field, (int)$GLOBALS['SIM_ACCESS_TIME'])
                )
            );
        }

        if ($query->count() === 0) {
            return '';
        }

        return ' AND ' . ($inv ? $invQuery : $query);
    }

    /**
     * Fetches the localization for a given record.
     *
     * @param string $table Table name, available in Schema API
     * @param int $uid The uid of the record
     * @param int $language The id of the site language
     * @param string $andWhereClause Optional additional WHERE clause (default: '')
     * @return mixed Multidimensional array with selected records, empty array if none exists and FALSE if table is not localizable
     */
    public static function getRecordLocalization(string $table, $uid, $language, $andWhereClause = '')
    {
        $recordLocalization = false;
        $schema = static::getTcaSchema($table);
        if ($schema !== null && $schema->hasCapability(TcaSchemaCapability::Language)) {
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $queryBuilder = self::getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, static::getBackendUserAuthentication()->workspace));

            $queryBuilder->select('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $languageCapability->hasTranslationSourceField() ? $languageCapability->getTranslationSourceField()->getName() : $languageCapability->getTranslationOriginPointerField()->getName(),
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $languageCapability->getLanguageField()->getName(),
                        $queryBuilder->createNamedParameter((int)$language, Connection::PARAM_INT)
                    )
                )
                ->setMaxResults(1);

            if ($andWhereClause) {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($andWhereClause));
            }

            $recordLocalization = $queryBuilder->executeQuery()->fetchAllAssociative();
        }

        return $recordLocalization;
    }

    /*******************************************
     *
     * Page tree, TCA related
     *
     *******************************************/
    /**
     * Returns what is called the 'RootLine'. That is an array with information about the page records from a page id
     * ($uid) and back to the root.
     * By default deleted pages are filtered.
     * This RootLine will follow the tree all the way to the root. This is opposite to another kind of root line known
     * from the frontend where the rootline stops when a root-template is found.
     *
     * @param int $uid Page id for which to create the root line.
     * @param string $clause Clause can be used to select other criteria. It would typically be where-clauses that
     *          stops the process if we meet a page, the user has no reading access to.
     * @param bool $workspaceOL If TRUE, version overlay is applied. This must be requested specifically because it is
     *          usually only wanted when the rootline is used for visual output while for permission checking you want the raw thing!
     * @param string[] $additionalFields Additional Fields to select for rootline records
     * @param bool $useDeleteClause Use the deleteClause to check if a record is deleted (default TRUE)
     * @return array Root line array, all the way to the page tree root uid=0 (or as far as $clause allows!), including the page given as $uid
     */
    public static function BEgetRootLine($uid, $clause = '', $workspaceOL = false, array $additionalFields = [], bool $useDeleteClause = true)
    {
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $beGetRootLineCache = $runtimeCache->get('backendUtilityBeGetRootLine') ?: [];
        $output = [];
        $pid = $uid;
        $ident = $pid . '-' . $clause . '-' . $workspaceOL . ($additionalFields ? '-' . md5(implode(',', $additionalFields)) : '');
        if (is_array($beGetRootLineCache[$ident] ?? false)) {
            $output = $beGetRootLineCache[$ident];
        } else {
            $loopCheck = 100;
            $theRowArray = [];
            while ($uid != 0 && $loopCheck) {
                $loopCheck--;
                $row = self::getPageForRootline($uid, $clause, $workspaceOL, $additionalFields, $useDeleteClause);
                if (is_array($row)) {
                    $uid = $row['pid'];
                    $theRowArray[] = $row;
                } else {
                    break;
                }
            }
            $fields = [
                'uid',
                'pid',
                'title',
                'doktype',
                'slug',
                'tsconfig_includes',
                'TSconfig',
                'is_siteroot',
                't3ver_oid',
                't3ver_wsid',
                't3ver_state',
                't3ver_stage',
                'backend_layout',
                'backend_layout_next_level',
                'hidden',
                'starttime',
                'endtime',
                'fe_group',
                'nav_hide',
                'content_from_pid',
                'module',
                'extendToSubpages',
            ];
            $fields = array_merge($fields, $additionalFields);
            $rootPage = array_fill_keys($fields, null);
            if ($uid == 0) {
                $rootPage['uid'] = 0;
                $theRowArray[] = $rootPage;
            }
            $c = count($theRowArray);
            foreach ($theRowArray as $val) {
                $c--;
                $output[$c] = array_intersect_key($val, $rootPage);
                if (isset($val['_ORIG_pid'])) {
                    $output[$c]['_ORIG_pid'] = $val['_ORIG_pid'];
                }
            }
            $beGetRootLineCache[$ident] = $output;
            $runtimeCache->set('backendUtilityBeGetRootLine', $beGetRootLineCache);
        }
        return $output;
    }

    /**
     * Gets the cached page record for the rootline
     *
     * @param int $uid Page id for which to create the root line.
     * @param string $clause Clause can be used to select other criteria. It would typically be where-clauses that stops the process if we meet a page, the user has no reading access to.
     * @param bool $workspaceOL If TRUE, version overlay is applied. This must be requested specifically because it is usually only wanted when the rootline is used for visual output while for permission checking you want the raw thing!
     * @param string[] $additionalFields AdditionalFields to fetch from the root line
     * @param bool $useDeleteClause Use the deleteClause to check if a record is deleted (default TRUE)
     * @return array Cached page record for the rootline
     * @see BEgetRootLine
     */
    protected static function getPageForRootline($uid, $clause, $workspaceOL, array $additionalFields = [], bool $useDeleteClause = true)
    {
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $pageForRootlineCache = $runtimeCache->get('backendUtilityPageForRootLine') ?: [];
        $statementCacheIdent = md5($clause . ($additionalFields ? '-' . implode(',', $additionalFields) : '') . ($useDeleteClause ? '-delete' : ''));
        $ident = $uid . '-' . $workspaceOL . '-' . $statementCacheIdent;
        if (is_array($pageForRootlineCache[$ident] ?? false)) {
            $row = $pageForRootlineCache[$ident];
        } else {
            /** @var Statement $statement */
            $statement = $runtimeCache->get('getPageForRootlineStatement-' . $statementCacheIdent);
            if (!$statement) {
                $queryBuilder = self::getQueryBuilderForTable('pages');
                $queryBuilder->getRestrictions()->removeAll();
                if ($useDeleteClause) {
                    $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                }
                $queryBuilder
                    ->select(
                        'pid',
                        'uid',
                        'title',
                        'doktype',
                        'slug',
                        'tsconfig_includes',
                        'TSconfig',
                        'is_siteroot',
                        't3ver_oid',
                        't3ver_wsid',
                        't3ver_state',
                        't3ver_stage',
                        'backend_layout',
                        'backend_layout_next_level',
                        'hidden',
                        'starttime',
                        'endtime',
                        'fe_group',
                        'nav_hide',
                        'content_from_pid',
                        'module',
                        'extendToSubpages',
                        ...$additionalFields
                    )
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createPositionalParameter($uid, Connection::PARAM_INT)),
                        QueryHelper::stripLogicalOperatorPrefix($clause)
                    );
                $statement = $queryBuilder->prepare();
                $runtimeCache->set('getPageForRootlineStatement-' . $statementCacheIdent, $statement);
            }

            $statement->bindValue(1, (int)$uid, Connection::PARAM_INT);
            $result = $statement->executeQuery();
            $row = $result->fetchAssociative();
            $result->free();

            if ($row) {
                if ($workspaceOL) {
                    self::workspaceOL('pages', $row);
                }
                if (is_array($row)) {
                    $pageForRootlineCache[$ident] = $row;
                    $runtimeCache->set('backendUtilityPageForRootLine', $pageForRootlineCache);
                }
            }
        }
        return $row;
    }

    /**
     * Fetch all records of the given page ID.
     * Does not check permissions.
     *
     * @internal
     */
    public static function getExistingPageTranslations(int $pageUid): array
    {
        if ($pageUid === 0 || !($schema = self::getTcaSchema('pages'))?->hasCapability(TcaSchemaCapability::Language)) {
            return [];
        }
        $queryBuilder = self::getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, self::getBackendUserAuthentication()->workspace));
        $result = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName(),
                    $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)
                )
            )
            ->executeQuery();

        $rows = [];
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('pages', $row, self::getBackendUserAuthentication()->workspace);
            if ($row && VersionState::tryFrom($row['t3ver_state']) !== VersionState::DELETE_PLACEHOLDER) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Opens the page tree to the specified page id
     *
     * @param int $pid Page id.
     * @param bool $clearExpansion If set, then other open branches are closed.
     * @internal should only be used from within TYPO3 Core
     */
    public static function openPageTree($pid, $clearExpansion)
    {
        $beUser = static::getBackendUserAuthentication();
        // Get current expansion data:
        if ($clearExpansion) {
            $expandedPages = [];
        } else {
            $expandedPages = $beUser->uc['BackendComponents']['States']['Pagetree']['stateHash'] ?? [];
        }
        // Get rootline:
        $rL = self::BEgetRootLine($pid);
        // First, find out what mount index to use (if more than one Page Tree Entry Point exists):
        $mountIndex = 0;
        $mountKeys = $beUser->getWebmounts();

        foreach ($rL as $rLDat) {
            if (isset($mountKeys[$rLDat['uid']])) {
                $mountIndex = $mountKeys[$rLDat['uid']];
                break;
            }
        }
        // Traverse rootline and open paths:
        foreach ($rL as $rLDat) {
            $expandedPages[$mountIndex . '_' . $rLDat['uid']] = '1';
        }
        // Write back:
        $beUser->uc['BackendComponents']['States']['Pagetree']['stateHash'] = $expandedPages;
        $beUser->writeUC();
    }

    /**
     * Returns the path (visually) of a page $uid, fx. "/First page/Second page/Another subpage"
     * Each part of the path will be limited to $titleLimit characters
     * Deleted pages are filtered out.
     *
     * @param int $uid Page uid for which to create record path
     * @param string $clause Clause is additional where clauses, eg.
     * @param int $titleLimit Title limit
     * @param int $fullTitleLimit Title limit of Full title (typ. set to 1000 or so)
     * @return mixed Path of record (string) OR array with short/long title if $fullTitleLimit is set.
     */
    public static function getRecordPath($uid, $clause, $titleLimit, $fullTitleLimit = 0)
    {
        if (!$titleLimit) {
            $titleLimit = 1000;
        }
        $output = $fullOutput = '/';
        $clause = trim($clause);
        if ($clause !== '' && !str_starts_with($clause, 'AND')) {
            $clause = 'AND ' . $clause;
        }
        $data = self::BEgetRootLine($uid, $clause, true);
        foreach ($data as $record) {
            if ($record['uid'] === 0) {
                continue;
            }
            $output = '/' . GeneralUtility::fixed_lgd_cs(strip_tags($record['title']), (int)$titleLimit) . $output;
            if ($fullTitleLimit) {
                $fullOutput = '/' . GeneralUtility::fixed_lgd_cs(strip_tags($record['title']), (int)$fullTitleLimit) . $fullOutput;
            }
        }
        if ($fullTitleLimit) {
            return [$output, $fullOutput];
        }
        return $output;
    }

    /**
     * Determines whether a table is localizable and has the languageField and transOrigPointerField set.
     *
     * @param string $table The table to check
     * @return bool Whether a table is localizable
     * @deprecated since TYPO3 v14.0, will be removed in TYPO3 v15.0.
     */
    public static function isTableLocalizable(string $table): bool
    {
        trigger_error(
            'BackendUtility::isTableLocalizable() has been deprecated in TYPO3 v14.0 and will be removed in v15.0. Use Schema API with $schema->hasCapability(TcaSchemaCapability::Language) instead.',
            E_USER_DEPRECATED
        );

        return (bool)self::getTcaSchema($table)?->hasCapability(TcaSchemaCapability::Language);
    }

    /**
     * Returns a page record (of page with $id) with an extra field "_thePath" set to the record path if
     * the WHERE clause $perms_clause selects the record. This works as an access check that returns a page
     * record if access was granted, otherwise false.
     * If $id is zero a pseudo root-page with "_thePath" set is returned IF the current BE_USER is admin.
     * In any case ->isInWebMount must return TRUE for the user (regardless of $perms_clause)
     *
     * @param int $id Page uid for which to check read-access
     * @param string $perms_clause This is typically a value generated with static::getBackendUserAuthentication()->getPagePermsClause(1);
     * @return array|false Returns page record if OK, otherwise FALSE.
     */
    public static function readPageAccess($id, $perms_clause)
    {
        if ((string)$id !== '') {
            $id = (int)$id;
            if (!$id) {
                if (static::getBackendUserAuthentication()->isAdmin()) {
                    return ['_thePath' => '/'];
                }
            } else {
                $pageinfo = self::getRecord('pages', $id, '*', $perms_clause);
                if (($pageinfo['uid'] ?? false) && static::getBackendUserAuthentication()->isInWebMount($pageinfo, $perms_clause)) {
                    self::workspaceOL('pages', $pageinfo);
                    if (is_array($pageinfo)) {
                        [$pageinfo['_thePath'], $pageinfo['_thePathFull']] = self::getRecordPath((int)$pageinfo['uid'], $perms_clause, 15, 1000);
                        return $pageinfo;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Returns the "type" value of $rec from $table which can be used to look up the correct "types" rendering section
     * If the schema does not support sub schemata, zero is used.
     * If zero is not a valid sub schemata for the table, then the $fieldValue returned will default to 1 (no matter if that is valid or not)
     *
     * Note: This method is very similar to the type determination of FormDataProvider/DatabaseRecordTypeValue,
     * however, it has two differences:
     * 1) The method in TCEForms also takes care of localization (which is difficult to do here as the whole infrastructure for language overlays is only in TCEforms).
     * 2) The $row array looks different in TCEForms, as in there it's not the raw record but the prepared data from other providers is handled, which changes e.g. how "select"
     * and "group" field values are stored, which makes different processing of the "foreign pointer field" type field variant necessary.
     *
     * @param string $table Table name present in TCA
     * @param array $row Record from $table
     * @throws \RuntimeException
     * @return string Field value
     */
    public static function getTCAtypeValue($table, $row)
    {
        $typeNum = null;
        $schema = self::getTcaSchema($table);
        if ($schema) {
            if ($schema->supportsSubSchema()) {
                $subSchemaInformation = $schema->getSubSchemaTypeInformation();
                if ($subSchemaInformation->isPointerToForeignFieldInForeignSchema()) {
                    $pointerField = $subSchemaInformation->getFieldName();
                    // Check if the record has been persisted already
                    $foreignUid = 0;
                    if (isset($row['uid'])) {
                        // Get field value from database if field is not in the $row array
                        if (!isset($row[$subSchemaInformation->getFieldName()])) {
                            $localRow = self::getRecord($table, $row['uid'], $pointerField);
                            $foreignUid = $localRow[$pointerField] ?? 0;
                        } else {
                            $foreignUid = $row[$pointerField];
                        }
                    }
                    if ($foreignUid) {
                        if (($foreignSchema = self::getTcaSchema($subSchemaInformation->getForeignSchemaName())) !== null) {
                            if (isset($row[$pointerField]) && $foreignSchema->hasField($subSchemaInformation->getForeignFieldName())) {
                                $foreignRecord = self::getRecord($subSchemaInformation->getForeignSchemaName(), $row[$pointerField], $subSchemaInformation->getForeignFieldName());
                                $typeNum = $foreignRecord[$subSchemaInformation->getForeignFieldName()] ?? null;
                            }
                        }
                    }
                } else {
                    $typeNum = $row[$subSchemaInformation->getFieldName()] ?? null;
                }
            }
        }
        // If typeNum is an array, e.g. when using type=select and renderType=selectCheckBox, use the
        // first value or fallback to NULL in case multiple values are selected - which does not work.
        if (is_array($typeNum)) {
            $typeNum = count($typeNum) === 1 ? reset($typeNum) : null;
        }
        // If current typeNum doesn't exist, set it to 0 (or to 1 for historical reasons, if 0 doesn't exist)
        // @todo Resolve this
        if ($typeNum === null || !$schema?->hasSubSchema((string)$typeNum)) {
            $typeNum = $schema?->hasSubSchema('0') ? '0' : '1';
        }
        // Force to string. Necessary for eg '-1' to be recognized as a type value.
        $typeNum = (string)$typeNum;
        return $typeNum;
    }

    /*******************************************
     *
     * TypoScript related
     *
     *******************************************/

    /**
     * Returns the page TSconfig for page with uid $pageUid.
     *
     * This method tends to be called by casual backend controllers multiple times
     * with the same page uid, it has a runtime cache to short circuit this.
     *
     * The DataHandler however tends to also call this for different page uids when
     * doing bulk operations like multi row updates or imports, for instance by
     * the cache flush logic. FormEngine can trigger this as well when editing
     * multiple pages at once.
     *
     * A single-level page uid -> PageTsConfig cache can be pretty memory
     * hungry since the PageTsConfig object can be relatively huge. To prevent
     * this method from being a memory hog, a two-level-cache is implemented:
     * Many pages typically share the same page TSconfig. We get the rootline
     * of a page, and create a hash from the two relevant TSconfig and
     * tsconfig_includes fields, the attached site identifier, plus the hash of
     * matched conditions. We then store a hash-to-object cache entry per
     * different hash, and a page uid-to-hash pointer.
     *
     * @param int $pageUid
     */
    public static function getPagesTSconfig($pageUid): array
    {
        $runtimeCache = static::getRuntimeCache();
        $pageTsConfigHash = $runtimeCache->get('pageTsConfig-pid-to-hash-' . $pageUid);
        if ($pageTsConfigHash) {
            $pageTsConfig = $runtimeCache->get('pageTsConfig-hash-to-object-' . $pageTsConfigHash);
            if ($pageTsConfig instanceof PageTsConfig) {
                return $pageTsConfig->getPageTsConfigArray();
            }
        }

        $pageUid = (int)$pageUid;
        $fullRootLine = self::BEgetRootLine($pageUid, '', true);
        // Order correctly
        ksort($fullRootLine);

        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageUid);
        } catch (SiteNotFoundException) {
            $site = new NullSite();
        }

        $pageTsConfigFactory = GeneralUtility::makeInstance(PageTsConfigFactory::class);
        $pageTsConfig = $pageTsConfigFactory->create($fullRootLine, $site, static::getBackendUserAuthentication()?->getUserTsConfig());

        $cacheRelevantData = $site->getIdentifier() . json_encode($pageTsConfig->getConditionListWithVerdicts(), JSON_THROW_ON_ERROR);
        foreach ($fullRootLine as $rootLine) {
            if (!empty($rootLine['TSconfig'])) {
                $cacheRelevantData .= (string)$rootLine['TSconfig'];
            }
            if (!empty($rootLine['tsconfig_includes'])) {
                $cacheRelevantData .= (string)$rootLine['tsconfig_includes'];
            }
        }
        $pageTsConfigHash = hash('xxh3', $cacheRelevantData);

        $runtimeCache->set('pageTsConfig-pid-to-hash-' . $pageUid, $pageTsConfigHash);
        $runtimeCache->set('pageTsConfig-hash-to-object-' . $pageTsConfigHash, $pageTsConfig);
        return $pageTsConfig->getPageTsConfigArray();
    }

    /*******************************************
     *
     * Users / Groups related
     *
     *******************************************/
    /**
     * Returns an array with be_users records of all user NOT DELETED sorted by their username
     * Keys in the array is the be_users uid
     *
     * @param string $fields Optional $fields list (default: username,usergroup,uid) can be used to set the selected fields
     * @param string $where Optional $where clause (fx. "AND username='pete'") can be used to limit query
     * @return array
     * @internal should only be used from within TYPO3 Core, use a direct SQL query instead to ensure proper DBAL where statements
     */
    public static function getUserNames($fields = 'username,usergroup,uid', $where = '')
    {
        return self::getRecordsSortedByTitle(
            GeneralUtility::trimExplode(',', $fields, true),
            'be_users',
            'username',
            'AND pid=0 ' . $where
        );
    }

    /**
     * Returns an array with be_groups records (title, uid) of all groups NOT DELETED sorted by their title
     *
     * @param string $fields Field list
     * @param string $where WHERE clause
     * @return array
     * @internal should only be used from within TYPO3 Core, use a direct SQL query instead to ensure proper DBAL where statements
     */
    public static function getGroupNames($fields = 'title,uid', $where = '')
    {
        return self::getRecordsSortedByTitle(
            GeneralUtility::trimExplode(',', $fields, true),
            'be_groups',
            'title',
            'AND pid=0 ' . $where
        );
    }

    /**
     * Returns an array of all non-deleted records of a table sorted by a given title field.
     * The value of the title field will be replaced by the return value
     * of self::getRecordTitle() before the sorting is performed.
     *
     * @param array $fields Fields to select
     * @param string $table Table name
     * @param string $titleField Field that will contain the record title
     * @param string $where Additional where clause
     * @return array Array of sorted records
     */
    protected static function getRecordsSortedByTitle(array $fields, string $table, $titleField, $where = ''): array
    {
        $fieldsIndex = array_flip($fields);
        // Make sure the titleField is amongst the fields when getting sorted
        $fieldsIndex[$titleField] = 1;

        $result = [];

        $queryBuilder = self::getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $res = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(QueryHelper::stripLogicalOperatorPrefix($where))
            ->executeQuery();

        while ($record = $res->fetchAssociative()) {
            // store the uid, because it might be unset if it's not among the requested $fields
            $recordId = (int)$record['uid'];
            $record[$titleField] = self::getRecordTitle($table, $record);

            // include only the requested fields in the result
            $result[$recordId] = array_intersect_key($record, $fieldsIndex);
        }

        // sort records by $sortField. This is not done in the query because the title might have been overwritten by
        // self::getRecordTitle();
        return ArrayUtility::sortArraysByKey($result, $titleField);
    }

    /*******************************************
     *
     * Output related
     *
     *******************************************/
    /**
     * Returns the difference in days between input $tstamp and $EXEC_TIME
     *
     * @param int $tstamp Time stamp, seconds
     * @return int
     */
    public static function daysUntil($tstamp)
    {
        $delta_t = $tstamp - $GLOBALS['EXEC_TIME'];
        return ceil($delta_t / (3600 * 24));
    }

    /**
     * Returns $tstamp formatted as "ddmmyy" (According to $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'])
     *
     * @param int $tstamp Time stamp, seconds
     * @return string Formatted time
     */
    public static function date($tstamp)
    {
        return date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], (int)$tstamp);
    }

    /**
     * Returns $tstamp formatted as "ddmmyy hhmm" (According to $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] AND $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'])
     *
     * @param int $value Time stamp, seconds
     * @return string Formatted time
     */
    public static function datetime($value)
    {
        return date(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
            $value
        );
    }

    /**
     * Returns $value (in seconds) formatted as hh:mm:ss
     * For instance $value = 3600 + 60*2 + 3 should return "01:02:03"
     *
     * @param int $value Time stamp, seconds
     * @param bool $withSeconds Output hh:mm:ss. If FALSE: hh:mm
     * @return string Formatted time
     */
    public static function time($value, $withSeconds = true)
    {
        return gmdate('H:i' . ($withSeconds ? ':s' : ''), (int)$value);
    }

    /**
     * Returns the "age" in minutes / hours / days / years of the number of $seconds inputted.
     *
     * @param int $seconds Seconds is the difference of current time() and a certain timestamp
     * @param string $labels Labels should be something like ' min| hrs| days| yrs| min| hour| day| year'. This value is typically delivered by this function call: $GLOBALS["LANG"]->sL("LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears")
     * @return string Formatted time
     */
    public static function calcAge($seconds, $labels = 'min|hrs|days|yrs|min|hour|day|year')
    {
        $now = DateTimeFactory::createFromTimestamp($GLOBALS['EXEC_TIME']);
        $then = DateTimeFactory::createFromTimestamp($GLOBALS['EXEC_TIME'] - $seconds);
        // Show past dates without a leading sign, but future dates with.
        // This does not make sense, but is kept for legacy reasons.
        $sign = $then > $now ? '-' : '';
        // Take an absolute diff, since we don't want formatDateInterval to output the (correct) sign
        $diff = $now->diff($then, true);
        return $sign . (new DateFormatter())->formatDateInterval($diff, $labels);
    }

    /**
     * Returns a formatted timestamp if $tstamp is set.
     * The date/datetime will be followed by the age in parenthesis.
     *
     * @param int $tstamp Time stamp, seconds
     * @param int $prefix 1/-1 depending on polarity of age.
     * @param string $date $date=="date" will yield "dd:mm:yy" formatting, otherwise "dd:mm:yy hh:mm
     * @return string
     */
    public static function dateTimeAge($tstamp, $prefix = 1, $date = '')
    {
        if (!$tstamp) {
            return '';
        }
        $label = static::getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears');
        $age = ' (' . self::calcAge($prefix * ($GLOBALS['EXEC_TIME'] - $tstamp), $label) . ')';
        return ($date === 'date' ? self::date($tstamp) : self::datetime($tstamp)) . $age;
    }

    /**
     * Resolves file references for a given record.
     *
     * @param string $tableName Name of the table of the record
     * @param string $fieldName Name of the field of the record
     * @param array $element Record data
     * @param int|null $workspaceId Workspace to fetch data for
     * @return \TYPO3\CMS\Core\Resource\FileReference[]|null
     * @deprecated since TYPO3 v14.0, will be removed in TYPO3 v15.0.
     */
    public static function resolveFileReferences($tableName, $fieldName, $element, $workspaceId = null)
    {
        trigger_error(
            'BackendUtility::resolveFileReferences() has been deprecated in TYPO3 v14.0 and will be removed in v15.0.',
            E_USER_DEPRECATED
        );

        if (!($schema = self::getTcaSchema($tableName))?->hasField($fieldName)) {
            return null;
        }
        $field = $schema->getField($fieldName);
        if ($field instanceof FileFieldType === false) {
            return null;
        }

        $fileReferences = [];
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        if ($workspaceId !== null) {
            $relationHandler->setWorkspaceId($workspaceId);
        }
        $relationHandler->initializeForField(
            $tableName,
            $field->getConfiguration(),
            $element,
            $element[$fieldName],
        );
        $relationHandler->processDeletePlaceholder();
        $referenceUids = $relationHandler->tableArray[$field->getConfiguration()['foreign_table']] ?? [];

        foreach ($referenceUids as $referenceUid) {
            try {
                $fileReference = GeneralUtility::makeInstance(ResourceFactory::class)->getFileReferenceObject(
                    $referenceUid,
                    [],
                    $workspaceId === 0
                );
                $fileReferences[$fileReference->getUid()] = $fileReference;
            } catch (FileDoesNotExistException $e) {
                /**
                 * We just catch the exception here
                 * Reasoning: There is nothing an editor or even admin could do
                 */
            } catch (\InvalidArgumentException $e) {
                /**
                 * The storage does not exist anymore
                 * Log the exception message for admins as they maybe can restore the storage
                 */
                self::getLogger()->error($e->getMessage(), [
                    'table' => $tableName,
                    'fieldName' => $fieldName,
                    'referenceUid' => $referenceUid,
                    'exception' => $e,
                ]);
            }
        }

        return $fileReferences;
    }

    /**
     * Returns title-attribute information for a page-record informing about id, doktype, hidden, starttime, endtime, fe_group etc.
     *
     * @param array $row Input must be a page row ($row) with the proper fields set (be sure - send the full range of fields for the table)
     * @param string $perms_clause This is used to get the record path of the shortcut page, if any (and doktype==4)
     * @param bool $includeAttrib If $includeAttrib is set, then the 'title=""' attribute is wrapped about the return value, which is in any case htmlspecialchar()'ed already
     * @param bool $preferNavTitle Prefers the 'nav_title' if available over the 'title' (nessesary for Tree with options.pageTree.showNavTitle = 1)
     * @return string
     */
    public static function titleAttribForPages($row, $perms_clause = '', $includeAttrib = true, bool $preferNavTitle = false)
    {
        $schema = self::getTcaSchema('pages');
        if (!isset($row['uid']) || $schema === null) {
            return '';
        }
        $lang = static::getLanguageService();
        $parts = [];
        $parts[] = 'id=' . $row['uid'];
        if ($preferNavTitle && trim($row['nav_title'] ?? '') !== '') {
            $parts[] = $row['nav_title'];
        } else {
            $parts[] = $row['title'];
        }
        if ($row['uid'] === 0) {
            $out = implode(' - ', $parts);
            return $includeAttrib ? 'title="' . htmlspecialchars($out) . '"' : $out;
        }
        switch (VersionState::tryFrom($row['t3ver_state'] ?? 0)) {
            case VersionState::DELETE_PLACEHOLDER:
                $parts[] = 'Deleted element!';
                break;
            case VersionState::MOVE_POINTER:
                $parts[] = 'NEW LOCATION (Move-to Pointer) WSID#' . $row['t3ver_wsid'];
                break;
            case VersionState::NEW_PLACEHOLDER:
                $parts[] = 'New element!';
                break;
        }
        if ($row['doktype'] == PageRepository::DOKTYPE_LINK) {
            $parts[] = $lang->sL($schema->hasField('url') ? $schema->getField('url')->getLabel() : '') . ' ' . ($row['url'] ?? '');
        } elseif ($row['doktype'] == PageRepository::DOKTYPE_SHORTCUT) {
            if ($perms_clause) {
                $label = self::getRecordPath((int)($row['shortcut'] ?? 0), $perms_clause, 20);
            } else {
                $row['shortcut'] = (int)($row['shortcut'] ?? 0);
                $lRec = self::getRecordWSOL('pages', $row['shortcut'], 'title');
                $label = ($lRec['title'] ?? '') . ' (id=' . $row['shortcut'] . ')';
            }
            if (($row['shortcut_mode'] ?? 0) != PageRepository::SHORTCUT_MODE_NONE) {
                $label .= ', ' . $lang->sL($schema->hasField('shortcut_mode') ? $schema->getField('shortcut_mode')->getLabel() : '') . ' '
                    . $lang->sL(self::getLabelFromItemlist('pages', 'shortcut_mode', $row['shortcut_mode'], $row));
            }
            $parts[] = $lang->sL($schema->hasField('shortcut') ? $schema->getField('shortcut')->getLabel() : '') . ' ' . $label;
        } elseif ($row['doktype'] == PageRepository::DOKTYPE_MOUNTPOINT) {
            if ((int)$row['mount_pid'] > 0) {
                if ($perms_clause) {
                    $label = self::getRecordPath((int)$row['mount_pid'], $perms_clause, 20);
                } else {
                    $lRec = self::getRecordWSOL('pages', (int)$row['mount_pid'], 'title');
                    $label = ($lRec['title'] ?? '') . ' (id=' . $row['mount_pid'] . ')';
                }
                $parts[] = $lang->sL($schema->hasField('mount_pid') ? $schema->getField('mount_pid')->getLabel() : '') . ' ' . $label;
                if ($row['mount_pid_ol'] ?? 0) {
                    $parts[] = $lang->sL($schema->hasField('mount_pid_ol') ? $schema->getField('mount_pid_ol')->getLabel() : '');
                }
            } else {
                $parts[] = $lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:no_mount_pid');
            }
        }
        if ($row['nav_hide']) {
            $parts[] = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:pages.nav_hide');
        }
        if ($row['hidden']) {
            $parts[] = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.hidden');
        }
        if ($row['starttime']) {
            $parts[] = $lang->sL($schema->hasField('starttime') ? $schema->getField('starttime')->getLabel() : '')
                . ' ' . self::dateTimeAge($row['starttime'], -1, 'date');
        }
        if ($row['endtime']) {
            $parts[] = $lang->sL($schema->hasField('endtime') ? $schema->getField('endtime')->getLabel() : '') . ' '
                . self::dateTimeAge($row['endtime'], -1, 'date');
        }
        if ($row['fe_group']) {
            $fe_groups = [];
            foreach (GeneralUtility::intExplode(',', (string)$row['fe_group']) as $fe_group) {
                if ($fe_group < 0) {
                    $fe_groups[] = $lang->sL(self::getLabelFromItemlist('pages', 'fe_group', (string)$fe_group, $row));
                } else {
                    $lRec = self::getRecordWSOL('fe_groups', $fe_group, 'title');
                    if (is_array($lRec)) {
                        $fe_groups[] = $lRec['title'] ?? '';
                    }
                }
            }
            $label = implode(', ', $fe_groups);
            $parts[] = $lang->sL($schema->hasField('fe_group') ? $schema->getField('fe_group')->getLabel() : '') . ' ' . $label;
        }
        $out = implode(' - ', $parts);
        return $includeAttrib ? 'title="' . htmlspecialchars($out) . '"' : $out;
    }

    /**
     * Returns title-attribute information for ANY record (from a table defined in TCA of course)
     * The included information depends on features of the table, but if hidden, starttime, endtime and fe_group fields are configured for, information about the record status in regard to these features are is included.
     * "pages" table can be used as well and will return the result of ->titleAttribForPages() for that page.
     *
     * @param array $row Table row; $row is a row from the table, $table
     * @param string $table Table name
     * @param bool $escapeResult If $escapeResult is set, then the return value is escaped with htmlspecialchars()
     */
    public static function getRecordIconAltText($row, $table = 'pages', bool $escapeResult = true): string
    {
        if ($table === 'pages') {
            $title = self::titleAttribForPages($row, '', false);
            return $escapeResult ? htmlspecialchars($title) : $title;
        }

        $languageService = static::getLanguageService();
        $schema = self::getTcaSchema($table);
        $parts = ['id=' . ($row['uid'] ?? '0')];

        if ($schema !== null) {
            if ($schema->hasCapability(TcaSchemaCapability::InternalDescription)) {
                $description = trim((string)($row[$schema->getCapability(TcaSchemaCapability::InternalDescription)->getFieldName()] ?? ''));
                if ($description !== '') {
                    $parts[] = $description;
                }
            }
            $recordTitle = self::getRecordTitle($table, $row);
            if (!empty($recordTitle)) {
                $parts[] = $recordTitle;
            }

            if ($schema->supportsSubSchema()) {
                $subSchemaInformation = $schema->getSubSchemaTypeInformation();
                if ($subSchemaInformation->isPointerToForeignFieldInForeignSchema()) {

                    if (($foreignSchema = self::getTcaSchema($subSchemaInformation->getForeignSchemaName())) !== null) {
                        if (isset($row[$subSchemaInformation->getFieldName()]) && $foreignSchema->hasField($subSchemaInformation->getForeignFieldName())) {
                            $foreignRecord = self::getRecord($subSchemaInformation->getForeignSchemaName(), $row[$subSchemaInformation->getFieldName()], $subSchemaInformation->getForeignFieldName());
                            $typeValue = $foreignRecord[$subSchemaInformation->getForeignFieldName()] ?? null;
                            if (is_array($typeValue)) {
                                $typeValue = (string)array_shift($typeValue);
                            }
                            if ($typeValue) {
                                $recordType = self::getProcessedValue($subSchemaInformation->getForeignSchemaName(), $subSchemaInformation->getForeignFieldName(), $typeValue, 0, false, false, 0, false, 0, $foreignRecord);
                            }
                        }
                    }
                } else {
                    $typeValue = $row[$subSchemaInformation->getFieldName()] ?? null;
                    if (is_array($typeValue)) {
                        $typeValue = (string)array_shift($typeValue);
                    }
                    if ($typeValue) {
                        $recordType = self::getProcessedValue($table, $subSchemaInformation->getFieldName(), $typeValue, 0, false, false, 0, false, 0, $row);
                    }
                }
                if (!empty($recordType)) {
                    $parts[] = $recordType;
                }
            }

            if ($schema->hasCapability(TcaSchemaCapability::Workspace)) {
                switch (VersionState::tryFrom($row['t3ver_state'] ?? 0)) {
                    case VersionState::DELETE_PLACEHOLDER:
                        $parts[] = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.ws.deletedElement');
                        break;
                    case VersionState::MOVE_POINTER:
                        $parts[] = sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.ws.newLocation'), (string)($row['t3ver_wsid'] ?? '0'));
                        break;
                    case VersionState::NEW_PLACEHOLDER:
                        $parts[] = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.ws.newElement');
                        break;
                }
            }
            if ($schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField) && ($row[$schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName()] ?? false)) {
                $parts[] = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.hidden');
            }
            if ($schema->hasCapability(TcaSchemaCapability::RestrictionStartTime) && ($row[$schema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName()] ?? 0) > $GLOBALS['EXEC_TIME']) {
                $starttimeField = $schema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName();
                $parts[] = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.starttime')
                    . ': ' . self::date($row[$starttimeField])
                    . ' (' . self::daysUntil($row[$starttimeField]) . ' ' . $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.days') . ')';
            }
            if ($schema->hasCapability(TcaSchemaCapability::RestrictionEndTime) && ($row[$schema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName()] ?? false)) {
                $endtimeField = $schema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName();
                $parts[] = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.endtime')
                    . ': ' . self::date($row[$endtimeField])
                    . ' (' . self::daysUntil($row[$endtimeField]) . ' ' . $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.days') . ')';
            }
        }

        return $escapeResult ? htmlspecialchars(implode(' - ', $parts)) : implode(' - ', $parts);
    }

    /**
     * Returns the label of the first found entry in an "items" array from the column $col in table $table, where $key is the item's value
     *
     * @param string $table Table name, available in Schema API
     * @param string $col Field name, available in Schema API
     * @param string $key items-array value to match
     * @param array $columnConfig @internal Needs to be migrated properly - will not stay! Is required for "volatile" columns config, {@see FlexFormValueFormatter}
     * @return string Label for item entry
     * @todo MERGE WITH getLabelsFromItemsList() !!
     */
    public static function getLabelFromItemlist($table, $col, $key, array $row = [], array $columnConfig = [])
    {
        if ($columnConfig === []) {
            if (($schema = self::getTcaSchema($table))?->hasField($col)) {
                $columnConfig = $schema->getField($col)->getConfiguration();
            } else {
                return '';
            }
        }

        if (isset($columnConfig['items']) && !is_array($columnConfig['items'])) {
            return '';
        }

        $items = $columnConfig['items'] ?? [];

        if ($columnConfig['itemsProcFunc'] ?? false) {
            $processingService = GeneralUtility::makeInstance(ItemProcessingService::class);
            $items = $processingService->getProcessingItems(
                $table,
                $row['pid'] ?? 0,
                $col,
                $row,
                $columnConfig,
                $items
            );
        }

        foreach ($items as $itemConfiguration) {
            if ((string)$itemConfiguration['value'] === (string)$key) {
                return $itemConfiguration['label'];
            }
        }

        return '';
    }

    /**
     * Return the label of a field by additionally checking TsConfig values
     *
     * @param string $table Table name
     * @param string $column Field Name
     * @param string $key item value
     * @return string Label for item entry
     * @todo MERGE into getLabelsFromItemsList() !!
     */
    public static function getLabelFromItemListMerged(int $pageId, $table, $column, $key, array $row = [])
    {
        $pageTsConfig = static::getPagesTSconfig($pageId);
        $label = '';
        if (isset($pageTsConfig['TCEFORM.'])
            && is_array($pageTsConfig['TCEFORM.'] ?? null)
            && is_array($pageTsConfig['TCEFORM.'][$table . '.'] ?? null)
            && is_array($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.'] ?? null)
        ) {
            if (is_array($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['addItems.'] ?? null)
                && isset($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['addItems.'][$key])
            ) {
                $label = $pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['addItems.'][$key];
            } elseif (is_array($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['altLabels.'] ?? null)
                && isset($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['altLabels.'][$key])
            ) {
                $label = $pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['altLabels.'][$key];
            }
        }
        if (empty($label)) {
            $tcaValue = self::getLabelFromItemlist($table, $column, $key, $row);
            if (!empty($tcaValue)) {
                $label = $tcaValue;
            }
        }
        return $label;
    }

    /**
     * Splits the given key with commas and returns the list of all the localized items labels, separated by a comma.
     *
     * @param string $table Table name, present in TCA
     * @param string $column Field name
     * @param string $keyList Key or comma-separated list of keys.
     * @param array $columnTsConfig page TSconfig for $column (TCEMAIN.<table>.<column>)
     * @param array $columnConfig @internal Needs to be migrated properly - will not stay! Is required for "volatile" columns config, {@see FlexFormValueFormatter}
     * @return string Comma-separated list of localized labels
     * @todo getLabelFromItemsList() should use this method !!
     */
    public static function getLabelsFromItemsList($table, $column, $keyList, array $columnTsConfig = [], array $row = [], array $columnConfig = []): string
    {
        if ($columnConfig === []) {
            if (($schema = self::getTcaSchema($table))?->hasField($column)) {
                $columnConfig = $schema->getField($column)->getConfiguration();
            } else {
                return '';
            }
        }

        if ($keyList === '' || (isset($columnConfig['items']) && !is_array($columnConfig['items']))) {
            return '';
        }

        $items = $columnConfig['items'] ?? [];

        if ($columnConfig['itemsProcFunc'] ?? false) {
            $processingService = GeneralUtility::makeInstance(ItemProcessingService::class);
            $items = $processingService->getProcessingItems(
                $table,
                $row['pid'] ?? 0,
                $column,
                $row,
                $columnConfig,
                $items
            );
        }

        $keys = GeneralUtility::trimExplode(',', $keyList, true);
        $labels = [];
        // Loop on all selected values
        foreach ($keys as $key) {
            $label = null;
            if ($columnTsConfig) {
                // Check if label has been defined or redefined via pageTsConfig
                if (isset($columnTsConfig['addItems.'][$key])) {
                    $label = $columnTsConfig['addItems.'][$key];
                } elseif (isset($columnTsConfig['altLabels.'][$key])) {
                    $label = $columnTsConfig['altLabels.'][$key];
                }
            }
            if ($label === null) {
                // Otherwise lookup the label in TCA items list
                foreach ($items as $itemConfiguration) {
                    if ($key === (string)$itemConfiguration['value']) {
                        $label = $itemConfiguration['label'];
                        break;
                    }
                }
            }
            if ($label !== null) {
                $labels[] = static::getLanguageService()->sL($label);
            }
        }
        return implode(', ', $labels);
    }

    /**
     * Returns the label-value for fieldname $column in table $table.
     *
     * @param string $table Table name, available in Schema API
     * @param string $column Field name, available in Schema API
     * @return string|null Value of the $column "label" or null if not set
     * @deprecated since TYPO3 v14.0, will be removed in TYPO3 v15.0.
     */
    public static function getItemLabel(string $table, string $column): ?string
    {
        trigger_error(
            'BackendUtility::getItemLabel() has been deprecated in TYPO3 v14.0 and will be removed in v15.0. Use $schema->getField($fieldName)->getLabel() instead.',
            E_USER_DEPRECATED
        );

        if (!($schema = self::getTcaSchema($table))?->hasField($column)) {
            return null;
        }
        return $schema->getField($column)->getConfiguration()['label'] ?? null;
    }

    /**
     * Returns the "title"-value in record, $row, from table, $table
     * The field(s) from which the value is taken is determined by the "ctrl"-entries 'label', 'label_alt' and 'label_alt_force'
     *
     * @param string $table Table name, present in TCA
     * @param array $row Row from table
     * @param bool $prep If set, result is prepared for output: The output is cropped to a limited length (depending on BE_USER->uc['titleLen']) and if no value is found for the title, '<em>[No title]</em>' is returned (localized). Further, the output is htmlspecialchars()'ed
     * @param bool $forceResult If set, the function always returns an output. If no value is found for the title, '[No title]' is returned (localized).
     * @return string
     */
    public static function getRecordTitle($table, $row, $prep = false, $forceResult = true)
    {
        $schema = self::getTcaSchema($table);
        if ($schema === null) {
            return '';
        }
        $labelCapability = $schema->hasCapability(TcaSchemaCapability::Label) ? $schema->getCapability(TcaSchemaCapability::Label) : null;
        // If configured, call userFunc
        if ($labelCapability?->getConfiguration()['generator'] ?? false) {
            $params = [
                'table' => $table,
                'row' => $row,
                'title' => '',
                'options' => $labelCapability->getConfiguration()['generatorOptions'],
            ];
            // Create NULL-reference
            $null = null;
            GeneralUtility::callUserFunction($labelCapability->getConfiguration()['generator'], $params, $null);
            // Ensure that result of called userFunc still have title set, and it is a string.
            $recordTitle = (string)($params['title'] ?? '');
        } else {
            // No userFunc: Build label
            $ctrlLabel = $labelCapability?->getPrimaryFieldName() ?? '';
            $ctrlLabelValue = $row[$ctrlLabel] ?? '';
            // $row might be a processed row generated by FormResultCompiler
            // => bail out if the ctrlLabel field has been processed into an array
            //    (e.g. in sys_file_reference.uid_local)
            if (is_array($ctrlLabelValue)) {
                $ctrlLabelValue = '';
            }
            $recordTitle = self::getProcessedValue($table, $ctrlLabel, (string)$ctrlLabelValue, 0, false, false, $row['uid'] ?? null, true, 0, $row) ?? '';
            if (($labelCapability?->getAdditionalFieldNames() ?? []) !== []
                && ($labelCapability->alwaysRenderAdditionalFields() || $recordTitle === '')
            ) {
                // Add the resolved record title - based on "label" - to the array to have them, in case we deal with "label_alt_force"
                $alternatives = [];
                if (!empty($recordTitle)) {
                    $alternatives[] = $recordTitle;
                }
                foreach ($labelCapability->getAdditionalFieldNames() as $fieldName) {
                    $altLabel = '';
                    // Format string value - leave array value (e.g. for select fields) as is
                    if (!is_array($row[$fieldName] ?? false)) {
                        $altLabel = trim(strip_tags((string)($row[$fieldName] ?? '')));
                    }
                    if ($altLabel !== '') {
                        $altLabel = self::getProcessedValue($table, $fieldName, $altLabel, 0, false, false, $row['uid'] ?? 0, true, 0, $row) ?? '';
                        if ($altLabel) {
                            $alternatives[] = $altLabel;
                        }
                    }
                }
                // In case no record title could be resolved based on "label" or "label_alt_force" is set, implode all resolved labels
                if ($recordTitle === '' || $labelCapability->alwaysRenderAdditionalFields()) {
                    $recordTitle = implode(', ', $alternatives);
                }
            }
        }
        // If the current result is empty, set it to '[No title]' (localized) and prepare for output if requested
        if ($prep || $forceResult) {
            if ($prep) {
                $recordTitle = self::getRecordTitlePrep($recordTitle);
            }
            if (trim($recordTitle) === '') {
                $recordTitle = self::getNoRecordTitle($prep);
            }
        }

        return $recordTitle;
    }

    /**
     * Crops a title string to a limited length and if it really was cropped, wrap it in a <span title="...">|</span>,
     * which offers a tooltip with the original title when moving mouse over it.
     *
     * @param string $title The title string to be cropped
     * @param int $titleLength Crop title after this length - if not set, BE_USER->uc['titleLen'] is used
     * @return string The processed title string, wrapped in <span title="...">|</span> if cropped
     */
    public static function getRecordTitlePrep($title, $titleLength = 0)
    {
        // If $titleLength is not a valid positive integer, use BE_USER->uc['titleLen']:
        if (!$titleLength || !MathUtility::canBeInterpretedAsInteger($titleLength) || $titleLength < 0) {
            $titleLength = (int)static::getBackendUserAuthentication()->uc['titleLen'];
        }
        $titleOrig = htmlspecialchars($title);
        $title = htmlspecialchars(GeneralUtility::fixed_lgd_cs($title, (int)$titleLength));
        // If title was cropped, offer a tooltip:
        if ($titleOrig != $title) {
            $title = '<span title="' . $titleOrig . '">' . $title . '</span>';
        }
        return $title;
    }

    /**
     * Get a localized [No title] string, wrapped in <em>|</em> if $prep is TRUE.
     *
     * @param bool $prep Wrap result in <em>|</em>
     * @return string Localized [No title] string
     */
    public static function getNoRecordTitle($prep = false)
    {
        $noTitle = '[' .
            htmlspecialchars(static::getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title'))
            . ']';
        if ($prep) {
            $noTitle = '<em>' . $noTitle . '</em>';
        }
        return $noTitle;
    }

    /**
     * Returns a human readable output of a value from a record
     * For instance a database record relation would be looked up to display the title-value of that record. A checkbox with a "1" value would be "Yes", etc.
     * $table/$col is tablename and fieldname
     * REMEMBER to pass the output through htmlspecialchars() if you output it to the browser! (To protect it from XSS attacks and be XHTML compliant)
     *
     * @param string $table Table name, present in TCA
     * @param string $col Field name, present in TCA
     * @param mixed $value The value of that field from a selected record
     * @param int $fixed_lgd_chars The max amount of characters the value may occupy
     * @param bool $defaultPassthrough Flag means that values for columns that has no conversion will just be pass through directly (otherwise cropped to 200 chars or returned as "N/A")
     * @param bool $noRecordLookup If set, no records will be looked up, UIDs are just shown.
     * @param int $uid Uid of the current record
     * @param bool $_ Unused - previously $forceResult
     * @param int $pid Optional page uid is used to evaluate page TSconfig for the given field
     * @param array $fullRow Optional full database row to provide additional context, e.g. to be used in itemsProcFunc
     * @param array $theColConf @internal Needs to be migrated properly - will not stay! Is required for "volatile" columns config, {@see FlexFormValueFormatter}
     * @throws \InvalidArgumentException
     * @return string|null
     */
    public static function getProcessedValue(
        $table,
        $col,
        $value,
        $fixed_lgd_chars = 0,
        $defaultPassthrough = false,
        $noRecordLookup = false,
        $uid = 0,
        $_ = true,
        $pid = 0,
        $fullRow = [],
        $theColConf = []
    ) {
        if ($col === 'uid') {
            // uid is not in TCA-array
            return $value;
        }
        // Depending on the fields configuration, make a meaningful output value.
        if ($theColConf === []) {
            // Check if table and field is configured to get column config
            if (($schema = self::getTcaSchema($table))?->hasField($col)) {
                $theColConf = $schema->getField($col)->getConfiguration();
            } else {
                return null;
            }
        }
        /*****************
         *HOOK: pre-processing the human readable output from a record
         ****************/
        $referenceObject = new \stdClass();
        $referenceObject->table = $table;
        $referenceObject->fieldName = $col;
        $referenceObject->uid = $uid;
        $referenceObject->value = &$value;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['preProcessValue'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $theColConf, $referenceObject);
        }

        // Label functions can transport the full $row as context.
        // If missing, a minimal context row is constructed.
        // @see #102698
        if ($fullRow === []) {
            $fullRow = ['uid' => (int)$uid, 'pid' => (int)$pid];
        } else {
            // Ensure uid and pid are always provided in full $row
            if (!isset($fullRow['uid'])) {
                $fullRow['uid'] = (int)$uid;
            }
            if (!isset($fullRow['pid'])) {
                $fullRow['pid'] = (int)$pid;
            }
        }
        $l = '';
        $lang = static::getLanguageService();
        switch ((string)($theColConf['type'] ?? '')) {
            case 'radio':
                $l = $lang->sL(self::getLabelFromItemlist($table, $col, $value, $fullRow, $theColConf));
                if ($l === '' && !empty($value)) {
                    // Use plain database value when label is empty
                    $l = $value;
                }
                break;
            case 'inline':
            case 'file':
                if ($uid) {
                    $finalValues = static::resolveRelationLabels($theColConf, $table, $uid, $value, $noRecordLookup);
                    $l = implode(', ', $finalValues);
                } else {
                    $l = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:notAvailableAbbreviation');
                }
                break;
            case 'select':
            case 'category':
                if (!empty($theColConf['MM'])) {
                    if ($uid) {
                        $finalValues = static::resolveRelationLabels($theColConf, $table, $uid, $value, $noRecordLookup);
                        $l = implode(', ', $finalValues);
                    } else {
                        $l = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:notAvailableAbbreviation');
                    }
                } else {
                    $columnTsConfig = [];
                    if ($pid) {
                        $pageTsConfig = self::getPagesTSconfig($pid);
                        if (isset($pageTsConfig['TCEFORM.'][$table . '.'][$col . '.']) && is_array($pageTsConfig['TCEFORM.'][$table . '.'][$col . '.'])) {
                            $columnTsConfig = $pageTsConfig['TCEFORM.'][$table . '.'][$col . '.'];
                        }
                    }
                    $l = self::getLabelsFromItemsList($table, $col, (string)$value, $columnTsConfig, $fullRow, $theColConf);
                    if (!empty($theColConf['foreign_table']) && !$l && self::getTcaSchema($theColConf['foreign_table']) !== null) {
                        if ($noRecordLookup) {
                            $l = $value;
                        } else {
                            $finalValues = [];
                            if ($uid) {
                                $finalValues = static::resolveRelationLabels($theColConf, $table, $uid, $value, $noRecordLookup);
                            }
                            $l = implode(', ', $finalValues);
                        }
                    }
                    if (empty($l) && !empty($value)) {
                        // Use plain database value when label is empty
                        $l = $value;
                    }
                }
                break;
            case 'group':
                // resolve titles of DB records
                $finalValues = static::resolveRelationLabels($theColConf, $table, $uid, $value, $noRecordLookup);
                if ($finalValues !== []) {
                    $l = implode(', ', $finalValues);
                } else {
                    $l = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:notAvailableAbbreviation');
                }
                break;
            case 'folder':
                $l = implode(', ', GeneralUtility::trimExplode(',', (string)$value, true));
                break;
            case 'check':
                if (!is_array($theColConf['items'] ?? null)) {
                    $l = $value ? $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes') : $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:no');
                } elseif (count($theColConf['items']) === 1) {
                    reset($theColConf['items']);
                    $invertStateDisplay = current($theColConf['items'])['invertStateDisplay'] ?? false;
                    if ($invertStateDisplay) {
                        $value = !$value;
                    }
                    $l = $value ? $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes') : $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:no');
                } else {
                    $lA = [];
                    foreach ($theColConf['items'] as $key => $val) {
                        if ((int)$value & 2 ** $key) {
                            $lA[] = $lang->sL($val['label']);
                        }
                    }
                    $l = implode(', ', $lA);
                }
                break;
            case 'input':
            case 'number':
                // todo: As soon as more strict types are used, this isset check must be replaced with a more
                //       appropriate check.
                if (isset($value)) {
                    $l = $value;
                }
                break;
            case 'datetime':
                try {
                    $datetime = DateTimeFactory::createFomDatabaseValueAndTCAConfig($value, $theColConf);
                    $format = DateTimeFactory::getFormatFromTCAConfig($theColConf);
                } catch (\InvalidArgumentException) {
                    $datetime = false;
                    $format = null;
                }
                if ($datetime === null) {
                    $l = '';
                } elseif ($format === 'date') {
                    $ageSuffix = '';
                    // Generate age suffix as long as not explicitly suppressed
                    if (!($theColConf['disableAgeDisplay'] ?? false)) {
                        $now = DateTimeFactory::createFromTimestamp($GLOBALS['EXEC_TIME']);
                        $ageSuffix = sprintf(' (%s)', (new DateFormatter())->formatDateInterval(
                            $now->diff($datetime),
                            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
                        ));
                    }
                    $l = self::date($datetime->getTimestamp()) . $ageSuffix;
                } elseif ($format === 'time') {
                    $l = $datetime->format('H:i');
                } elseif ($format === 'timesec') {
                    $l = $datetime->format('H:i:s');
                } elseif ($format === 'datetime') {
                    $l = self::datetime($datetime->getTimestamp());
                } elseif (isset($value)) {
                    // todo: As soon as more strict types are used, this isset check must be replaced with a more
                    //       appropriate check.
                    $l = $value;
                }
                break;
            case 'password':
                // Hide the password by changing it to asterisk (*) - if anything is set at all
                if ($value) {
                    $l = '********';
                }
                break;
            case 'flex':
                if (is_string($value)) {
                    $l = strip_tags($value);
                }
                break;
            case 'language':
                $l = $value;
                if ($uid) {
                    $pageId = (int)($table === 'pages' ? $uid : (static::getRecordWSOL($table, (int)$uid, 'pid')['pid'] ?? 0));
                    $languageTitle = GeneralUtility::makeInstance(TranslationConfigurationProvider::class)
                        ->getSystemLanguages($pageId)[(int)$value]['title'] ?? '';
                    if ($languageTitle !== '') {
                        $l = $languageTitle;
                    }
                }
                break;
            case 'country':
                $country = GeneralUtility::makeInstance(CountryProvider::class)->getByIsoCode($value ?? '');
                if ($country) {
                    $l = $lang->sL($country->getLocalizedNameLabel());
                }
                break;
            case 'json':
                // For database type "JSON" the value in decoded state is most likely an array. This is not compatible with
                // the "human-readable" processing and returning promise of this method. Thus, we ensure to handle value for
                // this field as json encoded string. This should be the best readable version of the value data.
                if (
                    (
                        is_string($value)
                        && !str_starts_with($value, '{')
                        && !str_starts_with($value, '[')
                    )
                    || !is_string($value)
                ) {
                    // @todo Consider to pretty print the json value, as this would match the "human readable" goal.
                    $value = json_encode($value);
                }
                // no break intended.
            default:
                if ($defaultPassthrough) {
                    $l = $value;
                } elseif (isset($theColConf['MM'])) {
                    $l = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:notAvailableAbbreviation');
                } elseif ($value) {
                    $l = GeneralUtility::fixed_lgd_cs(strip_tags($value), 200);
                }
        }
        /*****************
         *HOOK: post-processing the human readable output from a record
         ****************/
        $null = null;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['postProcessValue'] ?? [] as $_funcRef) {
            $params = [
                'value' => $l,
                'colConf' => $theColConf,
            ];
            $l = GeneralUtility::callUserFunction($_funcRef, $params, $null);
        }
        if ($fixed_lgd_chars && $l) {
            return GeneralUtility::fixed_lgd_cs((string)$l, (int)$fixed_lgd_chars);
        }
        return $l;
    }

    /**
     * Helper method to fetch all labels for all relations of processed Values.
     *
     * @param string|int|null $recordId
     * @param string|int $value
     */
    protected static function resolveRelationLabels(array $theColConf, string $table, $recordId, $value, bool $noRecordLookup): array
    {
        $finalValues = [];

        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->initializeForField($table, $theColConf, $recordId, $value);

        if ($noRecordLookup) {
            $finalValues = array_column($relationHandler->itemArray, 'id');
        } else {
            $relationHandler->getFromDB();
            foreach ($relationHandler->getResolvedItemArray() as $item) {
                $relationRecord = $item['record'];
                static::workspaceOL($item['table'], $relationRecord);
                if (!is_array($relationRecord)) {
                    $finalValues[] = '[' . $item['uid'] . ']';
                } else {
                    $title = static::getRecordTitle($item['table'], $relationRecord);
                    if ($theColConf['foreign_table_prefix'] ?? null) {
                        $title = static::getLanguageService()->sL($theColConf['foreign_table_prefix']) . $title;
                    }
                    $finalValues[] = $title;
                }
            }
        }

        return $finalValues;
    }

    /**
     * Same as ->getProcessedValue() but will go easy on fields like "tstamp" and "pid" which are not configured in TCA - they will be formatted by this function instead.
     *
     * @param string $table Table name, present in TCA
     * @param string $fN Field name
     * @param string $fV Field value
     * @param int $fixed_lgd_chars The max amount of characters the value may occupy
     * @param int $uid Uid of the current record
     * @param bool $forceResult If BackendUtility::getRecordTitle is used to process the value, this parameter is forwarded.
     * @param int $pid Optional page uid is used to evaluate page TSconfig for the given field
     * @param array $fullRow Optional full database row to provide additional context, e.g. to be used in itemsProcFunc
     * @return string
     * @see getProcessedValue()
     */
    public static function getProcessedValueExtra(
        $table,
        $fN,
        $fV,
        $fixed_lgd_chars = 0,
        $uid = 0,
        $forceResult = true,
        $pid = 0,
        $fullRow = []
    ) {
        $fVnew = self::getProcessedValue($table, $fN, $fV, $fixed_lgd_chars, true, false, $uid, $forceResult, $pid, $fullRow);
        if (!isset($fVnew)) {
            if (($schema = self::getTcaSchema($table)) !== null) {
                if ($fN === ($schema->getRawConfiguration()['tstamp'] ?? '') || $fN === ($schema->getRawConfiguration()['crdate'] ?? '0')) {
                    $fVnew = self::datetime((int)$fV);
                } elseif ($fN === 'pid') {
                    // Fetches the path with no regard to the users permissions to select pages.
                    $fVnew = self::getRecordPath((int)$fV, '1=1', 20);
                } else {
                    $fVnew = $fV;
                }
            }
        }
        return $fVnew;
    }

    /**
     * Returns fields for a table, $table, which would typically be interesting to select
     * This includes uid, the fields defined for title, icon-field.
     * Returned as a list ready for query ($prefix can be set to eg. "pages." if you are selecting from the pages table and want the table name prefixed)
     *
     * @param string $table Table name, available in Schema API
     * @param string $prefix Table prefix
     * @param array $fields Preset fields (must include prefix if that is used)
     * @return string List of fields.
     * @internal should only be used from within TYPO3 Core
     * @deprecated since TYPO3 v14.0, will be removed in TYPO3 v15.0.
     */
    public static function getCommonSelectFields($table, $prefix = '', $fields = [])
    {
        trigger_error(
            'BackendUtility::getCommonSelectFields() has been deprecated in TYPO3 v14.0 and will be removed in v15.0. Use Schema API instead to retrieve the fields.',
            E_USER_DEPRECATED
        );

        $fields[] = 'uid';
        $fields[] = 'pid';

        if (($schema = self::getTcaSchema($table)) !== null) {
            if ($schema->hasCapability(TcaSchemaCapability::Label)) {
                $fields = array_merge($fields, $schema->getCapability(TcaSchemaCapability::Label)->getAllLabelFieldNames());
            }
            if ($schema->isWorkspaceAware()) {
                $fields[] = 't3ver_state';
                $fields[] = 't3ver_wsid';
            }
            if ($schema->getRawConfiguration()['selicon_field'] ?? '') {
                $fields[] = $schema->getRawConfiguration()['selicon_field'];
            }
            if ($schema->getRawConfiguration()['typeicon_column'] ?? '') {
                $fields[] = $schema->getRawConfiguration()['typeicon_column'];
            }
            $capabilities = [
                TcaSchemaCapability::SoftDelete,
                TcaSchemaCapability::RestrictionDisabledField,
                TcaSchemaCapability::RestrictionStartTime,
                TcaSchemaCapability::RestrictionEndTime,
                TcaSchemaCapability::RestrictionUserGroup,
            ];
            foreach ($capabilities as $capability) {
                if ($schema->hasCapability($capability)) {
                    $fields[] = $schema->getCapability($capability)->getFieldName();
                }
            }
        }

        $fields = array_unique($fields);
        return implode(',', array_map(static fn(string $value): string => $prefix . $value, $fields));
    }

    /**
     * Makes click menu link (context sensitive menu)
     *
     * Returns $str wrapped in a link which will activate the context sensitive
     * menu for the record ($table/$uid) or file ($table = file)
     * The link will load the top frame with the parameter "&item" which is the table, uid
     * and context arguments imploded by "|": rawurlencode($table.'|'.$uid.'|'.$context)
     *
     * @param string $content String to be wrapped in link, typ. image tag.
     * @param string $table Table name/File path. If the icon is for a database
     * record, enter the (table)name from the Tca Schema. If a file then enter
     * the absolute filepath
     * @param int|string $uid If icon is for database record this is the UID for the
     * record from $table or identifier for sys_file record
     * @param string $context Set tree if menu is called from tree view
     * @param array $row The record row if available
     *
     * @return string The link wrapped input string.
     */
    public static function wrapClickMenuOnIcon($content, $table, $uid = 0, $context = '', array $row = []): string
    {
        $attributes = self::getContextMenuAttributes((string)$table, $uid, (string)$context, 'click', $row);
        return '<button type="button" class="btn btn-link" ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . $content . '</button>';
    }

    /**
     * @param string $table Table name/File path. If the icon is for a database
     * record, enter the (table)name from the Tca Schema. If a file then enter
     * the absolute filepath
     * @param int|string $uid If icon is for database record this is the UID for the
     * record from $table or identifier for sys_file record
     * @param string $context Set tree if menu is called from tree view
     * @param string $trigger Set the trigger the context menu is attached to. Possible options (click/contextmenu)
     * @param array $row The record row if available
     */
    public static function getContextMenuAttributes(
        string $table,
        $uid = 0,
        string $context = '',
        string $trigger = 'click',
        array $row = []
    ): array {
        $lang = static::getLanguageService();

        $attributes = [
            'data-contextmenu-trigger' => $trigger,
            'data-contextmenu-table' => $table,
            'data-contextmenu-uid' => $uid,
            'data-contextmenu-context' => $context,
        ];

        if ($row !== []) {
            $attributes['aria-label'] = sprintf(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.contextMenu.open.recordLabel'),
                self::getRecordTitle($table, $row)
            );
        } else {
            $attributes['aria-label'] = $lang->sL(
                'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.contextMenu.open'
            );
        }

        return $attributes;
    }

    /**
     * Call to update the page tree frame (or something else..?) after
     * use 'updatePageTree' as a first parameter will set the page tree to be updated.
     *
     * @param string $set Key to set the update signal. When setting, this value contains strings telling WHAT to set.
     *      At this point it seems that the value "updatePageTree" is the only one it makes sense to set.
     *      If empty, all update signals will be removed.
     * @param mixed $params Additional information for the update signal, used to only refresh a branch of the tree
     * @see BackendUtility::getUpdateSignalDetails()
     */
    public static function setUpdateSignal($set = '', $params = '')
    {
        // A CLI use does not need to update the pagetree or anything else
        // Otherwise DataHandler hook in EXT:redirects in SlugService will throw an error
        if (Environment::isCli()) {
            return;
        }
        $beUser = static::getBackendUserAuthentication();
        $modData = $beUser->getModuleData(
            BackendUtility::class . '::getUpdateSignal',
            'ses'
        );
        if ($set) {
            $modData[$set] = [
                'set' => $set,
                'parameter' => $params,
            ];
        } else {
            // clear the module data
            $modData = [];
        }
        $beUser->pushModuleData(BackendUtility::class . '::getUpdateSignal', $modData);
    }

    /**
     * Gets instructions for update signals (e.g. page tree shall be refreshed,
     * since some page title has been modified during the current HTTP request).
     *
     * @return array{html: list<string>}
     * @see BackendUtility::setUpdateSignal()
     */
    public static function getUpdateSignalDetails(): array
    {
        $details = [
            'html' => [],
        ];
        $modData = static::getBackendUserAuthentication()->getModuleData(
            BackendUtility::class . '::getUpdateSignal',
            'ses'
        );
        if (empty($modData)) {
            return $details;
        }
        // Hook: Allows to let TYPO3 execute your JS code
        $updateSignals = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook'] ?? [];
        // Loop through all setUpdateSignals and get the JS code
        foreach ($modData as $set => $val) {
            if (isset($updateSignals[$set])) {
                $params = ['set' => $set, 'parameter' => $val['parameter'], 'html' => ''];
                $ref = null;
                GeneralUtility::callUserFunction($updateSignals[$set], $params, $ref);
                if (!empty($params['html'])) {
                    $details['html'][] = $params['html'];
                }
            } else {
                switch ($set) {
                    case 'updatePageTree':
                        $details['html'][] = ImmediateActionElement::dispatchCustomEvent(
                            'typo3:pagetree:refresh',
                            null,
                            true
                        );
                        break;
                    case 'updateFolderTree':
                        $details['html'][] = ImmediateActionElement::dispatchCustomEvent(
                            'typo3:filestoragetree:refresh',
                            null,
                            true
                        );
                        break;
                    case 'updateColorScheme':
                        $details['html'][] = ImmediateActionElement::dispatchCustomEvent(
                            'typo3:color-scheme:update',
                            ['colorScheme' => $val['parameter']],
                            true
                        );
                        break;
                    case 'updateTheme':
                        $details['html'][] = ImmediateActionElement::dispatchCustomEvent(
                            'typo3:theme:update',
                            ['theme' => $val['parameter']],
                            true
                        );
                        break;
                    case 'updateTitleFormat':
                        $details['html'][] = ImmediateActionElement::dispatchCustomEvent(
                            'typo3:title-format:update',
                            ['format' => $val['parameter']],
                            true
                        );
                        break;
                    case 'updateBackendLanguage':
                        $details['html'][] = ImmediateActionElement::dispatchCustomEvent(
                            'typo3:backend-language:update',
                            [
                                'language' => $val['parameter']['language'],
                                'direction' => $val['parameter']['direction'] ?? null,
                            ],
                            true
                        );
                        break;
                    case 'updateSystemInformationMenu':
                        $details['html'][] = ImmediateActionElement::dispatchCustomEvent(
                            'typo3:system-information-menu:update',
                            null,
                            true
                        );
                        break;
                    case 'updateModuleMenu':
                        $details['html'][] = ImmediateActionElement::forAction(
                            'TYPO3.ModuleMenu.App.refreshMenu',
                        );
                        break;
                    case 'updateTopbar':
                        $details['html'][] = ImmediateActionElement::forAction(
                            'TYPO3.Backend.Topbar.refresh'
                        );
                        break;
                }
            }
        }
        // reset update signals
        self::setUpdateSignal();
        return $details;
    }

    /**
     * Returns an array which is most backend modules becomes MOD_SETTINGS containing values from function menus etc. determining the function of the module.
     * This is kind of session variable management framework for the backend users.
     * If a key from MOD_MENU is set in the CHANGED_SETTINGS array (eg. a value is passed to the script from the outside), this value is put into the settings-array
     *
     * @param array $MOD_MENU MOD_MENU is an array that defines the options in menus.
     * @param array $CHANGED_SETTINGS CHANGED_SETTINGS represents the array used when passing values to the script from the menus.
     * @param string $modName modName is the name of this module. Used to get the correct module data.
     * @param string $type If type is 'ses' then the data is stored as session-lasting data. This means that it'll be wiped out the next time the user logs in.
     * @param string $dontValidateList dontValidateList can be used to list variables that should not be checked if their value is found in the MOD_MENU array. Used for dynamically generated menus.
     * @param string $setDefaultList List of default values from $MOD_MENU to set in the output array (only if the values from MOD_MENU are not arrays)
     * @throws \RuntimeException
     * @return array The array $settings, which holds a key for each MOD_MENU key and the values of each key will be within the range of values for each menuitem
     */
    public static function getModuleData(
        $MOD_MENU,
        $CHANGED_SETTINGS,
        $modName,
        $type = '',
        $dontValidateList = '',
        $setDefaultList = ''
    ) {
        if ($modName && is_string($modName)) {
            // Getting stored user-data from this module:
            $beUser = static::getBackendUserAuthentication();
            $settings = $beUser->getModuleData($modName, $type);
            $changed = 0;
            if (!is_array($settings)) {
                $changed = 1;
                $settings = [
                    'function' => null,
                    'language' => null,
                    'constant_editor_cat' => null,
                ];
            }
            if (is_array($MOD_MENU)) {
                foreach ($MOD_MENU as $key => $var) {
                    // If a global var is set before entering here. eg if submitted, then it's substituting the current value the array.
                    if (is_array($CHANGED_SETTINGS) && isset($CHANGED_SETTINGS[$key])) {
                        if (is_array($CHANGED_SETTINGS[$key])) {
                            $serializedSettings = serialize($CHANGED_SETTINGS[$key]);
                            if ((string)$settings[$key] !== $serializedSettings) {
                                $settings[$key] = $serializedSettings;
                                $changed = 1;
                            }
                        } else {
                            if ((string)($settings[$key] ?? '') !== (string)($CHANGED_SETTINGS[$key] ?? '')) {
                                $settings[$key] = $CHANGED_SETTINGS[$key];
                                $changed = 1;
                            }
                        }
                    }
                    // If the $var is an array, which denotes the existence of a menu, we check if the value is permitted
                    if (is_array($var) && (!$dontValidateList || !GeneralUtility::inList($dontValidateList, $key))) {
                        // If the setting is an array or not present in the menu-array, MOD_MENU, then the default value is inserted.
                        if (is_array($settings[$key] ?? null) || !isset($MOD_MENU[$key][$settings[$key] ?? null])) {
                            $settings[$key] = (string)key($var);
                            $changed = 1;
                        }
                    }
                    // Sets default values (only strings/checkboxes, not menus)
                    if ($setDefaultList && !is_array($var)) {
                        if (GeneralUtility::inList($setDefaultList, $key) && !isset($settings[$key])) {
                            $settings[$key] = (string)$var;
                        }
                    }
                }
            } else {
                throw new \RuntimeException('No menu', 1568119229);
            }
            if ($changed) {
                $beUser->pushModuleData($modName, $settings);
            }
            return $settings;
        }
        throw new \RuntimeException('Wrong module name "' . $modName . '"', 1568119221);
    }

    /*******************************************
     *
     * Core
     *
     *******************************************/
    /**
     * Unlock or Lock a record from $table with $uid
     * If $table and $uid is not set, then all locking for the current BE_USER is removed!
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param int $pid Record pid
     * @internal
     */
    public static function lockRecords(string $table = '', $uid = 0, $pid = 0): void
    {
        $beUser = static::getBackendUserAuthentication();
        if (isset($beUser->user['uid'])) {
            $userId = (int)$beUser->user['uid'];
            if ($table && $uid) {
                $fieldsValues = [
                    'userid' => $userId,
                    'feuserid' => 0,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'record_table' => $table,
                    'record_uid' => $uid,
                    'username' => $beUser->user['username'],
                    'record_pid' => $pid,
                ];
                self::getConnectionForTable('sys_lockedrecords')
                    ->insert(
                        'sys_lockedrecords',
                        $fieldsValues
                    );
            } else {
                self::getConnectionForTable('sys_lockedrecords')
                    ->delete(
                        'sys_lockedrecords',
                        ['userid' => (int)$userId]
                    );
            }
        }
    }

    /**
     * Returns information about whether the record from table, $table, with uid, $uid is currently locked
     * (edited by another user - which should issue a warning).
     * Notice: Locking is not strictly carried out since locking is abandoned when other backend scripts
     * are activated - which means that a user CAN have a record "open" without having it locked.
     * So this just serves as a warning that counts well in 90% of the cases, which should be sufficient.
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @return array|bool
     * @internal
     */
    public static function isRecordLocked($table, $uid)
    {
        $runtimeCache = self::getRuntimeCache();
        $cacheId = 'backend-recordLocked';
        $recordLockedCache = $runtimeCache->get($cacheId);
        if ($recordLockedCache !== false) {
            $lockedRecords = $recordLockedCache;
        } else {
            $lockedRecords = [];

            $queryBuilder = self::getQueryBuilderForTable('sys_lockedrecords');
            $result = $queryBuilder
                ->select('*')
                ->from('sys_lockedrecords')
                ->where(
                    $queryBuilder->expr()->neq(
                        'sys_lockedrecords.userid',
                        $queryBuilder->createNamedParameter(
                            static::getBackendUserAuthentication()->user['uid'],
                            Connection::PARAM_INT
                        )
                    ),
                    $queryBuilder->expr()->gt(
                        'sys_lockedrecords.tstamp',
                        $queryBuilder->createNamedParameter(
                            $GLOBALS['EXEC_TIME'] - 2 * 3600,
                            Connection::PARAM_INT
                        )
                    )
                )
                ->executeQuery();

            $lang = static::getLanguageService();
            while ($row = $result->fetchAssociative()) {
                $row += [
                    'userid' => 0,
                    'record_pid' => 0,
                    'feuserid' => 0,
                    'username' => '',
                    'record_table' => '',
                    'record_uid' => 0,

                ];
                // Get the type of the user that locked this record:
                if ($row['userid']) {
                    $userTypeLabel = 'beUser';
                } elseif ($row['feuserid']) {
                    $userTypeLabel = 'feUser';
                } else {
                    $userTypeLabel = 'user';
                }
                $userType = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.' . $userTypeLabel);
                // Get the username (if available):
                $userName = ($row['username'] ?? '') ?: $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.unknownUser');

                $lockedRecords[$row['record_table'] . ':' . $row['record_uid']] = $row;
                $lockedRecords[$row['record_table'] . ':' . $row['record_uid']]['msg'] = sprintf(
                    $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.lockedRecordUser'),
                    $userType,
                    $userName,
                    self::calcAge(
                        $GLOBALS['EXEC_TIME'] - $row['tstamp'],
                        $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
                    )
                );
                if ($row['record_pid'] && !isset($lockedRecords[$row['record_table'] . ':' . $row['record_pid']])) {
                    $lockedRecords['pages:' . ($row['record_pid'] ?? '')]['msg'] = sprintf(
                        $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.lockedRecordUser_content'),
                        $userType,
                        $userName,
                        self::calcAge(
                            $GLOBALS['EXEC_TIME'] - $row['tstamp'],
                            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
                        )
                    );
                }
            }
            $runtimeCache->set($cacheId, $lockedRecords);
        }

        return $lockedRecords[$table . ':' . $uid] ?? false;
    }

    /**
     * Returns TSConfig for the TCEFORM object in page TSconfig.
     * Used in TCEFORMs
     *
     * @param string $table Table name present in TCA
     * @param array $row Row from table
     */
    public static function getTCEFORM_TSconfig($table, $row): array
    {
        $res = [];
        // Get main config for the table
        [$TScID, $cPid] = self::getTSCpid($table, $row['uid'] ?? 0, $row['pid'] ?? 0);
        if ($TScID >= 0) {
            $tsConfig = static::getPagesTSconfig($TScID)['TCEFORM.'][$table . '.'] ?? [];
            $typeVal = self::getTCAtypeValue($table, $row);
            foreach ($tsConfig as $key => $val) {
                if (is_array($val)) {
                    $fieldN = substr($key, 0, -1);
                    $res[$fieldN] = $val;
                    unset($res[$fieldN]['types.']);
                    if ((string)$typeVal !== '' && is_array($val['types.'][$typeVal . '.'] ?? false)) {
                        ArrayUtility::mergeRecursiveWithOverrule($res[$fieldN], $val['types.'][$typeVal . '.']);
                    }
                }
            }
        }
        $res['_CURRENT_PID'] = $cPid;
        $res['_THIS_UID'] = $row['uid'] ?? 0;
        // So the row will be passed to foreign_table_where_query()
        $res['_THIS_ROW'] = $row;
        return $res;
    }

    /**
     * Find the real PID of the record (with $uid from $table).
     * This MAY be impossible if the pid is set as a reference to the former record or a page (if two records are created at one time).
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param int|string $pid Record pid, could be negative then pointing to a record from same table whose pid to find and return
     * @return int|null
     * @internal
     * @see \TYPO3\CMS\Core\DataHandling\DataHandler::copyRecord()
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::getTSCpid()
     */
    public static function getTSconfig_pidValue($table, $uid, $pid)
    {
        // If pid is an integer this takes precedence in our lookup.
        if (MathUtility::canBeInterpretedAsInteger($pid)) {
            $thePidValue = (int)$pid;
            // If ref to another record, look that record up.
            if ($thePidValue < 0) {
                $pidRec = self::getRecord($table, abs($thePidValue), 'pid');
                $thePidValue = is_array($pidRec) ? $pidRec['pid'] : -2;
            }
        } else {
            // Try to fetch the record pid from uid. If the uid is 'NEW...' then this will of course return nothing
            $rr = self::getRecord($table, $uid);
            $thePidValue = null;
            if (is_array($rr)) {
                // First check if the t3ver_oid value is greater 0, which means
                // it is a workspace element. If so, get the "real" record:
                if ((int)($rr['t3ver_oid'] ?? 0) > 0) {
                    $rr = self::getRecord($table, $rr['t3ver_oid'], 'pid');
                    if (is_array($rr)) {
                        $thePidValue = $rr['pid'];
                    }
                } else {
                    // Returning the "pid" of the record
                    $thePidValue = $rr['pid'];
                }
            }
            if (!$thePidValue) {
                // Returns -1 if the record with this pid was not found.
                $thePidValue = -1;
            }
        }
        return $thePidValue;
    }

    /**
     * Return the real pid of a record and caches the result.
     * The non-cached method needs database queries to do the job, so this method
     * can be used if code sometimes calls the same record multiple times to save
     * some queries. This should not be done if the calling code may change the
     * same record meanwhile.
     *
     * @param string $table Tablename
     * @param string $uid UID value
     * @param string $pid PID value
     * @return array Array of two integers; first is the real PID of a record, second is the PID value for TSconfig.
     */
    public static function getTSCpidCached($table, $uid, $pid)
    {
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $firstLevelCache = $runtimeCache->get('backendUtilityTscPidCached') ?: [];
        $key = $table . ':' . $uid . ':' . $pid;
        if (!isset($firstLevelCache[$key])) {
            $firstLevelCache[$key] = static::getTSCpid($table, (int)$uid, (int)$pid);
            $runtimeCache->set('backendUtilityTscPidCached', $firstLevelCache);
        }
        return $firstLevelCache[$key];
    }

    /**
     * Returns the REAL pid of the record, if possible. If both $uid and $pid is strings, then pid=-1 is returned as an error indication.
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param int|string $pid Record pid
     * @return array Array of two integers; first is the REAL PID of a record and if its a new record negative values are resolved to the true PID,
     * second value is the PID value for TSconfig (uid if table is pages, otherwise the pid)
     * @internal
     * @see \TYPO3\CMS\Core\DataHandling\DataHandler::setHistory()
     * @see \TYPO3\CMS\Core\DataHandling\DataHandler::process_datamap()
     */
    public static function getTSCpid($table, $uid, $pid)
    {
        // If pid is negative (referring to another record) the pid of the other record is fetched and returned.
        $cPid = self::getTSconfig_pidValue($table, $uid, $pid);
        // $TScID is the id of $table = pages, else it's the pid of the record.
        $TScID = $table === 'pages' && MathUtility::canBeInterpretedAsInteger($uid) ? $uid : $cPid;
        return [$TScID, $cPid];
    }

    /**
     * Gets an instance of the runtime cache.
     *
     * @return FrontendInterface
     */
    protected static function getRuntimeCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }

    /**
     * Counting references to a record/file
     *
     * @param string $table Table name
     * @param int $ref Record uid
     * @param string $msg Message with %s, e.g. "There were %s records pointing to this file!"
     * @param string|int|null $count Reference count
     * @return string|int Output string (or int count value if no msg string specified)
     * @todo: The method should vanish altogether: The signature with $msg and $count and
     *        string|int return value is odd, and the method contains hacks for sys_file.
     */
    public static function referenceCount($table, $ref, $msg = '', $count = null)
    {
        if ($count === null && MathUtility::canBeInterpretedAsInteger($ref)) {
            // MathUtility::canBeInterpretedAsInteger($ref) and no method type hint for b/w compat.
            $queryBuilder = self::getQueryBuilderForTable('sys_refindex');
            $queryBuilder->count('*')->from('sys_refindex')
                ->where(
                    $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter($table)),
                    $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($ref, Connection::PARAM_INT))
                );
            if ($table === 'sys_file') {
                $queryBuilder->andWhere($queryBuilder->expr()->neq('tablename', $queryBuilder->quote('sys_file_metadata')));
            }
            $count = $queryBuilder->executeQuery()->fetchOne();
        }
        if ($count) {
            return $msg ? sprintf($msg, $count) : $count;
        }
        return $msg ? '' : 0;
    }

    /**
     * Counting translations of records
     *
     * @param string $table Table name
     * @param string $ref Reference: the record's uid
     * @param string $msg Message with %s, eg. "This record has %s translation(s) which will be deleted, too!
     * @return string Output string (or int count value if no msg string specified)
     */
    public static function translationCount(string $table, $ref, $msg = ''): string
    {
        $schema = self::getTcaSchema($table);
        $count = 0;
        if ($schema?->hasCapability(TcaSchemaCapability::Language)) {
            /** @var LanguageAwareSchemaCapability $languageCapability */
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $queryBuilder = self::getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $count = (int)$queryBuilder
                ->count('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $languageCapability->getTranslationOriginPointerField()->getName(),
                        $queryBuilder->createNamedParameter($ref, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        $languageCapability->getLanguageField()->getName(),
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchOne();
        }

        if ($count > 0) {
            return $msg ? sprintf($msg, $count) : (string)$count;
        }
        return $msg ? '' : '0';
    }

    /*******************************************
     *
     * Workspaces / Versioning
     *
     *******************************************/
    /**
     * Select all versions of a record, ordered by latest created version (uid DESC)
     *
     * @param string $table Table name to select from
     * @param int $uid Record uid for which to find versions.
     * @param string $fields Field list to select
     * @param int|null $workspace Search in workspace ID and Live WS, if 0 search only in LiveWS, if NULL search in all WS.
     * @param bool $includeDeletedRecords If set, deleted-flagged versions are included! (Only for clean-up script!)
     * @param array $row The current record
     * @return array|null Array of versions of table/uid
     * @internal should only be used from within TYPO3 Core
     */
    public static function selectVersionsOfRecord(
        string $table,
        $uid,
        $fields = '*',
        $workspace = 0,
        $includeDeletedRecords = false,
        $row = null
    ): ?array {
        if (!self::getTcaSchema($table)?->hasCapability(TcaSchemaCapability::Workspace)) {
            return null;
        }

        $outputRows = [];
        if (is_array($row) && !$includeDeletedRecords) {
            $row['_CURRENT_VERSION'] = true;
            $outputRows[] = $row;
        } else {
            // Select UID version:
            $row = self::getRecord($table, $uid, $fields, '', !$includeDeletedRecords);
            // Add rows to output array:
            if ($row) {
                $row['_CURRENT_VERSION'] = true;
                $outputRows[] = $row;
            }
        }

        $queryBuilder = self::getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        // build fields to select
        $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields));

        $queryBuilder
            ->from($table)
            ->where(
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->orderBy('uid', 'DESC');

        if (!$includeDeletedRecords) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }

        if ($workspace === 0) {
            // Only in Live WS
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            );
        } elseif ($workspace !== null) {
            // In Live WS and Workspace with given ID
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter([0, (int)$workspace], Connection::PARAM_INT_ARRAY)
                )
            );
        }

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        // Add rows to output array:
        if (is_array($rows)) {
            $outputRows = array_merge($outputRows, $rows);
        }
        return $outputRows;
    }

    /**
     * Workspace Preview Overlay.
     *
     * Generally ALWAYS used when records are selected based on uid or pid.
     * Principle; Record online! => Find offline?
     * The function MAY set $row to FALSE. This happens if a moved record is given and
     * $unsetMovePointers is set to true. In other words, you should check if the input record
     * is still an array afterwards when using this function.
     *
     * If the versioned record is a moved record the "pid" value will then contain the newly moved location
     * and "ORIG_pid" will contain the live pid.
     *
     * @param string $table Table name
     * @param array|null $row Record by reference. At least "uid", "pid", "t3ver_oid" and "t3ver_state" must be set.
     *                        Keys not prefixed with '_' are used as field names in SQL.
     * @param int $wsid Workspace ID, if not specified will use static::getBackendUserAuthentication()->workspace
     * @param bool $unsetMovePointers If TRUE the function does not return a "pointer" row for moved records in a workspace
     * @param-out false|array|null $row
     */
    public static function workspaceOL($table, &$row, $wsid = -99, $unsetMovePointers = false)
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')
            || !is_array($row)
            || !self::getTcaSchema($table)?->hasCapability(TcaSchemaCapability::Workspace)
        ) {
            return;
        }

        // Initialize workspace ID
        $wsid = (int)$wsid;
        if ($wsid === -99 && static::getBackendUserAuthentication() instanceof BackendUserAuthentication) {
            $wsid = static::getBackendUserAuthentication()->workspace;
        }
        if ($wsid === 0) {
            // Return early if in live workspace
            return;
        }

        // Check if input record is a moved record
        $incomingRecordIsAMoveVersion = false;
        if (isset($row['t3ver_oid'])
            && $row['t3ver_oid'] > 0
            && VersionState::tryFrom($row['t3ver_state'] ?? 0) === VersionState::MOVE_POINTER
        ) {
            // @todo: This handling needs a review, together with the 4th param $unsetMovePointers
            $incomingRecordIsAMoveVersion = true;
        }

        $wsAlt = self::getWorkspaceVersionOfRecord(
            $wsid,
            $table,
            $row['uid'],
            implode(',', static::purgeComputedPropertyNames(array_keys($row + ['t3ver_state' => null])))
        );

        // If version was found, swap the default record with that one.
        if (is_array($wsAlt)) {
            $versionState = VersionState::tryFrom($wsAlt['t3ver_state'] ?? 0);
            // Check if this is in move-state
            if ($versionState === VersionState::MOVE_POINTER) {
                // @todo Same problem as frontend in versionOL(). See TODO point there and todo above.
                if (!$incomingRecordIsAMoveVersion && $unsetMovePointers) {
                    $row = false;
                    return;
                }
                // When a moved record is found the "PID" value contains the newly moved location
                // Whereas the _ORIG_pid field contains the PID of the live version
                $wsAlt['_ORIG_pid'] = $row['pid'];
            }
            // Swap UID
            if ($versionState !== VersionState::NEW_PLACEHOLDER) {
                $wsAlt['_ORIG_uid'] = $wsAlt['uid'];
                $wsAlt['uid'] = $row['uid'];
            }
            // Changing input record to the workspace version alternative:
            $row = $wsAlt;
        }
    }

    /**
     * Select the workspace version of a record, if exists
     *
     * @param int $workspace Workspace ID
     * @param string $table Table name to select from
     * @param int $uid Record uid for which to find workspace version.
     * @param string $fields Field list to select
     * @return array|false If found, return record, otherwise false
     */
    public static function getWorkspaceVersionOfRecord($workspace, string $table, $uid, $fields = '*'): array|false
    {
        if ($workspace === 0
            || !ExtensionManagementUtility::isLoaded('workspaces')
            || !self::getTcaSchema($table)?->hasCapability(TcaSchemaCapability::Workspace)

        ) {
            return false;
        }
        $queryBuilder = self::getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            // Workspace records aren't soft-delete aware: deleted=1 & t3ver_wsid>0 should not exist.
            // It should be fine to add the restriction to not accidentally catch invalid records.
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder
            ->select(...GeneralUtility::trimExplode(',', $fields))
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($workspace, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->or(
                    // t3ver_state=1 does not contain a t3ver_oid, and returns itself
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            't3ver_state',
                            $queryBuilder->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT)
                        )
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_oid',
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    )
                )
            )
            ->executeQuery()
            ->fetchAssociative();
    }

    /**
     * Use this function if you have a large set of IDs to find out which ones have a counterpart within a workspace.
     * Within a workspace, this is one additional query, so use it only if you have a set of > 2 to find out if you
     * really need to call BackendUtility::workspaceOL() all the time.
     *
     * If you have 1000 records, but only have two 2 records which have been modified in a workspace, only 2 items
     * are returned.
     *
     * @return array<int, int> keys contain the live record ID, values the versioned record ID
     * @internal this method is not public API and might change, as you really should know what you are doing.
     */
    public static function getPossibleWorkspaceVersionIdsOfLiveRecordIds(string $table, array $liveRecordIds, int $workspaceId): array
    {
        if ($liveRecordIds === []
            || $workspaceId === 0
            || !self::getTcaSchema($table)?->hasCapability(TcaSchemaCapability::Workspace)
        ) {
            return [];
        }
        $doOverlaysForRecords = [];
        $connection = self::getConnectionForTable($table);
        $maxChunk = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());
        foreach (array_chunk($liveRecordIds, (int)floor($maxChunk / 2)) as $liveRecordIdChunk) {
            $queryBuilder = self::getQueryBuilderForTable($table);
            $doOverlaysForRecordsStatement = $queryBuilder
                ->select('t3ver_oid', 'uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter($workspaceId, Connection::PARAM_INT)),
                    $queryBuilder->expr()->in('t3ver_oid', $queryBuilder->quoteArrayBasedValueListToIntegerList($liveRecordIdChunk))
                )
                ->executeQuery();
            while ($recordWithVersionedRecord = $doOverlaysForRecordsStatement->fetchNumeric()) {
                $doOverlaysForRecords[(int)$recordWithVersionedRecord[0]] = (int)$recordWithVersionedRecord[1];
            }
        }
        return $doOverlaysForRecords;
    }

    /**
     * Returns live version of record
     *
     * @param string $table Table name
     * @param int|string $uid Record UID of draft, offline version
     * @param string $fields Field list, default is *
     * @return array|null If found, the record, otherwise NULL
     * @todo: Warning. If uid is a 'new placeholder' record in workspaces, this row is returned.
     */
    public static function getLiveVersionOfRecord($table, $uid, $fields = '*')
    {
        $liveVersionId = self::getLiveVersionIdOfRecord($table, $uid);
        if ($liveVersionId !== null) {
            return self::getRecord($table, $liveVersionId, $fields);
        }
        return null;
    }

    /**
     * Gets the id of the live version of a record.
     *
     * @param string $table Name of the table
     * @param int|string $uid Uid of the offline/draft record
     * @return int|null The id of the live version of the record (or NULL if nothing was found)
     * @internal should only be used from within TYPO3 Core
     * @todo: Warning. If uid is a 'new placeholder' record in workspaces, this is considered the 'live' uid!
     */
    public static function getLiveVersionIdOfRecord($table, $uid)
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')
            || !self::getTcaSchema($table)?->hasCapability(TcaSchemaCapability::Workspace)
        ) {
            return null;
        }
        $liveVersionId = null;
        $currentRecord = self::getRecord($table, $uid, 'pid,t3ver_oid,t3ver_state');
        if (is_array($currentRecord)) {
            if ((int)$currentRecord['t3ver_oid'] > 0) {
                $liveVersionId = $currentRecord['t3ver_oid'];
            } elseif (VersionState::tryFrom($currentRecord['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER) {
                // New versions do not have a live counterpart
                $liveVersionId = (int)$uid;
            }
        }
        return $liveVersionId;
    }

    /**
     * Performs mapping of new uids to new versions UID in case of import inside a workspace.
     *
     * @param string $table Table name
     * @param int $uid Record uid (of live record placeholder)
     * @return int Uid of offline version if any, otherwise live uid.
     * @internal should only be used from within TYPO3 Core
     */
    public static function wsMapId($table, $uid)
    {
        $wsRec = null;
        if (static::getBackendUserAuthentication() instanceof BackendUserAuthentication) {
            $wsRec = self::getWorkspaceVersionOfRecord(
                static::getBackendUserAuthentication()->workspace,
                $table,
                $uid,
                'uid'
            );
        }
        return is_array($wsRec) ? $wsRec['uid'] : $uid;
    }

    /*******************************************
     *
     * Miscellaneous
     *
     *******************************************/

    /**
     * Determines whether a table is enabled for workspaces.
     *
     * @param string $table Name of the table to be checked
     * @return bool
     * @deprecated since TYPO3 v14.0, will be removed in TYPO3 v15.0.
     */
    public static function isTableWorkspaceEnabled(string $table): bool
    {
        trigger_error(
            'BackendUtility::isTableWorkspaceEnabled() has been deprecated in TYPO3 v14.0 and will be removed in v15.0. Use Schema API with $schema->hasCapability(TcaSchemaCapability::Workspace) instead.',
            E_USER_DEPRECATED
        );

        return (bool)self::getTcaSchema($table)?->hasCapability(TcaSchemaCapability::Workspace);
    }

    /**
     * Whether to ignore restrictions on a web-mount of a table.
     * The regular behaviour is that records to be accessed need to be
     * in a valid user's web-mount.
     *
     * @param string $table Name of the table
     * @return bool
     * @deprecated since TYPO3 v14.0, will be removed in TYPO3 v15.0.
     */
    public static function isWebMountRestrictionIgnored($table)
    {
        trigger_error(
            'BackendUtility::isWebMountRestrictionIgnored() has been deprecated in TYPO3 v14.0 and will be removed in v15.0. Use Schema API with $schema->hasCapability(TcaSchemaCapability::RestrictionWebMount) instead.',
            E_USER_DEPRECATED
        );

        return (bool)self::getTcaSchema($table)?->hasCapability(TcaSchemaCapability::RestrictionWebMount);

    }

    /**
     * Whether to ignore restrictions on root-level records.
     * The regular behaviour is that records on the root-level (page-id 0)
     * only can be accessed by admin users.
     *
     * @param string $table Name of the table
     * @return bool
     * @deprecated since TYPO3 v14.0, will be removed in TYPO3 v15.0.
     */
    public static function isRootLevelRestrictionIgnored($table)
    {
        trigger_error(
            'BackendUtility::isRootLevelRestrictionIgnored() has been deprecated in TYPO3 v14.0 and will be removed in v15.0. Use Schema API with $schema->get($table)->getCapability(TcaSchemaCapability::RestrictionRootLevel)->shallIgnoreRootLevelRestriction()',
            E_USER_DEPRECATED
        );

        return (bool)self::getTcaSchema($table)?->getCapability(TcaSchemaCapability::RestrictionRootLevel)?->shallIgnoreRootLevelRestriction();
    }

    /**
     * Get all fields of a table, which are allowed for the current user
     *
     * @param string $table Table name
     * @param bool $checkUserAccess If set, users access to the field (non-exclude-fields) is checked.
     * @return string[] Array, where values are fieldnames
     * @internal should only be used from within TYPO3 Core
     * @todo Centralize this with the other methods in core: getColumnsToRender(), getCommonSelectFields(), etc.
     */
    public static function getAllowedFieldsForTable(string $table, bool $checkUserAccess = true): array
    {
        if (($schema = self::getTcaSchema($table)) === null || $schema->getFields()->count() === 0) {
            self::getLogger()->error('TCA is broken for the table "' . $table . '": no required "columns" entry in TCA.');
            return [];
        }

        $fieldList = [];
        $backendUser = self::getBackendUserAuthentication();
        $rawConfiguration = $schema->getRawConfiguration();

        // Traverse configured columns and add them to field array, if available for user.
        foreach ($schema->getFields() as $field) {
            if ($field instanceof NoneFieldType) {
                // Never render or fetch type=none fields from db
                continue;
            }
            if (!$checkUserAccess
                || (
                    (
                        !$field->supportsAccessControl()
                        || $backendUser?->check('non_exclude_fields', $table . ':' . $field->getName())
                    )
                    && $field instanceof PassthroughFieldType === false
                )
            ) {
                $fieldList[] = $field->getName();
            }
        }

        $fieldList[] = 'uid';
        $fieldList[] = 'pid';

        // Add date fields - if defined for the table
        if ($rawConfiguration['tstamp'] ?? false) {
            $fieldList[] = $rawConfiguration['tstamp'];
        }
        if ($rawConfiguration['crdate'] ?? false) {
            $fieldList[] = $rawConfiguration['crdate'];
        }

        // Add more special fields in case user should not be checked or is admin
        if (!$checkUserAccess || $backendUser?->isAdmin()) {
            if ($schema->hasCapability(TcaSchemaCapability::SortByField)) {
                $fieldList[] = $schema->getCapability(TcaSchemaCapability::SortByField)->getFieldName();
            }
            if ($schema->hasCapability(TcaSchemaCapability::Workspace)) {
                $fieldList[] = 't3ver_state';
                $fieldList[] = 't3ver_wsid';
                $fieldList[] = 't3ver_oid';
            }
        }

        // Return unique field list
        return array_values(array_unique($fieldList));
    }

    /**
     * Gets the raw database row values and calls the php converter of doctrine to get the PHP value.
     * Currently only handles type=json and takes care of decoding the value.
     *
     * @internal
     */
    public static function convertDatabaseRowValuesToPhp(string $table, array $row): array
    {
        $schema = self::getTcaSchema($table);
        if ($schema === null) {
            return $row;
        }
        $platform = self::getConnectionForTable($table)->getDatabasePlatform();
        foreach ($row as $field => $value) {
            // @todo Only handle specific TCA type=json
            if ($schema->hasField($field) && $schema->getField($field) instanceof JsonFieldType) {
                $row[$field] = Type::getType('json')->convertToPHPValue($value, $platform);
            }
        }
        return $row;
    }

    protected static function getConnectionForTable(string $table): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
    }

    protected static function getQueryBuilderForTable(string $table): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }

    protected static function getLogger(): LoggerInterface
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    protected static function getTcaSchema(string $tableName): ?TcaSchema
    {
        $schemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        return $schemaFactory->has($tableName) ? $schemaFactory->get($tableName) : null;
    }

    protected static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected static function getBackendUserAuthentication(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
