

function handleFileSelect(evt) 
{
  var files = evt.target.files;

  // Read the deck file(s)
  for (var i = 0, f; f = files[i]; i++) {
    var reader = new FileReader();
    reader.onload = (function(theFile) {
		       return function(e) {
			 var cards = e.target.result.split('\n');
			 // Add list of cards to page
			 var span = document.createElement('span');
			 span.innerHTML = "<hr><tiny>" + cards.join('<br>') + "</tiny>";
			 document.getElementById('decklist').insertBefore(span, null);
			 // Add the cards to the game
			 AddCards(cards);
		       };
		     })(f);
    // Skip any non-text files
    if (!f.type.match('text/plain')) {
      continue;
    }
    reader.readAsBinaryString(f);
  }
}


function select_deck()
{
  if (window.File && window.FileReader && window.FileList && window.Blob) {
    document.getElementById('deckfiles').addEventListener('change', handleFileSelect, false);
  } else {
    alert('File APIs are not fully supported in this browser.');
  }
}
