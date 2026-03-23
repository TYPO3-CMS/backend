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

namespace TYPO3\CMS\Backend\Controller\Wizard;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Wizard\WizardProviderFactory;
use TYPO3\CMS\Backend\Wizard\WizardProviderInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
final readonly class WizardController
{
    public function __construct(
        private WizardProviderFactory $wizardProviderFactory
    ) {}

    public function getConfigurationAction(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(
            $this->getProviderByRequest($request)
                ->getConfiguration($request)
                ->jsonSerialize()
        );
    }

    public function submitDataAction(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(
            $this->getProviderByRequest($request)
                ->handleSubmit($request)
                ->jsonSerialize()
        );
    }

    private function getProviderByRequest(ServerRequestInterface $request): WizardProviderInterface
    {
        return $this->wizardProviderFactory->getProvider($request->getQueryParams()['mode'] ?? '');
    }
}
