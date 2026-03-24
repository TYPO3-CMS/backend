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

namespace TYPO3\CMS\Backend\Wizard\DTO;

/**
 * DTO for wizard configuration holding all steps.
 * Used in WizardProviderInterface::getConfiguration() when dynamically loading wizard steps.
 *
 * @internal
 */
final readonly class Configuration implements \JsonSerializable
{
    /**
     * @param Step[] $steps
     */
    private function __construct(private array $steps)
    {
        foreach ($steps as $step) {
            if (!$step instanceof Step) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'All elements of $steps must be instances of WizardStepDTO, got %s',
                        get_debug_type($step)
                    ),
                    1772114103
                );
            }
        }

    }

    /**
     * @return Step[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @param Step[] $steps
     * @return self
     */
    public static function create(array $steps): self
    {
        return new self($steps);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'steps' => array_map(fn(Step $step) => $step->jsonSerialize(), $this->steps),
        ];
    }
}
