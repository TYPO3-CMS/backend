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

final readonly class SubmissionResult implements \JsonSerializable
{
    private function __construct(
        private bool $success = true,
        private ?Finisher $finisher = null,
        private array $errors = []
    ) {}

    public function jsonSerialize(): mixed
    {
        $data = [
            'success' => $this->success,
        ];

        if ($this->finisher !== null) {
            $data['finisher'] = $this->finisher->jsonSerialize();
        }

        if (!empty($this->errors)) {
            $data['errors'] = $this->errors;
        }

        return $data;
    }

    public static function createSuccessResult(
        Finisher $finisher
    ): self {
        return new self(
            success: true,
            finisher: $finisher
        );
    }

    /**
     * @param string[] $errors
     */
    public static function createErrorResult(array $errors): self
    {
        return new self(
            success: false,
            errors: $errors
        );
    }
}
