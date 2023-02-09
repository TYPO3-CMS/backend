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
var __decorate=function(e,t,r,i){var n,o=arguments.length,l=o<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,r):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(e,t,r,i);else for(var a=e.length-1;a>=0;a--)(n=e[a])&&(l=(o<3?n(l):o>3?n(t,r,l):n(t,r))||l);return o>3&&l&&Object.defineProperty(t,r,l),l};import LiveSearchConfigurator from"@typo3/backend/live-search/live-search-configurator.js";import{customElement,property,query}from"lit/decorators.js";import{html,LitElement}from"lit";import{lll}from"@typo3/core/lit-helper.js";import"@typo3/backend/live-search/element/result/item/item-container.js";import"@typo3/backend/live-search/element/result/result-detail-container.js";export const componentName="typo3-backend-live-search-result-container";let ResultContainer=class extends LitElement{constructor(){super(...arguments),this.results=null,this.loading=!1}connectedCallback(){super.connectedCallback(),this.addEventListener("livesearch:request-actions",(e=>{this.resultDetailContainer.resultItem=e.detail.resultItem})),this.addEventListener("livesearch:invoke-action",(e=>{const t=LiveSearchConfigurator.getInvokeHandlers(),r=e.detail.resultItem,i=e.detail.action;void 0!==i&&("function"==typeof t[r.provider+"_"+i.identifier]?t[r.provider+"_"+i.identifier](r,i):TYPO3.Backend.ContentContainer.setUrl(i.url),this.dispatchEvent(new CustomEvent("live-search:item-chosen",{detail:{resultItem:r}})))}))}createRenderRoot(){return this}render(){return this.loading?html`<div class="d-flex flex-fill justify-content-center mt-2"><typo3-backend-spinner size="large"></typo3-backend-spinner></div>`:null===this.results?html``:0===this.results.length?html`<div class="alert alert-info">${lll("liveSearch_listEmptyText")}</div>`:html`
      <typo3-backend-live-search-result-item-container .results="${this.results}"></typo3-backend-live-search-result-item-container>
      <typo3-backend-live-search-result-item-detail-container></typo3-backend-live-search-result-item-detail-container>
    `}};__decorate([property({type:Object})],ResultContainer.prototype,"results",void 0),__decorate([property({type:Boolean,attribute:!1})],ResultContainer.prototype,"loading",void 0),__decorate([query("typo3-backend-live-search-result-item-container")],ResultContainer.prototype,"itemContainer",void 0),__decorate([query("typo3-backend-live-search-result-item-detail-container")],ResultContainer.prototype,"resultDetailContainer",void 0),ResultContainer=__decorate([customElement(componentName)],ResultContainer);export{ResultContainer};