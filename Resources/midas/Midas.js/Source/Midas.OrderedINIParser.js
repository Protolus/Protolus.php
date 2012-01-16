if(!Midas) var Midas = {};
Midas.OrderedINIParser = new Class({
    parse: function(text, order){
        var inQuote = false;
        var inComment = false;
        var isAssigning = false;
        var inGrouping = false;
        var buffer = '';
        var label = '';
        var group = null;
        var currentQuoteType = '';
        var results = [];
        var ch;
        for(var lcv=0; lcv < text.length; lcv++){
            ch = text[lcv];
            if(buffer == '' && ch == '['){
                inGrouping = true;
            }
            if(inGrouping){
                if(ch == ']'){
                    group = buffer;
                    buffer = '';
                    inGrouping = false;
                }else if(ch != '[') buffer += ch;
                continue;
            }
            if(inComment){
                if(ch == "\n"){
                    inComment = false;
                    if(isAssigning){
                        if(group == null){
                            if(label != '') results[label] = buffer;
                        }else{
                            if(!results[group]) results[group] = {};
                            if(label != '') results[group][label] = buffer;
                        }
                        label = buffer = '';
                    }
                }else continue;
            }
            if(!inQuote && !inComment){ //we're reading chars
                if(ch == ';'){
                    inComment = true;
                    continue;
                }
                if(ch == '\'' || ch == '"'){
                    inQuote = true;
                    currentQuoteType = ch;
                    continue;
                }
                if(!isAssigning && ch == '='){
                    label = buffer;
                    buffer = '';
                    isAssigning = true;
                }else{
                    if(ch == "\n"){
                        isAssigning = false;
                        if(group == null){
                            if(label != '') results[results.length] = {key:label,value:buffer};
                        }else{
                            if(results.length == 0 || (results[results.length-1].key != group)) results[results.length] = {key:group,value:[]};
                            if(label != '') results[results.length-1]['value'][results[results.length-1]['value'].length] = {key:label, value:buffer};
                        }
                        label = buffer = '';
                    }else{
                        if(ch != ' ') buffer += ch;
                    }
                }
            }else{
                if(inQuote){ // keep reading until we see our quote end
                    if(ch == currentQuoteType){
                        inQuote = false;
                    }else{
                        buffer += ch;
                    }
                }
            }
        }
        if(group == null){
            if(label != ''){
                results[results.length] = {key:label,value:buffer};
            }
        }else{
            if(results.length == 0 || (results[results.length-1].key != group)) results[results.length] = {key:group,value:[]};
            if(label != '') results[results.length-1]['value'][results[results.length-1]['value'].length] = {key:label, value:buffer};
        }
        return results;
    }
});