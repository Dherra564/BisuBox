const A = require('../Publico/js/algoritmosVista.js');
let fallas = 0;
function ok(cond, nombre, extra) { if (!cond) fallas++; console.log((cond ? 'OK   ' : 'FALLA') + ' ' + nombre + (extra ? '  ' + extra : '')); }
function lienzo(w, h, fondo, ruido) {
  const d = new Uint8ClampedArray(w * h * 4); let s = 7;
  const rnd = () => { s = (s * 16807) % 2147483647; return s / 2147483647; };
  for (let p = 0; p < w * h; p++) { const n = (rnd() - .5) * ruido; d[p*4]=fondo[0]+n; d[p*4+1]=fondo[1]+n; d[p*4+2]=fondo[2]+n; d[p*4+3]=255; }
  return { ancho: w, alto: h, datos: d };
}
function pintar(img, cond, color) { for (let y=0;y<img.alto;y++) for (let x=0;x<img.ancho;x++) if (cond(x,y)) { const i=(y*img.ancho+x)*4; const c = typeof color==='function'?color(x,y):color; img.datos[i]=c[0];img.datos[i+1]=c[1];img.datos[i+2]=c[2]; } }
const circulo = (cx,cy,r) => (x,y) => (x-cx)**2+(y-cy)**2 <= r*r;
const VERDE=[52,163,92];
function analizar(img, tol=70) { const m=A.quitarFondo(img,tol); const s=A.separarPiezas(m); return {m,s}; }

let img = lienzo(400,250,VERDE,16);
const centros=[[40,60],[110,60],[180,60],[250,60],[320,60],[60,180],[160,180],[280,180]];
centros.forEach(([x,y],k)=>pintar(img,circulo(x,y,18),[[200,40,40],[215,165,41],[46,127,209],[224,85,142],[226,178,58],[185,188,196],[240,236,226],[155,107,214]][k]));
let r = analizar(img);
ok(r.s.piezas.length===8,'1 ocho piezas separadas', r.s.piezas.length+' piezas');
const rec = A.recortarPieza(img, r.m, r.s, r.s.piezas[0], false);
let verdes=0; for(let i=0;i<rec.datos.length;i+=4){ if(rec.datos[i+3]>0 && rec.datos[i+1]>140 && rec.datos[i]<80) verdes++; }
ok(verdes===0,'1 sin borde verde en el recorte', verdes+' pixeles verdes');

img = lienzo(200,200,VERDE,16);
pintar(img, circulo(110,112,40), (x,y)=>[VERDE[0]*0.55,VERDE[1]*0.55,VERDE[2]*0.55]);
pintar(img, circulo(100,100,35), [200,40,40]);
r = analizar(img);
const p2=r.s.piezas[0];
ok(r.s.zonasSospechosas.length===0,'2 la sombra junto a la pieza no se marca como sospechosa');
ok(r.s.piezas.length===1 && p2.ancho<=73 && p2.ancho>=69,'2 sombra quitada, pieza entera', 'ancho '+(p2&&p2.ancho)+' (esperado ~71)');

img = lienzo(200,200,VERDE,16);
pintar(img,(x,y)=>circulo(100,100,50)(x,y)&&!circulo(100,100,36)(x,y),[190,190,198]);
r = analizar(img);
ok(r.s.piezas.length===1 && r.s.piezas[0].tieneHueco,'3 aro: centro transparente');
const rc3=A.recortarPieza(img,r.m,r.s,r.s.piezas[0],false); const mid=((rc3.alto>>1)*rc3.ancho+(rc3.ancho>>1))*4;
ok(rc3.datos[mid+3]===0,'3 el centro del recorte es transparente');
const rc3b=A.recortarPieza(img,r.m,r.s,r.s.piezas[0],true);
ok(rc3b.datos[mid+3]===255,'3 con "rellenar huecos" el centro queda lleno');

img = lienzo(200,200,VERDE,16);
pintar(img,circulo(100,100,40),[238,234,224]); pintar(img,circulo(88,86,8),[255,255,255]);
r = analizar(img);
const rc4=A.recortarPieza(img,r.m,r.s,r.s.piezas[0],false); const brillo=((86-r.s.piezas[0].y0+2)*rc4.ancho+(88-r.s.piezas[0].x0+2))*4;
ok(r.s.piezas.length===1 && rc4.datos[brillo+3]===255 && !r.s.piezas[0].tieneHueco,'4 el brillo de la perla blanca no se borra');

