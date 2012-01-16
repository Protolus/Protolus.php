/*
---
description: An extensible Mootools object container bridging to a pureJS SAX parser

license: [MIT-style, LGPL]

authors:
- Abbey Hawk Sparrow

requires:
    core/1.2.4: '*'

provides: [Midas.XMLParser]
...
*/
if(!Midas) var Midas = {};
Midas.TagParser = new Class({ //my ultra-slim tag parser
    strict : true,
    opener : '<',
    closer : '>',
    attributeAssign : '=',
    attributeDelimiters : ['"'],
    closeEscape : '/',
    allowUndelimitedAttributes : false,
    literalTags : [],
    unaryTags : [],
    specialTags : {},
    initialize: function(options){
        Object.each(options, function(option, name){
            this[name] = option;
        });
        if(typeOf(this.literalTags) == 'string') this.literalTags = this.literalTags.split(',')
        if(typeOf(this.attributeDelimiters) == 'string') this.attributeDelimiters = this.attributeDelimiters.split(',')
    },
    open: function(tagName, attributes){
        sys.puts('open:'+tagName.toLowerCase());
    },
    content: function(text){
        sys.puts('con:'+text);
    },
    close: function(tagName){
        sys.puts('close:'+tagName.toLowerCase());
    },
    error: function(exception){
        sys.puts(exception);
    },
    parse: function(xmlChars){
        var tagOpen = false;
        var currentTag = '';
        var content = '';
        var ch;
        var tagStack = [];
        var literalMode = false;
        var strictError = 'Strict parse error: Unmatched Tag!';
        for(var lcv = 0; lcv < xmlChars.length; lcv++){
            ch = String.fromCharCode(xmlChars[lcv]);
            if(tagOpen){
                if(ch == this.closer){
                    var tag = this.parseTag(currentTag);
                    if(tag.name[0] == this.closeEscape){
                        tag.name = tag.name.substring(1);
                        this.close(tag);
                        var lastTag = tagStack.pop();
                        if(this.strict && lastTag.name != tag.name){
                            this.error(strictError+' ['+lastTag.name+']');
                            return;   
                        }
                        literalMode = this.literalTags.contains(tagStack[tagStack.length-1]);
                    }else{
                        this.open(tag);
                        tagStack.push(tag);
                        literalMode = this.literalTags.contains(tagStack[tagStack.length-1]);
                        if(currentTag[currentTag.length-1] == this.closeEscape || this.unaryTags.contains(tag.name)){
                            this.close(tag);
                            var lastTag = tagStack.pop();
                            if(this.strict && lastTag.name != tag.name){
                                this.error(strictError+' ['+lastTag.name+']');
                                return;
                            }
                            literalMode = this.literalTags.contains(tagStack[tagStack.length-1]);
                        }
                    }
                    tagOpen = false;
                }else currentTag += ch;
            }else{
                if(!literalMode && ch == this.opener){
                    currentTag = '';
                    tagOpen = true;
                    if(content.trim() != '') this.content(content.trim());
                    content = '';
                }else content += ch;
            }
        }
        if(content.trim() != '') this.content(content.trim());
    },
    parseTag: function(tag){
        var ch;
        var currentValue = '';
        var tagName = false;
        var attributeName = false;
        var inQuote = false;
        var attributes = {};
        for(var lcv = 0; lcv < tag.length; lcv++){
            ch = tag[lcv];
            if(tagName){
                var endedQuote = false;
                if(inQuote){
                    if(inQuote == ch){ //end of quote
                        inQuote = false;
                        endedQuote = true;
                    }else{
                        currentValue += ch;
                        continue;
                    }
                }else{
                    if(this.attributeDelimiters.contains(ch)){
                        inQuote = ch;
                        continue;
                    }
                }
                if(attributeName){
                    if(ch == ' ' || endedQuote){
                        attributes[attributeName.trim()] = currentValue;
                        attributeName = false;
                        currentValue = '';
                    }else currentValue += ch;
                }else{
                    if(ch == this.attributeAssign){
                        attributeName = currentValue;
                        currentValue = '';
                    }else currentValue += ch;
                }
                this.attributeDelimiters.contains(ch)
            }else{
                if(ch == ' '){
                    tagName = currentValue;
                    currentValue = '';
                }else currentValue += ch;
            }
        }
        if(attributeName && currentValue != ''){
            attributes[attributeName.trim()] = currentValue;
        }
        if(!tagName) tagName = currentValue;
        return {
            name: tagName,
            attributes: attributes
        };
    }
});


Midas.HTMLParser = new Class({
    Extends : Midas.TagParser,
    root : null,
    nodeStack : [],
    initialize: function(){
        this.parent({
            strict : true,
            opener : '<',
            closer : '>',
            attributeAssign : '=',
            attributeDelimiters : ['"', "'"],
            closeEscape : '/',
            allowUndelimitedAttributes : false,
            literalTags : ['script'],
            unaryTags : ['br', 'hr', 'img']
        });
        this.root = new Element('root');
        this.  nodeStack.push(this.root);
    },
    open: function(tagName, attributes){
        var element = new Element(tagName, attributes);
        this.nodeStack.getLast().appendChild(element);
        this.nodeStack.push(element);
    },
    content: function(text){
        this.nodeStack.getLast().appendText(text);
    },
    close: function(tagName){
        this.nodeStack.pop(element);
    }
});
Midas.SmartyParser = new Class({
    Extends : Midas.TagParser,
    initialize: function(){
        this.parent({
            strict : true,
            opener : '{',
            closer : '{',
            attributeAssign : '=',
            attributeDelimiters : ['"', "'"],
            closeEscape : '/',
            allowUndelimitedAttributes : true,
            literalTags : ['literal'],
            specialTags : {
                'if':function(text){
                    
                },
                'for':function(text){
                
                }
            }
        });
    }
});