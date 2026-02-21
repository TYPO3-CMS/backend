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
import{html as m}from"lit";import{PseudoButtonLitElement as f}from"@typo3/backend/element/pseudo-button.js";import{property as p,customElement as y}from"lit/decorators.js";import{SeverityEnum as b}from"@typo3/backend/enum/severity.js";import u from"@typo3/backend/modal.js";import s from"~labels/backend.wizards.localization";var l=function(i,t,r,a){var n=arguments.length,e=n<3?t:a===null?a=Object.getOwnPropertyDescriptor(t,r):a,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(i,t,r,a);else for(var d=i.length-1;d>=0;d--)(c=i[d])&&(e=(n<3?c(e):n>3?c(t,r,e):c(t,r))||e);return n>3&&e&&Object.defineProperty(t,r,e),e};let o=class extends f{buttonActivated(){const t=m`<typo3-backend-localization-wizard record-type=${this.recordType} record-uid=${this.recordUid} target-language=${this.targetLanguage}></typo3-backend-localization-wizard>`;u.advanced({title:s.get("localization_wizard.modal.title"),content:t,severity:b.notice,size:u.sizes.medium,staticBackdrop:!0,buttons:[]})}};l([p({type:String,attribute:"record-type"})],o.prototype,"recordType",void 0),l([p({type:Number,attribute:"record-uid"})],o.prototype,"recordUid",void 0),l([p({type:Number,attribute:"target-language"})],o.prototype,"targetLanguage",void 0),o=l([y("typo3-backend-localization-button")],o);export{o as LocalizationButton};
