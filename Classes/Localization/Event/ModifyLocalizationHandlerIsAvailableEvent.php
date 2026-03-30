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

namespace TYPO3\CMS\Backend\Localization\Event;

use TYPO3\CMS\Backend\Localization\LocalizationHandlerRegistry;
use TYPO3\CMS\Backend\Localization\LocalizationInstructions;

/**
 * Fired in {@see LocalizationHandlerRegistry::getAvailableHandlers()} for each registered
 * handler to allow overriding `isAvailable` state returned by the handler `isAvailable()`
 * method. Main use-case is to disable handlers for special cases not implemented in the
 * handler and mitigate the need to xclass them and reduces headaches in instances and for
 * extension developers.
 */
final class ModifyLocalizationHandlerIsAvailableEvent
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $className,
        public readonly LocalizationInstructions $instructions,
        public bool $isAvailable,
    ) {}
}
