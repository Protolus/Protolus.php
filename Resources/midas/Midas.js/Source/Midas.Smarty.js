/*
---
description: An extensible Smarty Parser in Mootools

license: MIT-style

authors:
- Abbey Hawk Sparrow

requires:
    core/1.2.4: '*'

provides: [Midas.Smarty]
...
*/
if(!Midas) var Midas = {};
Midas.Smarty = new Class({
    //I am just going to do this my own way, my attempt at doing Smarty the way the smarty team does it left me wanting for death
    macroRegistry: {},
    template_directory : '',
    environment : {},
    internal : {},
    properties : {},
    blocks : [],
    config : {},
    debug : false,
    caching : true,
    runtimeCache : {},
    database : null,
    cacheID : null,
    initialize : function(options){
        if(!options) options = {};
        if(options.template_directory) this.template_directory = options.template_directory;
        //this.macroRegistry['panel'] = Midas.SmartyLib.renderPanel;
        this.macroRegistry['rdelim'] = function(panelName, attributes, smartyInstance, block){ return '}' };
        this.macroRegistry['ldelim'] = function(panelName, attributes, smartyInstance, block){ return '{' };
        this.macroRegistry['foreach'] = function(panelName, attributes, smartyInstance, block){
            return Midas.SmartyLib.forMacro(block.tag, smartyInstance, block)
        };
        this.macroRegistry['assign'] = function(panelName, attributes, smartyInstance, block){
            var value = Midas.SmartyLib.evaluateSmartyPHPHybridExpression(attributes['value'], smartyInstance);
            smartyInstance.set(attributes['var'], value );
        };
        this.macroRegistry['if'] = function(panelName, attributes, smartyInstance, block){
            return Midas.SmartyLib.ifMacro(block.tag, block.attrs, smartyInstance, block)
        };
        this.macroRegistry['/if'] = function(panelName, attributes, smartyInstance, block){ return ''; };
        this.macroRegistry['/foreach'] = function(panelName, attributes, smartyInstance, block){ return ''; };
        this.macroRegistry[''] = function(panelName, attributes, smartyInstance, block){ return ''; };
        this.macroRegistry['value'] = function(panelName, attributes, smartyInstance, block){
            var value = Midas.SmartyLib.evaluateSmartyPHPHybridExpression(block.content, smartyInstance);
            return value;
        };
        this.cacheID = Midas.SmartyLib.generateUUID();
        //if (this.caching) 
        //this.database = openDatabase('SmartyDB', "1.0", "Smarty Client Database", 200000);
        //this.database = new Database('SmartyDB'); 
    },
    seekVariable: function(accessor, rootNode, value){
        var name = accessor.trim();
        if(name.substr(0,9) == '$smarty.' ) name = name.substr(8);
        if(name.substr(0,8) == 'smarty.' ) name = name.substr(7);
        if(name.substr(0,1) == '$' ) name = name.substr(1);
        var keys = name.split('.');
        keys = keys.reverse();
        var nextIndex = keys.pop();
        if(rootNode[nextIndex] == undefined && keys.length > 0) rootNode[nextIndex] = {}; 
        var current = rootNode[nextIndex];
        var expression = 'rootNode[\''+nextIndex+'\']';
        var accessor = 'rootNode.'+nextIndex;
        while(keys.length > 0){
            nextIndex = keys.pop();
            expression += '[\''+nextIndex+'\']';
            if(isNaN(parseFloat(nextIndex))) accessor += '.'+nextIndex;
            else accessor += '['+nextIndex+']';
            var res = false;
            try{
                eval('if(!'+accessor+'){ res = true;}');
            }catch(error){ 
                //console.log(['error', error, accessor]); 
            }if(res && keys.length > 0){
                eval(accessor+' = {};') //if we aren't initialized, let's do that
            }
        }
        if((value ||value === 0) && value != undefined){
            if(this.debug) Midas.SmartyLib.output(expression+' <= '+value);
            eval(expression+' = value;');
            //if(value === null) eval('delete '+expression+';');
        }else{
            var value = eval(expression);
            if(this.debug) Midas.SmartyLib.output(expression+' => '+value);
            return value;
        }

    },
    //the disconnect in naming between the following two functions is insanity, sorry to pass on bad convention
    getTemplateVars : function(name){
        return this.get_template_vars(name);
    },
    get_template_vars : function(name){
        return this.get(name);
    },
    assign : function(name, value){
        return this.set(name, value);
    },
    set : function(name, value){
        //if(value == undefined) return false; //this should throw an error
        //if(name == null) return false;
        //console.log(['set', name, value]);
        var val = this.seekVariable(name, this.environment, value);
    },
    get : function(name){
            try{
                var val = this.seekVariable(name, this.environment);
                if(val != undefined) return val;
                else return '';
            }catch(exception){
                return '';
            }
    },
    getConfig : function(name){
        return this.seekVariable(name, this.config);
    },
    setConfig : function(name, value){
        return this.seekVariable(name, this.config, value);
    },
    getProperty : function(name){
        
    },
    output : function(value){
        return Midas.SmartyLib.output(value);
    },
    macro : function(name, attrs, block){
        //console.log(['MACRO', name]);
        //this.output(profile(attrs));
        if(!attrs.get) attrs.get = function(tagName){
            var value = null;
            this.each(function(item, index){
                if(item.name == tagName) value = item.value;
            }.bind(this));
            return value;
        }
        //console.log(this.macroRegistry[name]);
        if(this.macroRegistry[name]) return this.macroRegistry[name](attrs.get('name'), attrs, this, block);
        else return '<!-- '+name+' [Macro not found!] -->';
    },
    getTemplate : function(name, callback, immediate){
        if(!immediate) immediate = false;
        if(Midas.Smarty.runtimeCache[name]){
            callback(Midas.Smarty.runtimeCache[name]);
            return Midas.Smarty.runtimeCache[name];
        }
        var value = '';
        if(false){
            this.database.execute('SELECT * FROM templates where name = ?', { 
                values: [name], 
                onComplete: function(resultSet){ 
                    this.output('return');
                    while(row = resultSet.next()){ 
                        this.output('result');
                    } 
                }.bind(this), 
                onError: function(err){
                    this.output(err.message);
                    this.database.execute('CREATE TABLE IF NOT EXISTS templates (name TEXT NOT NULL PRIMARY KEY, template TEXT NOT NULL)', { 
                        values: [name], 
                        onComplete: function(resultSet){ 
                            this.output('created');
                        }
                    }); 
                }.bind(this)
            }); 
        }
        if(value == ''){
            var templateRequest = new Request({
                url: name+'?'+this.cacheID, 
                method: 'get', 
                //async: immediate,
                onSuccess: function(responseText, responseXML) {
                    Midas.Smarty.runtimeCache[name] = responseText;
                    if(callback) callback(this.response.text);
                }
            }).send();
        }
        this.value = value;
        if(false){
            this.database.execute('INSERT INTO templates (name, template) values(?, ?)', { 
                values: [name, this.value], 
                onComplete: function(resultSet){ 
                    this.output('insert');
                }
            }); 
        }
        return this.value;
    },
    parseMacro : function(macroText){
        var intext = false
        var textMarker = '';
        var result = {
            name : '',
            attrs :[]
        }
        var text = '';
        var name = '';
        invalue = false;
        first_tag = true;
        if(macroText.trim().startsWith('$')){
            result = {
                name : 'value',
                attrs :[]
            };
        }else{
            for(var lcv = 0; lcv < macroText.length; lcv++){
                ch = macroText.charAt(lcv);
                if(!first_tag){
                    if(intext){
                        if(ch === textMarker){
                            result.attrs.push({
                                name: name.trim(),
                                value: text
                            });
                            text = '';
                            intext = false;
                        }else{
                            text += ch;
                        }
                    }else{
                        switch(ch){
                            case '\'':
                            case '"':
                                if(!intext){
                                    intext = true;
                                    textMarker = ch;
                                }
                                break;
                            case ' ':
                                if(text != ''){
                                    result.attrs.push({
                                        name: name.trim(),
                                        value: text
                                    });
                                    text = '';
                                }
                                break;
                            case '=':
                                name = text;
                                text = '';
                                break;
                            default:
                                text += ch;
                        }
                    }
                }else{
                    if((ch === ' ' || macroText.length-1 == lcv) && text.trim() != ''){
                        first_tag = false;
                        if(ch !== ' ') text += ch;
                        result.name = text;
                        text = '';
                    }else{
                        if(ch.trim() != '') text += ch;
                    }
                }
            }
            result.attrs[name] = text;
        }
        if(text != ''){
            result.attrs.push({
                name: name,
                value: text
            });
            text = '';
        }
        return result;
    },
    nestBlocks : function(results, stack){
        //seperate blocks (single level > multi level)
        if(!results) return;
        if(stack == undefined) stack = [];
        var tag, last, blocks;
        results.copy().each(function(item, index){
            last = stack.getLast();
            tag = (last)?last.tag.name:undefined;
            blocks = (last)?last.blocks:results;
            if(!item.blocks) item.blocks = [];
            if(item.tag && this.blockTags.contains(item.tag.name)){
                stack.push(item);
            }
            if(tag && item.tag && '/'+tag == item.tag.name){
                this.nestBlocks(blocks.blocks, stack);
                stack.pop();
                blocks = (stack.getLast())?stack.getLast().blocks:results;
            }
            if(!blocks.contains(item)){
                blocks.push(item)
                results.erase(item);
            }
        }.bind(this));
    },
    fetch : function(templateName, callback, immediate){
        if(!immediate) immediate = false;
        var buffer = '';
        this.getTemplate(this.template_directory + templateName, function(template){
            var results = this.scan(template);
            //parse tags (single level)
            results.each(function(item, index){
                switch(item.blockType){
                    case 'tag':
                        item.tag = this.parseMacro(item.content);
                        break;
                }
            }.bind(this));
            this.nestBlocks(results);
            this.renderOutput(results);
            if(this.debug) this.output('['+templateName+']');
            buffer = Midas.SmartyLib.executeBlocks(results, this);
            if(callback) callback(buffer+'');
        }.bind(this), immediate);
        return buffer+'';
    },
    blockStack : [],
    blockTags : ['if', 'foreach'],
    parseTags : function(block_list){
        block_list.each(function(item, index){
            switch(item.blockType){
                case 'tag':
                    item.tag = this.parseMacro(item.content);
                    break;
            }
        }.bind(this));
    },
    renderOutput : function(block_list){ //any atomic node outputs it's value, node level parse
        //parse all the macros, do anything else we can
        block_list.each(function(item, index){
            switch(item.blockType){
                case 'tag':
                    //console.log('attempting render of tag '+item.tag.name);
                    if(item.tag.name == 'value'){
                        item.output = Midas.SmartyLib.evaluateSmartyPHPHybridExpression(item.content, this);
                        //console.log(['value:', item.content]);
                    }
                    if(item.blocks && item.blocks.length > 0){
                        this.renderOutput(item.blocks);
                    }
                    break;
                case 'text':
                    item.output = item.content;
                    break;
                case 'comment':
                    if(this.debug) item.output = '<!--[MSC] '+item.content+'-->';
                    break;
                default:
                    item.output = item.content;
                    break;
            }
        }.bind(this));
    },
    scan : function(template){
        var parts = [];
        var ch;
        var intag = false;
        var incomment = false;
        var instring = false;
        var stringtype = "'";
        var inliteral = false;
        //our current buffer is: parts[parts.length-1].content
        parts[0] = {
            blockType : 'text',
            content : ''
        };
        for(var lcv = 0; lcv < template.length; lcv++){
            ch = template.charAt(lcv);
            if(instring){
                    if(ch == stringtype && template.chatAt(lcv-1) != '\\'){ //are we closing the string?
                        instring = false;
                    }
                    parts[parts.length-1].content += ch;
            }else if(inliteral){
                if(ch == '{' && template.substring(lcv, lcv+10).toLowerCase() == '{/literal}'){ //we found a literal tag end
                    lcv += 9; //we need to eat the literal tag
                    parts[parts.length] = {
                        blockType : 'text',
                        content : ''
                    };
                    inliteral = false;
                }else{ //just another char on this tag
                    parts[parts.length-1].content += ch;
                }
            }else if(intag){
                if(incomment){
                    if(ch == '*'){ //we found a comment tag end
                        var count = 0
                        while(template.charAt(lcv+count+1) == ' ') count++;
                        if(template.charAt(lcv+count+1) == '}'){ //is the first non-whitespace char after the asterisk?
                            parts[parts.length] = {
                                blockType : 'text',
                                content : ''
                            };
                            lcv += count+1
                            intag = false;
                            incomment = false;
                        }else{
                            parts[parts.length-1].content += ch;
                        }
                    }else{ //just another char on this tag
                        parts[parts.length-1].content += ch;
                    }
                }else{
                    if(ch == '}'){ //we found a tag end
                        parts[parts.length] = {
                            blockType : 'text',
                            content : ''
                        };
                        intag = false;
                    }else{ //just another char on this tag
                        parts[parts.length-1].content += ch;
                    }
                }
            }else{ // we're not in a tag or a string!
                if(ch == '{'){ //we found a tag start
                    if(template.substring(lcv, lcv+9).toLowerCase() == '{literal}'){
                        lcv += 8; //we need to eat the literal tag
                        parts[parts.length] = {
                            blockType : 'literal',
                            content : ''
                        };
                        inliteral = true;
                    }else{
                        if(template.charAt(lcv+1) == '*'){ //starting a new smarty comment
                            parts[parts.length] = {
                                blockType : 'comment',
                                content : ''
                            };
                            lcv++;
                            intag = true;
                            incomment=true;
                        }else{ //starting a new smarty tag
                            parts[parts.length] = {
                                blockType : 'tag',
                                content : ''
                            };
                            intag = true;
                        }
                    }
                }else{ //we think this must just be a normal char
                    parts[parts.length-1].content += ch;
                }
            }
        }
        return parts;
    }
});

