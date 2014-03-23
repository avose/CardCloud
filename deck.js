

function addItem(Text,Value)
{
  var opt = document.createElement("option");

  opt.text = Text;
  opt.value = Value;
  
  document.getElementById("decklist").options.add(opt);  
}


function handleFileSelect(evt) 
{
  var files = evt.target.files;

  // Empty the deck card list box
  var sbox = document.getElementById("decklist");
  sbox.options.length = 0;

  // Read the deck file(s)
  for (var i = 0, f; f = files[i]; i++) {
    var reader = new FileReader();
    reader.onload = (function(theFile) {
		       return function(e) {
			 var data  = String(e.target.result).trim();
			 var cards = data.split('\n');
			 for(var j=0; j<cards.length; j++) {
			   var cwords = cards[j].split(' ');
			   addItem(cwords[1].replace('_',' '),cwords[1].replace('_',' '));
			 }
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
