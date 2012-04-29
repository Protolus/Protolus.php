/*
---
name: baseBox
description: a MooTools 1.3 modal box class, powered by CSS and with scale transitions
version: 1.0.1
authors:
  - Dimitar Christoff

requires:
  - Core/Class.Extras
  - Core/Element.Event
  - Core/Element.Style
  - Core/Element.FX
  - Core/Element.Morph
  - More/Element.Delegation
  - More/Drag.move
  - baseBox

license: MIT-style license

provides: [baseBox.lightBox]
...
*/

baseBox.lightBox = new Class({

    Extends: baseBox,

    options: {
        outerClose: true,
        warpClass: "baseBoxLightBox",
        boxTitle: "baseBoxLightBoxTitle",
        boxBodyOuter: "LightBoxBodyOuter",
        boxBody: "LightBoxBody",
        modal: {
            enabled: true,
            background: "#000"
        }
    },

    initialize: function(img, options) {
        var self = this;
        this.parent(options);
        Asset.image(img, {
            onload: function() {
                if (!self.options.width) {
                    self.options.width = this.width + 20;
                }
                self.doBox("", ["<img class='shadowy bordered' src='", img, "' />"].join(""));
            }
        });
    }
});