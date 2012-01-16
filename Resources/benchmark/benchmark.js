if(!Browser.benchmark){
    Browser.benchmark = function(){
        var results = {};
        results.core = {};
        results.start = (new Date()).getTime();
        Browser.benchmark.core(results.core);
        results.stop = (new Date()).getTime();
        results.total = results.stop = results.start;
        return results;
    };
    Browser.benchmark.core = function(results){
        var starting = (new Date()).getTime();
        var a = function(){ return true; }
        var count =0;
        while( (new Date()).getTime() < starting + 10 ){ //how many function calls can I make in 10ms?
            a();
            count++;
        }
        starting = (new Date()).getTime();
        results.callsIn10ms = count;
        var count = 0;
        while( (new Date()).getTime() < starting + 10 ){ //how many json encodings can I make in 10ms?
            JSON.encode(document);
            count++;
        }
        starting = (new Date()).getTime();
        results.encodingsIn10ms = count;
        var count = 0;
        while( (new Date()).getTime() < starting + 10 ){ //how many random assignments can I make in 10ms?
            a = Math.random();
            count++;
        }
        results.assignmentsIn10ms = count;
    };
    Browser.benchmark.document = function(results){
        
    };
    Browser.benchmark.domain = function(results){
        
    };
}