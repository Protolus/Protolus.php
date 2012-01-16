/*
---
description: This is an asynchronous JS request pool for MooTools. Sometimes it's useful to fetch a bunch of resources in parallel, this plugin facilitates that.

license: MIT-style

authors:
- Abbey Hawk Sparrow

requires:
- Request
- Class

provides: [Request.Stable]

*/
Request.Stable = new Class({
    worker : {},
    baseClass : null,
    initialize : function(options, baseClass){ //baseClass is experimental, do not use yet
        if(!baseClass) this.baseClass = Request;
        else this.baseClass = baseClass;
        var completed = false;
        options = this.wrap(options, 'onSuccess', this.complete.bind(this));
        options = this.wrap(options, 'onFailure', this.complete.bind(this));
        options = this.wrap(options, 'onError', this.complete.bind(this));
        Request.Stable.workers.each(function(worker){ //try to use the first inactive thread
            if(!worker.working && !completed){ //potential for thread collision
                worker.working = true;
                worker.slave = new this.baseClass(options);
                worker.slave.send();
                this.worker = worker;
                completed = true;
            }
        }.bind(this));
        if(!completed){ //if we haven't already found a place to execute the job
            if(Request.Stable.workers.length < Request.Stable.workerSize){ //generate a thread if we have space for a new one
                this.worker.slave = new this.baseClass(options);
                this.worker.working = true;
                Request.Stable.workers.push(this.worker);
                this.worker.slave.send();
            }else{ //wait
                Request.Stable.workQueue.push(options);
            }
        }
    },
    nextJob : function(){
        var job = Request.Stable.workQueue.pop();
        if(job){
            this.worker.slave = new this.baseClass(job);
            this.worker.working = false;
            this.worker.slave.send();
        } 
    },
    status : function(title){
        console.log(['S:'+title, {
            q : Request.Stable.workQueue.length,
            t : Request.Stable.workers.length,
            workers : Request.Stable.workers
        }]);
    },
    complete : function(){
        //if(!this.worker) console.log(['NW', this]);
        this.worker.working = false;
        this.nextJob();
        //this.status('complete');
    },
    wrap : function(obj, key, func){
        if(key in obj){
            var innerFunction = obj[key];
            var myFunction = func.bind(this);
            obj[key] = function(arg1, arg2){
                    myFunction(arg1, arg2);
                    innerFunction(arg1, arg2);
            }.bind(this);
        }else obj[key] = func;
        return obj;
    }
});
Request.Stable.workers = [];
Request.Stable.workQueue = [];
Request.Stable.workerSize = 6;