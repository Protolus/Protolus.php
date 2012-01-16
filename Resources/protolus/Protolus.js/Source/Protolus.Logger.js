/*
---
description: Protolus RAD framework port to JS

license: MIT-style

authors:
    - Abbey Hawk Sparrow

requires:
    - core/1.2.4: '*'
    - [Midas.Smarty, Midas.INIParser, Midas.Properties]

provides: [Protolus.Logger]
...
*/

if(!Protolus) var Protolus = {};
Protolus.Logger = {
    ERROR : 1,
    WARNING : 2,
    NOTICE : 3,
    INFO : 3,
    DEBUG : 3,
    DATA : 3,
    buffer : [],
    externalLogEndpoints : [],
    log: function(message, level){
        if(!message) return;
        if(!level) level = Protolus.Logger.ERROR;
        if(typeOf(level) == 'string') level = level.toLowerCase();
        switch(level){
            case 'error' :
            case Protolus.Logger.ERROR :
                document.id(document).fireEvent('log-error', { message : message });
                break;
            case 'warning' :
            case Protolus.Logger.WARNING :
                document.id(document).fireEvent('log-error', { message : message });
                break;
            case 'info' :
            case Protolus.Logger.INFO :
                document.id(document).fireEvent('log-error', { message : message });
                break;
            case 'notice' :
            case Protolus.Logger.NOTICE :
                document.id(document).fireEvent('log-error', { message : message });
                break;
            case 'debug' :
            case Protolus.Logger.DEBUG :
                document.id(document).fireEvent('log-error', { message : message });
                break;
            case 'data' :
            case Protolus.Logger.DATA :
                document.id(document).fireEvent('log-error', { message : message });
                break;
        }
        document.id(document).fireEvent('log', {
            level : level,
            message : message
        });
        if(window.debug && debug.log){ //if Ben Alman's debug wedge is loaded, use that
            //todo: handle the no console environment with : debug.setCallback
            switch(level){
                case 'error' :
                case Protolus.Logger.ERROR :
                    debug.error(message); return;
                    break;
                case 'warning' :
                case Protolus.Logger.WARNING :
                    debug.warning(message); return;
                    break;
                case 'info' :
                case Protolus.Logger.INFO :
                    debug.info(message); return;
                    break;
                case 'notice' :
                case Protolus.Logger.NOTICE :
                    debug.notice(message); return;
                    break;
                case 'debug' :
                case Protolus.Logger.DEBUG :
                    debug.debug(message); return;
                    break;
                case 'data' :
                case Protolus.Logger.DATA :
                    debug.data(message); return;
                    break;
            }
        }else{
            if(window.console && console.log){
                //todo: natively support the different console functions
                console.log(message);
            }else{
                //not much to do!
            }
        }
    },
    delayCounts : {},
    logPageEvents : function(){
        document.addEvent('locationchange', function(event){
            Protolus.Logger.log('location-change : '+ event.panel);
        });
        document.addEvent('panelload', function(event){
            Protolus.Logger.log(['LOAD', 'Element \''+event.target+'\' loaded with \''+event.name+'\'.', event]);
        });
        document.addEvent('index_panelload', function(event){
            Protolus.Logger.log('INDEX PANEL');
        });
        document.addEvent('panelrender', function(event){
            //Protolus.Logger.log(['RENDER', 'Panel \''+event.name+'\' rendered.', event]);
            //console.log(event.content);
        });
        document.addEvent('paneldelay', function(event){
            if(!Protolus.Logger.delayCounts[event.insert_id]) Protolus.Logger.delayCounts[event.insert_id] = 0;
            //if(Protolus.Logger.delayCounts[event.insert_id] < 50) Protolus.Logger.log(['DELAY', 'Could not find element \''+event.insert_id+'\' after rendering '+event.name+', rescheduling...', event]);
            Protolus.Logger.delayCounts[event.insert_id]++;
        });
        document.addEvent('paginate', function(event){
            Protolus.Logger.log('paginate : ' + event.page);
        });
    }
};