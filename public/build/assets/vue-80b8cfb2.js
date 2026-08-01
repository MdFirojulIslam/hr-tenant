import{q as d,s as f,N as t,v as y,x as a,y as l,z as C}from"./@vue-fd82b18d.js";/**
* vue v3.5.16
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/const u=Object.create(null);function E(n,r){if(!f(n))if(n.nodeType)n=n.innerHTML;else return t;const s=y(n,r),c=u[s];if(c)return c;if(n[0]==="#"){const e=document.querySelector(n);n=e?e.innerHTML:""}const o=a({hoistStatic:!0,onError:void 0,onWarn:t},r);!o.isCustomElement&&typeof customElements<"u"&&(o.isCustomElement=e=>!!customElements.get(e));const{code:m}=l(n,o),i=new Function("Vue",m)(C);return i._rc=!0,u[s]=i}d(E);
