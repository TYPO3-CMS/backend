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
import Icons from"@typo3/backend/icons.js";import PersistentStorage from"@typo3/backend/storage/persistent.js";import RegularEvent from"@typo3/core/event/regular-event.js";import DocumentService from"@typo3/core/document-service.js";import{MultiRecordSelectionSelectors}from"@typo3/backend/multi-record-selection.js";import{selector}from"@typo3/core/literals.js";import AjaxDataHandler from"@typo3/backend/ajax-data-handler.js";import Modal from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";class Recordlist{constructor(){this.identifier={entity:".t3js-entity",toggle:".t3js-toggle-recordlist",localize:".t3js-action-localize",hide:'button[data-datahandler-action="visibility"]',delete:".t3js-record-delete",editMultiple:".t3js-record-edit-multiple",icons:{collapse:"actions-view-list-collapse",expand:"actions-view-list-expand"}},this.toggleClick=(e,t)=>{e.preventDefault();const a=t.dataset.table,n=document.querySelector(t.dataset.bsTarget),l="expanded"===n.dataset.state,i=t.querySelector(".t3js-icon"),o=l?this.identifier.icons.expand:this.identifier.icons.collapse;Icons.getIcon(o,Icons.sizes.small).then((e=>{i.replaceWith(document.createRange().createContextualFragment(e))}));let r={};PersistentStorage.isset("moduleData.web_list.collapsedTables")&&(r=PersistentStorage.get("moduleData.web_list.collapsedTables"));const s={};s[a]=l?1:0,r=Object.assign(r,s),PersistentStorage.set("moduleData.web_list.collapsedTables",r).then((()=>{n.dataset.state=l?"collapsed":"expanded"}))},this.onEditMultiple=(e,t)=>{e.preventDefault();let a="",n="",l=[];const i=[];if("multiRecordSelection:action:edit"===e.type){const t=e.detail,o=t.configuration;if(n=o.returnUrl||"",l=o.columnsOnly||[],a=o.tableName||"",""===a)return;t.checkboxes.forEach((e=>{const t=e.closest(MultiRecordSelectionSelectors.elementSelector);null!==t&&t.dataset[o.idField]&&i.push(t.dataset[o.idField])}))}else{const e=t.closest("[data-table]");if(null===e)return;if(a=e.dataset.table||"",""===a)return;n=t.dataset.returnUrl||"",l=JSON.parse(t.dataset.columnsOnly||"{}");const o=e.querySelectorAll(this.identifier.entity+'[data-uid][data-table="'+a+'"] td.col-checkbox input[type="checkbox"]:checked');if(o.length)o.forEach((e=>{i.push(e.closest(this.identifier.entity+selector`[data-uid][data-table="${a}"]`).dataset.uid)}));else{const t=e.querySelectorAll(this.identifier.entity+selector`[data-uid][data-table="${a}"]`);if(!t.length)return;t.forEach((e=>{i.push(e.dataset.uid)}))}}if(!i.length)return;let o=top.TYPO3.settings.FormEngine.moduleUrl+"&edit["+a+"]["+i.join(",")+"]=edit&returnUrl="+Recordlist.getReturnUrl(n);l.length>0&&(o+=l.map(((e,t)=>"&columnsOnly["+a+"]["+t+"]="+e)).join("")),window.location.href=o},this.disableButton=(e,t)=>{t.setAttribute("disabled","disabled"),t.classList.add("disabled")},this.toggleVisibility=(e,t)=>{const a=t.closest("tr[data-uid]"),n=t.querySelector(".t3js-icon");Icons.getIcon("spinner-circle",Icons.sizes.small).then((e=>{n.replaceWith(document.createRange().createContextualFragment(e))}));const l="visible"===t.dataset.datahandlerStatus,i={table:t.dataset.datahandlerTable,uid:t.dataset.datahandlerUid,field:t.dataset.datahandlerField,visible:l,overlayIcon:l?t.dataset.datahandlerRecordHiddenOverlayIcon??"overlay-hidden":t.dataset.datahandlerRecordVisibleOverlayIcon??null},o={data:{[i.table]:{[i.uid]:{[i.field]:i.visible?t.dataset.datahandlerHiddenValue:t.dataset.datahandlerVisibleValue}}}};AjaxDataHandler.process(o).then((e=>{if(!e.hasErrors){i.visible=!i.visible,t.setAttribute("data-datahandler-status",i.visible?"visible":"hidden");const e=i.visible?t.dataset.datahandlerVisibleLabel:t.dataset.datahandlerHiddenLabel;t.setAttribute("title",e);const n=i.visible?t.dataset.datahandlerVisibleIcon:t.dataset.datahandlerHiddenIcon,l=t.querySelector(".t3js-icon");Icons.getIcon(n,Icons.sizes.small).then((e=>{l.replaceWith(document.createRange().createContextualFragment(e))}));const o=a.querySelector(".col-icon .t3js-icon");o.querySelector(".icon-overlay")?.remove(),Icons.getIcon("miscellaneous-placeholder",Icons.sizes.small,i.overlayIcon).then((e=>{const t=document.createRange().createContextualFragment(e);o.append(t.querySelector(".icon-overlay"))}));const r=new RegularEvent("animationend",(()=>{a.classList.remove("record-pulse"),r.release()}));r.bindTo(a),a.classList.add("record-pulse"),"pages"===i.table&&top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))}}))},this.deleteRecord=(e,t)=>{e.preventDefault();const a=Modal.confirm(t.dataset.title,t.dataset.message,SeverityEnum.warning,[{text:t.dataset.buttonCloseText||TYPO3.lang["button.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel"},{text:t.dataset.buttonOkText||TYPO3.lang["button.delete"]||"Delete",btnClass:"btn-warning",name:"delete"}]);a.addEventListener("button.clicked",(e=>{if("cancel"===e.target.getAttribute("name"))a.hideModal();else if("delete"===e.target.getAttribute("name")){a.hideModal(),t.disabled=!0;const e=t.querySelector(".t3js-icon");Icons.getIcon("spinner-circle",Icons.sizes.small).then((t=>{e.replaceWith(document.createRange().createContextualFragment(t))}));const n=t.closest("table[data-table]").dataset.table,l=t.closest("tr[data-uid]"),i=parseInt(l.dataset.uid,10),o={component:"datahandler",action:"delete",table:n,uid:i};AjaxDataHandler.process({cmd:{[n]:{[i]:{delete:!0}}}},o).then((e=>{e.hasErrors&&(t.disabled=!1,Icons.getIcon("actions-edit-delete",Icons.sizes.small).then((e=>{t.querySelector(".t3js-icon").replaceWith(document.createRange().createContextualFragment(e))})))})).catch((()=>{t.disabled=!1,Icons.getIcon("actions-edit-delete",Icons.sizes.small).then((e=>{t.querySelector(".t3js-icon").replaceWith(document.createRange().createContextualFragment(e))}))}))}}))},this.deleteRow=e=>{const t=document.querySelector(`table[data-table="${e.table}"]`),a=t.closest(".recordlist"),n=a.querySelector(".recordlist-heading"),l=t.querySelector(`tr[data-uid="${e.uid}"]`);if(t.querySelectorAll(`[data-l10nparent="${e.uid}"]`).forEach((e=>{new RegularEvent("transitionend",(()=>{e.remove()})).bindTo(e),e.classList.add("record-deleted")})),new RegularEvent("transitionend",(()=>{l.remove(),0===t.querySelectorAll("tbody tr").length&&a.remove()})).bindTo(l),l.classList.add("record-deleted"),"0"===l.dataset.l10nparent||""===l.dataset.l10nparent){const e=parseInt(n.querySelector(".t3js-table-total-items").textContent,10);n.querySelector(".t3js-table-total-items").textContent=(e-1).toString()}"pages"===e.table&&top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))},this.registerPaginationEvents=()=>{document.querySelectorAll(".t3js-recordlist-paging").forEach((e=>{e.addEventListener("keyup",(t=>{t.preventDefault();let a=Number(e.value);const n=Number(e.min),l=Number(e.max);if(n&&a<n&&(a=n),l&&a>l&&(a=l),e.value=a.toString(10),"Enter"===t.key&&a!==Number(e.dataset.currentpage)){const t=e.closest('form[name^="list-table-form-"]'),n=new URL(t.action,window.origin);n.searchParams.set("pointer",a.toString()),window.location.href=n.toString()}}))}))},new RegularEvent("click",this.toggleClick).delegateTo(document,this.identifier.toggle),new RegularEvent("click",this.onEditMultiple).delegateTo(document,this.identifier.editMultiple),new RegularEvent("click",this.disableButton).delegateTo(document,this.identifier.localize),new RegularEvent("click",this.toggleVisibility).delegateTo(document,this.identifier.hide),new RegularEvent("click",this.deleteRecord).delegateTo(document,this.identifier.delete),DocumentService.ready().then((()=>{this.registerPaginationEvents()})),new RegularEvent("typo3:datahandler:process",this.handleDataHandlerResult.bind(this)).bindTo(document),new RegularEvent("multiRecordSelection:action:edit",this.onEditMultiple).bindTo(document),new RegularEvent("multiRecordSelection:action:copyMarked",(e=>{Recordlist.submitClipboardFormWithCommand("copyMarked",e.target)})).bindTo(document),new RegularEvent("multiRecordSelection:action:removeMarked",(e=>{Recordlist.submitClipboardFormWithCommand("removeMarked",e.target)})).bindTo(document)}static submitClipboardFormWithCommand(e,t){const a=t.closest("form");if(!a)return;const n=a.querySelector('input[name="cmd"]');n&&(n.value=e,a.submit())}static getReturnUrl(e){return""===e&&(e=top.list_frame.document.location.pathname+top.list_frame.document.location.search),encodeURIComponent(e)}handleDataHandlerResult(e){const t=e.detail.payload;t.hasErrors||"delete"===t.action&&this.deleteRow(t)}}export default new Recordlist;