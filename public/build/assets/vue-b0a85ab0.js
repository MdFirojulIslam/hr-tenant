import{B as d,C as f,N as t,D as C,E,G as a,H as l}from"./@vue-22a79ae7.js";/**
* vue v3.5.16
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/const u=Object.create(null);function y(n,r){if(!f(n))if(n.nodeType)n=n.innerHTML;else return t;const c=C(n,r),s=u[c];if(s)return s;if(n[0]==="#"){const e=document.querySelector(n);n=e?e.innerHTML:""}const o=E({hoistStatic:!0,onError:void 0,onWarn:t},r);!o.isCustomElement&&typeof customElements<"u"&&(o.isCustomElement=e=>!!customElements.get(e));const{code:m}=a(n,o),i=new Function("Vue",m)(l);return i._rc=!0,u[c]=i}d(y);
