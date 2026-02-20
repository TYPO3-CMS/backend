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
use TYPO3\CMS\Backend\Routing\RouteResult;
use TYPO3\CMS\Core\Localization\JavaScriptLanguageDomainProvider;

/**
 * @internal
 */
#[AsController]
final readonly class JavaScriptLanguageDomainController
{
    public function __construct(
        private JavaScriptLanguageDomainProvider $javaScriptLanguageDomainProvider,
    ) {}

    public function getLanguageDomainAction(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RouteResult $routing */
        $routing = $request->getAttribute('routing');
        $domain = $routing['domain'];
        $locale = $routing['locale'];
        return $this->javaScriptLanguageDomainProvider->createLanguageDomainResponse($domain, $locale);
    }
}
