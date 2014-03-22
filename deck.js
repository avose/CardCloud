
function handleFileSelect(evt) 
{
  var files = evt.target.files; // FileList object

  // files is a FileList of File objects. List some properties.
  var output = [];
  for (var i = 0, f; f = files[i]; i++) {
    if (!f.type.match('text/plain')) {
      continue;
    }
    output.push('<li>', escape(f.name), '</li>');
  }
  document.getElementById('decklist').innerHTML = '<ul>' + output.join('') + '</ul>';

  // Read the deck file
  for (var i = 0, f; f = files[i]; i++) {
    var reader = new FileReader();
  
    // Closure to capture the file information.
    reader.onload = (function(theFile) {
		       return function(e) {
			 var cards = e.target.result.split('\n');
			 var span = document.createElement('span');
			 span.innerHTML = cards.join('<br>');
			 document.getElementById('decklist').insertBefore(span, null);
			 AddCards(cards);
		       };
		     })(f);
    
    if (!f.type.match('text/plain')) {
      continue;
    }
    reader.readAsBinaryString(f);
  }
}


function select_deck()
{
  // Check for the various File API support.
  if (window.File && window.FileReader && window.FileList && window.Blob) {
    // Great success! All the File APIs are supported.
    document.getElementById('deckfiles').addEventListener('change', handleFileSelect, false);
  } else {
    alert('The File APIs are not fully supported in this browser.');
  }
}
