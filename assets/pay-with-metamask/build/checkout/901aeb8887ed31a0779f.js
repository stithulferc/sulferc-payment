"use strict";(globalThis.webpackChunkpay_with_metamask=globalThis.webpackChunkpay_with_metamask||[]).push([[382],{9382:(e,t,n)=>{n.r(t),n.d(t,{default:()=>_});var c=n(99196),a=n(36286),r=n(93506),s=n(84211);const{const_msg:o,networkName:l}=connect_wallts,m=({currentchain:e})=>{const[t,n]=(0,c.useState)(!1),{chain:m}=(0,a.LN)(),{open:i,setOpen:p}=(0,r.dd)(),{isConnected:u,address:d}=(0,a.mA)(),{disconnect:w}=(0,a.qL)();if((0,c.useEffect)((()=>{m?.id===e.networks.id&&p(!1),i||n(!1)}),[m?.id,i]),i){const e=document.querySelector(".sc-dcJsrY div"),c=document.querySelector(".sc-imWYAI"),a=document.querySelector("#__CONNECTKIT__ button.sc-bypJrT");a&&!t&&(a.click(),n(!0)),c&&"Switch Networks"==e.firstChild.textContent&&(c.textContent=o.switch_network_msg)}return(0,c.createElement)(c.Fragment,null,!u&&(0,c.createElement)("div",{className:"cpgw_selected_wallet"},(0,c.createElement)("div",{className:"cpgw_p_network"},(0,c.createElement)("strong",null,o.select_network,":"),l)),m&&u&&(0,c.createElement)(c.Fragment,null,(0,c.createElement)("div",{className:"cpgw_p_connect"},(0,c.createElement)("div",{className:"cpgw_p_status"},o.connected),(0,c.createElement)("div",{className:"cpgw_disconnect_wallet",onClick:()=>{w()}},o.disconnect)),(0,c.createElement)("div",{className:"cpgw_p_info"},(0,c.createElement)("div",{className:"cpgw_address_wrap"},(0,c.createElement)("strong",null,o.wallet,":"),(0,c.createElement)("span",{className:"cpgw_p_address"},d)),(0,c.createElement)("div",{className:"cpgw_p_network"},(0,c.createElement)("strong",null,o.network,":")," ",e.networkResponse.decimal_networks[m?.id]?e.networkResponse.decimal_networks[m?.id]:m.name))),(0,c.createElement)(s.D8,{data:e,const_msg:o}),!u&&(0,c.createElement)(s.PP,{const_msg:o}))},i=e=>{try{const[t,n]=(0,c.useState)(null);return(0,c.useEffect)((()=>{const t={appName:"Pay With Metamask",appDescription:window.location.host,chains:e.networks,appUrl:window.location.host,appIcon:"https://family.co/logo.png"};n((e=>{const t=(0,r._K)({appName:e.appName,chains:[e.chains],appDescription:e.appDescription,appUrl:e.appUrl,appIcon:e.appIcon});if(t){const e=[];e.push("metaMask");const n=t.connectors.filter((t=>{if(e.includes(t.id))return e.includes(t.id)}));return t.connectors=n,(0,a._g)(t)}})(t))}),[e.networks]),(0,c.createElement)(c.Fragment,null,t&&e.networks&&(0,c.createElement)(a.eM,{config:t},(0,c.createElement)(r.bO,{options:{hideBalance:!0,hideQuestionMarkCTA:!0},mode:"auto"},(0,c.createElement)(m,{currentchain:e}))))}catch(e){console.log(e)}};var p=n(79896),u=n(71257);const d=async(e,{nonce:t,restUrl:n})=>{try{if(e){const c={_wpnonce:t,total_amount:e};u.Z.defaults.headers.common["X-WP-Nonce"]=t;const a=`${n}update-price`;return(await u.Z.post(a,c)).data}}catch(e){console.error(e)}},w=async(e,{nonce:t,restUrl:n})=>{try{if(e){const c={_wpnonce:t,symbol:e};u.Z.defaults.headers.common["X-WP-Nonce"]=t;const a=`${n}selected-network`;return(await u.Z.post(a,c)).data}}catch(e){console.error(e)}};function _(){const{enabledCurrency:e,const_msg:t,currency_lbl:n,decimalchainId:a,active_network:r}=connect_wallts,[o,l]=(0,c.useState)(null),[m,u]=(0,c.useState)(null),[_,h]=(0,c.useState)(null),[y,E]=(0,c.useState)(!1),[k,g]=(0,c.useState)(!1),f=document.querySelector('input[name="payment_method"]:checked')?.value,N=document.querySelector("button#place_order"),v=document.querySelector(".order-total").textContent.replace(/[^\d.]/g,"");(0,c.useEffect)((()=>{v&&b(Number(v))}),[v]),(0,c.useEffect)((()=>{N.disabled="cpgw"===f}),[f]),(0,c.useEffect)((()=>{S(e)}),[e]);const b=async e=>{try{const t=await d(e,connect_wallts);S(t)}catch(e){console.error("Error fetching data:",e)}},S=async e=>{let t=[];0!==e.length&&(Object.values(e).forEach(((e,n)=>{if(!e.price)return;const a={value:e.symbol,label:(0,c.createElement)("span",{key:`cpgw_logos_${n}`,className:"cpgw_logos"},(0,c.createElement)("img",{key:`cpgw_logo_${n}`,src:e.url,alt:e.symbol,style:{width:"28px",height:"28px"}})," ",e.price," ",e.symbol),rating:e.price};t.push(a)})),h(t))};return(0,c.createElement)("div",{key:"show_currency_wrapper"},_?(0,c.createElement)(c.Fragment,null,(0,c.createElement)("div",{key:n,className:"cpgwp_currency_lbl"},n),(0,c.createElement)(p.ZP,{name:"cpgwp_crypto_coin",value:m,onChange:async e=>{E(!0),u(e);const t=await(0,s.M7)(a),n=await w(e.value,connect_wallts);g(n),l(t),E(!1)},options:_,placeholder:t.select_cryptocurrency}),(0,c.createElement)("input",{key:r,type:"hidden",name:"cpgw_payment_network",value:r}),y&&(0,c.createElement)(s.aN,{loader:1,width:250}),!y&&o&&(0,c.createElement)(i,{networks:o,currentprice:m,networkResponse:k})):(0,c.createElement)(s.aN,{loader:1,width:250}))}}}]);