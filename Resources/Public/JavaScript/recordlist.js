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
import Icons from"@typo3/backend/icons.js";import PersistentStorage from"@typo3/backend/storage/persistent.js";import RegularEvent from"@typo3/core/event/regular-event.js";import DocumentService from"@typo3/core/document-service.js";import{MultiRecordSelectionSelectors}from"@typo3/backend/multi-record-selection.js";import{selector}from"@typo3/core/literals.js";class Recordlist{constructor(){this.identifier={entity:".t3js-entity",toggle:".t3js-toggle-recordlist",localize:".t3js-action-localize",editMultiple:".t3js-record-edit-multiple",icons:{collapse:"actions-view-list-collapse",expand:"actions-view-list-expand"}},this.toggleClick=(e,t)=>{e.preventDefault();const o=t.dataset.table,i=document.querySelector(t.dataset.bsTarget),l="expanded"===i.dataset.state,a=t.querySelector(".t3js-icon"),n=l?this.identifier.icons.expand:this.identifier.icons.collapse;Icons.getIcon(n,Icons.sizes.small).then((e=>{a.replaceWith(document.createRange().createContextualFragment(e))}));let r={};PersistentStorage.isset("moduleData.web_list.collapsedTables")&&(r=PersistentStorage.get("moduleData.web_list.collapsedTables"));const s={};s[o]=l?1:0,r=Object.assign(r,s),PersistentStorage.set("moduleData.web_list.collapsedTables",r).then((()=>{i.dataset.state=l?"collapsed":"expanded"}))},this.onEditMultiple=(e,t)=>{e.preventDefault();let o="",i="",l=[];const a=[];if("multiRecordSelection:action:edit"===e.type){const t=e.detail,n=t.configuration;if(i=n.returnUrl||"",l=n.columnsOnly||[],o=n.tableName||"",""===o)return;t.checkboxes.forEach((e=>{const t=e.closest(MultiRecordSelectionSelectors.elementSelector);null!==t&&t.dataset[n.idField]&&a.push(t.dataset[n.idField])}))}else{const e=t.closest("[data-table]");if(null===e)return;if(o=e.dataset.table||"",""===o)return;i=t.dataset.returnUrl||"",l=JSON.parse(t.dataset.columnsOnly||"{}");const n=e.querySelectorAll(this.identifier.entity+'[data-uid][data-table="'+o+'"] td.col-checkbox input[type="checkbox"]:checked');if(n.length)n.forEach((e=>{a.push(e.closest(this.identifier.entity+selector`[data-uid][data-table="${o}"]`).dataset.uid)}));else{const t=e.querySelectorAll(this.identifier.entity+selector`[data-uid][data-table="${o}"]`);if(!t.length)return;t.forEach((e=>{a.push(e.dataset.uid)}))}}if(!a.length)return;let n=top.TYPO3.settings.FormEngine.moduleUrl+"&edit["+o+"]["+a.join(",")+"]=edit&returnUrl="+Recordlist.getReturnUrl(i);l.length>0&&(n+=l.map(((e,t)=>"&columnsOnly["+o+"]["+t+"]="+e)).join("")),window.location.href=n},this.disableButton=(e,t)=>{t.setAttribute("disabled","disabled"),t.classList.add("disabled")},this.deleteRow=e=>{const t=document.querySelector(`table[data-table="${e.table}"]`),o=t.querySelector(`tr[data-uid="${e.uid}"]`),i=t.closest(".panel"),l=i.querySelector(".panel-heading");if([o,...t.querySelectorAll(`[data-l10nparent="${e.uid}"]`)].forEach((e=>{e?.remove()})),null===t.querySelector("tbody tr")&&i.remove(),"0"===o.dataset.l10nparent||""===o.dataset.l10nparent){const e=Number(l.querySelector(".t3js-table-total-items").textContent),t=l.querySelector(".t3js-table-total-items");null!==t&&(t.textContent=String(e-1))}"pages"===e.table&&top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))},this.registerPaginationEvents=()=>{document.querySelectorAll(".t3js-recordlist-paging").forEach((e=>{e.addEventListener("keyup",(t=>{t.preventDefault();let o=Number(e.value);const i=Number(e.min),l=Number(e.max);if(i&&o<i&&(o=i),l&&o>l&&(o=l),e.value=o.toString(10),"Enter"===t.key&&o!==Number(e.dataset.currentpage)){const t=e.closest('form[name^="list-table-form-"]'),i=new URL(t.action,window.origin);i.searchParams.set("pointer",o.toString()),window.location.href=i.toString()}}))}))},new RegularEvent("click",this.toggleClick).delegateTo(document,this.identifier.toggle),new RegularEvent("click",this.onEditMultiple).delegateTo(document,this.identifier.editMultiple),new RegularEvent("click",this.disableButton).delegateTo(document,this.identifier.localize),DocumentService.ready().then((()=>{this.registerPaginationEvents()})),new RegularEvent("typo3:datahandler:process",this.handleDataHandlerResult.bind(this)).bindTo(document),new RegularEvent("multiRecordSelection:action:edit",this.onEditMultiple).bindTo(document),new RegularEvent("multiRecordSelection:action:copyMarked",(e=>{Recordlist.submitClipboardFormWithCommand("copyMarked",e.target)})).bindTo(document),new RegularEvent("multiRecordSelection:action:removeMarked",(e=>{Recordlist.submitClipboardFormWithCommand("removeMarked",e.target)})).bindTo(document)}static submitClipboardFormWithCommand(e,t){const o=t.closest("form");if(!o)return;const i=o.querySelector('input[name="cmd"]');i&&(i.value=e,o.submit())}static getReturnUrl(e){return""===e&&(e=top.list_frame.document.location.pathname+top.list_frame.document.location.search),encodeURIComponent(e)}handleDataHandlerResult(e){const t=e.detail.payload;t.hasErrors||"datahandler"!==t.component&&"delete"===t.action&&this.deleteRow(t)}}export default new Recordlist;