
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

    
function mouseDown(evt) 
{ 
    var target = evt.currentTarget;
    draggingElement = target;
}


function mouseUp(evt) 
{ 
    mdown = false;

    if(draggingElement) {
	// Unlock node position in mem
	var n = Nodes[ parseInt(draggingElement.getAttribute("id")) ];
	n.locked = false;
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
	var n = Nodes[ parseInt(draggingElement.getAttribute("id")) ];
	n.l[0] = p.x;
	n.l[1] = p.y;
	n.locked = true;
    }
}


function txtmouseDown(evt) 
{ 
    var target = evt.currentTarget;
    var id = target.id;

    id = id.substr(0, id.length-(" - text".length));
    target = svgel.getElementById(id);
    draggingElement = target;
}


function init() {
    svgel = document.getElementById("svgelement");
    tfbox = svgel.getElementById("tfbox");
}


/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////
// SVG user interface device code
///////////////////////////////////////////////


var Root     = new Object();
var MaxDepth = 0;
var Newick;

var svgel;
var tfbox;

var XMLHTTPClient;

var lastTime = 0;
var framenum = 0;

var mdown = false;
var mx    = 0;
var my    = 0;
var tx    = 0;
var ty    = 0;
var zoom  = 1.0;

var font_size = 12;



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
    lastTime = 0;
}


function fill_nodes(n,d)
{
    var i;
    
    // Add ourself
    var card = document.createElementNS("http://www.w3.org/2000/svg", "image");
    card.setAttribute("x", "0");
    card.setAttribute("y", "0");
    card.setAttribute("width", "200px");
    card.setAttribute("height", "200px");
    card.setAttributeNS('http://www.w3.org/1999/xlink','href','cards/card_redirect.jpeg');
    card.setAttribute("draggable", "false");
    card.setAttribute("transform", "translate(" + (n.l[0]-100) + "," + (n.l[1]-100) + ")");
    card.setAttribute("fill", "rgb(64,64,128)");
    card.setAttribute("dragx", n.l[0]);
    card.setAttribute("dragy", n.l[1]);
    card.setAttribute("id", n.id);
    card.setAttribute("opacity", "1.00");
    card.addEventListener("mousedown", mouseDown, false);
    tfbox.appendChild(card);

    // Add our children
    for(i=0; i < n.nc; i++) {
	// Process the child
	fill_nodes(n.c[i],d+1);
    }
    
}


function fill_labels(n,d)
{
    var i;
    
    // Add a label for ourself
    if( !(n.nc) || (n.id == 0) || ((n.collapsedname != undefined) && (n.collapsedname != "")) ) {
	var newText = document.createElementNS("http://www.w3.org/2000/svg","text");
	newText.setAttributeNS(null,"x",0);
	newText.setAttributeNS(null,"y",0);
	newText.setAttributeNS(null,"transform", "translate(" + (n.l[0]) + "," + (n.l[1]-5) + ")");
	newText.setAttributeNS(null,"font-size",font_size);
	newText.setAttributeNS(null,"text-anchor","middle");
	newText.setAttributeNS(null,"fill-opacity","1.0");
	newText.setAttributeNS(null,"fill","black");
	newText.setAttributeNS(null,"id",n.id + " - text");
	newText.addEventListener("mousedown", txtmouseDown, false);
	newText.addEventListener("mouseup", mouseUp, false);
	var textNode;
	if( (n.collapsedname != undefined) && (n.collapsedname != "") ) {
	    textNode = document.createTextNode(n.collapsedname);
	} else {
	    textNode = document.createTextNode(n.n);
	}
	newText.appendChild(textNode);
	tfbox.appendChild(newText);
    }
    
    // Add our children
    for(i=0; i < n.nc; i++) {
	// Process the child
	fill_labels(n.c[i],d+1);
    }
    
}


function update_node(n)
{
    var i;

    // Update our position
    var card = svgel.getElementById(n.id);
    if( card ) {
        card.setAttribute("dragx", n.l[0]);
        card.setAttribute("dragy", n.l[1]);
        card.setAttribute("transform", "translate(" + (n.l[0]-100) + "," + (n.l[1]-100) + ")");
    }
    
    // Update our label
    /*
    var label = svgel.getElementById(n.id + " - text");
    if( label ) {
        label.setAttributeNS(null,"transform", "translate(" + (n.l[0]) + "," + (n.l[1]-5) + ")");
	if( n.selected ) {
	    label.setAttributeNS(null,"font-size",font_size*1.3);
	} else {
	    label.setAttributeNS(null,"font-size",font_size);
	}
    }
    */
}


function update_pos(n)
{
    var i;

    // Update this node
    update_node(n);

    // Update our children
    for(i=0; i < n.nc; i++) {
	update_pos(n.c[i]);
    }
}


function update_container()
{
    tfbox.setAttribute("transform","scale("+zoom+") "+"translate("+tx+","+ty+")");
}


