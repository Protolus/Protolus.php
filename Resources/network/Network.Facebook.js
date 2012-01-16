Network.Facebook = new Class({
    Extends: Network,
    options:{
       app_id : '',
       require : true,
       permissions: 'user_photos,offline_access,publish_stream',
       onLoad : function(){}
    },
    identity: false,
    initialize : function(options){
       this.parent(options);
    },
    login : function(credentials, callback){
        FB.getLoginStatus(function(response) {
            if (response.session){
                if(this.tracker) this.tracker.track(this.options.name+'_login');
                callback(response);
            }else{
                FB.login(function(response){
                    if(this.tracker) this.tracker.track(this.options.name+'_login');
                    if(response.status == 'connected') callback(response);
                }.bind(this), {
                    perms: this.options.permissions
                });
            }
        });
    },
    identity : function(callback){
        FB.getLoginStatus(function(response) {
            if (response.session){
                if(this.tracker) this.tracker.track(this.options.name+'_identity');
                callback(response);
            }else return false;
        });
    },
    link : function(credentials, callback){
        FB.login(function(response){
            if(this.tracker) this.tracker.track(this.options.name+'_link');
            if(response.status == 'connected') callback(response);
        }.bind(this), {
            perms: this.options.permissions
        });
    },
    require : function(){
        Asset.javascript( 'http://connect.facebook.net/en_US/all.js', {
            id: 'facebook_library',
            'onload': function(){
                //give facebook a place in the body to operate
                document.body.appendChild(new Element('div', {id: 'fb-root'}));
                FB.init({
                    appId  : app_id,
                    status : true,
                    cookie : true,
                    xfbml : true 
                });
                if(callback) callback();
            }.bind(this)
        });
    }
});