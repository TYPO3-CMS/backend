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
import{SeverityEnum}from"@typo3/backend/enum/severity.js";import $ from"jquery";import{Carousel}from"bootstrap";import Modal from"@typo3/backend/modal.js";import Severity from"@typo3/backend/severity.js";import Icons from"@typo3/backend/icons.js";class MultiStepWizard{constructor(){this.setup={slides:[],settings:{},forceSelection:!0,$carousel:null,carousel:null},this.originalSetup=$.extend(!0,{},this.setup)}set(t,e){return this.setup.settings[t]=e,this}addSlide(t,e,s="",i=SeverityEnum.info,r,a){const l={identifier:t,title:e,content:s,severity:i,progressBarTitle:r,callback:a};return this.setup.slides.push(l),this}addFinalProcessingSlide(t){return t||(t=()=>{this.dismiss()}),Icons.getIcon("spinner-circle",Icons.sizes.default,null,null).then((e=>{const s=$("<div />",{class:"text-center"}).append(e);this.addSlide("final-processing-slide",top.TYPO3.lang["wizard.processing.title"],s[0].outerHTML,Severity.info,null,t)}))}show(){const t=this.generateSlides(),e=this.setup.slides[0];Modal.advanced({title:e.title,content:t,severity:e.severity,staticBackdrop:!0,buttons:[{text:top.TYPO3.lang["wizard.button.cancel"],active:!0,btnClass:"btn-default float-start",name:"cancel",trigger:()=>{this.getComponent().trigger("wizard-dismiss")}},{text:top.TYPO3.lang["wizard.button.prev"],btnClass:"btn-"+Severity.getCssClass(e.severity),name:"prev"},{text:top.TYPO3.lang["wizard.button.next"],btnClass:"btn-"+Severity.getCssClass(e.severity),name:"next"}],additionalCssClasses:["modal-multi-step-wizard"],callback:t=>{this.setup.carousel=new Carousel(t.querySelector(".carousel")),this.addButtonContainer(),this.addProgressBar(),this.initializeEvents()}}),this.getComponent().on("wizard-visible",(()=>{this.runSlideCallback(e,this.setup.$carousel.find(".carousel-item").first())})).on("wizard-dismissed",(()=>{this.setup=$.extend(!0,{},this.originalSetup)}))}getComponent(){return null===this.setup.$carousel&&this.generateSlides(),this.setup.$carousel}dismiss(){Modal.dismiss()}lockNextStep(){const t=this.setup.$carousel.closest(".modal").find('button[name="next"]');return t.prop("disabled",!0),t}unlockNextStep(){const t=this.setup.$carousel.closest(".modal").find('button[name="next"]');return t.prop("disabled",!1),t}lockPrevStep(){const t=this.setup.$carousel.closest(".modal").find('button[name="prev"]');return t.prop("disabled",!0),t}unlockPrevStep(){const t=this.setup.$carousel.closest(".modal").find('button[name="prev"]');return t.prop("disabled",!1),t}triggerStepButton(t){const e=this.setup.$carousel.closest(".modal").find('button[name="'+t+'"]');return e.length>0&&!0!==e.prop("disabled")&&e.get(0).click(),e}blurCancelStep(){const t=this.setup.$carousel.closest(".modal").find('button[name="cancel"]');return t.trigger("blur"),t}initializeEvents(){const t=this.setup.$carousel.closest(".modal");this.initializeSlideNextEvent(t),this.initializeSlidePrevEvent(t),this.setup.$carousel.get(0).addEventListener("slide.bs.carousel",(e=>{"left"===e.direction?this.nextSlideChanges(t):this.prevSlideChanges(t)})),this.setup.$carousel.get(0).addEventListener("slid.bs.carousel",(t=>{const e=this.setup.$carousel.data("currentIndex"),s=this.setup.slides[e];this.runSlideCallback(s,$(t.relatedTarget)),this.setup.forceSelection&&this.lockNextStep()}));const e=this.getComponent();e.on("wizard-dismiss",this.dismiss),Modal.currentModal.addEventListener("typo3-modal-hidden",(()=>{e.trigger("wizard-dismissed")})),Modal.currentModal.addEventListener("typo3-modal-shown",(()=>{e.trigger("wizard-visible")}))}initializeSlideNextEvent(t){t.find(".modal-footer").find('button[name="next"]').off().on("click",(()=>{this.setup.carousel.next()}))}initializeSlidePrevEvent(t){t.find(".modal-footer").find('button[name="prev"]').off().on("click",(()=>{this.setup.carousel.prev()}))}nextSlideChanges(t){this.initializeSlideNextEvent(t);const e=t.find(".modal-title"),s=t.find(".modal-footer"),i=this.setup.$carousel.data("currentSlide")+1,r=this.setup.$carousel.data("currentIndex"),a=r+1;e.text(this.setup.slides[a].title),this.setup.$carousel.data("currentSlide",i),this.setup.$carousel.data("currentIndex",a);const l=s.find(".progress-bar");l.eq(r).width("0%"),l.eq(a).width(this.setup.$carousel.data("initialStep")*i+"%").removeClass("inactive"),this.updateCurrentSeverity(t,r,a)}prevSlideChanges(t){this.initializeSlidePrevEvent(t);const e=t.find(".modal-title"),s=t.find(".modal-footer"),i=s.find('button[name="next"]'),r=this.setup.$carousel.data("currentSlide")-1,a=this.setup.$carousel.data("currentIndex"),l=a-1;this.setup.$carousel.data("currentSlide",r),this.setup.$carousel.data("currentIndex",l),e.text(this.setup.slides[l].title),s.find(".progress-bar.last-step").width(this.setup.$carousel.data("initialStep")+"%").text(this.getProgressBarTitle(this.setup.$carousel.data("slideCount")-1)),i.text(top.TYPO3.lang["wizard.button.next"]);const n=s.find(".progress-bar");n.eq(a).width(this.setup.$carousel.data("initialStep")+"%").addClass("inactive"),n.eq(l).width(this.setup.$carousel.data("initialStep")*r+"%").removeClass("inactive"),this.updateCurrentSeverity(t,a,l)}updateCurrentSeverity(t,e,s){t.find(".modal-footer").find('button[name="next"]').removeClass("btn-"+Severity.getCssClass(this.setup.slides[e].severity)).addClass("btn-"+Severity.getCssClass(this.setup.slides[s].severity)),t.removeClass("modal-severity-"+Severity.getCssClass(this.setup.slides[e].severity)).addClass("modal-severity-"+Severity.getCssClass(this.setup.slides[s].severity))}getProgressBarTitle(t){let e;return e=null===this.setup.slides[t].progressBarTitle?0===t?top.TYPO3.lang["wizard.progressStep.start"]:t>=this.setup.$carousel.data("slideCount")-1?top.TYPO3.lang["wizard.progressStep.finish"]:top.TYPO3.lang["wizard.progressStep"]+String(t+1):this.setup.slides[t].progressBarTitle,e}runSlideCallback(t,e){"function"==typeof t.callback&&t.callback(e,this.setup.settings,t.identifier)}addProgressBar(){const t=this.setup.$carousel.find(".carousel-item").length,e=Math.max(1,t),s=Math.round(100/e),i=this.setup.$carousel.closest(".modal").find(".modal-footer");if(this.setup.$carousel.data("initialStep",s).data("slideCount",e).data("realSlideCount",t).data("currentIndex",0).data("currentSlide",1),e>1){i.prepend($("<div />",{class:"progress"}));for(let t=0;t<this.setup.slides.length;++t){let e;e=0===t?"progress-bar first-step":t===this.setup.$carousel.data("slideCount")-1?"progress-bar last-step inactive":"progress-bar step inactive",i.find(".progress").append($("<div />",{role:"progressbar",class:e,"aria-valuemin":0,"aria-valuenow":s,"aria-valuemax":100}).width(s+"%").text(this.getProgressBarTitle(t)))}}}addButtonContainer(){this.setup.$carousel.closest(".modal").find(".modal-footer .btn").wrapAll('<div class="modal-btn-group" />')}generateSlides(){if(null!==this.setup.$carousel)return this.setup.$carousel;let t='<div class="carousel slide" data-bs-ride="false"><div class="carousel-inner" role="listbox">';for(let e=0;e<this.setup.slides.length;++e){const s=this.setup.slides[e];let i=s.content;"object"==typeof i&&(i=i.html()),t+='<div class="carousel-item" data-bs-slide="'+s.identifier+'" data-step="'+e+'">'+i+"</div>"}return t+="</div></div>",this.setup.$carousel=$(t),this.setup.$carousel.find(".carousel-item").first().addClass("active"),this.setup.$carousel}}let multistepWizardObject;try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.MultiStepWizard&&(multistepWizardObject=window.opener.TYPO3.MultiStepWizard),parent&&parent.window.TYPO3&&parent.window.TYPO3.MultiStepWizard&&(multistepWizardObject=parent.window.TYPO3.MultiStepWizard),top&&top.TYPO3&&top.TYPO3.MultiStepWizard&&(multistepWizardObject=top.TYPO3.MultiStepWizard)}catch(t){}multistepWizardObject||(multistepWizardObject=new MultiStepWizard,"undefined"!=typeof TYPO3&&(TYPO3.MultiStepWizard=multistepWizardObject));export default multistepWizardObject;