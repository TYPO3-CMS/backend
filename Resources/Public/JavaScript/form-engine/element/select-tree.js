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
import{html as a}from"lit";import{Tree as f}from"@typo3/backend/tree/tree.js";import{state as h,customElement as u}from"lit/decorators.js";var o=function(c,e,t,s){var l=arguments.length,n=l<3?e:s===null?s=Object.getOwnPropertyDescriptor(e,t):s,i;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")n=Reflect.decorate(c,e,t,s);else for(var r=c.length-1;r>=0;r--)(i=c[r])&&(n=(l<3?i(n):l>3?i(e,t,n):i(e,t))||n);return l>3&&n&&Object.defineProperty(e,t,n),n};let d=class extends f{constructor(){super(),this.settings={unselectableElements:[],exclusiveNodesIdentifiers:"",validation:{},readOnlyMode:!1,showIcons:!0,width:300,dataUrl:"",defaultProperties:{},expandUpToLevel:null},this.exclusiveSelectedNode=null,this.addEventListener("typo3:tree:nodes-prepared",this.prepareLoadedNodes)}expandAll(){this.nodes.forEach(e=>{this.showChildren(e)})}selectNode(e,t=!0){if(!this.isNodeSelectable(e))return;const s=e.checked;this.handleExclusiveNodeSelection(e),!(this.settings.validation&&this.settings.validation.maxItems&&!s&&this.getSelectedNodes().length>=this.settings.validation.maxItems)&&(e.checked=!s,this.dispatchEvent(new CustomEvent("typo3:tree:node-selected",{detail:{node:e,propagate:t}})))}filter(e){const t=[];this.searchTerm=e,this.nodes.length&&(this.nodes[0].__expanded=!1);const s=this.nodes[0],l=new RegExp(e,"i");this.nodes.forEach(i=>{i!==s&&(i.__expanded=!1,i.__hidden=!0,l.test(i.name)&&t.push(i))}),t.forEach(i=>{i.__hidden=!1,this.showParents(i)}),this.nodes.filter(i=>t.some(r=>i.__parents.includes(r.identifier))).forEach(i=>{i.__hidden=!1})}showParents(e){if(e.__parents.length===0)return;const t=this.nodes.find(s=>s.identifier===e.__parents.at(-1));t.__hidden=!1,t.__expanded=!0,this.showParents(t)}isNodeSelectable(e){return!this.settings.readOnlyMode&&this.settings.unselectableElements.indexOf(e.identifier)===-1}createNodeContent(e){return a`${this.renderCheckbox(e)} ${super.createNodeContent(e)}`}renderCheckbox(e){const t=!!e.checked;let s="actions-square";return!this.isNodeSelectable(e)&&!t?s="actions-minus-circle":e.checked?s="actions-check-square":e.__indeterminate&&!t&&(s="actions-minus-square"),a`<span class=node-select> <typo3-backend-icon identifier=${s} size=small></typo3-backend-icon> </span>`}prepareLoadedNodes(e){const t=e.detail.nodes;e.detail.nodes=t.map(s=>(s.selectable===!1&&this.settings.unselectableElements.push(s.identifier),s))}handleExclusiveNodeSelection(e){const t=this.settings.exclusiveNodesIdentifiers.split(",");this.settings.exclusiveNodesIdentifiers.length&&e.checked===!1&&(t.indexOf(""+e.identifier)>-1?(this.resetSelectedNodes(),this.exclusiveSelectedNode=e):t.indexOf(""+e.identifier)===-1&&this.exclusiveSelectedNode&&(this.exclusiveSelectedNode.checked=!1,this.exclusiveSelectedNode=null))}};o([h()],d.prototype,"settings",void 0),o([h()],d.prototype,"exclusiveSelectedNode",void 0),d=o([u("typo3-backend-form-selecttree")],d);export{d as SelectTree};
