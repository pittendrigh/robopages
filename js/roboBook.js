
 function fixItUp(buttonState) {
      let nav = document.getElementById("tocComesAndGoes");
      let doHack = document.getElementById("tocPopper");
      let date = new Date();
          date.setDate(date.getDate()+30);
      let expires="; "+"expires="+date.toUTCString() + "; SameSite=Strict;"


      //console.log('fixItUp ' + buttonState)
      if (buttonState == 'toc') {
        nav.style.display = "block";
        doHack.innerHTML = "toc";
        document.cookie = "buttonState=toc"+expires;
        //console.log("buttonState=toc"+expires);
        //console.log("display was none, buttonState now " + 'toc');
      } else {
        nav.style.display = "none";
        doHack.innerHTML = "TOC";
        document.cookie = "buttonState=TOC"+expires;
        //console.log("buttonState=toc"+expires);
        //console.log("display was block, buttonState now " + 'TOC');
      }
    }

    function flipAndRedraw() {
      let nav = document.getElementById("tocComesAndGoes");
      let doHack = document.getElementById("tocPopper");
      let date = new Date();
          date.setDate(date.getDate()+30);
      let expires="; " +"expires=" + date.toUTCString();

      if (nav.style.display == "none") {
        nav.style.display = "block";
        doHack.innerHTML = "toc";
        document.cookie = "buttonState=toc"+expires;
        //console.log("buttonState=toc"+expires);
        //console.log("display was none, buttonState now " + 'toc');
      } else {
        nav.style.display = "none";
        doHack.innerHTML = "TOC";
        document.cookie = "buttonState=TOC"+expires;
        //console.log("buttonState=toc"+expires);
        //console.log("display was block, buttonState now " + 'TOC');
      }
    }
