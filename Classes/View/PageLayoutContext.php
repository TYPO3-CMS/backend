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

namespace TYPO3\CMS\Backend\View;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\ContentFetcher;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
 */
class PageLayoutContext
{
    protected ContentFetcher $contentFetcher;
    protected ?array $localizedPageRecord = null;
    protected int $pageId;

    /**
     * @var SiteLanguage[]
     */
    protected array $siteLanguages = [];
    protected SiteLanguage $siteLanguage;

    /**
     * Array of content type labels. Key is CType, value is either a plain text
     * label or an LLL:EXT:... reference to a specific label.
     */
    protected array $contentTypeLabels = [];

    /**
     * Labels for columns, in format of TCA select options. Numerically indexed
     * array of numerically indexed value arrays, with each sub-array containing
     * at least two values and one optional third value:
     *
     * - label (hardcoded or LLL:EXT:... reference. MANDATORY)
     * - value (colPos of column. MANDATORY)
     * - icon (icon name or file reference. OPTIONAL)
     */
    protected array $itemLabels = [];

    public function __construct(
        protected readonly array $pageRecord,
        protected readonly BackendLayout $backendLayout,
        protected readonly SiteInterface $site,
        protected readonly DrawingConfiguration $drawingConfiguration,
        protected readonly ServerRequestInterface $request
    ) {
        $this->pageId = (int)($pageRecord['uid'] ?? 0);
        $this->contentFetcher = GeneralUtility::makeInstance(ContentFetcher::class, $this);
        $this->siteLanguages = $this->site->getAvailableLanguages($this->getBackendUser(), true, $this->pageId);
        $this->siteLanguage = $this->site->getDefaultLanguage();
    }

    public function cloneForLanguage(SiteLanguage $language): self
    {
        $copy = clone $this;
        $copy->setSiteLanguage($language);
        return $copy;
    }

    protected function setSiteLanguage(SiteLanguage $siteLanguage): void
    {
        $this->siteLanguage = $siteLanguage;
        $languageId = $siteLanguage->getLanguageId();
        if ($languageId > 0) {
            $pageLocalizationRecord = BackendUtility::getRecordLocalization(
                'pages',
                $this->getPageId(),
                $languageId
            );
            $pageLocalizationRecord = reset($pageLocalizationRecord);
            if (!empty($pageLocalizationRecord)) {
                BackendUtility::workspaceOL('pages', $pageLocalizationRecord);
                $this->localizedPageRecord = $pageLocalizationRecord ?: null;
            }
        }
    }

    public function getBackendLayout(): BackendLayout
    {
        return $this->backendLayout;
    }

    public function getDrawingConfiguration(): DrawingConfiguration
    {
        return $this->drawingConfiguration;
    }

    public function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    public function getPageRecord(): array
    {
        return $this->pageRecord;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    /**
     * @return SiteLanguage[]
     */
    public function getSiteLanguages(): iterable
    {
        return $this->siteLanguages;
    }

    /**
     * @return SiteLanguage[]
     */
    public function getLanguagesToShow(): iterable
    {
        $selectedLanguageId = $this->drawingConfiguration->getSelectedLanguageId();
        if ($selectedLanguageId === -1) {
            $languages = $this->siteLanguages;
            if (!isset($languages[0])) {
                // $languages may not contain the default (0) in case the user does not have access to it.
                // However, as for selected pages, it should also be displayed readonly in the "all languages" view
                $languages = [
                    $this->site->getDefaultLanguage(),
                    ...$languages,
                ];
            }
            return $languages;
        }
        if ($selectedLanguageId > 0) {
            // A specific language is selected; compose a list of default language plus selected language
            return [
                $this->site->getDefaultLanguage(),
                $this->site->getLanguageById($selectedLanguageId),
            ];
        }
        return [$this->site->getDefaultLanguage()];
    }

    public function getSiteLanguage(?int $languageId = null): SiteLanguage
    {
        if ($languageId === null) {
            return $this->siteLanguage;
        }
        if ($languageId === -1) {
            return $this->siteLanguages[-1];
        }
        return $this->site->getLanguageById($languageId);
    }

    public function isPageEditable(): bool
    {
        // TODO: refactor to page permissions container
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        return !$this->pageRecord['editlock'] && $this->getBackendUser()->doesUserHaveAccess($this->pageRecord, Permission::PAGE_EDIT);
    }

    public function getAllowNewContent(): bool
    {
        $allowInconsistentLanguageHandling = $this->drawingConfiguration->getAllowInconsistentLanguageHandling();
        if (!$allowInconsistentLanguageHandling && $this->getLanguageModeIdentifier() === 'connected') {
            return false;
        }
        return true;
    }

    public function getContentTypeLabels(): array
    {
        if (empty($this->contentTypeLabels)) {
            foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $val) {
                $this->contentTypeLabels[$val['value']] = $this->getLanguageService()->sL($val['label']);
            }
        }
        return $this->contentTypeLabels;
    }

