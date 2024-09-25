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
import{MessageUtility}from"@typo3/backend/utility/message-utility.js";import{AjaxDispatcher}from"@typo3/backend/form-engine/inline-relation/ajax-dispatcher.js";import NProgress from"nprogress";import FormEngine from"@typo3/backend/form-engine.js";import FormEngineValidation from"@typo3/backend/form-engine-validation.js";import{default as Modal}from"@typo3/backend/modal.js";import Notification from"@typo3/backend/notification.js";import RegularEvent from"@typo3/core/event/regular-event.js";import Severity from"@typo3/backend/severity.js";import Utility from"@typo3/backend/utility.js";import{selector}from"@typo3/core/literals.js";var Selectors,States,Separators;!function(e){e.toggleSelector='[data-bs-toggle="formengine-inline"]',e.controlSectionSelector=".t3js-formengine-irre-control",e.createNewRecordButtonSelector=".t3js-create-new-button",e.createNewRecordBySelectorSelector=".t3js-create-new-selector",e.deleteRecordButtonSelector=".t3js-editform-delete-inline-record",e.createNewRecordPresetSelector=".t3js-create-new-preset"}(Selectors||(Selectors={})),function(e){e.new="inlineIsNewRecord",e.visible="panel-visible",e.collapsed="panel-collapsed",e.notLoaded="t3js-not-loaded"}(States||(States={})),function(e){e.structureSeparator="-"}(Separators||(Separators={}));class SiteLanguageContainer extends HTMLElement{constructor(){super(...arguments),this.container=null,this.ajaxDispatcher=null,this.requestQueue={},this.progressQueue={},this.handlePostMessage=e=>{if(!MessageUtility.verifyOrigin(e.origin))throw"Denied message sent by "+e.origin;if("typo3:foreignRelation:insert"===e.data.actionName){if(void 0===e.data.objectGroup)throw"No object group defined for message";if(e.data.objectGroup!==this.container.dataset.objectGroup)return;if(this.isUniqueElementUsed(parseInt(e.data.uid,10)))return void Notification.error("There is already a relation to the selected element");this.importRecord([e.data.objectGroup,e.data.uid]).then((()=>{if(e.source){const t={actionName:"typo3:foreignRelation:inserted",objectGroup:e.data.objectId,table:e.data.table,uid:e.data.uid};MessageUtility.send(t,e.source)}}))}if("typo3:foreignRelation:delete"===e.data.actionName){if(e.data.objectGroup!==this.container.dataset.objectGroup)return;const t=e.data.directRemoval||!1,r=[e.data.objectGroup,e.data.uid].join("-");this.deleteRecord(r,t)}}}static getInlineRecordContainer(e){return document.querySelector(selector`[data-object-id="${e}"]`)}static getValuesFromHashMap(e){return Object.keys(e).map((t=>e[t]))}static selectOptionValueExists(e,t){return null!==e.querySelector(selector`option[value="${t}"]`)}static removeSelectOptionByValue(e,t){const r=e.querySelector(selector`option[value="${t}"]`);null!==r&&r.remove()}static reAddSelectOption(e,t,r){if(SiteLanguageContainer.selectOptionValueExists(e,t))return;const o=e.querySelectorAll("option");let n=-1;for(const e of Object.keys(r.possible)){if(e===t)break;for(let t=0;t<o.length;++t){if(o[t].value===e){n=t;break}}}-1===n?n=1:n<o.length&&n++;const i=document.createElement("option");i.text=r.possible[t],i.value=t,e.insertBefore(i,e.options[n])}static collapseExpandRecord(e){const t=SiteLanguageContainer.getInlineRecordContainer(e),r=document.querySelector(selector`[aria-controls="${e}_fields"]`);t.classList.contains(States.collapsed)?(t.classList.remove(States.collapsed),t.classList.add(States.visible),r.setAttribute("aria-expanded","true")):(t.classList.remove(States.visible),t.classList.add(States.collapsed),r.setAttribute("aria-expanded","false"))}connectedCallback(){const e=this.getAttribute("identifier")||"";this.container=this.querySelector(selector`#${e}`),null!==this.container&&(this.ajaxDispatcher=new AjaxDispatcher(this.container.dataset.objectGroup),this.registerEvents())}registerEvents(){this.registerCreateRecordButton(),this.registerCreateRecordByPresetSelector(),this.registerCreateRecordBySelector(),this.registerRecordToggle(),this.registerDeleteButton(),new RegularEvent("message",this.handlePostMessage).bindTo(window)}registerCreateRecordButton(){new RegularEvent("click",((e,t)=>{e.preventDefault(),e.stopImmediatePropagation();let r=this.container.dataset.objectGroup;void 0!==t.dataset.recordUid&&(r+=Separators.structureSeparator+t.dataset.recordUid),this.importRecord([r],t.dataset.recordUid??null)})).delegateTo(this.container,Selectors.createNewRecordButtonSelector)}registerCreateRecordByPresetSelector(){new RegularEvent("change",((e,t)=>{e.preventDefault(),e.stopImmediatePropagation();const r=this.container.querySelector(Selectors.createNewRecordPresetSelector),o=r?.value;if(""===o)return;let n=this.container.dataset.objectGroup;void 0!==t.dataset.recordUid&&(n+=Separators.structureSeparator+t.dataset.recordUid),r.value="",this.importRecord([n,"",o],t.dataset.recordUid??null)})).delegateTo(this.container,Selectors.createNewRecordPresetSelector)}registerCreateRecordBySelector(){new RegularEvent("change",((e,t)=>{e.preventDefault(),e.stopImmediatePropagation();const r=t,o=r.options[r.selectedIndex].getAttribute("value");""!==o&&this.importRecord([this.container.dataset.objectGroup,o])})).delegateTo(this.container,Selectors.createNewRecordBySelectorSelector)}registerRecordToggle(){new RegularEvent("click",((e,t)=>{e.preventDefault(),e.stopImmediatePropagation(),this.loadRecordDetails(t.closest(Selectors.toggleSelector).parentElement.dataset.objectId)})).delegateTo(this.container,`${Selectors.toggleSelector} .form-irre-header-cell:not(${Selectors.controlSectionSelector}`)}registerDeleteButton(){new RegularEvent("click",((e,t)=>{e.preventDefault(),e.stopImmediatePropagation();const r=TYPO3.lang["label.confirm.delete_record.title"]||"Delete this record?",o=(TYPO3.lang["label.confirm.delete_record.content"]||"Are you sure you want to delete the record '%s'?").replace("%s",t.dataset.recordInfo);Modal.confirm(r,o,Severity.warning,[{text:TYPO3.lang["buttons.confirm.delete_record.no"]||"Cancel",active:!0,btnClass:"btn-default",name:"no",trigger:(e,t)=>t.hideModal()},{text:TYPO3.lang["buttons.confirm.delete_record.yes"]||"Yes, delete this record",btnClass:"btn-warning",name:"yes",trigger:(e,r)=>{this.deleteRecord(t.closest("[data-object-id]").dataset.objectId),r.hideModal()}}])})).delegateTo(this.container,Selectors.deleteRecordButtonSelector)}createRecord(e,t,r=null,o=null){let n=this.container.dataset.objectGroup;null!==r?(n+=Separators.structureSeparator+r,SiteLanguageContainer.getInlineRecordContainer(n).insertAdjacentHTML("afterend",t),this.memorizeAddRecord(e,r,o)):(document.getElementById(this.container.getAttribute("id")+"_records").insertAdjacentHTML("beforeend",t),this.memorizeAddRecord(e,null,o))}async importRecord(e,t){return this.ajaxDispatcher.send(this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint("site_configuration_inline_create")),e).then((async e=>{this.createRecord(e.compilerInput.uid,e.data,void 0!==t?t:null,void 0!==e.compilerInput.childChildUid?e.compilerInput.childChildUid:null)}))}loadRecordDetails(e){const t=document.getElementById(e+"_fields"),r=SiteLanguageContainer.getInlineRecordContainer(e),o=void 0!==this.requestQueue[e];if(null!==t&&!r.classList.contains(States.notLoaded))SiteLanguageContainer.collapseExpandRecord(e);else{const n=this.getProgress(e,r.dataset.objectIdHash);if(o)this.requestQueue[e].abort(),delete this.requestQueue[e],delete this.progressQueue[e],n.done();else{const o=this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint("site_configuration_inline_details"));this.ajaxDispatcher.send(o,[e]).then((async o=>{delete this.requestQueue[e],delete this.progressQueue[e],r.classList.remove(States.notLoaded),t.innerHTML=o.data,SiteLanguageContainer.collapseExpandRecord(e),n.done(),FormEngine.reinitialize(),FormEngineValidation.initializeInputFields(),FormEngineValidation.validate(this.container),this.removeUsed(SiteLanguageContainer.getInlineRecordContainer(e))})),this.requestQueue[e]=o,n.start()}}}memorizeAddRecord(e,t=null,r=null){const o=this.getFormFieldForElements();if(null===o)return;let n=Utility.trimExplode(",",o.value);if(t){const r=[];for(let o=0;o<n.length;o++)n[o].length&&r.push(n[o]),t===n[o]&&r.push(e);n=r}else n.push(e);o.value=n.join(","),FormEngineValidation.markFieldAsChanged(o),document.dispatchEvent(new Event("change")),this.setUnique(e,r),FormEngine.reinitialize(),FormEngineValidation.initializeInputFields(),FormEngineValidation.validate(this.container)}memorizeRemoveRecord(e){const t=this.getFormFieldForElements();if(null===t)return[];const r=Utility.trimExplode(",",t.value),o=r.indexOf(e);return o>-1&&(r.splice(o,1),t.value=r.join(","),FormEngineValidation.markFieldAsChanged(t),document.dispatchEvent(new Event("change"))),r}deleteRecord(e,t=!1){const r=SiteLanguageContainer.getInlineRecordContainer(e),o=r.dataset.objectUid;if(r.classList.add("t3js-inline-record-deleted"),!r.classList.contains(States.new)&&!t){const e=this.container.querySelector(selector`[name="cmd${r.dataset.fieldName}[delete]"]`);e.removeAttribute("disabled"),r.parentElement.insertAdjacentElement("afterbegin",e)}new RegularEvent("transitionend",(()=>{r.remove(),FormEngineValidation.validate(this.container)})).bindTo(r),this.revertUnique(o),this.memorizeRemoveRecord(o),r.classList.add("form-irre-object--deleted")}getProgress(e,t){const r="#"+t+"_header";let o;return void 0!==this.progressQueue[e]?o=this.progressQueue[e]:(o=NProgress,o.configure({parent:r,showSpinner:!1}),this.progressQueue[e]=o),o}getFormFieldForElements(){const e=document.getElementsByName(this.container.dataset.formField);return e.length>0?e[0]:null}isUniqueElementUsed(e){const t=TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup];return-1!==SiteLanguageContainer.getValuesFromHashMap(t.used).indexOf(e)}removeUsed(e){const t=TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup],r=SiteLanguageContainer.getValuesFromHashMap(t.used),o=e.querySelector('[name="data['+t.table+"]["+e.dataset.objectUid+"]["+t.field+']"]');if(null!==o){const e=o.options[o.selectedIndex].value;for(const t of r)t!==e&&SiteLanguageContainer.removeSelectOptionByValue(o,t)}}setUnique(e,t){const r=TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup],o=document.getElementById(this.container.dataset.objectGroup+"_selector");if(-1!==r.max){const n=this.getFormFieldForElements(),i=this.container.dataset.objectGroup+Separators.structureSeparator+e;let a=SiteLanguageContainer.getInlineRecordContainer(i).querySelector('[name="data['+r.table+"]["+e+"]["+r.field+']"]');const s=SiteLanguageContainer.getValuesFromHashMap(r.used);if(null!==o){if(null!==a)for(const e of s)SiteLanguageContainer.removeSelectOptionByValue(a,e);for(const e of s)SiteLanguageContainer.removeSelectOptionByValue(a,e);void 0!==r.used.length&&(r.used={}),r.used[e]={table:r.elTable,uid:t}}if(null!==n&&SiteLanguageContainer.selectOptionValueExists(o,t)){const o=Utility.trimExplode(",",n.value);for(const n of o)a=document.querySelector('[name="data['+r.table+"]["+n+"]["+r.field+']"]'),null!==a&&n!==e&&SiteLanguageContainer.removeSelectOptionByValue(a,t)}}SiteLanguageContainer.selectOptionValueExists(o,t)&&(SiteLanguageContainer.removeSelectOptionByValue(o,t),r.used[e]={table:r.elTable,uid:t})}revertUnique(e){const t=TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup],r=this.container.dataset.objectGroup+Separators.structureSeparator+e,o=SiteLanguageContainer.getInlineRecordContainer(r),n=o.querySelector('[name="data['+t.table+"]["+o.dataset.objectUid+"]["+t.field+']"]');let i;if(null!==n)i=n.value;else{if(""===o.dataset.tableUniqueOriginalValue)return;i=o.dataset.tableUniqueOriginalValue.replace(t.table+"_","")}if("9223372036854775807"!==i){const e=document.getElementById(this.container.dataset.objectGroup+"_selector");SiteLanguageContainer.reAddSelectOption(e,i,t)}if(-1===t.max)return;const a=this.getFormFieldForElements();if(null===a)return;const s=Utility.trimExplode(",",a.value);let l;for(let e=0;e<s.length;e++)l=document.querySelector('[name="data['+t.table+"]["+s[e]+"]["+t.field+']"]'),null!==l&&SiteLanguageContainer.reAddSelectOption(l,i,t);delete t.used[e]}}window.customElements.define("typo3-formengine-container-sitelanguage",SiteLanguageContainer);