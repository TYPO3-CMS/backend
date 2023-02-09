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
import{lll}from"@typo3/core/lit-helper.js";import Modal from"@typo3/backend/modal.js";import"@typo3/backend/element/icon-element.js";import"@typo3/backend/input/clearable.js";import"@typo3/backend/live-search/element/search-option-item.js";import"@typo3/backend/live-search/element/show-all.js";import"@typo3/backend/live-search/live-search-shortcut.js";import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";import DebounceEvent from"@typo3/core/event/debounce-event.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import BrowserSession from"@typo3/backend/storage/browser-session.js";import{componentName as resultContainerComponentName}from"@typo3/backend/live-search/element/result/result-container.js";var Identifiers;!function(e){e.toolbarItem=".t3js-topbar-button-search",e.searchOptionDropdown=".t3js-search-provider-dropdown",e.searchOptionDropdownToggle=".t3js-search-provider-dropdown-toggle"}(Identifiers||(Identifiers={}));class LiveSearch{constructor(){this.searchTerm="",this.searchOptions={},this.search=async()=>{BrowserSession.set("livesearch-term",this.searchTerm);let e=null;if(""!==this.searchTerm){document.querySelector(resultContainerComponentName).loading=!0;const t=await new AjaxRequest(TYPO3.settings.ajaxUrls.livesearch).post({q:this.searchTerm,options:this.searchOptions});e=await t.raw().json()}this.updateSearchResults(e)},DocumentService.ready().then((()=>{this.registerEvents()}))}registerEvents(){new RegularEvent("click",(()=>{this.openSearchModal()})).delegateTo(document,Identifiers.toolbarItem),new RegularEvent("typo3:live-search:trigger-open",(()=>{Modal.currentModal||this.openSearchModal()})).bindTo(document)}openSearchModal(){const e=Modal.advanced({type:Modal.types.ajax,content:TYPO3.settings.ajaxUrls.livesearch_form+"&q="+(BrowserSession.get("livesearch-term")??""),title:lll("labels.search"),severity:SeverityEnum.notice,size:Modal.sizes.medium});e.addEventListener("typo3-modal-shown",(()=>{this.searchTerm=BrowserSession.get("livesearch-term")??"";const t=Object.entries(BrowserSession.getByPrefix("livesearch-option-")).filter((e=>"1"===e[1])).map((e=>{const t=e[0].replace("livesearch-option-",""),[r,o]=t.split("-",2);return{key:r,value:o}}));this.composeSearchOptions(t);const r=e.querySelector('input[type="search"]');r.clearable({onClear:()=>{this.searchTerm="",this.search()}}),r.focus(),r.select();const o=document.querySelector("typo3-backend-live-search-result-container");new RegularEvent("live-search:item-chosen",(()=>{Modal.dismiss()})).bindTo(o),new RegularEvent("hide.bs.dropdown",(()=>{const t=Array.from(e.querySelectorAll(Identifiers.searchOptionDropdown+" typo3-backend-live-search-option-item")).filter((e=>e.active)).map((e=>({key:e.optionName,value:e.optionId})));this.composeSearchOptions(t),this.search()})).bindTo(e.querySelector(Identifiers.searchOptionDropdownToggle)),new DebounceEvent("input",(e=>{this.searchTerm=e.target.value,this.search()})).bindTo(r),new RegularEvent("keydown",this.handleKeyDown).bindTo(r),this.search()}))}composeSearchOptions(e){this.searchOptions={},e.forEach((e=>{void 0===this.searchOptions[e.key]&&(this.searchOptions[e.key]=[]),this.searchOptions[e.key].push(e.value)}))}handleKeyDown(e){if("ArrowDown"!==e.key)return;e.preventDefault();document.querySelector("typo3-backend-live-search").querySelector("typo3-backend-live-search-result-item")?.focus()}updateSearchResults(e){document.querySelector("typo3-backend-live-search-show-all").parentElement.hidden=null===e||0===e.length;const t=document.querySelector("typo3-backend-live-search-result-container");t.results=e,t.loading=!1}}export default top.TYPO3.LiveSearch??new LiveSearch;