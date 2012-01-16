/*
---
description: A MooTools SASS parser, applier and converter

license: MIT-style

authors:
- Abbey Hawk Sparrow

requires:
    core/1.2.4: '*'

provides: [Midas.SCSSParser]
...
*/
if(!Midas) var Midas = {};
Midas.SASSFunctions = {
    hsl : function(hue, saturation, lightness){
    
    },
    darken : function(color, percent){
        if(percent.endsWith('%')) percent = percent.substring(0, percent.length-1);
        if(color.startsWith('#')) color = color.substring(1);
        //console.log([color, percent]);
        var colors = [parseInt("0x"+color.substring(0,2)), parseInt("0x"+color.substring(2,4)), parseInt("0x"+color.substring(4,6))];
        colors[0] = Math.floor(colors[0] * ((100-percent)/100));
        colors[1] = Math.floor(colors[1] * ((100-percent)/100));
        colors[2] = Math.floor(colors[2] * ((100-percent)/100));
        //console.log(colors);
        return '#'+colors[0].toString(16).toUpperCase()+colors[1].toString(16).toUpperCase()+colors[2].toString(16).toUpperCase();
    },
    greyscale : function(color){
        if(percent.endsWith('%')) percent = percent.substring(0, percent.length-1);
        if(color.startsWith('#')) color = color.substring(1);
        //console.log([color, percent]);
        var colors = [parseInt("0x"+color.substring(0,2)), parseInt("0x"+color.substring(2,4)), parseInt("0x"+color.substring(4,6))];
        var val = (colors[0]+colors[1]+colors[3] == 0)?0:(colors[0]+colors[1]+colors[3])/3
        colors[0] = val;
        colors[1] = val;
        colors[2] = val;
        //console.log(colors);
        return '#'+colors[0].toString(16).toUpperCase()+colors[1].toString(16).toUpperCase()+colors[2].toString(16).toUpperCase();
    }
};
Midas.SCSS = function(){
    window.addEvent('domready', function() {
        var scssStyles = $$('head style[@type="text/scss"]');
        scssStyles.each(function(style){
            var sassParser = new Midas.SCSSParser();
            var css = sassParser.convertScssToCss(style.innerText);
            var convertedTag = new Element('style', {type: 'text/css'});
            convertedTag.innerHTML = css;
            convertedTag.inject(style, 'before');
        });
    });
}
//Midas.SCSS();
Midas.SCSSParser = new Class({
    parse: function(text){
        var inComment = false;
        var inPredicate = false;
        var identifier = '';
        var predicate = '';
        var ch;
        var definitions = [];
        var root = [];
        definitions.push(root);
        var node;
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
            if(inPredicate){
                if(ch == '{'){ //compound property
                    var newChild = [];
                    definitions.getLast().push({
                        property: identifier.trim(),
                        properties : newChild
                    });
                    definitions.push(newChild);
                    identifier = predicate = '';
                    inPredicate = false;
                    continue;
                }
                if(ch == ';' || ch == '}'){
                    definitions.getLast().push({
                        property: identifier,
                        value : predicate.trim()
                    });
                    inPredicate = false;
                    identifier = predicate = '';
                    if(ch == '}') definitions.pop();
                }else{
                    predicate += ch;
                }
                continue;
            }else{
                switch(ch){
                    case ':':
                        inPredicate = true;
                        identifier = identifier.trim();
                        break;
                    case '}':
                        definitions.pop();
                        identifier = predicate = '';
                        break;
                    case ';':
                        definitions.getLast().push({
                            directive: identifier.trim()
                        });
                        inPredicate = false;
                        identifier = predicate = '';
                        break;
                    case '{':
                        identifier = identifier.trim();
                        var newChild = [];
                            definitions.getLast().push({
                                selector: identifier,
                                styles : newChild
                            });
                        definitions.push(newChild);
                        identifier = predicate = '';
                        break;
                    default:
                        identifier += ch;
                }
                continue;
            }
        }
        return root;
    },
    convertScssPropsToCss : function(prefix, properties){
        var results = [];
        properties.each(function(property){
            if(property.value){
                results.push(prefix+'-'+property.property+':'+this.interpretValue(property.value));
            }else{
                this.convertScssPropsToCss(prefix+'-'+property.property, property.properties).append(results);
            }
        }.bind(this));
        return results;
    },
    interpretValue: function(value){
        var orig =  value;
        //replace any variable references we find in both the environment and a property (some vars are from mixins)
        var variableOccurances = value.match(/\$[A-Za-z][A-Za-z0-9_-]*/g);
        if(variableOccurances !== null) variableOccurances.each(function(match){
            if(match.startsWith('$')) match = match.substring(1);
            if(this.environment[match]){
                value = value.replace(new RegExp('\\$'+match), this.environment[match]);
            }
        }.bind(this));
        //now let's do any math we find
        var unitsExpression = /(px|em)/gi
        var level2_ops = value.match(/([0-9]+(?:px|em)? +[*\/] +[0-9]+(?:px|em)?)/gi);
        if(level2_ops !== null) level2_ops.each(function(op){
            var units = op.match(unitsExpression);
            var newVal = eval(op.replace(unitsExpression, ''))+'';
            newVal += (units.length > 0)?units[0]:'';
            value = value.replace(op, newVal);
        });
        var level1_ops = value.match(/([0-9]+(?:px|em)? +[-\+] +[0-9]+(?:px|em)?)/gi);
        if(level1_ops !== null) level1_ops.each(function(op){
            var units = op.match(unitsExpression);
            var newVal = eval(op.replace(unitsExpression, ''))+'';
            newVal += (units.length > 0)?units[0]:'';
            value = value.replace(op, newVal);
        });
        //now let's support functions
        var vals = value.match(/^([A-Za-z][A-Za-z0-9_-]*)\((.*)\)/);
        if(vals !== null && vals.length == 3 && Midas.SASSFunctions[vals[1]]){
            var args = vals[2].split(',');
            args.each(function(value, index){
                args[index] = value.trim();
            });
            var ret = Midas.SASSFunctions[vals[1]].apply( this, args );
            value = value.replace(vals[0], ret);
        }
        //if(orig != value) console.log([orig, ' ===> ', value]);
        return value;
    },
    environment : {_mixins:[]},
    extensions : {},
    mixin: function(name){
        result = false;
        this.environment['_mixins'].each(function(mixin){
            if(mixin.name == name) result = mixin;
        }.bind(this));
        return result;
    },
    extend: function(name, styles, base){
        if(!base){
            if(!this.extensions[name]) return;
            base = this.extensions[name];
        }
        var extender;
        var extendable = [];
        styles.each(function(style){
            if(style.style == name){
                extender = style;
            }
            if(style.style.startsWith(base)){
                extendable.push(style);
            }
        }.bind(this));
        styles.erase(extender);
        extendable.each(function(style){
            styles.push({style:style.style.replace(base, name), properties:style.properties.clone().append(extender.properties)})
        });
    },
    convertScssNodesToCss : function(nodes, parentSelector, results, environment){
        var selector = '';
        if(!parentSelector) parentSelector = '';
        else selector = parentSelector;
        if(!results) results = [];
        nodes.each(function(node){
            if(node.property){
                if(node.properties){
                    var props = this.convertScssPropsToCss(node.property, node.properties);
                    if(results.getLast() != null && results.getLast().style == selector){
                        props.append(results.getLast().properties);
                    }else{
                        results.push({style:selector, properties: props});
                    }
                }else{
                    if(selector.trim() == ''){
                        if(node.property.startsWith('$')) node.property = node.property.substring(1);
                        this.environment[node.property] = this.interpretValue(node.value);
                        return;
                    }
                    if(results.getLast() != null && results.getLast().style == selector){
                        results.getLast().properties.push(node.property+':'+this.interpretValue(node.value));
                    }else{
                        results.push({style:selector, properties: [node.property+':'+this.interpretValue(node.value)]});
                    }
                }
            }else if(node.directive){
                if(node.directive.startsWith('@include ')){
                    var pieces = node.directive.match(/@include ([A-Za-z][A-Za-z0-9_-]*)(\(.*\))?/);
                    var args = (pieces[2])? pieces[2].substring(1, pieces[2].length-1).split(',') : [];
                    args.each(function(value, index){
                        args[index] = value.trim();
                    });
                    var mixin;
                    if(mixin = this.mixin(pieces[1])){
                        args.each(function(value, index){
                            this.environment[mixin['_args'][index]] = value;
                        }.bind(this));
                        this.convertScssNodesToCss(mixin.styles, (selector).trim(), results);
                    }
                }
                if(node.directive.startsWith('@extend ')){
                    var baseStyle = node.directive.substring(8).trim();
                    this.extensions[selector] = baseStyle;
                }
            }else{
                if(node.selector.startsWith('@mixin ')){
                    var pieces = node.selector.match(/@mixin ([A-Za-z][A-Za-z0-9_-]*)(\(.*\))?/);
                    var name = pieces[1];
                    var args = pieces[2]?pieces[2].substring(1, pieces[2].length-1).split(','):[];
                    args.each(function(value, index){
                        if(value.startsWith('$')) args[index] = value.substring(1).trim();
                        else args[index] = value.trim();
                    });
                    node['_args'] = args;
                    node.name = name;
                    this.environment['_mixins'].push(node);
                    return;
                }
                if(node.selector.indexOf(',') == -1){
                    this.convertScssNodesToCss(node.styles, (selector+' '+node.selector).trim(), results);
                    this.extend((selector+' '+node.selector).trim(), results);
                }else{
                    node.selector.split(',').each(function(select){
                        this.convertScssNodesToCss(node.styles, (selector+' '+select.trim()).trim(), results);
                        this.extend((selector+' '+select).trim(), results);
                    }.bind(this));
                }
            }
        }.bind(this));
        return results;
    },
    convertScssToCss : function(data){
        if(typeof data == 'string') data = this.parse(data);
        var styles = this.convertScssNodesToCss(data);
        results = '';
        styles.each(function(style){
            results += style.style+'{'+"\n";
            style.properties.each(function(property){
                results += '   '+property+";\n";
            });
            results += '}'+"\n";
        }.bind(this));
        return results;
    }
});