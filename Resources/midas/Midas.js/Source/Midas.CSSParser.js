/*
---
description: A MooTools CSS loader

license: MIT-style

authors:
- Abbey Hawk Sparrow

requires:
    core/1.2.4: '*'

provides: [Midas.CSSParser]
...
*/
if(!Midas) var Midas = {};
Midas.CSSParser = new Class({
    parse: function(text){
        var inStyle = false;
        var inComment = false;
        var inPredicate = false;
        var styleName = '';
        var definition = {selector:'', styles: {}};
        var ch;
        var result = [];
        for(var lcv=0; lcv < text.length; lcv++){
            ch = text[lcv];
            if(inComment){
                if(ch == '*' && text[lcv+1] == '/'){
                    inComment = false;
                    lcv++;
                }
                continue;
            }
            if(ch == '/' && text[lcv+1] == '*'){
                inComment = true;
                lcv++;
                continue;
            }
            if(inStyle){
                if(inPredicate){
                    if(ch == ';' || ch == '}'){
                        definition.styles[styleName] = definition.styles[styleName].trim();
                        inPredicate = false;
                        styleName = '';
                        if(ch == '}') {
                            result.push(definition);
                            inStyle = false;
                            definition = {selector:'', styles: []};
                        }
                    }else{
                        if(!definition.styles[styleName]) definition.styles[styleName] = ch;
                        else definition.styles[styleName] += ch;
                    }
                }else{
                    if(ch == ':'){
                        inPredicate = true;
                        styleName = styleName.trim();
                    }else if(ch == '}') {
                        result.push(definition);
                        inStyle = false;
                        definition = {selector:'', styles: {}};
                    }else{
                        styleName += ch;
                    }
                    
                }
                continue;
            }
            if(ch == '{'){
                definition.selector = definition.selector.trim()
                inStyle = true;
                continue;
            }
            definition.selector += ch;
        }
        return result;
    },
    apply : function(data){
        if(typeof data == 'string') data = this.parse(data);
        //console.log(data);
        data.each(function(definition){
            var elements = $$(definition.selector);
            elements.each(function(element){
                for(style in definition.styles) element.setStyle(style, definition.styles[style]);
            }.bind(this));
        }.bind(this));
    }
});