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
if(!NodeList.prototype.each){
    NodeList.prototype.each = function(callback){
        for(index in this){
            callback(this[index], index);
        }
    }
}

if(!NodeList.prototype.indexOf){
    NodeList.prototype.indexOf = function(item){
        var result = -1;
        for(index in this){
            if(item === this[index]) result = index;
        }
        return result;
    }
}