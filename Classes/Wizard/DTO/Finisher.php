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

final readonly class Finisher implements \JsonSerializable
{
    private function __construct(
        private string $identifier,
        private string $module,
        private string $successTitle,
        private string $successMessage,
        private array $data = []
    ) {}

    public function withResetButton(string $resetButtonLabel): self
    {
        $newData = $this->data;
        $newData['resetButtonTitle'] = $resetButtonLabel;

        // Return a new instance with the updated data
        return new self(
            identifier: $this->identifier,
            module: $this->module,
            successTitle: $this->successTitle,
            successMessage: $this->successMessage,
            data: $newData
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'identifier' => $this->identifier,
            'module' => $this->module,
            'data' => $this->data,
            'labels' => [
                'successTitle' => $this->successTitle,
                'successDescription' => $this->successMessage,
            ],
        ];
    }

    public static function createRedirectFinisher(string $url, string $successTitle, string $successMessage): self
    {
        return new self(
            identifier: 'redirect',
            module: '@typo3/backend/wizard/finisher/redirect-finisher.js',
            successTitle: $successTitle,
            successMessage: $successMessage,
            data: [
                'url' => $url,
            ]
        );
    }

    public static function createNoopFinisher(string $successTitle, string $successMessage): self
    {
        return new self(
            identifier: 'noop',
            module: '@typo3/backend/wizard/finisher/noop-finisher.js',
            successTitle: $successTitle,
            successMessage: $successMessage,
        );
    }

    public static function createReloadFinisher(string $successTitle, string $successMessage): self
    {
        return new self(
            identifier: 'reload',
            module: '@typo3/backend/wizard/finisher/reload-finisher.js',
            successTitle: $successTitle,
            successMessage: $successMessage,
        );
    }

    public static function createCustomFinisher(
        string $identifier,
        string $module,
        string $successTitle,
        string $successMessage,
        array $data = []
    ): self {
        return new self($identifier, $module, $successTitle, $successMessage, $data);
    }
}
