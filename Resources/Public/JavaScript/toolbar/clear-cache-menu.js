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
import u from"@typo3/core/ajax/ajax-request.js";import s from"@typo3/backend/icons.js";import l from"@typo3/backend/notification.js";import h from"@typo3/backend/viewport.js";import f from"@typo3/core/event/regular-event.js";import i from"~labels/core.core";var e;(function(c){c.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",c.menuItemSelector=".t3js-toolbar-cache-flush-action",c.toolbarIconSelector=".toolbar-item-icon .t3js-icon"})(e||(e={}));class p{constructor(){this.initializeEvents=()=>{const a=document.querySelector(e.containerSelector);new f("click",(o,r)=>{o.preventDefault(),r.href&&this.clearCache(r.href)}).delegateTo(a,e.menuItemSelector)},h.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(a){const o=document.querySelector(e.containerSelector);o.classList.remove("open");const r=o.querySelector(e.toolbarIconSelector),m=r.cloneNode(!0);s.getIcon("spinner-circle",s.sizes.small).then(n=>{r.replaceWith(document.createRange().createContextualFragment(n))}),new u(a).post({}).then(async n=>{const t=await n.resolve();t.success===!0?l.success(t.title,t.message):t.success===!1&&l.error(t.title,t.message)},()=>{l.error(i.get("flushCaches.error"),i.get("flushCaches.error.description"))}).finally(()=>{o.querySelector(e.toolbarIconSelector).replaceWith(m)})}}var b=new p;export{b as default};
