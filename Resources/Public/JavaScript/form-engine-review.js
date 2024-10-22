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
import"bootstrap";import DocumentService from"@typo3/core/document-service.js";import{selector}from"@typo3/core/literals.js";import"@typo3/backend/element/icon-element.js";import Popover from"@typo3/backend/popover.js";import{Tab as BootstrapTab}from"bootstrap";export class FormEngineReview{constructor(e){this.formElement=e,this.toggleButtonClass="t3js-toggle-review-panel",this.labelSelector=".t3js-formengine-label",this.initialize()}static findInvalidField(){return document.querySelectorAll(".tab-content .has-error")}initialize(){DocumentService.ready().then((()=>{this.attachButtonToModuleHeader(),this.checkForReviewableField(),this.formElement.addEventListener("t3-formengine-postfieldvalidation",(()=>{this.checkForReviewableField()}))}))}attachButtonToModuleHeader(){const e=document.querySelector(".t3js-module-docheader-bar-buttons").lastElementChild.querySelector('[role="toolbar"]'),t=document.createElement("typo3-backend-icon");t.setAttribute("identifier","actions-info"),t.setAttribute("size","small");const o=document.createElement("button");o.type="button",o.classList.add("btn","btn-danger","btn-sm","hidden",this.toggleButtonClass),o.title=TYPO3.lang["buttons.reviewFailedValidationFields"],o.appendChild(t),Popover.popover(o),e.prepend(o)}checkForReviewableField(){const e=FormEngineReview.findInvalidField(),t=document.querySelector("."+this.toggleButtonClass);if(null!==t)if(e.length>0){const o=document.createElement("div");o.classList.add("list-group");for(const t of e){const e=t.querySelector("[data-formengine-validation-rules]"),i=document.createElement("a");i.classList.add("list-group-item"),i.href="#",i.textContent=t.querySelector(this.labelSelector)?.textContent||"",i.addEventListener("click",(t=>this.switchToField(t,e))),o.append(i)}t.classList.remove("hidden"),Popover.setOptions(t,{html:!0,content:o})}else t.classList.add("hidden"),Popover.hide(t)}switchToField(e,t){e.preventDefault();let o=t;for(;o;){if(o.matches('[id][role="tabpanel"]')){const e=document.querySelector(selector`[aria-controls="${o.id}"]`);new BootstrapTab(e).show()}o=o.parentElement}t.focus()}}