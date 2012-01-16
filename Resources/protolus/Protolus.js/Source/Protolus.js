/*
---
description: Protolus RAD framework port to JS

license: MIT-style

authors:
    - Abbey Hawk Sparrow

requires:
    - core/1.2.4: '*'
    - [Midas.Smarty, Midas.INIParser, Midas.Properties, Protolus.Page, Protolus.Panel]

provides: [Protolus.currentPanel(), Protolus.initialize()]
...
*/

if(!Protolus) var Protolus = {};
Protolus.root = '/';
Protolus.dynamic = false;
Protolus.globals = {
    protolusVersion : '{$ version $}', // <- replace as part of the build process
    protolusBuild : '{$ build $}', // <- replace as part of the build process
    protolusImplementation : 'js',
    protolusApplication : 'Protolus'
};
Protolus.renderEventCallbacks = {};
//todo: we may need to store environments here, so we have a common 'this' between render & destroy per panel
Protolus.onRender = function(panel, callback){ //takes the place of 'domready' for most events in protolus
    if(!Protolus.renderEventCallbacks[panel]) Protolus.renderEventCallbacks[panel] = [];
    Protolus.renderEventCallbacks[panel].push(callback);
    window.addEvent(panel+'_panelload', callback);
    window.addEvent('domready', callback);
};
Protolus.onDestroy = function(panel, callback){ //takes the place of 'domready' for most events in protolus
    if(!Protolus.renderEventCallbacks[panel]) Protolus.renderEventCallbacks[panel] = [];
    Protolus.renderEventCallbacks[panel].push(callback);
    window.addEvent(panel+'_paneldestroy', callback);
    window.addEvent('unload', callback);
};
Protolus.releaseRenderCallbacks = function(panel){
    if(Protolus.renderEventCallbacks[panel]){
        Protolus.renderEventCallbacks[panel].each(function(callback){
            window.removeEvent(panel+'_panelload', callback);
            window.removeEvent('domready', callback);
            window.removeEvent(panel+'_paneldestroy', callback);
            window.removeEvent('unload', callback);
        });
    }
};
Protolus.defaultTransition = {
    transition : 'Fx.Transitions.Sine.easeOut',
    duration : 5000,
    target : 'protolus_root'
};
Protolus.transition = function(options){
    transitionOptions = {};
    for(key in Protolus.defaultTransition) transitionOptions[key] = Protolus.defaultTransition[key];
    for(key in options) transitionOptions[key] = options;
    if(typeof(transitionOptions.transition) == 'string') eval('transitionOptions.transition = '+transitionOptions.transition);
    return transitionOptions;
}
Protolus.reverseTransition = function(transition){
    //todo: implement
};
Protolus.sharedBaseDirectory = function(paths){
    return paths.commonBase('/');
}
Protolus.currentLocation = function(){
    if(window.location.hash){
        panel = window.location.hash.substr(1);
    }else{
        var panelRoot = window.location.protocol+'//'+window.location.hostname+Protolus.root;
        var url = window.location.protocol+'//'+window.location.hostname+window.location.pathname
        if(url.indexOf(panelRoot) == -1 || (panel = url.substring(panelRoot.length)) == '') panel = 'index';
    }
    // if there is a script extension on the panel, ignore it
    if(panel.indexOf('.php') != -1) panel = panel.substr(0, panel.indexOf('.php'));
    //pull off a trailing slash if there is one
    if(panel.substr(panel.length-1, 1) == '/') panel = panel.substr(0, panel.length-1);
    return panel;
}
Protolus.currentPanel = function(callback){
    var panel = Protolus.currentLocation();
    Protolus.route(panel, function(routedPanel){
        routedPanel = Protolus.consumeGetParameters(routedPanel);
        if(callback) callback(routedPanel);
        panel = routedPanel;
    }.bind(this));
    return panel;
};
Protolus.replacementTimeout = 16384;
Protolus.renderDelays = {};
Protolus.renderCounts = {};
if(!Protolus.actionTimeout) Protolus.actionTimeout = 16384;
Protolus.whenTrueAct = function(evaluatorFunction, actionFunction, args, delayFunction, timeoutFunction, timeout, counter){
    if(!timeout) timeout = Protolus.actionTimeout;
    if(!counter) counter = 0;
    if(!timeoutFunction) timeoutFunction = function(event){
        throw('Condition not met after '+event.time+'ms');
    };
    var result = evaluatorFunction();
    if(!result){
        var delayTime = Math.pow(2, counter); // geometric falloff
        if(delayTime >= timeout){
            timeoutFunction({
                count : counter,
                time : delayTime
            });
            return;
        }
        counter++;
        Protolus.whenTrueAct.delay(delayTime, this, [evaluatorFunction, actionFunction, args, delayFunction, timeoutFunction, timeout, counter]);
        if(delayFunction) delayFunction({
            count : counter,
            time : delayTime
        });
    }else{
        actionFunction.apply(this, args);
    }
}
Protolus.rewriteAllRelativeLinksToSoveriegn = function(){
    var links = $$('a');
    links.each(function(link){ //overwrite all local links
        var href = link.get('href');
        if(href && href.indexOf('://') == -1 && !href.startsWith('#')){
            link.set('href', '#'+href.substr(1));
        }
    });
};
Protolus.replaceWhenAvailable = function(id, content, name){
    id.whenInDOM(function(){
        var target = '';
        if(id.indexOf('_panel') != -1){
            var panel = id.substring(0, id.indexOf('_panel'));
            Protolus.refreshTimes[panel] = (new Date()).getTime();
            target = id;
        }else if(id == 'protolus_root'){
            target = 'protolus_root';
            Protolus.refreshTimes['_root_'] = (new Date()).getTime();
            //console.log(['root', content, name]);
        }
        
        var newDocument = content.toDOM();
        var replaced = document.id(target).innerHTML;
        var oldContent = document.id(target).getChildren();
        document.id(id).empty().adopt(newDocument);
        document.id(document).fireEvent('panelload', {
            name : name,
            content : content,
            replaced : replaced,
            replacedElements : oldContent,
            newElements : newDocument,
            target : target
        });
        document.id(document).fireEvent(name+'_panelload', {
            name : name,
            content : content,
            replaced : replaced,
            replacedElements : oldContent,
            newElements : newDocument,
            target : target
        });
    }, function(){ //delay
        document.id(document).fireEvent('paneldelay', {
            name : name,
            content : content,
            insert_id : id,
            delay : Protolus.renderDelays[id]
        });
    });
};
Protolus.refreshTimes = [];
Protolus.refreshTimeout = 500;
Protolus.templateLocation = '/App/Panels/';
Protolus.panelExtension = 'panel.tpl';
Protolus.refreshTime = function(panel){
    return Protolus.refreshTimes[panel]?Protolus.refreshTimes[panel]:0;
};
Protolus.initialized = false;
Protolus.debug = function(){
    var debugTerminal = new Terminal();
    document.addEvent('log', function(event){
        debugTerminal.echo(event.message);
    });
}
Protolus.initialize = function(){
    Protolus.globals.protolusIdentity = Protolus.globals.protolusApplication+'.'+Protolus.globals.protolusImplementation+' v.'+Protolus.globals.protolusVersion+'(build '+Protolus.globals.protolusBuild+')';
    document.addEvent('domready', function(){
        if(!Protolus.initialized){
            Protolus.Logger.log(Protolus.globals.protolusIdentity+' startup');
            Protolus.initialized = true;
            Protolus.currentPanel(function(panel){
                Protolus.Panel.exists(panel, function(panelExists){
                    if(panelExists){
                        document.innerHTML = Protolus.Page.render(panel, function(html){
                            Protolus.replaceWhenAvailable('protolus_root', html, panel);
                        });
                    }else{
                        throw('Panel doesn\'t exist(\''+panel+'\')!');
                    }
                });
            });
            Protolus.soveriegn();
            Protolus.Logger.logPageEvents();
        }
    });
};
Protolus.soveriegn = function(){
    var url = new URI(window.location);
    var location = url.get('fragment');
    if(location && (!!url.get('file') || url.get('directory') != '/')){ // domain.com/target1#target2 -> domain.com/#target2
        window.location = url.get('domain')+'/'+'#'+location;
        return;
    }
    Protolus.rewriteAllRelativeLinksToSoveriegn();
    if(location){
        Protolus.dynamic = true;
        Protolus.route(location, function(panel){ // domain.com/#target -> (loads content)
            document.id(document).fireEvent('locationchange', {
                location : location,
                panel : panel
            });
            panel = Protolus.consumeGetParameters(panel);
            Protolus.Page.render(panel, function(html){
                Protolus.replaceWhenAvailable('protolus_root', html, panel);
            });
        });
        window.addEvent('hashchange', function (hash) { // handle the user moving across sovereign url
            if((new Date()).getTime()-Protolus.refreshTime('_root_') > Protolus.refreshTimeout){
                if(location && (!!url.get('file') || url.get('directory') != '/')){
                    window.location = url.get('domain')+'/'+'#'+location;
                    return;
                }
                Protolus.route(hash, function(panel){
                    document.id(document).fireEvent('locationchange', {
                        location : hash,
                        panel : panel
                    });
                    panel = Protolus.consumeGetParameters(panel);
                    Protolus.Page.render(panel, function(html){
                        Protolus.replaceWhenAvailable('protolus_root', html, panel);
                    });
                });
            }
        });
    }
};