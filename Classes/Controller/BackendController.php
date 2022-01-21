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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemsRegistry;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;

/**
 * Class for rendering the TYPO3 backend
 */
class BackendController
{
    /**
     * @var string
     */
    protected $css = '';

    /**
     * @var array
     */
    protected $toolbarItems = [];

    protected BackendModuleRepository $backendModuleRepository;
    protected PageRenderer $pageRenderer;
    protected IconFactory $iconFactory;
    protected Typo3Version $typo3Version;
    protected UriBuilder $uriBuilder;
    protected ModuleLoader $moduleLoader;
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected ToolbarItemsRegistry $toolbarItemsRegistry;

    /**
     * @var \SplObjectStorage
     */
    protected $moduleStorage;

    public function __construct(
        Typo3Version $typo3Version,
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
        PageRenderer $pageRenderer,
        ModuleLoader $moduleLoader,
        BackendModuleRepository $backendModuleRepository,
        ModuleTemplateFactory $moduleTemplateFactory,
        ToolbarItemsRegistry $toolbarItemsRegistry
    ) {
        $javaScriptRenderer = $pageRenderer->getJavaScriptRenderer();
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->backendModuleRepository = $backendModuleRepository;
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
        $this->typo3Version = $typo3Version;
        $this->pageRenderer = $pageRenderer;
        $this->moduleLoader = $moduleLoader;
        $this->toolbarItemsRegistry = $toolbarItemsRegistry;
        $this->moduleLoader->observeWorkspaces = true;
        $this->moduleLoader->load($GLOBALS['TBE_MODULES']);
        $this->moduleTemplateFactory = $moduleTemplateFactory;

        // Add default BE javascript
        $this->pageRenderer->addJsFile('EXT:backend/Resources/Public/JavaScript/backend.js');
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Backend/LoginRefresh')
                ->invoke('initialize', [
                    'intervalTime' => MathUtility::forceIntegerInRange((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] - 60, 60),
                    'loginFramesetUrl' => (string)$this->uriBuilder->buildUriFromRoute('login_frameset'),
                    'logoutUrl' => (string)$this->uriBuilder->buildUriFromRoute('logout'),
                ])
        );
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('TYPO3/CMS/Backend/BroadcastService.js')->invoke('listen')
        );
        // load the storage API and fill the UC into the PersistentStorage, so no additional AJAX call is needed
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('TYPO3/CMS/Backend/Storage/Persistent.js')
                ->invoke('load', $this->getBackendUser()->uc)
        );
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/Module/Router.js');
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/ModuleMenu.js');
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/Storage/ModuleStateStorage.js');
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/Toolbar.js');
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/Notification.js');
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/Modal.js');
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/InfoWindow.js');
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/Viewport/ResizableNavigation.js');
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/DebugConsole.js');

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/debugger.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/wizard.xlf');

        $this->pageRenderer->addInlineSetting('ContextHelp', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('help_cshmanual'));
        $this->pageRenderer->addInlineSetting('ShowItem', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('show_item'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_history'));
        $this->pageRenderer->addInlineSetting('NewRecord', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('db_new'));
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordCommit', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('tce_db'));
        $this->pageRenderer->addInlineSetting('FileCommit', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('tce_file'));
        $this->pageRenderer->addInlineSetting('Clipboard', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('clipboard_process'));

        $this->toolbarItems = $this->toolbarItemsRegistry->getToolbarItems();
        $this->executeHook('constructPostProcess');

        $this->moduleStorage = $this->backendModuleRepository->loadAllowedModules(['user', 'help']);
    }

    /**
     * Main function generating the BE scaffolding
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->executeHook('renderPreProcess');

        $moduleMenuCollapsed = $this->getCollapseStateOfMenu();
        $hasModules = count($this->moduleStorage) > 0;
        $bodyTag = '<body class="scaffold t3js-scaffold' . (!$moduleMenuCollapsed && $hasModules ? ' scaffold-modulemenu-expanded' : '') . '">';

        // Prepare the scaffolding, at this point extension may still add javascript and css
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $view = $moduleTemplate->getView();
        $view->setTemplatePathAndFilename('EXT:backend/Resources/Private/Templates/Backend/Main.html');
        $moduleTemplate->setBodyTag($bodyTag);
        $view->assign('moduleMenu', $this->generateModuleMenu());
        $view->assign('topbar', $this->renderTopbar());
        $view->assign('hasModules', $hasModules);
        $view->assign('startupModule', $this->getStartupModule($request));
        $view->assign('stateTracker', (string)$this->uriBuilder->buildUriFromRoute('state-tracker'));

        if (!empty($this->css)) {
            $this->pageRenderer->addCssInlineBlock('BackendInlineCSS', $this->css);
        }
        $this->generateJavascript($request);

        // Set document title
        $typo3Version = 'TYPO3 CMS ' . $this->typo3Version->getVersion();
        $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . ' [' . $typo3Version . ']' : $typo3Version;
        $moduleTemplate->setTitle($title);
        $view->assign('sitename', $title);
        $view->assign('sitenameFirstInBackendTitle', ($this->getBackendUser()->uc['backendTitleFormat'] ?? '') === 'sitenameFirst');

        // Renders the backend scaffolding
        $content = $moduleTemplate->renderContent();
        $this->executeHook('renderPostProcess', ['content' => &$content]);
        return new HtmlResponse($content);
    }

    /**
     * Renders the topbar, containing the backend logo, sitename etc.
     *
     * @return string
     */
    protected function renderTopbar()
    {
        // Extension Configuration to find the TYPO3 logo in the left corner
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('backend');
        $logoPath = '';
        if (!empty($extConf['backendLogo'])) {
            $customBackendLogo = GeneralUtility::getFileAbsFileName(ltrim($extConf['backendLogo'], '/'));
            if (!empty($customBackendLogo)) {
                $logoPath = $customBackendLogo;
            }
        }
        // if no custom logo was set or the path is invalid, use the original one
        if (empty($logoPath) || !file_exists($logoPath)) {
            $logoPath = GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Public/Images/typo3_logo_orange.svg');
            $logoWidth = 22;
            $logoHeight = 22;
        } else {
            // set width/height for custom logo
            $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $logoPath);
            $logoWidth = $imageInfo->getWidth() ?? '22';
            $logoHeight = $imageInfo->getHeight() ?? '22';

            // High-resolution?
            if (str_contains($logoPath, '@2x.')) {
                $logoWidth /= 2;
                $logoHeight /= 2;
            }
        }

        $view = $this->getFluidTemplateObject();
        $view->assign('hasModules', count($this->moduleStorage) > 0);
        $view->assign('logoUrl', PathUtility::getAbsoluteWebPath($logoPath));
        $view->assign('logoWidth', $logoWidth);
        $view->assign('logoHeight', $logoHeight);
        $view->assign('applicationVersion', $this->typo3Version->getVersion());
        $view->assign('siteName', $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        $view->assign('toolbar', $this->renderToolbar());
        return $view->render('Backend/Topbar.html');
    }

    /**
     * Renders the items in the top toolbar
     *
     * @return string top toolbar elements as HTML
     */
    protected function renderToolbar()
    {
        $toolbar = [];
        foreach ($this->toolbarItems as $toolbarItem) {
            /** @var ToolbarItemInterface $toolbarItem */
            if ($toolbarItem->checkAccess()) {
                $hasDropDown = (bool)$toolbarItem->hasDropDown();
                $additionalAttributes = (array)$toolbarItem->getAdditionalAttributes();

                $liAttributes = [];

                // Merge class: Add dropdown class if hasDropDown, add classes from additional attributes
                $classes = [];
                $classes[] = 'toolbar-item';
                $classes[] = 't3js-toolbar-item';
                if (isset($additionalAttributes['class'])) {
                    $classes[] = $additionalAttributes['class'];
                    unset($additionalAttributes['class']);
                }
                $liAttributes['class'] = implode(' ', $classes);

                // Add further attributes
                foreach ($additionalAttributes as $name => $value) {
                    $liAttributes[(string)$name] = (string)$value;
                }

                // Create a unique id from class name
                $fullyQualifiedClassName = \get_class($toolbarItem);
                $className = GeneralUtility::underscoredToLowerCamelCase($fullyQualifiedClassName);
                $className = GeneralUtility::camelCaseToLowerCaseUnderscored($className);
                $className = str_replace(['_', '\\'], '-', $className);
                $liAttributes['id'] = $className;

                // Create data attribute identifier
                $shortName = substr($fullyQualifiedClassName, (int)strrpos($fullyQualifiedClassName, '\\') + 1);
                $dataToolbarIdentifier = GeneralUtility::camelCaseToLowerCaseUnderscored($shortName);
                $dataToolbarIdentifier = str_replace('_', '-', $dataToolbarIdentifier);
                $liAttributes['data-toolbar-identifier'] = $dataToolbarIdentifier;

                $toolbar[] = '<li ' . GeneralUtility::implodeAttributes($liAttributes, true) . '>';

                if ($hasDropDown) {
                    $toolbar[] = '<a href="#" class="toolbar-item-link dropdown-toggle" data-bs-toggle="dropdown" data-bs-offset="0,-2">';
                    $toolbar[] = $toolbarItem->getItem();
                    $toolbar[] = '</a>';
                    $toolbar[] = '<div class="dropdown-menu" role="menu">';
                    $toolbar[] = $toolbarItem->getDropDown();
                    $toolbar[] = '</div>';
                } else {
                    $toolbar[] = $toolbarItem->getItem();
                }
                $toolbar[] = '</li>';
            }
        }
        return implode(LF, $toolbar);
    }

    /**
     * Generates the JavaScript code for the backend.
     *
     * @param ServerRequestInterface $request
     */
    protected function generateJavascript(ServerRequestInterface $request)
    {
        $beUser = $this->getBackendUser();
        // Needed for FormEngine manipulation (date picker)
        $dateFormat = ['DD-MM-Y', 'HH:mm DD-MM-Y'];
        $this->pageRenderer->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);

        $t3Configuration = [
            'username' => htmlspecialchars($beUser->user['username']),
            'showRefreshLoginPopup' => (bool)($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup'] ?? false),
        ];
        $this->pageRenderer->getJavaScriptRenderer()->addGlobalAssignment(
            ['TYPO3' => ['configuration' => $t3Configuration]]
        );
    }

    /**
     * Sets the startup module from either "redirect" GET parameters or user configuration.
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function getStartupModule(ServerRequestInterface $request): array
    {
        $startModule = null;
        $moduleParameters = [];
        try {
            $redirect = RouteRedirect::createFromRequest($request);
            if ($request->getMethod() === 'GET' && $redirect !== null) {
                // Only redirect to existing non-ajax routes with no restriction to a specific method
                $redirect->resolve(GeneralUtility::makeInstance(Router::class));
                $startModule = $redirect->getName();
                $moduleParameters = $redirect->getParameters();
            }
        } finally {
            // No valid redirect, check for the start module
            if (!$startModule) {
                $beUser = $this->getBackendUser();
                // start module on first login, will be removed once used the first time
                if (isset($beUser->uc['startModuleOnFirstLogin'])) {
                    $startModule = $beUser->uc['startModuleOnFirstLogin'];
                    unset($beUser->uc['startModuleOnFirstLogin']);
                    $beUser->writeUC();
                } elseif ($this->moduleLoader->checkMod($beUser->uc['startModule'] ?? '') !== 'notFound') {
                    $startModule = $beUser->uc['startModule'];
                } else {
                    $startModule = $this->determineFirstAvailableBackendModule();
                }

                // check if the start module has additional parameters, so a redirect to a specific
                // action is possible
                if (str_contains($startModule, '->')) {
                    [$startModule, $startModuleParameters] = explode('->', $startModule, 2);
                    // if no GET parameters are set, check if there are parameters given from the UC
                    if (!$moduleParameters && $startModuleParameters) {
                        $moduleParameters = $startModuleParameters;
                    }
                }
            }
        }
        if ($startModule) {
            if (is_array($moduleParameters)) {
                $parameters = $moduleParameters;
            } else {
                $parameters = [];
                parse_str($moduleParameters, $parameters);
            }
            $deepLink = $this->uriBuilder->buildUriFromRoute($startModule, $parameters);
            return [$startModule, (string)$deepLink];
        }
        return [null, null];
    }

    protected function determineFirstAvailableBackendModule(): string
    {
        foreach ($this->moduleLoader->getModules() as $modData) {
            $hasSubmodules = !empty($modData['sub']) && is_array($modData['sub']);
            $isStandalone = $modData['standalone'] ?? false;
            if ($isStandalone) {
                return $modData['name'];
            }

            if ($hasSubmodules) {
                $firstSubmodule = reset($modData['sub']);
                return $firstSubmodule['name'];
            }
        }

        return '';
    }

    /**
     * Adds a css snippet to the backend
     *
     * @param string $css Css snippet
     * @throws \InvalidArgumentException
     */
    public function addCss($css)
    {
        if (!is_string($css)) {
            throw new \InvalidArgumentException('parameter $css must be of type string', 1195129642);
        }
        $this->css .= $css;
    }

    /**
     * Executes defined hooks functions for the given identifier.
     *
     * These hook identifiers are valid:
     * + constructPostProcess
     * + renderPreProcess
     * + renderPostProcess
     *
     * @param string $identifier Specific hook identifier
     * @param array $hookConfiguration Additional configuration passed to hook functions
     */
    protected function executeHook($identifier, array $hookConfiguration = [])
    {
        $options = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php'];
        foreach ($options[$identifier] ?? [] as $hookFunction) {
            GeneralUtility::callUserFunction($hookFunction, $hookConfiguration, $this);
        }
    }

    /**
     * loads all modules from the repository
     * and renders it with a template
     *
     * @return string
     */
    protected function generateModuleMenu()
    {
        $view = $this->getFluidTemplateObject();
        $view->assign('modules', $this->moduleStorage);
        return $view->render('Backend/ModuleMenu.html');
    }

    protected function getCollapseStateOfMenu(): bool
    {
        $uc = json_decode((string)json_encode($this->getBackendUser()->uc), true);
        $collapseState = $uc['BackendComponents']['States']['typo3-module-menu']['collapsed'] ?? false;

        return $collapseState === true || $collapseState === 'true';
    }

    /**
     * Returns the Module menu for the AJAX request
     *
     * @return ResponseInterface
     */
    public function getModuleMenu(): ResponseInterface
    {
        return new JsonResponse(['menu' => $this->generateModuleMenu()]);
    }

    /**
     * Returns the toolbar for the AJAX request
     *
     * @return ResponseInterface
     */
    public function getTopbar(): ResponseInterface
    {
        return new JsonResponse(['topbar' => $this->renderTopbar()]);
    }

    protected function getFluidTemplateObject(): BackendTemplateView
    {
        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/']);
        return $view;
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
