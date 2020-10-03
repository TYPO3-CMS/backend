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

namespace TYPO3\CMS\Backend\Tests\Functional\ViewHelpers\Uri;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for \TYPO3\CMS\Backend\ViewHelpers\Uri\NewRecordViewHelper
 */
class NewRecordViewHelperTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function renderReturnsValidLinkInExplicitFormat()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Uri/NewRecordViewHelper/WithPidAndTable.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('route=/record/edit', $result);
        self::assertStringContainsString('edit[a_table][17]=new', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkForRoot()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Uri/NewRecordViewHelper/WithTable.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('route=/record/edit', $result);
        self::assertStringContainsString('edit[a_table][0]=new', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkInInlineFormat()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Uri/NewRecordViewHelper/InlineWithPidAndTable.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('route=/record/edit', $result);
        self::assertStringContainsString('edit[b_table][17]=new', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkWithReturnUrl()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Uri/NewRecordViewHelper/WithPidTableAndReturnUrl.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('route=/record/edit', $result);
        self::assertStringContainsString('edit[c_table][17]=new', $result);
        self::assertStringContainsString('returnUrl=foo/bar', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkWitPosition()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Uri/NewRecordViewHelper/WithNegativeUid.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('route=/record/edit', $result);
        self::assertStringContainsString('edit[c_table][-11]=new', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkWithDefaultValue()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Uri/NewRecordViewHelper/WithPidTableAndDefaultValue.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('route=/record/edit', $result);
        self::assertStringContainsString('edit[c_table][17]=new', $result);
        self::assertStringContainsString('defVals[c_table][c_field]=c_value', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkWithDefaultValues()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Uri/NewRecordViewHelper/WithPidTableAndDefaultValues.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('route=/record/edit', $result);
        self::assertStringContainsString('edit[c_table][17]=new', $result);
        self::assertStringContainsString('defVals[c_table][c_field]=c_value&defVals[c_table][c_field2]=c_value2', $result);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionForUidAndPid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526136338);

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Uri/NewRecordViewHelper/WithUidAndPid.html');
        $view->render();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionForInvalidUidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526136362);

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Uri/NewRecordViewHelper/WithPositiveUid.html');
        $view->render();
    }
}
