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
define(["require","exports","../Enum/Viewport/ScaffoldIdentifier","../Enum/Viewport/TopbarIdentifiers","./AbstractContainer","jquery","./PageTree","./../Icons","../Event/TriggerRequest"],function(e,n,t,o,a,i,r,d,f){"use strict";return class extends a.AbstractContainer{constructor(){super(...arguments),this.PageTree=null,this.instance=null}setComponentInstance(e){this.instance=e,this.PageTree=new r(e)}toggle(){i(t.ScaffoldIdentifierEnum.scaffold).toggleClass("scaffold-content-navigation-expanded")}cleanup(){i(t.ScaffoldIdentifierEnum.moduleMenu).removeAttr("style"),i(t.ScaffoldIdentifierEnum.content).removeAttr("style")}hide(){i(o.TopbarIdentifiersEnum.buttonNavigationComponent).prop("disabled",!0),d.getIcon("actions-pagetree",d.sizes.small,"overlay-readonly",null,d.markupIdentifiers.inline).done(e=>{i(o.TopbarIdentifiersEnum.buttonNavigationComponent).html(e)}),i(t.ScaffoldIdentifierEnum.scaffold).removeClass("scaffold-content-navigation-expanded"),i(t.ScaffoldIdentifierEnum.contentModule).removeAttr("style")}show(e){i(o.TopbarIdentifiersEnum.buttonNavigationComponent).prop("disabled",!1),d.getIcon("actions-pagetree",d.sizes.small,null,null,d.markupIdentifiers.inline).done(e=>{i(o.TopbarIdentifiersEnum.buttonNavigationComponent).html(e)}),i(t.ScaffoldIdentifierEnum.contentNavigationDataComponent).hide(),void 0!==typeof e&&(i(t.ScaffoldIdentifierEnum.scaffold).addClass("scaffold-content-navigation-expanded"),i(t.ScaffoldIdentifierEnum.contentNavigation+' [data-component="'+e+'"]').show())}setUrl(e,n){const o=this.consumerScope.invoke(new f("typo3.setUrl",n));return o.then(()=>{i(t.ScaffoldIdentifierEnum.scaffold).addClass("scaffold-content-navigation-expanded"),i(t.ScaffoldIdentifierEnum.contentNavigationIframe).attr("src",e)}),o}getUrl(){return i(t.ScaffoldIdentifierEnum.contentNavigationIframe).attr("src")}refresh(e){return i(t.ScaffoldIdentifierEnum.contentNavigationIframe)[0].contentWindow.location.reload(e)}calculateScrollbar(){this.cleanup();const e=i(t.ScaffoldIdentifierEnum.scaffold),n=i(t.ScaffoldIdentifierEnum.moduleMenu),o=i(t.ScaffoldIdentifierEnum.content),a=i(".t3js-modulemenu");n.css("overflow","auto");const r=n.outerWidth(),d=a.outerWidth();n.removeAttr("style").css("overflow","hidden"),!1===e.hasClass("scaffold-modulemenu-expanded")?(n.width(r+(r-d)),o.css("left",r+(r-d))):(n.removeAttr("style"),o.removeAttr("style")),n.css("overflow","auto")}}});