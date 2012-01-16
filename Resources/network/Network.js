var Network = new Class({
    tracker: false,
    options:{
        credentials: new Array(),
        site_domain : "domain.com",
        name : "network",
    },
    initialize : function(options){
        this.options = Object.merger(options, this.options);
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
    require : function(){ },
    error : function(){ }
});

Network.networks = {};
Network.get = function(name){
    if(!name) return Object.keys(Network.networks);
    if(Network.networks[name])
};
Network.add = function(network){
    Network.networks[network.options.name] = network;
};