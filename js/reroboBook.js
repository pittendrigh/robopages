
 function fixItUp(buttonState) {
      var nav = document.getElementById("ttoc");
      var doHack = document.getElementById("tcdo");

      //console.log('fixItUp ' + buttonState)
      if (buttonState == 'toc') {
        nav.style.display = "block";
        doHack.innerHTML = "toc";
        document.cookie = "buttonState=toc";
        //console.log("display was none, buttonState now " + 'toc');
      } else {
        nav.style.display = "none";
        doHack.innerHTML = "TOC";
        document.cookie = "buttonState=TOC";
        //console.log("display was block, buttonState now " + 'TOC');
      }
    }

    function flipAndRedraw() {
      var nav = document.getElementById("ttoc");
      var doHack = document.getElementById("tcdo");

      if (nav.style.display == "none") {
        nav.style.display = "block";
        doHack.innerHTML = "toc";
        document.cookie = "buttonState=toc";
        console.log("display was none, buttonState now " + 'toc');
      } else {
        nav.style.display = "none";
        doHack.innerHTML = "TOC";
        document.cookie = "buttonState=TOC";
        console.log("display was block, buttonState now " + 'TOC');
      }
    }
