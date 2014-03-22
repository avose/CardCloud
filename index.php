<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CardCloud</title>
  <meta name="description" content="Virtual Physical Card Simulation">
  <meta name="author" content="Aaron Vose">
</head>
<body id="body" onload="StartCardCloud()" style="-webkit-touch-callout: none; -webkit-user-select: none; -khtml-user-select: none; -moz-user-select: none; -ms-user-select: none; -o-user-select: none; user-select: none;">


<script>


/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////


/*
    Some code in this section rewritten from:

    "Testing Dragging of SVG Entities
    Copyright(c) 2005, Jeff Schiller"
    http://www.codedread.com/blog/archives/2005/12/21/how-to-enable-dragging-in-svg/
*/
var draggingElement = null;

// Following is from Holger Will since ASV3 and O9 do not support getScreenTCM()
// See http://groups.yahoo.com/group/svg-developers/message/50789
function getScreenCTM(doc){
    if( doc.getScreenCTM ) {
        return doc.getScreenCTM(); 
    }
    
    var root=doc;
    var sCTM=root.createSVGMatrix();
    var tr  =root.createSVGMatrix();
    var par =root.getAttribute("preserveAspectRatio");
    
    if (par==null || par=="") {
        // setting to default value
        par="xMidYMid meet";
    }
    
    parX=par.substring(0,4); //xMin;xMid;xMax
    parY=par.substring(4,8); //YMin;YMid;YMax;
    ma=par.split(" ");
    mos=ma[1];               //meet;slice
    
    //get dimensions of the viewport
    sCTM.a=1;
    sCTM.d=1;
    sCTM.e=0;
    sCTM.f=0;
    
    w=root.getAttribute("width")
    if( w==null || w=="" ) {
        w=innerWidth;
    }
    h=root.getAttribute("height")
    if( h==null || h=="" ) {
        h=innerHeight;
    }
    
    // Jeff Schiller:  Modified to account for percentages - I'm not 
    // absolutely certain this is correct but it works for 100%/100%
    if(w.substr(w.length-1, 1) == "%") {
        w = (parseFloat(w.substr(0,w.length-1)) / 100.0) * innerWidth;
    }
    if(h.substr(h.length-1, 1) == "%") {
        h = (parseFloat(h.substr(0,h.length-1)) / 100.0) * innerHeight;
    }
    
    // get the ViewBox
    vba=root.getAttribute("viewBox");
    if(vba==null) {
        vba = "0 0 " + w + " " + h;
    }
    var vb=vba.split(" ")//get the viewBox into an array
    
    //--------------------------------------------------------------------------
    //create a matrix with current user transformation
    tr.a=root.currentScale;
    tr.d=root.currentScale;
    tr.e=root.currentTranslate.x;
    tr.f=root.currentTranslate.y;
    
    //scale factors
    sx=w/vb[2];
    sy=h/vb[3];
    
    //meetOrSlice
    if( mos=="slice" ){
        s=(sx>sy ? sx:sy);
    } else {
        s=(sx<sy ? sx:sy);
    }
    
    //preserveAspectRatio="none"
    if( par=="none" ){
        sCTM.a=sx//scaleX
        sCTM.d=sy//scaleY
        sCTM.e=-vb[0]*sx //translateX
        sCTM.f=-vb[0]*sy //translateY
        sCTM=tr.multiply(sCTM)//taking user transformations into acount
	
        return sCTM;
    }
    
    sCTM.a=s //scaleX
    sCTM.d=s //scaleY
    //-------------------------------------------------------
    switch(parX){
    case "xMid":
        sCTM.e=((w-vb[2]*s)/2) - vb[0]*s //translateX
        break;
    case "xMin":
        sCTM.e=- vb[0]*s//translateX
        break;
    case "xMax":
        sCTM.e=(w-vb[2]*s)- vb[0]*s //translateX
        break;
    }
    //------------------------------------------------------------
    switch(parY){
    case "YMid":
        sCTM.f=(h-vb[3]*s)/2 - vb[1]*s //translateY
        break;
    case "YMin":
        sCTM.f=- vb[1]*s//translateY
        break;
    case "YMax":
        sCTM.f=(h-vb[3]*s) - vb[1]*s //translateY
        break;
    }
    sCTM=tr.multiply(sCTM)//taking user transformations into acount
    
    return sCTM;
}


