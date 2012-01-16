Network.OAuth = new Class({
   Extends: Network,
   initialize : function(options){
      this.parent(options);
   }, 
   require : function(callback){
      if(callback) callback();
   },
   authCredentials : function(callback){
      var site_domain = this.options.site_domain;
      var oauth = new oauthPopUp('yahoo', { path : '/signup/yahoo', 
                                             'autoOpen':true, 
                                             'directComm' : true,
                                             'domain' : site_domain,
                                             'specs' : {   
                                                 height: 700
                                             },
                                             callback: function(message){ 
                                                 var payload = {};
                                                 var keyVals = message.split(",");
                                                 if(keyVals.length > 0){
                                                     var i=0;
                                                     while(i < keyVals.length){
                                                         var parts = keyVals[i].split(":");
                                                         if(parts.length >= 2){
                                                             payload[parts[0]] = parts[1];
                                                         }
                                                         i++;
                                                     }
                                                     payload['type'] = 'yahoo';
                                                     payload['referral'] = Cookie.read('referral');
                                                     if(window.user) payload['user_id'] = window.user.user_id;                                                   
                                                     callback(payload);
                                                       
                                                 }
                                                 
                                             }
                                         }
                 );
      
   }   
});