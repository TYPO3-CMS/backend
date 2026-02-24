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
import u from"@typo3/core/ajax/ajax-request.js";import i from"@typo3/backend/icons.js";import l from"@typo3/backend/notification.js";import p from"@typo3/backend/viewport.js";import b from"@typo3/core/event/regular-event.js";import s from"~labels/core.cache";var e;(function(r){r.containerSelector="#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem",r.menuItemSelector=".t3js-toolbar-cache-flush-action",r.toolbarIconSelector=".toolbar-item-icon .t3js-icon"})(e||(e={}));class h{constructor(){this.initializeEvents=()=>{const c=document.querySelector(e.containerSelector);new b("click",(t,o)=>{t.preventDefault(),o.dataset.endpoint&&this.clearCache(o.dataset.endpoint)}).delegateTo(c,e.menuItemSelector)},p.Topbar.Toolbar.registerEvent(this.initializeEvents)}clearCache(c){const t=document.querySelector(e.containerSelector);t.classList.remove("open");const o=t.querySelector(e.toolbarIconSelector),m=o.cloneNode(!0);i.getIcon("spinner-circle",i.sizes.small).then(a=>{o.replaceWith(document.createRange().createContextualFragment(a))}),new u(c).post({}).then(async a=>{const n=await a.resolve();l.success(n.title,n.message)},()=>{l.error(s.get("notification.error.title"),s.get("notification.error.message"))}).finally(()=>{t.querySelector(e.toolbarIconSelector).replaceWith(m)})}}var f=new h;export{f as default};
