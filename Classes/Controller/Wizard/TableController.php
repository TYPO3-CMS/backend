<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller\Wizard;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Script Class for rendering the Table Wizard
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class TableController extends AbstractWizardController
{
    use PublicPropertyDeprecationTrait;

    /**
     * Properties which have been moved to protected status from public
     *
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'content' => 'Using $content of class TableController from the outside is discouraged, as this variable is only used for internal storage.',
        'inputStyle' => 'Using $inputStyle of class TableController from the outside is discouraged, as this variable is only used for internal storage.',
        'xmlStorage' => 'Using $xmlStorage of class TableController from the outside is discouraged, as this variable is only used for internal storage.',
        'numNewRows' => 'Using $numNewRows of class TableController from the outside is discouraged, as this variable is only used for internal storage.',
        'colsFieldName' => 'Using $colsFieldName of class TableController from the outside is discouraged, as this variable is only used for internal storage.',
        'P' => 'Using $P of class TableController from the outside is discouraged, as this variable is only used for internal storage.',
        'TABLECFG' => 'Using $TABLECFG of class TableController from the outside is discouraged, as this variable is only used for internal storage.',
        'tableParsing_quote' => 'Using $tableParsing_quote of class TableController from the outside is discouraged, as this variable is only used for internal storage.',
        'tableParsing_delimiter' => 'Using $tableParsing_delimiter of class TableController from the outside is discouraged, as this variable is only used for internal storage.',
     ];

    /**
     * Content accumulation for the module.
     *
     * @var string
     */
    protected $content;

    /**
     * If TRUE, <input> fields are shown instead of textareas.
     *
     * @var bool
     */
    protected $inputStyle = false;

    /**
     * If set, the string version of the content is interpreted/written as XML
     * instead of the original line-based kind. This variable still needs binding
     * to the wizard parameters - but support is ready!
     *
     * @var int
     */
    protected $xmlStorage = 0;

    /**
     * Number of new rows to add in bottom of wizard
     *
     * @var int
     */
    protected $numNewRows = 1;

    /**
     * Name of field in parent record which MAY contain the number of columns for the table
     * here hardcoded to the value of tt_content. Should be set by FormEngine parameters (from P)
     *
     * @var string
     */
    protected $colsFieldName = 'cols';

    /**
     * Wizard parameters, coming from FormEngine linking to the wizard.
     *
     * @var array
     */
    protected $P;

    /**
     * The array which is constantly submitted by the multidimensional form of this wizard.
     *
     * @var array
     */
    protected $TABLECFG;

    /**
     * Table parsing
     * quoting of table cells
     *
     * @var string
     */
    protected $tableParsing_quote;

    /**
     * delimiter between table cells
     *
     * @var string
     */
    protected $tableParsing_delimiter;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_wizards.xlf');

        // @deprecated since TYPO3 v9, will be moved out of __construct() in TYPO3 v10.0
        $this->init($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->renderContent($request);

        if (empty($response)) {
            $response = new HtmlResponse($this->moduleTemplate->renderContent());
        }

        return $response;
    }

    /**
     * Main function, rendering the table wizard
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function main()
    {
        trigger_error('TableController->main() will be replaced by protected method renderContent() in TYPO3 v10.0. Do not call from other extensions.', E_USER_DEPRECATED);

        $response = $this->renderContent($GLOBALS['TYPO3_REQUEST']);

        if ($response instanceof RedirectResponse) {
            HttpUtility::redirect($response->getHeaders()['location'][0]);
        }
    }

    /**
     * Draws the table wizard content
     *
     * @return string HTML content for the form.
     * @throws \RuntimeException
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function tableWizard()
    {
        trigger_error('TableController->tableWizard() will be replaced by protected method renderTableWizard() in TYPO3 v10.0. Do not call from other extensions.', E_USER_DEPRECATED);

        $result = $this->renderTableWizard($GLOBALS['TYPO3_REQUEST']);

        if ($result instanceof RedirectResponse) {
            HttpUtility::redirect($result->getHeaders()['location'][0]);
        }

        return $result;
    }

    /**
     * Will get and return the configuration code string
     * Will also save (and possibly redirect/exit) the content if a save button has been pressed
     *
     * @param array $row Current parent record row
     * @return array Table config code in an array
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function getConfigCode($row)
    {
        trigger_error('TableController->getConfigCode() will be replaced by protected method getConfiguration() in TYPO3 v10.0. Do not call from other extensions.', E_USER_DEPRECATED);

        $result = $this->getConfiguration($row, $GLOBALS['TYPO3_REQUEST']);

        if ($result instanceof RedirectResponse) {
            HttpUtility::redirect($result->getHeaders()['location'][0]);
        }

        return $result;
    }

    /**
     * Creates the HTML for the Table Wizard:
     *
     * @param array $configuration Table config array
     * @return string HTML for the table wizard
     * @internal
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function getTableHTML($configuration)
    {
        trigger_error('TableController->getTableHTML() will be replaced by protected method getTableWizard() in TYPO3 v10.0. Do not call from other extensions.', E_USER_DEPRECATED);
        return $this->getTableWizard($configuration);
    }

    /**
     * Detects if a control button (up/down/around/delete) has been pressed for an item and accordingly it will
     * manipulate the internal TABLECFG array
     *
     * @internal
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function changeFunc()
    {
        trigger_error('TableController->changeFunc() will be replaced by protected method manipulateTable() in TYPO3 v10.0. Do not call from other extensions.', E_USER_DEPRECATED);
        $this->manipulateTable();
    }

    /**
     * Converts the input array to a configuration code string
     *
     * @param array $cfgArr Array of table configuration (follows the input structure from the table wizard POST form)
     * @return string The array converted into a string with line-based configuration.
     * @see cfgString2CfgArray()
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function cfgArray2CfgString($cfgArr)
    {
        trigger_error('TableController->cfgArray2CfgString() will be replaced by protected method configurationArrayToString() in TYPO3 v10.0. Do not call from other extensions.', E_USER_DEPRECATED);
        return $this->configurationArrayToString($cfgArr);
    }

    /**
     * Converts the input configuration code string into an array
     *
     * @param string $configurationCode Configuration code
     * @param int $columns Default number of columns
     * @return array Configuration array
     * @see cfgArray2CfgString()
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function cfgString2CfgArray($configurationCode, $columns)
    {
        trigger_error('TableController->cfgString2CfgArray() will be replaced by protected method configurationStringToArray() in TYPO3 v10.0. Do not call from other extensions.', E_USER_DEPRECATED);
        return $this->configurationStringToArray($configurationCode, $columns);
    }

    /**
     * Initialization of the class
     *
     * @param ServerRequestInterface $request
     */
    protected function init(ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        // GPvars:
        $this->P = $parsedBody['P'] ?? $queryParams['P'] ?? null;
        $this->TABLECFG = $parsedBody['TABLE'] ?? $queryParams['TABLE'] ?? null;
        // Setting options:
        $this->xmlStorage = $this->P['params']['xmlOutput'];
        $this->numNewRows = MathUtility::forceIntegerInRange($this->P['params']['numNewRows'], 1, 50, 5);
        // Textareas or input fields:
        $this->inputStyle = isset($this->TABLECFG['textFields']) ? (bool)$this->TABLECFG['textFields'] : true;
        $this->tableParsing_delimiter = '|';
        $this->tableParsing_quote = '';
    }

    /**
     * Main function, rendering the table wizard
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    protected function renderContent(ServerRequestInterface $request): ?ResponseInterface
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        $requestUri = $normalizedParams->getRequestUri();
        list($rUri) = explode('#', $requestUri);
        $this->content .= '<form action="' . htmlspecialchars($rUri) . '" method="post" id="TableController" name="wizardForm">';
        if ($this->P['table'] && $this->P['field'] && $this->P['uid']) {
            $tableWizard = $this->renderTableWizard($request);

            if ($tableWizard instanceof RedirectResponse) {
                return $tableWizard;
            }

            $this->content .= '<h2>' . htmlspecialchars($this->getLanguageService()->getLL('table_title')) . '</h2>'
                . '<div>' . $tableWizard . '</div>';
        } else {
            $this->content .= '<h2>' . htmlspecialchars($this->getLanguageService()->getLL('table_title')) . '</h2>'
                . '<div><span class="text-danger">' . htmlspecialchars($this->getLanguageService()->getLL('table_noData')) . '</span></div>';
        }
        $this->content .= '</form>';
        // Setting up the buttons and markers for docHeader
        $this->getButtons();
        // Build the <body> for the module
        $this->moduleTemplate->setContent($this->content);

        return null;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if ($this->P['table'] && $this->P['field'] && $this->P['uid']) {
            // CSH
            $cshButton = $buttonBar->makeHelpButton()
                ->setModuleName('xMOD_csh_corebe')
                ->setFieldName('wizard_table_wiz');
            $buttonBar->addButton($cshButton);
            // Close
            $closeButton = $buttonBar->makeLinkButton()
                ->setHref($this->P['returnUrl'])
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-close', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                ->setShowLabelText(true);
            $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
            // Save
            $saveButton = $buttonBar->makeInputButton()
                ->setName('_savedok')
                ->setValue('1')
                ->setForm('TableController')
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
                ->setShowLabelText(true);
            $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            // Reload
            $reloadButton = $buttonBar->makeInputButton()
                ->setName('_refresh')
                ->setValue('1')
                ->setForm('TableController')
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->getLL('forms_refresh'));
            $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
    }

    /**
     * Draws the table wizard content
     *
     * @param ServerRequestInterface $request
     * @return string|ResponseInterface HTML content for the form.
     * @throws \RuntimeException
     */
    protected function renderTableWizard(ServerRequestInterface $request)
    {
        if (!$this->checkEditAccess($this->P['table'], $this->P['uid'])) {
            throw new \RuntimeException('Wizard Error: No access', 1349692692);
        }
        // First, check the references by selecting the record:
        $row = BackendUtility::getRecord($this->P['table'], $this->P['uid']);
        if (!is_array($row)) {
            throw new \RuntimeException('Wizard Error: No reference to record', 1294587125);
        }
        // This will get the content of the form configuration code field to us - possibly cleaned up,
        // saved to database etc. if the form has been submitted in the meantime.
        $tableCfgArray = $this->getConfiguration($row, $request);

        if ($tableCfgArray instanceof ResponseInterface) {
            return $tableCfgArray;
        }

        // Generation of the Table Wizards HTML code:
        $content = $this->getTableWizard($tableCfgArray);
        // Return content:
        return $content;
    }

    /**
     * Will get and return the configuration code string
     * Will also save (and possibly redirect/exit) the content if a save button has been pressed
     *
     * @param array $row Current parent record row
     * @param ServerRequestInterface $request
     * @return array|ResponseInterface Table config code in an array
     */
    protected function getConfiguration(array $row, ServerRequestInterface $request)
    {
        // Get delimiter settings
        $this->tableParsing_quote = $row['table_enclosure'] ? chr((int)$row['table_enclosure']) : '';
        $this->tableParsing_delimiter = $row['table_delimiter'] ? chr((int)$row['table_delimiter']) : '|';
        // If some data has been submitted, then construct
        if (isset($this->TABLECFG['c'])) {
            // Process incoming:
            $this->manipulateTable();
            // Convert to string (either line based or XML):
            if ($this->xmlStorage) {
                // Convert the input array to XML:
                $bodyText = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . LF . GeneralUtility::array2xml($this->TABLECFG['c'], '', 0, 'T3TableWizard');
                // Setting cfgArr directly from the input:
                $configuration = $this->TABLECFG['c'];
            } else {
                // Convert the input array to a string of configuration code:
                $bodyText = $this->configurationArrayToString($this->TABLECFG['c']);
                // Create cfgArr from the string based configuration - that way it is cleaned up
                // and any incompatibilities will be removed!
                $configuration = $this->configurationStringToArray($bodyText, (int)$row[$this->colsFieldName]);
            }
            // If a save button has been pressed, then save the new field content:
            if ($_POST['_savedok'] || $_POST['_saveandclosedok']) {
                // Get DataHandler object:
                /** @var DataHandler $dataHandler */
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                // Put content into the data array:
                $data = [];
                if ($this->P['flexFormPath']) {
                    // Current value of flexForm path:
                    $currentFlexFormData = GeneralUtility::xml2array($row[$this->P['field']]);
                    /** @var FlexFormTools $flexFormTools */
                    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
                    $flexFormTools->setArrayValueByPath($this->P['flexFormPath'], $currentFlexFormData, $bodyText);
                    $data[$this->P['table']][$this->P['uid']][$this->P['field']] = $currentFlexFormData;
                } else {
                    $data[$this->P['table']][$this->P['uid']][$this->P['field']] = $bodyText;
                }
                // Perform the update:
                $dataHandler->start($data, []);
                $dataHandler->process_datamap();
                // If the save/close button was pressed, then redirect the screen:
                if ($_POST['_saveandclosedok']) {
                    return new RedirectResponse(GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']));
                }
            }
        } else {
            // If nothing has been submitted, load the $bodyText variable from the selected database row:
            if ($this->xmlStorage) {
                $configuration = GeneralUtility::xml2array($row[$this->P['field']]);
            } else {
                if ($this->P['flexFormPath']) {
                    // Current value of flexForm path:
                    $currentFlexFormData = GeneralUtility::xml2array($row[$this->P['field']]);
                    /** @var FlexFormTools $flexFormTools */
                    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
                    $configuration = $flexFormTools->getArrayValueByPath(
                        $this->P['flexFormPath'],
                        $currentFlexFormData
                    );
                    $configuration = $this->configurationStringToArray($configuration, 0);
                } else {
                    // Regular line based table configuration:
                    $columns = $row[$this->colsFieldName] ?? 0;
                    $configuration = $this->configurationStringToArray($row[$this->P['field']] ?? '', (int)$columns);
                }
            }
            $configuration = is_array($configuration) ? $configuration : [];
        }
        return $configuration;
    }

    /**
     * Creates the HTML for the Table Wizard:
     *
     * @param array $configuration Table config array
     * @return string HTML for the table wizard
     */
    protected function getTableWizard(array $configuration): string
    {
        // Traverse the rows:
        $tRows = [];
        $k = 0;
        $countLines = count($configuration);
        foreach ($configuration as $cellArr) {
            if (is_array($cellArr)) {
                // Initialize:
                $cells = [];
                $a = 0;
                // Traverse the columns:
                foreach ($cellArr as $cellContent) {
                    if ($this->inputStyle) {
                        $cells[] = '<input class="form-control" type="text" name="TABLE[c][' . ($k + 1) * 2 . '][' . ($a + 1) * 2 . ']" value="' . htmlspecialchars($cellContent) . '" />';
                    } else {
                        $cellContent = preg_replace('/<br[ ]?[\\/]?>/i', LF, $cellContent);
                        $cells[] = '<textarea class="form-control" rows="6" name="TABLE[c][' . ($k + 1) * 2 . '][' . ($a + 1) * 2 . ']">' . htmlspecialchars($cellContent) . '</textarea>';
                    }
                    // Increment counter:
                    $a++;
                }
                // CTRL panel for a table row (move up/down/around):
                $onClick = 'document.wizardForm.action+=' . GeneralUtility::quoteJSvalue('#ANC_' . (($k + 1) * 2 - 2)) . ';';
                $onClick = ' onclick="' . htmlspecialchars($onClick) . '"';
                $ctrl = '';
                if ($k !== 0) {
                    $ctrl .= '<button class="btn btn-default" name="TABLE[row_up][' . ($k + 1) * 2 . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_up')) . '"' . $onClick . '><span class="t3-icon fa fa-fw fa-angle-up"></span></button>';
                } else {
                    $ctrl .= '<button class="btn btn-default" name="TABLE[row_bottom][' . ($k + 1) * 2 . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_bottom')) . '"' . $onClick . '><span class="t3-icon fa fa-fw fa-angle-double-down"></span></button>';
                }
                if ($k + 1 !== $countLines) {
                    $ctrl .= '<button class="btn btn-default" name="TABLE[row_down][' . ($k + 1) * 2 . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_down')) . '"' . $onClick . '><span class="t3-icon fa fa-fw fa-angle-down"></span></button>';
                } else {
                    $ctrl .= '<button class="btn btn-default" name="TABLE[row_top][' . ($k + 1) * 2 . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_top')) . '"' . $onClick . '><span class="t3-icon fa fa-fw fa-angle-double-up"></span></button>';
                }
                $ctrl .= '<button class="btn btn-default" name="TABLE[row_remove][' . ($k + 1) * 2 . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_removeRow')) . '"' . $onClick . '><span class="t3-icon fa fa-fw fa-trash"></span></button>';
                $ctrl .= '<button class="btn btn-default" name="TABLE[row_add][' . ($k + 1) * 2 . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_addRow')) . '"' . $onClick . '><span class="t3-icon fa fa-fw fa-plus"></span></button>';
                $tRows[] = '
					<tr>
						<td>
							<a name="ANC_' . ($k + 1) * 2 . '"></a>
							<span class="btn-group' . ($this->inputStyle ? '' : '-vertical') . '">' . $ctrl . '</span>
						</td>
						<td>' . implode('</td>
						<td>', $cells) . '</td>
					</tr>';
                // Increment counter:
                $k++;
            }
        }
        // CTRL panel for a table column (move left/right/around/delete)
        $cells = [];
        $cells[] = '';
        // Finding first row:
        $firstRow = reset($configuration);
        if (is_array($firstRow)) {
            $cols = count($firstRow);
            for ($a = 1; $a <= $cols; $a++) {
                $b = $a * 2;
                $ctrl = '';
                if ($a !== 1) {
                    $ctrl .= '<button class="btn btn-default" name="TABLE[col_left][' . $b . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_left')) . '"><span class="t3-icon fa fa-fw fa-angle-left"></span></button>';
                } else {
                    $ctrl .= '<button class="btn btn-default" name="TABLE[col_end][' . $b . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_end')) . '"><span class="t3-icon fa fa-fw fa-angle-double-right"></span></button>';
                }
                if ($a != $cols) {
                    $ctrl .= '<button class="btn btn-default" name="TABLE[col_right][' . $b . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_right')) . '"><span class="t3-icon fa fa-fw fa-angle-right"></span></button>';
                } else {
                    $ctrl .= '<button class="btn btn-default" name="TABLE[col_start][' . $b . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_start')) . '"><span class="t3-icon fa fa-fw fa-angle-double-left"></span></button>';
                }
                $ctrl .= '<button class="btn btn-default" name="TABLE[col_remove][' . $b . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_removeColumn')) . '"><span class="t3-icon fa fa-fw fa-trash"></span></button>';
                $ctrl .= '<button class="btn btn-default" name="TABLE[col_add][' . $b . ']" title="' . htmlspecialchars($this->getLanguageService()->getLL('table_addColumn')) . '"><span class="t3-icon fa fa-fw fa-plus"></span></button>';
                $cells[] = '<span class="btn-group">' . $ctrl . '</span>';
            }
            $tRows[] = '
				<tfoot>
					<tr>
						<td>' . implode('</td>
						<td>', $cells) . '</td>
					</tr>
				</tfoot>';
        }
        $content = '';
        $addSubmitOnClick = 'onclick="document.getElementById(\'TableController\').submit();"';
        // Implode all table rows into a string, wrapped in table tags.
        $content .= '

			<!-- Table wizard -->
			<div class="table-fit table-fit-inline-block">
				<table id="typo3-tablewizard" class="table table-center">
					' . implode('', $tRows) . '
				</table>
			</div>';
        // Input type checkbox:
        $content .= '

			<!-- Input mode check box: -->
			<div class="checkbox">
				<input type="hidden" name="TABLE[textFields]" value="0" />
				<label for="textFields">
					<input type="checkbox" ' . $addSubmitOnClick . ' name="TABLE[textFields]" id="textFields" value="1"' . ($this->inputStyle ? ' checked="checked"' : '') . ' />
					' . $this->getLanguageService()->getLL('table_smallFields') . '
				</label>
			</div>';
        return $content;
    }

    /**
     * Detects if a control button (up/down/around/delete) has been pressed for an item and accordingly it will
     * manipulate the internal TABLECFG array
     */
    protected function manipulateTable(): void
    {
        if ($this->TABLECFG['col_remove']) {
            $kk = key($this->TABLECFG['col_remove']);
            $cmd = 'col_remove';
        } elseif ($this->TABLECFG['col_add']) {
            $kk = key($this->TABLECFG['col_add']);
            $cmd = 'col_add';
        } elseif ($this->TABLECFG['col_start']) {
            $kk = key($this->TABLECFG['col_start']);
            $cmd = 'col_start';
        } elseif ($this->TABLECFG['col_end']) {
            $kk = key($this->TABLECFG['col_end']);
            $cmd = 'col_end';
        } elseif ($this->TABLECFG['col_left']) {
            $kk = key($this->TABLECFG['col_left']);
            $cmd = 'col_left';
        } elseif ($this->TABLECFG['col_right']) {
            $kk = key($this->TABLECFG['col_right']);
            $cmd = 'col_right';
        } elseif ($this->TABLECFG['row_remove']) {
            $kk = key($this->TABLECFG['row_remove']);
            $cmd = 'row_remove';
        } elseif ($this->TABLECFG['row_add']) {
            $kk = key($this->TABLECFG['row_add']);
            $cmd = 'row_add';
        } elseif ($this->TABLECFG['row_top']) {
            $kk = key($this->TABLECFG['row_top']);
            $cmd = 'row_top';
        } elseif ($this->TABLECFG['row_bottom']) {
            $kk = key($this->TABLECFG['row_bottom']);
            $cmd = 'row_bottom';
        } elseif ($this->TABLECFG['row_up']) {
            $kk = key($this->TABLECFG['row_up']);
            $cmd = 'row_up';
        } elseif ($this->TABLECFG['row_down']) {
            $kk = key($this->TABLECFG['row_down']);
            $cmd = 'row_down';
        } else {
            $kk = '';
            $cmd = '';
        }
        if ($cmd && MathUtility::canBeInterpretedAsInteger($kk)) {
            if (strpos($cmd, 'row_') === 0) {
                switch ($cmd) {
                    case 'row_remove':
                        unset($this->TABLECFG['c'][$kk]);
                        break;
                    case 'row_add':
                        for ($a = 1; $a <= $this->numNewRows; $a++) {
                            // Checking if set: The point is that any new row between existing rows
                            // will be TRUE after one row is added while if rows are added in the bottom
                            // of the table there will be no existing rows to stop the addition of new rows
                            // which means it will add up to $this->numNewRows rows then.
                            if (!isset($this->TABLECFG['c'][$kk + $a])) {
                                $this->TABLECFG['c'][$kk + $a] = [];
                            } else {
                                break;
                            }
                        }
                        break;
                    case 'row_top':
                        $this->TABLECFG['c'][1] = $this->TABLECFG['c'][$kk];
                        unset($this->TABLECFG['c'][$kk]);
                        break;
                    case 'row_bottom':
                        $this->TABLECFG['c'][10000000] = $this->TABLECFG['c'][$kk];
                        unset($this->TABLECFG['c'][$kk]);
                        break;
                    case 'row_up':
                        $this->TABLECFG['c'][$kk - 3] = $this->TABLECFG['c'][$kk];
                        unset($this->TABLECFG['c'][$kk]);
                        break;
                    case 'row_down':
                        $this->TABLECFG['c'][$kk + 3] = $this->TABLECFG['c'][$kk];
                        unset($this->TABLECFG['c'][$kk]);
                        break;
                }
                ksort($this->TABLECFG['c']);
            }
            if (strpos($cmd, 'col_') === 0) {
                foreach ($this->TABLECFG['c'] as $cAK => $value) {
                    switch ($cmd) {
                        case 'col_remove':
                            unset($this->TABLECFG['c'][$cAK][$kk]);
                            break;
                        case 'col_add':
                            $this->TABLECFG['c'][$cAK][$kk + 1] = '';
                            break;
                        case 'col_start':
                            $this->TABLECFG['c'][$cAK][1] = $this->TABLECFG['c'][$cAK][$kk];
                            unset($this->TABLECFG['c'][$cAK][$kk]);
                            break;
                        case 'col_end':
                            $this->TABLECFG['c'][$cAK][1000000] = $this->TABLECFG['c'][$cAK][$kk];
                            unset($this->TABLECFG['c'][$cAK][$kk]);
                            break;
                        case 'col_left':
                            $this->TABLECFG['c'][$cAK][$kk - 3] = $this->TABLECFG['c'][$cAK][$kk];
                            unset($this->TABLECFG['c'][$cAK][$kk]);
                            break;
                        case 'col_right':
                            $this->TABLECFG['c'][$cAK][$kk + 3] = $this->TABLECFG['c'][$cAK][$kk];
                            unset($this->TABLECFG['c'][$cAK][$kk]);
                            break;
                    }
                    ksort($this->TABLECFG['c'][$cAK]);
                }
            }
        }
        // Convert line breaks to <br /> tags:
        foreach ($this->TABLECFG['c'] as $a => $value) {
            foreach ($this->TABLECFG['c'][$a] as $b => $value2) {
                $this->TABLECFG['c'][$a][$b] = str_replace(
                    [CR, LF],
                    ['', '<br />'],
                    $this->TABLECFG['c'][$a][$b]
                );
            }
        }
    }

    /**
     * Converts the input array to a configuration code string
     *
     * @param array $cfgArr Array of table configuration (follows the input structure from the table wizard POST form)
     * @return string The array converted into a string with line-based configuration.
     * @see configurationStringToArray()
     */
    protected function configurationArrayToString(array $cfgArr): string
    {
        $inLines = [];
        // Traverse the elements of the table wizard and transform the settings into configuration code.
        foreach ($cfgArr as $valueA) {
            $thisLine = [];
            foreach ($valueA as $valueB) {
                $thisLine[] = $this->tableParsing_quote
                    . str_replace($this->tableParsing_delimiter, '', $valueB) . $this->tableParsing_quote;
            }
            $inLines[] = implode($this->tableParsing_delimiter, $thisLine);
        }
        // Finally, implode the lines into a string:
        return implode(LF, $inLines);
    }

    /**
     * Converts the input configuration code string into an array
     *
     * @param string $configurationCode Configuration code
     * @param int $columns Default number of columns
     * @return array Configuration array
     * @see configurationArrayToString()
     */
    protected function configurationStringToArray(string $configurationCode, int $columns): array
    {
        // Explode lines in the configuration code - each line is a table row.
        $tableLines = explode(LF, $configurationCode);
        // Setting number of columns
        // auto...
        if (!$columns && trim($tableLines[0])) {
            $columns = count(explode($this->tableParsing_delimiter, $tableLines[0]));
        }
        $columns = $columns ?: 4;
        // Traverse the number of table elements:
        $configurationArray = [];
        foreach ($tableLines as $key => $value) {
            // Initialize:
            $valueParts = explode($this->tableParsing_delimiter, $value);
            // Traverse columns:
            for ($a = 0; $a < $columns; $a++) {
                if ($this->tableParsing_quote
                    && $valueParts[$a][0] === $this->tableParsing_quote
                    && substr($valueParts[$a], -1, 1) === $this->tableParsing_quote
                ) {
                    $valueParts[$a] = substr(trim($valueParts[$a]), 1, -1);
                }
                $configurationArray[$key][$a] = (string)$valueParts[$a];
            }
        }
        return $configurationArray;
    }
}
