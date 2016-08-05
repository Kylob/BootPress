/* This script and many more are available free online at

The JavaScript Source!! http://www.javascriptsource.com

Created by: Kenny Orovic :: http://www.geocities.com/ax_c/ */



// size of Sparkler

var s=80;



// Sparkle size range

var fs=1;

var fb=30;



// sparkle shape or text

var st="*";



// blink rate (may affect time)

var br=5;



// blink time (may affect rate)

var bt=70;



// colors

var cl=new Array("aaffee","ccff77","ffcc44","ffaa22","ffbb66","ffff88")



// number of colors

var nc=6;



// fallow mouse (true or false)

var fm = true;



// Do not alter anything below

/***************************************************/

var bl=0;

var bi=0;

var a=s/2;

blink=setInterval('blinker(0,0)', br);

clearInterval(blink);

function add(x,y){

  clearInterval(blink);

  document.all.planet.style.visibility = "visible";

  to=x;

  le=y;

  blink=setInterval('blinker(to,le)', br);

  bl=0;

}

function fallow(x,y){

  to=x;

  le=y;

  clearInterval(blink);

  blink=setInterval('blinker(to,le)', br);

}

function blinker(x,y){

  if(fm==true)

  document.onmousemove=new Function("fallow(event.x,event.y);return false")

  c=Math.floor(Math.random()*s);

  d=Math.floor(Math.random()*s);

  f=Math.floor(Math.floor(Math.random() * (fb - fs + 1) + fs));

  document.all.planet.style.color=cl[bi];

  document.all.planet.style.font=f+"px;";

  document.all.planet.style.left=x-c+a;

  document.all.planet.style.top=y-d+a;

  bl=bl+1;

  bi=bi+1;

    if(bi==nc){

    bi=0;

  }

  if(bl==bt){

  clearInterval(blink);

    document.all.planet.style.visibility = "hidden";

    document.all.planet.style.left=-s;

    document.all.planet.style.top=-s;

    document.all.planet.style.color=000000;

    document.all.planet.style.font=1+"px;";

    document.onmousemove=new Function("return false")

    bl=0;

  }

}

document.oncontextmenu=new Function("add(event.x,event.y);return false")

document.write("<span id=planet style=visibility:hidden;position:absolute;top:-80px;left:-80px>"+st+"</span>")

