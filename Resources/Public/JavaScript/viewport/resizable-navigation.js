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
import{LitElement as f,html as m}from"lit";import{property as s,state as p,customElement as b}from"lit/decorators.js";import{lll as u}from"@typo3/core/lit-helper.js";import v from"@typo3/backend/storage/persistent.js";import"@typo3/backend/element/icon-element.js";var a=function(i,t,e,c){var d=arguments.length,o=d<3?t:c===null?c=Object.getOwnPropertyDescriptor(t,e):c,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(i,t,e,c);else for(var h=i.length-1;h>=0;h--)(l=i[h])&&(o=(d<3?l(o):d>3?l(t,e,o):l(t,e))||o);return d>3&&o&&Object.defineProperty(t,e,o),o};const g={fromAttribute(i){return document.querySelector(i)}};var r;(function(i){i.ltr="ltr",i.rtl="rtl"})(r||(r={}));class N{static get(){return document.querySelector("html").dir==="rtl"?r.rtl:r.ltr}}let n=class extends f{constructor(){super(...arguments),this.minimumWidth=250,this.resizing=!1,this.toggleNavigation=t=>{t.stopPropagation(),this.parentContainer.classList.toggle("scaffold-content-navigation-expanded"),t.currentTarget instanceof HTMLElement&&(t.currentTarget.nextElementSibling??t.currentTarget.previousElementSibling).focus()},this.fallbackNavigationSizeIfNeeded=t=>{const e=t.currentTarget;this.getNavigationWidth()!==0&&e.outerWidth<this.getNavigationWidth()+this.getNavigationPosition().left+this.minimumWidth&&this.autoNavigationWidth()},this.handleMouseMove=t=>{this.resizeNavigation(t.clientX)},this.handleTouchMove=t=>{this.resizeNavigation(t.changedTouches[0].clientX)},this.resizeNavigation=t=>{let e=0;N.get()===r.ltr?e=Math.round(t)-Math.round(this.getNavigationPosition().left):e=Math.round(this.getNavigationPosition().right)-Math.round(t),this.setNavigationWidth(e)},this.startResizeNavigation=t=>{t instanceof MouseEvent&&t.button===2||(t.stopPropagation(),this.resizing=!0,document.addEventListener("mousemove",this.handleMouseMove,!1),document.addEventListener("mouseup",this.stopResizeNavigation,!1),document.addEventListener("touchmove",this.handleTouchMove,!1),document.addEventListener("touchend",this.stopResizeNavigation,!1))},this.stopResizeNavigation=()=>{this.resizing=!1,document.removeEventListener("mousemove",this.handleMouseMove,!1),document.removeEventListener("mouseup",this.stopResizeNavigation,!1),document.removeEventListener("touchmove",this.handleTouchMove,!1),document.removeEventListener("touchend",this.stopResizeNavigation,!1),v.set(this.persistenceIdentifier,this.getNavigationWidth()),document.dispatchEvent(new CustomEvent("typo3:navigation:resized"))}}connectedCallback(){super.connectedCallback();const t=this.initialWidth||parseInt(v.get(this.persistenceIdentifier),10);this.setNavigationWidth(t),window.addEventListener("resize",this.fallbackNavigationSizeIfNeeded,{passive:!0})}disconnectedCallback(){super.disconnectedCallback(),window.removeEventListener("resize",this.fallbackNavigationSizeIfNeeded)}createRenderRoot(){return this}async firstUpdated(){await new Promise(t=>setTimeout(t,0)),this.querySelector(".scaffold-content-navigation-switcher-btn").addEventListener("touchstart",this.toggleNavigation,{passive:!0}),this.querySelector(".scaffold-content-navigation-drag").addEventListener("touchstart",this.startResizeNavigation,{passive:!0})}render(){return m`<div class=scaffold-content-navigation-switcher><button @click=${this.toggleNavigation} class="btn btn-sm btn-default btn-borderless scaffold-content-navigation-switcher-btn scaffold-content-navigation-switcher-open" role=button title=${u("viewport_navigation_show")}><typo3-backend-icon identifier=actions-chevron-right size=small></typo3-backend-icon></button> <button @click=${this.toggleNavigation} class="btn btn-sm btn-default btn-borderless scaffold-content-navigation-switcher-btn scaffold-content-navigation-switcher-close" role=button title=${u("viewport_navigation_hide")}><typo3-backend-icon identifier=actions-chevron-left size=small></typo3-backend-icon></button></div><div @mousedown=${this.startResizeNavigation} class="scaffold-content-navigation-drag ${this.resizing?"resizing":""}"></div>`}getNavigationPosition(){return this.navigationContainer.getBoundingClientRect()}getNavigationWidth(){return this.navigationContainer.offsetWidth}autoNavigationWidth(){this.navigationContainer.style.width="auto"}setNavigationWidth(t){const e=Math.round(this.parentContainer.getBoundingClientRect().width/2);t>e&&(t=e),t=t>this.minimumWidth?t:this.minimumWidth,this.navigationContainer.style.width=t+"px"}};a([s({type:Number,attribute:"minimum-width"})],n.prototype,"minimumWidth",void 0),a([s({type:Number,attribute:"initial-width"})],n.prototype,"initialWidth",void 0),a([s({type:String,attribute:"persistence-identifier"})],n.prototype,"persistenceIdentifier",void 0),a([s({attribute:"parent",converter:g})],n.prototype,"parentContainer",void 0),a([s({attribute:"navigation",converter:g})],n.prototype,"navigationContainer",void 0),a([p()],n.prototype,"resizing",void 0),n=a([b("typo3-backend-navigation-switcher")],n);export{n as ResizableNavigation};
