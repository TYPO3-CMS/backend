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
var __decorate=function(e,t,o,r){var l,n=arguments.length,s=n<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,o,r);else for(var a=e.length-1;a>=0;a--)(l=e[a])&&(s=(n<3?l(s):n>3?l(t,o,s):l(t,o))||s);return n>3&&s&&Object.defineProperty(t,o,s),s};import{html,LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import{lll}from"@typo3/core/lit-helper.js";import DebounceEvent from"@typo3/core/event/debounce-event.js";import"@typo3/backend/element/icon-element.js";import{Tree}from"@typo3/backend/tree/tree.js";let TreeToolbar=class extends LitElement{constructor(){super(...arguments),this.tree=null,this.settings={searchInput:".search-input",filterTimeout:450}}createRenderRoot(){return this}firstUpdated(){const e=this.querySelector(this.settings.searchInput);e&&new DebounceEvent("input",(e=>{const t=e.target;this.tree.filter(t.value.trim())}),this.settings.filterTimeout).bindTo(e)}render(){return html`
      <div class="tree-toolbar">
        <div class="tree-toolbar__menu">
          <div class="tree-toolbar__search">
              <label for="toolbarSearch" class="visually-hidden">
                ${lll("labels.label.searchString")}
              </label>
              <input type="search" id="toolbarSearch" class="form-control form-control-sm search-input" placeholder="${lll("tree.searchTermInfo")}">
          </div>
        </div>
        <div class="tree-toolbar__submenu">
          <button
            type="button"
            class="tree-toolbar__menuitem dropdown-toggle dropdown-toggle-no-chevron float-end"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <typo3-backend-icon identifier="actions-menu-alternative" size="small"></typo3-backend-icon>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <button class="dropdown-item" @click="${()=>this.refreshTree()}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll("labels.refresh")}
                  </span>
                </span>
              </button>
            </li>
            <li>
              <button class="dropdown-item" @click="${e=>this.collapseAll(e)}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="apps-pagetree-category-collapse-all" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll("labels.collapse")}
                  </span>
                </span>
              </button>
            </li>
          </ul>
        </div>
      </div>
    `}refreshTree(){this.tree.refreshOrFilterTree()}collapseAll(e){e.preventDefault(),this.tree.nodes.forEach((e=>{e.__parents.length&&this.tree.hideChildren(e)}))}};__decorate([property({type:Tree})],TreeToolbar.prototype,"tree",void 0),TreeToolbar=__decorate([customElement("typo3-backend-tree-toolbar")],TreeToolbar);export{TreeToolbar};