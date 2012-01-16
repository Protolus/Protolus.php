/*
---
description: Protolus RAD framework port to JS

license: MIT-style

authors:
    - Abbey Hawk Sparrow

requires:
    - core/1.2.4: '*'
    - [Midas.Smarty, Midas.INIParser, Midas.Properties]

provides: [Protolus.PageRenderer, Protolus.Panel, Protolus.Lib, Protolus.Logger, Protolus.WebApplication]
...
*/

if(!Protolus) var Protolus = {};
Protolus.Panel = new Class({
    Extends : Midas.Smarty,
    template_location : '',
    compile_location : '',
    cache_location : '',
    root_location : '',
    name : '',
    delay : 0,
    delayCount : 1,
    requiredResources : [],
    delayTimeout : 65536,
    wrapper : false,
    onDemandRender : true,
    progenitor : null,
    instanceNumber : null,
    injectID : null,
    initialize : function(name, progenitor){
        //if true, then it's a wrapper (and cannot have a progenitor)
        if(progenitor === true) this.wrapper = true;
        this.name = name;
        this.template_location = Protolus.templateLocation;
        this.parent();
        this.instanceNumber = Protolus.Panel.count(name);
        this.injectID = (this.instanceNumber)?name+'_panel_'+this.instanceNumber:name+'_panel';
        if(progenitor && progenitor !== true){
            this.template_location = progenitor.template_location;
            this.compile_location = progenitor.compile_location;
            this.cache_location = progenitor.cache_location;
            this.root_location = progenitor.root_location;
            this.progenitor = progenitor;
            this.requiredResources = progenitor.requiredResources.clone();
        }
        this.macroRegistry['panel'] = function(panelName, attributes, smartyInstance, block){
            var target = (attributes.get('target')) ? attributes.get('target') : attributes.get('name');
            var subpanel = new Protolus.Panel(attributes.get('name'), this);
            var populates = attributes.get('populate');
            if(populates && populates != '' && this){
                populates = populates.split(',');
                populates.each(function(populate){
                    populate = populate.trim();
                    var values = this.get(populate);
                    if(values && typeOf(values) == 'object'){
                        for(index in values) subpanel.assign(index, values[index]);
                    }
                }.bind(this));
            }
            var variables = attributes.get('variables');
            if(variables && variables != '' && this){
                variables = variables.split(',');
                variables.each(function(variable){
                    variable = variable.trim();
                    var value = this.get(variable);
                    if(value || value === 0) subpanel.assign(variable, value);
                }.bind(this));
            }
            subpanel.render( function(html){
                Protolus.replaceWhenAvailable(subpanel.injectID, html, panelName);
            }.bind(this));
            return '<div id="'+subpanel.injectID+'"></div>';
        }.bind(this);
        
        this.macroRegistry['ll'] = function(panelName, attributes, smartyInstance, block){
            var currentPanel = smartyInstance.name;
            var rootPanel = Protolus.currentLocation();
            if(!(attributes.get('link') || attributes.get('variable'))) throw('local link(ll) macro requires the \'link\' or \'variable\' attribute be set!');
            var target = attributes.get('link');
            var vars = attributes.get('link');
            if(!vars) vars = '';
            vars = vars.match(new RegExp("\\[([^\\]]*)?\\]"));
            //console.log(['VARS', vars]);
            if(vars && vars[1] ) {
                try{
                    var variable;
                    for(index in vars[1]){
                        variable = vars[index];
                        if(variable == '') continue;
                        target = target.replace(new RegExp("\\["+variable+"\\]", 'g'), this.get(variable));
                    }
                }catch(ex){}
            }
            if(attributes.get('variable')){
                var target = smartyInstance.get_template_vars(attributes.get('variable'));
            }
            if(!Protolus.dynamic){
                return '<a href="/'+target+'" '+(attributes.get('class')?' class="'+attributes.get('class')+'"':'')+(attributes.get('id')?' id="'+attributes.get('id')+'"':'')+'>';
            }else{
                return '<a href="#'+target+'" onclick="Protolus.render(\''+target+'\')"'+(attributes.get('class')?' class="'+attributes.get('class')+'"':'')+(attributes.get('id')?' id="'+attributes.get('id')+'"':'')+'>';
            }
        }.bind(this);
        this.macroRegistry['/ll'] = function(panelName, attributes, smartyInstance, block){
            return '</a>';
        }.bind(this);
        
        this.macroRegistry['page'] = function(panelName, attributes, smartyInstance, block){
            if(attributes.get('wrapper') && this.onDemandRender) Protolus.Page.wrapper(attributes.get('wrapper'));
            return '';
        }.bind(this);
        
        this.macroRegistry['json'] = function(panelName, attributes, smartyInstance, block){
            if(attributes.variable) return JSON.encode(smartyInstance.get_template_vars(attributes.variable));
            return '';
        }.bind(this);
        
        this.macroRegistry['require'] = function(panelName, attributes, smartyInstance, block){
            if(attributes.get('name')){
                var attrs = attributes.get('name').split(',');
                attrs.each(function(attr){
                    this.requiredResources.push(attr);
                    Protolus.requireBundle(attr);
                }.bind(this));
                return '';
            }
            return '';
        }.bind(this);
        
        this.macroRegistry['date'] = function(panelName, attributes, smartyInstance, block){
           var timestamp = attributes.get("timestamp");
           var format = attributes.get("format");
           if(!timestamp) timestamp = Math.round( (new Date()).getTime() / 1000) 
           
           if(variable = attributes.get("variable")){
              timestamp = Midas.SmartyLib.evaluateSmartyPHPHybridVariable(variable, smartyInstance);
           }
           return this.render_date(timestamp, format);
        }.bind(this);
        
        this.macroRegistry['encode'] = function(panelName, attributes, smartyInstance, block){
           //var value = attributes.get("value");
           var type = attributes.get("type");
           var target = attributes.get('value');
           var vars = attributes.get('value');
           if(!vars) return '';
           vars = vars.match(new RegExp("\\[([^\\]]*)?\\]", 'g'));
           if(vars) {
               try{
                   var val;
                   for(index in vars){
                       val = vars[index].substring(1,vars[index].length-1);
                       if(val == '') continue;
                       target = target.replace(new RegExp("\\["+val+"\\]", 'g'), this.get(val));
                   }
               }catch(ex){}
           }
           return target.entityEncode();
        }.bind(this);
        
    },
    fileType : function(){
        if(this.wrapper) return 'wrapper.tpl';
        else return 'panel.tpl';
    },
    fetchData : function(callback){
        if(Protolus.Panel.dataCache[this.name]){
            //todo: handle out-of-date data
            callback(Protolus.Panel.dataCache[this.name]);
        }else{
            (new Request.JSON({
                url : '/data/'+this.name,
                onSuccess : function(json){
                    Protolus.Panel.dataCache[this.name] = json;
                    callback(json);
                },
                onFailure :function(event){
                    console.log(['ERROR', event]);
                }
            })).send();
        }
    },
    render : function(data, callback){
        if(typeOf(data) == 'function' && !callback){
            this.fetchData(function(fetchedData){
                this.render(fetchedData, data); 
            }.bind(this));
            return;
        }
        if(data && typeOf(data) == 'object'){
            for(index in data) this.assign(index, data[index]);
        }
        var dt = data;
        var cb = callback;
        var result = this.fetch(
            this.template_location+this.name+'.'+this.fileType(),
            function(text){
                document.id(document).fireEvent('panelrender', {
                    name : this.name,
                    content : text,
                    data : dt,
                    globals : Protolus.globals
                });
                document.id(document).fireEvent(this.name+'_panelrender', {
                    name : this.name,
                    content : text,
                    data : dt,
                    globals : Protolus.globals
                });
                //callback(text);
                (function(){
                    Protolus.executeWhenResourceExists(this.requiredResources, cb, [text]);
                }.bind(this))();
            }.bind(this)
        );
        if(result == '') return '<div id="'+this.injectID+'"></div>';
        else return result;
    },
    render_date : function(timestamp, format){
       return Protolus.Panel.date(timestamp, format);
    }
});
Protolus.Panel.renderCounts = {};
Protolus.Panel.count = function(panel){
    if(!Protolus.Panel.renderCounts[panel]) Protolus.Panel.renderCounts[panel] = 0;
    return Protolus.Panel.renderCounts[panel]++;
};
Protolus.Panel.exists = function(panel, callback){
    var routedPanel = panel;
    routedPanel = Protolus.consumeGetParameters(routedPanel);
    var result = (Protolus.templateLocation+routedPanel+'.'+Protolus.panelExtension).existsAsURL();
    if(callback) callback(result);
    return result;
};
Protolus.Panel.dataCache = {};
//perhaps something that should be static
Protolus.Panel.date = function(timestamp, format){
    //todo: finish this mootools replacement
    //var result = format.replace(new RegExp('([A-Za-z])', 'g'), '%$1');
    //return (new Date(timestamp*1000)).format(result);
    var that = this,
        jsdate, f, formatChr = /\\?([a-z])/gi,
        formatChrCb,
        // Keep this here (works, but for code commented-out
        // below for file size reasons)
        //, tal= [],
        _pad = function (n, c) {
            if ((n = n + "").length < c) {
                return new Array((++c) - n.length).join("0") + n;
            } else {
                return n;
            }
        },
        txt_words = ["Sun", "Mon", "Tues", "Wednes", "Thurs", "Fri", "Satur", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
        txt_ordin = {
            1: "st",
            2: "nd",
            3: "rd",
            21: "st",
            22: "nd",
            23: "rd",
            31: "st"
        };
    formatChrCb = function (t, s) {
        return f[t] ? f[t]() : s;
    };
    f = {
        // Day
        d: function () { return _pad(f.j(), 2); },// Day of month w/leading 0; 01..31
        D: function () { return f.l().slice(0, 3); },// Shorthand day name; Mon...Sun
        j: function () { return jsdate.getDate(); },// Day of month; 1..31
        l: function () { return txt_words[f.w()] + 'day'; },// Full day name; Monday...Sunday
        N: function () { return f.w() || 7; },// ISO-8601 day of week; 1[Mon]..7[Sun]
        S: function () { return txt_ordin[f.j()] || 'th'; },// Ordinal suffix for day of month; st, nd, rd, th
        w: function () { return jsdate.getDay(); },// Day of week; 0[Sun]..6[Sat]
        z: function () { // Day of year; 0..365
            var a = new Date(f.Y(), f.n() - 1, f.j()),
                b = new Date(f.Y(), 0, 1);
            return Math.round((a - b) / 864e5) + 1;
        },

        // Week
        W: function () { // ISO-8601 week number
            var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3),
                b = new Date(a.getFullYear(), 0, 4);
            return 1 + Math.round((a - b) / 864e5 / 7);
        },

        // Month
        F: function () { return txt_words[6 + f.n()]; },// Full month name; January...December
        m: function () { return _pad(f.n(), 2); },// Month w/leading 0; 01...12
        M: function () { return f.F().slice(0, 3); },// Shorthand month name; Jan...Dec
        n: function () { return jsdate.getMonth() + 1; },// Month; 1...12
        t: function () { return (new Date(f.Y(), f.n(), 0)).getDate();},// Days in month; 28...31

        // Year
        L: function () { return new Date(f.Y(), 1, 29).getMonth() === 1 | 0; },// Is leap year?; 0 or 1
        o: function () { // ISO-8601 year
            var n = f.n(),
                W = f.W(),
                Y = f.Y();
            return Y + (n === 12 && W < 9 ? -1 : n === 1 && W > 9);
        },
        Y: function () { return jsdate.getFullYear(); },// Full year; e.g. 1980...2010
        y: function () { return (f.Y() + "").slice(-2); },// Last two digits of year; 00...99

        // Time
        a: function () { return jsdate.getHours() > 11 ? "pm" : "am"; },// am or pm
        A: function () { return f.a().toUpperCase(); },// AM or PM
        B: function () { // Swatch Internet time; 000..999
            var H = jsdate.getUTCHours() * 36e2,
                i = jsdate.getUTCMinutes() * 60,
                s = jsdate.getUTCSeconds(); // Seconds
            return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3);
        },
        g: function () { return f.G() % 12 || 12; },// 12-Hours; 1..12
        G: function () { return jsdate.getHours(); },// 24-Hours; 0..23
        h: function () { return _pad(f.g(), 2); },// 12-Hours w/leading 0; 01..12
        H: function () { return _pad(f.G(), 2); },// 24-Hours w/leading 0; 00..23
        i: function () { return _pad(jsdate.getMinutes(), 2); },// Minutes w/leading 0; 00..59
        s: function () { return _pad(jsdate.getSeconds(), 2); },// Seconds w/leading 0; 00..59
        u: function () { return _pad(jsdate.getMilliseconds() * 1000, 6); },// Microseconds; 000000-999000

        // Timezone
        e: function () { // Timezone identifier; e.g. Atlantic/Azores, ...
            // The following works, but requires inclusion of the very large
            // timezone_abbreviations_list() function.
//             return this.date_default_timezone_get();
            throw 'Not supported (see source code of date() for timezone on how to add support)';
        },
        I: function () { // DST observed?; 0 or 1
            // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
            // If they are not equal, then DST is observed.
            var a = new Date(f.Y(), 0),
                c = Date.UTC(f.Y(), 0),
                b = new Date(f.Y(), 6),
                d = Date.UTC(f.Y(), 6); // Jul 1 UTC
            return 0 + ((a - c) !== (b - d));
        },
        O: function () { var a = jsdate.getTimezoneOffset(); return (a > 0 ? "-" : "+") + _pad(Math.abs(a / 60 * 100), 4); },// Difference to GMT in hour format; e.g. +0200
        P: function () { // Difference to GMT w/colon; e.g. +02:00
            var O = f.O();
            return (O.substr(0, 3) + ":" + O.substr(3, 2));
        },
        T: function () { // Timezone abbreviation; e.g. EST, MDT, ...
            // The following works, but requires inclusion of the very
            // large timezone_abbreviations_list() function.
/*              var abbr = '', i = 0, os = 0, default = 0;
            if (!tal.length) {
                tal = that.timezone_abbreviations_list();
            }
            if (that.php_js && that.php_js.default_timezone) {
                default = that.php_js.default_timezone;
                for (abbr in tal) {
                    for (i=0; i < tal[abbr].length; i++) {
                        if (tal[abbr][i].timezone_id === default) {
                            return abbr.toUpperCase();
                        }
                    }
                }
            }
            for (abbr in tal) {
                for (i = 0; i < tal[abbr].length; i++) {
                    os = -jsdate.getTimezoneOffset() * 60;
                    if (tal[abbr][i].offset === os) {
                        return abbr.toUpperCase();
                    }
                }
            }
*/
            return 'UTC';
        },
        Z: function () { return -jsdate.getTimezoneOffset() * 60; },// Timezone offset in seconds (-43200...50400)

        // Full Date/Time
        c: function () { return 'Y-m-d\\Th:i:sP'.replace(formatChr, formatChrCb); },// ISO-8601 date.
        r: function () { return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb); },// RFC 2822
        U: function () { return jsdate.getTime() / 1000 | 0; }// Seconds since UNIX epoch
    };
    this.date = function (timestamp, format) {
       // console.log([format, timestamp]);
/*       if(!format.replace && timestamp.replace){// somehow, the args are swapped, todo: study this
        var swap = timestamp;
        timestamp = format;
        format = swap;
       }
  */    
        that = this;
        jsdate = ((typeof timestamp === 'undefined') ? new Date() : // Not provided
        (timestamp instanceof Date) ? new Date(timestamp) : // JS Date()
        new Date(timestamp * 1000) // UNIX timestamp (auto-convert to int)
        );
        if(format == null) return "";
        var val= format.replace(formatChr, formatChrCb);
        
        return val;
    };
    // console.log([format, timestamp]);
    var val = this.date(timestamp, format);
    //console.log(val);
    return val; //*/
};