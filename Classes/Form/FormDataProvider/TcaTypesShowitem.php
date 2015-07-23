<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Create final showitem configuration in processedTca for types and palette fields
 * Handles all the nasty defails like subtypes_addlist and friends.
 */
class TcaTypesShowitem implements FormDataProviderInterface {

	/**
	 * Processed TCA array
	 *
	 * @var array
	 */
	protected $processedTca;

	/**
	 * Set processedTca showitem
	 *
	 * @param array $result
	 * @return array
	 */
	public function addData(array $result) {
		$this->processedTca = $result['processedTca'];

		$recordTypeValue = $result['recordTypeValue'];

		// Inline may override the type value - setting is given down from InlineRecordContainer if so - used primarily for FAL
		if (!empty($result['overruleTypesArray'][$recordTypeValue]['showitem'])) {
			$result['processedTca']['types'][$recordTypeValue]['showitem'] = $result['overruleTypesArray'][$recordTypeValue]['showitem'];
		}

		// Handle subtype_value_field, subtypes_addlist, subtypes_excludelist
		if (!empty($result['processedTca']['types'][$recordTypeValue]['subtype_value_field'])) {
			$subtypeFieldName = $result['processedTca']['types'][$recordTypeValue]['subtype_value_field'];
			if (array_key_exists($subtypeFieldName, $result['databaseRow'])) {
				$subtypeValue = $result['databaseRow'][$subtypeFieldName];
				$result = $this->addFieldsBySubtypeAddList($result, $subtypeFieldName, $subtypeValue, $recordTypeValue);
				$result = $this->removeFieldsBySubtypeExcludeList($result, $subtypeValue, $recordTypeValue);
			}
		}

		// Handle bitmask_value_field, bitmask_excludelist_bits
		if (!empty($result['processedTca']['types'][$recordTypeValue]['bitmask_value_field'])
			&& isset($result['processedTca']['types'][$recordTypeValue]['bitmask_excludelist_bits'])
			&& is_array($result['processedTca']['types'][$recordTypeValue]['bitmask_excludelist_bits'])
		) {
			$bitmaskFieldName = $result['processedTca']['types'][$recordTypeValue]['bitmask_value_field'];
			if (array_key_exists($bitmaskFieldName, $result['databaseRow'])) {
				$bitmaskValue = $result['databaseRow'][$bitmaskFieldName];
				$result = $this->removeFieldsByBitmaskExcludeBits($result, $bitmaskValue, $recordTypeValue);
			}
		}

		$result = $this->removeObsoleteColumns($result);

		// Handling of these parameters is finished. Unset them to not allow other handlers to fiddle with it.
		// unset does not throw notice, even if not set
		unset($result['processedTca']['types'][$recordTypeValue]['subtype_value_field']);
		unset($result['processedTca']['types'][$recordTypeValue]['subtypes_excludelist']);
		unset($result['processedTca']['types'][$recordTypeValue]['subtypes_addlist']);
		unset($result['processedTca']['types'][$recordTypeValue]['bitmask_value_field']);
		unset($result['processedTca']['types'][$recordTypeValue]['bitmask_excludelist_bits']);

		return $result;
	}

	/**
	 * Insert additional fields in showitem based on subtypes_addlist
	 *
	 * databaseRow['theSubtypeValueField'] = 'theSubtypeValue'
	 * showitem = 'foo,theSubtypeValueField,bar'
	 * subtype_value_field = 'theSubtypeValueField'
	 * subtypes_addlist['theSubtypeValue'] = 'additionalField'
	 *
	 * -> showitem = 'foo,theSubtypeValueField,additionalField,bar'
	 *
	 * @param array $result Result array
	 * @param string $subtypeFieldName Field name holding subtype value
	 * @param string $subtypeValue subtype value
	 * @param string $recordTypeValue Given record type value
	 * @return array Modified result array
	 */
	protected function addFieldsBySubtypeAddList(array $result, $subtypeFieldName, $subtypeValue, $recordTypeValue) {
		if (!empty($this->processedTca['types'][$recordTypeValue]['subtypes_addlist'][$subtypeValue])
			&& is_string($this->processedTca['types'][$recordTypeValue]['subtypes_addlist'][$subtypeValue])
		) {
			$addListString = $this->processedTca['types'][$recordTypeValue]['subtypes_addlist'][$subtypeValue];
			$addListArray = GeneralUtility::trimExplode(',', $addListString, TRUE);
			$showItemFieldString = $result['processedTca']['types'][$recordTypeValue]['showitem'];
			$showItemFieldArray = GeneralUtility::trimExplode(',', $showItemFieldString, TRUE);
			// The "new" fields should be added after the subtype field itself, so find it
			foreach ($showItemFieldArray as $index => $fieldConfigurationString) {
				$found = FALSE;
				$fieldConfigurationArray = GeneralUtility::trimExplode(';', $fieldConfigurationString);
				$fieldName = $fieldConfigurationArray[0];
				if ($fieldName === $subtypeFieldName) {
					// Found the subtype value field in showitem
					$found = TRUE;
				} elseif ($fieldName === '--palette--') {
					// Try to find subtype value field in palette
					if (isset($fieldConfigurationArray[2])) {
						$paletteName = $fieldConfigurationArray[2];
						if (!empty($this->processedTca['palettes'][$paletteName]['showitem'])) {
							$paletteFields = GeneralUtility::trimExplode(',', $this->processedTca['palettes'][$paletteName]['showitem'], TRUE);
							foreach ($paletteFields as $paletteFieldConfiguration) {
								$paletteFieldConfigurationArray = GeneralUtility::trimExplode(';', $paletteFieldConfiguration);
								if ($paletteFieldConfigurationArray[0] === $subtypeFieldName) {
									// Found it in palette
									$found = TRUE;
									break;
								}
							}
						}
					}
				}
				if ($found) {
					// Add fields between subtype field and next element
					array_splice($showItemFieldArray, $index + 1, 0, $addListArray);
					break;
				}
			}
			$result['processedTca']['types'][$recordTypeValue]['showitem'] = implode(',', $showItemFieldArray);
		}
		return $result;
	}

