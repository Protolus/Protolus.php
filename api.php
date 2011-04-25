<?php
    /******************************************************
     * Protolus : Output all calls through the API layer
     ******************************************************
     * @created 09/09/09
     * @author  Abbey Hawk Sparrow
     * @name root index
     ******************************************************
     * This script provides an entry point for all URLS to
     * map to a panel, redirection, or to location failure
     * It also performs all needed App initialization
     * todo: replace this header block with YAML
     ******************************************************/

    //require our core loader
    require('./Protolus/initialize.php');
    PageRenderer::$return_mode = true;
    if(Panel::isDefined($panel)){
        PageRenderer::render($panel, $silo);
        //if(isset($silo)) $pan = new Panel($panel, $silo);
        //else $pan = new Panel($panel);
        //echo($pan->render());
    }
    ?>
    <html><head><script origin="protolus" type="text/javascript" src="/min/?b=js&amp;f=mootools-core-1.3-full-nocompat.js,mootools-more.js"></script></head><body><script>(function () {
    var INTEND = "\t";
    var NEWLINE = "\n";
    var pPr = false;
    var intendLevel = 0;
    var intend = function(a) {
        if (!pPr) return a;
        for (var l=0; l<intendLevel; l++) {
            a[a.length] = INTEND;
        }
        return a;
    };

    var newline = function(a) {
        if (pPr) a[a.length] = NEWLINE;
        return a;
    };

    var m = {
            '\b': '\\b',
            '\t': '\\t',
            '\n': '\\n',
            '\f': '\\f',
            '\r': '\\r',
            '"' : '\\"',
            '\\': '\\\\'
        },
        s = {
            array: function (x) {
                var a = ['['], b, f, i, l = x.length, v;
                a = newline(a);
                intendLevel++;
                for (i = 0; i < l; i += 1) {
                    v = x[i];
                    f = s[typeof v];
                    if (f) {
                        v = f(v);
                        if (typeof v == 'string') {
                            if (b) {
                                a[a.length] = ',';
                                a = newline(a);
                            }
                            a = intend(a);
                            a[a.length] = v;
                            b = true;
                        }
                    }
                }
                intendLevel--;
                a = newline(a);
                a = intend(a);
                a[a.length] = ']';
                return a.join('');
            },
            'boolean': function (x) {
                return String(x);
            },
            'null': function (x) {
                return "null";
            },
            number: function (x) {
                return isFinite(x) ? String(x) : 'null';
            },
            object: function (x, formatedOutput) {
                if (x) {
                    if (x instanceof Array) {
                        return s.array(x);
                    }
                    var a = ['{'], b, f, i, v;
                    a = newline(a);
                    intendLevel++;
                    for (i in x) {
                        v = x[i];
                        f = s[typeof v];
                        if (f) {
                            v = f(v);
                            if (typeof v == 'string') {
                                if (b) {
                                    a[a.length] = ',';
                                    a = newline(a);
                                }
                                a = intend(a);
                                a.push(s.string(i), ((pPr) ? ' : ' : ':'), v);
                                b = true;
                            }
                        }
                    }
                    intendLevel--;
                    a = newline(a);
                    a = intend(a);
                    a[a.length] = '}';
                    return a.join('');
                }
                return 'null';
            },
            string: function (x) {
                if (/["\\\x00-\x1f]/.test(x)) {
                    x = x.replace(/([\x00-\x1f\\"])/g, function(a, b) {
                        var c = m[b];
                        if (c) {
                            return c;
                        }
                        c = b.charCodeAt();
                        return '\\u00' +
                            Math.floor(c / 16).toString(16) +
                            (c % 16).toString(16);
                    });
                }
                return '"' + x + '"';
            }
        };

    Object.prototype.toJSONString = function (prettyPrint) {
        pPr = prettyPrint;
        return s.object(this);
    };

    Array.prototype.toJSONString = function (prettyPrint) {
        pPr = prettyPrint;
        return s.array(this);
    };
})();

String.prototype.parseJSON = function () {
    try {
        return !(/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(
                this.replace(/"(\\.|[^"\\])*"/g, ''))) &&
            eval('(' + this + ')');
    } catch (e) {
        return false;
    }
};
    </script>
    <style>
        td{
            vertical-align: top;
        }
    </style>
    <h1>Fugraph Calls</h1>
    <table>
    <tr><td>Moment of Load</td><td>Fetch Time</td><td>Mode</td><td>Info</td><td>URL</td><td>Data</td><td>Response</td></tr>
    <?php
    $mouseMini = 'onmouseover="this.setStyle(\'font-size\', \'12px\')" onmouseout="this.setStyle(\'font-size\', \'2px\')" style="font-size:2px"';
    foreach(Furgraph::$events as $index=>$event){
        unset($event['info']['url']);
        echo('<tr><td>'.number_format($event['moment']-$start_time, 3).'</td><td>'.number_format($event['time'], 3).'</td><td>'.$event['mode'].'</td><td ><pre>'.print_r($event['info'], true).'</pre></td><td><span  '.$mouseMini.' >'.$event['url'].'</span></td><td '.$mouseMini.' class="json">'.json_encode($event['data']).'</td><td '.$mouseMini.' class="json">'.$event['raw'].'</td></tr>'."\n");
    }
    ?>
    </table><script>$$('.json').each(function(element){element.innerHTML = '<pre>'+element.innerHTML.parseJSON().toJSONString(true)+'</pre>'});</script></body></html>