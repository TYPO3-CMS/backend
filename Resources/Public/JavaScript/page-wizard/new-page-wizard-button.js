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
import{property as a,customElement as l}from"lit/decorators.js";import{PseudoButtonLitElement as m}from"@typo3/backend/element/pseudo-button.js";import"@typo3/backend/new-record-wizard.js";import{openPageWizardModal as d}from"@typo3/backend/page-wizard/helper/wizard-helper.js";var f=function(n,e,o,r){var i=arguments.length,t=i<3?e:r===null?r=Object.getOwnPropertyDescriptor(e,o):r,p;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(n,e,o,r);else for(var c=n.length-1;c>=0;c--)(p=n[c])&&(t=(i<3?p(t):i>3?p(e,o,t):p(e,o))||t);return i>3&&t&&Object.defineProperty(e,o,t),t};let u=class extends m{constructor(){super(...arguments),this.configuration=null}buttonActivated(){d(this.configuration)}};f([a({type:Object})],u.prototype,"configuration",void 0),u=f([l("typo3-backend-new-page-wizard-button")],u);export{u as NewPageWizardButton};
