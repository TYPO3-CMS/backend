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
import{Collapse}from"bootstrap";import Sortable from"sortablejs";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import DocumentService from"@typo3/core/document-service.js";import FlexFormContainerContainer from"@typo3/backend/form-engine/container/flex-form-container-container.js";import FormEngine from"@typo3/backend/form-engine.js";import RegularEvent from"@typo3/core/event/regular-event.js";import{JavaScriptItemProcessor}from"@typo3/core/java-script-item-processor.js";var Selectors;!function(e){e.toggleAllSelector=".t3-form-flexsection-toggle",e.addContainerSelector=".t3js-flex-container-add",e.actionFieldSelector=".t3js-flex-control-action",e.sectionContainerSelector=".t3js-flex-section",e.sectionContentContainerSelector=".t3js-flex-section-content",e.sectionContainerLabelSelector=".t3js-formengine-label",e.sortContainerButtonSelector=".t3js-sortable-handle"}(Selectors||(Selectors={}));class FlexFormSectionContainer{constructor(e){this.allowRestructure=!1,this.flexformContainerContainers=[],this.updateSorting=e=>{this.container.querySelectorAll(Selectors.actionFieldSelector).forEach(((e,t)=>{e.value=t.toString()})),this.updateToggleAllState(),this.flexformContainerContainers.splice(e.newIndex,0,this.flexformContainerContainers.splice(e.oldIndex,1)[0]),document.dispatchEvent(new Event("formengine:flexform:sorting-changed"))},DocumentService.ready().then((t=>{this.container=t.getElementById(e),this.sectionContainer=this.container.querySelector(this.container.dataset.section),this.allowRestructure="1"===this.sectionContainer.dataset.t3FlexAllowRestructure,this.registerEvents(),this.registerContainers()}))}static getCollapseInstance(e){return Collapse.getInstance(e)??new Collapse(e,{toggle:!1})}getContainer(){return this.container}getSectionContainer(){return this.sectionContainer}isRestructuringAllowed(){return this.allowRestructure}registerEvents(){this.allowRestructure&&(this.registerSortable(),this.registerContainerDeleted()),this.registerToggleAll(),this.registerCreateNewContainer(),this.registerPanelToggle()}registerContainers(){const e=this.container.querySelectorAll(Selectors.sectionContainerSelector);for(const t of e)this.flexformContainerContainers.push(new FlexFormContainerContainer(this,t));this.updateToggleAllState()}getToggleAllButton(){return this.container.querySelector(Selectors.toggleAllSelector)}registerSortable(){new Sortable(this.sectionContainer,{group:this.sectionContainer.id,handle:Selectors.sortContainerButtonSelector,onSort:this.updateSorting})}registerToggleAll(){new RegularEvent("click",(e=>{const t="true"===e.target.dataset.expandAll,n=this.container.querySelectorAll(Selectors.sectionContentContainerSelector);for(const e of n)t?FlexFormSectionContainer.getCollapseInstance(e).show():FlexFormSectionContainer.getCollapseInstance(e).hide()})).bindTo(this.getToggleAllButton())}registerCreateNewContainer(){new RegularEvent("click",((e,t)=>{e.preventDefault(),this.createNewContainer(t.dataset)})).delegateTo(this.container,Selectors.addContainerSelector)}createNewContainer(e){new AjaxRequest(TYPO3.settings.ajaxUrls.record_flex_container_add).post({vanillaUid:e.vanillauid,databaseRowUid:e.databaserowuid,command:e.command,tableName:e.tablename,fieldName:e.fieldname,recordTypeValue:e.recordtypevalue,dataStructureIdentifier:JSON.parse(e.datastructureidentifier),flexFormSheetName:e.flexformsheetname,flexFormFieldName:e.flexformfieldname,flexFormContainerName:e.flexformcontainername}).then((async t=>{const n=await t.resolve(),o=(new DOMParser).parseFromString(n.html,"text/html").body.firstElementChild;this.flexformContainerContainers.push(new FlexFormContainerContainer(this,o));const r=document.querySelector(e.target);if(r.insertAdjacentElement("beforeend",o),n.scriptItems instanceof Array&&n.scriptItems.length>0){(new JavaScriptItemProcessor).processItems(n.scriptItems)}if(n.stylesheetFiles&&n.stylesheetFiles.length>0)for(const e of n.stylesheetFiles){const t=document.createElement("link");t.rel="stylesheet",t.type="text/css",t.href=e,document.head.appendChild(t)}this.updateToggleAllState(),FormEngine.reinitialize(),FormEngine.Validation.initializeInputFields(),FormEngine.Validation.validate(r),this.container.querySelector(Selectors.sectionContainerLabelSelector)?.classList.add("has-change")}))}registerContainerDeleted(){new RegularEvent("formengine:flexform:container-deleted",(e=>{const t=e.detail.containerId;this.flexformContainerContainers=this.flexformContainerContainers.filter((e=>e.getStatus().id!==t)),FormEngine.Validation.validate(this.container),this.updateToggleAllState()})).bindTo(this.container)}registerPanelToggle(){["hide.bs.collapse","show.bs.collapse"].forEach((e=>{new RegularEvent(e,(()=>{this.updateToggleAllState()})).delegateTo(this.container,Selectors.sectionContentContainerSelector)}))}updateToggleAllState(){if(this.flexformContainerContainers.length>0){const e=this.flexformContainerContainers.find(Boolean);this.getToggleAllButton().dataset.expandAll=!0===e.getStatus().collapsed?"true":"false"}}}export default FlexFormSectionContainer;