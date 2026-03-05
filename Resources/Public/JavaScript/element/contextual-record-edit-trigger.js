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
import{property as l,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as u}from"@typo3/backend/element/pseudo-button.js";import g,{Positions as y,Sizes as h,Types as v}from"@typo3/backend/modal.js";import E from"@typo3/backend/notification.js";import p from"@typo3/backend/storage/persistent.js";import f from"~labels/backend.alt_doc";var c=function(d,e,i,n){var r=arguments.length,o=r<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,i):n,t;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(d,e,i,n);else for(var s=d.length-1;s>=0;s--)(t=d[s])&&(o=(r<3?t(o):r>3?t(e,i,o):t(e,i))||o);return r>3&&o&&Object.defineProperty(e,i,o),o};let a=class extends u{async buttonActivated(){if(p.isset("contextualRecordEdit")&&p.get("contextualRecordEdit")==0){top?.TYPO3?.Backend?.ContentContainer&&top.TYPO3.Backend.ContentContainer.setUrl(this.editUrl);return}const e=g.advanced({type:v.iframe,title:"",content:this.url,size:h.expand,position:y.sheet,hideHeader:!0});this.setupMessageHandling(e)}setupMessageHandling(e){const i=top;let n="",r=!1;const o=t=>{t.origin===window.location.origin&&(t.data?.actionName==="typo3:editform:saved"&&(n=t.data.recordTitle??""),t.data?.actionName==="typo3:editform:closed"&&(r=!0,e.hideModal()),t.data?.actionName==="typo3:editform:navigate"&&(r=!0,e.hideModal()))};i.addEventListener("message",o),e.addEventListener("typo3-modal-hide",t=>{if(r)return;t.preventDefault(),e.querySelector("iframe")?.contentWindow?.postMessage({actionName:"typo3:editform:requestclose"},window.location.origin)}),e.addEventListener("typo3-modal-hidden",()=>{i.removeEventListener("message",o),n!==""?(top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh")),top.TYPO3?.Backend?.ContentContainer&&top.TYPO3.Backend.ContentContainer.refresh(),E.success(f.get("notification.record_updated.title"),f.get("notification.record_updated.message",[n]))):this.focus()})}};c([l({type:String})],a.prototype,"url",void 0),c([l({type:String,attribute:"edit-url"})],a.prototype,"editUrl",void 0),a=c([m("typo3-backend-contextual-record-edit-trigger")],a);export{a as ContextualRecordEditTriggerElement};