function draw()
{
    if( lastTime == 0 ) {
	// This is the first time we are drawing the fdp.
	// Treat this as a kind of init, and load the tree data
	// into the svg object.
	fill_nodes(Root,1);
	//fill_labels(Root,1);
	window.setTimeout(svg_tick,50);
    } else {
	update_container();
	update_pos(Root);
	var frame = svgel.getElementById("frame");
	if( frame ) {
            frame.firstChild.nodeValue = "frame: " + framenum;
	}
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
    AddRandomCards();
    window.setTimeout(svg_tick,50);
}


///////////////////////////////////////////////


var  Nodes = new Array();
var nNodes = 0;


var rid = 0;


function FlattenTree(n)
{
    var i,cnt;

    // Add ourself to the flat list
    n.id = rid++;
    Nodes[n.id] = n;

    for(i=cnt=0; i<n.nc; i++) {
	cnt += FlattenTree(n.c[i]);
    }
    
    return cnt+1;
}


function CountLeaves(n)
{
    var i,cnt;

    if( n.nc ) {
	for(i=cnt=0; i<n.nc; i++) {
	    cnt += CountLeaves(n.c[i]);
	}
    } else {
	cnt = 1;
    }

    n.leaves = cnt;

    return cnt;
}


///////////////////////////////////////////////


// Initializes node loataions to something reasonable
function TreeInitLocRecur(l, s, w, n)
{
  var p = [ 0.0, 0.0 ];
  var i = 0;

  // Set our position and mass
  if( n.p ) {
    p = n.p.l;
  }
  n.l = new Array();
  n.l[0] = p[0] + (Math.floor(Math.random()*100)) - 50 + 150;
  n.l[1] = p[1] + (Math.floor(Math.random()*100)) - 50 + 150;
  n.m = 1.0;

  // Let our children set their position
  for(i=0; i < n.nc; i++) {
    TreeInitLocRecur(l+1, n.nc, i, n.c[i]);
  }
}


///////////////////////////////////////////////
// Newick Tree Format Parser
///////////////////////////////////////////////


// Test for digits
function isDigit(aChar)
{
    myCharCode = aChar.charCodeAt(0);
    
    if((myCharCode > 47) && (myCharCode <  58))
    {
        return true;
    }
    
    return false;
}


function AddRandomCards()
{
    var p   = new Object();
    var c   = new Object();  
    var brk = 0;
    var id  = 0;
    var i   = 0;
    
    // Create a root node and an initial child
    p.n           = "ROOT_NODE";
    p.id          = id++;
    p.d           = 0;
    p.v           = new Array();
    p.c           = new Array();
    p.nc          = 0;
    p.c[p.nc]     = new Object();
    p.c[p.nc].p   = p;
    p.c[p.nc].d   = p.d+1;
    p.c[p.nc].n   = "";
    p.c[p.nc].nc  = 0;
    p.c[p.nc].id  = id++;
    c = p.c[p.nc];
    p.nc++;
    if(c.d > MaxDepth) {
	MaxDepth = c.d;
    }
    Root = p;
    
    // Current node will be internal:  Give it a child and move deeper into the tree.
    p = c;
    if( p.c == null ) {
      p.c = new Array();
    }
    p.c[p.nc]    = new Object();
    p.c[p.nc].v  = [ 0.0, 0.0 ];
    p.c[p.nc].p  = p;
    p.c[p.nc].d  = p.d+1;
    p.c[p.nc].n  = "";
    p.c[p.nc].id = id++;
    p.c[p.nc].nc = 0;
    c = p.c[p.nc];
    p.nc++;
    // Check for maxdepth
    if(c.d > MaxDepth) {
      MaxDepth = c.d;
    }
    
    // Debug message
    nNodes = id;
    var bpr = nNodes;
    
    // Init the node positions
    TreeInitLocRecur(0, 0, 0, Root);
    
    // Flatten tree
    nNodes = FlattenTree(Root);
    var apr = nNodes;

    // Count leaves
    CountLeaves(Root);
    //    alert("Newick data contains " + bpr + " nodes. " +
    //	  "There are " + apr + " nodes and " + Root.leaves + " species.\n\n");
}


/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////


function fszchg()
{
    var sb = document.getElementById("fontsize");
    font_size = sb.value;
    var lbl = document.getElementById("txtsz");
    lbl.innerHTML = font_size;
}


function settype(mode) 
{
    clear_svg();
}


/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////


</script>


<script type="text/javascript" src="mwheel.js"></script>


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

    <desc>CardCloud SVG Display Area</desc>

    <rect id="background" x="0" y="0" width="854" height="480" fill="white" stroke="black" onmouseup="handleMouseUp(evt)" onmousedown="handleMouseDown(evt)" onmousemove="handleMouseMove(evt)" />

    <g id="tfbox" transform="" width="856" height="480"></g>

    <text id="frame" x="779" y="13" font-size="10" fill="black">frame: 0</text>

</svg>
<br><br>


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
