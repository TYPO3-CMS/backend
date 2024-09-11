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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Backend\ColorScheme;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;

#[AsController]
class ColorSchemeController
{
    public function updateAction(ServerRequestInterface $request): ResponseInterface
    {
        $colorScheme = $request->getParsedBody()['colorScheme'];

        if ($request->getMethod() !== 'POST' || !ColorScheme::tryFrom($colorScheme)) {
            return new JsonResponse(null, 400);
        }

        $backendUser = $this->getBackendUser();
        $backendUser->uc['colorScheme'] = $colorScheme;
        $backendUser->writeUC();

        return new Response(null);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
