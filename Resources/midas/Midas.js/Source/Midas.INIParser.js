/*
---
description: A MooTools INI loader

license: MIT-style

authors:
- Abbey Hawk Sparrow

requires:
    core/1.2.4: '*'

provides: [Midas.INIParser]
...
*/
if(!Midas) var Midas = {};
Midas.INIParser = new Class({
    parse: function(text){
        var inQuote = false;
        var inComment = false;
        var isAssigning = false;
        var inGrouping = false;
        var buffer = '';
        var label = '';
        var group = null;
        var currentQuoteType = '';
        var results = {};
        var ch;
        for(var lcv=0; lcv < text.length; lcv++){
            ch = text[lcv];
            if(buffer == '' && ch == '['){
                inGrouping = true;
            }
            if(inGrouping){
                if(ch == ']'){
                    group = buffer;
                    buffer = '';
                    inGrouping = false;
                }else if(ch != '[') buffer += ch;
                continue;
            }
            if(inComment){
                if(ch == "\n"){
                    inComment = false;
                    if(isAssigning){
                        if(group == null){
                            if(label != '') results[label] = buffer;
                        }else{
                            if(!results[group]) results[group] = {};
                            if(label != '') results[group][label] = buffer;
                        }
                        label = buffer = '';
                    }
                }else continue;
            }
            if(!inQuote && !inComment){ //we're reading chars
                if(ch == ';'){
                    inComment = true;
                    continue;
                }
                if(ch == '\'' || ch == '"'){
                    inQuote = true;
                    currentQuoteType = ch;
                    continue;
                }
                if(!isAssigning && ch == '='){
                    label = buffer;
                    buffer = '';
                    isAssigning = true;
                }else{
                    if(ch == "\n"){
                        isAssigning = false;
                        if(group == null){
                            if(label != '') results[label] = buffer;
                        }else{
                            if(!results[group]) results[group] = {};
                            if(label != '') results[group][label] = buffer;
                        }
                        label = buffer = '';
                    }else{
                        if(ch != ' ') buffer += ch;
                    }
                }
            }else{
                if(inQuote){ // keep reading until we see our quote end
                    if(ch == currentQuoteType){
                        inQuote = false;
                    }else{
                        buffer += ch;
                    }
                }
            }
        }
        if(group == null){
            if(label != '') results[label] = buffer;
        }else{
            if(!results[group]) results[group] = {};
            if(label != '') results[group][label] = buffer;
        }
        return results;
    }
});