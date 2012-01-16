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
if(!String.whenIn){
    String.implement({
        whenIn : function(element, callback){
            (function(){
                return element.getElements(this.toString()).length > 0;
            }.bind(this)).whenTrue(function(){
                callback(element.getElements(this.toString())[0]);
            }.bind(this));
        }
    });
}
if(!String.whenFullIn){
    String.implement({
        whenFullIn : function(element, callback){
            (function(){
                var q = element.getElements(this.toString());
                return q.length > 0 && q[0].getChildren().length > 0;
            }.bind(this)).whenTrue(function(){
                callback(element.getElements(this.toString())[0]);
            }.bind(this));
        }
    });
}
if(!String.toDOM){
    String.implement({
        toDOM: function(mode) {
            if(!mode) mode = 'sax';
            var result = false;
            switch(mode){
                case 'sax':
                    //we're going to parse the HTML and build our own DOM off the page
                    var pageParser = new XHTMLParser();
                    return pageParser.parse(this);
                    break;
                case 'iframe':
                    var myIFrame = new IFrame({
                        src: 'about:blank',
                        id: 'dummy_iframe',
                    });
                    myIFrame.inject(document.body);
                    myIFrame.set('html', this);
                    result = myIFrame.clone();
                    myIFrame.destroy();
                    break;
                case 'div':
                    var injector = new Element('div', {
                        'html': this,
                        'styles': {
                            'position': 'absolute',
                            'left': -1000000
                        }
                    }).inject(document.body);
                    result = injector.getChildren();
                    injector.destroy();
                    break;
            }
            return result;
        }
    });
}
if(!String.startsWith){
    String.implement({
        startsWith : function(text) {
            return this.indexOf(text) == 0;
        }
    });
}

if(!String.endsWith){
    String.implement({
        endsWith : function(text) {
            return this.substr(text.length * -1) === text;
        }
    });
}

if(!String.reverse){
    String.implement({
        reverse : function(){
            splitext = this.split("");
            revertext = splitext.reverse();
            reversed = revertext.join("");
            return reversed;
        }
    });
}

if(!String.splitHonoringQuotes){
    String.implement({
        splitHonoringQuotes: function(delimiter, quotes) {
            if(quotes == undefined) quotes = ['\'', '"'];
            var results = [''];
            var inQuote = false;
            var quote = null;
            for(var lcv=0; lcv < this.length; lcv++){
                if(inQuote){
                    if(this[lcv] == quote){
                        inQuote = false;
                        //results[results.length-1] += this[lcv];
                        //results[results.length] = '';
                    }else{
                        results[results.length-1] += this.charAt(lcv);
                    }
                }else{
                    if(quotes.contains(this[lcv])){
                        quote = this[lcv];
                        //results[results.length-1] += this[lcv];
                        inQuote = true;
                    }else if(this[lcv] == delimiter){
                        results[results.length] = '';
                    }else{
                        results[results.length-1] += this.charAt(lcv);
                    }
                }
            }
            return results;
        }
    });
}

if(!String.distance){
    String.implement({
        distance : function(s2){
            // levenshtein code by: Carlos R. L. Rodrigues, Onno Marsman, Andrea Giammarchi, Brett Zamir, Alexander M Beedie, Kevin van Zonneveld
            if (this == s2)  return 0;
            var s1_len = this.length;
            var s2_len = s2.length;
            if (s1_len === 0) return s2_len;
            if (s2_len === 0) return s1_len;
            var split = false;                                       // IE hack
            try { split = !('0')[0]; } catch (e) { split = true; }   // IE hack
            var s1;
            if (split) { 
                s1 = this.split('');
                s2 = s2.split('');
            }else{
                s1 = this;
            }
            var v0 = new Array(s1_len + 1);
            var v1 = new Array(s1_len + 1);
            var s1_idx = 0,
                s2_idx = 0,
                cost = 0;
            for (s1_idx = 0; s1_idx < s1_len + 1; s1_idx++) {
                v0[s1_idx] = s1_idx;
            }
            var char_s1 = '',
                char_s2 = '';
            for (s2_idx = 1; s2_idx <= s2_len; s2_idx++) {
                v1[0] = s2_idx;
                char_s2 = s2[s2_idx - 1];
                for (s1_idx = 0; s1_idx < s1_len; s1_idx++) {
                    char_s1 = s1[s1_idx];
                    cost = (char_s1 == char_s2) ? 0 : 1;
                    var m_min = v0[s1_idx + 1] + 1;
                    var b = v1[s1_idx] + 1;
                    var c = v0[s1_idx] + cost;
                    if (b < m_min) m_min = b;
                    if (c < m_min) m_min = c;
                    v1[s1_idx + 1] = m_min;
                }
                var v_tmp = v0;
                v0 = v1;
                v1 = v_tmp;
            }
            return v0[s1_len];
        }
    });
}

