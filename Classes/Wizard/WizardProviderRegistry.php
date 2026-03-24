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

namespace TYPO3\CMS\Backend\Wizard;

use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 */
final readonly class WizardProviderRegistry
{
    /**
     * @param ServiceLocator<WizardProviderInterface> $wizardProviders
     */
    public function __construct(
        #[AutowireLocator(
            services: 'backend.wizard.provider',
        )]
        private ServiceLocator $wizardProviders,
    ) {}

    public function getProvider(string $identifier): WizardProviderInterface
    {
        if (!$this->wizardProviders->has($identifier)) {
            throw new \RuntimeException('WizardProvider "' . $identifier . '" not found', 1772114079);
        }
        return $this->wizardProviders->get($identifier);
    }
}
