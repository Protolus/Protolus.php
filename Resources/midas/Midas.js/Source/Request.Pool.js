/*
---
description: This is an asynchronous JS request pool for MooTools. Sometimes it's useful to fetch a bunch of resources in parallel, this plugin facilitates that.

license: MIT-style

authors:
- Abbey Hawk Sparrow

requires:
- Request
- Class
- Request.JSON
- Request.HTML
- Asset

provides: [Request.Stable]

*/
Request.Pool = new Class({
    workers : [],
    successes : [],
    errors : [],
    data : {},
    initialize : function(requests, globalSuccess, globalError){
        requests.each(function(requestOptions){
            var request;
            if(requestOptions.onSuccess) requestOptions.successFunc = requestOptions.onSuccess;
            if(requestOptions.onFailure) requestOptions.failFunc = requestOptions.onFailure;
            requestOptions.onSuccess = function(data, xml){
                if(requestOptions.successFunc) requestOptions.successFunc(data, xml);
                if(requestOptions.id){
                    data.id = requestOptions.id;
                }
                this.successes.push(data);
                if(!this.isWorking()){
                    if(this.completeWithNoErrors()){
                        globalSuccess(this.successes);
                    }else{
                        globalError(this.errors, this.successes);
                    }
                }
            }.bind(this);
            requestOptions.onFailure = function(data){
                if(requestOptions.failFunc) requestOptions.failFunc(data);
                if(requestOptions.id){
                    data.id = requestOptions.id;
                }
                this.errors.push(data);
                if(!this.isWorking()){
                    if(this.completeWithNoErrors()){
                        globalSuccess(this.successes);
                    }else{
                        globalError(this.errors, this.successes);
                    }
                }
            }.bind(this);
            switch(requestOptions.objectType.toUpperCase()){
                case 'IMAGE' :
                case 'IMG' :
                    request = Asset.image(requestOptions.url, {
                        id: requestOptions.id,
                        title: requestOptions.title,
                        onload: requestOptions.onSuccess,
                        onerror: requestOptions.onFailure
                    });
                    break;
                case 'CSS' :
                    request = Asset.css(requestOptions.url, {
                        id: requestOptions.id,
                        title: requestOptions.title,
                        onload: requestOptions.onSuccess,
                        onerror: requestOptions.onFailure
                    });
                    break;
                case 'JAVASCRIPT' :
                case 'JS' :
                    request = Asset.javascript(requestOptions.url, {
                        id: requestOptions.id,
                        title: requestOptions.title,
                        onload: requestOptions.onSuccess,
                        onerror: requestOptions.onFailure
                    });
                    break;
                case 'JSON' :
                    request = new Request.JSON(requestOptions);
                    request.send();
                    break;
                case 'HTML' : 
                    request = new Request.HTML(requestOptions);
                    request.send();
                    break;
                default :
                    request = new Request(requestOptions);
                    request.send();
            }
            this.workers.push(request);
        }.bind(this));
    },
    isWorking :function(){
        return (this.workers.length != (this.successes.length + this.errors.length));
    },
    completeWithNoErrors :function(){
        return (this.workers.length == this.successes.length);
    }
});