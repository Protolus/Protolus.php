/*
---
description: A properties parser and simple properties abstraction

license: MIT-style

authors:
- Abbey Hawk Sparrow

requires:
    core/1.2.4: '*'

provides: [Midas.Properties,Midas.PropertiesParser]
...
*/
if(!Midas) var Midas = {};
Midas.Properties = new Class({
    values : {},
    parser : null,
    initialize: function(filename, callback){
        if(filename){
            new Request({
                url: filename,
                onSuccess: function(data) {
                    this.load(data);
                    if(callback) callback();
                }.bind(this)
            }).send();
        }
        this.parser = new Midas.PropertiesParser();
    },
    set : function(label, value){
        this.values[label] = value;
    }, 
    get : function(label){
        return this.values[label];
    },
    load : function(data){
        this.values = this.parser.parse(data);
    },
    write : function(){
        result = '';
        for(label in this.values){
            result += label+' = '+this.values[label]+"\n";
        }
        return result;
    }
});
Midas.PropertiesParser = new Class({
    parse : function(fileBody){
        var lines = fileBody.split("\n");
        var pos, predicate, unicodeMatches;
        var properties = {};
        var multi = false;
        var last = null;
        lines.each(function(line){
            if(line.trim().substring(0,1) == '#' || line.trim().substring(0,1) == '!' ) return;
            if(multi && last != null){
                if(line.substring( line.length-1) != '\\'){
                    properties[last] = properties[last] + line.trim();
                    multi = false;
                }else{
                    properties[last] = properties[last] + line.trim().substring(0, line.length-1);
                }
                return;
            }
            pos = line.indexOf("=");
            if(pos == -1) pos = line.indexOf(":");
            if(pos != -1){
                last = line.substring(0, pos).trim().replace(/\\ /g, ' ');
                predicate = line.substring(pos+1).trim();
                unicodeMatches = predicate.match( /\\u[0-9]{4}/g );
                if(unicodeMatches) unicodeMatches.each(function(match){
                    var character = eval('"'+match+'"');
                    var rx = new RegExp(match.replace('\\', '\\\\'),'g');
                    predicate = predicate.replace(rx, character);
                });
                if(line.substring( line.length-1) == '\\'){
                    predicate = line.substring(pos+1).trim();
                    properties[last] = predicate.substring(0, predicate.length-1);
                    multi = true;
                }else{
                    properties[last] = predicate;
                }
            }
        });
        return properties;
    }
});