    public function getItemLabels(): array
    {
        if (empty($this->itemLabels)) {
            foreach ($GLOBALS['TCA']['tt_content']['columns'] as $name => $val) {
                $this->itemLabels[$name] = $this->getLanguageService()->sL($val['label'] ?? '');
            }
        }
        return $this->itemLabels;
    }

    public function getLanguageModeLabelClass(): string
    {
        $languageId = $this->siteLanguage->getLanguageId();
        $contentRecordsPerColumn = $this->contentFetcher->getFlatContentRecords($languageId);
        $translationData = $this->contentFetcher->getTranslationData($contentRecordsPerColumn, $languageId);
        return $translationData['mode'] === 'mixed' ? 'danger' : 'info';
    }

    public function getLanguageMode(): string
    {
        return match ($this->getLanguageModeIdentifier()) {
            'mixed' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:languageModeMixed'),
            'connected' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:languageModeConnected'),
            'free' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:languageModeFree'),
            default => '',
        };
    }

    public function getLanguageModeIdentifier(): string
    {
        $contentRecordsPerColumn = $this->contentFetcher->getContentRecordsPerColumn(null, $this->siteLanguage->getLanguageId());
        $contentRecords = empty($contentRecordsPerColumn) ? [] : array_merge(...$contentRecordsPerColumn);
        $translationData = $this->contentFetcher->getTranslationData($contentRecords, $this->siteLanguage->getLanguageId());
        return $translationData['mode'] ?? '';
    }

    public function getNewLanguageOptions(): array
    {
        if (!$this->getBackendUser()->check('tables_modify', 'pages')) {
            return [];
        }

        // First, select all languages that are available for the current user
        $availableTranslations = [];
        foreach ($this->getSiteLanguages() as $language) {
            if ($language->getLanguageId() <= 0) {
                continue;
            }
            $availableTranslations[$language->getLanguageId()] = $language->getTitle();
        }

        // Then, subtract the languages which are already on the page:
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($this->pageId, Connection::PARAM_INT)
                )
            );
        $statement = $queryBuilder->executeQuery();
        while ($row = $statement->fetchAssociative()) {
            BackendUtility::workspaceOL('pages', $row, $this->getBackendUser()->workspace);
            if ($row && VersionState::tryFrom($row['t3ver_state']) !== VersionState::DELETE_PLACEHOLDER) {
                unset($availableTranslations[(int)$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']]]);
            }
        }
        // If any languages are left, make selector:
        $options = [];
        if (!empty($availableTranslations)) {
            $options[] = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:new_language');
            foreach ($availableTranslations as $languageUid => $languageTitle) {
                // Build localize command URL to DataHandler (tce_db)
                // which redirects to FormEngine (record_edit)
                // which, when finished editing should return back to the current page (returnUrl)
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $targetUrl = (string)$uriBuilder->buildUriFromRoute(
                    'tce_db',
                    [
                        'cmd' => [
                            'pages' => [
                                $this->pageId => [
                                    'localize' => $languageUid,
                                ],
                            ],
                        ],
                        'redirect' => (string)$uriBuilder->buildUriFromRoute(
                            'record_edit',
                            [
                                'justLocalized' => 'pages:' . $this->pageId . ':' . $languageUid,
                                'returnUrl' => $this->getCurrentRequest()->getAttribute('normalizedParams')->getRequestUri(),
                            ]
                        ),
                    ]
                );
                $options[$targetUrl] = $languageTitle;
            }
        }
        return $options;
    }

    public function getCurrentRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getLocalizedPageTitle(): string
    {
        return $this->localizedPageRecord['title'] ?? $this->pageRecord['title'];
    }

    public function getLocalizedPageRecord(): ?array
    {
        return $this->localizedPageRecord;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
