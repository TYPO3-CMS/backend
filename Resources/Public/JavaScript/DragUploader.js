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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","moment","./Enum/Severity","./Utility/MessageUtility","nprogress","TYPO3/CMS/Core/Ajax/AjaxRequest","./Modal","./Notification","TYPO3/CMS/Backend/ActionButton/ImmediateAction","TYPO3/CMS/Backend/Hashing/Md5"],(function(e,t,i,a,s,o,r,l,d,n,p,h){"use strict";var u;Object.defineProperty(t,"__esModule",{value:!0}),t.initialize=void 0,i=__importDefault(i),a=__importDefault(a),p=__importDefault(p),h=__importDefault(h),function(e){e.OVERRIDE="replace",e.RENAME="rename",e.SKIP="cancel",e.USE_EXISTING="useExisting"}(u||(u={}));class f{constructor(e){this.askForOverride=[],this.percentagePerFile=1,this.hideDropzone=e=>{e.stopPropagation(),e.preventDefault(),this.$dropzone.hide(),this.$dropzone.removeClass("drop-status-ok"),this.manuallyTriggered=!1},this.dragFileIntoDocument=e=>{var t;return e.stopPropagation(),e.preventDefault(),i.default(e.currentTarget).addClass("drop-in-progress"),(null===(t=this.$element.get(0))||void 0===t?void 0:t.offsetParent)&&this.showDropzone(),!1},this.dragAborted=e=>(e.stopPropagation(),e.preventDefault(),i.default(e.currentTarget).removeClass("drop-in-progress"),!1),this.ignoreDrop=e=>(e.stopPropagation(),e.preventDefault(),this.dragAborted(e),!1),this.handleDrop=e=>{this.ignoreDrop(e),this.hideDropzone(e),this.processFiles(e.originalEvent.dataTransfer.files)},this.fileInDropzone=()=>{this.$dropzone.addClass("drop-status-ok")},this.fileOutOfDropzone=()=>{this.$dropzone.removeClass("drop-status-ok"),this.manuallyTriggered||this.manualTable||this.$dropzone.hide()},this.$body=i.default("body"),this.$element=i.default(e);const t=void 0!==this.$element.data("dropzoneTrigger");this.$trigger=i.default(this.$element.data("dropzoneTrigger")),this.defaultAction=this.$element.data("defaultAction")||u.SKIP,this.$dropzone=i.default("<div />").addClass("dropzone").hide(),this.irreObjectUid=this.$element.data("fileIrreObject");const a=this.$element.data("dropzoneTarget");this.irreObjectUid&&0!==this.$element.nextAll(a).length?(this.dropZoneInsertBefore=!0,this.$dropzone.insertBefore(a)):(this.dropZoneInsertBefore=!1,this.$dropzone.insertAfter(a)),this.$dropzoneMask=i.default("<div />").addClass("dropzone-mask").appendTo(this.$dropzone),this.fileInput=document.createElement("input"),this.fileInput.setAttribute("type","file"),this.fileInput.setAttribute("multiple","multiple"),this.fileInput.setAttribute("name","files[]"),this.fileInput.classList.add("upload-file-picker"),this.$body.append(this.fileInput),this.$fileList=i.default(this.$element.data("progress-container")),this.fileListColumnCount=i.default("thead tr:first th",this.$fileList).length+1,this.filesExtensionsAllowed=this.$element.data("file-allowed"),this.fileDenyPattern=this.$element.data("file-deny-pattern")?new RegExp(this.$element.data("file-deny-pattern"),"i"):null,this.maxFileSize=parseInt(this.$element.data("max-file-size"),10),this.target=this.$element.data("target-folder"),this.reloadUrl=this.$element.data("reload-url"),this.browserCapabilities={fileReader:"undefined"!=typeof FileReader,DnD:"draggable"in document.createElement("span"),Progress:"upload"in new XMLHttpRequest},this.browserCapabilities.DnD?(this.$body.on("dragover",this.dragFileIntoDocument),this.$body.on("dragend",this.dragAborted),this.$body.on("drop",this.ignoreDrop),this.$dropzone.on("dragenter",this.fileInDropzone),this.$dropzoneMask.on("dragenter",this.fileInDropzone),this.$dropzoneMask.on("dragleave",this.fileOutOfDropzone),this.$dropzoneMask.on("drop",e=>this.handleDrop(e)),this.$dropzone.prepend('<button type="button" class="dropzone-hint" aria-labelledby="dropzone-title"><div class="dropzone-hint-media"><div class="dropzone-hint-icon"></div></div><div class="dropzone-hint-body"><h3 id="dropzone-title" class="dropzone-hint-title">'+TYPO3.lang["file_upload.dropzonehint.title"]+'</h3><p class="dropzone-hint-message">'+TYPO3.lang["file_upload.dropzonehint.message"]+"</p></div></div>").on("click",()=>{this.fileInput.click()}),i.default('<button type="button" />').addClass("dropzone-close").attr("aria-label",TYPO3.lang["file_upload.dropzone.close"]).on("click",this.hideDropzone).appendTo(this.$dropzone),0===this.$fileList.length&&(this.$fileList=i.default("<table />").attr("id","typo3-filelist").addClass("table table-striped table-hover upload-queue").html("<tbody></tbody>").hide(),this.dropZoneInsertBefore?this.$fileList.insertAfter(this.$dropzone):this.$fileList.insertBefore(this.$dropzone),this.fileListColumnCount=8,this.manualTable=!0),this.fileInput.addEventListener("change",e=>{this.hideDropzone(e),this.processFiles(Array.apply(null,this.fileInput.files))}),document.addEventListener("keydown",e=>{"Escape"===e.code&&this.$dropzone.is(":visible")&&!this.manualTable&&this.hideDropzone(e)}),this.bindUploadButton(!0===t?this.$trigger:this.$element)):console.warn("Browser has no Drag and drop capabilities; cannot initialize DragUploader")}showDropzone(){this.$dropzone.show()}processFiles(e){var t,i,a;this.queueLength=e.length,this.$fileList.is(":visible")||(this.$fileList.show(),null===(t=this.$fileList.closest(".t3-filelist-table-container"))||void 0===t||t.removeClass("hidden"),null===(a=null===(i=this.$fileList.closest("form"))||void 0===i?void 0:i.find(".t3-filelist-info-container"))||void 0===a||a.hide()),r.start(),this.percentagePerFile=1/e.length;const s=[];Array.from(e).forEach(e=>{const t=new l(TYPO3.settings.ajaxUrls.file_exists).withQueryArguments({fileName:e.name,fileTarget:this.target}).get({cache:"no-cache"}).then(async t=>{const i=await t.resolve();void 0!==i.uid?(this.askForOverride.push({original:i,uploaded:e,action:this.irreObjectUid?u.USE_EXISTING:this.defaultAction}),r.inc(this.percentagePerFile)):new g(this,e,u.SKIP)});s.push(t)}),Promise.all(s).then(()=>{this.drawOverrideModal(),r.done()}),this.fileInput.value=""}bindUploadButton(e){e.on("click",e=>{e.preventDefault(),this.fileInput.click(),this.showDropzone(),this.manuallyTriggered=!0})}decrementQueueLength(){this.queueLength>0&&(this.queueLength--,0===this.queueLength&&new l(TYPO3.settings.ajaxUrls.flashmessages_render).get({cache:"no-cache"}).then(async e=>{const t=await e.resolve();for(let e of t)n.showMessage(e.title,e.message,e.severity);this.reloadUrl&&!this.manualTable&&setTimeout(()=>{n.info(TYPO3.lang["file_upload.reload.filelist"],TYPO3.lang["file_upload.reload.filelist.message"],10,[{label:TYPO3.lang["file_upload.reload.filelist.actions.dismiss"]},{label:TYPO3.lang["file_upload.reload.filelist.actions.reload"],action:new p.default(()=>{top.list_frame.document.location.href=this.reloadUrl})}])},5e3)}))}drawOverrideModal(){const e=Object.keys(this.askForOverride).length;if(0===e)return;const t=i.default("<div/>").append(i.default("<p/>").text(TYPO3.lang["file_upload.existingfiles.description"]),i.default("<table/>",{class:"table"}).append(i.default("<thead/>").append(i.default("<tr />").append(i.default("<th/>"),i.default("<th/>").text(TYPO3.lang["file_upload.header.originalFile"]),i.default("<th/>").text(TYPO3.lang["file_upload.header.uploadedFile"]),i.default("<th/>").text(TYPO3.lang["file_upload.header.action"])))));for(let s=0;s<e;++s){const e=i.default("<tr />").append(i.default("<td />").append(""!==this.askForOverride[s].original.thumbUrl?i.default("<img />",{src:this.askForOverride[s].original.thumbUrl,height:40}):i.default(this.askForOverride[s].original.icon)),i.default("<td />").html(this.askForOverride[s].original.name+" ("+c.fileSizeAsString(this.askForOverride[s].original.size)+")<br>"+a.default.unix(this.askForOverride[s].original.mtime).format("YYYY-MM-DD HH:mm")),i.default("<td />").html(this.askForOverride[s].uploaded.name+" ("+c.fileSizeAsString(this.askForOverride[s].uploaded.size)+")<br>"+a.default(this.askForOverride[s].uploaded.lastModified?this.askForOverride[s].uploaded.lastModified:this.askForOverride[s].uploaded.lastModifiedDate).format("YYYY-MM-DD HH:mm")),i.default("<td />").append(i.default("<select />",{class:"form-select t3js-actions","data-override":s}).append(this.irreObjectUid?i.default("<option/>").val(u.USE_EXISTING).text(TYPO3.lang["file_upload.actions.use_existing"]):"",i.default("<option />",{selected:this.defaultAction===u.SKIP}).val(u.SKIP).text(TYPO3.lang["file_upload.actions.skip"]),i.default("<option />",{selected:this.defaultAction===u.RENAME}).val(u.RENAME).text(TYPO3.lang["file_upload.actions.rename"]),i.default("<option />",{selected:this.defaultAction===u.OVERRIDE}).val(u.OVERRIDE).text(TYPO3.lang["file_upload.actions.override"]))));t.find("table").append("<tbody />").append(e)}const o=d.confirm(TYPO3.lang["file_upload.existingfiles.title"],t,s.SeverityEnum.warning,[{text:i.default(this).data("button-close-text")||TYPO3.lang["file_upload.button.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel"},{text:i.default(this).data("button-ok-text")||TYPO3.lang["file_upload.button.continue"]||"Continue with selected actions",btnClass:"btn-warning",name:"continue"}],["modal-inner-scroll"]);o.find(".modal-dialog").addClass("modal-lg"),o.find(".modal-footer").prepend(i.default("<span/>").addClass("form-inline").append(i.default("<label/>").text(TYPO3.lang["file_upload.actions.all.label"]),i.default("<select/>",{class:"form-select t3js-actions-all"}).append(i.default("<option/>").val("").text(TYPO3.lang["file_upload.actions.all.empty"]),this.irreObjectUid?i.default("<option/>").val(u.USE_EXISTING).text(TYPO3.lang["file_upload.actions.all.use_existing"]):"",i.default("<option/>",{selected:this.defaultAction===u.SKIP}).val(u.SKIP).text(TYPO3.lang["file_upload.actions.all.skip"]),i.default("<option/>",{selected:this.defaultAction===u.RENAME}).val(u.RENAME).text(TYPO3.lang["file_upload.actions.all.rename"]),i.default("<option/>",{selected:this.defaultAction===u.OVERRIDE}).val(u.OVERRIDE).text(TYPO3.lang["file_upload.actions.all.override"]))));const r=this;o.on("change",".t3js-actions-all",(function(){const e=i.default(this).val();""!==e?o.find(".t3js-actions").each((t,a)=>{const s=i.default(a),o=parseInt(s.data("override"),10);s.val(e).prop("disabled","disabled"),r.askForOverride[o].action=s.val()}):o.find(".t3js-actions").removeProp("disabled")})).on("change",".t3js-actions",(function(){const e=i.default(this),t=parseInt(e.data("override"),10);r.askForOverride[t].action=e.val()})).on("button.clicked",(function(e){"cancel"===e.target.name?(r.askForOverride=[],d.dismiss()):"continue"===e.target.name&&(i.default.each(r.askForOverride,(e,t)=>{t.action===u.USE_EXISTING?c.addFileToIrre(r.irreObjectUid,t.original):t.action!==u.SKIP&&new g(r,t.uploaded,t.action)}),r.askForOverride=[],d.dismiss())})).on("hidden.bs.modal",()=>{this.askForOverride=[]})}}class g{constructor(e,t,a){if(this.dragUploader=e,this.file=t,this.override=a,this.$row=i.default("<tr />").addClass("upload-queue-item uploading"),this.dragUploader.manualTable||(this.$selector=i.default("<td />").addClass("col-selector").appendTo(this.$row)),this.$iconCol=i.default("<td />").addClass("col-icon").appendTo(this.$row),this.$fileName=i.default("<td />").text(t.name).appendTo(this.$row),this.$progress=i.default("<td />").attr("colspan",this.dragUploader.fileListColumnCount-this.$row.find("td").length).appendTo(this.$row),this.$progressContainer=i.default("<div />").addClass("upload-queue-progress").appendTo(this.$progress),this.$progressBar=i.default("<div />").addClass("upload-queue-progress-bar").appendTo(this.$progressContainer),this.$progressPercentage=i.default("<span />").addClass("upload-queue-progress-percentage").appendTo(this.$progressContainer),this.$progressMessage=i.default("<span />").addClass("upload-queue-progress-message").appendTo(this.$progressContainer),0===i.default("tbody tr.upload-queue-item",this.dragUploader.$fileList).length?(this.$row.prependTo(i.default("tbody",this.dragUploader.$fileList)),this.$row.addClass("last")):this.$row.insertBefore(i.default("tbody tr.upload-queue-item:first",this.dragUploader.$fileList)),this.$selector&&this.$selector.html('<span class="form-check form-toggle"><input type="checkbox" class="form-check-input t3js-multi-record-selection-check" disabled/></span>'),this.$iconCol.html('<span class="t3-icon t3-icon-mimetypes t3-icon-other-other">&nbsp;</span>'),this.dragUploader.maxFileSize>0&&this.file.size>this.dragUploader.maxFileSize)this.updateMessage(TYPO3.lang["file_upload.maxFileSizeExceeded"].replace(/\{0\}/g,this.file.name).replace(/\{1\}/g,c.fileSizeAsString(this.dragUploader.maxFileSize))),this.$row.addClass("error");else if(this.dragUploader.fileDenyPattern&&this.file.name.match(this.dragUploader.fileDenyPattern))this.updateMessage(TYPO3.lang["file_upload.fileNotAllowed"].replace(/\{0\}/g,this.file.name)),this.$row.addClass("error");else if(this.checkAllowedExtensions()){this.updateMessage("- "+c.fileSizeAsString(this.file.size));const e=new FormData;e.append("data[upload][1][target]",this.dragUploader.target),e.append("data[upload][1][data]","1"),e.append("overwriteExistingFiles",this.override),e.append("redirect",""),e.append("upload_1",this.file);const t=new XMLHttpRequest;t.onreadystatechange=()=>{if(t.readyState===XMLHttpRequest.DONE)if(200===t.status)try{this.uploadSuccess(JSON.parse(t.responseText))}catch(e){this.uploadError(t)}else this.uploadError(t)},t.upload.addEventListener("progress",e=>this.updateProgress(e)),t.open("POST",TYPO3.settings.ajaxUrls.file_process),t.send(e)}else this.updateMessage(TYPO3.lang["file_upload.fileExtensionExpected"].replace(/\{0\}/g,this.dragUploader.filesExtensionsAllowed)),this.$row.addClass("error")}updateMessage(e){this.$progressMessage.text(e)}removeProgress(){this.$progress&&this.$progress.remove()}uploadStart(){this.$progressPercentage.text("(0%)"),this.$progressBar.width("1%"),this.dragUploader.$trigger.trigger("uploadStart",[this])}uploadError(e){this.updateMessage(TYPO3.lang["file_upload.uploadFailed"].replace(/\{0\}/g,this.file.name));const t=i.default(e.responseText);t.is("t3err")?this.$progressPercentage.text(t.text()):e.statusText?this.$progressPercentage.text("("+e.statusText+") "):this.$progressPercentage.text(""),this.$row.addClass("error"),this.dragUploader.decrementQueueLength(),this.dragUploader.$trigger.trigger("uploadError",[this,e])}updateProgress(e){const t=Math.round(e.loaded/e.total*100)+"%";this.$progressBar.outerWidth(t),this.$progressPercentage.text(t),this.dragUploader.$trigger.trigger("updateProgress",[this,t,e])}uploadSuccess(e){var t;if(e.upload){this.dragUploader.decrementQueueLength(),this.$row.removeClass("uploading"),this.$row.prop("data-type","file"),this.$row.prop("data-file-uid",e.upload[0].uid),this.$fileName.text(e.upload[0].name),this.$progressPercentage.text(""),this.$progressMessage.text("100%"),this.$progressBar.outerWidth("100%");const a=String(e.upload[0].id);if(this.$selector){const e=null===(t=this.$selector.find("input"))||void 0===t?void 0:t.get(0);e&&(e.removeAttribute("disabled"),e.setAttribute("name","CBC[_FILE|"+h.default.hash(a)+"]"),e.setAttribute("value",a))}e.upload[0].icon&&this.$iconCol.html('<a href="#" class="t3js-contextmenutrigger" data-uid="'+a+'" data-table="sys_file">'+e.upload[0].icon+"&nbsp;</span></a>"),this.dragUploader.irreObjectUid?(c.addFileToIrre(this.dragUploader.irreObjectUid,e.upload[0]),setTimeout(()=>{var t;this.$row.remove(),0===i.default("tr",this.dragUploader.$fileList).length&&(this.dragUploader.$fileList.hide(),null===(t=this.dragUploader.$fileList.closest(".t3-filelist-table-container"))||void 0===t||t.addClass("hidden"),this.dragUploader.$trigger.trigger("uploadSuccess",[this,e]))},3e3)):setTimeout(()=>{this.showFileInfo(e.upload[0]),this.dragUploader.$trigger.trigger("uploadSuccess",[this,e])},3e3)}}showFileInfo(e){var t;this.removeProgress(),(null===(t=document.querySelector("#search_field"))||void 0===t?void 0:t.value)&&i.default("<td />").text(e.path).appendTo(this.$row),i.default("<td />").text("").appendTo(this.$row),i.default("<td />").text(TYPO3.lang["type.file"]+" ("+e.extension.toUpperCase()+")").appendTo(this.$row),i.default("<td />").text(c.fileSizeAsString(e.size)).appendTo(this.$row);let a="";e.permissions.read&&(a+='<strong class="text-danger">'+TYPO3.lang["permissions.read"]+"</strong>"),e.permissions.write&&(a+='<strong class="text-danger">'+TYPO3.lang["permissions.write"]+"</strong>"),i.default("<td />").html(a).appendTo(this.$row),i.default("<td />").text("-").appendTo(this.$row);for(let e=this.$row.find("td").length;e<this.dragUploader.fileListColumnCount;e++)i.default("<td />").text("").appendTo(this.$row)}checkAllowedExtensions(){if(!this.dragUploader.filesExtensionsAllowed)return!0;const e=this.file.name.split(".").pop(),t=this.dragUploader.filesExtensionsAllowed.split(",");return-1!==i.default.inArray(e.toLowerCase(),t)}}class c{static fileSizeAsString(e){const t=e/1024;let i="";return i=t>1024?(t/1024).toFixed(1)+" MB":t.toFixed(1)+" KB",i}static addFileToIrre(e,t){const i={actionName:"typo3:foreignRelation:insert",objectGroup:e,table:"sys_file",uid:t.uid};o.MessageUtility.send(i)}static init(){const e=this.options;i.default.fn.extend({dragUploader:function(e){return this.each((t,a)=>{const s=i.default(a);let o=s.data("DragUploaderPlugin");o||s.data("DragUploaderPlugin",o=new f(a)),"string"==typeof e&&o[e]()})}}),i.default(()=>{i.default(".t3js-drag-uploader").dragUploader(e)});new MutationObserver(()=>{i.default(".t3js-drag-uploader").dragUploader(e)}).observe(document,{childList:!0,subtree:!0})}}t.initialize=function(){c.init(),void 0!==TYPO3.settings&&void 0!==TYPO3.settings.RequireJS&&void 0!==TYPO3.settings.RequireJS.PostInitializationModules&&void 0!==TYPO3.settings.RequireJS.PostInitializationModules["TYPO3/CMS/Backend/DragUploader"]&&i.default.each(TYPO3.settings.RequireJS.PostInitializationModules["TYPO3/CMS/Backend/DragUploader"],(t,i)=>{e([i])})},t.initialize()}));