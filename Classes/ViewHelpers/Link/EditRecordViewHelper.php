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

namespace TYPO3\CMS\Backend\ViewHelpers\Link;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Use this ViewHelper to provide edit links to records. The ViewHelper will
 * pass the uid and table to FormEngine.
 *
 * The uid must be given as a positive integer.
 * For new records, use the :ref:`<be:link.newRecordViewHelper> <typo3-backend-link-newrecord>`.
 *
 * Examples
 * ========
 *
 * Link to the record-edit action passed to FormEngine::
 *
 *    <be:link.editRecord uid="42" table="a_table" returnUrl="foo/bar" />
 *
 * Output::
 *
 *    <a href="/typo3/record/edit?edit[a_table][42]=edit&returnUrl=foo/bar">
 *        Edit record
 *    </a>
 *
 * Link to edit page uid=3 and then return back to the BE module "web_MyextensionList"::
 *
 *    <be:link.editRecord uid="3" table="pages" returnUrl="{f:be.uri(route: 'web_MyextensionList')}">
 *
 * Link to edit only the fields title and subtitle of page uid=42 and return to foo/bar::
 *
 *    <be:link.editRecord uid="42" table="pages" fields="title,subtitle" returnUrl="foo/bar">
 *        Edit record
 *    </be:link.editRecord>
 *
 * Output::
 *
 *    <a href="/typo3/record/edit?edit[pages][42]=edit&returnUrl=foo/bar&columnsOnly[pages]=title,subtitle">
 *        Edit record
 *    </a>
 */
final class EditRecordViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('uid', 'int', 'uid of record to be edited', true);
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('fields', 'string', 'Edit only these fields (comma separated list)');
        $this->registerArgument('returnUrl', 'string', 'return to this URL after closing the edit dialog', false, '');
    }

    /**
     * @throws \InvalidArgumentException
     * @throws RouteNotFoundException
     */
    public function render(): string
    {
        if ($this->arguments['uid'] < 1) {
            throw new \InvalidArgumentException('Uid must be a positive integer, ' . $this->arguments['uid'] . ' given.', 1526127158);
        }
        if (empty($this->arguments['returnUrl'])
            && $this->renderingContext->hasAttribute(ServerRequestInterface::class)
        ) {
            // @todo: We may want to deprecate fetching returnUrl from request
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
            $this->arguments['returnUrl'] = $request->getAttribute('normalizedParams')->getRequestUri();
        }

        $params = [
            'edit' => [$this->arguments['table'] => [$this->arguments['uid'] => 'edit']],
            'returnUrl' => $this->arguments['returnUrl'],
        ];
        if ($this->arguments['fields'] ?? false) {
            $params['columnsOnly'] = [
                $this->arguments['table'] => GeneralUtility::trimExplode(',', $this->arguments['fields'], true),
            ];
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = (string)$uriBuilder->buildUriFromRoute('record_edit', $params);
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent((string)$this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
