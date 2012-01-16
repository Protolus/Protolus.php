/*
---
description: This is an asynchronous JS request pool for MooTools. Sometimes it's useful to fetch a bunch of resources in parallel, this plugin facilitates that.

license: MIT-style

authors:
- Abbey Hawk Sparrow

requires:
- XHTMLParser
- Class
- Request.JSON
- Request.HTML
- Asset

provides: [Request.Stable]

*/
if(!Element){
    //dummy DOM for server-side management
    var Element = new Class({
        children : [],
        attributes : {},
        initialize : function(name, options){
            this.tagName = name;
            this.attributes = options;
        },
        appendChild : function(element){
            this.children.push(element);
        },
        appendText : function(text){
            this.children.push(text);
        },
        getChildren : function(expression){
            if(expression.substring(0, 2) == '//'){
                var results = [];
                var val = expression.substring(2);
                this.traverse(function(node){
                    if( node.tagName == val ) results.push(node);
                });
            }
        }
    });
    Element.Events = {};
}

if(!Elements) var Elements = new Class({});
if(!NodeList) var NodeList = new Class({});

//todo: wrap with conditional
Element.Events.hashchange = {
    onAdd: function (){
        var hash = location.hash;
        var hashchange = function (){
            if (hash == location.hash) return;
            hash = location.hash;
            document.id(window).fireEvent('hashchange', hash.indexOf('#') == 0 ? hash.substr(1) : hash);
        };
        if ("onhashchange" in window) window.onhashchange = hashchange;
        else hashchange.periodical(50);
    }
};

if(!Element.diff){
    Element.implement({
        diff : function(node){
            //console.log(['diff', this, node]);
            this.childNodes.each(function(child, index){
                //console.log(['node comp', child.sameAs(node.childNodes[index])]);
            });
        }
    });
}

if(!Element.sameAs){
    Element.implement({
        sameAs : function(node, recursive){
            different = false;
            if(this.nodeName != node.nodeName) different = true;
            this.attributes.each(function(value, name){
                if(node[name] != value) different = true;
            });
            if(recursive){
                if(this.childNodes.length != node.childNodes.length) different = true;
                else this.childNodes.each(function(child, index){
                    if(!child.sameAs(node.childNodes[index])) different = true;
                });
            }
            return !different;
        }
    });
}

if(!Element.traverse){
    Element.implement({
        traverse : function(nodeFunction){
            nodeFunction(this);
            this.getChildren().each(function(child){
                child.traverse(nodeFunction);
            });
        }
    });
}

if(!Element.siblingsBefore){
    Element.implement({
        siblingsBefore : function(){
            var results = [];
            var found = false;
            this.getParent().getChildren().each(function(child){
                if(this == child) found = true;
                if(!found) results.push(child);
            });
            return new Elements(results);
        }
    });
}

if(!Element.siblingsAfter){
    Element.implement({
        siblingsAfter : function(){
            var results = [];
            var found = false;
            this.getParent().getChildren().each(function(child){
                if(found) results.push(child);
                if(this == child) found = true;
            });
            return new Elements(results);
        }
    });
}

if(!Element.aquiresStyle){
    Element.implement({
        aquiresStyle : function(name, callback){
            if(this.getStyle(name)){
                callback(this.getStyle(name));
            }else{
                this.aquiresStyle.delay(50, this, [name, callback]);
            }
        }
    });
}

if(!Element.removeElements){
    Element.implement({
        removeElements : function(elements){
            elements.each(function(element){
                if(this.contains(element)){
                    try{
                        this.removeChild(element);
                    }catch(ex){ //this means it's contained but not a direct child... recurse
                        this.getChildren().each(function(child){
                            document.id(child).removeElements(elements);
                        }.bind(this));
                    }
                }
            }.bind(this));
            return this;
        }
    });
}

if(!Element.iFrameContainsJSON){
    Element.implement({
        iFrameContainsJSON : function(callback){
            try{
                var data = JSON.decode(this.contentWindow.document.body.innerHTML);
                if(!data) throw('nope');
                try{
                    callback(data);
                }catch(ex2){
                    console.log(['error', ex2])
                }
            }catch(ex){
                this.iFrameContainsJSON.delay(50, this, [callback]);
            }
        }
    });
}

