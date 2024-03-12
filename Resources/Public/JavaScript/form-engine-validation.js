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
import $ from"jquery";import{DateTime}from"luxon";import Md5 from"@typo3/backend/hashing/md5.js";import DocumentSaveActions from"@typo3/backend/document-save-actions.js";import Modal from"@typo3/backend/modal.js";import Severity from"@typo3/backend/severity.js";import{selector}from"@typo3/core/literals.js";export default(function(){const FormEngineValidation={rulesSelector:"[data-formengine-validation-rules]",inputSelector:"[data-formengine-input-params]",markerSelector:".t3js-formengine-validation-marker",groupFieldHiddenElement:".t3js-formengine-field-group input[type=hidden]",relatedFieldSelector:"[data-relatedfieldname]",errorClass:"has-error",lastYear:0,lastDate:0,lastTime:0,passwordDummy:"********"},customEvaluations=new Map;return FormEngineValidation.initialize=function(){$(document).find("."+FormEngineValidation.errorClass).removeClass(FormEngineValidation.errorClass),FormEngineValidation.initializeInputFields().promise().done((function(){$(document).on("change",FormEngineValidation.rulesSelector,(e=>{FormEngineValidation.validateField(e.currentTarget),FormEngineValidation.markFieldAsChanged(e.currentTarget)})),FormEngineValidation.registerSubmitCallback()}));const e=new Date;FormEngineValidation.lastYear=FormEngineValidation.getYear(e),FormEngineValidation.lastDate=FormEngineValidation.getDate(e),FormEngineValidation.lastTime=0,FormEngineValidation.validate()},FormEngineValidation.initializeInputFields=function(){return $(document).find(FormEngineValidation.inputSelector).each((function(e,n){const t=$(n).data("formengine-input-params"),a=t.field,i=$(selector`[name="${a}"]`);void 0===i.data("main-field")&&(i.data("main-field",a),i.data("config",t),FormEngineValidation.initializeInputField(a))}))},FormEngineValidation.initializeInputField=function(e){const n=$(selector`[name="${e}"]`),t=$(selector`[data-formengine-input-name="${e}"]`);let a=$(selector`[name="${n.data("main-field")}"]`);0===a.length&&(a=n);const i=a.data("config");if(void 0!==i){const e=FormEngineValidation.trimExplode(",",i.evalList);let a=n.val();for(let n=0;n<e.length;n++)a=FormEngineValidation.formatValue(e[n],a,i);a.length&&t.val(a)}t.data("main-field",e),t.data("config",i),t.on("change",(function(){FormEngineValidation.updateInputField(t.attr("data-formengine-input-name"))})),t.attr("data-formengine-input-initialized","true")},FormEngineValidation.registerCustomEvaluation=function(e,n){customEvaluations.has(e)||customEvaluations.set(e,n)},FormEngineValidation.formatValue=function(e,n,t){let a,i,o="";switch(e){case"date":if(n.toString().indexOf("-")>0){o=DateTime.fromISO(n.toString(),{zone:"utc"}).toFormat("dd-MM-yyyy")}else{if(""===n||"0"===n)return"";if(a=parseInt(n.toString(),10),isNaN(a))return"";i=new Date(1e3*a);o=i.getUTCDate().toString(10).padStart(2,"0")+"-"+(i.getUTCMonth()+1).toString(10).padStart(2,"0")+"-"+this.getYear(i)}break;case"datetime":if(""===n||"0"===n)return"";o=(FormEngineValidation.formatValue("time",n,t)+" "+FormEngineValidation.formatValue("date",n,t)).trim();break;case"time":case"timesec":let r;if(n.toString().indexOf("-")>0)r=DateTime.fromISO(n.toString(),{zone:"utc"});else{if(""===n||"0"===n)return"";if(a="number"==typeof n?n:parseInt(n),isNaN(a))return"";r=DateTime.fromSeconds(a,{zone:"utc"})}o="timesec"===e?r.toFormat("HH:mm:ss"):r.toFormat("HH:mm");break;case"password":o=n?FormEngineValidation.passwordDummy:"";break;default:o=n.toString()}return o},FormEngineValidation.updateInputField=function(e){const n=$(selector`[name="${e}"]`);let t=$(selector`[name="${n.data("main-field")}"]`);0===t.length&&(t=n);const a=$(selector`[data-formengine-input-name="${t.attr("name")}"]`),i=t.data("config");if(void 0!==i){const e=FormEngineValidation.trimExplode(",",i.evalList);let n=a.val();for(let t=0;t<e.length;t++)n=FormEngineValidation.processValue(e[t],n,i);let o=n;for(let n=0;n<e.length;n++)o=FormEngineValidation.formatValue(e[n],o,i);t.prop("disabled")&&t.data("enableOnModification")&&t.prop("disabled",!1),t.val(n),t.get(0).dispatchEvent(new Event("change")),a.val(o)}},FormEngineValidation.validateField=function(e,n){const t=e instanceof $?e.get(0):e;if(n=n||t.value||"",void 0===t.dataset.formengineValidationRules)return n;const a=JSON.parse(t.dataset.formengineValidationRules);let i=!1,o=0;const r=n;let l,s,m;Array.isArray(n)||(n=n.trimStart());for(const e of a){if(i)break;switch(e.type){case"required":""===n&&(i=!0,t.closest(FormEngineValidation.markerSelector).classList.add(FormEngineValidation.errorClass));break;case"range":if(""!==n){if((e.minItems||e.maxItems)&&(l=$(document).find(selector`[name="${t.dataset.relatedfieldname}"]`),o=l.length?FormEngineValidation.trimExplode(",",l.val()).length:parseInt(t.value,10),void 0!==e.minItems&&(s=1*e.minItems,!isNaN(s)&&o<s&&(i=!0)),void 0!==e.maxItems&&(m=1*e.maxItems,!isNaN(m)&&o>m&&(i=!0))),void 0!==e.lower){const t=1*e.lower;!isNaN(t)&&parseInt(n,10)<t&&(i=!0)}if(void 0!==e.upper){const t=1*e.upper;!isNaN(t)&&parseInt(n,10)>t&&(i=!0)}}break;case"select":case"category":(e.minItems||e.maxItems)&&(l=$(document).find(selector`[name="${t.dataset.relatedfieldname}"]`),o=l.length?FormEngineValidation.trimExplode(",",l.val()).length:t instanceof HTMLSelectElement?t.querySelectorAll("option:checked").length:t.querySelectorAll("input[value]:checked").length,void 0!==e.minItems&&(s=1*e.minItems,!isNaN(s)&&o<s&&(i=!0)),void 0!==e.maxItems&&(m=1*e.maxItems,!isNaN(m)&&o>m&&(i=!0)));break;case"group":case"folder":case"inline":(e.minItems||e.maxItems)&&(o=FormEngineValidation.trimExplode(",",t.value).length,void 0!==e.minItems&&(s=1*e.minItems,!isNaN(s)&&o<s&&(i=!0)),void 0!==e.maxItems&&(m=1*e.maxItems,!isNaN(m)&&o>m&&(i=!0)));break;case"min":(t instanceof HTMLInputElement||t instanceof HTMLTextAreaElement)&&t.value.length>0&&t.value.length<t.minLength&&(i=!0)}}const d=!i,c=t.closest(FormEngineValidation.markerSelector);return null!==c&&c.classList.toggle(FormEngineValidation.errorClass,!d),FormEngineValidation.markParentTab($(t),d),$(document).trigger("t3-formengine-postfieldvalidation"),r},FormEngineValidation.processValue=function(e,n,t){let a="",i="",o=0,r=n;switch(e){case"alpha":case"num":case"alphanum":case"alphanum_x":for(a="",o=0;o<n.length;o++){const t=n.substr(o,1);let i="_"===t||"-"===t,r=t>="a"&&t<="z"||t>="A"&&t<="Z",l=t>="0"&&t<="9";switch(e){case"alphanum":i=!1;break;case"alpha":l=!1,i=!1;break;case"num":r=!1,i=!1}(r||l||i)&&(a+=t)}a!==n&&(r=a);break;case"is_in":if(t.is_in){i=""+n,t.is_in=t.is_in.replace(/[-[\]{}()*+?.,\\^$|#\s]/g,"\\$&");const e=new RegExp("[^"+t.is_in+"]+","g");a=i.replace(e,"")}else a=i;r=a;break;case"nospace":r=(""+n).replace(/ /g,"");break;case"md5":""!==n&&(r=Md5.hash(n));break;case"upper":r=n.toUpperCase();break;case"lower":r=n.toLowerCase();break;case"integer":""!==n&&(r=FormEngineValidation.parseInt(n));break;case"decimal":""!==n&&(r=FormEngineValidation.parseDouble(n));break;case"trim":r=String(n).trim();break;case"datetime":""!==n&&(r=FormEngineValidation.parseDateTime(n));break;case"date":""!==n&&(r=FormEngineValidation.parseDate(n));break;case"time":case"timesec":""!==n&&(r=FormEngineValidation.parseTime(n,e));break;case"year":""!==n&&(r=FormEngineValidation.parseYear(n));break;case"null":case"password":break;default:customEvaluations.has(e)?r=customEvaluations.get(e).call(null,n):"object"==typeof TBE_EDITOR&&void 0!==TBE_EDITOR.customEvalFunctions&&"function"==typeof TBE_EDITOR.customEvalFunctions[e]&&(r=TBE_EDITOR.customEvalFunctions[e](n))}return r},FormEngineValidation.validate=function(e){(void 0===e||e instanceof Document)&&$(document).find(FormEngineValidation.markerSelector+", .t3js-tabmenu-item").removeClass(FormEngineValidation.errorClass).removeClass("has-validation-error");const n=e||document;for(const e of n.querySelectorAll(FormEngineValidation.rulesSelector)){const n=$(e);if(!n.closest(".t3js-flex-section-deleted, .t3js-inline-record-deleted, .t3js-file-reference-deleted").length){let e=!1;const t=n.val(),a=FormEngineValidation.validateField(n,t);if(Array.isArray(a)&&Array.isArray(t)){if(a.length!==t.length)e=!0;else for(let n=0;n<a.length;n++)if(a[n]!==t[n]){e=!0;break}}else a.length&&t!==a&&(e=!0);e&&(n.prop("disabled")&&n.data("enableOnModification")&&n.prop("disabled",!1),n.val(a))}}},FormEngineValidation.markFieldAsChanged=function(e){if(e instanceof $&&(e=e.get(0)),!(e instanceof HTMLElement))return;const n=e.closest(".t3js-formengine-palette-field");null!==n&&n.classList.add("has-change")},FormEngineValidation.trimExplode=function(e,n){const t=[],a=n.split(e);for(let e=0;e<a.length;e++){const n=a[e].trim();n.length>0&&t.push(n)}return t},FormEngineValidation.parseInt=function(e){if(!e)return 0;const n=parseInt(""+e,10);return isNaN(n)?0:n},FormEngineValidation.parseDouble=function(e,n=2){let t=""+e;t=t.replace(/[^0-9,.-]/g,"");const a=t.startsWith("-");t=t.replace(/-/g,""),t=t.replace(/,/g,"."),-1===t.indexOf(".")&&(t+=".0");const i=t.split("."),o=i.pop();let r=Number(i.join("")+"."+o);return a&&(r*=-1),t=r.toFixed(n),t},FormEngineValidation.parseDateTime=function(e){const n=e.indexOf(" ");if(-1!==n){const t=FormEngineValidation.parseDate(e.substring(n+1));FormEngineValidation.lastTime=t+FormEngineValidation.parseTime(e.substring(0,n),"time")}else FormEngineValidation.lastTime=FormEngineValidation.parseDate(e);return FormEngineValidation.lastTime},FormEngineValidation.parseDate=function(e){return FormEngineValidation.lastDate=DateTime.fromFormat(e,"dd-MM-yyyy",{zone:"utc"}).toUnixInteger(),FormEngineValidation.lastDate},FormEngineValidation.parseTime=function(e,n){const t="timesec"===n?"HH:mm:ss":"HH:mm";return FormEngineValidation.lastTime=DateTime.fromFormat(e,t,{zone:"utc"}).set({year:1970,month:1,day:1}).toUnixInteger(),FormEngineValidation.lastTime<0&&(FormEngineValidation.lastTime+=86400),FormEngineValidation.lastTime},FormEngineValidation.parseYear=function(e){let n=parseInt(e,10);return isNaN(n)&&(n=FormEngineValidation.getYear(new Date)),FormEngineValidation.lastYear=n,FormEngineValidation.lastYear},FormEngineValidation.getYear=function(e){return null===e?null:e.getUTCFullYear()},FormEngineValidation.getDate=function(e){const n=new Date(FormEngineValidation.getYear(e),e.getUTCMonth(),e.getUTCDate());return FormEngineValidation.getTimestamp(n)},FormEngineValidation.pol=function(foreign,value){return eval(("-"==foreign?"-":"")+value)},FormEngineValidation.getTimestamp=function(e){return Date.parse(e instanceof Date?e.toISOString():e)/1e3},FormEngineValidation.getTime=function(e){return 60*e.getUTCHours()*60+60*e.getUTCMinutes()+FormEngineValidation.getSecs(e)},FormEngineValidation.getSecs=function(e){return e.getUTCSeconds()},FormEngineValidation.getTimeSecs=function(e){return 60*e.getHours()*60+60*e.getMinutes()+e.getSeconds()},FormEngineValidation.markParentTab=function(e,n){e.parents(".tab-pane").each((function(e,t){const a=$(t);n&&(n=0===a.find(".has-error").length);const i=a.attr("id");$(document).find('a[href="#'+i+'"]').closest(".t3js-tabmenu-item").toggleClass("has-validation-error",!n)}))},FormEngineValidation.registerSubmitCallback=function(){DocumentSaveActions.getInstance().addPreSubmitCallback((function(e){if($("."+FormEngineValidation.errorClass).length>0){const n=Modal.confirm(TYPO3.lang.alert||"Alert",TYPO3.lang["FormEngine.fieldsMissing"],Severity.error,[{text:TYPO3.lang["button.ok"]||"OK",active:!0,btnClass:"btn-default",name:"ok"}]);n.addEventListener("button.clicked",(()=>n.hideModal())),e.stopImmediatePropagation()}}))},FormEngineValidation}());