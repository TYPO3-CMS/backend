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
import e from"@typo3/core/event/regular-event.js";class n{constructor(){this.preventEscapePropagationInSearchInputs()}preventEscapePropagationInSearchInputs(){new e("keydown",t=>{t.key==="Escape"&&t.target instanceof HTMLInputElement&&t.target.type==="search"&&t.target.value!==""&&t.stopPropagation()},!0).bindTo(document)}}var a=new n;export{a as default};
