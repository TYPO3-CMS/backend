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

namespace TYPO3\CMS\Backend\Form\Event;

use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;

/**
 * Listeners to this Event will be able to add custom file selectors to a
 * TCA type="file" field in FormEngine
 */
final class CustomFileSelectorsEvent
{
    public function __construct(
        private array $selectors,
        private array $javascriptModules,
        private readonly string $tableName,
        private readonly string $fieldName,
        private readonly array $databaseRow,
        private readonly array $fieldConfig,
        private readonly FileExtensionFilter $fileExtensionFilter,
        private readonly string $formFieldIdentifier,
    ) {}

    public function getSelectors(): array
    {
        return $this->selectors;
    }

    public function setSelectors(array $selectors): void
    {
        $this->selectors = $selectors;
    }

    public function getJavascriptModules(): array
    {
        return $this->javascriptModules;
    }

    public function setJavascriptModules(array $javascriptModules): void
    {
        $this->javascriptModules = $javascriptModules;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getDatabaseRow(): array
    {
        return $this->databaseRow;
    }

    public function getFieldConfig(): array
    {
        return $this->fieldConfig;
    }

    public function getFileExtensionFilter(): FileExtensionFilter
    {
        return $this->fileExtensionFilter;
    }

    public function getFormFieldIdentifier(): string
    {
        return $this->formFieldIdentifier;
    }
}
