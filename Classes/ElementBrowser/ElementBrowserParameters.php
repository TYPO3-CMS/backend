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

namespace TYPO3\CMS\Backend\ElementBrowser;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Data Transfer Object for Element Browser parameters.
 *
 * Replaces the legacy pipe-delimited `bparams` string with a proper typed object
 * for cleaner communication between FormEngine and ElementBrowser.
 *
 * Legacy bparams format: "fieldRef|rteParams|rteConfig|allowedTypes|irreObjectId"
 * Example: "data[tt_content][123][image]|||gif,jpg|data-4-pages-4-nav_icon"
 *
 * @internal This class is not part of the TYPO3 Core API.
 */
final readonly class ElementBrowserParameters implements \JsonSerializable
{
    /**
     * @param string $fieldReference Form field name reference, e.g., "data[tt_content][123][image]"
     * @param string $rteParameters Legacy RTE parameters (editorNo:contentTypo3Language) - deprecated, kept for BC @deprecated Remove in v15.0
     * @param string $rteConfiguration Legacy RTE configuration (RTEtsConfigParams) - deprecated, kept for BC @deprecated Remove in v15.0
     * @param string $allowedTypes Allowed types: tables (comma-separated) for db mode, or file extensions for file mode
     * @param string $disallowedFileExtensions Disallowed file extensions (comma-separated) for file mode
     * @param string $irreObjectId IRRE uniqueness target, e.g., "data-4-pages-4-nav_icon-sys_file_reference"
     */
    public function __construct(
        public string $fieldReference = '',
        public string $rteParameters = '',
        public string $rteConfiguration = '',
        public string $allowedTypes = '',
        public string $disallowedFileExtensions = '',
        public string $irreObjectId = '',
    ) {}

    /**
     * Creates an instance from the legacy pipe-delimited bparams string.
     *
     * @param string $bparams Legacy bparams string (e.g., "data[tt_content][123][image]|||gif,jpg|data-4-pages-4-nav_icon")
     * @deprecated Remove in v15.0: This method only exists for backward compatibility with the legacy bparams format.
     */
    public static function fromBparams(string $bparams): self
    {
        $params = explode('|', $bparams);
        $allowedTypesRaw = $params[3] ?? '';

        // Parse legacy format: "allowed=jpg,png~disallowed=exe,bat" or simple "jpg,png"
        $allowedTypes = '';
        $disallowedFileExtensions = '';

        if (str_contains($allowedTypesRaw, '~') || str_contains($allowedTypesRaw, '=')) {
            $parts = GeneralUtility::trimExplode('~', $allowedTypesRaw, true);
            foreach ($parts as $part) {
                if (str_starts_with($part, 'allowed=')) {
                    $allowedTypes = preg_replace('/^allowed=/', '', $part, 1);
                } elseif (str_starts_with($part, 'disallowed=')) {
                    $disallowedFileExtensions = preg_replace('/^disallowed=/', '', $part, 1);
                }
            }
        } else {
            $allowedTypes = $allowedTypesRaw;
        }

        return new self(
            fieldReference: $params[0],
            rteParameters: $params[1] ?? '',
            rteConfiguration: $params[2] ?? '',
            allowedTypes: $allowedTypes,
            disallowedFileExtensions: $disallowedFileExtensions,
            irreObjectId: $params[4] ?? '',
        );
    }

    /**
     * Creates an instance from the current HTTP request.
     *
     * Supports both legacy bparams parameter and new separate parameters for backward compatibility.
     */
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody() ?? [];

        // @deprecated Remove me in v15.0: Check for legacy bparams parameter first (backward compatibility)
        $bparams = $parsedBody['bparams'] ?? $queryParams['bparams'] ?? null;
        if ($bparams !== null && $bparams !== '') {
            return self::fromBparams($bparams);
        }

        // Use new separate parameters
        return new self(
            fieldReference: (string)($parsedBody['fieldReference'] ?? $queryParams['fieldReference'] ?? ''),
            rteParameters: (string)($parsedBody['rteParameters'] ?? $queryParams['rteParameters'] ?? ''),
            rteConfiguration: (string)($parsedBody['rteConfiguration'] ?? $queryParams['rteConfiguration'] ?? ''),
            allowedTypes: (string)($parsedBody['allowedTypes'] ?? $queryParams['allowedTypes'] ?? ''),
            disallowedFileExtensions: (string)($parsedBody['disallowedFileExtensions'] ?? $queryParams['disallowedFileExtensions'] ?? ''),
            irreObjectId: (string)($parsedBody['irreObjectId'] ?? $queryParams['irreObjectId'] ?? ''),
        );
    }

    /**
     * Converts the parameters back to the legacy pipe-delimited bparams string.
     *
     * @return string Legacy bparams format
     * @deprecated remove this method in v15.0: This method only exists for backward compatibility with the legacy bparams format.
     */
    public function toBparams(): string
    {
        // Build legacy allowedTypes format
        $allowedTypes = $this->buildLegacyAllowedTypes();

        return implode('|', [
            $this->fieldReference,
            $this->rteParameters,
            $this->rteConfiguration,
            $allowedTypes,
            $this->irreObjectId,
        ]);
    }

    /**
     * Builds the legacy allowedTypes string format for backward compatibility.
     *
     * @deprecated remove in v15.0 together with toBparams()
     */
    private function buildLegacyAllowedTypes(): string
    {
        // If there are disallowed extensions, build the complex format
        if ($this->disallowedFileExtensions !== '') {
            $parts = [];
            if ($this->allowedTypes !== '') {
                $parts[] = 'allowed=' . $this->allowedTypes;
            }
            $parts[] = 'disallowed=' . $this->disallowedFileExtensions;
            return implode('~', $parts);
        }

        return $this->allowedTypes;
    }

    /**
     * Returns the allowed file extensions as an array.
     *
     * @return string[] List of allowed file extensions
     */
    public function getAllowedFileExtensions(): array
    {
        if ($this->allowedTypes === '' || $this->allowedTypes === '*') {
            return [];
        }

        // Skip if it looks like a table name (contains underscore typical for TYPO3 tables)
        if (str_contains($this->allowedTypes, 'sys_file')) {
            return [];
        }

        return GeneralUtility::trimExplode(',', $this->allowedTypes, true);
    }

    /**
     * Returns the disallowed file extensions as an array.
     *
     * @return string[] List of disallowed file extensions
     */
    public function getDisallowedFileExtensions(): array
    {
        if ($this->disallowedFileExtensions === '') {
            return [];
        }

        return GeneralUtility::trimExplode(',', $this->disallowedFileExtensions, true);
    }

    /**
     * Parses the allowed file extensions from the allowedTypes field.
     *
     * @return array{allowed: string[], disallowed: string[]}
     */
    public function getFileExtensions(): array
    {
        return [
            'allowed' => $this->getAllowedFileExtensions(),
            'disallowed' => $this->getDisallowedFileExtensions(),
        ];
    }

    /**
     * Parses the allowed tables from the allowedTypes field.
     *
     * @return string[] List of allowed table names
     */
    public function getAllowedTables(): array
    {
        if ($this->allowedTypes === '' || $this->allowedTypes === '*') {
            return [];
        }

        return GeneralUtility::trimExplode(',', $this->allowedTypes, true);
    }

    /**
     * Returns the field reference parsed into table name and field name.
     *
     * Parses format like "data[tt_content][123][image]" to extract
     * table name ("tt_content") and field name ("image").
     *
     * @return array{tableName: string, fieldName: string}
     */
    public function getFieldReferenceParts(): array
    {
        $result = [
            'tableName' => '',
            'fieldName' => '',
        ];

        if ($this->fieldReference === '') {
            return $result;
        }

        // Parse "data[table][uid][field]" format
        $parts = explode('[', $this->fieldReference);
        if (count($parts) >= 4) {
            // parts[1] = "table]", parts[3] = "field]"
            $result['tableName'] = rtrim($parts[1] ?? '', ']');
            $result['fieldName'] = rtrim($parts[3] ?? '', ']');
        }

        return $result;
    }

    /**
     * Returns data attributes for use in HTML elements (body tag).
     *
     * @return array<string, string|null>
     */
    public function toDataAttributes(): array
    {
        return [
            // @deprecated Remove in v15.0: data-form-field-name is a legacy attribute
            'data-form-field-name' => 'data[' . $this->fieldReference . '][' . $this->rteParameters . '][' . $this->rteConfiguration . ']',
            'data-field-reference' => $this->fieldReference,
            // @deprecated Remove in v15.0: data-rte-parameters is a legacy attribute
            'data-rte-parameters' => $this->rteParameters ?: null,
            // @deprecated Remove in v15.0: data-rte-configuration is a legacy attribute
            'data-rte-configuration' => $this->rteConfiguration ?: null,
            'data-irre-object-id' => $this->irreObjectId ?: null,
        ];
    }

    /**
     * Returns array representation of the parameters.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'fieldReference' => $this->fieldReference,
            'rteParameters' => $this->rteParameters,
            'rteConfiguration' => $this->rteConfiguration,
            'allowedTypes' => $this->allowedTypes,
            'disallowedFileExtensions' => $this->disallowedFileExtensions,
            'irreObjectId' => $this->irreObjectId,
        ];
    }

    /**
     * Returns URL query parameters array (new format).
     *
     * @return array<string, string>
     */
    public function toQueryParameters(): array
    {
        $params = [];
        if ($this->fieldReference !== '') {
            $params['fieldReference'] = $this->fieldReference;
        }
        if ($this->rteParameters !== '') {
            $params['rteParameters'] = $this->rteParameters;
        }
        if ($this->rteConfiguration !== '') {
            $params['rteConfiguration'] = $this->rteConfiguration;
        }
        if ($this->allowedTypes !== '') {
            $params['allowedTypes'] = $this->allowedTypes;
        }
        if ($this->disallowedFileExtensions !== '') {
            $params['disallowedFileExtensions'] = $this->disallowedFileExtensions;
        }
        if ($this->irreObjectId !== '') {
            $params['irreObjectId'] = $this->irreObjectId;
        }
        return $params;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
