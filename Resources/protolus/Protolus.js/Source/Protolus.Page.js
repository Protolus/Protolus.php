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
Protolus.Page = {
    /*callStack : [],
    panel_extension : '.panel.tpl',
    wrapper_extension : '.wrapper.tpl',
    template_location : '',
    compile_location : '',
    cache_location : '',
    root_location : '',
    wrapper : '',
    panel : 'protolus_root',
    oldPanel : 'protolus_root',
    wrapperRoot : null,
    time : 0,
    simpleRender : false, //*/
    deadControllers : [],
    currentWrapper : null,
    wrapper : function(panel, callback){
        if(!callback) callback = function(renderedWrapper){
            var wrapperDom = renderedWrapper.toDOM();
            var head = wrapperDom.getElementsByTagName('head')[0];
            var body = wrapperDom.getElementsByTagName('body')[0];
            Protolus.currentWrapper = panel;
            if(body){ //do the body shuffle
                var currentRoot = document.id(Protolus.defaultTransition.target);
                var documentBody = document.id(document.body);
                var contentNodes = [];
                documentBody.getChildren().each(function(child){
                    contentNodes.push(child);
                });
                documentBody.empty();
                body.getChildren().each(function(child){
                    documentBody.appendChild(child);
                });
                if(currentRoot){ //todo: would likely be safer to do before hitting the dom
                    document.id(Protolus.defaultTransition.target).adopt(currentRoot.getChildren());
                }
                document.id(Protolus.defaultTransition.target).adopt(contentNodes);
            }
            if(head){
                head.getChildren().each(function(newTag){
                    var existing = null;
                    var documentHead = document.getElementsByTagName('head')[0];
                    documentHead.getChildren().each(function(tag){
                        if(newTag.tagName == tag.tagName){
                            //todo: implement buffering for resources and localization
                            try{
                                switch(newTag.tagName.toLowerCase()){
                                    case 'meta' : // SEO descriptions
                                        //todo: implement meta 'scopes' so meta's can naturally expire
                                        if(tag.get('property') && newTag.get('property') && tag.get('property').toLowerCase() == newTag.get('property').toLowerCase()){
                                            if(tag.get('content').toLowerCase() != newTag.get('content').toLowerCase()) tag.set('content', newTag.get('content'));
                                            existing = tag;
                                        }
                                        break;
                                    case 'link' :
                                        // todo: implement style garbage collection
                                        if(
                                            tag.get('rel').toLowerCase() == 'stylesheet' && 
                                            tag.get('href').toLowerCase() == newTag.get('href').toLowerCase()
                                        ){ //this is the same style!
                                            existing = tag;
                                        }
                                        if(tag.get('rel').toLowerCase() == 'icon' || tag.get('rel').toLowerCase() == 'shortcut icon'){ // again, only one, so safe to replace
                                            if(tag.get('href') != newTag.get('href')) tag.set('href', newTag.get('href'));
                                            existing = tag;
                                        }
                                        break;
                                    case 'title' :
                                        document.title = newTag.innerHTML; //there's only 1, that's easy enough
                                        existing = tag;
                                        break;
                                    case 'script' :
                                        //todo: implement detection of minifed JS packages as well as files
                                        if(tag.get('href') && tag.get('href').toLowerCase() == newTag.get('href').toLowerCase()){
                                            existing = tag;
                                        }
                                        //todo: implement hashing for inline scripts
                                        break;
                                }
                            }catch(ex){}
                            if(existing == null) head.adopt(newTag);
                        }
                    });
                });
            }
        };
        if(Protolus.currentWrapper != panel){
            //Protolus.Logger.log('WRAPPER['+panel+']');
            Protolus.Page.render(panel+'.wrapper', callback);
        }
    },
    render : function(panel, data, callback, preventPropogation){ //legal calls: (panel, data, callback), -(panel, data, options)>, (panel, data, true)i, (panel, data)>, (panel, callback), (panel, true)i, (panel)>
        if(typeOf(panel) == 'string'){
            if(panel.indexOf('.wrapper') != -1){
                panel = new Protolus.Panel(panel.substring(0, panel.indexOf('.wrapper')));
                panel.wrapper = true;
            }else{
                panel = new Protolus.Panel(panel);
            }
            panel.assign('content', '<div id="protolus_root"></div>');
        }
        if(preventPropogation) panel.onDemandRender = false;
        //todo: test data to see if we've actually passed in (panel, options) rather than (panel, data)
        if(!callback && (typeOf(data) == 'function' || typeOf(data) == 'boolean') ){ //we're calling (panel, callback) or (panel, true) depending on server data
            var immediate = (typeOf(data) == 'boolean' && data === true ? true : false);
            callback = data;
            //todo: check index to make sure this data exists (if we've previously checked and failed, don't check again)
            var iResult = false; //immediate mode result
            if(!Protolus.Page.deadControllers.contains(panel)){
                var vars = {};
                for(key in Protolus.globals) {
                    if(
                        typeOf(Protolus.globals[key]) != 'array' && 
                        typeOf(Protolus.globals[key]) != 'object' && 
                        typeOf(Protolus.globals[key]) != 'function' && 
                        typeOf(Protolus.globals[key]) != 'class' && 
                        typeOf(Protolus.globals[key]) != 'collection'
                    ) vars[key] = Protolus.globals[key];
                }
                var jsonRequest = new Request.JSON({
                    url: '/data/'+panel.name+'', 
                    async: (!immediate),
                    onSuccess: function(controllerData){
                        //we found a controller
                        iResult = Protolus.Page.render(panel, controllerData, callback);
                    },
                    onFailure: function(controllerData){
                        //no controller for this panel
                        Protolus.deadControllers.push(panel);
                        iResult = Protolus.Page.render(panel, [], callback);
                    }
                }).get(vars);
            }else{
                iResult = Protolus.Page.render(panel, [], callback);
            }
            return iResult;
        }
        //set all the data
        for(key in data) panel.assign(key, data[key]);
        for(key in Protolus.globals) panel.assign(key, Protolus.globals[key]);
        if(typeOf(callback) == 'function'){ //callback
            var result = panel.fetch(panel.template_location+panel.name+'.'+panel.fileType(), function(renderedText){
                document.id(document).fireEvent('pagerender', {
                    name : panel.name,
                    content : renderedText,
                    data : data,
                    globals : Protolus.globals
                });
                callback(renderedText);
            });
            return result;
        }else if(typeOf(callback) == 'object'){ //take action with our data
            var options = {};
            if(!options.duration) options.duration = Protolus.defaultTransition.duration;
            if(!options.transition) options.transition = Protolus.defaultTransition.transition;
            if(typeof(options.transition) == 'string') eval('options.transition = '+options.transition);
            if(!target) target = panel.name;
            if(!target) target = Protolus.defaultTransition.target;
            
            return;
        } else if(typeOf(callback) == 'boolean' && callback === true){
            var iResult = false;
            panel.fetch(panel.template_location+panel.name+'.'+panel.fileType(), function(renderedText){
                iResult = renderedText;
            }, true);
            return iResult;
        }
        //if we are here, this must be a global, single argument call
        Protolus.Page.render(panel, function(html){
            Protolus.replaceWhenAvailable('protolus_root', html, panel);
        });
    }
};