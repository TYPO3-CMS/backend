<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {

	// Register record edit module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'record_edit',
		'EXT:backend/Modules/FormEngine/'
	);

	// Register record history module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'record_history',
		'EXT:backend/Modules/RecordHistory/'
	);

	// Register login frameset
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'login_frameset',
		'EXT:backend/Modules/LoginFrameset/'
	);

	// Register file_navframe
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addCoreNavigationComponent('file', 'file_navframe');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'file_navframe',
		'EXT:backend/Modules/FileSystemNavigationFrame/'
	);

	// Register tce_db
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'tce_db',
		'EXT:backend/Modules/File/Database/'
	);

	// Register tce_file
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'tce_file',
		'EXT:backend/Modules/File/Administration/'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'layout',
		'top',
		'EXT:backend/Modules/Layout/',
		array(
			'script' => '_DISPATCH',
			'access' => 'user,group',
			'name' => 'web_layout',
			'labels' => array(
				'tabs_images' => array(
					'tab' => 'EXT:backend/Resources/Public/Icons/module-page.svg',
				),
				'll_ref' => 'LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf',
			),
		)
	);

	// Register new record
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'db_new',
		'EXT:backend/Modules/NewRecord/'
	);

	// Register new content element module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'new_content_element',
		'EXT:backend/Modules/NewContentElement/'
	);

	// Register move element module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'move_element',
		'EXT:backend/Modules/MoveElement/'
	);

	// Register show item module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'show_item',
		'EXT:backend/Modules/ShowItem/'
	);

	// Register browser
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'browser',
		'EXT:backend/Modules/Browser/'
	);

	// Register dummy window
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'dummy',
		'EXT:backend/Modules/Dummy/'
	);

	// Register BackendLayoutDataProvider for PageTs
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']['pagets'] = \TYPO3\CMS\Backend\Provider\PageTsBackendLayoutDataProvider::class;
}
