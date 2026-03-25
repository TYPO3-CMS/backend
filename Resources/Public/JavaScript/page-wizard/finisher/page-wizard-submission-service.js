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
import o from"@typo3/core/ajax/ajax-request.js";class r{constructor(e){this.context=e}async execute(){const{fields:e,...t}=this.context.getDataStore(),s=Object.assign({},t,...Object.values(e||{})),a=await(await new o(TYPO3.settings.ajaxUrls.wizard_submit).withQueryArguments({mode:"page_wizard"}).post(s)).resolve();return document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh")),a}}export{r as PageWizardSubmissionService};
