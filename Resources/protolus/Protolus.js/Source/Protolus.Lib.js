/*
---
description: Protolus RAD framework port to JS

license: MIT-style

authors:
    - Abbey Hawk Sparrow

requires:
    - core/1.2.4: '*'
    - [Midas.Smarty, Midas.INIParser, Midas.Properties]

provides: [Protolus.Lib]
...
*/

if(!Protolus) var Protolus = {};
//todo: finish
// the purpose of this object is a hands-free cross domain shim. Ask for a gateway to a domain (by specifying the terminal script and go to town)
var Terminal = new Class({
    term_id : null,
    window_id : null,
    window : null,
    container : null,
    input :null,
    registry :{},
    history : [],
    historyPosition : null,
    currentHistory : [],
    initialize : function(){
        this.term_id = Midas.SmartyLib.generateUUID();
        this.window_id = 'protolus_debug_'+this.term_id;
        this.window = window.open(null, null, 'menubar=no,location=no,resizable=no,scrollbars=yes,status=no');
        var title = new Element('title', { text :'Protolus : Debug Output' });
        this.window.resizeTo(500,300);
        this.window.document.body.style.overflow = 'scroll';
        this.window.document.body.style.overflowX = 'hidden';
        this.container = new Element('ol');
        var head = this.window.document.getElementsByTagName('head')[0];
        head.appendChild(title);
        //head.appendChild(new Element('style', {
        //    html : 'pre{ width : 100% }'
        //}));
        this.window.document.body.innerHTML = '<input id="prompt" name="prompt" type="text" style="border:0px;width:100%;position:fixed;bottom:0px;color:008800;background-color:#000000">';
        this.window.document.body.setAttribute('style', 'margin:0px;background-color:#000000;color:008800;border-width:0px');
        this.window.document.body.appendChild(new Element('h2', {
            html : '<b>'+title.get('text')+'</b><hr/>',
            styles : {
                margin : '10px'
            }
        }));
        this.window.document.body.appendChild(this.container);
        this.input = document.id(this.window.document.getElementById('prompt'));
        this.input.focus();
        this.input.onkeyup = function(event){
            if(event.keyCode == 13){ //enter
                var command = this.input.value;
                this.input.value = '';
                if(command.trim() != ''){
                    this.history[this.history.length] = command;
                    this.historyPosition = null;
                    this.currentHistory = this.history.clone();
                    this.execute(command);
                }
            }
            if(event.keyCode == 38){ //up
                if(this.currentHistory.length == 0 || this.historyPosition == 0) return;
                if(this.historyPosition == null){
                    this.historyPosition = this.currentHistory.length-1;
                    this.currentHistory.push(this.input.value);
                }else{
                    this.currentHistory[this.historyPosition] = this.input.value;
                    this.historyPosition--;
                }
                this.input.value = this.currentHistory[this.historyPosition];
            }
            if(event.keyCode == 40){ //down
                if(this.historyPosition == null || this.historyPosition == this.currentHistory.length-1) return;
                this.currentHistory[this.historyPosition] = this.input.value;
                this.historyPosition++;
                this.input.value = this.currentHistory[this.historyPosition];
            }
        }.bind(this);
        this.registry.echo = function(options, callback){
            callback(options.join(' '));
        }.bind(this);
        this.registry.get = function(options, callback){
            var data = this.parseOptions(options);
            var result = '';
            data.args.each(function(name){
                result = name+' = '+Object.profile(eval(name))+'<br/>';
            });
            callback(result);
        }.bind(this);
        this.registry.set = function(options, callback){
            var data = {args:options};
            var result = '';
            console.log(['dd', 'json:', data.args[1].substring(0, 5)]);
            if(data.args[1].substring(0, 5) == 'json:'){
                json = data.args[1].substring(5);
                console.log(['JSON', json]);
                data.args[1] = JSON.decode(json);
                console.log('JSON2!!');
            }
            if(data.args.length == 2){
                window[data.args[0]] = data.args[1];
            }
            callback(result);
        }.bind(this);
        this.registry.panel = function(options, callback){
            var data = this.parseOptions(options);
            var openRequestCount = 0;
            var results = {};
            data.args.each(function(panel){
                openRequestCount++;
                new Request({
                    url : Protolus.templateLocation+panel+'.panel.tpl',
                    onSuccess : function(template){
                        openRequestCount--;
                        results[panel] = template;
                        if(openRequestCount == 0){
                            var result = '<b>Panel Fetch</b>';
                            Object.each(results, function(res, panelName){
                                result += '<hr/>'+panelName+'<br/><pre>'+res.entityEncode()+'</pre>';
                            });
                            callback(result);
                        }
                    }.bind(this),
                    onFailure :function(){
                        openRequestCount--;
                    }.bind(this)
                }).send()
            });
        }.bind(this);
        // render -d /data/profile -p profile
        this.registry.render = function(options, callback){
            var data = this.parseOptions(options);
            if(data.options.d){
                data.options.data = data.options.d;
                delete data.options.d;
            }
            if(data.options.p){
                data.options.panel = data.options.p;
                delete data.options.p;
            }
            console.log(['d', data]);
            if(!(data.options.panel && data.options.data)) throw('render requires data and panel!');
            new Request.JSON({
                url : data.options.data,
                onSuccess : function(json){
                    console.log(['data', json]);
                    Protolus.Page.render(data.options.panel, json, function(html){
                       callback('<pre>'+html.entityEncode()+'</pre>'); 
                    }, true);
                },
                onFailure : function(){
                    throw('data not found('+data.data+')!');
                }
            }).send();
        }.bind(this);
    },
    parseOptions : function(flatOptions){
        var options = {};
        var theArgs = [];
        var newOption = null;
        flatOptions.each(function(option){
            if(option.startsWith('-')){
                if(newOption != null){ //save this option
                    options[newOption] = true;
                    newOption = null;
                }
                if(option.startsWith('--')){
                    newOption = option.substring(2);
                }else newOption = option.substring(1);
            }else{
                if(newOption != null){ //save this option
                    options[newOption] = option;
                    newOption = null;
                }else theArgs[theArgs.length] = option;
            }
        }.bind(this));
        return {
            args : theArgs,
            options : options
        };
    },
    echo :function(command, message){
        if(!message) message = '';
        var messageElement = new Element('li', {
            html :command+'<br/>'+message
        });
        this.container.adopt(messageElement);
    },
    execute : function(text){
        var commandWords = text.splitHonoringQuotes(' ', ['"']);
        var command = commandWords.shift();
        if(this.registry[command]){
            this.registry[command](commandWords, function(result){
                this.echo('<b>&gt;'+text+'</b>', result);
            }.bind(this));
        }else{
            this.echo('<b>&gt;'+text+'</b>', 'command \''+command+'\' not found!');
        }
    }
});

