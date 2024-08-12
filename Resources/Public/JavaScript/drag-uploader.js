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
import DocumentService from"@typo3/core/document-service.js";import{DateTime}from"luxon";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import{MessageUtility}from"@typo3/backend/utility/message-utility.js";import NProgress from"nprogress";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{default as Modal,Sizes as ModalSizes}from"@typo3/backend/modal.js";import Notification from"@typo3/backend/notification.js";import ImmediateAction from"@typo3/backend/action-button/immediate-action.js";import Md5 from"@typo3/backend/hashing/md5.js";import"@typo3/backend/element/icon-element.js";import RegularEvent from"@typo3/core/event/regular-event.js";import DomHelper from"@typo3/backend/utility/dom-helper.js";import{KeyTypesEnum}from"@typo3/backend/enum/key-types.js";var Action;!function(e){e.OVERRIDE="replace",e.RENAME="rename",e.SKIP="cancel",e.USE_EXISTING="useExisting"}(Action||(Action={}));export default class DragUploader{constructor(e){this.askForOverride=[],this.percentagePerFile=1,this.dragStartedInDocument=!1,this.hideDropzone=e=>{e.stopPropagation(),e.preventDefault(),this.dropzone.setAttribute("hidden","hidden"),this.dropzone.classList.remove("drop-status-ok"),this.manuallyTriggered=!1},this.dragFileIntoDocument=e=>!this.dragStartedInDocument&&(!!e.dataTransfer.types.includes("Files")&&(e.stopPropagation(),e.preventDefault(),e.currentTarget.classList.add("drop-in-progress"),this.element.offsetParent&&this.showDropzone(),!1)),this.dragAborted=e=>(e.stopPropagation(),e.preventDefault(),e.currentTarget.classList.remove("drop-in-progress"),this.dragStartedInDocument=!1,!1),this.ignoreDrop=e=>(e.stopPropagation(),e.preventDefault(),this.dragAborted(e),!1),this.handleDrop=e=>{this.ignoreDrop(e),this.hideDropzone(e),this.processFiles(e.dataTransfer.files)},this.fileInDropzone=()=>{this.dropzone.classList.add("drop-status-ok")},this.fileOutOfDropzone=()=>{this.dropzone.classList.remove("drop-status-ok"),this.manuallyTriggered||this.dropzone.setAttribute("hidden","hidden")},this.body=document.querySelector("body"),this.element=e;const t=void 0!==this.element.dataset.dropzoneTrigger;this.trigger=document.querySelector(this.element.dataset.dropzoneTrigger),this.defaultAction=this.element.dataset.defaultAction||Action.SKIP,this.dropzone=document.createElement("div"),this.dropzone.classList.add("dropzone"),this.dropzone.setAttribute("hidden","hidden"),this.irreObjectUid=this.element.dataset.fileIrreObject;const i=document.querySelector(this.element.dataset.dropzoneTarget);if(this.irreObjectUid&&0!==DomHelper.nextAll(i).length?(this.dropZoneInsertBefore=!0,i.before(this.dropzone)):(this.dropZoneInsertBefore=!1,i.after(this.dropzone)),this.fileInput=document.createElement("input"),this.fileInput.setAttribute("type","file"),this.fileInput.setAttribute("multiple","multiple"),this.fileInput.setAttribute("name","files[]"),this.fileInput.classList.add("upload-file-picker"),this.body.append(this.fileInput),this.fileList=document.querySelector(this.element.dataset.progressContainer),this.fileListColumnCount=this.fileList?.querySelectorAll("thead tr:first-child th").length+1,this.filesExtensionsAllowed=this.element.dataset.fileAllowed,this.filesExtensionsDisallowed=this.element.dataset.fileDisallowed,this.fileDenyPattern=this.element.dataset.fileDenyPattern?new RegExp(this.element.dataset.fileDenyPattern,"i"):null,this.maxFileSize=parseInt(this.element.dataset.maxFileSize,10),this.target=this.element.dataset.targetFolder,this.reloadUrl=this.element.dataset.reloadUrl,this.browserCapabilities={fileReader:"undefined"!=typeof FileReader,DnD:"draggable"in document.createElement("span"),Progress:"upload"in new XMLHttpRequest},!this.browserCapabilities.DnD)return void console.warn("Browser has no Drag and drop capabilities; cannot initialize DragUploader");this.body.addEventListener("dragstart",(()=>{this.dragStartedInDocument=!0})),this.body.addEventListener("dragover",this.dragFileIntoDocument),this.body.addEventListener("dragend",this.dragAborted),this.body.addEventListener("drop",this.ignoreDrop),this.dropzone.innerHTML='<button type="button" class="dropzone-hint" aria-labelledby="dropzone-title"><div class="dropzone-hint-media"><div class="dropzone-hint-icon"></div></div><div class="dropzone-hint-body"><h3 id="dropzone-title" class="dropzone-hint-title">'+TYPO3.lang["file_upload.dropzonehint.title"]+'</h3><p class="dropzone-hint-message">'+TYPO3.lang["file_upload.dropzonehint.message"]+"</p></div></div>",this.dropzoneMask=document.createElement("div"),this.dropzoneMask.classList.add("dropzone-mask"),this.dropzone.append(this.dropzoneMask),this.dropzone.addEventListener("dragenter",this.fileInDropzone),this.dropzoneMask.addEventListener("dragenter",this.fileInDropzone),this.dropzoneMask.addEventListener("dragleave",this.fileOutOfDropzone),this.dropzoneMask.addEventListener("drop",(e=>this.handleDrop(e))),this.dropzone.addEventListener("click",(()=>{this.fileInput.click()}));const s=document.createElement("button");if(s.classList.add("dropzone-close"),s.setAttribute("aria-label",TYPO3.lang["file_upload.dropzone.close"]),s.addEventListener("click",this.hideDropzone),this.dropzone.append(s),null===this.fileList){this.fileList=document.createElement("table"),this.fileList.setAttribute("id","typo3-filelist"),this.fileList.classList.add("table","table-striped","table-hover","upload-queue"),this.fileList.innerHTML="<tbody></tbody>";const e=document.createElement("div");e.classList.add("table-fit"),e.setAttribute("hidden","hidden"),e.append(this.fileList),this.dropZoneInsertBefore?this.dropzone.after(e):this.dropzone.before(e),this.fileListColumnCount=8,this.manualTable=!0}this.fileInput.addEventListener("change",(e=>{this.hideDropzone(e),this.processFiles(this.fileInput.files)})),document.addEventListener("keydown",(e=>{e.key!==KeyTypesEnum.ENTER||this.dropzone.hasAttribute("hidden")||this.hideDropzone(e)})),this.bindUploadButton(!0===t?this.trigger:this.element)}static init(){DocumentService.ready().then((()=>{document.querySelectorAll(".t3js-drag-uploader").forEach((e=>{new DragUploader(e)}))}))}static fileSizeAsString(e){const t=e/1024;let i="";return i=t>1024?(t/1024).toFixed(1)+" MB":t.toFixed(1)+" KB",i}static addFileToIrre(e,t){const i={actionName:"typo3:foreignRelation:insert",objectGroup:e,table:"sys_file",uid:t.uid};MessageUtility.send(i)}showDropzone(){this.dropzone.removeAttribute("hidden")}processFiles(e){this.queueLength=e.length,this.fileList.parentElement.hasAttribute("hidden")&&(this.fileList.parentElement.removeAttribute("hidden"),this.fileList.closest(".t3-filelist-table-container")?.classList.remove("hidden"),this.fileList.closest("form")?.querySelector(".t3-filelist-info-container")?.setAttribute("hidden","hidden")),NProgress.start(),this.percentagePerFile=1/e.length;const t=[];Array.from(e).forEach((e=>{const i=new AjaxRequest(TYPO3.settings.ajaxUrls.file_exists).withQueryArguments({fileName:e.name,fileTarget:this.target}).get({cache:"no-cache"}).then((async t=>{const i=await t.resolve();void 0!==i.uid?(this.askForOverride.push({original:i,uploaded:e,action:this.irreObjectUid?Action.USE_EXISTING:this.defaultAction}),NProgress.inc(this.percentagePerFile)):new FileQueueItem(this,e,Action.SKIP)}));t.push(i)})),Promise.all(t).then((()=>{this.drawOverrideModal(),NProgress.done()})),this.fileInput.value=""}bindUploadButton(e){e.addEventListener("click",(e=>{e.preventDefault(),this.fileInput.click(),this.manuallyTriggered=!0}))}decrementQueueLength(e){if(this.queueLength>0&&(this.queueLength--,0===this.queueLength)){const t=e&&e.length?5e3:0;if(t)for(const t of e)Notification.showMessage(t.title,t.message,t.severity);this.reloadUrl&&setTimeout((()=>{Notification.info(TYPO3.lang["file_upload.reload.filelist"],TYPO3.lang["file_upload.reload.filelist.message"],10,[{label:TYPO3.lang["file_upload.reload.filelist.actions.dismiss"]},{label:TYPO3.lang["file_upload.reload.filelist.actions.reload"],action:new ImmediateAction((()=>{top.list_frame.document.location.href=this.reloadUrl}))}])}),t)}}drawOverrideModal(){const e=Object.keys(this.askForOverride).length;if(0===e)return;const t=document.createElement("div");let i=`\n      <p>${TYPO3.lang["file_upload.existingfiles.description"]}</p>\n      <table class="table">\n        <thead>\n          <tr>\n            <th></th>\n            <th>${TYPO3.lang["file_upload.header.originalFile"]}</th>\n            <th>${TYPO3.lang["file_upload.header.uploadedFile"]}</th>\n            <th>${TYPO3.lang["file_upload.header.action"]}</th>\n          </tr>\n        </thead>\n        <tbody>\n    `;for(let t=0;t<e;++t){i+=`\n        <tr>\n          <td>\n  ${""!==this.askForOverride[t].original.thumbUrl?`<img src="${this.askForOverride[t].original.thumbUrl}" height="40" />`:this.askForOverride[t].original.icon}\n          </td>\n          <td>\n            ${this.askForOverride[t].original.name} (${DragUploader.fileSizeAsString(this.askForOverride[t].original.size)})<br />\n            ${DateTime.fromSeconds(this.askForOverride[t].original.mtime).toLocaleString(DateTime.DATETIME_MED)}\n          </td>\n          <td>\n            ${this.askForOverride[t].uploaded.name} (${DragUploader.fileSizeAsString(this.askForOverride[t].uploaded.size)})<br />\n            ${DateTime.fromMillis(this.askForOverride[t].uploaded.lastModified).toLocaleString(DateTime.DATETIME_MED)}\n          </td>\n          <td>\n            <select class="form-select t3js-actions" data-override="${t}">\n              ${this.irreObjectUid?`<option value="${Action.USE_EXISTING}">${TYPO3.lang["file_upload.actions.use_existing"]}</option>`:""}\n              <option value="${Action.SKIP}" ${this.defaultAction===Action.SKIP?"selected":""}>${TYPO3.lang["file_upload.actions.skip"]}</option>\n              <option value="${Action.RENAME}" ${this.defaultAction===Action.RENAME?"selected":""}>${TYPO3.lang["file_upload.actions.rename"]}</option>\n              <option value="${Action.OVERRIDE}" ${this.defaultAction===Action.OVERRIDE?"selected":""}>${TYPO3.lang["file_upload.actions.override"]}</option>\n            </select>\n          </td>\n        </tr>\n      `}i+="</tbody></table>",t.innerHTML=i;const s=Modal.advanced({title:TYPO3.lang["file_upload.existingfiles.title"],content:t,severity:SeverityEnum.warning,buttons:[{text:TYPO3.lang["file_upload.button.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel"},{text:TYPO3.lang["file_upload.button.continue"]||"Continue with selected actions",btnClass:"btn-warning",name:"continue"}],additionalCssClasses:["modal-inner-scroll"],size:ModalSizes.large,callback:e=>{const t=e.querySelector(".modal-footer"),i=document.createElement("label");i.textContent=TYPO3.lang["file_upload.actions.all.label"];const s=document.createElement("span");s.innerHTML=`\n          <select class="form-select t3js-actions-all">\n            <option value="">${TYPO3.lang["file_upload.actions.all.empty"]}</option>\n            ${this.irreObjectUid?`<option value="${Action.USE_EXISTING}">${TYPO3.lang["file_upload.actions.all.use_existing"]}</option>`:""}\n            <option value="${Action.SKIP}" ${this.defaultAction===Action.SKIP?"selected":""}>${TYPO3.lang["file_upload.actions.all.skip"]}</option>\n            <option value="${Action.RENAME}" ${this.defaultAction===Action.RENAME?"selected":""}>${TYPO3.lang["file_upload.actions.all.rename"]}</option>\n            <option value="${Action.OVERRIDE}" ${this.defaultAction===Action.OVERRIDE?"selected":""}>${TYPO3.lang["file_upload.actions.all.override"]}</option>\n          </select>\n        `,t.prepend(i,s)}});new RegularEvent("change",(e=>{const t=e.currentTarget.value;if(""!==t)for(const e of s.querySelectorAll(".t3js-actions")){const i=parseInt(e.dataset.override,10);e.value=t,e.disabled=!0,this.askForOverride[i].action=e.value}else s.querySelectorAll(".t3js-actions").forEach((e=>e.disabled=!1))})).delegateTo(s,".t3js-actions-all"),new RegularEvent("change",(e=>{const t=e.target,i=parseInt(t.dataset.override,10);this.askForOverride[i].action=t.value})).delegateTo(s,".t3js-actions"),s.addEventListener("button.clicked",(e=>{const t=e.target;if("cancel"===t.name)this.askForOverride=[],Modal.dismiss();else if("continue"===t.name){for(const e of this.askForOverride)e.action===Action.USE_EXISTING?DragUploader.addFileToIrre(this.irreObjectUid,e.original):e.action!==Action.SKIP&&new FileQueueItem(this,e.uploaded,e.action);this.askForOverride=[],s.hideModal()}})),s.addEventListener("typo3-modal-hidden",(()=>{this.askForOverride=[]}))}}class FileQueueItem{constructor(e,t,i){if(this.dragUploader=e,this.file=t,this.override=i,this.row=document.createElement("tr"),this.row.classList.add("upload-queue-item","uploading"),this.dragUploader.manualTable||(this.selector=document.createElement("td"),this.selector.classList.add("col-checkbox"),this.row.append(this.selector)),this.iconCol=document.createElement("td"),this.iconCol.classList.add("col-icon"),this.row.append(this.iconCol),this.fileName=document.createElement("td"),this.fileName.classList.add("col-title","col-responsive"),this.fileName.textContent=t.name,this.row.append(this.fileName),this.progress=document.createElement("td"),this.progress.classList.add("col-progress"),this.progress.setAttribute("colspan",String(this.dragUploader.fileListColumnCount-this.row.querySelectorAll("td").length)),this.row.append(this.progress),this.progressContainer=document.createElement("div"),this.progressContainer.classList.add("upload-queue-progress"),this.progress.append(this.progressContainer),this.progressBar=document.createElement("div"),this.progressBar.classList.add("upload-queue-progress-bar"),this.progressContainer.append(this.progressBar),this.progressPercentage=document.createElement("span"),this.progressPercentage.classList.add("upload-queue-progress-percentage"),this.progressContainer.append(this.progressPercentage),this.progressMessage=document.createElement("span"),this.progressMessage.classList.add("upload-queue-progress-message"),this.progressContainer.append(this.progressMessage),0===this.dragUploader.fileList.querySelectorAll("tbody tr.upload-queue-item").length?(this.dragUploader.fileList.querySelector("tbody").prepend(this.row),this.row.classList.add("last")):this.dragUploader.fileList.querySelector("tbody tr.upload-queue-item:first-child").before(this.row),this.selector&&(this.selector.innerHTML='<span class="form-check form-check-type-toggle"><input type="checkbox" class="form-check-input t3js-multi-record-selection-check" disabled/></span>'),this.iconCol.innerHTML='<typo3-backend-icon identifier="mimetypes-other-other" />',this.dragUploader.maxFileSize>0&&this.file.size>this.dragUploader.maxFileSize)this.updateMessage(TYPO3.lang["file_upload.maxFileSizeExceeded"].replace(/\{0\}/g,this.file.name).replace(/\{1\}/g,DragUploader.fileSizeAsString(this.dragUploader.maxFileSize))),this.row.classList.add("error");else if(this.dragUploader.fileDenyPattern&&this.file.name.match(this.dragUploader.fileDenyPattern))this.updateMessage(TYPO3.lang["file_upload.fileNotAllowed"].replace(/\{0\}/g,this.file.name)),this.row.classList.add("error");else if(this.checkAllowedExtensions())if(this.checkDisallowedExtensions()){this.updateMessage("- "+DragUploader.fileSizeAsString(this.file.size));const e=new FormData;e.append("data[upload][1][target]",this.dragUploader.target),e.append("data[upload][1][data]","1"),e.append("overwriteExistingFiles",this.override),e.append("redirect",""),e.append("upload_1",this.file);const t=new XMLHttpRequest;t.onreadystatechange=()=>{if(t.readyState===XMLHttpRequest.DONE)if(200===t.status)try{const e=JSON.parse(t.responseText);e.hasErrors?this.uploadError(t):this.uploadSuccess(e)}catch{this.uploadError(t)}else this.uploadError(t)},t.upload.addEventListener("progress",(e=>this.updateProgress(e))),t.open("POST",TYPO3.settings.ajaxUrls.file_process),t.send(e)}else this.updateMessage(TYPO3.lang["file_upload.fileExtensionDisallowed"].replace(/\{0\}/g,this.dragUploader.filesExtensionsDisallowed)),this.row.classList.add("error");else this.updateMessage(TYPO3.lang["file_upload.fileExtensionExpected"].replace(/\{0\}/g,this.dragUploader.filesExtensionsAllowed)),this.row.classList.add("error")}updateMessage(e){this.progressMessage.textContent=e}removeProgress(){this.progress&&this.progress.remove()}uploadError(e){const t=TYPO3.lang["file_upload.uploadFailed"].replace(/\{0\}/g,this.file.name);this.updateMessage(t);try{const t=JSON.parse(e.responseText).messages;if(this.progressPercentage.textContent="",t&&t.length)for(const e of t)Notification.showMessage(e.title,e.message,e.severity,10)}catch{}this.row.classList.add("error"),this.dragUploader.decrementQueueLength(),this.dragUploader.trigger?.dispatchEvent(new CustomEvent("uploadError",{detail:[this,e]}))}updateProgress(e){const t=Math.round(e.loaded/e.total*100)+"%";this.progressBar.style.width=t,this.progressPercentage.textContent=t,this.dragUploader.trigger?.dispatchEvent(new CustomEvent("updateProgress",{detail:[this,t,e]}))}uploadSuccess(e){if(e.upload){this.dragUploader.decrementQueueLength(e.messages),this.row.classList.remove("uploading"),this.row.setAttribute("data-type","file"),this.row.setAttribute("data-file-uid",String(e.upload[0].uid)),this.fileName.textContent=e.upload[0].name,this.progressPercentage.textContent="",this.progressMessage.textContent="100%",this.progressBar.style.width="100%";const t=String(e.upload[0].id);if(this.selector){const e=this.selector.querySelector("input");e&&(e.removeAttribute("disabled"),e.setAttribute("name","CBC[_FILE|"+Md5.hash(t)+"]"),e.setAttribute("value",t))}e.upload[0].icon&&(this.iconCol.innerHTML='<button type="button" class="btn btn-link p-0" data-contextmenu-trigger="click" data-contextmenu-uid="'+t+'" data-contextmenu-table="sys_file" aria-label="'+(TYPO3.lang["labels.contextMenu.open"]||"Open context menu")+'">'+e.upload[0].icon+"</span></button>"),this.dragUploader.irreObjectUid?(DragUploader.addFileToIrre(this.dragUploader.irreObjectUid,e.upload[0]),setTimeout((()=>{this.row.remove(),0===this.dragUploader.fileList.querySelectorAll("tr").length&&(this.dragUploader.fileList.setAttribute("hidden","hidden"),this.dragUploader.fileList.closest(".t3-filelist-table-container")?.classList.add("hidden"),this.dragUploader.trigger?.dispatchEvent(new CustomEvent("uploadSuccess",{detail:[this,e]})))}),3e3)):setTimeout((()=>{this.showFileInfo(e.upload[0]),this.dragUploader.trigger?.dispatchEvent(new CustomEvent("uploadSuccess",{detail:[this,e]}))}),3e3)}}showFileInfo(e){if(this.removeProgress(),document.querySelector("#filelist-searchterm")?.value){const t=document.createElement("td");t.textContent=e.path,this.row.append(t)}const t=document.createElement("td");t.classList.add("col-control"),this.row.append(t);const i=document.createElement("td");i.textContent=TYPO3.lang["type.file"]+" ("+e.extension.toUpperCase()+")",this.row.append(i);const s=document.createElement("td");s.textContent=DragUploader.fileSizeAsString(e.size),this.row.append(s);let o="";e.permissions.read&&(o+='<strong class="text-danger">'+TYPO3.lang["permissions.read"]+"</strong>"),e.permissions.write&&(o+='<strong class="text-danger">'+TYPO3.lang["permissions.write"]+"</strong>");const r=document.createElement("td");r.innerHTML=o,this.row.append(r);const n=document.createElement("td");n.textContent="-",this.row.append(n);for(let e=this.row.querySelectorAll("td").length;e<this.dragUploader.fileListColumnCount;e++)this.row.append(document.createElement("td"))}checkAllowedExtensions(){if(!this.dragUploader.filesExtensionsAllowed)return!0;const e=this.file.name.split(".").pop();return this.dragUploader.filesExtensionsAllowed.split(",").includes(e.toLowerCase())}checkDisallowedExtensions(){if(!this.dragUploader.filesExtensionsDisallowed)return!0;const e=this.file.name.split(".").pop();return this.dragUploader.filesExtensionsDisallowed.split(",").includes(e.toLowerCase())}}DragUploader.init();