Midas.SmartyLib = {
    conditions : [],
    pad : function(number, length) {
        var str = '' + number;
        while (str.length < length) {
            str = '0' + str;
        }
        return str;
    },
    evaluateSmartyPHPHybridBooleanExpression : function(expression, smartyInstance){
        //var pattern = /[Ii][Ff] +(\$[A-Za-z][A-Za-z0-9.]*) *$/s;
        expression = expression.trim();
        if(expression.toLowerCase().substring(0, 2) == 'if'){
            //todo: multilevel
            expression = expression.substring(2).trim();
            var expressions = expression.split('&&');
            var value = true;
            expressions.each(function(exp){
                value = value && Midas.SmartyLib.evaluateSmartyPHPHybridBooleanExpression(exp, smartyInstance);
            });
            return value;
        }else{
            pattern = new RegExp('(.*)( eq| ne| gt| lt| ge| le|==|>=|<=|<|>)(.*)', 'm');
            parts = expression.match(pattern);
            if(parts && parts.length > 3){
                var varOne = Midas.SmartyLib.evaluateSmartyPHPHybridVariable(parts[1].trim(), smartyInstance);
                var varTwo = Midas.SmartyLib.evaluateSmartyPHPHybridVariable(parts[3].trim(), smartyInstance);
                var res;
                switch(parts[2]){
                    case '==':
                    case 'eq':
                        res = (varOne == varTwo);
                        break;
                    case '!=':
                    case 'ne':
                        res = (varOne != varTwo);
                        break;
                    case '>':
                    case 'gt':
                        res = (varOne > varTwo);
                        break;
                    case '<':
                    case 'lt':
                        res = (varOne < varTwo);
                        break;
                    case '<=':
                    case 'le':
                        res = (varOne <= varTwo);
                        break;
                    case '>=':
                    case 'ge':
                        res = (varOne >= varTwo);
                        break;
                }
                return res;
            }else{
                var res;
                if( (expression - 0) == expression && expression.length > 0){ //isNumeric?
                    res = eval(expression);
                    res = res == 0;
                }else if(expression == 'true' || expression == 'false'){ //boolean
                    res = eval(expression);
                }else{
                    res = Midas.SmartyLib.evaluateSmartyPHPHybridVariable(expression, smartyInstance);
                    res = (res != null && res != undefined && res != '' && res != false);
                }
                return res;
            }
        }
    },
    evaluateSmartyPHPHybridExpression : function(variableName, smartyInstance){ // this decodes a value that may be modified by functions using the '|' separator
        if(variableName === undefined) return null;
        var methods = variableName.splitHonoringQuotes('|', ['#']);
        methods.reverse();
        //console.log(['expression-methods:', methods]);
        var accessor = methods.pop();
        var value = Midas.SmartyLib.evaluateSmartyPHPHybridVariable(accessor, smartyInstance);
        //now that we have the value, we must run it through the function stack we found
        var method;
        var params;
        var old = value;
        methods.each(function(item, index){
            params = item.split(':');
            params.reverse();
            //console.log(['expression-item:', item]);
            method = params.pop(); //1st element is
            if(method == 'default'){
                if(!value || value == '') value = Midas.SmartyLib.evaluateSmartyPHPHybridVariable(params[0], smartyInstance);
            }else{
                value = Midas.SmartyLib.executeMethod(method, value, params);
            }
        });
        return value;
    },
    evaluateSmartyPHPHybridVariable : function(accessor, smartyInstance, isConf){
        if(isConf == 'undefined' || isConf == null) isConf = false;
        if(!accessor) return '';
        if(accessor.toLowerCase().startsWith('\'') && accessor.toLowerCase().endsWith('\'')) return accessor.substr(1, accessor.length-2);
        if(accessor.toLowerCase().startsWith('"') && accessor.toLowerCase().endsWith('"')) return accessor.substr(1, accessor.length-2);
        if(accessor.toLowerCase().startsWith('$smarty.')) return smartyInstance.get(accessor.substr(8));
        if(accessor.startsWith('$')){
            var acc = accessor.substring(1);
            return smartyInstance.get(acc);
        }
        if(accessor.startsWith('#') && accessor.endsWith('#')){
            var cnf = accessor.substr(1, accessor.length-2);
            return Midas.SmartyLib.evaluateSmartyPHPHybridVariable( cnf , smartyInstance, true);
        }
        return smartyInstance.get(accessor);
        var parts = accessor.split('.');
        parts.reverse();
        var currentPart = parts.pop();
        var currentValue;
        if(isConf){
            return smartyInstance.getConf(accessor);
            currentValue = smartyInstance.config[currentPart];
        }else switch(currentPart){
            case 'smarty':
                currentValue = smartyInstance.internal;
                break;
            default:
                currentValue = smartyInstance.get(currentPart);
                if(currentValue == 'undefined' ) currentValue = '';
        }
        parts.each(function(item, index){
            if(!currentValue && currentValue !== 0) return;
            if(currentValue[item] == 'undefined'){
                currentValue = null;
            }else{
                currentValue = currentValue[item];
            }
        });
        return currentValue;
    },
    executeMethod : function(name, value, params){
        if(window[name] && typeOf(window[name]) == 'function') return window[name].apply(this, params);
        switch(name){
            case 'default':
                if(value == null || value == ''){
                    var p = params.pop();
                    return p;
                }else{
                    return value;
                }
                break;
            case 'truncate':
                var width = params[0]?Number.from(params[0]):80;
                var trailer = params[1]?params[1]:'...';
                //var fullWords = params[3] && (params[3].toLowerCase() == 't' || params[3].toLowerCase() == 'true')?true:false;
                //var truncateInMiddle = = params[4] && (params[4].toLowerCase() == 't' || params[4].toLowerCase() == 'true')?true:false;
                return width < value.length ? value.substr(0, width)+trailer:value;
            default:
                //alert('|'+name+'||'+params.pop()+'|');
        }
    },
    conditionalBranch : function(conditional, truebranch, falsebranch){
        if(!conditions[conditional]){
            conditions[conditional] = convertConditionToJS(conditional);
        }
        eval('var result = '+conditions[conditional]+';');
        //todo: branch caching as a function
        if(result){
            eval(truebranch);
        }else{
            eval(falsebranch);
        }
    },
    convertConditionToJS: function(conditional){
        
    },
    ifMacro: function(panelName, attributes, smartyInstance, block){
        var result = Midas.SmartyLib.evaluateSmartyPHPHybridBooleanExpression(block.content, smartyInstance);
        //console.log(['if', block.content, result])
        //prescan to make sure the else branch is there
        var hitElse = false;
        var item;
        if(block.ifBlocks) block.ifBlocks.empty();
        if(block.elseBlocks) block.elseBlocks.empty();
        for(index in block.blocks){
            item = block.blocks[index];
            if(item.blockType == 'tag' && item.content.trim() == 'else'){
                hitElse = true;
            }else if(hitElse){
                if(!block.elseBlocks) block.elseBlocks = [];
                block.elseBlocks.push(item);
            }else{
                if(!block.ifBlocks) block.ifBlocks = [];
                block.ifBlocks.push(item);
            }
        }
        var buffer = '';
        //do the actual rendering
        if(result) buffer = Midas.SmartyLib.executeBlocks(block.ifBlocks, smartyInstance);
        else buffer = Midas.SmartyLib.executeBlocks(block.elseBlocks, smartyInstance);
        if(buffer == undefined) return '';
        return buffer;
    },
    generateUUID: function(){
        var s = [];
        var hexDigits = "0123456789ABCDEF";
        for (var i = 0; i < 32; i++)  s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
        s[12] = "4";  // bits 12-15 of the time_hi_and_version field to 0010
        s[16] = hexDigits.substr((s[16] & 0x3) | 0x8, 1);  // bits 6-7 of the clock_seq_hi_and_reserved to 01
        var uuid = s.join("");
        return uuid;
    },
    executeBlocks: function(blocks, smartyInstance){
        var buffer = '';
        var out = {};
        var count = 1;
        if(!blocks || typeof(blocks) != 'object'){
            if(smartyInstance.debug) smartyInstance.output('No blocks to execute!');
            return;
        }
        var ins = this.generateUUID();
        var count = 0;
        blocks.each(function(item, index){
            if(typeOf(item) != 'object') return;
            //console.log(['block-X['+item.blockType+']', item.content]);
            //console.log(['block-X['+ins+':'+(count++)+']', item, blocks, buffer]);
            if(item.tag){
                buffer += smartyInstance.macro(item.tag.name, item.tag.attrs, item);
            }else if(item.output){
                buffer += item.output;
            }
        }.bind(this));
        return buffer;
    },
    forMacro: function(tag, smartyInstance, block){
        var requiredAttrs = ['from', 'item'];
        var buffer = '';
        if(this.requireAttrs(tag, requiredAttrs, smartyInstance)){
            var name;
            var indexName;
            if(tag.attrs.name){
                name = this.getAttr('name', tag.attrs);
                indexName = 'foreach.'+name+'.index';
            }else{ //assign a random ID for this loop variable
                name = 'A'+this.generateUUID(); //begins with a var
                indexName = 'foreach.'+name+'.index';
            }
            var from = this.getAttr('from', tag.attrs);
            var to = this.getAttr('to', tag.attrs);
            var keyName = this.getAttr('key', tag.attrs);
            if(!keyName) keyName = '__loop_key';
            var itemName = this.getAttr('item', tag.attrs);
            var collection = Midas.SmartyLib.evaluateSmartyPHPHybridVariable(from, smartyInstance);
            var collection_length = 0;
            var keys = [];
            for(index in collection){
                collection_length++;
                keys.push(index);
            }
            var count = 0;
            if(collection_length > 0){
                var indexName = 'foreach.'+name+'.index';
                if(typeof(collection) == 'string' && collection == ''){
                    return buffer;
                }
                var originalIndex = smartyInstance.get(indexName);
                var originalKey = smartyInstance.set(keyName);
                var originalItem = smartyInstance.set(itemName);
                if(collection.each){ //it's an array;
                    collection.each(function(item, index){ //handles continue
                        smartyInstance.set(indexName, count++);
                        smartyInstance.set(keyName, index);
                        smartyInstance.set(itemName, item);
                        buffer += Midas.SmartyLib.executeBlocks(this.blocks, smartyInstance);
                    }.bind(block));
                }else{ //let's hope this is an object
                    var item;
                    for(var index in collection){
                        item = collection[index];
                        smartyInstance.set(indexName, count);
                        smartyInstance.set(keyName, index);
                        smartyInstance.set(itemName, item);
                        //smartyInstance.output('x');
                        buffer += Midas.SmartyLib.executeBlocks(block.blocks, smartyInstance);
                        //count++;
                    }
                }
                smartyInstance.set(indexName, null);
                smartyInstance.set(keyName, null);
                smartyInstance.set(itemName, null);
            }else{
                smartyInstance.output('ERROR: empty collection('+from+')!');
                smartyInstance.output(collection);
                smartyInstance.output('environment:');
                smartyInstance.output(smartyInstance.environment);
            }
        }else{
            smartyInstance.output('missing a required attr! '+requiredAttrs);
            smartyInstance.output(tag.attrs);
        }
        return buffer;
    },
    requireAttrs: function(tag, attrs, smarty){
        var returnVal = true;
        var foundOne;
        var out;
        attrs.each(function(attr, aIndex){
            foundOne = false;
            out = '';
            tag.attrs.each(function(tagAttr, taIndex){
                if(attr == tagAttr.name){
                    foundOne = true;
                }
            });
            returnVal = returnVal && foundOne;
        });
        return returnVal;
    },
    getAttr: function(tag, attrs){
        var returnVal = null;
        attrs.each(function(attr, aIndex){
            if(tag == attr.name) returnVal = attr.value;
        });
        return returnVal;
    },
    output : function(value){
        if(console) {
            if(navigator.userAgent.toLowerCase().indexOf("applewebkit") != -1) {
                //console.log(value);
            } else {
                //console.log.apply(this,arguments);
            }
        }
    },
    getValue : function(expression){
        var parts = explode('|', expression);
        var value = parts[0].substring(1); // omit the '$'
        for(var lcv = 1; lcv < parts.length; lcv++){
            eval('value = '+parts[lcv]+'(value);');
        }
        //console.log(['getval', expression, value]);
        return value;
    }
};
Midas.Smarty.runtimeCache = {};

