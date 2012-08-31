window.OpenBudget = {
  SVGSupport: (!!document.createElementNS && !!document.createElementNS('http://www.w3.org/2000/svg', "svg").createSVGRect)
};

$(function() {
  if(!OpenBudget.SVGSupport) {
        $('#sidebar').append('<div id="notsupported"><h2>Browser inkompatibel</h2><p>Leider unterstüzt ihr Browser kein SVG.<br /><br />Wir empfehlen die neuste Version von <span class="recommendation"></span>.</p></div>');
        // maybe OS dependant but Safari currently sucks
        $('#notsupported .recommendation').html('<a href="https://www.google.com/chrome" target="_blank">Google Chrome</a> oder <a href="http://www.mozilla.org/firefox/" target="_blank">Firefox</a>');
    }
});