if(!String.existsAsURL){
    if(!String.urlExistences) String.urlExistences = [];
    String.implement({
        existsAsURL : function(callback){
            if(String.urlExistences[this] === true || String.urlExistences[this] === false) return String.urlExistences[this]; //buffer
            if(callback){
                throw('callback not yet supported!');
                //todo: support callback
            }else{
                try{
                    var req = document.window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
                    if (!req) throw new Error('XMLHttpRequest not supported');
                    req.open('HEAD', this, false);
                    req.send(null);
                    if (req.status == 200){
                        String.urlExistences[this] = true;
                        return true;
                    }else{
                        String.urlExistences[this] = false;
                        return false;
                    }
                }catch(ex){
                    return false;
                }
            }
        }
    });
}

if(!String.whenInDOM){ // try to execute the function using the string as an id
    if(!String.defaultReplacementTimeout) String.defaultReplacementTimeout = 16384;
    String.implement({
        whenInDOM : function(callback, delayCallback, timeoutCallback, timeout, counter){
            if(!timeout) timeout = String.defaultReplacementTimeout;
            if(!counter) counter = 0;
            if(!timeoutCallback) timeoutCallback = function(event){
                throw('Element did not appear in DOM(\''+event.id+'\') after '+event.time+'ms');
            };
            if(!document.id(this)){
                //we want to get the DOM node when it appears so we're looking for a gradient falloff of intervals as it approaches timeout
                //if(!String.DOMQueryCounts[this]) String.DOMQueryCounts[this] = 0;
                var delayTime = Math.pow(2, counter);
                if(delayTime >= timeout){
                    timeoutCallback({
                        id : this,
                        count : counter, 
                        time : delayTime
                    });
                }
                counter++;
                this.whenInDOM.delay(delayTime, this, [callback, delayCallback, timeoutCallback, timeout, counter]);
                if(delayCallback) delayCallback({
                    count : counter, 
                    time : delayTime
                });
            }else{
                callback(document.id(this));
            }
        }
    });
}

if(!String.regexEncode){
    String.regexChars = ['\\', '&','^', '$', '*', '+', '?', '.', '(', ')', '|', '{', '}', '[', ']'];
    String.regexEncodeRegexObject = new RegExp('([\\'+String.regexChars.join('|\\')+'])', 'g');
    String.implement({
        regexEncode : function(){
            return this.replace(String.regexEncodeRegexObject, '\\$1');
        }
    });
}

if(!String.nl2br){
    String.implement({
        nl2br : function(){
            return this.replace(/\n/mg, '<br/>');
        }
    });
}

