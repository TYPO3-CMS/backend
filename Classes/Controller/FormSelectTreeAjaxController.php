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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Dto\Tree\SelectTreeItem;
use TYPO3\CMS\Backend\Dto\Tree\TreeItem;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaSelectTreeAjaxFieldData;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend controller for selectTree ajax operations
 */
#[AsController]
class FormSelectTreeAjaxController
{
    public function __construct(
        private readonly FormDataCompiler $formDataCompiler,
        private readonly FlexFormTools $flexFormTools,
        private readonly TcaSchemaFactory $schemaFactory,
    ) {}

    /**
     * Returns json representing category tree
     *
     * @throws \RuntimeException
     */
    public function fetchDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $tableName = $request->getQueryParams()['tableName'] ?? '';
        $fieldName = $request->getQueryParams()['fieldName'] ?? '';

        // Prepare processedTca: Remove all column definitions except the one that contains
        // our tree definition. This way only this field is calculated, everything else is ignored.
        if (!$this->schemaFactory->has($tableName)) {
            throw new \RuntimeException(
                'TCA for table ' . $tableName . ' not found',
                1479386729
            );
        }
        $schema = $this->schemaFactory->get($tableName);
        if (!$schema->hasField($fieldName)) {
            throw new \RuntimeException(
                'TCA for table ' . $tableName . ' and field ' . $fieldName . ' not found',
                1479386990
            );
        }

        // @todo Replace with a mutable schema
        $processedTca = $GLOBALS['TCA'][$tableName];

        // Force given record type and set showitem to our field only
        $recordTypeValue = $request->getQueryParams()['recordTypeValue'];
        $processedTca['types'][$recordTypeValue]['showitem'] = $fieldName;
        // Unset all columns except our field
        $processedTca['columns'] = [
            $fieldName => $processedTca['columns'][$fieldName],
        ];

        $dataStructureIdentifier = '';
        $flexFormSheetName = '';
        $flexFormFieldName = '';
        $flexFormContainerIdentifier = '';
        $flexFormContainerFieldName = '';
        $flexSectionContainerPreparation = [];
        if ($processedTca['columns'][$fieldName]['config']['type'] === 'flex') {
            if (!empty($request->getQueryParams()['dataStructureIdentifier'])) {
                $dataStructureIdentifier = $request->getQueryParams()['dataStructureIdentifier'];
            }
            $flexFormSheetName = $request->getQueryParams()['flexFormSheetName'];
            $flexFormFieldName = $request->getQueryParams()['flexFormFieldName'];
            $flexFormContainerName = $request->getQueryParams()['flexFormContainerName'];
            $flexFormContainerIdentifier = $request->getQueryParams()['flexFormContainerIdentifier'];
            $flexFormContainerFieldName = $request->getQueryParams()['flexFormContainerFieldName'];
            $flexFormSectionContainerIsNew = (bool)$request->getQueryParams()['flexFormSectionContainerIsNew'];

            $dataStructure = $this->flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier, $schema);