img = lienzo(200,200,VERDE,16);
pintar(img,circulo(100,100,45),[30,90,50]); pintar(img,circulo(100,100,38),[56,168,96]);
r = analizar(img);
ok(r.s.zonasSospechosas.length===1,'5 pieza del color del fondo: zona sospechosa avisada', r.s.zonasSospechosas.length+' zonas');

img = lienzo(500,200,VERDE,16);
[[60,100],[150,100],[240,100]].forEach(([x,y])=>pintar(img,circulo(x,y,25),[200,40,40]));
pintar(img,circulo(360,100,25),[46,127,209]); pintar(img,circulo(405,100,25),[46,127,209]);
r = analizar(img);
ok(r.s.piezas.length===4 && r.s.piezas[3].posiblesPegadas,'6 piezas pegadas avisadas', r.s.piezas.map(p=>p.posiblesPegadas).join(','));

img = lienzo(600,220,VERDE,16);
const corazon=(cx,cy,s)=>(x,y)=>{const X=(x-cx)/s, Y=-(y-cy)/s; return (X*X+Y*Y-1)**3 - X*X*Y**3 <= 0;};
pintar(img,corazon(80,110,55),[224,85,142]);
pintar(img,(x,y)=>Math.abs(x-230)<=55&&Math.abs(y-110)<=16,[185,188,196]);
const estrella=(cx,cy,R)=>(x,y)=>{const a=Math.atan2(y-cy,x-cx), d=Math.hypot(x-cx,y-cy); const k=((a+Math.PI/2)%(2*Math.PI/5)+2*Math.PI/5)%(2*Math.PI/5)/(2*Math.PI/5); const rr=R*(0.45+0.55*Math.abs(1-2*k)); return d<=rr;};
pintar(img,estrella(390,110,60),[226,178,58]);
pintar(img,(x,y)=>((x-520)/50)**2+((y-110)/30)**2<=1,[46,127,209]);
r = analizar(img);
ok(r.s.piezas.length===4 && r.s.piezas.every(p=>!p.posiblesPegadas),'6 corazón, tubo, estrella y óvalo no se marcan como pegadas', r.s.piezas.map(p=>p.posiblesPegadas).join(','));

img = lienzo(200,200,VERDE,16);
pintar(img,circulo(10,100,40),[200,40,40]);
r = analizar(img);
ok(r.s.piezas[0].tocaBorde,'7 pieza que toca el borde marcada');
img = lienzo(200,200,VERDE,16); pintar(img,(x,y)=>x<70,[200,40,40]);
ok(!A.colorDeFondo(img,70).parejo,'7 fondo no parejo detectado');

img = lienzo(1000,750,VERDE,16);
for(let k=0;k<30;k++) pintar(img,circulo(60+(k%10)*95,100+Math.floor(k/10)*250,30),[200,40,40]);
let t=Date.now(); r=analizar(img); t=Date.now()-t;
ok(r.s.piezas.length===30 && t<1000,'8 foto grande procesada', t+' ms, '+r.s.piezas.length+' piezas');

img = lienzo(300,200,VERDE,16); pintar(img,circulo(100,100,30),[200,40,40]); pintar(img,circulo(250,50,3),[20,20,20]);
r = analizar(img);
ok(r.s.piezas.length===1 && r.s.descartadas===1,'manchas pequeñas descartadas y contadas');

const g = A.girar90({ancho:2,alto:1,datos:new Uint8ClampedArray([1,0,0,255, 2,0,0,255])});
ok(g.ancho===1&&g.alto===2&&g.datos[0]===1&&g.datos[4]===2,'girar 90 grados');

