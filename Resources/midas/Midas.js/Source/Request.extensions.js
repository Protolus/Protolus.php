/*
---
description: This is an asynchronous JS request pool for MooTools. Sometimes it's useful to fetch a bunch of resources in parallel, this plugin facilitates that.

license: MIT-style

authors:
- Abbey Hawk Sparrow

requires:
- Class

provides: [Request]

*/
if(!Request){
    var Request = new Class({
        options : {
            url : '',
            method : 'GET',
            data : {},
            evalScripts : false,
            evalResponse : false,
            timeout : false,
            noCache : false,
            user : false,
            password : false,
            onRequest : false,
            onLoadStart : false,
            onProgress : false,
            onComplete : false,
            onCancel : false,
            onSuccess : false,
            onFailure : false,
            onException : false
        },
        initialize : function(){
        
        },
        send : function(){
            if(this.options.onRequest) this.options.onRequest(onRequest);
            if(this.options.onLoadStart) this.options.onLoadStart(onLoadStart);
            //TODO: WTF to do about progress?
            request({
                uri: this.options.url
            }, function (error, response, bodyText) {
                if(error){
                    //todo: create error object here
                    if(this.options.onException) this.options.onException(error);
                }else{
                    if(this.options.onSuccess) this.options.onSuccess(bodyText);
                }
                if(this.options.onComplete) this.options.onSuccess(onComplete);
            }.bind(this));
        },
        getHeader : function(){},
        setHeader : function(){},
        cancel : function(){},
    });
}