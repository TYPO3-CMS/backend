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
import{LitElement as u,html as d,nothing as m}from"lit";import{property as f,state as s,customElement as p}from"lit/decorators.js";var r=function(o,e,n,a){var i=arguments.length,t=i<3?e:a===null?a=Object.getOwnPropertyDescriptor(e,n):a,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(o,e,n,a);else for(var h=o.length-1;h>=0;h--)(c=o[h])&&(t=(i<3?c(t):i>3?c(e,n,t):c(e,n))||t);return i>3&&t&&Object.defineProperty(e,n,t),t};let l=class extends u{constructor(){super(...arguments),this.value="",this.checked=!1,this.filename=""}connectedCallback(){super.connectedCallback(),this.checked=this.value!=="",this.filename=this.value!==""&&this.value!=="true"?this.value:""}createRenderRoot(){return this}render(){return d`<input type=hidden name=ldownload .value=${this.computeValue()}><div class=form-check><input type=checkbox class=form-check-input id=ldownload-checkbox .checked=${this.checked} @change=${this.handleCheckboxChange}> <label class=form-check-label for=ldownload-checkbox>${this.getAttribute("label-download")??"Force download"}</label></div>${this.checked?d`<div class=mt-2><label for=ldownload-filename class=form-label>${this.getAttribute("label-filename")??"Custom filename"}</label> <input id=ldownload-filename type=text class=form-control .value=${this.filename} @input=${this.handleFilenameInput}></div>`:m}`}computeValue(){return this.checked?this.filename!==""?this.filename:"true":""}handleCheckboxChange(e){this.checked=e.target.checked}handleFilenameInput(e){this.filename=e.target.value}};r([f({type:String})],l.prototype,"value",void 0),r([s()],l.prototype,"checked",void 0),r([s()],l.prototype,"filename",void 0),l=r([p("typo3-backend-link-browser-download")],l);export{l as LinkBrowserDownloadElement};