/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////


var  Cards = new Array();
var nCards = 0;
var keymode = 'm';
var svgel;
var tfbox;

var lastTime = 0;
var framenum = 0;

var mdown = false;
var mx    = 0;
var my    = 0;
var tx    = 0;
var ty    = 0;
var zoom  = 1.0;


/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////


function keymode2str()
{
  switch( keymode ) {
  case 't':
    return "Tap/Untap";
  case 'd':
    return "Delete";
  case 'm':
    return "Move";
  case 'f':
    return "Flip";
  }
}


function update_cursor()
{
  switch( keymode ) {
  case 't':
    svgel.style.cursor="url(png/arrow_refresh.png),crosshair";
    break;
  case 'd':
    svgel.style.cursor="url(png/cross.png),not-allowed";
    break;
  case 'm':
    svgel.style.cursor="move";
    break;
  case 'f':
    svgel.style.cursor="url(png/arrow_switch.png),crosshair";
    break;
  }
}


function keyPress(evt)
{ 
  var charkey = '\0';

  if (evt.which == null) {
    charkey = String.fromCharCode(evt.keyCode);
  } else if (evt.which!=0 && evt.charCode!=0) {
    charkey = String.fromCharCode(evt.which);
  }

  switch( charkey ) {
  case 't':
    keymode = 't';
    break;
  case 'd':
    keymode = 'd';
    break;
  case 'm':
    keymode = 'm';
    break;
  case 'f':
    keymode = 'f';
    break;
  }

  update_cursor();
  update_textframe();
}

    
function mouseDown(evt) 
{ 
    var target = evt.currentTarget;
    var ndx = parseInt(target.getAttribute("id"));
    var c = Cards[ndx];
    var i;

    if( !c ) {
      return;
    }

    switch( keymode ) {
    case 'm':
      draggingElement = target;
      tfbox.appendChild(target);
      c.z = nCards-1;
      for(i=c.id; i<nCards; i++) {
	Cards[i].z--;
      }
      break;
    case 'd':
      target.setAttribute("visibility", "hidden");
      c.hidden = true;
      break;
    case 'f':
      if( c.flipped == true ) {
	c.flipped = false;
      } else {
	c.flipped = true;
      }
      break;
    case 't':
      if( c.tapped ) {
	c.tapped = false;
      } else {
	c.tapped = true;
      }
      update_cards();
      break;
    }
}


function mouseUp(evt) 
{ 
    mdown = false;

    if(draggingElement) {
	// Unlock node position in mem
	var c = Cards[ parseInt(draggingElement.getAttribute("id")) ];
	c.locked = false;
    }

    draggingElement = null;
}


function mouseMove(evt) 
{ 
    if( svgel == undefined ) {
	return;
    }
    var p = svgel.createSVGPoint();
    p.x = evt.clientX;
    p.y = evt.clientY;
    var m = getScreenCTM(tfbox);
    p = p.matrixTransform(m.inverse());
    
    if( draggingElement ) {
        // Update circle position
        draggingElement.setAttribute("dragx", p.x);
        draggingElement.setAttribute("dragy", p.y);
	// Update node position in mem
	var c = Cards[ parseInt(draggingElement.getAttribute("id")) ];
	c.l[0] = p.x;
	c.l[1] = p.y;
	c.locked = true;
    }
}


function init() {
    svgel = document.getElementById("svgelement");
    tfbox = svgel.getElementById("tfbox");
    
    svgel.style.cursor="move";
    document.addEventListener('keypress',keyPress,false);

    select_deck();
}


/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////


function handle_delta(delta)
{
    zoom *= (delta>0)?(1.05):(1/1.05);
}


function handleMouseDown(event)
{
    mdown = true;
    mx    = event.clientX;
    my    = event.clientY;
}


