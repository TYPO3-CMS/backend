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

readonly class WizardProviderFactory
{
    /**
     * @param iterable<WizardProviderInterface> $wizardProviders
     */
    public function __construct(
        private iterable $wizardProviders
    ) {}

    public function getProvider(string $name): WizardProviderInterface
    {
        foreach ($this->wizardProviders as $wizardProvider) {
            if ($wizardProvider->getName() === $name) {
                return $wizardProvider;
            }
        }

        throw new \RuntimeException("WizardProvider '$name' not found", 1772114079);
    }
}
