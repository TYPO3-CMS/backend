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
var __decorate=this&&this.__decorate||function(e,t,o,n){var r,i=arguments.length,a=i<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,o):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)a=Reflect.decorate(e,t,o,n);else for(var l=e.length-1;l>=0;l--)(r=e[l])&&(a=(i<3?r(a):i>3?r(t,o,a):r(t,o))||a);return i>3&&a&&Object.defineProperty(t,o,a),a};define(["require","exports","lit","lit/decorators","TYPO3/CMS/Core/lit-helper"],(function(e,t,o,n,r){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.IframeModuleElement=t.componentName=void 0,t.componentName="typo3-iframe-module";let i=class extends o.LitElement{constructor(){super(...arguments),this.endpoint=""}createRenderRoot(){return this}render(){return this.endpoint?o.html`
      <iframe
        src="${this.endpoint}"
        name="list_frame"
        id="typo3-contentIframe"
        class="scaffold-content-module-iframe t3js-scaffold-content-module-iframe"
        title="${r.lll("iframe.listFrame")}"
        scrolling="no"
        @load="${this._loaded}"
      ></iframe>
    `:o.html``}attributeChangedCallback(e,t,o){super.attributeChangedCallback(e,t,o),"endpoint"===e&&o===t&&this.iframe.setAttribute("src",o)}connectedCallback(){super.connectedCallback(),this.endpoint&&this.dispatch("typo3-iframe-load",{url:this.endpoint})}registerUnloadHandler(e){try{e.contentWindow.addEventListener("unload",t=>this._unload(t,e),{once:!0})}catch(e){throw console.error("Failed to access contentWindow of module iframe – using a foreign origin?"),e}}retrieveModuleStateFromIFrame(e){var t;try{return{url:e.contentWindow.location.href,title:e.contentDocument.title,module:null===(t=e.contentDocument.body.querySelector(".module[data-module-name]"))||void 0===t?void 0:t.getAttribute("data-module-name")}}catch(e){return console.error("Failed to access contentWindow of module iframe – using a foreign origin?"),{url:this.endpoint}}}_loaded({target:e}){const t=e;this.registerUnloadHandler(t);const o=this.retrieveModuleStateFromIFrame(t);this.dispatch("typo3-iframe-loaded",o)}_unload(e,t){new Promise(e=>window.setTimeout(e,0)).then(()=>{null!==t.contentWindow&&this.dispatch("typo3-iframe-load",{url:t.contentWindow.location.href})})}dispatch(e,t){this.dispatchEvent(new CustomEvent(e,{detail:t,bubbles:!0,composed:!0}))}};__decorate([n.property({type:String})],i.prototype,"endpoint",void 0),__decorate([n.query("iframe",!0)],i.prototype,"iframe",void 0),i=__decorate([n.customElement(t.componentName)],i),t.IframeModuleElement=i}));