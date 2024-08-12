import{parser}from"@lezer/javascript";import{syntaxTree,LRLanguage,indentNodeProp,continuedIndent,flatIndent,delimitedIndent,foldNodeProp,foldInside,defineLanguageFacet,sublanguageProp,LanguageSupport}from"@codemirror/language";import{EditorSelection}from"@codemirror/state";import{EditorView}from"@codemirror/view";import{snippetCompletion,ifNotIn,completeFromList}from"@codemirror/autocomplete";import{NodeWeakMap,IterMode}from"@lezer/common";const snippets=[snippetCompletion("function ${name}(${params}) {\n\t${}\n}",{label:"function",detail:"definition",type:"keyword"}),snippetCompletion("for (let ${index} = 0; ${index} < ${bound}; ${index}++) {\n\t${}\n}",{label:"for",detail:"loop",type:"keyword"}),snippetCompletion("for (let ${name} of ${collection}) {\n\t${}\n}",{label:"for",detail:"of loop",type:"keyword"}),snippetCompletion("do {\n\t${}\n} while (${})",{label:"do",detail:"loop",type:"keyword"}),snippetCompletion("while (${}) {\n\t${}\n}",{label:"while",detail:"loop",type:"keyword"}),snippetCompletion("try {\n\t${}\n} catch (${error}) {\n\t${}\n}",{label:"try",detail:"/ catch block",type:"keyword"}),snippetCompletion("if (${}) {\n\t${}\n}",{label:"if",detail:"block",type:"keyword"}),snippetCompletion("if (${}) {\n\t${}\n} else {\n\t${}\n}",{label:"if",detail:"/ else block",type:"keyword"}),snippetCompletion("class ${name} {\n\tconstructor(${params}) {\n\t\t${}\n\t}\n}",{label:"class",detail:"definition",type:"keyword"}),snippetCompletion('import {${names}} from "${module}"\n${}',{label:"import",detail:"named",type:"keyword"}),snippetCompletion('import ${name} from "${module}"\n${}',{label:"import",detail:"default",type:"keyword"})],typescriptSnippets=snippets.concat([snippetCompletion("interface ${name} {\n\t${}\n}",{label:"interface",detail:"definition",type:"keyword"}),snippetCompletion("type ${name} = ${type}",{label:"type",detail:"definition",type:"keyword"}),snippetCompletion("enum ${name} {\n\t${}\n}",{label:"enum",detail:"definition",type:"keyword"})]),cache=new NodeWeakMap,ScopeNodes=new Set(["Script","Block","FunctionExpression","FunctionDeclaration","ArrowFunction","MethodDeclaration","ForStatement"]);function defID(e){return(t,n)=>{let o=t.node.getChild("VariableDefinition");return o&&n(o,e),!0}}const functionContext=["FunctionDeclaration"],gatherCompletions={FunctionDeclaration:defID("function"),ClassDeclaration:defID("class"),ClassExpression:()=>!0,EnumDeclaration:defID("constant"),TypeAliasDeclaration:defID("type"),NamespaceDeclaration:defID("namespace"),VariableDefinition(e,t){e.matchContext(functionContext)||t(e,"variable")},TypeDefinition(e,t){t(e,"type")},__proto__:null};function getScope(e,t){let n=cache.get(t);if(n)return n;let o=[],a=!0;function r(t,n){let a=e.sliceString(t.from,t.to);o.push({label:a,type:n})}return t.cursor(IterMode.IncludeAnonymous).iterate((t=>{if(a)a=!1;else if(t.name){let e=gatherCompletions[t.name];if(e&&e(t,r)||ScopeNodes.has(t.name))return!1}else if(t.to-t.from>8192){for(let n of getScope(e,t.node))o.push(n);return!1}})),cache.set(t,o),o}const Identifier=/^[\w$\xa1-\uffff][\w$\d\xa1-\uffff]*$/,dontComplete=["TemplateString","String","RegExp","LineComment","BlockComment","VariableDefinition","TypeDefinition","Label","PropertyDefinition","PropertyName","PrivatePropertyDefinition","PrivatePropertyName",".","?."];function localCompletionSource(e){let t=syntaxTree(e.state).resolveInner(e.pos,-1);if(dontComplete.indexOf(t.name)>-1)return null;let n="VariableName"==t.name||t.to-t.from<20&&Identifier.test(e.state.sliceDoc(t.from,t.to));if(!n&&!e.explicit)return null;let o=[];for(let n=t;n;n=n.parent)ScopeNodes.has(n.name)&&(o=o.concat(getScope(e.state.doc,n)));return{options:o,from:n?t.from:e.pos,validFor:Identifier}}function pathFor(e,t,n){var o;let a=[];for(;;){let r,i=t.firstChild;if("VariableName"==(null==i?void 0:i.name))return a.push(e(i)),{path:a.reverse(),name:n};if("MemberExpression"!=(null==i?void 0:i.name)||"PropertyName"!=(null===(o=r=i.lastChild)||void 0===o?void 0:o.name))return null;a.push(e(r)),t=i}}function completionPath(e){let t=t=>e.state.doc.sliceString(t.from,t.to),n=syntaxTree(e.state).resolveInner(e.pos,-1);return"PropertyName"==n.name?pathFor(t,n.parent,t(n)):"."!=n.name&&"?."!=n.name||"MemberExpression"!=n.parent.name?dontComplete.indexOf(n.name)>-1?null:"VariableName"==n.name||n.to-n.from<20&&Identifier.test(t(n))?{path:[],name:t(n)}:"MemberExpression"==n.name?pathFor(t,n,""):e.explicit?{path:[],name:""}:null:pathFor(t,n.parent,"")}function enumeratePropertyCompletions(e,t){let n=[],o=new Set;for(let a=0;;a++){for(let r of(Object.getOwnPropertyNames||Object.keys)(e)){if(!/^[a-zA-Z_$\xaa-\uffdc][\w$\xaa-\uffdc]*$/.test(r)||o.has(r))continue;let i;o.add(r);try{i=e[r]}catch(e){continue}n.push({label:r,type:"function"==typeof i?/^[A-Z]/.test(r)?"class":t?"function":"method":t?"variable":"property",boost:-a})}let r=Object.getPrototypeOf(e);if(!r)return n;e=r}}function scopeCompletionSource(e){let t=new Map;return n=>{let o=completionPath(n);if(!o)return null;let a=e;for(let e of o.path)if(a=a[e],!a)return null;let r=t.get(a);return r||t.set(a,r=enumeratePropertyCompletions(a,!o.path.length)),{from:n.pos-o.name.length,options:r,validFor:Identifier}}}const javascriptLanguage=LRLanguage.define({name:"javascript",parser:parser.configure({props:[indentNodeProp.add({IfStatement:continuedIndent({except:/^\s*({|else\b)/}),TryStatement:continuedIndent({except:/^\s*({|catch\b|finally\b)/}),LabeledStatement:flatIndent,SwitchBody:e=>{let t=e.textAfter,n=/^\s*\}/.test(t),o=/^\s*(case|default)\b/.test(t);return e.baseIndent+(n?0:o?1:2)*e.unit},Block:delimitedIndent({closing:"}"}),ArrowFunction:e=>e.baseIndent+e.unit,"TemplateString BlockComment":()=>null,"Statement Property":continuedIndent({except:/^{/}),JSXElement(e){let t=/^\s*<\//.test(e.textAfter);return e.lineIndent(e.node.from)+(t?0:e.unit)},JSXEscape(e){let t=/\s*\}/.test(e.textAfter);return e.lineIndent(e.node.from)+(t?0:e.unit)},"JSXOpenTag JSXSelfClosingTag":e=>e.column(e.node.from)+e.unit}),foldNodeProp.add({"Block ClassBody SwitchBody EnumBody ObjectExpression ArrayExpression ObjectType":foldInside,BlockComment:e=>({from:e.from+2,to:e.to-2})})]}),languageData:{closeBrackets:{brackets:["(","[","{","'",'"',"`"]},commentTokens:{line:"//",block:{open:"/*",close:"*/"}},indentOnInput:/^\s*(?:case |default:|\{|\}|<\/)$/,wordChars:"$"}}),jsxSublanguage={test:e=>/^JSX/.test(e.name),facet:defineLanguageFacet({commentTokens:{block:{open:"{/*",close:"*/}"}}})},typescriptLanguage=javascriptLanguage.configure({dialect:"ts"},"typescript"),jsxLanguage=javascriptLanguage.configure({dialect:"jsx",props:[sublanguageProp.add((e=>e.isTop?[jsxSublanguage]:void 0))]}),tsxLanguage=javascriptLanguage.configure({dialect:"jsx ts",props:[sublanguageProp.add((e=>e.isTop?[jsxSublanguage]:void 0))]},"typescript");let kwCompletion=e=>({label:e,type:"keyword"});const keywords="break case const continue default delete export extends false finally in instanceof let new return static super switch this throw true typeof var yield".split(" ").map(kwCompletion),typescriptKeywords=keywords.concat(["declare","implements","private","protected","public"].map(kwCompletion));function javascript(e={}){let t=e.jsx?e.typescript?tsxLanguage:jsxLanguage:e.typescript?typescriptLanguage:javascriptLanguage,n=e.typescript?typescriptSnippets.concat(typescriptKeywords):snippets.concat(keywords);return new LanguageSupport(t,[javascriptLanguage.data.of({autocomplete:ifNotIn(dontComplete,completeFromList(n))}),javascriptLanguage.data.of({autocomplete:localCompletionSource}),e.jsx?autoCloseTags:[]])}function findOpenTag(e){for(;;){if("JSXOpenTag"==e.name||"JSXSelfClosingTag"==e.name||"JSXFragmentTag"==e.name)return e;if("JSXEscape"==e.name||!e.parent)return null;e=e.parent}}function elementName(e,t,n=e.length){for(let o=null==t?void 0:t.firstChild;o;o=o.nextSibling)if("JSXIdentifier"==o.name||"JSXBuiltin"==o.name||"JSXNamespacedName"==o.name||"JSXMemberExpression"==o.name)return e.sliceString(o.from,Math.min(o.to,n));return""}const android="object"==typeof navigator&&/Android\b/.test(navigator.userAgent),autoCloseTags=EditorView.inputHandler.of(((e,t,n,o,a)=>{if((android?e.composing:e.compositionStarted)||e.state.readOnly||t!=n||">"!=o&&"/"!=o||!javascriptLanguage.isActiveAt(e.state,t,-1))return!1;let r=a(),{state:i}=r,l=i.changeByRange((e=>{var t;let n,{head:a}=e,r=syntaxTree(i).resolveInner(a-1,-1);if("JSXStartTag"==r.name&&(r=r.parent),i.doc.sliceString(a-1,a)!=o||"JSXAttributeValue"==r.name&&r.to>a);else{if(">"==o&&"JSXFragmentTag"==r.name)return{range:e,changes:{from:a,insert:"</>"}};if("/"==o&&"JSXStartCloseTag"==r.name){let e=r.parent,o=e.parent;if(o&&e.from==a-2&&((n=elementName(i.doc,o.firstChild,a))||"JSXFragmentTag"==(null===(t=o.firstChild)||void 0===t?void 0:t.name))){let e=`${n}>`;return{range:EditorSelection.cursor(a+e.length,-1),changes:{from:a,insert:e}}}}else if(">"==o){let t=findOpenTag(r);if(t&&"JSXOpenTag"==t.name&&!/^\/?>|^<\//.test(i.doc.sliceString(a,a+2))&&(n=elementName(i.doc,t,a)))return{range:e,changes:{from:a,insert:`</${n}>`}}}}return{range:e}}));return!l.changes.empty&&(e.dispatch([r,i.update(l,{userEvent:"input.complete",scrollIntoView:!0})]),!0)}));function esLint(e,t){return t||(t={parserOptions:{ecmaVersion:2019,sourceType:"module"},env:{browser:!0,node:!0,es6:!0,es2015:!0,es2017:!0,es2020:!0},rules:{}},e.getRules().forEach(((e,n)=>{e.meta.docs.recommended&&(t.rules[n]=2)}))),n=>{let{state:o}=n,a=[];for(let{from:n,to:r}of javascriptLanguage.findRegions(o)){let i=o.doc.lineAt(n),l={line:i.number-1,col:n-i.from,pos:n};for(let i of e.verify(o.sliceDoc(n,r),t))a.push(translateDiagnostic(i,o.doc,l))}return a}}function mapPos(e,t,n,o){return n.line(e+o.line).from+t+(1==e?o.col-1:-1)}function translateDiagnostic(e,t,n){let o=mapPos(e.line,e.column,t,n),a={from:o,to:null!=e.endLine&&1!=e.endColumn?mapPos(e.endLine,e.endColumn,t,n):o,message:e.message,source:e.ruleId?"eslint:"+e.ruleId:"eslint",severity:1==e.severity?"warning":"error"};if(e.fix){let{range:t,text:r}=e.fix,i=t[0]+n.pos-o,l=t[1]+n.pos-o;a.actions=[{name:"fix",apply(e,t){e.dispatch({changes:{from:t+i,to:t+l,insert:r},scrollIntoView:!0})}}]}return a}export{autoCloseTags,completionPath,esLint,javascript,javascriptLanguage,jsxLanguage,localCompletionSource,scopeCompletionSource,snippets,tsxLanguage,typescriptLanguage,typescriptSnippets};