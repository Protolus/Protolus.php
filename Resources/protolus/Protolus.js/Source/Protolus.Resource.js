/*
---
description: Protolus RAD framework port to JS

license: MIT-style

authors:
    - Abbey Hawk Sparrow

requires:
    - core/1.2.4: '*'
    - [Midas.Smarty, Midas.INIParser, Midas.Properties]

provides: [Protolus.PageRenderer, Protolus.Panel, Protolus.Lib, Protolus.Logger, Protolus.WebApplication]
...
*/

if(!Protolus) var Protolus = {};

// Routes
Protolus.routes = [];
Protolus.route = function(path, callback){
    if(Protolus.routes.length == 0){
        var iniParser = new Midas.OrderedINIParser();
        var myRequest = new Request({
            url: '/App/routes.conf',
            onSuccess: function(data){
                var ini = iniParser.parse(data, true);
                Protolus.routes = ini[0].value;
                Protolus.routes.each(function(value, index){
                    //Protolus.Panel.exists(path, function(panelExists){
                        if(Protolus.routes[index].key == '%') Protolus.routes[index].key = '(.*?)'
                        else Protolus.routes[index].key = Protolus.routes[index].key.replace(/\*/g, '(.*?)').replace('#', '([0-9]*?)');
                        Protolus.routes[index].regex = new RegExp('^'+Protolus.routes[index].key+'$');
                        count = 1;
                        var pos = Protolus.routes[index].value.indexOf('*');
                        while(pos != -1){
                            Protolus.routes[index].value = Protolus.routes[index].value.substring(0, pos)+'$'+(count++)+Protolus.routes[index].value.substring(pos+1);
                            pos = Protolus.routes[index].value.indexOf('*');
                        }
                    //});
                });
                Protolus.route(path, callback);
            }
        }).send();
        return;
    }
    var called = false;
    Protolus.Panel.exists(path, function(panelExists){
        Protolus.routes.each(function(route, index){
            if(called) return;
            //console.log(['routing', path, path.match(route.regex), route])
            if(route.key == '(.*?)'){
                if(!panelExists){
                    callback(path.replace(route.regex, route.value));
                    called = true;
                }
            }else if(!called && path.match(route.regex)){
                callback(path.replace(route.regex, route.value));
                called = true;
            }
        }.bind(this));
    }.bind(this));
    if(!called){
        callback(path);
    }
};
Protolus.consumeGetParameters = function(panel){
    if(panel.indexOf('?') != -1){ //we have some additional args we need to strip off
        var parts = panel.split('?');
        panel = parts[0];
        var parameters = parts[1].split('&');
        parameters.each(function(line){
            var words = line.split('=');
            var key = words[0];
            var value = words[1];
            Protolus.globals[key] = value;
        });
    }
    return panel;
};

