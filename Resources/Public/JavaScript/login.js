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
import"bootstrap";import"@typo3/backend/input/clearable.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import RegularEvent from"@typo3/core/event/regular-event.js";class BackendLogin{constructor(){this.ready=!0,this.options={error:".t3js-login-error",errorNoCookies:".t3js-login-error-nocookies",errorNoReferrer:".t3js-login-error-noreferrer",formFields:".t3js-login-formfields",interfaceField:".t3js-login-interface-field",loginForm:"#typo3-login-form",loginUrlLink:"t3js-login-url",submitButton:".t3js-login-submit",submitHandler:null,useridentField:".t3js-login-userident-field"},this.checkLoginRefresh(),this.checkCookieSupport(),this.checkForInterfaceCookie(),this.checkDocumentReferrerSupport(),this.initializeEvents(),top.location.href!==location.href&&(this.ready=!1,top.location.href=location.href),this.ready&&document.body.setAttribute("data-typo3-login-ready","true")}showLoginProcess(){this.showLoadingIndicator(),document.querySelector(this.options.error)?.classList.add("hidden"),document.querySelector(this.options.errorNoCookies)?.classList.add("hidden")}showLoadingIndicator(){const e=document.querySelector(this.options.submitButton);e.innerHTML=e.dataset.loadingText}handleSubmit(e){this.showLoginProcess(),"function"==typeof this.options.submitHandler&&this.options.submitHandler(e)}interfaceSelectorChanged(){const e=new Date,o=new Date(e.getTime()+31536e6);document.cookie="typo3-login-interface="+document.querySelector(this.options.interfaceField).value+"; expires="+o.toUTCString()+";"}checkForInterfaceCookie(){const e=document.querySelector(this.options.interfaceField);if(null!==e){const o=document.cookie.indexOf("typo3-login-interface=");if(-1!==o){let t=document.cookie.substring(o+22);t=t.substring(0,t.indexOf(";")),e.value=t}}}checkDocumentReferrerSupport(){const e=document.getElementById(this.options.loginUrlLink);null!==e&&void 0===e.dataset.referrerCheckEnabled&&"1"!==e.dataset.referrerCheckEnabled||void 0!==TYPO3.settings&&void 0!==TYPO3.settings.ajaxUrls&&new AjaxRequest(TYPO3.settings.ajaxUrls.login_preflight).get().then(async e=>{!0!==(await e.resolve("application/json")).capabilities.referrer&&document.querySelectorAll(this.options.errorNoReferrer).forEach(e=>e.classList.remove("hidden"))})}showCookieWarning(){document.querySelector(this.options.formFields)?.classList.add("hidden"),document.querySelector(this.options.errorNoCookies)?.classList.remove("hidden")}hideCookieWarning(){document.querySelector(this.options.formFields)?.classList.remove("hidden"),document.querySelector(this.options.errorNoCookies)?.classList.add("hidden")}checkLoginRefresh(){const e=document.querySelector(this.options.loginForm+' input[name="loginRefresh"]');e instanceof HTMLInputElement&&e.value&&window.opener&&window.opener.TYPO3&&window.opener.TYPO3.LoginRefresh&&(window.opener.TYPO3.LoginRefresh.startTask(),window.close())}checkCookieSupport(){const e=navigator.cookieEnabled;!1===e?this.showCookieWarning():document.cookie||null!==e||(document.cookie="typo3-login-cookiecheck=1",document.cookie?document.cookie="typo3-login-cookiecheck=; expires="+new Date(0).toUTCString():this.showCookieWarning())}initializeEvents(){new RegularEvent("submit",this.handleSubmit.bind(this)).bindTo(document.querySelector(this.options.loginForm));const e=document.querySelector(this.options.interfaceField);null!==e&&new RegularEvent("change blur",this.interfaceSelectorChanged.bind(this)).bindTo(e),document.querySelectorAll(".t3js-clearable").forEach(e=>e.clearable()),this.registerNewsCarouselEvents()}registerNewsCarouselEvents(){const e=document.querySelector(".t3js-login-news-carousel .carousel-inner");if(null!==e){const o=e.closest(".t3js-login-news-carousel");this.calculateScrollbarWidth(e),new RegularEvent("scroll",e=>{const o=e.currentTarget;this.setOverflowClasses(o)}).bindTo(e),new RegularEvent("slid.bs.carousel",()=>{e.scrollTop=0,this.setOverflowClasses(e),this.calculateScrollbarWidth(e)}).bindTo(o),this.setOverflowClasses(e)}}calculateScrollbarWidth(e){const o=e.offsetWidth-e.clientWidth;e.setAttribute("style","--scrollbar-width: "+o+"px")}setOverflowClasses(e){let o;!function(e){e.top="carousel-inner--overflowing-top",e.bottom="carousel-inner--overflowing-bottom"}(o||(o={}));if(!(e.scrollHeight>e.clientHeight))return void e.classList.remove(o.bottom,o.top);const t=e.scrollHeight<=e.clientHeight+e.scrollTop,i=0===e.scrollTop;e.classList.toggle(o.bottom,!t),e.classList.toggle(o.top,!i)}}export default new BackendLogin;