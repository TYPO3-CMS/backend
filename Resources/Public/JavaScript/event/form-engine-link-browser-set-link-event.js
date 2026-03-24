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
class e extends Event{static{this.eventName="typo3:form-engine:link-browser:set-link"}constructor(t,s){super(e.eventName,{bubbles:!0,composed:!0,cancelable:!1}),this.value=t,this.onFieldChangeItems=s}}export{e as FormEngineLinkBrowserSetLinkEvent};
