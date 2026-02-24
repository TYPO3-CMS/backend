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
import n from"@typo3/backend/notification.js";import i from"@typo3/backend/icons.js";import d from"@typo3/core/event/regular-event.js";import g from"@typo3/core/ajax/ajax-request.js";import u from"~labels/core.cache";var a;(function(l){l.clearCache=".t3js-clear-page-cache",l.icon=".t3js-icon"})(a||(a={}));class c{constructor(){this.registerClickHandler()}static setDisabled(t,e){t.disabled=e,t.classList.toggle("disabled",e)}static sendClearCacheRequest(t){const e=new g(TYPO3.settings.ajaxUrls.clearcache_page).post({id:t});return e.then(async s=>{const r=await s.resolve();r.success===!0?n.success(r.title,r.message):n.error(r.title,r.message)},()=>{n.error(u.get("notification.error.title"),u.get("notification.error.message"))}),e}registerClickHandler(){const t=document.querySelector(`${a.clearCache}:not([disabled])`);t!==null&&new d("click",e=>{e.preventDefault();const s=e.currentTarget,r=parseInt(s.dataset.id,10);c.setDisabled(s,!0),i.getIcon("spinner-circle",i.sizes.small,null,"disabled").then(o=>{s.querySelector(a.icon).outerHTML=o}),c.sendClearCacheRequest(r).finally(()=>{i.getIcon("actions-system-cache-clear",i.sizes.small).then(o=>{s.querySelector(a.icon).outerHTML=o}),c.setDisabled(s,!1)})}).bindTo(t)}}var m=new c;export{m as default};
