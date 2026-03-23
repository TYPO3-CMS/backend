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
import s from"@typo3/core/ajax/ajax-request.js";function i(r,a){return new s(TYPO3.settings.ajaxUrls.wizard_config).withQueryArguments({mode:r,data:a.getDataStore()}).get().then(e=>e.resolve()).then(async e=>await Promise.all(e.steps.map(async t=>{if(!t.module)throw new Error("Step data does not contain a module path");const o=(await import(t.module)).default;if(!o)throw new Error(`Step module ${t.module} does not export a default class`);const n=new o;return Object.assign(n,{context:a,...t.configurationData}),n})))}export{i as loadDynamicSteps};