	/**
	 * Remove fields from showitem based on subtypes_excludelist
	 *
	 * databaseRow['theSubtypeValueField'] = 'theSubtypeValue'
	 * showitem = 'foo,toRemove,bar'
	 * subtype_value_field = 'theSubtypeValueField'
	 * subtypes_excludelist['theSubtypeValue'] = 'toRemove'
	 *
	 * -> showitem = 'foo,bar'
	 *
	 * @param array $result Result array
	 * @param string $subtypeValue subtype value
	 * @param string $recordTypeValue Given record type value
	 * @return array Modified result array
	 */
	protected function removeFieldsBySubtypeExcludeList(array $result, $subtypeValue, $recordTypeValue) {
		if (!empty($this->processedTca['types'][$recordTypeValue]['subtypes_excludelist'][$subtypeValue])
			&& is_string($this->processedTca['types'][$recordTypeValue]['subtypes_excludelist'][$subtypeValue])
		) {
			$removeListString = $this->processedTca['types'][$recordTypeValue]['subtypes_excludelist'][$subtypeValue];
			$removeListArray = GeneralUtility::trimExplode(',', $removeListString, TRUE);
			$result = $this->removeFields($result, $removeListArray, $recordTypeValue);
			$result = $this->removeFieldsFromPalettes($result, $removeListArray);
		}
		return $result;
	}

	/**
	 * Remove fields from showitem based on subtypes_excludelist
	 *
	 * databaseRow['theSubtypeValueField'] = 5 // 1 0 1
	 * showitem = 'foo,toRemoveBy4,bar'
	 * bitmask_value_field = 'theSubtypeValueField'
	 * bitmask_excludelist_bits[+2] = 'toRemoveBy4'
	 *
	 * -> showitem = 'foo,bar'
	 *
	 * @param array $result Result array
	 * @param string $bitmaskValue subtype value
	 * @param string $recordTypeValue Given record type value
	 * @return array Modified result array
	 */
	protected function removeFieldsByBitmaskExcludeBits(array $result, $bitmaskValue, $recordTypeValue) {
		$removeListArray = array();
		$bitmaskValue = MathUtility::forceIntegerInRange($bitmaskValue, 0);
		$excludeListBitsArray = $this->processedTca['types'][$recordTypeValue]['bitmask_excludelist_bits'];
		foreach ($excludeListBitsArray as $bitKey => $excludeList) {
			$bitKey = (int)$bitKey;
			$isNegative = (bool)($bitKey < 0);
			$bit = abs($bitKey);
			if (!$isNegative && ($bitmaskValue & pow(2, $bit))
				|| $isNegative && !($bitmaskValue & pow(2, $bit))
			) {
				$removeListArray = array_merge($removeListArray, GeneralUtility::trimExplode(',', $excludeList, TRUE));
			}
		}
		$result = $this->removeFields($result, $removeListArray, $recordTypeValue);
		return $this->removeFieldsFromPalettes($result, $removeListArray);
	}

