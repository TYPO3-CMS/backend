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
import $ from"jquery";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import ContextMenuActions from"@typo3/backend/context-menu-actions.js";import DebounceEvent from"@typo3/core/event/debounce-event.js";import RegularEvent from"@typo3/core/event/regular-event.js";import ThrottleEvent from"@typo3/core/event/throttle-event.js";class ContextMenu{constructor(){this.mousePos={X:null,Y:null},this.record={uid:null,table:null},this.eventSources=[],this.storeMousePositionEvent=t=>{this.mousePos={X:t.pageX,Y:t.pageY}},$(document).on("click contextmenu",".t3js-contextmenutrigger",(t=>{const e=$(t.currentTarget);e.prop("onclick")&&"click"===t.type||(t.preventDefault(),this.show(e.data("table"),e.data("uid"),e.data("context"),e.data("iteminfo"),e.data("parameters"),t.target))})),new ThrottleEvent("mousemove",this.storeMousePositionEvent.bind(this),50).bindTo(document)}static drawActionItem(t){const e=t.additionalAttributes||{};let n="";for(const t of Object.entries(e)){const[e,o]=t;n+=" "+e+'="'+o+'"'}return'<li role="menuitem" class="context-menu-item" tabindex="-1" data-callback-action="'+t.callbackAction+'"'+n+'><span class="context-menu-item-icon">'+t.icon+'</span> <span class="context-menu-item-label">'+t.label+"</span></li>"}static within(t,e,n){const o=t.getBoundingClientRect(),s=window.pageXOffset||document.documentElement.scrollLeft,i=window.pageYOffset||document.documentElement.scrollTop,c=e>=o.left+s&&e<=o.left+s+o.width,a=n>=o.top+i&&n<=o.top+i+o.height;return c&&a}show(t,e,n,o,s,i=null){this.hideAll(),this.record={table:t,uid:e};const c=i.matches("a, button, [tabindex]")?i:i.closest("a, button, [tabindex]");this.eventSources.push(c);let a="";void 0!==t&&(a+="table="+encodeURIComponent(t)),void 0!==e&&(a+=(a.length>0?"&":"")+"uid="+e),void 0!==n&&(a+=(a.length>0?"&":"")+"context="+n),void 0!==o&&(a+=(a.length>0?"&":"")+"enDisItems="+o),void 0!==s&&(a+=(a.length>0?"&":"")+"addParams="+s),this.fetch(a)}initializeContextMenuContainer(){if(0===$("#contentMenu0").length){const t='<div id="contentMenu0" class="context-menu" style="display: none;"></div><div id="contentMenu1" class="context-menu" data-parent="#contentMenu0" style="display: none;"></div>';$("body").append(t),document.querySelectorAll(".context-menu").forEach((t=>{new RegularEvent("mouseenter",(t=>{t.target;this.storeMousePositionEvent(t)})).bindTo(t),new DebounceEvent("mouseleave",(t=>{const e=t.target,n=document.querySelector('[data-parent="#'+e.id+'"]');if(!ContextMenu.within(e,this.mousePos.X,this.mousePos.Y)&&(null===n||null===n.offsetParent)){let t;this.hide("#"+e.id),void 0!==e.dataset.parent&&null!==(t=document.querySelector(e.dataset.parent))&&(ContextMenu.within(t,this.mousePos.X,this.mousePos.Y)||this.hide(e.dataset.parent))}}),500).bindTo(t)}))}}fetch(t){const e=TYPO3.settings.ajaxUrls.contextmenu;new AjaxRequest(e).withQueryArguments(t).get().then((async t=>{const e=await t.resolve();void 0!==t&&Object.keys(t).length>0&&this.populateData(e,0)}))}populateData(t,e){this.initializeContextMenuContainer();const n=$("#contentMenu"+e);if(n.length&&(0===e||$("#contentMenu"+(e-1)).is(":visible"))){const o=this.drawMenu(t,e);n.html('<ul class="context-menu-group" role="menu">'+o+"</ul>"),$("li.context-menu-item",n).on("click",(t=>{t.preventDefault();const n=t.currentTarget;if(n.classList.contains("context-menu-item-submenu"))return void this.openSubmenu(e,$(n),!1);const{callbackAction:o,callbackModule:s,...i}=n.dataset,c=new Proxy($(n),{get(t,e,n){console.warn(`\`this\` being bound to the selected context menu item is marked as deprecated. To access data attributes, use the 3rd argument passed to callback \`${o}\` in \`${s}\`.`);const i=t[e];return i instanceof Function?function(...e){return i.apply(this===n?t:this,e)}:i}});n.dataset.callbackModule?import(s+".js").then((({default:t})=>{t[o].bind(c)(this.record.table,this.record.uid,i)})):ContextMenuActions&&"function"==typeof ContextMenuActions[o]?ContextMenuActions[o].bind(c)(this.record.table,this.record.uid,i):console.log("action: "+o+" not found"),this.hideAll()})),$("li.context-menu-item",n).on("keydown",(t=>{const n=$(t.currentTarget);switch(t.key){case"Down":case"ArrowDown":this.setFocusToNextItem(n.get(0));break;case"Up":case"ArrowUp":this.setFocusToPreviousItem(n.get(0));break;case"Right":case"ArrowRight":if(!n.hasClass("context-menu-item-submenu"))return;this.openSubmenu(e,n,!0);break;case"Home":this.setFocusToFirstItem(n.get(0));break;case"End":this.setFocusToLastItem(n.get(0));break;case"Enter":case"Space":n.click();break;case"Esc":case"Escape":case"Left":case"ArrowLeft":this.hide("#"+n.parents(".context-menu").first().attr("id"));break;case"Tab":this.hideAll();break;default:return}t.preventDefault()})),n.css(this.getPosition(n,!1)).show(),$("li.context-menu-item[tabindex=-1]",n).first().focus()}}setFocusToPreviousItem(t){let e=this.getItemBackward(t.previousElementSibling);e||(e=this.getLastItem(t)),e.focus()}setFocusToNextItem(t){let e=this.getItemForward(t.nextElementSibling);e||(e=this.getFirstItem(t)),e.focus()}setFocusToFirstItem(t){let e=this.getFirstItem(t);e&&e.focus()}setFocusToLastItem(t){let e=this.getLastItem(t);e&&e.focus()}getItemBackward(t){for(;t&&(!t.classList.contains("context-menu-item")||"-1"!==t.getAttribute("tabindex"));)t=t.previousElementSibling;return t}getItemForward(t){for(;t&&(!t.classList.contains("context-menu-item")||"-1"!==t.getAttribute("tabindex"));)t=t.nextElementSibling;return t}getFirstItem(t){return this.getItemForward(t.parentElement.firstElementChild)}getLastItem(t){return this.getItemBackward(t.parentElement.lastElementChild)}openSubmenu(t,e,n){this.eventSources.push(e[0]);const o=$("#contentMenu"+(t+1)).html("");e.next().find(".context-menu-group").clone(!0).appendTo(o),o.css(this.getPosition(o,n)).show(),$(".context-menu-item[tabindex=-1]",o).first().focus()}getPosition(t,e){let n=0,o=0;if(this.eventSources.length&&(null===this.mousePos.X||e)){const t=this.eventSources[this.eventSources.length-1].getBoundingClientRect();n=this.eventSources.length>1?t.right:t.x,o=t.y}else n=this.mousePos.X-1,o=this.mousePos.Y-1;const s=$(window).width()-20,i=$(window).height(),c=t.width(),a=t.height(),u=n-$(document).scrollLeft(),r=o-$(document).scrollTop();return i-a<r&&(r>a?o-=a-10:o+=i-a-r),s-c<u&&(u>c?n-=c-10:s-c-u<$(document).scrollLeft()?n=$(document).scrollLeft():n+=s-c-u),{left:n+"px",top:o+"px"}}drawMenu(t,e){let n="";for(const o of Object.values(t))if("item"===o.type)n+=ContextMenu.drawActionItem(o);else if("divider"===o.type)n+='<li role="separator" class="context-menu-item context-menu-item-divider"></li>';else if("submenu"===o.type||o.childItems){n+='<li role="menuitem" aria-haspopup="true" class="context-menu-item context-menu-item-submenu" tabindex="-1"><span class="context-menu-item-icon">'+o.icon+'</span><span class="context-menu-item-label">'+o.label+'</span><span class="context-menu-item-indicator"><typo3-backend-icon identifier="actions-chevron-right" size="small"></typo3-backend-icon></span></li>';n+='<div class="context-menu contentMenu'+(e+1)+'" style="display:none;"><ul role="menu" class="context-menu-group">'+this.drawMenu(o.childItems,1)+"</ul></div>"}return n}hide(t){$(t).hide();const e=this.eventSources.pop();e&&$(e).focus()}hideAll(){this.hide("#contentMenu0"),this.hide("#contentMenu1")}}export default new ContextMenu;