<?php

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

namespace TYPO3\CMS\Backend\Form\Element;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Create a widget with a select box where multiple items can be selected
 *
 * This is rendered for config type=select, renderType=selectSingleBox
 */
class SelectSingleBoxElement extends AbstractFormElement
{
    /**
     * Default field information enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

    /**
     * Default field controls for this element.
     *
     * @var array
     */
    protected $defaultFieldControl = [
        'resetSelection' => [
            'renderType' => 'resetSelection',
        ],
    ];

    /**
     * Default field wizards enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldWizard = [
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
        ],
        'otherLanguageContent' => [
            'renderType' => 'otherLanguageContent',
            'after' => [
                'localizationStateSelector',
            ],
        ],
        'defaultLanguageDifferences' => [
            'renderType' => 'defaultLanguageDifferences',
            'after' => [
                'otherLanguageContent',
            ],
        ],
    ];

    /**
     * This will render a selector box element, or possibly a special construction with two selector boxes.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        // Field configuration from TCA:
        $config = $parameterArray['fieldConf']['config'];
        $selectItems = $parameterArray['fieldConf']['config']['items'];
        $disabled = !empty($config['readOnly']);

        // Get item value as array and make unique, which is fine because there can be no duplicates anyway.
        $itemArray = array_flip($parameterArray['itemFormElValue']);
        $width = $this->formMaxWidth($this->defaultInputWidth);

        $optionElements = [];
        foreach ($selectItems as $item) {
            $value = $item['value'];
            $attributes = [];
            // Selected or not by default
            if (isset($itemArray[$value])) {
                $attributes['selected'] = 'selected';
                unset($itemArray[$value]);
            }
            // Non-selectable element
            if ((string)$value === '--div--') {
                $attributes['disabled'] = 'disabled';
                $attributes['class'] = 'formcontrol-select-divider';
            }
            $optionElements[] = $this->renderOptionElement($value, $item['label'], $attributes);
        }

        $selectItems = $parameterArray['fieldConf']['config']['items'];
        $size = (int)($config['size'] ?? 0);
        $autoSizeMax = (int)($config['autoSizeMax'] ?? 0);
        if ($autoSizeMax > 0) {
            $size = MathUtility::forceIntegerInRange($size, 1);
            $size = MathUtility::forceIntegerInRange(count($selectItems) + 1, $size, $autoSizeMax);
        }
        $selectId = StringUtility::getUniqueId($size === 1 ? 'tceforms-select' : 'tceforms-multiselect');
        $selectElement = $this->renderSelectElement($optionElements, $parameterArray, $config, $selectId, $size);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $html = [];
        $html[] = $this->renderLabel($selectId);
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] = $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-item-element">';
        if (!$disabled) {
            // Add an empty hidden field which will send a blank value if all items are unselected.
            $html[] =           '<input type="hidden" name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" value="">';
        }
        $html[] =               $selectElement;
        $html[] =           '</div>';
        if (!$disabled) {
            if (!empty($fieldControlHtml)) {
                $html[] =       '<div class="form-wizards-item-aside form-wizards-item-aside--field-control">';
                $html[] =           $fieldControlHtml;
                $html[] =       '</div>';
            }
            $html[] = '</div>';
            $html[] =   '<div class="form-text">';
            $html[] =       '<em>' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.holdDownCTRL')) . '</em>';
            $html[] =   '</div>';
            if (!empty($fieldWizardHtml)) {
                $html[] = '<div class="form-wizards-item-bottom">';
                $html[] = $fieldWizardHtml;
                $html[] = '</div>';
            }
        } else {
            $html[] = '</div>';
        }
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }

    /**
     * Renders a <select> element
     */
    protected function renderSelectElement(array $optionElements, array $parameterArray, array $config, string $selectId, int $size): string
    {
        $attributes = array_merge(
            [
                'name' => $parameterArray['itemFormElName'] . '[]',
                'multiple' => 'multiple',
                'id' => $selectId,
                'class' => 'form-select ',
                'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            ],
            $this->getOnFieldChangeAttrs('change', $parameterArray['fieldChangeFunc'] ?? [])
        );
        if ($size) {
            $attributes['size'] = (string)$size;
        }
        if ($config['readOnly'] ?? false) {
            $attributes['disabled'] = 'disabled';
        }

        $html = [];
        $html[] = '<select ' . GeneralUtility::implodeAttributes($attributes, true) . '>';
        $html[] =   implode(LF, $optionElements);
        $html[] = '</select>';

        return implode(LF, $html);
    }

    /**
     * Renders a single <option> element
     *
     * @param string $value The option value
     * @param string $label The option label
     * @param array $attributes Map of attribute names and values
     * @return string
     */
    protected function renderOptionElement($value, $label, array $attributes = [])
    {
        $attributes['value'] = $value;
        $html = [
            '<option ' . GeneralUtility::implodeAttributes($attributes, true, true) . '>',
            htmlspecialchars($this->appendValueToLabelInDebugMode($label, $value), ENT_COMPAT, 'UTF-8', false),
            '</option>',

        ];

        return implode('', $html);
    }
}