function handleMouseUp(event)
{
    // Only in move mode
    if( keymode != 'm' ) {
      return;
    }

    mdown = false;
    if( !draggingElement ) {
	// Update translate values for x and y
	tx += event.clientX - mx;
	ty += event.clientY - my;

	// Move new coords over to old ones
	mx = event.clientX;
	my = event.clientY;
	
	// Update the container for fast response
	update_container();
    }
}


function handleMouseMove(event)
{
    // Skip if the mouse isn't down
    if( !mdown ) {
	return;
    }

    // Only in move mode
    if( keymode != 'm' ) {
      return;
    }

    if( !draggingElement ) {
	// Update translate values for x and y
	tx += event.clientX - mx;
	ty += event.clientY - my;
	
	// Move new coords over to old ones
	mx = event.clientX;
	my = event.clientY;
	
	// Update the container for fast response
	update_container();
    }
}


///////////////////////////////////////////////
// Render Code
///////////////////////////////////////////////


function clear_svg()
{
    var tfbox = svgel.getElementById("tfbox");

    while( tfbox.lastChild ) {
	tfbox.removeChild(tfbox.lastChild);
    }
    //lastTime = 0;
    Cards    = [];
    nCards   = 0;
}


function fill_cards()
{
    var i;
    
    for(i=0; i<nCards; i++) {
      var card = document.createElementNS("http://www.w3.org/2000/svg", "image");
      card.setAttribute("x", "0");
      card.setAttribute("y", "0");
      card.setAttribute("width", "200px");
      card.setAttribute("height", "200px");
      card.setAttributeNS('http://www.w3.org/1999/xlink','href',Cards[i].img);
      card.setAttribute("draggable", "false");
      card.setAttribute("transform", "translate(" + (Cards[i].l[0]-100) + "," + (Cards[i].l[1]-100) + ")");
      card.setAttribute("dragx", Cards[i].l[0]);
      card.setAttribute("dragy", Cards[i].l[1]);
      card.setAttribute("id", Cards[i].id);
      card.setAttribute("opacity", "1.00");
      card.addEventListener("mousedown", mouseDown, false);
      tfbox.appendChild(card);
    }    
}


function update_cards()
{
    var i;

    for(i=0; i<nCards; i++) {
      var card = svgel.getElementById(Cards[i].id);
      if( card ) {
        card.setAttribute("dragx", Cards[i].l[0]);
        card.setAttribute("dragy", Cards[i].l[1]);
	if( Cards[i].tapped == true ) {
	  card.setAttribute("transform", "translate(" + (Cards[i].l[0]-100) + "," + (Cards[i].l[1]-100) + ") rotate(-90,100,100)");
	} else {
	  card.setAttribute("transform", "translate(" + (Cards[i].l[0]-100) + "," + (Cards[i].l[1]-100) + ")");
	}
	if( Cards[i].flipped == true ) {
	  card.setAttributeNS('http://www.w3.org/1999/xlink','href',"cards/card_back.jpeg");
	} else {
	  card.setAttributeNS('http://www.w3.org/1999/xlink','href',Cards[i].img);
	}
      }
    }
}



function update_container()
{
    tfbox.setAttribute("transform","scale("+zoom+") "+"translate("+tx+","+ty+")");
}


function update_textframe()
{
  var frame = svgel.getElementById("frame");
  if( frame ) {
    frame.firstChild.nodeValue = "mode: " + keymode2str(); // + " frame: " + framenum;
  }
}


function draw()
{
    if( lastTime == 0 ) {
	// This is the first time we are drawing.
	// Treat this as a kind of init.
	fill_cards();
	window.setTimeout(svg_tick,50);
    } else {
	update_container();
	update_cards();
	update_textframe();
    }
}


////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////


///////////////////////////////////////////////
// Startup Code
///////////////////////////////////////////////


function animate() 
{
    var timeNow = new Date().getTime();
    if (lastTime != 0) {
        var elapsed = timeNow - lastTime;
    }
    lastTime = timeNow;
}


