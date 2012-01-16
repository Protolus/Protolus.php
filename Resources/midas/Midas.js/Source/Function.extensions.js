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
if(!Function.actionTimeout) Function.actionTimeout = 16384;
if(!Function.whenTrue){
    Function.implement({
        whenTrue : function(actionFunction, args, delayFunction, timeoutFunction, timeout, counter){
            if(!timeout) timeout = Function.actionTimeout;
            if(!counter) counter = 0;
            if(!timeoutFunction) timeoutFunction = function(event){
                throw('Condition not met after '+event.time+'ms');
            };
            var result = this();
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
                this.whenTrue.delay(delayTime, this, [actionFunction, args, delayFunction, timeoutFunction, timeout, counter]);
                if(delayFunction) delayFunction({
                    count : counter,
                    time : delayTime
                });
            }else{
                actionFunction.apply(this, args);
            }
        }
    });
}