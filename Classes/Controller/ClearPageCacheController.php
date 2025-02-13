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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Allows to clear the page cache of a given page uid.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
readonly class ClearPageCacheController
{
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $pageUid = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $message = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:clearcache.message.error');
        $success = false;
        $permissionClause = $this->getBackendUserAuthentication()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageRow = BackendUtility::readPageAccess($pageUid, $permissionClause);
        if ($pageUid !== 0 && $this->getBackendUserAuthentication()->doesUserHaveAccess($pageRow, Permission::PAGE_SHOW)) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([], []);
            $dataHandler->clear_cacheCmd($pageUid);
            $message = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:clearcache.message.success');
            $success = true;
        }
        return new JsonResponse([
            'success' => $success,
            'title' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:clearcache.title'),
            'message' => $message,
        ]);
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
