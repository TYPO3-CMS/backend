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
import{Modal as Y}from"bootstrap";import{LitElement as E,html as p,nothing as j}from"lit";import{property as u,state as z,customElement as S}from"lit/decorators.js";import{unsafeHTML as O}from"lit/directives/unsafe-html.js";import{classMap as R}from"lit/directives/class-map.js";import{styleMap as A}from"lit/directives/style-map.js";import{ifDefined as P}from"lit/directives/if-defined.js";import{classesArrayToClassInfo as L}from"@typo3/core/lit-helper.js";import T from"@typo3/core/event/regular-event.js";import{SeverityEnum as y}from"@typo3/backend/enum/severity.js";import D from"@typo3/core/ajax/ajax-request.js";import B from"@typo3/backend/severity.js";import"@typo3/backend/element/icon-element.js";import"@typo3/backend/element/spinner-element.js";var d=function(o,t,e,s){var a=arguments.length,l=a<3?t:s===null?s=Object.getOwnPropertyDescriptor(t,e):s,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")l=Reflect.decorate(o,t,e,s);else for(var m=o.length-1;m>=0;m--)(c=o[m])&&(l=(a<3?c(l):a>3?c(t,e,l):c(t,e))||l);return a>3&&l&&Object.defineProperty(t,e,l),l},w;(function(o){o.modal=".t3js-modal",o.content=".t3js-modal-content",o.close=".t3js-modal-close",o.body=".t3js-modal-body",o.footer=".t3js-modal-footer"})(w||(w={}));var h;(function(o){o.small="small",o.default="default",o.medium="medium",o.large="large",o.full="full"})(h||(h={}));var f;(function(o){o.default="default",o.light="light",o.dark="dark"})(f||(f={}));var n;(function(o){o.default="default",o.template="template",o.ajax="ajax",o.iframe="iframe"})(n||(n={}));let i=class extends E{constructor(){super(...arguments),this.modalTitle="",this.content="",this.type=n.default,this.severity=y.notice,this.variant=f.default,this.size=h.default,this.zindex=5e3,this.staticBackdrop=!1,this.hideCloseButton=!1,this.additionalCssClasses=[],this.buttons=[],this.templateResultContent=null,this.activeButton=null,this.bootstrapModal=null,this.callback=null,this.ajaxCallback=null,this.userData={},this.keydownEventHandler=null}setContent(t){this.templateResultContent=t}hideModal(){this.bootstrapModal&&(this.bootstrapModal.hide(),this.keydownEventHandler?.release())}createRenderRoot(){return this}firstUpdated(){this.bootstrapModal=new Y(this.renderRoot.querySelector(w.modal),{}),this.bootstrapModal.show(),this.callback&&this.callback(this)}updated(t){t.has("templateResultContent")&&this.dispatchEvent(new CustomEvent("modal-updated",{bubbles:!0}))}render(){const t={zIndex:this.zindex.toString()},e=L([`modal-type-${this.type}`,`modal-severity-${B.getCssClass(this.severity)}`,`modal-style-${this.variant}`,`modal-size-${this.size}`,...this.additionalCssClasses]);return p`<div tabindex=-1 class="modal fade t3js-modal ${R(e)}" style=${A(t)} data-bs-backdrop=${P(this.staticBackdrop)?"static":!0} @show.bs.modal=${()=>this.trigger("typo3-modal-show")} @shown.bs.modal=${()=>this.trigger("typo3-modal-shown")} @hide.bs.modal=${()=>this.trigger("typo3-modal-hide")} @hidden.bs.modal=${()=>this.trigger("typo3-modal-hidden")}><div class=modal-dialog><div class="t3js-modal-content modal-content"><div class=modal-header><h1 class="h4 t3js-modal-title modal-title">${this.modalTitle}</h1>${this.hideCloseButton?j:p`<button class="t3js-modal-close close" @click=${()=>this.bootstrapModal.hide()}><typo3-backend-icon identifier=actions-close size=small></typo3-backend-icon><span class=visually-hidden>${TYPO3?.lang?.["button.close"]||"Close"}</span></button>`}</div><div class="t3js-modal-body modal-body">${this.renderModalBody()}</div>${this.buttons.length===0?j:p`<div class="t3js-modal-footer modal-footer">${this.buttons.map(s=>this.renderModalButton(s))}</div>`}</div></div></div>`}_buttonClick(t,e){const s=t.currentTarget;e.action?(this.activeButton=e,e.action.execute(s).then(()=>this.bootstrapModal.hide())):e.trigger&&e.trigger(t,this),s.dispatchEvent(new CustomEvent("button.clicked",{bubbles:!0}))}renderAjaxBody(){return this.templateResultContent===null?(new D(this.content).get().then(async t=>{const e=await t.raw().text();this.templateResultContent=p`${O(e)}`,this.updateComplete.then(()=>{this.ajaxCallback&&this.ajaxCallback(this),this.dispatchEvent(new CustomEvent("modal-loaded"))})}).catch(async t=>{const e=await t.raw().text();e?this.templateResultContent=p`${O(e)}`:this.templateResultContent=p`<p><strong>Oops, received a ${t.response.status} response from </strong><span class=text-break>${this.content}</span>.</p>`}),p`<div class=modal-loading><typo3-backend-spinner size=large></typo3-backend-spinner></div>`):this.templateResultContent}renderModalBody(){if(this.keydownEventHandler=new T("keydown",this.handleKeydown),this.keydownEventHandler.bindTo(document),this.type===n.iframe){const t=e=>{const s=e.currentTarget;s.contentDocument.title&&(this.modalTitle=s.contentDocument.title),new T("keydown",this.handleKeydown).bindTo(s.contentDocument)};return p`<iframe src=${this.content} name=modal_frame class="modal-iframe t3js-modal-iframe" @load=${t}></iframe>`}return this.type===n.ajax?this.renderAjaxBody():this.type===n.template?this.templateResultContent:p`<p>${this.content}</p>`}renderModalButton(t){const s={btn:!0,[t.btnClass||"btn-default"]:!0,"t3js-active":t.active,disabled:this.activeButton&&this.activeButton!==t};return p`<button class=${R(s)} name=${P(t.name||void 0)} @click=${a=>this._buttonClick(a,t)}>${t.icon?p`<typo3-backend-icon identifier=${t.icon} size=small></typo3-backend-icon>`:j} ${t.text}</button>`}trigger(t){this.dispatchEvent(new CustomEvent(t,{bubbles:!0,composed:!0}))}handleKeydown(t){t.key==="Escape"&&parent?.top?.TYPO3?.Modal&&parent.top.TYPO3.Modal.dismiss()}};d([u({type:String,reflect:!0})],i.prototype,"modalTitle",void 0),d([u({type:String,reflect:!0})],i.prototype,"content",void 0),d([u({type:String,reflect:!0})],i.prototype,"type",void 0),d([u({type:String,reflect:!0})],i.prototype,"severity",void 0),d([u({type:String,reflect:!0})],i.prototype,"variant",void 0),d([u({type:String,reflect:!0})],i.prototype,"size",void 0),d([u({type:Number,reflect:!0})],i.prototype,"zindex",void 0),d([u({type:Boolean})],i.prototype,"staticBackdrop",void 0),d([u({type:Boolean})],i.prototype,"hideCloseButton",void 0),d([u({type:Array})],i.prototype,"additionalCssClasses",void 0),d([u({type:Array,attribute:!1})],i.prototype,"buttons",void 0),d([z()],i.prototype,"templateResultContent",void 0),d([z()],i.prototype,"activeButton",void 0),i=d([S("typo3-backend-modal")],i);class M{constructor(){this.sizes=h,this.styles=f,this.types=n,this.currentModal=null,this.instances=[],this.defaultConfiguration={type:n.default,title:"Information",content:"No content provided, please check your <code>Modal</code> configuration.",severity:y.notice,buttons:[],style:f.default,size:h.default,additionalCssClasses:[],callback:null,ajaxCallback:null,staticBackdrop:!1,hideCloseButton:!1},this.initializeMarkupTrigger(document)}static createModalResponseEventFromElement(t,e){return t.dataset.eventName?new CustomEvent(t.dataset.eventName,{bubbles:!0,detail:{result:e,payload:t.dataset.eventPayload||null}}):null}dismiss(){this.currentModal&&this.currentModal.hideModal()}confirm(t,e,s=y.warning,a=[],l){a.length===0&&a.push({text:TYPO3?.lang?.["button.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel"},{text:TYPO3?.lang?.["button.ok"]||"OK",btnClass:"btn-"+B.getCssClass(s),name:"ok"});const c=this.advanced({title:t,content:e,severity:s,buttons:a,additionalCssClasses:l});return c.addEventListener("button.clicked",m=>{const r=m.target;r.getAttribute("name")==="cancel"?r.dispatchEvent(new CustomEvent("confirm.button.cancel",{bubbles:!0})):r.getAttribute("name")==="ok"&&r.dispatchEvent(new CustomEvent("confirm.button.ok",{bubbles:!0}))}),c}loadUrl(t,e=y.info,s,a,l){return this.advanced({type:n.ajax,title:t,severity:e,buttons:s,ajaxCallback:l,content:a})}show(t,e,s=y.info,a,l){return this.advanced({type:n.default,title:t,content:e,severity:s,buttons:a,additionalCssClasses:l})}advanced(t){return t.type=typeof t.type=="string"&&t.type in n?t.type:this.defaultConfiguration.type,t.title=typeof t.title=="string"?t.title:this.defaultConfiguration.title,t.content=typeof t.content=="string"||typeof t.content=="object"?t.content:this.defaultConfiguration.content,t.severity=typeof t.severity<"u"?t.severity:this.defaultConfiguration.severity,t.buttons=t.buttons||this.defaultConfiguration.buttons,t.size=typeof t.size=="string"&&t.size in h?t.size:this.defaultConfiguration.size,t.style=typeof t.style=="string"&&t.style in f?t.style:this.defaultConfiguration.style,t.additionalCssClasses=t.additionalCssClasses||this.defaultConfiguration.additionalCssClasses,t.callback=typeof t.callback=="function"?t.callback:this.defaultConfiguration.callback,t.ajaxCallback=typeof t.ajaxCallback=="function"?t.ajaxCallback:this.defaultConfiguration.ajaxCallback,t.staticBackdrop=t.staticBackdrop||this.defaultConfiguration.staticBackdrop,t.hideCloseButton=t.hideCloseButton||this.defaultConfiguration.hideCloseButton,this.generate(t)}setButtons(t){return this.currentModal.buttons=t,this.currentModal}initializeMarkupTrigger(t){const e=(s,a)=>{s.preventDefault();const l=a.dataset.bsContent||a.dataset.content||TYPO3?.lang?.["message.confirmation"]||"Are you sure?";let c=y.notice;if(a.dataset.severity in y){const b=a.dataset.severity;c=y[b]}let m=h.default;if(a.dataset.size in h){const b=a.dataset.size;m=h[b]}let r=a.dataset.url||null;if(r!==null){const b=r.includes("?")?"&":"?",C=new URLSearchParams(a.dataset).toString();r=r+b+C}this.advanced({type:r!==null?n.ajax:n.default,title:a.dataset.title||"Alert",content:r!==null?r:l,size:m,severity:c,staticBackdrop:a.dataset.staticBackdrop!==void 0,buttons:[{text:a.dataset.buttonCloseText||TYPO3?.lang?.["button.close"]||"Close",active:!0,btnClass:"btn-default",trigger:(b,C)=>{C.hideModal();const k=M.createModalResponseEventFromElement(a,!1);k!==null&&a.dispatchEvent(k)}},{text:a.dataset.buttonOkText||TYPO3?.lang?.["button.ok"]||"OK",btnClass:"btn-"+B.getCssClass(c),trigger:(b,C)=>{C.hideModal();const k=M.createModalResponseEventFromElement(a,!0);k!==null&&a.dispatchEvent(k);const x=a.dataset.uri||a.dataset.href||a.getAttribute("href");if(x&&x!=="#"&&(a.ownerDocument.location.href=x),a.getAttribute("type")==="submit"&&(a.tagName==="BUTTON"||a.tagName==="INPUT")){const $=a;$.form?.requestSubmit($)}a.dataset.targetForm&&a.ownerDocument.querySelector("form#"+a.dataset.targetForm)?.submit()}}]})};new T("click",e).delegateTo(t,".t3js-modal-trigger")}generate(t){const e=document.createElement("typo3-backend-modal");return e.type=t.type,typeof t.content=="string"?e.content=t.content:t.type===n.default&&(e.type=n.template,e.templateResultContent=t.content),e.severity=t.severity,e.variant=t.style,e.size=t.size,e.modalTitle=t.title,e.additionalCssClasses=t.additionalCssClasses,e.buttons=t.buttons,e.staticBackdrop=t.staticBackdrop,e.hideCloseButton=t.hideCloseButton,t.callback&&(e.callback=t.callback),t.ajaxCallback&&(e.ajaxCallback=t.ajaxCallback),e.addEventListener("typo3-modal-shown",()=>{const s=e.nextElementSibling,a=1e3+10*this.instances.length;e.zindex=a;const l=a-5;s.style.zIndex=l.toString(),e.querySelector(`${w.footer} .t3js-active`)?.focus()}),e.addEventListener("typo3-modal-hide",()=>{if(this.instances.length>0){const s=this.instances.length-1;this.instances.splice(s,1),this.currentModal=this.instances[s-1]}}),e.addEventListener("typo3-modal-hidden",()=>{e.remove(),this.instances.length>0&&document.body.classList.add("modal-open")}),e.addEventListener("typo3-modal-show",()=>{this.currentModal=e,this.instances.push(e)}),document.body.appendChild(e),e}}let v=null;try{parent&&parent.window.TYPO3&&parent.window.TYPO3.Modal?(parent.window.TYPO3.Modal.initializeMarkupTrigger(document),v=parent.window.TYPO3.Modal):top&&top.TYPO3.Modal&&(top.TYPO3.Modal.initializeMarkupTrigger(document),v=top.TYPO3.Modal)}catch{}v||(v=new M,typeof TYPO3<"u"&&(TYPO3.Modal=v));var N=v;export{i as ModalElement,h as Sizes,f as Styles,n as Types,N as default};
