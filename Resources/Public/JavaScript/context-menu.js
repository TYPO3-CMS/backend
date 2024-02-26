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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import ContextMenuActions from"@typo3/backend/context-menu-actions.js";import DebounceEvent from"@typo3/core/event/debounce-event.js";import RegularEvent from"@typo3/core/event/regular-event.js";import{selector}from"@typo3/core/literals.js";import"@typo3/backend/element/spinner-element.js";class ContextMenu{constructor(){this.mousePos={X:null,Y:null},this.record={uid:null,table:null},this.eventSources=[],document.addEventListener("click",(e=>{this.handleTriggerEvent(e)})),document.addEventListener("contextmenu",(e=>{this.handleTriggerEvent(e)}))}static drawActionItem(e){const t=document.createElement("li");t.role="menuitem",t.classList.add("context-menu-item"),t.dataset.callbackAction=e.callbackAction,t.tabIndex=-1;const n=e.additionalAttributes||{};for(const e of Object.entries(n)){const[n,o]=e;t.setAttribute(n,o)}const o=document.createElement("span");o.classList.add("context-menu-item-icon"),o.innerHTML=e.icon;const s=document.createElement("span");return s.classList.add("context-menu-item-label"),s.innerHTML=e.label,t.append(o),t.append(s),t}static within(e,t,n){const o=e.getBoundingClientRect(),s=window.pageXOffset||document.documentElement.scrollLeft,i=window.pageYOffset||document.documentElement.scrollTop,c=t>=o.left+s&&t<=o.left+s+o.width,r=n>=o.top+i&&n<=o.top+i+o.height;return c&&r}show(e,t,n,o,s,i=null,c=null){this.hideAll(),this.initializeContextMenuContainer(),this.record={table:e,uid:t};const r=i.matches('a, button, [tabindex]:not([tabindex="-1"])')?i:i.closest('a, button, [tabindex]:not([tabindex="-1"])');!1===this.eventSources.includes(r)&&this.eventSources.push(r);const a=new URLSearchParams;void 0!==e&&a.set("table",e),void 0!==t&&a.set("uid",t.toString()),void 0!==n&&a.set("context",n);let l=null;null!==c&&(this.storeMousePosition(c),l=this.mousePos),this.fetch(a.toString(),l)}initializeContextMenuContainer(){if(null!==document.querySelector("#contentMenu0"))return;const e=document.createElement("div");e.classList.add("context-menu"),e.id="contentMenu0",e.style.display="none",document.querySelector("body").append(e);const t=document.createElement("div");t.classList.add("context-menu"),t.id="contentMenu1",t.style.display="none",t.dataset.parent="#contentMenu0",document.querySelector("body").append(t),document.querySelectorAll(".context-menu").forEach((e=>{new RegularEvent("mouseenter",(e=>{this.storeMousePosition(e)})).bindTo(e),new DebounceEvent("mouseleave",(e=>{const t=e.target,n=document.querySelector(selector`[data-parent="#${t.id}"]`);if(!ContextMenu.within(t,this.mousePos.X,this.mousePos.Y)&&(null===n||null===n.offsetParent)){let e;this.hide(t),void 0!==t.dataset.parent&&null!==(e=document.querySelector(t.dataset.parent))&&(ContextMenu.within(e,this.mousePos.X,this.mousePos.Y)||this.hide(document.querySelector(t.dataset.parent)))}}),500).bindTo(e)}))}handleTriggerEvent(e){if(!(e.target instanceof Element))return;const t=e.target.closest("[data-contextmenu-trigger]");if(t instanceof HTMLElement)return void this.handleContextMenuEvent(e,t);e.target.closest(".context-menu")||this.hideAll()}handleContextMenuEvent(e,t){const n=t.dataset.contextmenuTrigger;"click"!==n&&n!==e.type||(e.preventDefault(),this.show(t.dataset.contextmenuTable??"",t.dataset.contextmenuUid??"",t.dataset.contextmenuContext??"","","",t,e))}fetch(e,t){const n=this.renderStubMenu(0,t),o=TYPO3.settings.ajaxUrls.contextmenu;new AjaxRequest(o).withQueryArguments(e).get().then((async e=>{const t=await e.resolve();void 0!==e&&Object.keys(e).length>0&&this.populateData(t,0)})).catch((()=>{this.hide(n)}))}renderStubMenu(e,t){const n=document.querySelector("#contentMenu"+e);if(null!==n){n.replaceChildren(document.createRange().createContextualFragment('<typo3-backend-spinner size="medium"></typo3-backend-spinner>')),n.style.display=null,t??(t=this.getPosition(n));const e=this.toPixel(t);n.style.top=e.top,n.style.insetInlineStart=e.start}return n}populateData(e,t){const n=document.querySelector("#contentMenu"+t),o=document.querySelector("#contentMenu"+(t-1));if(null!==n&&null!==o?.offsetParent){const o=document.createElement("ul");o.classList.add("context-menu-group"),o.role="menu",this.drawMenu(e,t).forEach((e=>{o.appendChild(e)})),n.innerHTML="",n.appendChild(o),n.style.display=null,n.querySelector('.context-menu-item[tabindex="-1"]').focus(),this.initializeEvents(n,t)}}initializeEvents(e,t){e.querySelectorAll("li.context-menu-item").forEach((e=>{e.addEventListener("click",(e=>{e.preventDefault();const n=e.currentTarget;if(n.classList.contains("context-menu-item-submenu"))return void this.openSubmenu(t,n);const{callbackAction:o,callbackModule:s,...i}=n.dataset;n.dataset.callbackModule?import(s+".js").then((({default:e})=>{e[o](this.record.table,this.record.uid,i)})):ContextMenuActions&&"function"==typeof ContextMenuActions[o]?ContextMenuActions[o](this.record.table,this.record.uid,i):console.error("action: "+o+" not found"),this.hideAll()})),e.addEventListener("keydown",(e=>{e.preventDefault();const n=e.target;switch(e.key){case"Down":case"ArrowDown":this.setFocusToNextItem(n);break;case"Up":case"ArrowUp":this.setFocusToPreviousItem(n);break;case"Right":case"ArrowRight":if(!n.classList.contains("context-menu-item-submenu"))return;this.openSubmenu(t,n);break;case"Home":this.setFocusToFirstItem(n);break;case"End":this.setFocusToLastItem(n);break;case"Enter":case"Space":n.click();break;case"Esc":case"Escape":case"Left":case"ArrowLeft":this.hide(n.closest(".context-menu"));break;case"Tab":this.hideAll();break;default:return}}))}))}setFocusToPreviousItem(e){let t=this.getItemBackward(e.previousElementSibling);t||(t=this.getLastItem(e)),t.focus()}setFocusToNextItem(e){let t=this.getItemForward(e.nextElementSibling);t||(t=this.getFirstItem(e)),t.focus()}setFocusToFirstItem(e){const t=this.getFirstItem(e);t&&t.focus()}setFocusToLastItem(e){const t=this.getLastItem(e);t&&t.focus()}getItemBackward(e){for(;e&&(!e.classList.contains("context-menu-item")||"-1"!==e.getAttribute("tabindex"));)e=e.previousElementSibling;return e}getItemForward(e){for(;e&&(!e.classList.contains("context-menu-item")||"-1"!==e.getAttribute("tabindex"));)e=e.nextElementSibling;return e}getFirstItem(e){return this.getItemForward(e.parentElement.firstElementChild)}getLastItem(e){return this.getItemBackward(e.parentElement.lastElementChild)}openSubmenu(e,t){!1===this.eventSources.includes(t)&&this.eventSources.push(t);const n=document.querySelector("#contentMenu"+(e+1));n.innerHTML="",n.appendChild(t.nextElementSibling.querySelector(".context-menu-group").cloneNode(!0)),n.style.display=null;const o=this.toPixel(this.getPosition(n));n.style.top=o.top,n.style.insetInlineStart=o.start,n.querySelector('.context-menu-item[tabindex="-1"]').focus(),this.initializeEvents(n,e)}toPixel(e){return{start:Math.round(e.X)+"px",top:Math.round(e.Y)+"px"}}getPosition(e){const t="rtl"===document.querySelector("html").dir?"rtl":"ltr",n=this.eventSources?.[this.eventSources.length-1],o=e.offsetWidth,s=e.offsetHeight,i=window.innerWidth,c=window.innerHeight;let r=0,a=0;if(null!=n){const e=n.getBoundingClientRect();a=e.y,r="ltr"===t?e.x+e.width:i-e.x,n.classList.contains("context-menu-item-submenu")&&(a-=8)}else a=this.mousePos.Y,r="ltr"===t?this.mousePos.X:i-this.mousePos.X;return a+s+10+5<c?a+=5:a=c-s-10,r+o+10+5<i?r+=5:r=i-o-10,{X:r,Y:a}}drawMenu(e,t){const n=[];for(const o of Object.values(e))if("item"===o.type)n.push(ContextMenu.drawActionItem(o));else if("divider"===o.type){const e=document.createElement("li");e.role="separator",e.classList.add("context-menu-divider"),n.push(e)}else if("submenu"===o.type||o.childItems){const e=document.createElement("li");e.role="menuitem",e.ariaHasPopup="true",e.classList.add("context-menu-item","context-menu-item-submenu"),e.tabIndex=-1;const s=document.createElement("span");s.classList.add("context-menu-item-icon"),s.innerHTML=o.icon,e.appendChild(s);const i=document.createElement("span");i.classList.add("context-menu-item-label"),i.innerHTML=o.label,e.appendChild(i);const c=document.createElement("span");c.classList.add("context-menu-item-indicator"),c.innerHTML='<typo3-backend-icon identifier="actions-chevron-'+("rtl"===document.querySelector("html").dir?"left":"right")+'" size="small"></typo3-backend-icon>',e.appendChild(c),n.push(e);const r=document.createElement("div");r.classList.add("context-menu","contentMenu"+(t+1)),r.style.display="none";const a=document.createElement("ul");a.role="menu",a.classList.add("context-menu-group"),this.drawMenu(o.childItems,1).forEach((e=>{a.appendChild(e)})),r.appendChild(a),n.push(r)}return n}storeMousePosition(e){this.mousePos={X:e.pageX,Y:e.pageY}}hide(e){if(null===e)return;e.style.top=null,e.style.insetInlineStart=null,e.style.display="none";const t=this.eventSources.pop();t&&t.focus()}hideAll(){this.hide(document.querySelector("#contentMenu0")),this.hide(document.querySelector("#contentMenu1"))}}export default new ContextMenu;