//Resources
Protolus.resourceDir = '/Resources/';
Protolus.resources = {};
Protolus.resources.components = {};
Protolus.resources.js = [];
Protolus.Resource = new Class({
    resources : [],
    scriptFiles : [],
    styleFiles : [],
    loaded : false,
    initialize : function(name, callback){
        if(Protolus.resources.components[name] != undefined) return Protolus.resources.components[name];
        else Protolus.resources.components[name] = this;
        var iniParser = new Midas.OrderedINIParser();
        new Request({
            url: '/Resources/'+name+'/component.conf', 
            async: (!(callback === true)),
            onSuccess: function(data){
                this.resources = iniParser.parse(data, true);
                this.resources.each(function(section){
                    if(section.key.toLowerCase() == 'resource'){
                        section.value.each(function(item){
                            switch(item.value.split('.').pop().toLowerCase()){
                                case 'js':
                                    this.scriptFiles[this.scriptFiles.length] = Protolus.resourceDir+name+'/'+item.value;
                                    break;
                                case 'css':
                                    this.styleFiles[this.styleFiles.length] = Protolus.resourceDir+name+'/'+item.value;
                                    break;
                            }
                        }.bind(this));
                    }
                }.bind(this));
                Protolus.requireJS(this.scriptFiles, name, callback);
                if(this.styleFiles.length > 0){
                    Protolus.requireCSS(this.styleFiles, name, callback);
                }
            }.bind(this),
            onFailure: function(data){
                console.log('RESOURCE ERROR!')
            }
        }).send();
    }
});
Protolus.requireBundle = function(name, callback){
    var resources = Protolus.loadedResources();
    if(!resources.contains(name)){
        //console.log(['RES', name]);
        new Protolus.Resource(name);
    }//else console.log(['RES-E', name]);
}
Protolus.minify = true;
Protolus.requireGroups = {};
Protolus.loadedResources = function(){
    //var head = document.id(document.head);
    var documentHead = document.id(document.getElementsByTagName('head')[0]);
    var results = [];
    documentHead.getChildren().each(function(tag){
        if(!tag.get('resource')) return;
        tag.get('resource').split('-').each(function(resource){
            if(!results.contains(resource)) results.push(resource); 
        });
    });
    return results;
};
Protolus.requireCSS = function(resource, name, callback){
    var request = Midas.SmartyLib.generateUUID();
    if(typeOf(resource) == 'array'){ 
        if(!Protolus.minify){ //many non-minified items
            resource.each(function(res){
                Protolus.requireGroups[request]++;
                Protolus.requireCSS(res, name, function(){
                    Protolus.requireGroups[request]--;
                    if(Protolus.requireGroups[request] == 0){
                        callback();
                    }else{
                        console.log(['require ND', request, Protolus.requireGroups[request]]);
                    }
                });
            });
        }else{ //minified
            var base = Protolus.sharedBaseDirectory(resource);
            var relative_path;
            var paths = [];
            resource.each(function(res){
                relative_path = res.substring(base.length+1);
                paths[paths.length] = relative_path;
            });
            //minify default:
            //var link = '/min/?b='+base.substring(1)+'&f='+paths.join(',');
            var link = '/style/min/'+name;
            var styleTag = new Element('link', {
                origin : 'protolus',
                rel : 'stylesheet',
                type : 'text/css',
                href : link,
                resource : name
            });
            //console.log(styleTag);
            var head = document.getElementsByTagName('head')[0];
            head.adopt(styleTag);
        }
    }else{
        
    }
}
Protolus.executeWhenResourceExists = function(res, callback, args){
    if(typeOf(res) == 'string') res = res.split(',');
    Protolus.whenTrueAct(function(){
        var result = true;
        var registered = Protolus.loadedResources();
        this.each(function(resource){
            result = result && registered.contains(resource);
        });
        return result;
    }.bind(res), callback, args);
}
Protolus.requireJS = function(resource, name, callback){
    var request = Midas.SmartyLib.generateUUID();
    if(typeOf(resource) == 'array'){ 
        if(!Protolus.minify){ //many non-minified items
            resource.each(function(res){
                Protolus.requireGroups[request]++;
                Protolus.requireJS(res, name, function(){
                    Protolus.requireGroups[request]--;
                    if(Protolus.requireGroups[request] == 0){
                        callback();
                    }else{
                        console.log(['require ND', request, Protolus.requireGroups[request]]);
                    }
                });
            });
        }else{ //minified
            var base = Protolus.sharedBaseDirectory(resource);
            var relative_path;
            var paths = [];
            resource.each(function(res){
                relative_path = res.substring(base.length+1);
                paths[paths.length] = relative_path;
            });
            //var link = '/min/?b='+base.substring(1)+'&f='+paths.join(',');
            var link = '/javascript/min/'+name;
            var scriptTag = new Element('script', {
                origin : 'protolus',
                type : 'text/javascript',
                src : link,
                onLoad : "this.setAttribute('resource', '"+name+"');",
                //resource :name,
            });
            var head = document.getElementsByTagName('head')[0];
            head.adopt(scriptTag);
        }
    }else{
        
    }
}