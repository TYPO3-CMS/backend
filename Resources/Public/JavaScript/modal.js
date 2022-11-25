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
var Identifiers,__decorate=function(t,e,a,o){var s,i=arguments.length,n=i<3?e:null===o?o=Object.getOwnPropertyDescriptor(e,a):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(t,e,a,o);else for(var l=t.length-1;l>=0;l--)(s=t[l])&&(n=(i<3?s(n):i>3?s(e,a,n):s(e,a))||n);return i>3&&n&&Object.defineProperty(e,a,n),n};import{Modal as BootstrapModal}from"bootstrap";import{html,nothing,LitElement}from"lit";import{customElement,property,state}from"lit/decorators.js";import{unsafeHTML}from"lit/directives/unsafe-html.js";import{classMap}from"lit/directives/class-map.js";import{styleMap}from"lit/directives/style-map.js";import{ifDefined}from"lit/directives/if-defined.js";import{classesArrayToClassInfo}from"@typo3/core/lit-helper.js";import RegularEvent from"@typo3/core/event/regular-event.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Severity from"@typo3/backend/severity.js";import"@typo3/backend/element/icon-element.js";import"@typo3/backend/element/spinner-element.js";!function(t){t.modal=".t3js-modal",t.content=".t3js-modal-content",t.close=".t3js-modal-close",t.body=".t3js-modal-body",t.footer=".t3js-modal-footer"}(Identifiers||(Identifiers={}));export var Sizes;!function(t){t.small="small",t.default="default",t.medium="medium",t.large="large",t.full="full"}(Sizes||(Sizes={}));export var Styles;!function(t){t.default="default",t.light="light",t.dark="dark"}(Styles||(Styles={}));export var Types;!function(t){t.default="default",t.template="template",t.ajax="ajax",t.iframe="iframe"}(Types||(Types={}));export var PostActionModalBehavior;!function(t){t[t.KEEP_OPEN=0]="KEEP_OPEN",t[t.CLOSE=1]="CLOSE"}(PostActionModalBehavior||(PostActionModalBehavior={}));let ModalElement=class extends LitElement{constructor(){super(...arguments),this.modalTitle="",this.content="",this.type=Types.default,this.severity=SeverityEnum.notice,this.variant=Styles.default,this.size=Sizes.default,this.zindex=5e3,this.staticBackdrop=!1,this.additionalCssClasses=[],this.buttons=[],this.templateResultContent=null,this.activeButton=null,this.bootstrapModal=null,this.callback=null,this.ajaxCallback=null,this.userData={}}hideModal(){this.bootstrapModal&&this.bootstrapModal.hide()}createRenderRoot(){return this}firstUpdated(){this.bootstrapModal=new BootstrapModal(this.renderRoot.querySelector(Identifiers.modal),{}),this.bootstrapModal.show(),this.callback&&this.callback(this)}render(){const t={zIndex:this.zindex.toString()},e=classesArrayToClassInfo([`modal-type-${this.type}`,`modal-severity-${Severity.getCssClass(this.severity)}`,`modal-style-${this.variant}`,`modal-size-${this.size}`,...this.additionalCssClasses]);return html`
      <div
          tabindex="-1"
          class="modal fade t3js-modal ${classMap(e)}"
          style=${styleMap(t)}
          data-bs-backdrop="${!ifDefined(this.staticBackdrop)||"static"}"
          @show.bs.modal=${()=>this.trigger("typo3-modal-show")}
          @shown.bs.modal=${()=>this.trigger("typo3-modal-shown")}
          @hide.bs.modal=${()=>this.trigger("typo3-modal-hide")}
          @hidden.bs.modal=${()=>this.trigger("typo3-modal-hidden")}
      >
          <div class="modal-dialog">
              <div class="t3js-modal-content modal-content">
                  <div class="modal-header">
                      <h4 class="t3js-modal-title modal-title">${this.modalTitle}</h4>
                      <button class="t3js-modal-close close" @click=${()=>this.bootstrapModal.hide()}>
                          <span aria-hidden="true">
                              <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
                          </span>
                          <span class="visually-hidden"></span>
                      </button>
                  </div>
                  <div class="t3js-modal-body modal-body">${this.renderModalBody()}</div>
                  ${0===this.buttons.length?nothing:html`
                    <div class="t3js-modal-footer modal-footer">
                      ${this.buttons.map((t=>this.renderModalButton(t)))}
                    </div>
                  `}
              </div>
          </div>
      </div>
    `}_buttonClick(t,e){const a=t.currentTarget;e.action?(this.activeButton=e,e.action.execute(a).then(((t=PostActionModalBehavior.CLOSE)=>{this.activeButton=null;Object.values(PostActionModalBehavior).includes(t)||(console.warn(`postActionBehavior ${t} provided but expected any of ${Object.values(PostActionModalBehavior).join(",")}. Falling back to PostActionModalBehavior.CLOSE`),t=PostActionModalBehavior.CLOSE),t===PostActionModalBehavior.CLOSE&&this.bootstrapModal.hide()}))):e.trigger&&e.trigger(t,this),a.dispatchEvent(new CustomEvent("button.clicked",{bubbles:!0}))}renderAjaxBody(){return null===this.templateResultContent?(new AjaxRequest(this.content).get().then((async t=>{const e=await t.raw().text();this.templateResultContent=html`${unsafeHTML(e)}`,this.updateComplete.then((()=>{this.ajaxCallback&&this.ajaxCallback(this),this.dispatchEvent(new CustomEvent("modal-loaded"))}))})).catch((async t=>{const e=await t.raw().text();this.templateResultContent=e?html`${unsafeHTML(e)}`:html`<p><strong>Oops, received a ${t.response.status} response from </strong> <span class="text-break">${this.content}</span>.</p>`})),html`<div class="modal-loading"><typo3-backend-spinner size="default"></typo3-backend-spinner></div>`):this.templateResultContent}renderModalBody(){if(this.type===Types.ajax)return this.renderAjaxBody();if(this.type===Types.iframe){const t=t=>{const e=t.currentTarget;this.modalTitle=e.contentDocument.title,e.contentDocument.body.classList.add("with-overflow")};return html`
        <iframe src="${this.content}" name="modal_frame" class="modal-iframe t3js-modal-iframe" @load=${t}></iframe>
      `}return this.type===Types.template?this.templateResultContent:html`<p>${this.content}</p>`}renderModalButton(t){const e={btn:!0,[t.btnClass||"btn-default"]:!0,"t3js-active":t.active,disabled:this.activeButton&&this.activeButton!==t};return html`
      <button class=${classMap(e)}
              name=${ifDefined(t.name||void 0)}
              @click=${e=>this._buttonClick(e,t)}>
          ${t.icon?html`<typo3-backend-icon identifier="${t.icon}" size="small"></typo3-backend-icon>`:nothing}
          ${t.text}
      </button>
    `}trigger(t){this.dispatchEvent(new CustomEvent(t,{bubbles:!0,composed:!0}))}};__decorate([property({type:String,reflect:!0})],ModalElement.prototype,"modalTitle",void 0),__decorate([property({type:String,reflect:!0})],ModalElement.prototype,"content",void 0),__decorate([property({type:String,reflect:!0})],ModalElement.prototype,"type",void 0),__decorate([property({type:String,reflect:!0})],ModalElement.prototype,"severity",void 0),__decorate([property({type:String,reflect:!0})],ModalElement.prototype,"variant",void 0),__decorate([property({type:String,reflect:!0})],ModalElement.prototype,"size",void 0),__decorate([property({type:Number,reflect:!0})],ModalElement.prototype,"zindex",void 0),__decorate([property({type:Boolean})],ModalElement.prototype,"staticBackdrop",void 0),__decorate([property({type:Array})],ModalElement.prototype,"additionalCssClasses",void 0),__decorate([property({type:Array,attribute:!1})],ModalElement.prototype,"buttons",void 0),__decorate([state()],ModalElement.prototype,"templateResultContent",void 0),__decorate([state()],ModalElement.prototype,"activeButton",void 0),ModalElement=__decorate([customElement("typo3-backend-modal")],ModalElement);export{ModalElement};class Modal{constructor(){this.sizes=Sizes,this.styles=Styles,this.types=Types,this.currentModal=null,this.instances=[],this.defaultConfiguration={type:Types.default,title:"Information",content:"No content provided, please check your <code>Modal</code> configuration.",severity:SeverityEnum.notice,buttons:[],style:Styles.default,size:Sizes.default,additionalCssClasses:[],callback:null,ajaxCallback:null,staticBackdrop:!1},this.initializeMarkupTrigger(document)}static createModalResponseEventFromElement(t,e){return t.dataset.eventName?new CustomEvent(t.dataset.eventName,{bubbles:!0,detail:{result:e,payload:t.dataset.eventPayload||null}}):null}dismiss(){this.currentModal&&this.currentModal.hideModal()}confirm(t,e,a=SeverityEnum.warning,o=[],s){0===o.length&&o.push({text:TYPO3.lang["button.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel"},{text:TYPO3.lang["button.ok"]||"OK",btnClass:"btn-"+Severity.getCssClass(a),name:"ok"});const i=this.advanced({title:t,content:e,severity:a,buttons:o,additionalCssClasses:s});return i.addEventListener("button.clicked",(t=>{const e=t.target;"cancel"===e.getAttribute("name")?e.dispatchEvent(new CustomEvent("confirm.button.cancel",{bubbles:!0})):"ok"===e.getAttribute("name")&&e.dispatchEvent(new CustomEvent("confirm.button.ok",{bubbles:!0}))})),i}loadUrl(t,e=SeverityEnum.info,a,o,s){return this.advanced({type:Types.ajax,title:t,severity:e,buttons:a,ajaxCallback:s,content:o})}show(t,e,a=SeverityEnum.info,o,s){return this.advanced({type:Types.default,title:t,content:e,severity:a,buttons:o,additionalCssClasses:s})}advanced(t){return t.type="string"==typeof t.type&&t.type in Types?t.type:this.defaultConfiguration.type,t.title="string"==typeof t.title?t.title:this.defaultConfiguration.title,t.content="string"==typeof t.content||"object"==typeof t.content?t.content:this.defaultConfiguration.content,t.severity=void 0!==t.severity?t.severity:this.defaultConfiguration.severity,t.buttons=t.buttons||this.defaultConfiguration.buttons,t.size="string"==typeof t.size&&t.size in Sizes?t.size:this.defaultConfiguration.size,t.style="string"==typeof t.style&&t.style in Styles?t.style:this.defaultConfiguration.style,t.additionalCssClasses=t.additionalCssClasses||this.defaultConfiguration.additionalCssClasses,t.callback="function"==typeof t.callback?t.callback:this.defaultConfiguration.callback,t.ajaxCallback="function"==typeof t.ajaxCallback?t.ajaxCallback:this.defaultConfiguration.ajaxCallback,t.staticBackdrop=t.staticBackdrop||this.defaultConfiguration.staticBackdrop,this.generate(t)}setButtons(t){return this.currentModal.buttons=t,this.currentModal}initializeMarkupTrigger(t){new RegularEvent("click",((t,e)=>{t.preventDefault();const a=e.dataset.bsContent||"Are you sure?";let o=SeverityEnum.info;if(e.dataset.severity in SeverityEnum){const t=e.dataset.severity;o=SeverityEnum[t]}let s=e.dataset.url||null;if(null!==s){const t=s.includes("?")?"&":"?";s=s+t+new URLSearchParams(e.dataset).toString()}this.advanced({type:null!==s?Types.ajax:Types.default,title:e.dataset.title||"Alert",content:null!==s?s:a,severity:o,staticBackdrop:void 0!==e.dataset.staticBackdrop,buttons:[{text:e.dataset.buttonCloseText||TYPO3.lang["button.close"]||"Close",active:!0,btnClass:"btn-default",trigger:(t,a)=>{a.hideModal();const o=Modal.createModalResponseEventFromElement(e,!1);null!==o&&e.dispatchEvent(o)}},{text:e.dataset.buttonOkText||TYPO3.lang["button.ok"]||"OK",btnClass:"btn-"+Severity.getCssClass(o),trigger:(t,a)=>{a.hideModal();const o=Modal.createModalResponseEventFromElement(e,!0);null!==o&&e.dispatchEvent(o);let s=e.dataset.uri||e.dataset.href||e.getAttribute("href");s&&"#"!==s&&(e.ownerDocument.location.href=s),"submit"===e.getAttribute("type")&&(e.closest("form")?.submit(),"BUTTON"===e.tagName&&e.hasAttribute("form")&&e.ownerDocument.querySelector("form#"+e.getAttribute("form"))?.submit()),e.dataset.targetForm&&e.ownerDocument.querySelector("form#"+e.dataset.targetForm)?.submit()}}]})})).delegateTo(t,".t3js-modal-trigger")}generate(t){const e=document.createElement("typo3-backend-modal");return e.type=t.type,"string"==typeof t.content?e.content=t.content:t.type===Types.default&&(e.type=Types.template,e.templateResultContent=t.content),e.severity=t.severity,e.variant=t.style,e.size=t.size,e.modalTitle=t.title,e.additionalCssClasses=t.additionalCssClasses,e.buttons=t.buttons,e.staticBackdrop=t.staticBackdrop,t.callback&&(e.callback=t.callback),t.ajaxCallback&&(e.ajaxCallback=t.ajaxCallback),e.addEventListener("typo3-modal-shown",(()=>{const t=e.nextElementSibling,a=1e3+10*this.instances.length;e.zindex=a;const o=a-5;t.style.zIndex=o.toString(),e.querySelector(`${Identifiers.footer} .t3js-active`)?.focus()})),e.addEventListener("typo3-modal-hide",(()=>{if(this.instances.length>0){const t=this.instances.length-1;this.instances.splice(t,1),this.currentModal=this.instances[t-1]}})),e.addEventListener("typo3-modal-hidden",(t=>{e.remove(),this.instances.length>0&&document.body.classList.add("modal-open")})),e.addEventListener("typo3-modal-show",(()=>{this.currentModal=e,this.instances.push(e)})),document.body.appendChild(e),e}}let modalObject=null;try{parent&&parent.window.TYPO3&&parent.window.TYPO3.Modal?(parent.window.TYPO3.Modal.initializeMarkupTrigger(document),modalObject=parent.window.TYPO3.Modal):top&&top.TYPO3.Modal&&(top.TYPO3.Modal.initializeMarkupTrigger(document),modalObject=top.TYPO3.Modal)}catch{}modalObject||(modalObject=new Modal,TYPO3.Modal=modalObject);export default modalObject;