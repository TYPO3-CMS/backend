import{parser}from"@lezer/json";import{LRLanguage,indentNodeProp,continuedIndent,foldNodeProp,foldInside,LanguageSupport}from"@codemirror/language";const jsonParseLinter=()=>e=>{try{JSON.parse(e.state.doc.toString())}catch(n){if(!(n instanceof SyntaxError))throw n;const o=getErrorPosition(n,e.state.doc);return[{from:o,message:n.message,severity:"error",to:o}]}return[]};function getErrorPosition(e,n){let o;return(o=e.message.match(/at position (\d+)/))?Math.min(+o[1],n.length):(o=e.message.match(/at line (\d+) column (\d+)/))?Math.min(n.line(+o[1]).from+ +o[2]-1,n.length):0}const jsonLanguage=LRLanguage.define({name:"json",parser:parser.configure({props:[indentNodeProp.add({Object:continuedIndent({except:/^\s*\}/}),Array:continuedIndent({except:/^\s*\]/})}),foldNodeProp.add({"Object Array":foldInside})]}),languageData:{closeBrackets:{brackets:["[","{",'"']},indentOnInput:/^\s*[\}\]]$/}});function json(){return new LanguageSupport(jsonLanguage)}export{json,jsonLanguage,jsonParseLinter};