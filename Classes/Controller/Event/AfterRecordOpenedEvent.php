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

namespace TYPO3\CMS\Backend\Controller\Event;

/**
 * Event dispatched after a record is opened for editing in FormEngine.
 *
 * This event allows extensions to track when records are opened,
 * such as maintaining lists of open documents or logging user activity.
 *
 * One event is dispatched per record being opened.
 *
 * @internal This event may change until v15 LTS
 */
final readonly class AfterRecordOpenedEvent
{
    /**
     * @param string $table The database table name
     * @param int|string $uid The record UID
     * @param array<string, mixed> $record The full database record
     */
    public function __construct(
        public string $table,
        public int|string $uid,
        public array $record,
    ) {}
}