	/**
	 * Remove fields from show item field list
	 *
	 * @param array $result Given show item list
	 * @param array $removeListArray Fields to remove
	 * @param string $recordTypeValue Given record type value
	 * @return array Modified result array
	 */
	protected function removeFields(array $result, array $removeListArray, $recordTypeValue) {
		$newFieldList = array();
		$showItemFieldString = $result['processedTca']['types'][$recordTypeValue]['showitem'];
		$showItemFieldArray = GeneralUtility::trimExplode(',', $showItemFieldString, TRUE);
		foreach ($showItemFieldArray as $fieldConfigurationString) {
			$fieldConfigurationArray = GeneralUtility::trimExplode(';', $fieldConfigurationString);
			$fieldName = $fieldConfigurationArray[0];
			if (!in_array($fieldConfigurationArray[0], $removeListArray, TRUE)
				// It does not make sense to exclude --palette-- and --div--
				|| $fieldName === '--palette--' || $fieldName === '--div--'
			) {
				$newFieldList[] = $fieldConfigurationString;
			}
		}
		$result['processedTca']['types'][$recordTypeValue]['showitem'] = implode(',', $newFieldList);
		return $result;
	}

	/**
	 * Remove a list of element from all palettes
	 *
	 * @param array $result Result array
	 * @param array $removeListArray Array of elements to remove
	 * @return array Modified result array
	 * @todo: unit tests!
	 */
	protected function removeFieldsFromPalettes(array $result, $removeListArray) {
		if (isset($result['processedTca']['palettes']) && is_array($result['processedTca']['palettes'])) {
			foreach ($result['processedTca']['palettes'] as $paletteName => $paletteArray) {
				if (!isset($paletteArray['showitem']) || !is_string($paletteArray['showitem'])) {
					throw new \UnexpectedValueException(
						'showitem field of palette ' . $paletteName . ' in table ' . $result['tableName'] . ' not found or not a string',
						1439925240
					);
				}
				$showItemFieldString = $paletteArray['showitem'];
				$showItemFieldArray = GeneralUtility::trimExplode(',', $showItemFieldString, TRUE);
				$newFieldList = array();
				foreach ($showItemFieldArray as $fieldConfigurationString) {
					$fieldConfigurationArray = GeneralUtility::trimExplode(';', $fieldConfigurationString);
					$fieldName = $fieldConfigurationArray[0];
					if (!in_array($fieldConfigurationArray[0], $removeListArray, TRUE)
						|| $fieldName === '--linebreak--'
					) {
						$newFieldList[] = $fieldConfigurationString;
					}
				}
				$result['processedTca']['palettes'][$paletteName]['showitem'] = implode(',', $newFieldList);
			}
		}
		return $result;
	}

	/**
	 * Remove fields from columns not in showitem or palette list.
	 * This is a relatively effective performance improvement preventing other providers from
	 * resolving stuff of fields that are not show later. Especially effective for fal related tables
	 *
	 * @param array $result Given result
	 * @return array Modified result
	 * @todo: Unit tests missing for this one
	 */
	protected function removeObsoleteColumns(array $result) {
		$recordTypeValue = $result['recordTypeValue'];
		if (!empty($result['processedTca']['types'][$recordTypeValue]['showitem'])
			&& is_string($result['processedTca']['types'][$recordTypeValue]['showitem'])
			&& !empty($result['processedTca']['columns'])
			&& is_array($result['processedTca']['columns'])
		) {
			$showItemFieldString = $result['processedTca']['types'][$recordTypeValue]['showitem'];
			$showItemFieldArray = GeneralUtility::trimExplode(',', $showItemFieldString, TRUE);
			$shownColumnFields = [];
			foreach ($showItemFieldArray as $index => $fieldConfigurationString) {
				$fieldConfigurationArray = GeneralUtility::trimExplode(';', $fieldConfigurationString);
				$fieldName = $fieldConfigurationArray[0];
				if ($fieldName === '--div--') {
					continue;
				}
				if ($fieldName === '--palette--') {
					if (isset($fieldConfigurationArray[2])) {
						$paletteName = $fieldConfigurationArray[2];
						if (!empty($result['processedTca']['palettes'][$paletteName]['showitem'])) {
							$paletteFields = GeneralUtility::trimExplode(',', $result['processedTca']['palettes'][$paletteName]['showitem'], TRUE);
							foreach ($paletteFields as $paletteFieldConfiguration) {
								$paletteFieldConfigurationArray = GeneralUtility::trimExplode(';', $paletteFieldConfiguration);
								$shownColumnFields[] = $paletteFieldConfigurationArray[0];
							}
						}
					}
				} else {
					$shownColumnFields[] = $fieldName;
				}
			}
			array_unique($shownColumnFields);
			$columns = array_keys($result['processedTca']['columns']);
			foreach ($columns as $column) {
				if (!in_array($column, $shownColumnFields)) {
					unset ($result['processedTca']['columns'][$column]);
				}
			}
		}
		return $result;
	}
}