var XHTMLParser = new Class({
    Extends : Midas.SAXParser,
    stack : [],
    root : null,
    scripts : [],
    open : function(name, attrs){
        var node;
        if(name.indexOf(':') != -1){ //if this is one of FB's tags, we need to use innerHTML to get the correct tagName back out
            var attributes = '';
            for(key in attrs){
                attributes += key + '="'+attrs[key]+'" ';
            }
            node = new Element('div');
            node.innerHTML = '<'+name+' '+attributes+'></'+name+'>';
            node = node.children[0];
        }else{
            node = new Element(name, attrs);
        }
        if(this.stack.length > 0) this.stack.getLast().appendChild(node);
        this.stack.push(node);
    },
    content : function(text){
        if(this.stack.length > 0) this.stack.getLast().appendText(text);
    },
    close : function(name){
        //this.root = this.stack[0];
        this.root = this.stack.pop();
        //if(this.root.tagName.toLowerCase() != name.toLowerCase()) throw(this.root.tagName+' != '+name);
        if(name.toLowerCase() == 'script') this.scripts.push();
    },
    parse : function(html){
        //wrap the script tag bodies with CDATA tags so our strict parser won't freak out
        var exp = new RegExp('(<script\\b[^>]*>)([\\s\\S]*?)(<\\/script>)', 'igm');
        html = html.replace(exp, '$1/*<![CDATA[*//*---->*/$2/*--*//*]]>*/$3');
        this.parent('<xml>'+html+'</xml>');
        var result = this.root.getChildren();
        //don't ask
        if(result[0] && result[0].tagName.toLowerCase() == 'html') return result[0];
        if(this.stack[1]) return this.stack[1];
        return this.root.getChildren();
    }
});