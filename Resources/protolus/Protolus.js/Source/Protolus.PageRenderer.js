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
Protolus.PageRenderer = {
    callStack : [],
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
    simpleRender : false,
    panelDefined : function(panel){
        if(Protolus.Lib.file_exists(this.template_location+panel+this.panel_extension)){
            return true;
        }
        return false;
    },
    route: function(){
        
    },
    siloForPanel: function(panel){
        
    },
    setWrapper: function(wrapperName){
        if(Protolus.PageRenderer.wrapper == ''){
            Protolus.PageRenderer.wrapper = wrapperName;
            var wrapper = new Protolus.Panel(wrapperName, true);
            var renderedWrapper = wrapper.render({content:'<div id="protolus_root"></div>'});
            var wrapperDOM = renderedWrapper.toDOM();
            var wrapperHead = wrapperDOM.getElementsByTagName('head')[0];
            //we try to clean up the head (browsers don't like this)
            for(childIndex in wrapperHead.children){
                if(wrapperHead.children[childIndex].tagName){
                    if(wrapperHead.children[childIndex].getAttribute('origin') != 'protolus'){
                        try{
                            wrapperHead.removeChild(document.scripts[childIndex]);
                        }catch(ex){
                            //console.log('error manipulating head!');
                        }
                    }
                }
            }
            for(childIndex in document.scripts){
                if(document.scripts[childIndex].tagName){
                    if(document.scripts[childIndex].getAttribute('origin') != 'protolus'){
                        try{
                            document.scripts.removeChild(document.scripts[childIndex]);
                        }catch(ex){
                            //console.log('error manipulating head!');
                        }
                    }
                }
            }
            document.head.adopt(wrapperHead.children);
            document.body.innerHTML = '';
            var wrapperBody = wrapperDOM.getElementsByTagName('body')[0];
            document.body.adopt(wrapperBody.children);
            window.fireEvent('domreload', wrapperName);
            document.fireEvent('domreload', wrapperName);
            Protolus.PageRenderer.wrapperRoot = document.id('protolus_root').parentNode;
        }else{
            if(Protolus.PageRenderer.wrapper == wrapperName){
                //console.log(['NE', Protolus.PageRenderer.wrapper, wrapperName, Protolus.PageRenderer.wrapperRoot]);
                //if(Protolus.PageRenderer.wrapperRoot) Protolus.PageRenderer.wrapperRoot.innerHTML = '<div id="protolus_root"></div>';
                return;
            }
            //if we made it here, the page needs a new wrapper, so we need to refresh
            var newURL = window.location.protocol+'//'+window.location.hostname+window.location.search+window.location.hash;
            window.location.assign(newURL);
            window.location.reload(); // we might just be changing the hash
        }
    },
    render: function(template, data, target, attrs){
        Protolus.PageRenderer.time = (new Date()).getTime();
        Protolus.PageRenderer.oldPanel = Protolus.PageRenderer.panel;
        Protolus.PageRenderer.panel = template;
        if(target) window.location.hash='#'+template;
        if(!attrs) attrs = {};
        var panel = new Protolus.Panel(template);
        if(!target) target = Protolus.PageRenderer.oldPanel;
        return panel.render(data, Protolus.PageRenderer.oldPanel, attrs);
    },
    render_panel: function(panel_name, data){
        if(panel_name.split("\n").length > 1){
            Protolus.Lib.render_panel(data, target, this, attrs, immediate);
        }else{
            var renderValue = Protolus.PageRenderer.simpleRender;
            Protolus.PageRenderer.simpleRender = true;
            var panel = new Protolus.Panel(panel_name);
            var result = panel.render(data, true);
            Protolus.PageRenderer.simpleRender = renderValue;
            return result;
        }
    }
};