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
define(["require","exports","../../Utility/MessageUtility","./../InlineRelation/AjaxDispatcher","TYPO3/CMS/Core/DocumentService","nprogress","Sortable","TYPO3/CMS/Backend/FormEngine","TYPO3/CMS/Backend/FormEngineValidation","../../Icons","../../InfoWindow","../../Modal","../../Notification","TYPO3/CMS/Core/Event/RegularEvent","../../Severity","../../Utility"],(function(e,t,n,i,o,a,r,s,l,c,d,u,h,p,g,m){"use strict";var f,b,v,j;!function(e){e.toggleSelector='[data-toggle="formengine-inline"]',e.controlSectionSelector=".t3js-formengine-irre-control",e.createNewRecordButtonSelector=".t3js-create-new-button",e.createNewRecordBySelectorSelector=".t3js-create-new-selector",e.deleteRecordButtonSelector=".t3js-editform-delete-inline-record",e.enableDisableRecordButtonSelector=".t3js-toggle-visibility-button",e.infoWindowButton='[data-action="infowindow"]',e.synchronizeLocalizeRecordButtonSelector=".t3js-synchronizelocalize-button",e.uniqueValueSelectors="select.t3js-inline-unique",e.revertUniqueness=".t3js-revert-unique",e.controlContainer=".t3js-inline-controls"}(f||(f={})),function(e){e.new="inlineIsNewRecord",e.visible="panel-visible",e.collapsed="panel-collapsed",e.notLoaded="t3js-not-loaded"}(b||(b={})),function(e){e.structureSeparator="-"}(v||(v={})),function(e){e.DOWN="down",e.UP="up"}(j||(j={}));class S{constructor(e){this.container=null,this.ajaxDispatcher=null,this.appearance=null,this.requestQueue={},this.progessQueue={},this.noTitleString=TYPO3.lang?TYPO3.lang["FormEngine.noRecordTitle"]:"[No title]",this.handlePostMessage=e=>{if(!n.MessageUtility.verifyOrigin(e.origin))throw"Denied message sent by "+e.origin;if("typo3:foreignRelation:insert"===e.data.actionName){if(void 0===e.data.objectGroup)throw"No object group defined for message";if(e.data.objectGroup!==this.container.dataset.objectGroup)return;if(this.isUniqueElementUsed(parseInt(e.data.uid,10),e.data.table))return void h.error("There is already a relation to the selected element");this.importRecord([e.data.objectGroup,e.data.uid]).then(()=>{if(e.source){const t={actionName:"typo3:foreignRelation:inserted",objectGroup:e.data.objectId,table:e.data.table,uid:e.data.uid};n.MessageUtility.send(t,e.source)}})}else console.warn(`Unhandled action "${e.data.actionName}"`)},o.ready().then(t=>{this.container=t.getElementById(e),this.ajaxDispatcher=new i.AjaxDispatcher(this.container.dataset.objectGroup),this.registerEvents()})}static getInlineRecordContainer(e){return document.querySelector('[data-object-id="'+e+'"]')}static toggleElement(e){const t=S.getInlineRecordContainer(e);t.classList.contains(b.collapsed)?(t.classList.remove(b.collapsed),t.classList.add(b.visible)):(t.classList.remove(b.visible),t.classList.add(b.collapsed))}static isNewRecord(e){return S.getInlineRecordContainer(e).classList.contains(b.new)}static updateExpandedCollapsedStateLocally(e,t){const n=S.getInlineRecordContainer(e),i="uc[inlineView]["+n.dataset.topmostParentTable+"]["+n.dataset.topmostParentUid+"]"+n.dataset.fieldName,o=document.getElementsByName(i);o.length&&(o[0].value=t?"1":"0")}static getValuesFromHashMap(e){return Object.keys(e).map(t=>e[t])}static selectOptionValueExists(e,t){return null!==e.querySelector('option[value="'+t+'"]')}static removeSelectOptionByValue(e,t){const n=e.querySelector('option[value="'+t+'"]');null!==n&&n.remove()}static reAddSelectOption(e,t,n){if(S.selectOptionValueExists(e,t))return;const i=e.querySelectorAll("option");let o=-1;for(let e of Object.keys(n.possible)){if(e===t)break;for(let t=0;t<i.length;++t){if(i[t].value===e){o=t;break}}}-1===o?o=0:o<i.length&&o++;const a=document.createElement("option");a.text=n.possible[t],a.value=t,e.insertBefore(a,e.options[o])}registerEvents(){if(this.registerInfoButton(),this.registerSort(),this.registerCreateRecordButton(),this.registerEnableDisableButton(),this.registerDeleteButton(),this.registerSynchronizeLocalize(),this.registerRevertUniquenessAction(),this.registerToggle(),this.registerCreateRecordBySelector(),this.registerUniqueSelectFieldChanged(),new p("message",this.handlePostMessage).bindTo(window),this.getAppearance().useSortable){const e=document.getElementById(this.container.getAttribute("id")+"_records");new r(e,{group:e.getAttribute("id"),handle:".sortableHandle",onSort:()=>{this.updateSorting()}})}}registerToggle(){const e=this;new p("click",(function(t){t.preventDefault(),t.stopImmediatePropagation(),e.loadRecordDetails(this.closest(f.toggleSelector).parentElement.dataset.objectId)})).delegateTo(this.container,`${f.toggleSelector} .form-irre-header-cell:not(${f.controlSectionSelector}`)}registerSort(){const e=this;new p("click",(function(t){t.preventDefault(),t.stopImmediatePropagation(),e.changeSortingByButton(this.closest("[data-object-id]").dataset.objectId,this.dataset.direction)})).delegateTo(this.container,f.controlSectionSelector+' [data-action="sort"]')}registerCreateRecordButton(){const e=this;new p("click",(function(t){var n,i;if(t.preventDefault(),t.stopImmediatePropagation(),e.isBelowMax()){let t=e.container.dataset.objectGroup;void 0!==this.dataset.recordUid&&(t+=v.structureSeparator+this.dataset.recordUid),e.importRecord([t,null===(n=e.container.querySelector(f.createNewRecordBySelectorSelector))||void 0===n?void 0:n.value],null!==(i=this.dataset.recordUid)&&void 0!==i?i:null)}})).delegateTo(this.container,f.createNewRecordButtonSelector)}registerCreateRecordBySelector(){const e=this;new p("change",(function(t){t.preventDefault(),t.stopImmediatePropagation();const n=this.options[this.selectedIndex].getAttribute("value");e.importRecord([e.container.dataset.objectGroup,n])})).delegateTo(this.container,f.createNewRecordBySelectorSelector)}createRecord(e,t,n=null,i=null){let o=this.container.dataset.objectGroup;null!==n&&(o+=v.structureSeparator+n),null!==n?(S.getInlineRecordContainer(o).insertAdjacentHTML("afterend",t),this.memorizeAddRecord(e,n,i)):(document.getElementById(this.container.getAttribute("id")+"_records").insertAdjacentHTML("beforeend",t),this.memorizeAddRecord(e,null,i))}async importRecord(e,t){return this.ajaxDispatcher.send(this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint("record_inline_create")),e).then(async e=>{this.isBelowMax()&&(this.createRecord(e.compilerInput.uid,e.data,void 0!==t?t:null,void 0!==e.compilerInput.childChildUid?e.compilerInput.childChildUid:null),s.reinitialize(),s.Validation.initializeInputFields(),s.Validation.validate())})}registerEnableDisableButton(){new p("click",(e,t)=>{e.preventDefault(),e.stopImmediatePropagation();const n=t.closest("[data-object-id]").dataset.objectId,i=S.getInlineRecordContainer(n),o="data"+i.dataset.fieldName+"["+t.dataset.hiddenField+"]",a=document.querySelector('[data-formengine-input-name="'+o+'"'),r=document.querySelector('[name="'+o+'"');null!==a&&null!==r&&(a.checked=!a.checked,r.value=a.checked?"1":"0",TBE_EDITOR.fieldChanged(this.container.dataset.localTable,this.container.dataset.uid,this.container.dataset.localField,o));const s="t3-form-field-container-inline-hidden";let l;i.classList.contains(s)?(l="actions-edit-hide",i.classList.remove(s)):(l="actions-edit-unhide",i.classList.add(s)),c.getIcon(l,c.sizes.small).then(e=>{t.replaceChild(document.createRange().createContextualFragment(e),t.querySelector(".t3js-icon"))})}).delegateTo(this.container,f.enableDisableRecordButtonSelector)}registerInfoButton(){new p("click",(function(e){e.preventDefault(),e.stopImmediatePropagation(),d.showItem(this.dataset.infoTable,this.dataset.infoUid)})).delegateTo(this.container,f.infoWindowButton)}registerDeleteButton(){const e=this;new p("click",(function(t){t.preventDefault(),t.stopImmediatePropagation();const n=TYPO3.lang["label.confirm.delete_record.title"]||"Delete this record?",i=TYPO3.lang["label.confirm.delete_record.content"]||"Are you sure you want to delete this record?";u.confirm(n,i,g.warning,[{text:TYPO3.lang["buttons.confirm.delete_record.no"]||"Cancel",active:!0,btnClass:"btn-default",name:"no"},{text:TYPO3.lang["buttons.confirm.delete_record.yes"]||"Yes, delete this record",btnClass:"btn-warning",name:"yes"}]).on("button.clicked",t=>{if("yes"===t.target.name){const t=this.closest("[data-object-id]").dataset.objectId;e.deleteRecord(t)}u.dismiss()})})).delegateTo(this.container,f.deleteRecordButtonSelector)}registerSynchronizeLocalize(){const e=this;new p("click",(function(t){t.preventDefault(),t.stopImmediatePropagation(),e.ajaxDispatcher.send(e.ajaxDispatcher.newRequest(e.ajaxDispatcher.getEndpoint("record_inline_synchronizelocalize")),[e.container.dataset.objectGroup,this.dataset.type]).then(async t=>{document.getElementById(e.container.getAttribute("id")+"_records").insertAdjacentHTML("beforeend",t.data);const n=e.container.dataset.objectGroup+v.structureSeparator;for(let i of t.compilerInput.delete)e.deleteRecord(n+i,!0);for(let i of Object.values(t.compilerInput.localize)){if(void 0!==i.remove){const e=S.getInlineRecordContainer(n+i.remove);e.parentElement.removeChild(e)}e.memorizeAddRecord(i.uid,null,i.selectedValue)}})})).delegateTo(this.container,f.synchronizeLocalizeRecordButtonSelector)}registerUniqueSelectFieldChanged(){const e=this;new p("change",(function(t){t.preventDefault(),t.stopImmediatePropagation();const n=this.closest("[data-object-id]");if(null!==n){const t=n.dataset.objectId,i=n.dataset.objectUid;e.handleChangedField(this,t);const o=e.getFormFieldForElements();if(null===o)return;e.updateUnique(this,o,i)}})).delegateTo(this.container,f.uniqueValueSelectors)}registerRevertUniquenessAction(){const e=this;new p("click",(function(t){t.preventDefault(),t.stopImmediatePropagation(),e.revertUnique(this.dataset.uid)})).delegateTo(this.container,f.revertUniqueness)}loadRecordDetails(e){const t=document.getElementById(e+"_fields"),n=S.getInlineRecordContainer(e),i=void 0!==this.requestQueue[e];if(null!==t&&!n.classList.contains(b.notLoaded))this.collapseExpandRecord(e);else{const o=this.getProgress(e,n.dataset.objectIdHash);if(i)this.requestQueue[e].abort(),delete this.requestQueue[e],delete this.progessQueue[e],o.done();else{const i=this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint("record_inline_details"));this.ajaxDispatcher.send(i,[e]).then(async i=>{if(delete this.requestQueue[e],delete this.progessQueue[e],n.classList.remove(b.notLoaded),t.innerHTML=i.data,this.collapseExpandRecord(e),o.done(),s.reinitialize(),s.Validation.initializeInputFields(),s.Validation.validate(),this.hasObjectGroupDefinedUniqueConstraints()){const t=S.getInlineRecordContainer(e);this.removeUsed(t)}}),this.requestQueue[e]=i,o.start()}}}collapseExpandRecord(e){const t=S.getInlineRecordContainer(e),n=!0===this.getAppearance().expandSingle,i=t.classList.contains(b.collapsed);let o=[];const a=[];n&&i&&(o=this.collapseAllRecords(t.dataset.objectUid)),S.toggleElement(e),S.isNewRecord(e)?S.updateExpandedCollapsedStateLocally(e,i):i?a.push(t.dataset.objectUid):i||o.push(t.dataset.objectUid),this.ajaxDispatcher.send(this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint("record_inline_expandcollapse")),[e,a.join(","),o.join(",")])}memorizeAddRecord(e,t=null,n=null){const i=this.getFormFieldForElements();if(null===i)return;let o=m.trimExplode(",",i.value);if(t){const n=[];for(let i=0;i<o.length;i++)o[i].length&&n.push(o[i]),t===o[i]&&n.push(e);o=n}else o.push(e);i.value=o.join(","),i.classList.add("has-change"),document.dispatchEvent(new Event("change")),this.redrawSortingButtons(this.container.dataset.objectGroup,o),this.setUnique(e,n),this.isBelowMax()||this.toggleContainerControls(!1),TBE_EDITOR.fieldChanged(this.container.dataset.localTable,this.container.dataset.uid,this.container.dataset.localField,i)}memorizeRemoveRecord(e){const t=this.getFormFieldForElements();if(null===t)return[];let n=m.trimExplode(",",t.value);const i=n.indexOf(e);return i>-1&&(delete n[i],t.value=n.join(","),t.classList.add("has-change"),document.dispatchEvent(new Event("change")),this.redrawSortingButtons(this.container.dataset.objectGroup,n)),n}changeSortingByButton(e,t){const n=S.getInlineRecordContainer(e),i=n.dataset.objectUid,o=document.getElementById(this.container.getAttribute("id")+"_records"),a=Array.from(o.children).map(e=>e.dataset.objectUid);let r=a.indexOf(i),s=!1;if(t===j.UP&&r>0?(a[r]=a[r-1],a[r-1]=i,s=!0):t===j.DOWN&&r<a.length-1&&(a[r]=a[r+1],a[r+1]=i,s=!0),s){const e=this.container.dataset.objectGroup+v.structureSeparator,i=t===j.UP?1:0;n.parentElement.insertBefore(S.getInlineRecordContainer(e+a[r-i]),S.getInlineRecordContainer(e+a[r+1-i])),this.updateSorting()}}updateSorting(){const e=this.getFormFieldForElements();if(null===e)return;const t=document.getElementById(this.container.getAttribute("id")+"_records"),n=Array.from(t.querySelectorAll('[data-placeholder-record="0"]')).map(e=>e.dataset.objectUid);e.value=n.join(","),e.classList.add("has-change"),document.dispatchEvent(new Event("inline:sorting-changed")),document.dispatchEvent(new Event("change")),this.redrawSortingButtons(this.container.dataset.objectGroup,n)}deleteRecord(e,t=!1){const n=S.getInlineRecordContainer(e),i=n.dataset.objectUid;if(n.classList.add("t3js-inline-record-deleted"),!S.isNewRecord(e)&&!t){const e=this.container.querySelector('[name="cmd'+n.dataset.fieldName+'[delete]"]');e.removeAttribute("disabled"),n.parentElement.insertAdjacentElement("afterbegin",e)}new p("transitionend",()=>{n.parentElement.removeChild(n),l.validate()}).bindTo(n),this.revertUnique(i),this.memorizeRemoveRecord(i),n.classList.add("form-irre-object--deleted"),this.isBelowMax()&&this.toggleContainerControls(!0)}toggleContainerControls(e){this.container.querySelector(f.controlContainer).querySelectorAll("a").forEach(t=>{t.style.display=e?null:"none"})}getProgress(e,t){const n="#"+t+"_header";let i;return void 0!==this.progessQueue[e]?i=this.progessQueue[e]:(i=a,i.configure({parent:n,showSpinner:!1}),this.progessQueue[e]=i),i}collapseAllRecords(e){const t=this.getFormFieldForElements(),n=[];if(null!==t){const i=m.trimExplode(",",t.value);for(let t of i){if(t===e)continue;const i=this.container.dataset.objectGroup+v.structureSeparator+t,o=S.getInlineRecordContainer(i);o.classList.contains(b.visible)&&(o.classList.remove(b.visible),o.classList.add(b.collapsed),S.isNewRecord(i)?S.updateExpandedCollapsedStateLocally(i,!1):n.push(t))}}return n}getFormFieldForElements(){const e=document.getElementsByName(this.container.dataset.formField);return e.length>0?e[0]:null}redrawSortingButtons(e,t=[]){if(0===t.length){const e=this.getFormFieldForElements();null!==e&&(t=m.trimExplode(",",e.value))}0!==t.length&&t.forEach((n,i)=>{const o=S.getInlineRecordContainer(e+v.structureSeparator+n).dataset.objectIdHash+"_header",a=document.getElementById(o),r=a.querySelector('[data-action="sort"][data-direction="'+j.UP+'"]');if(null!==r){let e="actions-move-up";0===i?(r.classList.add("disabled"),e="empty-empty"):r.classList.remove("disabled"),c.getIcon(e,c.sizes.small).then(e=>{r.replaceChild(document.createRange().createContextualFragment(e),r.querySelector(".t3js-icon"))})}const s=a.querySelector('[data-action="sort"][data-direction="'+j.DOWN+'"]');if(null!==s){let e="actions-move-down";i===t.length-1?(s.classList.add("disabled"),e="empty-empty"):s.classList.remove("disabled"),c.getIcon(e,c.sizes.small).then(e=>{s.replaceChild(document.createRange().createContextualFragment(e),s.querySelector(".t3js-icon"))})}})}isBelowMax(){const e=this.getFormFieldForElements();if(null===e)return!0;if(void 0!==TYPO3.settings.FormEngineInline.config[this.container.dataset.objectGroup]){if(m.trimExplode(",",e.value).length>=TYPO3.settings.FormEngineInline.config[this.container.dataset.objectGroup].max)return!1;if(this.hasObjectGroupDefinedUniqueConstraints()){const e=TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup];if(e.used.length>=e.max&&e.max>=0)return!1}}return!0}isUniqueElementUsed(e,t){if(!this.hasObjectGroupDefinedUniqueConstraints())return!1;const n=TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup],i=S.getValuesFromHashMap(n.used);if("select"===n.type&&-1!==i.indexOf(e))return!0;if("groupdb"===n.type)for(let n=i.length-1;n>=0;n--)if(i[n].table===t&&i[n].uid===e)return!0;return!1}removeUsed(e){if(!this.hasObjectGroupDefinedUniqueConstraints())return;const t=TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup];if("select"!==t.type)return;let n=e.querySelector('[name="data['+t.table+"]["+e.dataset.objectUid+"]["+t.field+']"]');const i=S.getValuesFromHashMap(t.used);if(null!==n){const e=n.options[n.selectedIndex].value;for(let t of i)t!==e&&S.removeSelectOptionByValue(n,t)}}setUnique(e,t){if(!this.hasObjectGroupDefinedUniqueConstraints())return;const n=document.getElementById(this.container.dataset.objectGroup+"_selector"),i=TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup];if("select"===i.type){if(!i.selector||-1!==i.max){const o=this.getFormFieldForElements(),a=this.container.dataset.objectGroup+v.structureSeparator+e;let r=S.getInlineRecordContainer(a).querySelector('[name="data['+i.table+"]["+e+"]["+i.field+']"]');const s=S.getValuesFromHashMap(i.used);if(null!==n){if(null!==r){for(let e of s)S.removeSelectOptionByValue(r,e);i.selector||(t=r.options[0].value,r.options[0].selected=!0,this.updateUnique(r,o,e),this.handleChangedField(r,this.container.dataset.objectGroup+"["+e+"]"))}for(let e of s)S.removeSelectOptionByValue(r,e);void 0!==i.used.length&&(i.used={}),i.used[e]={table:i.elTable,uid:t}}if(null!==o&&S.selectOptionValueExists(n,t)){const n=m.trimExplode(",",o.value);for(let o of n)r=document.querySelector('[name="data['+i.table+"]["+o+"]["+i.field+']"]'),null!==r&&o!==e&&S.removeSelectOptionByValue(r,t)}}}else"groupdb"===i.type&&(i.used[e]={table:i.elTable,uid:t});"select"===i.selector&&S.selectOptionValueExists(n,t)&&(S.removeSelectOptionByValue(n,t),i.used[e]={table:i.elTable,uid:t})}updateUnique(e,t,n){if(!this.hasObjectGroupDefinedUniqueConstraints())return;const i=TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup],o=i.used[n];if("select"===i.selector){const t=document.getElementById(this.container.dataset.objectGroup+"_selector");S.removeSelectOptionByValue(t,e.value),void 0!==o&&S.reAddSelectOption(t,o,i)}if(i.selector&&-1===i.max)return;if(!i||null===t)return;const a=m.trimExplode(",",t.value);let r;for(let t of a)r=document.querySelector('[name="data['+i.table+"]["+t+"]["+i.field+']"]'),null!==r&&r!==e&&(S.removeSelectOptionByValue(r,e.value),void 0!==o&&S.reAddSelectOption(r,o,i));i.used[n]=e.value}revertUnique(e){if(!this.hasObjectGroupDefinedUniqueConstraints())return;const t=TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup],n=this.container.dataset.objectGroup+v.structureSeparator+e,i=S.getInlineRecordContainer(n);let o=i.querySelector('[name="data['+t.table+"]["+i.dataset.objectUid+"]["+t.field+']"]');if("select"===t.type){let n;if(null!==o)n=o.value;else{if(""===i.dataset.tableUniqueOriginalValue)return;n=i.dataset.tableUniqueOriginalValue}if("select"===t.selector&&!isNaN(parseInt(n,10))){const e=document.getElementById(this.container.dataset.objectGroup+"_selector");S.reAddSelectOption(e,n,t)}if(t.selector&&-1===t.max)return;const a=this.getFormFieldForElements();if(null===a)return;const r=m.trimExplode(",",a.value);let s;for(let e=0;e<r.length;e++)s=document.querySelector('[name="data['+t.table+"]["+r[e]+"]["+t.field+']"]'),null!==s&&S.reAddSelectOption(s,n,t);delete t.used[e]}else"groupdb"===t.type&&delete t.used[e]}hasObjectGroupDefinedUniqueConstraints(){return void 0!==TYPO3.settings.FormEngineInline.unique&&void 0!==TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup]}handleChangedField(e,t){let n;n=e instanceof HTMLSelectElement?e.options[e.selectedIndex].text:e.value,document.getElementById(t+"_label").textContent=n.length?n:this.noTitleString}getAppearance(){if(null===this.appearance&&(this.appearance={},"string"==typeof this.container.dataset.appearance))try{this.appearance=JSON.parse(this.container.dataset.appearance)}catch(e){console.error(e)}return this.appearance}}return S}));