function svg_tick() 
{
    // Advance frame counter
    framenum++;

    // Draw the scene
    draw();

    // Wait a bit before we draw again
    window.setTimeout(svg_tick,100);

    // Handle any needed animation changes
    animate();
}


function StartCardCloud()
{
    window.ondragstart = function() { return false; }
    clear_svg();
    window.setTimeout(svg_tick,50);
}


///////////////////////////////////////////////


function AddCards(acards)
{
  var i;

  clear_svg();
  
  for(i=0; i<acards.length; i++) {
    Cards[nCards]     = new Object();
    Cards[nCards].id  = nCards;
    Cards[nCards].img = String(acards[i]);
    Cards[nCards].n   = "";
    Cards[nCards].l = new Array();
    Cards[nCards].l[0] = (Math.floor(Math.random()*100)) - 50 + 150;
    Cards[nCards].l[1] = (Math.floor(Math.random()*100)) - 50 + 150;
    Cards[nCards].m = 1.0;
    Cards[nCards].z = nCards;
    nCards++;
  }

  fill_cards();
}


/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////


</script>


<script type="text/javascript" src="mwheel.js"></script>
<script type="text/javascript" src="deck.js"></script>


<!-- /////////////////////////////////////////////////////// -->


<center>
<h2 style="margin-bottom:0;padding-bottom:0;"><img src="png/images.png"/> CardCloud <img src="png/images.png"/></h2>
by <a href="http://www.aaronvose.net/">Aaron Vose</a> -- v. CardCloud-0.0.1-alpha (3/21/2014)
<br/><br/>
</center>


<!-- /////////////////////////////////////////////////////// -->

<center>
<table>
<tr><td>
<svg version="1.1" viewBox="0 0 854 480"
    style="-webkit-touch-callout: none; -webkit-user-select: none; -khtml-user-select: none; -moz-user-select: none; -ms-user-select: none; -o-user-select: none; user-select: none; vertical-align:top; width:854px; height:480px;"
    xmlns="http://www.w3.org/2000/svg" 
    xmlns:xlink="http://www.w3.org/1999/xlink"
    onload="init()"
    onmouseup="mouseUp(evt)"
    onmousemove="mouseMove(evt)"
    preserveAspectRatio="xMinYMax";
    id="svgelement"
    >
    <defs>
    <pattern id="board-bg" x="0" y="0" patternunits="userSpaceOnUse" width="256" height="256">
      <image xlink:href="cards/board_felt.jpeg" width="256" height="256" x="0" y="0">
    </pattern>
    </defs>

    <desc>CardCloud SVG Display Area</desc>

    <rect id="background" x="0" y="0" width="854" height="480" fill="url(#board-bg)" stroke="black" onmouseup="handleMouseUp(evt)" onmousedown="handleMouseDown(evt)" onmousemove="handleMouseMove(evt)" />

    <g id="tfbox" transform="" width="856" height="480" ></g>

    <text id="frame" x="700" y="16" font-size="16" fill="black">mode: Move</text>

</svg>
<br><br>


<!-- /////////////////////////////////////////////////////// -->


<small>
<table border="1px" cellspacing="0px" cellpadding="4px">
<tr><td style="align:center">
<img src="png/images.png"/> Deck:
<input type="file" id="deckfiles" name="files[]" />
<span id="decklist"></span>
</td><td>
<table><tr><td>
<ul>
    <li>d: delete cards</li>
    <li>t: tap/untap cards</li>
    <li>m: move cards</li>
</ul>
</td><td>
<ul>
    <li>f: flip cards</li>
    <li>mwheel: zoom in/out</li>
</ul>
</td></tr></table>
</td></tr></table>
</small>


<!-- /////////////////////////////////////////////////////// -->


<small>
<p>

</p>
</small>


<!-- /////////////////////////////////////////////////////// -->


<p>
<small>
Icons by <a href="http://www.famfamfam.com/">http://www.famfamfam.com</a>.<br/>
</small></p>
<br/><br/>


</td></tr></table>
</center>


<!-- /////////////////////////////////////////////////////// -->


</body>
</html>