if(!Element.hasStyle){
    Element.implement({
        hasStyle : function(name, style){
            var result = false;
            var num = Number.from(this.getStyle(name));
            if(num != null){
                switch(style.substring(0,1)){
                    case '>' :
                        if(num > Number.from(style.substring(1))) result = true;
                        break;
                    case '<' :
                        if(num > Number.from(style.substring(1))) result = true;
                        break;
                    default:
                        if(num == Number.from(style)) result = true;
                }
            }else{
                switch(style.substring(0,1)){
                    case '>' :
                        if(this.getStyle(name) > style.substring(1)) result = true;
                        break;
                    case '<' :
                        if(this.getStyle(name) > style.substring(1)) result = true;
                        break;
                    default:
                        if(this.getStyle(name) == style) result = true;
                }
            }
            return result;
        }
    })
}

if(!Elements.mergedStyles){
    Elements.implement({
        mergedStyles : function(styles){
            results = [];
            if(typeOf(styles) == 'string'){
                styles = styles.split(',');
            }
            styles.each(function(style){
                results = results.concat(this.getStyle(style));
            }.bind(this));
            return results;
        }
    });
}

if(!Element.enlargeToFit){
    Element.implement({
        enlargeToFit : function(element, callback){
            if(this.dummy && this.src == this.dummy.src){
                this.resizeToFit();
                return;
            }
            this.dummy = new Image();
            if(!this.resizeToFit){
                this.resizeToFit = function(){
                    var theseDim = {x:this.dummy.width, y:this.dummy.height};
                    var thoseDim = element.getSize();
                    var aR = this.dummy.height/this.dummy.width;
                    var viewAR = thoseDim.y/thoseDim.x;
                    if(aR > 1){ //viewport orientation
                        if(aR < viewAR){ //blow up to fit hieght
                            this.setStyle('height', thoseDim.y);
                            this.setStyle('width', (aR)*thoseDim.y);
                        }else{ //blow up to fit width
                            this.setStyle('height', (aR)*thoseDim.x);
                            this.setStyle('width', thoseDim.x);
                        }
                    }else{
                        if(aR < viewAR){ //blow up to fit hieght
                            this.setStyle('height', thoseDim.y);
                            this.setStyle('width', (1/aR)*thoseDim.y);
                        }else{ //blow up to fit width
                            this.setStyle('height', (aR)*thoseDim.x);
                            this.setStyle('width', thoseDim.x);
                        }
                    }
                    if(callback) callback();
                }.bind(this);
                this.dummy.onload = this.resizeToFit;
            }
            this.dummy.src = this.src;

        }
    });
}

if(!Elements.excludeStyles){
    Elements.implement({
        excludeStyles : function(styles){
            var results = [];
            this.each(function(element){
                var found = false;
                Object.each(styles, function(style, name){
                    if(typeOf(style) == 'array'){
                        style.each(function(thisStyle){
                            found = found || element.hasStyle(name, thisStyle);
                        }.bind(this));
                    }else{
                        found = found || element.hasStyle(name, style);
                    }
                }.bind(this));
                if(!found) results.push(element);
            }.bind(this));
            return new Elements(results);
        }
    });
}

if(!Elements.includeStyles){
    Elements.implement({
        includeStyles : function(styles){
            results = [];
            this.each(function(element){
                var found = false;
                Object.each(styles, function(style, name){
                    if(typeOf(style) == 'array'){
                        style.each(function(thisStyle){
                            found = found || element.hasStyle(name, thisStyle);
                        }.bind(this));
                    }else{
                        found = found || element.hasStyle(name, style);
                    }
                }.bind(this));
                if(found) results.push(element);
            }.bind(this));
            return new Elements(results);
        }
    });
}

if(!Element.transitionIn){
    Element.implement({
        transitionIn : function(element, options, outgoingOptions){ //todo: support callback
            if(typeOf(element) == 'string') element = element.toDOM();
            if(!outgoingOptions) outgoingOptions = options; //todo: actually we want to invert the option values here... later
            element.setStyle('position', 'absolute');
            element.setStyle('opacity', 0);
            element.inject(this, 'before');
            this.fade('out');
            element.fade('in');
            element.setStyle('position', 'relative');
            this.dispose();
            return element[0];
        }
    });
}

if(!Element.linkText){
	Element.implement({
		linkText : function(element, fieldname, regex){
			if(!this.tagName.toLowerCase() == 'input') throw('invalid element');
			if(!fieldname) fieldname = 'html';
			var reactor = function(){
				var val = this.value;
				element = document.id(element);
				if(element){
					element.set(fieldname, val);
				}
			}.bind(this);
			this.addEvent('keydown', reactor);
			this.addEvent('keyup', reactor);
			this.addEvent('keypress', reactor);
		}
	});
}