Network.OAuth.Google = new Class({
    Implements: Options,
    initialize : function(options){
        this.setOptions(options);
        if(this.options.onLoad){
            this.options.onLoad();
        }
    }, 
    require : function(callback){
        if(callback) callback();
    },
    authCredentials : function(callback){
        var site_domain = this.options.site_domain;
        var oauth = new oauthPopUp('google', { 
            path : '/signup/google', 
            'autoOpen':true, 
            'directComm' : true,
            'domain' : site_domain,
            'specs' : {   
            height: 700
            },
            callback: function(message){ 
                var payload = message.split(",");
                var i=0;
                var arr = new Array();
                var res = new Array();
                while(i<payload.length){
                    var v = i + 1;
                    arr[arr.length] = payload[i];
                    if(v % 3 == 0 && v !=0){
                        res[res.length] = arr;
                        arr = new Array();
                    }
                    i++;
                }
                callback(res);
            }
        });
    }   
});