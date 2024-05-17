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
import{BroadcastMessage}from"@typo3/backend/broadcast-message.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import DocumentService from"@typo3/core/document-service.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import BroadcastService from"@typo3/backend/broadcast-service.js";import Icons from"@typo3/backend/icons.js";import Modal from"@typo3/backend/modal.js";import Notification from"@typo3/backend/notification.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Identifiers;!function(e){e.hide='button[data-datahandler-action="visibility"]',e.delete=".t3js-record-delete",e.icon=".t3js-icon"}(Identifiers||(Identifiers={}));class AjaxDataHandler{constructor(){DocumentService.ready().then((()=>{this.initialize()}))}static refreshPageTree(){top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))}static call(e){return new AjaxRequest(TYPO3.settings.ajaxUrls.record_process).withQueryArguments(e).get().then((async e=>await e.resolve()))}process(e,t){return AjaxDataHandler.call(e).then((e=>{if(e.hasErrors&&this.handleErrors(e),t){const a={...t,hasErrors:e.hasErrors},r=new BroadcastMessage("datahandler","process",a);BroadcastService.post(r);const n=new CustomEvent("typo3:datahandler:process",{detail:{payload:a}});document.dispatchEvent(n)}return e}))}initialize(){new RegularEvent("click",((e,t)=>{e.preventDefault(),this.handleVisibilityToggle(t)})).delegateTo(document,Identifiers.hide),new RegularEvent("click",((e,t)=>{e.preventDefault();const a=Modal.confirm(t.dataset.title,t.dataset.message,SeverityEnum.warning,[{text:t.dataset.buttonCloseText||TYPO3.lang["button.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel"},{text:t.dataset.buttonOkText||TYPO3.lang["button.delete"]||"Delete",btnClass:"btn-warning",name:"delete"}]);a.addEventListener("button.clicked",(e=>{"cancel"===e.target.getAttribute("name")?a.hideModal():"delete"===e.target.getAttribute("name")&&(a.hideModal(),this.deleteRecord(t))}))})).delegateTo(document,Identifiers.delete)}handleVisibilityToggle(e){const t=e.closest("tr[data-uid]"),a=e.querySelector(Identifiers.icon);this._showSpinnerIcon(a);const r={table:e.dataset.datahandlerTable,uid:e.dataset.datahandlerUid,field:e.dataset.datahandlerField,visible:"visible"===e.dataset.datahandlerStatus},n={data:{[r.table]:{[r.uid]:{[r.field]:r.visible?e.dataset.datahandlerHiddenValue:e.dataset.datahandlerVisibleValue}}}};this.process(n).then((a=>{if(!a.hasErrors){r.visible=!r.visible,e.setAttribute("data-datahandler-status",r.visible?"visible":"hidden");const a=r.visible?e.dataset.datahandlerVisibleLabel:e.dataset.datahandlerHiddenLabel;e.setAttribute("title",a);const n=r.visible?e.dataset.datahandlerVisibleIcon:e.dataset.datahandlerHiddenIcon,s=e.querySelector(Identifiers.icon);Icons.getIcon(n,Icons.sizes.small).then((e=>{s.replaceWith(document.createRange().createContextualFragment(e))}));const o=t.querySelector(".col-icon "+Identifiers.icon);r.visible?o.querySelector(".icon-overlay").remove():Icons.getIcon("miscellaneous-placeholder",Icons.sizes.small,"overlay-hidden").then((e=>{const t=document.createRange().createContextualFragment(e);o.append(t.querySelector(".icon-overlay"))}));const i=new RegularEvent("animationend",(()=>{t.classList.remove("record-pulse"),i.release()}));i.bindTo(t),t.classList.add("record-pulse"),"pages"===r.table&&AjaxDataHandler.refreshPageTree()}}))}deleteRecord(e){const t=e.dataset.params;let a=e.querySelector(Identifiers.icon);this._showSpinnerIcon(a);const r=e.closest("table[data-table]"),n=r.dataset.table,s=e.closest("tr[data-uid]"),o=parseInt(s.dataset.uid,10),i={component:"datahandler",action:"delete",table:n,uid:o};this.process(t,i).then((t=>{if(Icons.getIcon("actions-edit-delete",Icons.sizes.small).then((t=>{a=e.querySelector(Identifiers.icon),a.replaceWith(document.createRange().createContextualFragment(t))})),!t.hasErrors){const t=e.closest(".recordlist"),a=t.querySelector(".recordlist-heading-title");if(r.querySelectorAll('[data-l10nparent="'+o+'"]').forEach((e=>{new RegularEvent("transitionend",(()=>{e.remove()})).bindTo(e),e.classList.add("record-deleted")})),new RegularEvent("transitionend",(()=>{s.remove(),0===r.querySelectorAll("tbody tr").length&&t.remove()})).bindTo(s),s.classList.add("record-deleted"),"0"===e.dataset.l10nparent||""===e.dataset.l10nparent){const e=parseInt(a.querySelector(".t3js-table-total-items").textContent,10);a.querySelector(".t3js-table-total-items").textContent=(e-1).toString()}"pages"===n&&AjaxDataHandler.refreshPageTree()}}))}handleErrors(e){for(const t of e.messages)Notification.error(t.title,t.message)}_showSpinnerIcon(e){Icons.getIcon("spinner-circle",Icons.sizes.small).then((t=>{e.replaceWith(document.createRange().createContextualFragment(t))}))}}export default new AjaxDataHandler;