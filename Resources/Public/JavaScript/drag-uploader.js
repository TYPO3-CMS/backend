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
import E from"@typo3/core/document-service.js";import{DateTime as c}from"luxon";import{SeverityEnum as h}from"@typo3/backend/enum/severity.js";import{MessageUtility as v}from"@typo3/backend/utility/message-utility.js";import u from"nprogress";import y from"@typo3/core/ajax/ajax-request.js";import g,{Sizes as L}from"@typo3/backend/modal.js";import f from"@typo3/backend/notification.js";import T from"@typo3/backend/action-button/immediate-action.js";import O from"@typo3/backend/hashing/md5.js";import"@typo3/backend/element/icon-element.js";import m from"@typo3/core/event/regular-event.js";import w from"@typo3/backend/utility/dom-helper.js";import{KeyTypesEnum as S}from"@typo3/backend/enum/key-types.js";import"@typo3/backend/element/progress-bar-element.js";var l;(function(p){p.OVERRIDE="replace",p.RENAME="rename",p.SKIP="cancel",p.USE_EXISTING="useExisting"})(l||(l={}));class n{constructor(t){this.askForOverride=[],this.percentagePerFile=1,this.dragStartedInDocument=!1,this.hideDropzone=e=>{e.stopPropagation(),e.preventDefault(),this.dropzone.setAttribute("hidden","hidden"),this.dropzone.classList.remove("drop-status-ok"),this.manuallyTriggered=!1},this.dragFileIntoDocument=e=>(this.dragStartedInDocument||!e.dataTransfer.types.includes("Files")||(e.stopPropagation(),e.preventDefault(),e.currentTarget.classList.add("drop-in-progress"),this.element.offsetParent&&this.showDropzone()),!1),this.dragAborted=e=>(e.stopPropagation(),e.preventDefault(),e.currentTarget.classList.remove("drop-in-progress"),this.dragStartedInDocument=!1,!1),this.ignoreDrop=e=>(e.stopPropagation(),e.preventDefault(),this.dragAborted(e),!1),this.handleDrop=e=>{this.ignoreDrop(e),this.hideDropzone(e),this.processFiles(e.dataTransfer.files)},this.fileInDropzone=()=>{this.dropzone.classList.add("drop-status-ok")},this.fileOutOfDropzone=()=>{this.dropzone.classList.remove("drop-status-ok"),this.manuallyTriggered||this.dropzone.setAttribute("hidden","hidden")},this.body=document.querySelector("body"),this.element=t;const i=this.element.dataset.dropzoneTrigger!==void 0;this.trigger=document.querySelector(this.element.dataset.dropzoneTrigger),this.defaultAction=this.element.dataset.defaultAction||l.SKIP,this.dropzone=document.createElement("div"),this.dropzone.classList.add("dropzone"),this.dropzone.setAttribute("hidden","hidden"),this.irreObjectUid=this.element.dataset.fileIrreObject;const s=document.querySelector(this.element.dataset.dropzoneTarget);if(this.irreObjectUid&&w.nextAll(s).length!==0?(this.dropZoneInsertBefore=!0,s.before(this.dropzone)):(this.dropZoneInsertBefore=!1,s.after(this.dropzone)),this.fileInput=document.createElement("input"),this.fileInput.setAttribute("type","file"),this.fileInput.setAttribute("multiple","multiple"),this.fileInput.setAttribute("name","files[]"),this.fileInput.classList.add("upload-file-picker"),this.body.append(this.fileInput),this.fileList=document.querySelector(this.element.dataset.progressContainer),this.fileListColumnCount=this.fileList?.querySelectorAll("thead tr:first-child th").length+1,this.filesExtensionsAllowed=this.element.dataset.fileAllowed,this.filesExtensionsDisallowed=this.element.dataset.fileDisallowed,this.fileDenyPattern=this.element.dataset.fileDenyPattern?new RegExp(this.element.dataset.fileDenyPattern,"i"):null,this.maxFileSize=parseInt(this.element.dataset.maxFileSize,10),this.target=this.element.dataset.targetFolder,this.reloadUrl=this.element.dataset.reloadUrl,this.browserCapabilities={fileReader:typeof FileReader<"u",DnD:"draggable"in document.createElement("span"),Progress:"upload"in new XMLHttpRequest},!this.browserCapabilities.DnD){console.warn("Browser has no Drag and drop capabilities; cannot initialize DragUploader");return}this.body.addEventListener("dragstart",()=>{this.dragStartedInDocument=!0}),this.body.addEventListener("dragover",this.dragFileIntoDocument),this.body.addEventListener("dragend",this.dragAborted),this.body.addEventListener("drop",this.ignoreDrop),this.dropzone.innerHTML='<button type="button" class="dropzone-hint" aria-labelledby="dropzone-title"><div class="dropzone-hint-media"><div class="dropzone-hint-icon"></div></div><div class="dropzone-hint-body"><h3 id="dropzone-title" class="dropzone-hint-title">'+TYPO3.lang["file_upload.dropzonehint.title"]+'</h3><p class="dropzone-hint-message">'+TYPO3.lang["file_upload.dropzonehint.message"]+"</p></div></div>",this.dropzoneMask=document.createElement("div"),this.dropzoneMask.classList.add("dropzone-mask"),this.dropzone.append(this.dropzoneMask),this.dropzone.addEventListener("dragenter",this.fileInDropzone),this.dropzoneMask.addEventListener("dragenter",this.fileInDropzone),this.dropzoneMask.addEventListener("dragleave",this.fileOutOfDropzone),this.dropzoneMask.addEventListener("drop",e=>this.handleDrop(e)),this.dropzone.addEventListener("click",()=>{this.fileInput.click()});const o=document.createElement("button");if(o.classList.add("dropzone-close"),o.type="button",o.setAttribute("aria-label",TYPO3.lang["file_upload.dropzone.close"]),o.addEventListener("click",this.hideDropzone),this.dropzone.append(o),this.fileList===null){this.fileList=document.createElement("table"),this.fileList.setAttribute("id","typo3-filelist"),this.fileList.classList.add("table","table-striped","table-hover","upload-queue"),this.fileList.innerHTML="<tbody></tbody>";const e=document.createElement("div");e.classList.add("table-fit"),e.setAttribute("hidden","hidden"),e.append(this.fileList),this.dropZoneInsertBefore?this.dropzone.after(e):this.dropzone.before(e),this.fileListColumnCount=8,this.manualTable=!0}this.fileInput.addEventListener("change",e=>{this.hideDropzone(e),this.processFiles(this.fileInput.files)}),document.addEventListener("keydown",e=>{e.key===S.ENTER&&!this.dropzone.hasAttribute("hidden")&&this.hideDropzone(e)}),this.bindUploadButton(i===!0?this.trigger:this.element)}static init(){new MutationObserver(i=>{for(const s of i)if(s.type==="childList")for(const o of[...s.addedNodes.values()])o instanceof HTMLElement&&(o.matches(".t3js-drag-uploader")?new n(o):o.querySelectorAll(".t3js-drag-uploader").forEach(e=>{new n(e)}))}).observe(document,{childList:!0,subtree:!0}),E.ready().then(()=>{document.querySelectorAll(".t3js-drag-uploader").forEach(i=>{new n(i)})})}static fileSizeAsString(t){const i=t/1024;let s="";return i>1024?s=(i/1024).toFixed(1)+" MB":s=i.toFixed(1)+" KB",s}static addFileToIrre(t,i){const s={actionName:"typo3:foreignRelation:insert",objectGroup:t,table:"sys_file",uid:i.uid};v.send(s)}showDropzone(){this.dropzone.removeAttribute("hidden")}processFiles(t){this.queueLength=t.length,this.fileList.parentElement.hasAttribute("hidden")&&(this.fileList.parentElement.removeAttribute("hidden"),this.fileList.closest(".t3-filelist-container")?.classList.remove("hidden"),this.fileList.closest(".filelist-main")?.querySelector(".t3-filelist-info-container")?.setAttribute("hidden","hidden")),u.start(),this.percentagePerFile=1/t.length;const i=[];Array.from(t).forEach(s=>{const o=new y(TYPO3.settings.ajaxUrls.file_exists).withQueryArguments({fileName:s.name,fileTarget:this.target}).get({cache:"no-cache"}).then(async e=>{const r=await e.resolve();typeof r.uid<"u"?(this.askForOverride.push({original:r,uploaded:s,action:this.irreObjectUid?l.USE_EXISTING:this.defaultAction}),u.inc(this.percentagePerFile)):new b(this,s,l.SKIP)});i.push(o)}),Promise.all(i).then(()=>{this.drawOverrideModal(),u.done()}),this.fileInput.value=""}bindUploadButton(t){t.addEventListener("click",i=>{i.preventDefault(),this.fileInput.click(),this.manuallyTriggered=!0})}decrementQueueLength(t){if(this.queueLength>0&&(this.queueLength--,this.queueLength===0)){const i=t&&t.length?5e3:0;if(i)for(const s of t)f.showMessage(s.title,s.message,s.severity);this.reloadUrl&&setTimeout(()=>{f.info(TYPO3.lang["file_upload.reload.filelist"],TYPO3.lang["file_upload.reload.filelist.message"],10,[{label:TYPO3.lang["file_upload.reload.filelist.actions.dismiss"]},{label:TYPO3.lang["file_upload.reload.filelist.actions.reload"],action:new T(()=>{top.list_frame.document.location.href=this.reloadUrl})}])},i)}}drawOverrideModal(){const t=Object.keys(this.askForOverride).length;if(t===0)return;const i=document.createElement("div");let s=`
      <p>${TYPO3.lang["file_upload.existingfiles.description"]}</p>
      <table class="table">
        <thead>
          <tr>
            <th></th>
            <th>${TYPO3.lang["file_upload.header.originalFile"]}</th>
            <th>${TYPO3.lang["file_upload.header.uploadedFile"]}</th>
            <th>${TYPO3.lang["file_upload.header.action"]}</th>
          </tr>
        </thead>
        <tbody>
    `;for(let e=0;e<t;++e){const r=`
        <tr>
          <td>
  ${this.askForOverride[e].original.thumbUrl!==""?`<img src="${this.askForOverride[e].original.thumbUrl}" height="40" />`:this.askForOverride[e].original.icon}
          </td>
          <td>
            ${this.askForOverride[e].original.name} (${n.fileSizeAsString(this.askForOverride[e].original.size)})<br />
            ${c.fromSeconds(this.askForOverride[e].original.mtime).toLocaleString(c.DATETIME_MED)}
          </td>
          <td>
            ${this.askForOverride[e].uploaded.name} (${n.fileSizeAsString(this.askForOverride[e].uploaded.size)})<br />
            ${c.fromMillis(this.askForOverride[e].uploaded.lastModified).toLocaleString(c.DATETIME_MED)}
          </td>
          <td>
            <select class="form-select t3js-actions" data-override="${e}">
              ${this.irreObjectUid?`<option value="${l.USE_EXISTING}">${TYPO3.lang["file_upload.actions.use_existing"]}</option>`:""}
              <option value="${l.SKIP}" ${this.defaultAction===l.SKIP?"selected":""}>${TYPO3.lang["file_upload.actions.skip"]}</option>
              <option value="${l.RENAME}" ${this.defaultAction===l.RENAME?"selected":""}>${TYPO3.lang["file_upload.actions.rename"]}</option>
              <option value="${l.OVERRIDE}" ${this.defaultAction===l.OVERRIDE?"selected":""}>${TYPO3.lang["file_upload.actions.override"]}</option>
            </select>
          </td>
        </tr>
      `;s+=r}s+="</tbody></table>",i.innerHTML=s;const o=g.advanced({title:TYPO3.lang["file_upload.existingfiles.title"],content:i,severity:h.warning,buttons:[{text:TYPO3.lang["file_upload.button.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel"},{text:TYPO3.lang["file_upload.button.continue"]||"Continue with selected actions",btnClass:"btn-warning",name:"continue"}],additionalCssClasses:["modal-inner-scroll"],size:L.large,callback:e=>{const r=e.querySelector(".modal-footer"),a=document.createElement("label");a.textContent=TYPO3.lang["file_upload.actions.all.label"];const d=document.createElement("span");d.innerHTML=`
          <select class="form-select t3js-actions-all">
            <option value="">${TYPO3.lang["file_upload.actions.all.empty"]}</option>
            ${this.irreObjectUid?`<option value="${l.USE_EXISTING}">${TYPO3.lang["file_upload.actions.all.use_existing"]}</option>`:""}
            <option value="${l.SKIP}" ${this.defaultAction===l.SKIP?"selected":""}>${TYPO3.lang["file_upload.actions.all.skip"]}</option>
            <option value="${l.RENAME}" ${this.defaultAction===l.RENAME?"selected":""}>${TYPO3.lang["file_upload.actions.all.rename"]}</option>
            <option value="${l.OVERRIDE}" ${this.defaultAction===l.OVERRIDE?"selected":""}>${TYPO3.lang["file_upload.actions.all.override"]}</option>
          </select>
        `,r.prepend(a,d)}});new m("change",(e,r)=>{if(r.value!=="")for(const a of o.querySelectorAll(".t3js-actions")){const d=parseInt(a.dataset.override,10);a.value=r.value,a.disabled=!0,this.askForOverride[d].action=a.value}else o.querySelectorAll(".t3js-actions").forEach(a=>a.disabled=!1)}).delegateTo(o,".t3js-actions-all"),new m("change",e=>{const r=e.target,a=parseInt(r.dataset.override,10);this.askForOverride[a].action=r.value}).delegateTo(o,".t3js-actions"),o.addEventListener("button.clicked",e=>{const r=e.target;if(r.name==="cancel")this.askForOverride=[],g.dismiss();else if(r.name==="continue"){for(const a of this.askForOverride)a.action===l.USE_EXISTING?n.addFileToIrre(this.irreObjectUid,a.original):a.action!==l.SKIP&&new b(this,a.uploaded,a.action);this.askForOverride=[],o.hideModal()}}),o.addEventListener("typo3-modal-hidden",()=>{this.askForOverride=[]})}}class b{constructor(t,i,s){if(this.dragUploader=t,this.file=i,this.override=s,this.row=document.createElement("tr"),this.row.classList.add("upload-queue-item"),this.dragUploader.manualTable||(this.selector=document.createElement("td"),this.selector.classList.add("col-checkbox"),this.row.append(this.selector)),this.iconCol=document.createElement("td"),this.iconCol.classList.add("col-icon"),this.row.append(this.iconCol),this.fileName=document.createElement("td"),this.fileName.classList.add("col-title","col-responsive"),this.fileName.textContent=i.name,this.row.append(this.fileName),this.progress=document.createElement("td"),this.progress.classList.add("col-progress"),this.progress.setAttribute("colspan",String(this.dragUploader.fileListColumnCount-this.row.querySelectorAll("td").length)),this.row.append(this.progress),this.progressBar=document.createElement("typo3-backend-progress-bar"),this.progress.append(this.progressBar),this.dragUploader.fileList.querySelectorAll("tbody tr.upload-queue-item").length===0?(this.dragUploader.fileList.querySelector("tbody").prepend(this.row),this.row.classList.add("last")):this.dragUploader.fileList.querySelector("tbody tr.upload-queue-item:first-child").before(this.row),this.selector&&(this.selector.innerHTML='<span class="form-check form-check-type-toggle"><input type="checkbox" class="form-check-input t3js-multi-record-selection-check" disabled/></span>'),this.iconCol.innerHTML='<typo3-backend-icon identifier="mimetypes-other-other" />',this.dragUploader.maxFileSize>0&&this.file.size>this.dragUploader.maxFileSize)this.updateMessage(TYPO3.lang["file_upload.maxFileSizeExceeded"].replace(/\{0\}/g,this.file.name).replace(/\{1\}/g,n.fileSizeAsString(this.dragUploader.maxFileSize))),this.progressBar.value=100,this.progressBar.severity=h.error;else if(this.dragUploader.fileDenyPattern&&this.file.name.match(this.dragUploader.fileDenyPattern))this.updateMessage(TYPO3.lang["file_upload.fileNotAllowed"].replace(/\{0\}/g,this.file.name)),this.progressBar.value=100,this.progressBar.severity=h.error;else if(!this.checkAllowedExtensions())this.updateMessage(TYPO3.lang["file_upload.fileExtensionExpected"].replace(/\{0\}/g,this.dragUploader.filesExtensionsAllowed)),this.progressBar.value=100,this.progressBar.severity=h.error;else if(!this.checkDisallowedExtensions())this.updateMessage(TYPO3.lang["file_upload.fileExtensionDisallowed"].replace(/\{0\}/g,this.dragUploader.filesExtensionsDisallowed)),this.progressBar.value=100,this.progressBar.severity=h.error;else{this.updateMessage("- "+n.fileSizeAsString(this.file.size));const o=new FormData;o.append("data[upload][1][target]",this.dragUploader.target),o.append("data[upload][1][data]","1"),o.append("overwriteExistingFiles",this.override),o.append("redirect",""),o.append("upload_1",this.file);const e=new XMLHttpRequest;e.onreadystatechange=()=>{if(e.readyState===XMLHttpRequest.DONE)if(e.status===200)try{const r=JSON.parse(e.responseText);r.hasErrors?this.uploadError(e):this.uploadSuccess(r)}catch{this.uploadError(e)}else this.uploadError(e)},e.upload.addEventListener("progress",r=>this.updateProgress(r)),e.open("POST",TYPO3.settings.ajaxUrls.file_process),e.send(o)}}updateMessage(t){this.progressBar.label=t}removeProgress(){this.progress&&this.progress.remove()}uploadError(t){const i=TYPO3.lang["file_upload.uploadFailed"].replace(/\{0\}/g,this.file.name);this.updateMessage(i);try{const o=JSON.parse(t.responseText).messages;if(o&&o.length)for(const e of o)f.showMessage(e.title,e.message,e.severity,10)}catch{}this.progressBar.severity=h.error,this.dragUploader.decrementQueueLength(),this.dragUploader.trigger?.dispatchEvent(new CustomEvent("uploadError",{detail:[this,t]}))}updateProgress(t){const i=Math.round(t.loaded/t.total*100);this.progressBar.value=i,this.progressBar.label=`${TYPO3.lang["file_upload.upload-in-progress"]} ${i}%`,this.dragUploader.trigger?.dispatchEvent(new CustomEvent("updateProgress",{detail:[this,i,t]}))}uploadSuccess(t){if(t.upload){this.dragUploader.decrementQueueLength(t.messages),this.row.setAttribute("data-type","file"),this.row.setAttribute("data-file-uid",String(t.upload[0].uid)),this.fileName.textContent=t.upload[0].name,this.progressBar.value=100,this.progressBar.label=TYPO3.lang["file_upload.uploadSucceeded"],this.progressBar.severity=h.ok;const i=String(t.upload[0].id);if(this.selector){const s=this.selector.querySelector("input");s&&(s.removeAttribute("disabled"),s.setAttribute("name","CBC[_FILE|"+O.hash(i)+"]"),s.setAttribute("value",i))}t.upload[0].icon&&(this.iconCol.innerHTML='<button type="button" class="btn btn-link" data-contextmenu-trigger="click" data-contextmenu-uid="'+i+'" data-contextmenu-table="sys_file" aria-label="'+(TYPO3.lang["labels.contextMenu.open"]||"Open context menu")+'">'+t.upload[0].icon+"</span></button>"),this.dragUploader.irreObjectUid?(n.addFileToIrre(this.dragUploader.irreObjectUid,t.upload[0]),setTimeout(()=>{this.row.remove(),this.dragUploader.fileList.querySelectorAll("tr").length===0&&(this.dragUploader.fileList.setAttribute("hidden","hidden"),this.dragUploader.fileList.closest(".t3-filelist-container")?.classList.add("hidden"),this.dragUploader.trigger?.dispatchEvent(new CustomEvent("uploadSuccess",{detail:[this,t]})))},3e3)):setTimeout(()=>{this.showFileInfo(t.upload[0]),this.dragUploader.trigger?.dispatchEvent(new CustomEvent("uploadSuccess",{detail:[this,t]}))},3e3)}}showFileInfo(t){if(this.removeProgress(),document.querySelector("#filelist-searchterm")?.value){const d=document.createElement("td");d.textContent=t.path,this.row.append(d)}const i=document.createElement("td");i.classList.add("col-control"),this.row.append(i);const s=document.createElement("td");s.textContent=TYPO3.lang["type.file"]+" ("+t.extension.toUpperCase()+")",this.row.append(s);const o=document.createElement("td");o.textContent=n.fileSizeAsString(t.size),this.row.append(o);let e="";t.permissions.read&&(e+='<strong class="text-danger">'+TYPO3.lang["permissions.read"]+"</strong>"),t.permissions.write&&(e+='<strong class="text-danger">'+TYPO3.lang["permissions.write"]+"</strong>");const r=document.createElement("td");r.innerHTML=e,this.row.append(r);const a=document.createElement("td");a.textContent="-",this.row.append(a);for(let d=this.row.querySelectorAll("td").length;d<this.dragUploader.fileListColumnCount;d++)this.row.append(document.createElement("td"))}checkAllowedExtensions(){if(!this.dragUploader.filesExtensionsAllowed)return!0;const t=this.file.name.split(".").pop();return this.dragUploader.filesExtensionsAllowed.split(",").includes(t.toLowerCase())}checkDisallowedExtensions(){if(!this.dragUploader.filesExtensionsDisallowed)return!0;const t=this.file.name.split(".").pop();return this.dragUploader.filesExtensionsDisallowed.split(",").includes(t.toLowerCase())}}n.init();export{n as default};
