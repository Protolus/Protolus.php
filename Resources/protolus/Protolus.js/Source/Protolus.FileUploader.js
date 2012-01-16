/*
---
description: Protolus RAD framework port to JS

license: MIT-style

authors:
    - Abbey Hawk Sparrow

requires:
    - core/1.2.4: '*'
    - [Protolus, Protolus.Panel]

provides: [Protolus.FileUploader]
...
*/

XMLHttpRequest.prototype.sendAsBinary = function(datastr) {
    function byteValue(x) {
       return x.charCodeAt(0) & 0xff;
    }
    var ords = Array.prototype.map.call(datastr, byteValue);
    var ui8a = new Uint8Array(ords);
    this.send(ui8a.buffer);
}

Protolus.FileUploader = new Class({
    /*
        options:
            resources : // file(HTML5), browser(Element.Draggable)
            types : // jpg, gif, png
    */
    options : {},
    initialize : function(element, options){
        //this.options = options;
        for(index in options) this.options[index] = options[index];
        this.element = document.id(element);
        if(!this.options.endpoint) window.location; // if there's no endpoint, deliver to the script we're on
        //the element is the container, but we need a  
        this.element.addEventListener('dragover', this.cancelCallback, false);
        this.element.addEventListener('dragenter', this.cancelCallback, false);
        //this.element.addEventListener('drop', this.dropCallback, false);
        this.element.addEventListener('drop', function(){console.log('jjdnjdn');}, false);
        
        //this.element.addEvent('drop', this.dropCallback);
    },
    dropCallback : function(e){
        e.stopPropogation();
        e.preventDefault();
        var dt = e.dataTransfer;
        var files = dt.files;
        var numFiles = files.length;
        console.log('drop');
        for (var i = 0; i < files.length; i++) {
          var file = files[i];
            var imageType = /image.*/;

            if (!file.type.match(imageType)) {
              continue;
            }

            var img = document.createElement("img");
            img.classList.add("obj");
            img.file = file;
            preview.appendChild(img);

            var reader = new FileReader();
            reader.onload = (function(aImg) { return function(e) { aImg.src = e.target.result; }; })(img);
            reader.readAsDataURL(file);
          
        }
        /*
        //if(this.options.resources.contains())
        if (e.dataTransfer.types) {
            var img = e.dataTransfer.files;
            var reader = new FileReader(); 
            this.ctrl = createThrobber(img);
            var xhr = new XMLHttpRequest();
            this.xhr = xhr;
            if(this.options.setup) this.options.setup();
            var self = this;
            this.xhr.upload.addEventListener("progress", function(e) {
                if (e.lengthComputable) {
                  var percentage = Math.round((e.loaded * 100) / e.total);
                  if(this.options.update) this.options.update(percentage);
                }
              }.bind(this), false);
            
            xhr.upload.addEventListener("load", function(e){
                if(this.options.cleanup) this.options.cleanup();
            }.bind(this), false);
            xhr.open("POST", this.options.endpoint);
            xhr.overrideMimeType('text/plain; charset=x-user-defined-binary');
            reader.onload = function(evt) {
                xhr.sendAsBinary(evt.target.result);
            };
            reader.readAsBinaryString(file);
            //e.dataTransfer.types.each(function(type){
               // e.dataTransfer.getData(type); 
            //});
        } else {
            // e.dataTransfer.getData('Text');
        };
        */
    },
    cancelCallback : function(){
        if (e.preventDefault) e.preventDefault(); // required by FF + Safari
        e.dataTransfer.dropEffect = 'copy'; // tells the browser what drop effect is allowed here
        console.log('drag!');
        return false; // required by IE
    }
});