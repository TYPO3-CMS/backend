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
import HotkeyStorage from"@typo3/backend/hotkeys/hotkey-storage.js";import RegularEvent from"@typo3/core/event/regular-event.js";export var ModifierKeys;!function(e){e.META="meta",e.CTRL="control",e.SHIFT="shift",e.ALT="alt"}(ModifierKeys||(ModifierKeys={}));class Hotkeys{constructor(){this.normalizedCtrlModifierKey=navigator.platform.toLowerCase().startsWith("mac")?ModifierKeys.META:ModifierKeys.CTRL,this.defaultOptions={scope:"all",allowOnEditables:!1,allowRepeat:!1,bindElement:void 0},this.scopedHotkeyMap=HotkeyStorage.getScopedHotkeyMap(),this.setScope("all"),this.registerEventHandler()}setScope(e){HotkeyStorage.activeScope=e}getScope(){return HotkeyStorage.activeScope}register(e,t,o={}){if(0===e.filter((e=>!Object.values(ModifierKeys).includes(e))).length)throw new Error('Attempted to register hotkey "'+e.join("+")+'" without a non-modifier key.');e=e.map((e=>e.toLowerCase()));const r={...this.defaultOptions,...o};this.scopedHotkeyMap.has(r.scope)||this.scopedHotkeyMap.set(r.scope,new Map);let s=this.composeAriaKeyShortcut(e);const i=this.scopedHotkeyMap.get(r.scope),n=this.createHotkeyStructFromTrigger(e),a=JSON.stringify(n);if(i.has(a)){const e=i.get(a);e.options.bindElement?.removeAttribute("aria-keyshortcuts"),i.delete(a)}if(i.set(a,{struct:n,handler:t,options:r}),r.bindElement instanceof Element){const e=r.bindElement.getAttribute("aria-keyshortcuts");null===e||e.includes(s)||(s=e+" "+s),r.bindElement.setAttribute("aria-keyshortcuts",s)}}registerEventHandler(){new RegularEvent("keydown",(e=>{const t=this.findHotkeySetup(e);if(null!==t&&(!e.repeat||t.options.allowRepeat)){if(!t.options.allowOnEditables){const t=e.target;if(t.isContentEditable||["INPUT","TEXTAREA","SELECT"].includes(t.tagName)&&!e.target.readOnly)return}t.handler(e)}})).bindTo(document)}findHotkeySetup(e){const t=[...new Set(["all",HotkeyStorage.activeScope])],o=this.createHotkeyStructFromEvent(e),r=JSON.stringify(o);for(const e of t){const t=this.scopedHotkeyMap.get(e);if(t.has(r))return t.get(r)}return null}createHotkeyStructFromTrigger(e){const t=e.filter((e=>!Object.values(ModifierKeys).includes(e)));if(t.length>1)throw new Error('Cannot register hotkey with more than one non-modifier key, "'+t.join("+")+'" given.');return{modifiers:{meta:e.includes(ModifierKeys.META),ctrl:e.includes(ModifierKeys.CTRL),shift:e.includes(ModifierKeys.SHIFT),alt:e.includes(ModifierKeys.ALT)},key:t[0].toLowerCase()}}createHotkeyStructFromEvent(e){return{modifiers:{meta:e.metaKey,ctrl:e.ctrlKey,shift:e.shiftKey,alt:e.altKey},key:e.key?.toLowerCase()}}composeAriaKeyShortcut(e){const t=[];for(let o of e)o="+"===o?"plus":o.replace(/[\u00A0-\u9999<>&]/g,(e=>"&#"+e.charCodeAt(0)+";")),t.push(o);return t.sort(((e,t)=>{const o=Object.values(ModifierKeys).includes(e),r=Object.values(ModifierKeys).includes(t);return o&&!r?-1:!o&&r?1:o&&r?-1:0})),t.join("+")}}let hotkeysInstance;TYPO3.Hotkeys?hotkeysInstance=TYPO3.Hotkeys:(hotkeysInstance=new Hotkeys,TYPO3.Hotkeys=hotkeysInstance);export default hotkeysInstance;