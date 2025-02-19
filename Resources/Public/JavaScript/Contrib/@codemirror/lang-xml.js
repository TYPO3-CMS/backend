import{parser as L}from"@lezer/xml";import{syntaxTree as E,LRLanguage as _,indentNodeProp as w,foldNodeProp as x,bracketMatchingHandle as M,LanguageSupport as k}from"@codemirror/language";import{EditorSelection as D}from"@codemirror/state";import{EditorView as P}from"@codemirror/view";function v(e,t){let i=t&&t.getChild("TagName");return i?e.sliceString(i.from,i.to):""}function N(e,t){let i=t&&t.firstChild;return!i||i.name!="OpenTag"?"":v(e,i)}function B(e,t,i){let n=t&&t.getChildren("Attribute").find(o=>o.from<=i&&o.to>=i),a=n&&n.getChild("AttributeName");return a?e.sliceString(a.from,a.to):""}function S(e){for(let t=e&&e.parent;t;t=t.parent)if(t.name=="Element")return t;return null}function H(e,t){var i;let n=E(e).resolveInner(t,-1),a=null;for(let o=n;!a&&o.parent;o=o.parent)(o.name=="OpenTag"||o.name=="CloseTag"||o.name=="SelfClosingTag"||o.name=="MismatchedCloseTag")&&(a=o);if(a&&(a.to>t||a.lastChild.type.isError)){let o=a.parent;if(n.name=="TagName")return a.name=="CloseTag"||a.name=="MismatchedCloseTag"?{type:"closeTag",from:n.from,context:o}:{type:"openTag",from:n.from,context:S(o)};if(n.name=="AttributeName")return{type:"attrName",from:n.from,context:a};if(n.name=="AttributeValue")return{type:"attrValue",from:n.from,context:a};let s=n==a||n.name=="Attribute"?n.childBefore(t):n;return s?.name=="StartTag"?{type:"openTag",from:t,context:S(o)}:s?.name=="StartCloseTag"&&s.to<=t?{type:"closeTag",from:t,context:o}:s?.name=="Is"?{type:"attrValue",from:t,context:a}:s?{type:"attrName",from:t,context:a}:null}else if(n.name=="StartCloseTag")return{type:"closeTag",from:t,context:n.parent};for(;n.parent&&n.to==t&&!(!((i=n.lastChild)===null||i===void 0)&&i.type.isError);)n=n.parent;return n.name=="Element"||n.name=="Text"||n.name=="Document"?{type:"tag",from:t,context:n.name=="Element"?n:S(n)}:null}class R{constructor(t,i,n){this.attrs=i,this.attrValues=n,this.children=[],this.name=t.name,this.completion=Object.assign(Object.assign({type:"type"},t.completion||{}),{label:this.name}),this.openCompletion=Object.assign(Object.assign({},this.completion),{label:"<"+this.name}),this.closeCompletion=Object.assign(Object.assign({},this.completion),{label:"</"+this.name+">",boost:2}),this.closeNameCompletion=Object.assign(Object.assign({},this.completion),{label:this.name+">"}),this.text=t.textContent?t.textContent.map(a=>({label:a,type:"text"})):[]}}const j=/^[:\-\.\w\u00b7-\uffff]*$/;function A(e){return Object.assign(Object.assign({type:"property"},e.completion||{}),{label:e.name})}function V(e){return typeof e=="string"?{label:`"${e}"`,type:"constant"}:/^"/.test(e.label)?e:Object.assign(Object.assign({},e),{label:`"${e.label}"`})}function $(e,t){let i=[],n=[],a=Object.create(null);for(let l of t){let p=A(l);i.push(p),l.global&&n.push(p),l.values&&(a[l.name]=l.values.map(V))}let o=[],s=[],h=Object.create(null);for(let l of e){let p=n,u=a;l.attributes&&(p=p.concat(l.attributes.map(r=>typeof r=="string"?i.find(b=>b.label==r)||{label:r,type:"property"}:(r.values&&(u==a&&(u=Object.create(u)),u[r.name]=r.values.map(V)),A(r)))));let d=new R(l,p,u);h[d.name]=d,o.push(d),l.top&&s.push(d)}s.length||(s=o);for(let l=0;l<o.length;l++){let p=e[l],u=o[l];if(p.children)for(let d of p.children)h[d]&&u.children.push(h[d]);else u.children=o}return l=>{var p;let{doc:u}=l.state,d=H(l.state,l.pos);if(!d||d.type=="tag"&&!l.explicit)return null;let{type:r,from:b,context:c}=d;if(r=="openTag"){let m=s,f=N(u,c);if(f){let g=h[f];m=g?.children||o}return{from:b,options:m.map(g=>g.completion),validFor:j}}else if(r=="closeTag"){let m=N(u,c);return m?{from:b,to:l.pos+(u.sliceString(l.pos,l.pos+1)==">"?1:0),options:[((p=h[m])===null||p===void 0?void 0:p.closeNameCompletion)||{label:m+">",type:"type"}],validFor:j}:null}else if(r=="attrName"){let m=h[v(u,c)];return{from:b,options:m?.attrs||n,validFor:j}}else if(r=="attrValue"){let m=B(u,c,b);if(!m)return null;let f=h[v(u,c)],g=(f?.attrValues||a)[m];return!g||!g.length?null:{from:b,to:l.pos+(u.sliceString(l.pos,l.pos+1)=='"'?1:0),options:g,validFor:/^"[^"]*"?$/}}else if(r=="tag"){let m=N(u,c),f=h[m],g=[],C=c&&c.lastChild;m&&(!C||C.name!="CloseTag"||v(u,C)!=m)&&g.push(f?f.closeCompletion:{label:"</"+m+">",type:"type",boost:2});let O=g.concat((f?.children||(c?o:s)).map(T=>T.openCompletion));if(c&&f?.text.length){let T=c.firstChild;T.to>l.pos-20&&!/\S/.test(l.state.sliceDoc(T.to,l.pos))&&(O=O.concat(f.text))}return{from:b,options:O,validFor:/^<\/?[:\-\.\w\u00b7-\uffff]*$/}}else return null}}const y=_.define({name:"xml",parser:L.configure({props:[w.add({Element(e){let t=/^\s*<\//.test(e.textAfter);return e.lineIndent(e.node.from)+(t?0:e.unit)},"OpenTag CloseTag SelfClosingTag"(e){return e.column(e.node.from)+e.unit}}),x.add({Element(e){let t=e.firstChild,i=e.lastChild;return!t||t.name!="OpenTag"?null:{from:t.to,to:i.name=="CloseTag"?i.from:e.to}}}),M.add({"OpenTag CloseTag":e=>e.getChild("TagName")})]}),languageData:{commentTokens:{block:{open:"<!--",close:"-->"}},indentOnInput:/^\s*<\/$/}});function q(e={}){let t=[y.data.of({autocomplete:$(e.elements||[],e.attributes||[])})];return e.autoCloseTags!==!1&&t.push(F),new k(y,t)}function I(e,t,i=e.length){if(!t)return"";let n=t.firstChild,a=n&&n.getChild("TagName");return a?e.sliceString(a.from,Math.min(a.to,i)):""}const F=P.inputHandler.of((e,t,i,n,a)=>{if(e.composing||e.state.readOnly||t!=i||n!=">"&&n!="/"||!y.isActiveAt(e.state,t,-1))return!1;let o=a(),{state:s}=o,h=s.changeByRange(l=>{var p,u,d;let{head:r}=l,b=s.doc.sliceString(r-1,r)==n,c=E(s).resolveInner(r,-1),m;if(b&&n==">"&&c.name=="EndTag"){let f=c.parent;if(((u=(p=f.parent)===null||p===void 0?void 0:p.lastChild)===null||u===void 0?void 0:u.name)!="CloseTag"&&(m=I(s.doc,f.parent,r))){let g=r+(s.doc.sliceString(r,r+1)===">"?1:0),C=`</${m}>`;return{range:l,changes:{from:r,to:g,insert:C}}}}else if(b&&n=="/"&&c.name=="StartCloseTag"){let f=c.parent;if(c.from==r-2&&((d=f.lastChild)===null||d===void 0?void 0:d.name)!="CloseTag"&&(m=I(s.doc,f,r))){let g=r+(s.doc.sliceString(r,r+1)===">"?1:0),C=`${m}>`;return{range:D.cursor(r+C.length,-1),changes:{from:r,to:g,insert:C}}}}return{range:l}});return h.changes.empty?!1:(e.dispatch([o,s.update(h,{userEvent:"input.complete",scrollIntoView:!0})]),!0)});export{F as autoCloseTags,$ as completeFromSchema,q as xml,y as xmlLanguage};