            // Reduce given data structure down to the relevant element only
            if (empty($flexFormContainerFieldName)) {
                if (isset($dataStructure['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName])
                ) {
                    $dataStructure = [
                        'sheets' => [
                            $flexFormSheetName => [
                                'ROOT' => [
                                    'type' => 'array',
                                    'el' => [
                                        $flexFormFieldName => $dataStructure['sheets'][$flexFormSheetName]['ROOT']
                                            ['el'][$flexFormFieldName],
                                    ],
                                ],
                            ],
                        ],
                    ];
                }
            } elseif (isset($dataStructure['sheets'][$flexFormSheetName]['ROOT']
                ['el'][$flexFormFieldName]
                ['el'][$flexFormContainerName]
                ['el'][$flexFormContainerFieldName])
            ) {
                // If this is a tree in a section container that has just been added by the FlexFormAjaxController
                // "new container" action, then this container is not yet persisted, so we need to trigger the
                // TcaFlexProcess data provider again to prepare the DS and databaseRow of that container.
                if ($flexFormSectionContainerIsNew) {
                    $flexSectionContainerPreparation = [
                        'flexFormSheetName' => $flexFormSheetName,
                        'flexFormFieldName' => $flexFormFieldName,
                        'flexFormContainerName' => $flexFormContainerName,
                        'flexFormContainerIdentifier' => $flexFormContainerIdentifier,
                    ];
                }
                // Now restrict the data structure to our tree element only
                $dataStructure = [
                    'sheets' => [
                        $flexFormSheetName => [
                            'ROOT' => [
                                'type' => 'array',
                                'el' => [
                                    $flexFormFieldName => [
                                        'section' => 1,
                                        'type' => 'array',
                                        'el' => [
                                            $flexFormContainerName => [
                                                'type' => 'array',
                                                'el' => [
                                                    $flexFormContainerFieldName => $dataStructure['sheets'][$flexFormSheetName]['ROOT']
                                                        ['el'][$flexFormFieldName]
                                                        ['el'][$flexFormContainerName]
                                                        ['el'][$flexFormContainerFieldName],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            }
            $processedTca['columns'][$fieldName]['config']['ds'] = $dataStructure;
            $processedTca['columns'][$fieldName]['config']['dataStructureIdentifier'] = $dataStructureIdentifier;
        }

        $formDataCompilerInput = [
            'request' => $request,
            'tableName' => $tableName,
            'vanillaUid' => (int)$request->getQueryParams()['uid'],
            'command' => $request->getQueryParams()['command'],
            'processedTca' => $processedTca,
            'recordTypeValue' => $recordTypeValue,
            'selectTreeCompileItems' => true,
            'flexSectionContainerPreparation' => $flexSectionContainerPreparation,
        ];
        if (!empty($request->getQueryParams()['overrideValues'])) {
            $formDataCompilerInput['overrideValues'] = json_decode($request->getQueryParams()['overrideValues'], true);
        }
        if (!empty($request->getQueryParams()['defaultValues'])) {
            $formDataCompilerInput['defaultValues'] = json_decode($request->getQueryParams()['defaultValues'], true);
        }
        $formData = $this->formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(TcaSelectTreeAjaxFieldData::class));

        if ($formData['processedTca']['columns'][$fieldName]['config']['type'] === 'flex') {
            if (empty($flexFormContainerFieldName)) {
                $treeData = $formData['processedTca']['columns'][$fieldName]['config']['ds']
                    ['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName]['config']['items'];
            } else {
                $treeData = $formData['processedTca']['columns'][$fieldName]['config']['ds']
                    ['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName]
                    ['children'][$flexFormContainerIdentifier]
                    ['el'][$flexFormContainerFieldName]['config']['items'];
            }
        } else {
            $treeData = $formData['processedTca']['columns'][$fieldName]['config']['items'];
        }

        $data = [];
        foreach ($treeData ?? [] as $item) {
            $treeItem = new SelectTreeItem(
                item: new TreeItem(
                    identifier: (string)$item['identifier'],
                    parentIdentifier: (string)($item['parentIdentifier'] ?? ''),
                    recordType: (string)($item['recordType'] ?? ''),
                    name: (string)($item['name'] ?? ''),
                    prefix: (string)($item['prefix'] ?? ''),
                    suffix: (string)($item['suffix'] ?? ''),
                    tooltip: (string)($item['tooltip'] ?? ''),
                    depth: (int)($item['depth'] ?? 0),
                    hasChildren: (bool)($item['hasChildren'] ?? false),
                    loaded: true,
                    icon: (string)($item['icon'] ?? ''),
                    overlayIcon: (string)($item['overlayIcon'] ?? ''),
                    statusInformation: (array)($item['statusInformation'] ?? []),
                    labels: (array)($item['labels'] ?? []),
                ),
                checked: (bool)($item['checked'] ?? false),
                selectable: (bool)($item['selectable'] ?? false),
            );
            $data[] = $treeItem;
        }

        return new JsonResponse($data);
    }
}
