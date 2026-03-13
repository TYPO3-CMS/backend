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

namespace TYPO3\CMS\Backend\Form\Container;

/**
 * Entry container called from controllers.
 * It either calls a FullRecordContainer or ListOfFieldsContainer to render
 * a full record or only some fields from a full record.
 */
class FormWrapContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $options = $this->data;
        if (empty($this->data['fieldListToRender'])) {
            $options['renderType'] = 'fullRecordContainer';
        } else {
            $options['renderType'] = 'listOfFieldsContainer';
        }
        $result = $this->nodeFactory->create($options)->render();

        $childHtml = $result['html'];

        $view = $this->backendViewFactory->create($this->data['request']);

        $descriptionColumn = !empty($this->data['processedTca']['ctrl']['descriptionColumn'])
            ? $this->data['processedTca']['ctrl']['descriptionColumn'] : null;
        if ($descriptionColumn !== null && isset($this->data['databaseRow'][$descriptionColumn])) {
            $view->assign('recordDescription', $this->data['databaseRow'][$descriptionColumn]);
        }
        $readOnlyRecord = !empty($this->data['processedTca']['ctrl']['readOnly'])
            ? (bool)$this->data['processedTca']['ctrl']['readOnly'] : null;
        if ($readOnlyRecord === true) {
            $view->assign('recordReadonly', true);
        }
        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $result = $this->mergeChildReturnIntoExistingResult($result, $fieldInformationResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $result = $this->mergeChildReturnIntoExistingResult($result, $fieldWizardResult, false);

        $view->assignMultiple([
            'fieldInformationHtml' => $fieldInformationHtml,
            'fieldWizardHtml' => $fieldWizardHtml,
            'childHtml' => $childHtml,
            'isNewRecord' => $this->data['command'] === 'new',
        ]);
        $result['html'] = $view->render('Form/FormWrapContainer');
        return $result;
    }
}
