Network.Login = new Class({
    Extends: Network,
    initialize : function(options){
      this.parent(options);
    },
    login : function(credentials, callback){
        throw('login not implemented');
    },
    identity : function(callback){
        throw('identity not implemented');
    },
    link : function(credentials, callback){
        throw('link not implemented');
    },

});