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
import u from"@typo3/core/document-service.js";import h from"@typo3/backend/form-engine.js";import{selector as l}from"@typo3/core/literals.js";import t from"@typo3/backend/modal.js";import{FormEngineLinkBrowserSetLinkEvent as p}from"@typo3/backend/event/form-engine-link-browser-set-link-event.js";class f{constructor(a){this.controlElement=null,this.handleControlClick=i=>{i.preventDefault();const e=this.controlElement.dataset.itemName,c=document.querySelector(l`[name="${e}"]`),n=document.querySelector(l`[data-formengine-input-name="${e}"]`),m=this.controlElement.getAttribute("href")+"&P[currentValue]="+encodeURIComponent(document.forms.namedItem("editform")[e].value)+"&P[currentSelectedValues]="+encodeURIComponent(c.value),o=t.advanced({type:t.types.iframe,content:m,size:t.sizes.large});o.addEventListener(p.eventName,s=>{const{value:d,onFieldChangeItems:r}=s;n.value=d,n.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0})),Array.isArray(r)&&h.processOnFieldChange(r),o.hideModal()})},u.ready().then(()=>{this.controlElement=document.querySelector(a),this.controlElement!==null&&this.controlElement.addEventListener("click",this.handleControlClick)})}}export{f as default};
