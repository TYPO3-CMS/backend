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
import $ from"jquery";import{DateTime}from"luxon";import Md5 from"@typo3/backend/hashing/md5.js";import DocumentSaveActions from"@typo3/backend/document-save-actions.js";import Modal from"@typo3/backend/modal.js";import Severity from"@typo3/backend/severity.js";import Utility from"@typo3/backend/utility.js";import RegularEvent from"@typo3/core/event/regular-event.js";import DomHelper from"@typo3/backend/utility/dom-helper.js";import{selector}from"@typo3/core/literals.js";export default(function(){const FormEngineValidation={rulesSelector:"[data-formengine-validation-rules]",inputSelector:"[data-formengine-input-params]",markerSelector:".t3js-formengine-validation-marker",groupFieldHiddenElement:".t3js-formengine-field-group input[type=hidden]",relatedFieldSelector:"[data-relatedfieldname]",errorClass:"has-error",lastYear:0,lastDate:0,lastTime:0,passwordDummy:"********"};let formEngineFormElement;const customEvaluations=new Map;return FormEngineValidation.initialize=function(e){formEngineFormElement=e,formEngineFormElement.querySelectorAll("."+FormEngineValidation.errorClass).forEach((e=>e.classList.remove(FormEngineValidation.errorClass))),FormEngineValidation.initializeInputFields(),new RegularEvent("change",((e,n)=>{FormEngineValidation.validateField(n),FormEngineValidation.markFieldAsChanged(n)})).delegateTo(formEngineFormElement,FormEngineValidation.rulesSelector),FormEngineValidation.registerSubmitCallback();const n=new Date;FormEngineValidation.lastYear=FormEngineValidation.getYear(n),FormEngineValidation.lastDate=FormEngineValidation.getDate(n),FormEngineValidation.lastTime=0,FormEngineValidation.validate()},FormEngineValidation.initializeInputFields=function(){formEngineFormElement.querySelectorAll(FormEngineValidation.inputSelector).forEach((e=>{const n=JSON.parse(e.dataset.formengineInputParams).field,t=formEngineFormElement.querySelector(selector`[name="${n}"]`);"formengineInputInitialized"in e.dataset||(t.dataset.config=e.dataset.formengineInputParams,FormEngineValidation.initializeInputField(n))}))},FormEngineValidation.initializeInputField=function(e){const n=formEngineFormElement.querySelector(selector`[name="${e}"]`),t=formEngineFormElement.querySelector(selector`[data-formengine-input-name="${e}"]`);if(void 0!==n.dataset.config){const e=JSON.parse(n.dataset.config),a=Utility.trimExplode(",",e.evalList);let i=n.value;for(let n=0;n<a.length;n++)i=FormEngineValidation.formatValue(a[n],i,e);i.length&&(t.value=i)}new RegularEvent("change",(()=>{FormEngineValidation.updateInputField(t.dataset.formengineInputName)})).bindTo(t),t.dataset.formengineInputInitialized="true"},FormEngineValidation.registerCustomEvaluation=function(e,n){customEvaluations.has(e)||customEvaluations.set(e,n)},FormEngineValidation.formatValue=function(e,n,t){let a,i,o="";switch(e){case"date":if(n.toString().indexOf("-")>0){o=DateTime.fromISO(n.toString(),{zone:"utc"}).toFormat("dd-MM-yyyy")}else{if(""===n||"0"===n)return"";if(a=parseInt(n.toString(),10),isNaN(a))return"";i=new Date(1e3*a);o=i.getUTCDate().toString(10).padStart(2,"0")+"-"+(i.getUTCMonth()+1).toString(10).padStart(2,"0")+"-"+this.getYear(i)}break;case"datetime":if(""===n||"0"===n)return"";o=(FormEngineValidation.formatValue("time",n,t)+" "+FormEngineValidation.formatValue("date",n,t)).trim();break;case"time":case"timesec":let r;if(n.toString().indexOf("-")>0)r=DateTime.fromISO(n.toString(),{zone:"utc"});else{if(""===n||"0"===n)return"";if(a="number"==typeof n?n:parseInt(n),isNaN(a))return"";r=DateTime.fromSeconds(a,{zone:"utc"})}o="timesec"===e?r.toFormat("HH:mm:ss"):r.toFormat("HH:mm");break;case"password":o=n?FormEngineValidation.passwordDummy:"";break;default:o=n.toString()}return o},FormEngineValidation.updateInputField=function(e){const n=formEngineFormElement.querySelector(selector`[name="${e}"]`),t=formEngineFormElement.querySelector(selector`[data-formengine-input-name="${e}"]`);if(void 0!==n.dataset.config){const e=JSON.parse(n.dataset.config),a=Utility.trimExplode(",",e.evalList);let i=t.value;for(let n=0;n<a.length;n++)i=FormEngineValidation.processValue(a[n],i,e);let o=i;for(let n=0;n<a.length;n++)o=FormEngineValidation.formatValue(a[n],o,e);n.value!==i&&(n.disabled&&n.dataset.enableOnModification&&(n.disabled=!1),n.value=i,n.dispatchEvent(new Event("change")),t.value=o)}},FormEngineValidation.validateField=function(e,n){if(e instanceof $&&(console.warn("Passing a jQuery element to FormEngineValidation.validateField() is deprecated and will be removed in TYPO3 v14."),console.trace(),e=e.get(0)),!(e instanceof HTMLElement))return n;if(n=n||e.value||"",void 0===e.dataset.formengineValidationRules)return n;const t=JSON.parse(e.dataset.formengineValidationRules);let a=!1,i=0;const o=n;let r,l,s;Array.isArray(n)||(n=n.trimStart());for(const o of t){if(a)break;switch(o.type){case"required":""===n&&(a=!0,e.closest(FormEngineValidation.markerSelector).classList.add(FormEngineValidation.errorClass));break;case"range":if(""!==n){if((o.minItems||o.maxItems)&&(r=formEngineFormElement.querySelector(selector`[name="${e.dataset.relatedfieldname}"]`),i=null!==r?Utility.trimExplode(",",r.value).length:parseInt(e.value,10),void 0!==o.minItems&&(l=1*o.minItems,!isNaN(l)&&i<l&&(a=!0)),void 0!==o.maxItems&&(s=1*o.maxItems,!isNaN(s)&&i>s&&(a=!0))),void 0!==o.lower){const e=1*o.lower;!isNaN(e)&&parseInt(n,10)<e&&(a=!0)}if(void 0!==o.upper){const e=1*o.upper;!isNaN(e)&&parseInt(n,10)>e&&(a=!0)}}break;case"select":case"category":(o.minItems||o.maxItems)&&(r=formEngineFormElement.querySelector(selector`[name="${e.dataset.relatedfieldname}"]`),i=null!==r?Utility.trimExplode(",",r.value).length:e instanceof HTMLSelectElement?e.querySelectorAll("option:checked").length:e.querySelectorAll("input[value]:checked").length,void 0!==o.minItems&&(l=1*o.minItems,!isNaN(l)&&i<l&&(a=!0)),void 0!==o.maxItems&&(s=1*o.maxItems,!isNaN(s)&&i>s&&(a=!0)));break;case"group":case"folder":case"inline":(o.minItems||o.maxItems)&&(i=Utility.trimExplode(",",e.value).length,void 0!==o.minItems&&(l=1*o.minItems,!isNaN(l)&&i<l&&(a=!0)),void 0!==o.maxItems&&(s=1*o.maxItems,!isNaN(s)&&i>s&&(a=!0)));break;case"min":(e instanceof HTMLInputElement||e instanceof HTMLTextAreaElement)&&e.value.length>0&&e.value.length<e.minLength&&(a=!0)}}const m=!a,d=e.closest(FormEngineValidation.markerSelector);return null!==d&&d.classList.toggle(FormEngineValidation.errorClass,!m),FormEngineValidation.markParentTab(e,m),formEngineFormElement.dispatchEvent(new CustomEvent("t3-formengine-postfieldvalidation",{cancelable:!1,bubbles:!0})),o},FormEngineValidation.processValue=function(e,n,t){let a="",i="",o=0,r=n;switch(e){case"alpha":case"num":case"alphanum":case"alphanum_x":for(a="",o=0;o<n.length;o++){const t=n.substr(o,1);let i="_"===t||"-"===t,r=t>="a"&&t<="z"||t>="A"&&t<="Z",l=t>="0"&&t<="9";switch(e){case"alphanum":i=!1;break;case"alpha":l=!1,i=!1;break;case"num":r=!1,i=!1}(r||l||i)&&(a+=t)}a!==n&&(r=a);break;case"is_in":if(t.is_in){i=""+n,t.is_in=t.is_in.replace(/[-[\]{}()*+?.,\\^$|#\s]/g,"\\$&");const e=new RegExp("[^"+t.is_in+"]+","g");a=i.replace(e,"")}else a=i;r=a;break;case"nospace":r=(""+n).replace(/ /g,"");break;case"md5":""!==n&&(r=Md5.hash(n));break;case"upper":r=n.toUpperCase();break;case"lower":r=n.toLowerCase();break;case"integer":""!==n&&(r=FormEngineValidation.parseInt(n));break;case"decimal":""!==n&&(r=FormEngineValidation.parseDouble(n));break;case"trim":r=String(n).trim();break;case"datetime":""!==n&&(r=FormEngineValidation.parseDateTime(n));break;case"date":""!==n&&(r=FormEngineValidation.parseDate(n));break;case"time":case"timesec":""!==n&&(r=FormEngineValidation.parseTime(n,e));break;case"year":""!==n&&(r=FormEngineValidation.parseYear(n));break;case"null":case"password":break;default:customEvaluations.has(e)?r=customEvaluations.get(e).call(null,n):"object"==typeof TBE_EDITOR&&void 0!==TBE_EDITOR.customEvalFunctions&&"function"==typeof TBE_EDITOR.customEvalFunctions[e]&&(r=TBE_EDITOR.customEvalFunctions[e](n))}return r},FormEngineValidation.validate=function(e){(void 0===e||e instanceof Document)&&formEngineFormElement.querySelectorAll(FormEngineValidation.markerSelector+", .t3js-tabmenu-item").forEach((e=>{e.classList.remove(FormEngineValidation.errorClass,"has-validation-error")}));const n=e||document;for(const e of n.querySelectorAll(FormEngineValidation.rulesSelector))if(null===e.closest(".t3js-flex-section-deleted, .t3js-inline-record-deleted, .t3js-file-reference-deleted")){let n=!1;const t=e.value,a=FormEngineValidation.validateField(e,t);if(Array.isArray(a)&&Array.isArray(t)){if(a.length!==t.length)n=!0;else for(let e=0;e<a.length;e++)if(a[e]!==t[e]){n=!0;break}}else a.length&&t!==a&&(n=!0);n&&(e.disabled&&e.dataset.enableOnModification&&(e.disabled=!1),e.value=a)}},FormEngineValidation.markFieldAsChanged=function(e){if(e instanceof $&&(console.warn("Passing a jQuery element to FormEngineValidation.markFieldAsChanged() is deprecated and will be removed in TYPO3 v14."),console.trace(),e=e.get(0)),!(e instanceof HTMLElement))return;const n=e.closest(".t3js-formengine-palette-field");null!==n&&n.classList.add("has-change")},FormEngineValidation.parseInt=function(e){if(!e)return 0;const n=parseInt(""+e,10);return isNaN(n)?0:n},FormEngineValidation.parseDouble=function(e,n=2){let t=""+e;t=t.replace(/[^0-9,.-]/g,"");const a="-"===t.substring(0,1);t=t.replace(/-/g,""),t=t.replace(/,/g,"."),-1===t.indexOf(".")&&(t+=".0");const i=t.split("."),o=i.pop();let r=Number(i.join("")+"."+o);return a&&(r*=-1),t=r.toFixed(n),t},FormEngineValidation.parseDateTime=function(e){const n=e.indexOf(" ");if(-1!==n){const t=FormEngineValidation.parseDate(e.substring(n+1));FormEngineValidation.lastTime=t+FormEngineValidation.parseTime(e.substring(0,n),"time")}else FormEngineValidation.lastTime=FormEngineValidation.parseDate(e);return FormEngineValidation.lastTime},FormEngineValidation.parseDate=function(e){return FormEngineValidation.lastDate=DateTime.fromFormat(e,"dd-MM-yyyy",{zone:"utc"}).toUnixInteger(),FormEngineValidation.lastDate},FormEngineValidation.parseTime=function(e,n){const t="timesec"===n?"HH:mm:ss":"HH:mm";return FormEngineValidation.lastTime=DateTime.fromFormat(e,t,{zone:"utc"}).set({year:1970,month:1,day:1}).toUnixInteger(),FormEngineValidation.lastTime<0&&(FormEngineValidation.lastTime+=86400),FormEngineValidation.lastTime},FormEngineValidation.parseYear=function(e){let n=parseInt(e,10);return isNaN(n)&&(n=FormEngineValidation.getYear(new Date)),FormEngineValidation.lastYear=n,FormEngineValidation.lastYear},FormEngineValidation.getYear=function(e){return null===e?null:e.getUTCFullYear()},FormEngineValidation.getDate=function(e){const n=new Date(FormEngineValidation.getYear(e),e.getUTCMonth(),e.getUTCDate());return FormEngineValidation.getTimestamp(n)},FormEngineValidation.pol=function(foreign,value){return eval(("-"==foreign?"-":"")+value)},FormEngineValidation.getTimestamp=function(e){return Date.parse(e instanceof Date?e.toISOString():e)/1e3},FormEngineValidation.getTime=function(e){return 60*e.getUTCHours()*60+60*e.getUTCMinutes()+FormEngineValidation.getSecs(e)},FormEngineValidation.getSecs=function(e){return e.getUTCSeconds()},FormEngineValidation.getTimeSecs=function(e){return 60*e.getHours()*60+60*e.getMinutes()+e.getSeconds()},FormEngineValidation.markParentTab=function(e,n){DomHelper.parents(e,".tab-pane").forEach((e=>{n&&(n=null===e.querySelector(".has-error"));const t=e.id;formEngineFormElement.querySelector('a[href="#'+t+'"]').closest(".t3js-tabmenu-item").classList.toggle("has-validation-error",!n)}))},FormEngineValidation.registerSubmitCallback=function(){DocumentSaveActions.getInstance().addPreSubmitCallback((()=>{if(null===document.querySelector("."+FormEngineValidation.errorClass))return!0;const e=Modal.confirm(TYPO3.lang.alert||"Alert",TYPO3.lang["FormEngine.fieldsMissing"],Severity.error,[{text:TYPO3.lang["button.ok"]||"OK",active:!0,btnClass:"btn-default",name:"ok"}]);return e.addEventListener("button.clicked",(()=>e.hideModal())),!1}))},FormEngineValidation}());