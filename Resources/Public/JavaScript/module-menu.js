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
import{ScaffoldIdentifierEnum}from"@typo3/backend/enum/viewport/scaffold-identifier.js";import{getRecordFromName}from"@typo3/backend/module.js";import $ from"jquery";import PersistentStorage from"@typo3/backend/storage/persistent.js";import Viewport from"@typo3/backend/viewport.js";import ClientRequest from"@typo3/backend/event/client-request.js";import TriggerRequest from"@typo3/backend/event/trigger-request.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import RegularEvent from"@typo3/core/event/regular-event.js";import{ModuleStateStorage}from"@typo3/backend/storage/module-state-storage.js";class ModuleMenu{constructor(){this.loadedModule=null,this.spaceKeyPressedOnCollapsible=!1,$((()=>this.initialize()))}static getCollapsedMainMenuItems(){return PersistentStorage.isset("modulemenu")?JSON.parse(PersistentStorage.get("modulemenu")):{}}static addCollapsedMainMenuItem(e){const t=ModuleMenu.getCollapsedMainMenuItems();t[e]=!0,PersistentStorage.set("modulemenu",JSON.stringify(t))}static removeCollapseMainMenuItem(e){const t=this.getCollapsedMainMenuItems();delete t[e],PersistentStorage.set("modulemenu",JSON.stringify(t))}static includeId(e,t){if(!e.navigationComponentId)return t;let n="";n="@typo3/backend/page-tree/page-tree-element"===e.navigationComponentId?"web":e.name.split("_")[0];const o=ModuleStateStorage.current(n);return o.selection&&(t="id="+o.selection+"&"+t),t}static toggleMenu(e){const t=document.querySelector(ScaffoldIdentifierEnum.scaffold),n="scaffold-modulemenu-expanded";void 0===e&&(e=t.classList.contains(n)),t.classList.toggle(n,!e),e||(t.classList.remove("scaffold-search-expanded"),t.classList.remove("scaffold-toolbar-expanded")),PersistentStorage.set("BackendComponents.States.typo3-module-menu",{collapsed:e})}static toggleModuleGroup(e){const t=e.closest(".modulemenu-group"),n=t.querySelector(".modulemenu-group-container"),o="true"===e.attributes.getNamedItem("aria-expanded").value;o?ModuleMenu.addCollapsedMainMenuItem(e.id):ModuleMenu.removeCollapseMainMenuItem(e.id),t.classList.toggle("modulemenu-group-collapsed",o),t.classList.toggle("modulemenu-group-expanded",!o),e.attributes.getNamedItem("aria-expanded").value=(!o).toString(),$(n).stop().slideToggle()}static highlightModuleMenuItem(e){document.querySelectorAll(".modulemenu-action.modulemenu-action-active").forEach((e=>{e.classList.remove("modulemenu-action-active"),e.removeAttribute("aria-current")}));const t=document.getElementById(e);t&&(t.classList.add("modulemenu-action-active"),t.setAttribute("aria-current","location"))}static getPreviousItem(e){let t=e.parentElement.previousElementSibling;return null===t?ModuleMenu.getLastItem(e):t.firstElementChild}static getNextItem(e){let t=e.parentElement.nextElementSibling;return null===t?ModuleMenu.getFirstItem(e):t.firstElementChild}static getFirstItem(e){return e.parentElement.parentElement.firstElementChild.firstElementChild}static getLastItem(e){return e.parentElement.parentElement.lastElementChild.firstElementChild}static getParentItem(e){return e.parentElement.parentElement.parentElement.firstElementChild}static getFirstChildItem(e){return e.nextElementSibling.firstElementChild.firstElementChild}static getLastChildItem(e){return e.nextElementSibling.lastElementChild.firstElementChild}refreshMenu(){new AjaxRequest(TYPO3.settings.ajaxUrls.modulemenu).get().then((async e=>{const t=await e.resolve();document.getElementById("modulemenu").outerHTML=t.menu,this.initializeModuleMenuEvents(),this.loadedModule&&ModuleMenu.highlightModuleMenuItem(this.loadedModule)}))}getCurrentModule(){return this.loadedModule}reloadFrames(){Viewport.ContentContainer.refresh()}showModule(e,t,n=null){t=t||"";const o=getRecordFromName(e);return this.loadModuleComponents(o,t,new ClientRequest("typo3.showModule",n))}initialize(){if(null===document.querySelector(".t3js-modulemenu"))return;let e=$.Deferred();e.resolve(),e.then((()=>{this.initializeModuleMenuEvents(),Viewport.Topbar.Toolbar.registerEvent((()=>{document.querySelector(".t3js-scaffold-toolbar")&&this.initializeTopBarEvents()}))}))}keyboardNavigation(e,t,n=!1){const o=t.parentElement.attributes.getNamedItem("data-level").value;let l=null;switch(n&&(this.spaceKeyPressedOnCollapsible=!1),e.code){case"ArrowUp":l=ModuleMenu.getPreviousItem(t);break;case"ArrowDown":l=ModuleMenu.getNextItem(t);break;case"ArrowLeft":"1"===o&&t.classList.contains("t3js-modulemenu-collapsible")?("false"===t.attributes.getNamedItem("aria-expanded").value&&ModuleMenu.toggleModuleGroup(t),l=ModuleMenu.getLastChildItem(t)):"2"===o&&(l=ModuleMenu.getPreviousItem(ModuleMenu.getParentItem(t)));break;case"ArrowRight":"1"===o&&t.classList.contains("t3js-modulemenu-collapsible")?("false"===t.attributes.getNamedItem("aria-expanded").value&&ModuleMenu.toggleModuleGroup(t),l=ModuleMenu.getFirstChildItem(t)):"2"===o&&(l=ModuleMenu.getNextItem(ModuleMenu.getParentItem(t)));break;case"Home":l=e.ctrlKey&&"2"===o?ModuleMenu.getFirstItem(ModuleMenu.getParentItem(t)):ModuleMenu.getFirstItem(t);break;case"End":l=e.ctrlKey&&"2"===o?ModuleMenu.getLastItem(ModuleMenu.getParentItem(t)):ModuleMenu.getLastItem(t);break;case"Space":case"Enter":"1"===o&&t.classList.contains("t3js-modulemenu-collapsible")&&("Enter"===e.code&&e.preventDefault(),ModuleMenu.toggleModuleGroup(t),"true"===t.attributes.getNamedItem("aria-expanded").value&&(l=ModuleMenu.getFirstChildItem(t),"Space"===e.code&&(this.spaceKeyPressedOnCollapsible=!0)));break;case"Esc":case"Escape":"2"===o&&(l=ModuleMenu.getParentItem(t),ModuleMenu.toggleModuleGroup(l));break;default:l=null}null!==l&&(e.defaultPrevented||e.preventDefault(),l.focus())}initializeModuleMenuEvents(){const e=document.querySelector(".t3js-modulemenu"),t=function(e){"Space"===e.code&&this.spaceKeyPressedOnCollapsible&&(e.preventDefault(),this.spaceKeyPressedOnCollapsible=!1)}.bind(this);new RegularEvent("keydown",this.keyboardNavigation).delegateTo(e,".t3js-modulemenu-action"),e.querySelectorAll('[data-level="2"] a.t3js-modulemenu-action[href]').forEach((e=>{e.addEventListener("keyup",t)})),new RegularEvent("keyup",((e,t)=>{"Space"===e.code&&e.preventDefault()})).delegateTo(e,".t3js-modulemenu-collapsible"),new RegularEvent("click",((e,t)=>{e.preventDefault(),this.showModule(t.id,"",e)})).delegateTo(e,"a.t3js-modulemenu-action[href]"),new RegularEvent("click",((e,t)=>{e.preventDefault(),ModuleMenu.toggleModuleGroup(t)})).delegateTo(e,".t3js-modulemenu-collapsible")}initializeTopBarEvents(){const e=document.querySelector(".t3js-scaffold-toolbar");new RegularEvent("keydown",((e,t)=>{this.keyboardNavigation(e,t)})).delegateTo(e,".t3js-modulemenu-action"),new RegularEvent("click",((e,t)=>{e.preventDefault(),this.showModule(t.id,"",e)})).delegateTo(e,"a.t3js-modulemenu-action[href]"),new RegularEvent("click",(e=>{e.preventDefault(),ModuleMenu.toggleMenu()})).bindTo(document.querySelector(".t3js-topbar-button-modulemenu")),new RegularEvent("click",(e=>{e.preventDefault(),ModuleMenu.toggleMenu(!0)})).bindTo(document.querySelector(".t3js-scaffold-content-overlay"));const t=e=>{const t=e.detail.module;if(!t||this.loadedModule===t)return;const n=getRecordFromName(t);if(!n.link)return;ModuleMenu.highlightModuleMenuItem(t);const o=document.getElementById(t);o&&o.focus(),this.loadedModule=t,n.navigationComponentId?Viewport.NavigationContainer.showComponent(n.navigationComponentId):Viewport.NavigationContainer.hide(!1)};document.addEventListener("typo3-module-load",t),document.addEventListener("typo3-module-loaded",t)}loadModuleComponents(e,t,n){const o=e.name,l=Viewport.ContentContainer.beforeSetUrl(n);return l.then((()=>{e.navigationComponentId?Viewport.NavigationContainer.showComponent(e.navigationComponentId):Viewport.NavigationContainer.hide(!0),ModuleMenu.highlightModuleMenuItem(o),this.loadedModule=o,t=ModuleMenu.includeId(e,t),this.openInContentContainer(o,e.link,t,new TriggerRequest("typo3.loadModuleComponents",n))})),l}openInContentContainer(e,t,n,o){const l=t+(n?(t.includes("?")?"&":"?")+n:"");return Viewport.ContentContainer.setUrl(l,new TriggerRequest("typo3.openInContentFrame",o),e)}}top.TYPO3.ModuleMenu||(top.TYPO3.ModuleMenu={App:new ModuleMenu});const moduleMenuApp=top.TYPO3.ModuleMenu;export default moduleMenuApp;