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
import DocumentService from"@typo3/core/document-service.js";import $ from"jquery";import FormEngineValidation from"@typo3/backend/form-engine-validation.js";import{default as Modal}from"@typo3/backend/modal.js";import*as MessageUtility from"@typo3/backend/utility/message-utility.js";import Severity from"@typo3/backend/severity.js";import*as BackendExceptionModule from"@typo3/backend/backend-exception.js";import InteractionRequestMap from"@typo3/backend/event/interaction-request-map.js";import Utility from"@typo3/backend/utility.js";import{selector}from"@typo3/core/literals.js";import"@typo3/backend/form-engine/element/extra/char-counter.js";import Hotkeys,{ModifierKeys}from"@typo3/backend/hotkeys.js";export default(function(){function e(e,t){t?n.interactionRequestMap.resolveFor(e):n.interactionRequestMap.rejectFor(e)}const t=new Map;t.set("typo3-backend-form-update-value",(e=>{const t=document.querySelector(selector`[name="${e.elementName}"]`),o=document.querySelector(selector`[data-formengine-input-name="${e.elementName}"]`);n.Validation.updateInputField(e.elementName),null!==t&&(n.Validation.markFieldAsChanged(t),n.Validation.validateField(t)),null!==o&&o!==t&&n.Validation.validateField(o)})),t.set("typo3-backend-form-reload",(e=>{n.requestFormEngineUpdate(e.confirmation)})),t.set("typo3-backend-form-update-bitmask",((e,t)=>{const o=t.target,a=n.formElement[e.elementName],i=o.checked!==e.invert,r=Math.pow(2,e.position),l=Math.pow(2,e.total)-r-1;a.value=i?a.value|r:a.value&l,a.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0}))}));const n={consumeTypes:["typo3.setUrl","typo3.beforeSetUrl","typo3.refresh"],Validation:FormEngineValidation,interactionRequestMap:InteractionRequestMap,formName:TYPO3.settings.FormEngine.formName,formElement:void 0,openedPopupWindow:null,legacyFieldChangedCb:function(){$.isFunction(TYPO3.settings.FormEngine.legacyFieldChangedCb)&&TYPO3.settings.FormEngine.legacyFieldChangedCb()},browserUrl:""};return Object.defineProperty(n,"formElement",{get:()=>document.forms.namedItem(n.formName),enumerable:!0,configurable:!1}),n.openPopupWindow=function(e,t,o){const a={mode:e,bparams:t};return o&&("db"===e?a.expandPage=o:a.expandFolder=o),Modal.advanced({type:Modal.types.iframe,content:n.browserUrl+"&"+new URLSearchParams(a).toString(),size:Modal.sizes.large})},n.setSelectOptionFromExternalSource=function(e,t,o,a,i,r){i=String(i);let l,c,s=!1,d=!1;c=n.getFieldElement(e),l=c.get(0);const m=c.get(0);if(null===m||"--div--"===t||m instanceof HTMLOptGroupElement)return;const u=n.getFieldElement(e,"_list",!0);if(u.length>0&&(c=u,l=c.get(0),s=c.prop("multiple")&&"1"!=c.prop("size"),d=!0),s||d){const d=n.getFieldElement(e,"_avail"),u=d.get(0);if(!s){for(const e of l.querySelectorAll("option")){const t=d.find(selector`option[value="${$(e).attr("value")}"]`);t.length&&(t.removeClass("hidden").prop("disabled",!1),n.enableOptGroup(t.get(0)))}c.empty()}if(i){let e=!1,o=new RegExp("(^|,)"+t+"($|,)");i.match(o)?(c.empty(),e=!0):1==c.find("option").length&&(o=new RegExp("(^|,)"+c.find("option").prop("value")+"($|,)"),i.match(o)&&(c.empty(),e=!0)),e&&void 0!==r&&r.closest("select").querySelectorAll("[disabled]").forEach((function(e){e.classList.remove("hidden"),e.disabled=!1,n.enableOptGroup(e)}))}let f=!0;const p=n.getFieldElement(e,"_mul",!0);if(0==p.length||0==p.val()){for(const e of l.querySelectorAll("option"))if(e.value==t){f=!1;break}if(f&&void 0!==r){r.classList.add("hidden"),r.disabled=!0;const e=r.parentElement;e instanceof HTMLOptGroupElement&&0===e.querySelectorAll("option:not([disabled]):not([hidden]):not(.hidden)").length&&(e.disabled=!0,e.classList.add("hidden"))}}if(f){const e=$("<option></option>");e.attr({value:t,title:a}).text(o),e.appendTo(c),n.updateHiddenFieldValueFromSelect(l,m),n.legacyFieldChangedCb(),n.Validation.markFieldAsChanged(m),n.Validation.validateField(l),n.Validation.validateField(u)}}else{const e=/_(\d+)$/,o=t.toString().match(e);null!=o&&(t=o[1]),c.val(t),n.Validation.validateField(l)}},n.updateHiddenFieldValueFromSelect=function(e,t){const n=Array.from(e.options).map((e=>e.value));t.value=n.join(","),t.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0}))},n.getFormElement=function(e){const t=$(selector`form[name="${n.formName}"]:first`);if(!e)return t;{const o=n.getFieldElement(e),a=n.getFieldElement(e,"_list");if(o.length>0&&("select-one"===o.prop("type")||a.length>0&&a.prop("type").match(/select-(one|multiple)/)))return t;console.error("Form fields missing: form: "+n.formName+", field name: "+e),alert("Form field is invalid")}},n.getFieldElement=function(e,t,o){const a=$(selector`form[name="${n.formName}"]:first`);if(t){let n;switch(t){case"_list":n=$(selector`:input[data-formengine-input-name="${e}"]:not([type=hidden])`,a);break;case"_avail":n=$(selector`:input[data-relatedfieldname="${e}"]`,a);break;case"_mul":case"_hr":n=$(selector`:input[type=hidden][data-formengine-input-name="${e}"]`,a);break;default:n=null}if(n&&n.length>0||!0===o)return n}return $(selector`:input[name="${e}"]`,a)},n.initializeEvents=function(){top.TYPO3&&void 0!==top.TYPO3.Backend&&(top.TYPO3.Backend.consumerScope.attach(n),window.addEventListener("pagehide",(()=>top.TYPO3.Backend.consumerScope.detach(n)),{once:!0})),$(document).on("click",".t3js-editform-close",(e=>{e.preventDefault(),n.preventExitIfNotSaved(n.preventExitIfNotSavedCallback)})).on("click",".t3js-editform-view",(e=>{e.preventDefault(),n.previewAction(e,n.previewActionCallback)})).on("click",".t3js-editform-new",(e=>{e.preventDefault(),n.newAction(e,n.newActionCallback)})).on("click",".t3js-editform-duplicate",(e=>{e.preventDefault(),n.duplicateAction(e,n.duplicateActionCallback)})).on("click",".t3js-editform-delete-record",(e=>{e.preventDefault(),n.deleteAction(e,n.deleteActionCallback)})).on("click",".t3js-editform-submitButton",(e=>{const t=$(e.currentTarget),n=t.data("name")||e.currentTarget.name,o=$("<input />").attr("type","hidden").attr("name",n).attr("value","1");t.parents("form").append(o)})).on("change",'.t3-form-field-eval-null-checkbox input[type="checkbox"]',(e=>{$(e.currentTarget).closest(".t3js-formengine-field-item").toggleClass("disabled")})).on("change",'.t3js-form-field-eval-null-placeholder-checkbox input[type="checkbox"]',(e=>{n.toggleCheckboxField($(e.currentTarget)),n.Validation.markFieldAsChanged(e.currentTarget)})).on("change",(()=>{$(".module-docheader-bar .btn").removeClass("disabled").prop("disabled",!1)})).on("click",".t3js-element-browser",(function(e){e.preventDefault(),e.stopPropagation();const t=$(e.currentTarget),o=t.data("mode"),a=t.data("params"),i=t.data("entryPoint");n.openPopupWindow(o,a,i)})).on("click",'[data-formengine-field-change-event="click"]',(e=>{const t=JSON.parse(e.currentTarget.dataset.formengineFieldChangeItems);n.processOnFieldChange(t,e)})).on("change",'[data-formengine-field-change-event="change"]',(e=>{const t=JSON.parse(e.currentTarget.dataset.formengineFieldChangeItems);n.processOnFieldChange(t,e)})),n.formElement.addEventListener("submit",(function(e){const t=e.target;if("0"===t.closeDoc?.value&&null!==e.submitter&&("A"===e.submitter.tagName||e.submitter.hasAttribute("form"))&&!e.defaultPrevented){const e=t.doSave;null!==e&&(e.value="1")}})),window.addEventListener("message",n.handlePostMessage)},n.consume=function(t){if(!t)throw new BackendExceptionModule.BackendException("No interaction request given",1496589980);let o;const a=new Promise(((e,t)=>{o={resolve:e,reject:t}}));if(t.concernsTypes(n.consumeTypes)){const a=t.outerMostRequest;n.interactionRequestMap.attachFor(a,o),a.isProcessed()?e(a,a.getProcessedData().response):n.hasChange()||n.isNew()?n.preventExitIfNotSaved((function(t){a.setProcessedData({response:t}),e(a,t)})):n.interactionRequestMap.resolveFor(a)}return a},n.handlePostMessage=function(e){if(!MessageUtility.MessageUtility.verifyOrigin(e.origin))throw"Denied message sent by "+e.origin;if("typo3:elementBrowser:elementAdded"===e.data.actionName){if(void 0===e.data.fieldName)throw"fieldName not defined in message";if(void 0===e.data.value)throw"value not defined in message";const t=e.data.label||e.data.value,o=e.data.title||t,a=e.data.exclusiveValues||"";n.setSelectOptionFromExternalSource(e.data.fieldName,e.data.value,t,o,a)}},n.initializeRemainingCharacterViews=function(){document.querySelectorAll('[maxlength]:not([data-input-type="datetimepicker"]):not(.t3js-color-picker)').forEach((e=>{const t=e.closest(".t3js-formengine-field-item");if(null!==t&&null===t.querySelector("typo3-backend-formengine-char-counter")){const n=document.createElement("typo3-backend-formengine-char-counter");n.setAttribute("target",`[data-formengine-input-name="${selector`${e.dataset.formengineInputName}`}"]`),t.append(n)}}))},n.initializeMinimumCharactersLeftViews=function(){const e=(e,t)=>{const n=t.currentTarget.closest(".t3js-formengine-field-item"),o=n.querySelector(".t3js-charcounter-min"),a=TYPO3.lang["FormEngine.minCharactersLeft"].replace("{0}",e);if(o)o.querySelector("span").innerHTML=a;else{const e=document.createElement("div");e.classList.add("t3js-charcounter-min");const t=document.createElement("span");t.classList.add("badge","badge-danger"),t.innerHTML=a,e.append(t);let o=n.querySelector(".t3js-charcounter-wrapper");o||(o=document.createElement("div"),o.classList.add("t3js-charcounter-wrapper"),n.append(o)),o.prepend(e)}},t=e=>{const t=e.currentTarget.closest(".t3js-formengine-field-item").querySelector(".t3js-charcounter-min");t&&t.remove()};document.querySelectorAll('[minlength]:not([data-input-type="datetimepicker"]):not(.t3js-charcounter-min-initialized)').forEach((o=>{o.addEventListener("focus",(t=>{const a=n.getMinCharacterLeftCount(o);a>0&&e(a,t)})),o.addEventListener("blur",t),o.addEventListener("keyup",(a=>{const i=n.getMinCharacterLeftCount(o);i>0?e(i,a):t(a)}))}))},n.getMinCharacterLeftCount=function(e){const t=e.value,n=e.minLength,o=t.length;if(0===o)return 0;return n-o-(t.match(/\n/g)||[]).length},n.initializeNullNoPlaceholderCheckboxes=function(){document.querySelectorAll(".t3-form-field-eval-null-checkbox").forEach((e=>{const t=e.querySelector('input[type="checkbox"]'),n=e.closest(".t3js-formengine-field-item");t.checked||n.classList.add("disabled")}))},n.initializeNullWithPlaceholderCheckboxes=function(){document.querySelectorAll(".t3js-form-field-eval-null-placeholder-checkbox").forEach((e=>{n.toggleCheckboxField($(e).find('input[type="checkbox"]'),!1)}))},n.toggleCheckboxField=function(e,t=!0){const n=e.closest(".t3js-formengine-field-item");e.prop("checked")?(n.find(".t3js-formengine-placeholder-placeholder").hide(),n.find(".t3js-formengine-placeholder-formfield").show(),t&&n.find(".t3js-formengine-placeholder-formfield").find(":input").trigger("focus")):(n.find(".t3js-formengine-placeholder-placeholder").show(),n.find(".t3js-formengine-placeholder-formfield").hide())},n.reinitialize=function(){const e=document.querySelectorAll(".t3js-clearable");e.length>0&&import("@typo3/backend/input/clearable.js").then((function(){e.forEach((e=>e.clearable()))})),n.initializeNullNoPlaceholderCheckboxes(),n.initializeNullWithPlaceholderCheckboxes(),n.initializeLocalizationStateSelector(),n.initializeMinimumCharactersLeftViews(),n.initializeRemainingCharacterViews()},n.initializeLocalizationStateSelector=function(){document.querySelectorAll(".t3js-l10n-state-container").forEach((e=>{const t=e.closest(".t3js-formengine-field-item")?.querySelector("[data-formengine-input-name]");if(null==t)return;const n=e.querySelector('input[type="radio"]:checked')?.value;void 0===n&&console.warn("The localization state of the field "+t.dataset.formengineInputName+" cannot be determined. This smells like a DataHandler bug."),"parent"!==n&&"source"!==n||(t.disabled=!0)}))},n.hasChange=function(){const e=$(selector`form[name="${n.formName}"] .has-change`).length>0,t=$('[name^="data["].has-change').length>0;return e||t},n.isNew=function(){return null!==document.querySelector('form[name="'+n.formName+'"] .typo3-TCEforms.is-new')},n.preventExitIfNotSavedCallback=()=>{n.closeDocument()},n.preventFollowLinkIfNotSaved=function(e){return n.preventExitIfNotSaved((function(){window.location.href=e})),!1},n.preventExitIfNotSaved=function(e){if(e=e||n.preventExitIfNotSavedCallback,n.hasChange()||n.isNew()){const t=TYPO3.lang["label.confirm.close_without_save.title"]||"Do you want to close without saving?",o=TYPO3.lang["label.confirm.close_without_save.content"]||"You currently have unsaved changes. Are you sure you want to discard these changes?",a=[{text:TYPO3.lang["buttons.confirm.close_without_save.no"]||"No, I will continue editing",btnClass:"btn-default",name:"no"},{text:TYPO3.lang["buttons.confirm.close_without_save.yes"]||"Yes, discard my changes",btnClass:"btn-default",name:"yes"}];0===$(".has-error").length&&a.push({text:TYPO3.lang["buttons.confirm.save_and_close"]||"Save and close",btnClass:"btn-primary",name:"save",active:!0});const i=Modal.confirm(t,o,Severity.warning,a);i.addEventListener("button.clicked",(function(t){"no"===t.target.name?i.hideModal():"yes"===t.target.name?(i.hideModal(),e.call(null,!0)):"save"===t.target.name&&(i.hideModal(),n.saveAndCloseDocument())}))}else e.call(null,!0)},n.preventSaveIfHasErrors=function(){if($(".has-error").length>0){const e=TYPO3.lang["label.alert.save_with_error.title"]||"You have errors in your form!",t=TYPO3.lang["label.alert.save_with_error.content"]||"Please check the form, there is at least one error in your form.",n=Modal.confirm(e,t,Severity.error,[{text:TYPO3.lang["buttons.alert.save_with_error.ok"]||"OK",btnClass:"btn-danger",name:"ok"}]);return n.addEventListener("button.clicked",(function(e){"ok"===e.target.name&&n.hideModal()})),!1}return!0},n.requestFormEngineUpdate=function(e){const t=()=>{n.Validation.suspend(),n.saveDocument(),n.Validation.resume()};e||t();const o=Modal.advanced({title:TYPO3.lang["FormEngine.refreshRequiredTitle"],content:TYPO3.lang["FormEngine.refreshRequiredContent"],severity:Severity.warning,staticBackdrop:!0,buttons:[{text:TYPO3.lang["button.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>{o.hideModal()}},{text:TYPO3.lang["button.ok"]||"OK",btnClass:"btn-"+Severity.getCssClass(Severity.warning),name:"ok",trigger:()=>{n.closeModalsRecursive(),t()}}]})},n.processOnFieldChange=function(e,n){e.forEach((e=>{const o=t.get(e.name);o instanceof Function&&o.call(null,e.data||null,n)}))},n.registerOnFieldChangeHandler=function(e,n){t.has(e)&&console.warn("Handler for onFieldChange name `"+e+"` has been overridden."),t.set(e,n)},n.closeModalsRecursive=function(){void 0!==Modal.currentModal&&null!==Modal.currentModal&&(Modal.currentModal.addEventListener("typo3-modal-hidden",(function(){n.closeModalsRecursive()})),Modal.currentModal.hideModal())},n.previewAction=function(e,t){t=t||n.previewActionCallback;const o=e.currentTarget.href,a="isNew"in e.target.dataset,i=$("<input />").attr("type","hidden").attr("name","_savedokview").attr("value","1");n.hasChange()||n.isNew()?n.showPreviewModal(o,a,i,t):($(selector`form[name="${n.formName}"]`).append(i),window.open("","newTYPO3frontendWindow"),n.formElement.submit())},n.previewActionCallback=function(e,t,o){switch(Modal.dismiss(),e){case"discard":const e=window.open(t,"newTYPO3frontendWindow");e.focus(),Utility.urlsPointToSameServerSideResource(e.location.href,t)&&e.location.reload();break;case"save":$(selector`form[name="${n.formName}"]`).append($(o)),window.open("","newTYPO3frontendWindow"),n.saveDocument()}},n.showPreviewModal=function(e,t,n,o){const a=TYPO3.lang["label.confirm.view_record_changed.title"]||"Do you want to save before viewing?",i={text:TYPO3.lang["buttons.confirm.view_record_changed.cancel"]||"Cancel",btnClass:"btn-default",name:"cancel"},r={text:TYPO3.lang["buttons.confirm.view_record_changed.no-save"]||"View without changes",btnClass:"btn-default",name:"discard"},l={text:TYPO3.lang["buttons.confirm.view_record_changed.save"]||"Save changes and view",btnClass:"btn-primary",name:"save",active:!0};let c=[],s="";t?(c=[i,l],s=TYPO3.lang["label.confirm.view_record_changed.content.is-new-page"]||"You need to save your changes before viewing the page. Do you want to save and view them now?"):(c=[i,r,l],s=TYPO3.lang["label.confirm.view_record_changed.content"]||"You currently have unsaved changes. You can either discard these changes or save and view them.");const d=Modal.confirm(a,s,Severity.info,c);d.addEventListener("button.clicked",(function(t){o(t.target.name,e,n,d)}))},n.newAction=function(e,t){t=t||n.newActionCallback;const o=$("<input />").attr("type","hidden").attr("name","_savedoknew").attr("value","1"),a="isNew"in e.target.dataset;n.hasChange()||n.isNew()?n.showNewModal(a,o,t):($(selector`form[name="${n.formName}"]`).append(o),n.formElement.submit())},n.newActionCallback=function(e,t){const o=$(selector`form[name="${n.formName}"]`);switch(Modal.dismiss(),e){case"no":o.append(t),n.formElement.submit();break;case"yes":o.append(t),n.saveDocument()}},n.showNewModal=function(e,t,n){const o=TYPO3.lang["label.confirm.new_record_changed.title"]||"Do you want to save before adding?",a=TYPO3.lang["label.confirm.new_record_changed.content"]||"You need to save your changes before creating a new record. Do you want to save and create now?";let i=[];const r={text:TYPO3.lang["buttons.confirm.new_record_changed.cancel"]||"Cancel",btnClass:"btn-default",name:"cancel"},l={text:TYPO3.lang["buttons.confirm.new_record_changed.no"]||"No, just add",btnClass:"btn-default",name:"no"},c={text:TYPO3.lang["buttons.confirm.new_record_changed.yes"]||"Yes, save and create now",btnClass:"btn-primary",name:"yes",active:!0};i=e?[r,c]:[r,l,c];Modal.confirm(o,a,Severity.info,i).addEventListener("button.clicked",(function(e){n(e.target.name,t)}))},n.duplicateAction=function(e,t){t=t||n.duplicateActionCallback;const o=$("<input />").attr("type","hidden").attr("name","_duplicatedoc").attr("value","1"),a="isNew"in e.target.dataset;n.hasChange()||n.isNew()?n.showDuplicateModal(a,o,t):($(selector`form[name="${n.formName}"]`).append(o),n.formElement.submit())},n.duplicateActionCallback=function(e,t){const o=$(selector`form[name="${n.formName}"]`);switch(Modal.dismiss(),e){case"no":o.append(t),n.formElement.submit();break;case"yes":o.append(t),n.saveDocument()}},n.showDuplicateModal=function(e,t,n){const o=TYPO3.lang["label.confirm.duplicate_record_changed.title"]||"Do you want to save before duplicating this record?",a=TYPO3.lang["label.confirm.duplicate_record_changed.content"]||"You currently have unsaved changes. Do you want to save your changes before duplicating this record?";let i=[];const r={text:TYPO3.lang["buttons.confirm.duplicate_record_changed.cancel"]||"Cancel",btnClass:"btn-default",name:"cancel"},l={text:TYPO3.lang["buttons.confirm.duplicate_record_changed.no"]||"No, just duplicate the original",btnClass:"btn-default",name:"no"},c={text:TYPO3.lang["buttons.confirm.duplicate_record_changed.yes"]||"Yes, save and duplicate this record",btnClass:"btn-primary",name:"yes",active:!0};i=e?[r,c]:[r,l,c];Modal.confirm(o,a,Severity.info,i).addEventListener("button.clicked",(function(e){n(e.target.name,t)}))},n.deleteAction=function(e,t){t=t||n.deleteActionCallback;const o=$(e.target);n.showDeleteModal(o,t)},n.deleteActionCallback=function(e,t){Modal.dismiss(),"yes"===e&&n.invokeRecordDeletion(t)},n.showDeleteModal=function(e,t){const n=TYPO3.lang["label.confirm.delete_record.title"]||"Delete this record?";let o=(TYPO3.lang["label.confirm.delete_record.content"]||"Are you sure you want to delete the record '%s'?").replace("%s",e.data("record-info"));e.data("reference-count-message")&&(o+="\n"+e.data("reference-count-message")),e.data("translation-count-message")&&(o+="\n"+e.data("translation-count-message"));Modal.confirm(n,o,Severity.warning,[{text:TYPO3.lang["buttons.confirm.delete_record.no"]||"Cancel",btnClass:"btn-default",name:"no"},{text:TYPO3.lang["buttons.confirm.delete_record.yes"]||"Yes, delete this record",btnClass:"btn-warning",name:"yes",active:!0}]).addEventListener("button.clicked",(function(n){t(n.target.name,e)}))},n.enableOptGroup=function(e){const t=e.parentElement;t instanceof HTMLOptGroupElement&&t.querySelectorAll("option:not([hidden]):not([disabled]):not(.hidden)").length&&(t.hidden=!1,t.disabled=!1,t.classList.remove("hidden"))},n.closeDocument=function(){n.formElement.closeDoc.value=1,n.formElement.submit()},n.saveDocument=function(){const e=document.activeElement;(e instanceof HTMLInputElement||e instanceof HTMLSelectElement||e instanceof HTMLTextAreaElement)&&e.blur(),n.formElement.doSave.value=1,n.formElement.requestSubmit()},n.saveAndCloseDocument=function(){const e=document.createElement("input");e.type="hidden",e.name="_saveandclosedok",e.value="1",document.querySelector(selector`form[name="${n.formName}"]`).append(e),n.saveDocument()},n.initialize=function(e){n.browserUrl=e,DocumentService.ready().then((()=>{n.initializeEvents(),n.Validation.initialize(n.formElement),n.reinitialize(),$("#t3js-ui-block").remove(),Hotkeys.setScope("backend/form-engine"),Hotkeys.register([Hotkeys.normalizedCtrlModifierKey,"s"],(e=>{e.preventDefault(),n.saveDocument()}),{scope:"backend/form-engine",allowOnEditables:!0,bindElement:n.formElement._savedok}),Hotkeys.register([Hotkeys.normalizedCtrlModifierKey,ModifierKeys.SHIFT,"s"],(e=>{e.preventDefault(),n.saveAndCloseDocument()}),{scope:"backend/form-engine",allowOnEditables:!0})}))},n.invokeRecordDeletion=function(e){window.location.href=e.attr("href")},TYPO3.FormEngine=n,n}());