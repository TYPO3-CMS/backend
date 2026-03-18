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

namespace TYPO3\CMS\Backend\Form\FieldInformation;

use TYPO3\CMS\Backend\Form\AbstractNode;

/**
 * Render localized ['columns']['theField']['description'] text as default
 * field information node. This is typically displayed in elements below the
 * element label and the field content.
 *
 * @deprecated since v14.2, will be removed in v15. Description is now rendered next to the label
 *             by AbstractFormElement::renderDescription() and AbstractContainer::wrapWithFieldsetAndLegend().
 */
class TcaDescription extends AbstractNode
{
    /**
     * Handler for single nodes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        trigger_error(
            'TcaDescription renderType has been deprecated in v14.2 and will be removed in v15.'
            . ' Field descriptions are now rendered next to the label automatically.',
            E_USER_DEPRECATED
        );
        return $this->initializeResultArray();
    }
}