var Conduit = new Class({
    'interval_id' : null,
    last_hash : null,
    id : 'id',
    cache_bust : 1,
    attached_callback : null,
    initialize : function(options){
        if(options && typeOf(options) == 'string') options = {location : options};
        //ensure there's a DOM element
        if(!options.location) options.location = '/child.html';
        if(!options.iframe){
            document.id(document.body).adopt(new Element('iframe',{
                src: options.location + '#' + encodeURIComponent(document.location.href),
                id : 'xd_frame_'+this.id
            }));
        }else{
            options.iframe = document.id(options.iframe);
            options.iframe.set('src', options.location + '#' + encodeURIComponent(document.location.href));
        }
    },
    postMessage : function(message, target_url, target) {
        if (!target_url) {
            return;
        }
        target = target || parent;  // default to parent
        if (window['postMessage']) {
            // the browser supports window.postMessage, so call it with a targetOrigin
            // set appropriately, based on the target_url parameter.
            target['postMessage'](message, target_url.replace( /([^:]+:\/\/[^\/]+).*/, '$1'));
        } else if (target_url) {
            // the browser does not support window.postMessage, so use the window.location.hash fragment hack
            target.location = target_url.replace(/#.*$/, '') + '#' + (+new Date) + (this.cache_bust++) + '&' + message;
        }
    },
    receiveMessage : function(callback, source_origin) {
        // browser supports window.postMessage
        if (window['postMessage']) {
            // bind the callback to the actual event associated with window.postMessage
            if (callback) {
                attached_callback = function(e) {
                    if ((typeof source_origin === 'string' && e.origin !== source_origin)
                    || (Object.prototype.toString.call(source_origin) === "[object Function]" && source_origin(e.origin) === !1)) {
                         return !1;
                     }
                     callback(e);
                 };
             }
             if (window['addEventListener']) {
                 window[callback ? 'addEventListener' : 'removeEventListener']('message', attached_callback, !1);
             } else {
                 window[callback ? 'attachEvent' : 'detachEvent']('onmessage', attached_callback);
             }
         } else {
             // a polling loop is started & callback is called whenever the location.hash changes
             this.interval_id && clearInterval(this.interval_id);
             this.interval_id = null;
             if (callback) {
                 this.interval_id = setInterval(function() {
                     var hash = document.location.hash,
                     re = /^#?\d+&/;
                     if (hash !== this.last_hash && re.test(hash)) {
                         this.last_hash = hash;
                         callback({data: hash.replace(re, '')});
                     }
                 }, 100);
             }
         }
     }
});