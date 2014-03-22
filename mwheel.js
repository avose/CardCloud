/*
  Code by:

  adomas.paltanavicius@gmail.com
  http://www.adomas.org/javascript-mouse-wheel/plain.html
*/

function handle(delta) {
    handle_delta(delta);
}

function wheel(event){
    var delta = 0;
    if (!event) event = window.event;
    if (event.wheelDelta) {
	delta = event.wheelDelta/120; 
	if (window.opera) delta = -delta;
    } else if (event.detail) {
	delta = -event.detail/3;
    }
    if (delta)
	handle(delta);
    if (event.preventDefault)
        event.preventDefault();
    event.returnValue = false;
}

/* Initialization code. */
if (window.addEventListener)
    window.addEventListener('DOMMouseScroll', wheel, false);
window.onmousewheel = document.onmousewheel = wheel;