const cat = {1:{tamano:80,existencia:20},2:{tamano:60,existencia:40},3:{tamano:140,existencia:3},4:{tamano:100,existencia:0}};
ok(JSON.stringify(A.listaFinal([3,1,2],true))==='[2,1,3,1,2]','lista simétrica');
const exacto=[]; for(let k=0;k<30;k++) exacto.push(2);
ok(A.revisarDiseno(exacto,false,1800,cat)===null,'10 pulsera de 18 cm llena exacto se acepta');
const casi=[]; for(let k=0;k<29;k++) casi.push(2);
const p11=A.revisarDiseno(casi.concat([1]),false,1800,cat);
ok(p11 && p11.tipo==='espacio','11 una pieza más no cabe');
const p12=A.revisarDiseno([1,3,3],true,1800,cat);
ok(p12 && p12.tipo==='existencia' && p12.pedidas===4 && p12.hay===3,'12 simétrico pide 4 y hay 3');
const p13=A.revisarDiseno([4],false,1800,cat);
ok(p13 && p13.tipo==='existencia','13 agotado no se agrega');
const largo=A.recortarHastaQueSirva(exacto,false,1500,cat);
ok(A.largoUsado(largo,cat)<=1500 && largo.length===25,'14 al acortar se quitan las últimas');
const pat=A.repetirPatron([1,2],false,1800,cat);
ok(pat.motivo==='espacio' && A.largoUsado(pat.elegidas,cat)<=1800 && A.largoUsado(pat.elegidas,cat)>1800-80,'repetir patrón llena hasta donde cabe', A.largoUsado(pat.elegidas,cat));
const pat2=A.repetirPatron([3,3],true,9999,cat);
ok(pat2.motivo==='existencia','repetir patrón se detiene por existencias');
ok(A.aDecimas('8.3')===83 && A.aDecimas(0.1+0.2)===3,'medidas en décimas enteras');
const pos=A.acomodarCirculo([1,1],cat,1800,0,0,100);
ok(Math.abs(pos[0].y-pos[1].y)<1e-9 && pos[0].x<0 && pos[1].x>0 && Math.abs(pos[0].giro+pos[1].giro)<1e-9,'pulsera centrada abajo y simétrica');
const curva=A.curvaCollar(280,40,215,430);
const posC=A.acomodarCollar([3],cat,4500,curva);
ok(Math.abs(posC[0].x-280)<1 && Math.abs(posC[0].giro)<0.02,'collar: el centro cae en el punto más bajo');
const cat2={1:{tamano:140,existencia:9},2:{tamano:60,existencia:99},3:{tamano:80,existencia:99},7:{tamano:80,existencia:99}};
const pr=A.repetirPatron([1,2,3,2,7],true,1800,cat2);
const usado=A.largoUsado(A.listaFinal(pr.elegidas,true),cat2);
ok(pr.relleno===2 && usado>=1800*0.95 && usado<=1800,'repetir patrón completa el final para poder cerrar', usado);
ok(A.largoParaCerrar(1700,1500,2200,50,0.95)===1700 && A.largoParaCerrar(1720,1500,2200,50,0.95)===1750 && A.largoParaCerrar(100,1500,2200,50,0.95)===null,'largo sugerido para cerrar');
ok(A.esColorClaro(245,244,240) && !A.esColorClaro(47,158,87) && !A.esColorClaro(200,40,40),'detecta hoja blanca');
img = lienzo(200,200,[246,245,242],10); pintar(img,circulo(60,100,30),[238,236,230]); pintar(img,circulo(150,100,30),[200,40,40]);
r = analizar(img, 40);
ok(r.s.piezas.length>=1 && !A.esPiezaClara(A.recortarPieza(img,r.m,r.s,r.s.piezas[r.s.piezas.length-1],true)),'la pieza roja sobre hoja blanca no se marca como clara');
img = lienzo(200,200,VERDE,16); pintar(img,circulo(100,100,40),[238,234,224]);
r = analizar(img);
ok(A.esPiezaClara(A.recortarPieza(img,r.m,r.s,r.s.piezas[0],true)),'una perla blanca se reconoce como pieza clara');
img = lienzo(400,250,[244,243,239],10); pintar(img,circulo(100,125,40),[236,233,226]); pintar(img,circulo(280,125,40),[192,48,42]);
r = analizar(img);
const borradas = A.buscarPiezasBorradas(img, 70, r.s);
ok(r.s.piezas.length===1 && borradas.length===1 && borradas[0].x0>50 && borradas[0].x1<150,'perla blanca borrada por la hoja blanca se detecta', r.s.piezas.length+' piezas, '+borradas.length+' borradas');
img = lienzo(400,250,[244,243,239],10); pintar(img,circulo(280,125,40),[192,48,42]);
r = analizar(img);
ok(A.buscarPiezasBorradas(img, 70, r.s).length===0,'sin piezas claras no hay falsas alarmas');
console.log(fallas ? fallas + ' FALLAS' : 'TODO BIEN');
process.exitCode = fallas ? 1 : 0;
