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
import flatpickr from"flatpickr/flatpickr.min.js";import moment from"moment";import PersistentStorage from"@typo3/backend/storage/persistent.js";import ThrottleEvent from"@typo3/core/event/throttle-event.js";class DateTimePicker{constructor(){this.format=(void 0!==opener?.top?.TYPO3?opener.top:top).TYPO3.settings.DateTimePicker.DateFormat}static formatDateForHiddenField(e,t){return"time"!==t&&"timesec"!==t||e.year(1970).month(0).date(1),e.format()}initialize(e){if(!(e instanceof HTMLInputElement)||void 0!==e.dataset.datepickerInitialized)return;let t=PersistentStorage.get("lang");""===t?t="default":"ch"===t&&(t="zh"),e.dataset.datepickerInitialized="1",import("flatpickr/locales.js").then(()=>{this.initializeField(e,t)})}initializeField(e,t){const a=this.getScrollEvent(),n=this.getDateOptions(e);n.locale=t,n.onOpen=[()=>{a.bindTo(document.querySelector(".t3js-module-body"))}],n.onClose=()=>{a.release()};const r=flatpickr(e,n);e.addEventListener("input",()=>{const e=r._input.value,t=r.parseDate(e);e===r.formatDate(t,r.config.dateFormat)&&r.setDate(e)}),e.addEventListener("keyup",e=>{"Escape"===e.key&&r.close()}),e.addEventListener("change",t=>{t.stopImmediatePropagation();const a=t.target,n=e.parentElement.parentElement.querySelector('input[type="hidden"]');if(""!==a.value){const e=a.dataset.dateType,t=moment.utc(a.value,a._flatpickr.config.dateFormat);t.isValid()?n.value=DateTimePicker.formatDateForHiddenField(t,e):a.value=DateTimePicker.formatDateForHiddenField(moment.utc(n.value),e)}else n.value="";a.dispatchEvent(new Event("formengine.dp.change"))})}getScrollEvent(){return new ThrottleEvent("scroll",()=>{const e=document.querySelector(".flatpickr-input.active");if(null===e)return;const t=e.getBoundingClientRect(),a=e._flatpickr.calendarContainer.offsetHeight;let n,r;window.innerHeight-t.bottom<a&&t.top>a?(n=t.y-a-2,r="arrowBottom"):(n=t.y+t.height+2,r="arrowTop"),e._flatpickr.calendarContainer.style.top=n+"px",e._flatpickr.calendarContainer.classList.remove("arrowBottom","arrowTop"),e._flatpickr.calendarContainer.classList.add(r)},15)}getDateOptions(e){const t=this.format,a=e.dataset.dateType,n={allowInput:!0,dateFormat:"",defaultDate:e.value,enableSeconds:!1,enableTime:!1,formatDate:(e,t)=>moment(e).format(t),parseDate:(e,t)=>moment(e,t,!0).toDate(),maxDate:"",minDate:"",minuteIncrement:1,noCalendar:!1,weekNumbers:!0};switch(a){case"datetime":n.dateFormat=t[1],n.enableTime=!0;break;case"date":n.dateFormat=t[0];break;case"time":n.dateFormat="HH:mm",n.enableTime=!0,n.noCalendar=!0;break;case"timesec":n.dateFormat="HH:mm:ss",n.enableSeconds=!0,n.enableTime=!0,n.noCalendar=!0;break;case"year":n.dateFormat="Y"}return"undefined"!==e.dataset.dateMindate&&(n.minDate=e.dataset.dateMindate),"undefined"!==e.dataset.dateMaxdate&&(n.maxDate=e.dataset.dateMaxdate),n}}export default new DateTimePicker;