if( (!String.entityEncode) && (!String.entityDecode) ){
    String.entities = {};
    String.entities.byCode = { 38: '&amp;', 60: '&lt;', 62: '&gt;', 160: '&nbsp;', 161: '&iexcl;', 162: '&cent;', 163: '&pound;', 164: '&curren;', 165: '&yen;', 166: '&brvbar;', 167: '&sect;', 168: '&uml;', 169: '&copy;', 170: '&ordf;', 171: '&laquo;', 172: '&not;', 173: '&shy;', 174: '&reg;', 175: '&macr;', 176: '&deg;', 177: '&plusmn;', 178: '&sup2;', 179: '&sup3;', 180: '&acute;', 181: '&micro;', 182: '&para;', 183: '&middot;', 184: '&cedil;', 185: '&sup1;', 186: '&ordm;', 187: '&raquo;', 188: '&frac14;', 189: '&frac12;', 190: '&frac34;', 191: '&iquest;', 192: '&Agrave;', 193: '&Aacute;', 194: '&Acirc;', 195: '&Atilde;', 196: '&Auml;', 197: '&Aring;', 198: '&AElig;', 199: '&Ccedil;', 200: '&Egrave;', 201: '&Eacute;', 202: '&Ecirc;', 203: '&Euml;', 204: '&Igrave;', 205: '&Iacute;', 206: '&Icirc;', 207: '&Iuml;', 208: '&ETH;', 209: '&Ntilde;', 210: '&Ograve;', 211: '&Oacute;', 212: '&Ocirc;', 213: '&Otilde;', 214: '&Ouml;', 215: '&times;', 216: '&Oslash;', 217: '&Ugrave;', 218: '&Uacute;', 219: '&Ucirc;', 220: '&Uuml;', 221: '&Yacute;', 222: '&THORN;', 223: '&szlig;', 224: '&agrave;', 225: '&aacute;', 226: '&acirc;', 227: '&atilde;', 228: '&auml;', 229: '&aring;', 230: '&aelig;', 231: '&ccedil;', 232: '&egrave;', 233: '&eacute;', 234: '&ecirc;', 235: '&euml;', 236: '&igrave;', 237: '&iacute;', 238: '&icirc;', 239: '&iuml;', 240: '&eth;', 241: '&ntilde;', 242: '&ograve;', 243: '&oacute;', 244: '&ocirc;', 245: '&otilde;', 246: '&ouml;', 247: '&divide;', 248: '&oslash;', 249: '&ugrave;', 250: '&uacute;', 251: '&ucirc;', 252: '&uuml;', 253: '&yacute;', 254: '&thorn;', 255: '&yuml;', 264: '&#264;', 265: '&#265;', 338: '&OElig;', 339: '&oelig;', 352: '&Scaron;', 353: '&scaron;', 372: '&#372;', 373: '&#373;', 374: '&#374;', 375: '&#375;', 376: '&Yuml;', 402: '&fnof;', 710: '&circ;', 732: '&tilde;', 913: '&Alpha;', 914: '&Beta;', 915: '&Gamma;', 916: '&Delta;', 917: '&Epsilon;', 918: '&Zeta;', 919: '&Eta;', 920: '&Theta;', 921: '&Iota;', 922: '&Kappa;', 923: '&Lambda;', 924: '&Mu;', 925: '&Nu;', 926: '&Xi;', 927: '&Omicron;', 928: '&Pi;', 929: '&Rho;', 931: '&Sigma;', 932: '&Tau;', 933: '&Upsilon;', 934: '&Phi;', 935: '&Chi;', 936: '&Psi;', 937: '&Omega;', 945: '&alpha;', 946: '&beta;', 947: '&gamma;', 948: '&delta;', 949: '&epsilon;', 950: '&zeta;', 951: '&eta;', 952: '&theta;', 953: '&iota;', 954: '&kappa;', 955: '&lambda;', 956: '&mu;', 957: '&nu;', 958: '&xi;', 959: '&omicron;', 960: '&pi;', 961: '&rho;', 962: '&sigmaf;', 963: '&sigma;', 964: '&tau;', 965: '&upsilon;', 966: '&phi;', 967: '&chi;', 968: '&psi;', 969: '&omega;', 977: '&thetasym;', 978: '&upsih;', 982: '&piv;', 8194: '&ensp;', 8195: '&emsp;', 8201: '&thinsp;', 8204: '&zwnj;', 8205: '&zwj;', 8206: '&lrm;', 8207: '&rlm;', 8211: '&ndash;', 8212: '&mdash;', 8216: '&lsquo;', 8217: '&rsquo;', 8218: '&sbquo;', 8220: '&ldquo;', 8221: '&rdquo;', 8222: '&bdquo;', 8224: '&dagger;', 8225: '&Dagger;', 8226: '&bull;', 8230: '&hellip;', 8240: '&permil;', 8242: '&prime;', 8243: '&Prime;', 8249: '&lsaquo;', 8250: '&rsaquo;', 8254: '&oline;', 8260: '&frasl;', 8364: '&euro;', 8472: '&weierp;', 8465: '&image;', 8476: '&real;', 8482: '&trade;', 8501: '&alefsym;', 8592: '&larr;', 8593: '&uarr;', 8594: '&rarr;', 8595: '&darr;', 8596: '&harr;', 8629: '&crarr;', 8656: '&lArr;', 8657: '&uArr;', 8658: '&rArr;', 8659: '&dArr;', 8660: '&hArr;', 8704: '&forall;', 8706: '&part;', 8707: '&exist;', 8709: '&empty;', 8711: '&nabla;', 8712: '&isin;', 8713: '&notin;', 8715: '&ni;', 8719: '&prod;', 8721: '&sum;', 8722: '&minus;', 8727: '&lowast;', 8729: '&#8729;', 8730: '&radic;', 8733: '&prop;', 8734: '&infin;', 8736: '&ang;', 8743: '&and;', 8744: '&or;', 8745: '&cap;', 8746: '&cup;', 8747: '&int;', 8756: '&there4;', 8764: '&sim;', 8773: '&cong;', 8776: '&asymp;', 8800: '&ne;', 8801: '&equiv;', 8804: '&le;', 8805: '&ge;', 8834: '&sub;', 8835: '&sup;', 8836: '&nsub;', 8838: '&sube;', 8839: '&supe;', 8853: '&oplus;', 8855: '&otimes;', 8869: '&perp;', 8901: '&sdot;', 8968: '&lceil;', 8969: '&rceil;', 8970: '&lfloor;', 8971: '&rfloor;', 9001: '&lang;', 9002: '&rang;', 9642: '&#9642;', 9643: '&#9643;', 9674: '&loz;', 9702: '&#9702;', 9824: '&spades;', 9827: '&clubs;', 9829: '&hearts;', 9830: '&diams;' };
    String.entities.byName = {};
    Object.each(String.entities.byCode, function(entity, code){ String.entities.byName[entity] = code; });
    var charSelectorString = '('+Object.keys(String.entities.byCode).map( function(code){ return String.fromCharCode(code).regexEncode() }, this).join('|')+')';
    String.entities.charSelector = new RegExp( charSelectorString, 'g' );
    var entitySelectorString = '('+Object.keys(String.entities.byName).map(function(entity){ return entity.regexEncode() }, this).join('|')+')';
    String.entities.entitySelector = new RegExp( entitySelectorString, 'gi');
    String.implement({
        entityEncode : function(){
            return this.replace(
                String.entities.charSelector,
                function(str, chr) {
                    return String.entities.byCode[chr.charCodeAt()];
                }
            );
        },
        entityDecode : function(){
            return this.replace(
                String.entities.entitySelector,
                function(str, entity) {
                    return String.fromCharCode(String.entities.byName[entity]);
                }
            );
        }
    });
}