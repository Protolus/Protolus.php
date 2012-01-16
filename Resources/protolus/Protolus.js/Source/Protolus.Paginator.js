/*
---
description: Protolus RAD framework port to JS

license: MIT-style

authors:
    - Abbey Hawk Sparrow

requires:
    - core/1.2.4: '*'
    - [Protolus, Protolus.Panel]

provides: [Protolus.Paginator, Protolus.InfiniteScroll, Protolus.InfiniteAlbum]
...
*/
Protolus.Paginator = new Class({
    options: {
        'footerIdentifier' : 'ol.paginator',
        'nodeIdentifier' : 'li',
        'template' : 'feed_item',
        controlsIdentifier: null,
        page : 1,
        startPage : 1,
        woopra : true,
        itemsPerPage : 20,
        pagePrefix : '',
        index:'index'
    },
    totalPages : 0,
    count : 0,
    totalCount:0,
    showFooter : true,
    checking : false,
    scrollContainer: null,
    footer: null,
    preloaded: false,
    insideLoad: false,
    offset:0,
    nodes: [],
    initialize : function(scrollContainer, options){
        try{
            
            this.scrollContainer = document.id(scrollContainer);
            if(!this.scrollContainer) throw('container '+ scrollContainer+ ' not found');
            for(key in options) this.options[key] = options[key];
            if(!this.options.itemCount) throw('Paginator requires you know the total number of items');
            this.totalPages = Math.floor(this.options.itemCount / this.options.itemsPerPage);
            if(this.options.itemCount % this.options.itemsPerPage != 0) this.totalPages++; //the optional partial last page
            if(this.options.controlsIdentifier) this.footer = document.id(this.options.controlsIdentifier);
            this.removeOldFooter();
            if(this.showFooter) this.renderFooter();
        }catch(ex){
            throw('Paginator Error: '+ex);
        }
        this.offset = this.options.itemsPerPage;
    },
    calcPages: function(){
        if(this.options.itemCount && typeOf(this.options.itemCount) != 'string'){
            this.totalPages = Math.floor(this.options.itemCount / this.options.itemsPerPage);
            if(this.options.itemCount % this.options.itemsPerPage != 0) this.totalPages++; //the optional partial last page
        }
    },
    lastPage: function(){
        return this.totalPages;
    },
    makeNode:function(identifier, text, link, classes){
        var result;
        if(identifier.contains('.')){
            var parts = identifier.split('.');
            if(parts[0] && parts[1]){
                result = new Element(parts[0], { 
                    'class' : parts[1],
                    html : text
                });
            }
        }else if(identifier.contains('#')){
            var parts = identifier.split('#');
            if(parts[0] && parts[1]){
                result = new Element(parts[0], {
                    id : parts[1],
                    html : text
                });
            }
         }else{
            result = new Element(identifier, {
                html : text
            });
         }
         if(!result) return false;
         if(link){
            var linkElement = new Element('a', {
                href : link,
                html : result.innerHTML
             });
             result.innerHTML = '';
             result.adopt(linkElement);
         }
         if(classes) classes.each(function(theClass){
            result.addClass(theClass);
         });
         return result;
    },
    trigger:function(){

        //console.log('paginate triggered');
        if(this.preloaded && this.preloaded !== true){
            this.expand(this.preloaded);
        }else{
            if(this.insideLoad) return;
            ///where we do preload
            if(this.options.onPreLoad) this.options.onPreLoad();
            this.options.page++;
            this.move(this.options.page);
            /*this.getPageData(this.options.page, function(data, globals){
                //console.log(['page '+this.options.page+' data recieved', data]);
                if(this.options.payload){
                    var st = 'data = data.'+this.options.payload+';';
                    eval(st);
                }
                if(data.length > 0){
                    this.expand(data, globals);
                }else throw('No data returned!');
            }.bind(this));*/
        }
        this.preloaded = false;
    },
    move:function(location){
        this.getPageData(location, function(data, globals){
            var orig = data;
            if(this.options.payload){
                var st = 'data = data.'+this.options.payload+';';
                eval(st);
            }

            if(( typeOf(data) == 'array' && data.length > 0 ) || //it's an empty array
(!this.options.payload && typeOf(data) == 'object' && Object.getLength(data) > 0) //it's an empty object and we aren't in payload mode
            ){
                if(typeOf(data) == 'array'){
                    //if(window.console && window.console.log) console.log(['data:'+this.options.template, Object.clone(data), orig]);
                    this.expand(data, globals);
                }else{
                
                    var panel = new Protolus.Panel(this.options.template);
                    if(globals) for(global in globals) data[global] = globals[global];
                    panel.render(data, function(html){
                        //this.scrollContainer.set('html', html);
                        //if(window.console && window.console.log) console.log(['render', Object.clone(data), html]);
                        this.scrollContainer = document.id(this.scrollContainer.transitionIn(html));
                    }.bind(this));
                }
            }else{
                this.stop();
                //if(window.console && window.console.log) console.log(['no render:'+this.options.template, Object.clone(data), orig]);
            }
            
            if(this.options.onLoad) this.options.onLoad();
        }.bind(this));
    },
    preload:function(){
        //todo: implement multi-load preload
        this.options.page++;
        this.insideLoad = true;
        this.preloaded = true;
        this.getPageData(this.page, function(data, globals){
            if(data.length > 0){
                if(this.preloaded) this.preloaded = data;
                else this.expand(data, globals);
                this.insideLoad = false;
            }else throw('No data returned!');
        }.bind(this));
    },
    getPageData:function(number, callback){
        throw('No data to fetch!');
    },
    checkComplete:function(){
        if(this.count == 0){
            this.removeOldFooter();
            this.nodes.each(function(node){
                this.scrollContainer.adopt(node);
            }.bind(this));
            this.nodes = [];
            this.complete(this.options.page);
            if(this.totalPages == this.options.page){
                this.finished();
            }else{
                if(this.showFooter) this.renderFooter();
            }
            
        }else this.checkComplete.delay(100, this);
    },
    complete:function(){
    },
    finished:function(){
    },
    expand: function(items, globals){
        //console.log('expanding paginate data');
        var panel;
        var scroller = this.scrollContainer;
        this.totalCount=0;
        items.each(function(item, position){
        //var position = 0;
        //var item = items[position];
            this.count++;
            panel = new Protolus.Panel(this.options.template);
            if(!item[this.options.index]){
                item[this.options.index] = parseInt(this.offset) + parseInt(this.totalCount);
            }
            this.totalCount++;
            if(globals) for(global in globals) item[global] = globals[global];
            panel.render(item, function(html){
                this.nodes[position] = (new Element('div', {html : html })).getChildren();
                this.count--;
            }.bind(this));
        }.bind(this));
        this.checkComplete();
    },
    removeOldFooter:function(){
        if(this.footer) this.footer.dispose();
    },
    renderFooter:function(){
        this.footer = this.makeNode(this.options.footerIdentifier);
        var items = this.options.itemCount;
        var page = 1;
        //todo: compute the baseURL
        var baseURL = '/feed'+this.options.pagePrefix;
        var pages = this.options.itemCount / this.options.itemsPerPage;
        if(this.options.itemCount % this.options.itemsPerPage != 0) pages++; //the optional partial last page
        this.footer.adopt(this.makeNode(
            this.options.nodeIdentifier,
            'first',
            baseURL+'/1', 
            ['terminal', 'first']
        ));
        this.footer.adopt(this.makeNode(
            this.options.nodeIdentifier,
            'previous',
            baseURL+'/'+(this.page-1), 
            ['step', 'previous']
        ));
        while(items > 0){
            var current = this.makeNode(
                this.options.nodeIdentifier,
                ''+page,
                baseURL+'/'+page
            );
            if(page > this.startPage && page <= this.page) current.addClass('current');
            this.footer.adopt(current);
            page++;
            items -= this.options.itemsPerPage;
        }
        this.footer.adopt(this.makeNode(
            this.options.nodeIdentifier,
            'next',
            baseURL+'/'+(this.page+1), 
            ['step', 'next']
        ));
        this.footer.adopt(this.makeNode(
            this.options.nodeIdentifier,
            'last',
            baseURL+'/'+pages, 
            ['terminal', 'last']
        ));
        this.scrollContainer.adopt(this.footer);
    }
});

/*
    new Protolus.InfiniteAlbum('container_id', {
        nextButton : 'next_button_id',
        previousButton : 'last_button_id',
        itemCount : 5,
        template: 'widgets/album',
        dataURL: '/data/widgets/album/'
    });
    
*/
Protolus.InfiniteAlbum = new Class({
    Extends : Protolus.Paginator,
    dataURL : '',
    fetching : false,
    dimensions : 1,
    container : null,
    position : '1',
    nextButton : null,
    previousButton : null,
    loaded : true,
    stateTransitionFunctions: {},
    initialize : function(element, options){
        this.showFooter = false;
        this.parent(element, options);
        if(options.dataURL) this.dataURL = options.dataURL;
        if(!this.options.nextButton) this.options.nextButton = 'infinite_pager_next_control';
        if(!this.options.previousButton) this.options.previousButton = 'infinite_pager_previous_control';
        this.nextButton = document.id(this.options.nextButton);
        this.previousButton = document.id(this.options.previousButton);
        this.stateTransitionFunctions['next'] = function(event){
            if(!this.working){
                this.working = true;
                this.next();
            }
        }.bind(this);
        this.stateTransitionFunctions['previous'] = function(event){
            if(!this.working){
                this.working = true;
                this.previous();
            }
        }.bind(this);
        if(this.nextButton) this.nextButton.addEvent('click', this.stateTransitionFunctions['next']);
        if(this.previousButton) this.previousButton.addEvent('click', this.stateTransitionFunctions['previous']);
        this.handleExtents();
        if(this.options.autoLoad){
            this.loaded = false;
            this.move(this.position);
            if(this.options.onLoad) this.options.onLoad()
        }
    },
    getPageData : function(number, callback){
        var pageURL = this.dataURL+this.position;
        var submission = new Request.Stable({
            url : pageURL,
            onSuccess : function(json, text) {
                if(typeOf(this.options.itemCount) == 'string'){
                    this.options.itemCount = json[this.options.itemCount];
                    this.calcPages();
                    this.handleExtents();
                    if(this.options.itemCount == 0){
                        if(this.nextButton) this.nextButton.addClass('invisible');
                        if(this.previousButton) this.previousButton.addClass('invisible');
                        if(this.options.removeWhenEmpty){
                            var ids = this.options.removeWhenEmpty.split(',');
                            ids.each(function(id){
                                if(document.id(id))document.id(id).addClass('invisible');
                            });
                        }
                    }else{
                        if(this.nextButton && this.nextButton.hasClass('invisible')) this.nextButton.removeClass('invisible');
                        if(this.previousButton && this.previousButton.hasClass('invisible')) this.previousButton.removeClass('invisible');
                        
                        if(this.options.removeWhenEmpty){
                            var ids = this.options.removeWhenEmpty.split(',');
                            ids.each(function(id){
                                if(document.id(id) && document.id(id).hasClass('invisible')) document.id(id).removeClass('invisible');
                            });
                        }
                    }
                }
                if(!this.loaded || this.options.itemCount > this.options.itemsPerPage){
                    callback(json); //if we only have one state we never change states
                    this.loaded = true;
                }
                this.fetching = false;
                this.working = false;
                if(window.woopraTracker && this.options.woopra) window.woopraTracker.pushEvent({ name:'pageturn', page: this.position.toString() });
            }.bind(this)
        }, Request.JSON)
        /*var submission = new Request.JSON({
            url : pageURL,
            onSuccess : function(json, text) {
                if(typeOf(this.options.itemCount) == 'string'){
                    this.options.itemCount = json[this.options.itemCount];
                    this.calcPages();
                    this.handleExtents();
                    if(this.options.itemCount == 0){
                        if(this.nextButton) this.nextButton.addClass('invisible');
                        if(this.previousButton) this.previousButton.addClass('invisible');
                        if(this.options.removeWhenEmpty){
                            var ids = this.options.removeWhenEmpty.split(',');
                            ids.each(function(id){
                                if(document.id(id))document.id(id).addClass('invisible');
                            });
                        }
                    }else{
                        if(this.nextButton && this.nextButton.hasClass('invisible')) this.nextButton.removeClass('invisible');
                        if(this.previousButton && this.previousButton.hasClass('invisible')) this.previousButton.removeClass('invisible');
                        
                        if(this.options.removeWhenEmpty){
                            var ids = this.options.removeWhenEmpty.split(',');
                            ids.each(function(id){
                                if(document.id(id) && document.id(id).hasClass('invisible')) document.id(id).removeClass('invisible');
                            });
                        }
                    }
                }
                if(!this.loaded || this.options.itemCount > this.options.itemsPerPage){
                    callback(json); //if we only have one state we never change states
                    this.loaded = true;
                }
                this.fetching = false;
                this.working = false;
                if(window.woopraTracker && this.options.woopra) window.woopraTracker.pushEvent({ name:'pageturn', page: this.position.toString() });
            }.bind(this)
        }).send();*/
    },
    complete:function(pageNumber){
        if(this.options.onpage) this.options.onpage(pageNumber);
        document.id(document).fireEvent('paginate', { page : pageNumber });
    },
    next :function(){
        this.alterPosition(0, 1);
    },
    previous : function(){
        this.alterPosition(0, -1);
    },
    ascend :function(){
        this.alterPosition(1, 1);
    },
    descend :function(){
        this.alterPosition(1, -1);
    },
    transition :function(){
        
    },
    alterPosition :function(position, alteration){
        var positions = this.position.split('-');
        for(var lcv=0; lcv < this.dimensions ; lcv++) if(!positions[lcv]) positions[lcv] = '0';
        positions[0] = Math.max(0, Number.from(positions[0]) + alteration);
        this.position = positions.join('-');
        this.move(this.position);
        this.handleExtents();
    },
    handleExtents : function(){
        if(this.nextButton){
            if(this.totalPages && this.totalPages == this.position){
                this.nextButton.removeEvent('click', this.stateTransitionFunctions['next']);
                this.nextButton.addClass('deactivated');
            }else{
                if(this.nextButton.hasClass('deactivated')){
                    this.nextButton.removeClass('deactivated');
                    this.nextButton.addEvent('click', this.stateTransitionFunctions['next']);
                }
            }
        }
        if(this.previousButton){
            if(this.position == '1'){
                this.previousButton.removeEvent('click', this.stateTransitionFunctions['previous']);
                this.previousButton.addClass('deactivated');
            }else{
                if(this.previousButton.hasClass('deactivated')){
                    this.previousButton.removeClass('deactivated');
                    this.previousButton.addEvent('click', this.stateTransitionFunctions['previous']);
                }
            }
        }
    },
    finished:function(){
        
        if(this.options.onend) this.options.onend();
    }
});

/*
    new Protolus.InfiniteScroll('container_element_id', {
        itemCount : count,
        dataURL : '/data/'+panel,
        partialAmount : 1,
        template : 'browse_person',
        controlsIdentifier : 'feed_pagination_controls' // <-- this is not an optional part of the paginator, It can be made to error if this isn't hooked up and we can't police ourselves
    });
    
*/

Protolus.InfiniteScroll = new Class({
    Extends : Protolus.Paginator,
    dataURL : '',
    fetching : false,
    scrollFunction : null,
    scrollTarget : window,
    initialize : function(element, options){
        this.showFooter = false;
        // options: itemCount, template, controlsIdentifier
        this.parent(element, options);
        if(options.dataURL) this.dataURL = options.dataURL;
        if(this.options.scrollTarget) this.scrollTarget = document.id(this.options.scrollTarget);
        if(!this.options.itemCount) this.options.itemCount = 0;
        if(!this.options.threshold) this.options.threshold = 0.75;
        if(this.options.threshold.isFloat()){
            this.scrollFunction = function(){
                if(!this.fetching && (this.scrollTarget.getScroll().y/ this.scrollTarget.getScrollSize().y) > this.options.threshold ){
                    this.fetching = true;
                    this.trigger();
                }
            }.bind(this);
        }else{
            this.scrollFunction = function(){
                if(
                    !this.fetching && 
                    (this.scrollTarget.getScrollSize().y < (this.options.threshold + this.scrollTarget.getScroll().y) )
                ){
                    this.fetching = true;
                    this.trigger();
                }
            }.bind(this);
        }
        this.scrollTarget.addEvent('domready', function(){
            this.start();
        }.bind(this));
    },
    getPageData : function(number, callback){
        var pageURL = this.dataURL+this.options.page;
        var submission = new Request.JSON({
            url : pageURL,
            onSuccess : function(json, text) {
                callback(json);
                this.fetching = false;
                if(window.woopraTracker && this.options.woopra) window.woopraTracker.pushEvent({ name:'scroll', page: this.options.page.toString() });
            }.bind(this)
        }).send();
    },
    complete:function(pageNumber){
        if(this.options.onpage) this.options.onpage(pageNumber);
        document.id(document).fireEvent('paginate', { page : pageNumber });
    },
    stop :function(){
        this.scrollTarget.removeEvent('scroll', this.scrollFunction);
    },
    start :function(){
        this.scrollTarget.addEvent('scroll', this.scrollFunction);
    },
    finished:function(){
        if(this.options.onend) this.options.onend();
    }
});

Protolus.InfiniteMultiScroll = new Class({
    Extends : Protolus.Paginator,
    dataURL : '',
    fetching : false,
    scrollFunction : null,
    targets : null,
    targetPosition : 0,
    initialize : function(elements, options){
        // options: itemCount, template, controlsIdentifier
        if(typeOf(elements) == 'string'){
            elements = elements.split(',');
        }
        if(typeOf(elements) != 'array') throw('MultiScroll requires an array of elements to target');
        this.parent(elements[0], options);
        elements.each(function(element, index){
            if(typeOf(element) == 'string'){
                elements[index] = document.id(element);
            }
        });
        this.targets = elements;
        if(options.dataURL) this.dataURL = options.dataURL;
        if(!this.options.itemCount) this.options.itemCount = 0;
        if(!this.options.threshold) this.options.threshold = 0.75;
        if(this.options.threshold.isFloat()){
            this.scrollFunction = function(){
                if(!this.fetching && (window.getScroll().y/ window.getScrollSize().y) > this.options.threshold ){
                    this.fetching = true;
                    this.trigger();
                }
            }.bind(this);
        }else{
            this.scrollFunction = function(){
                if(
                    !this.fetching && 
                    (window.getScrollSize().y < (this.options.threshold + window.getScroll().y) )
                ){
                    this.fetching = true;
                    this.trigger();
                }
            }.bind(this);
        }
        window.addEvent('domready', function(){
            this.start();
        }.bind(this));
    },
    checkComplete:function(){

        if(this.count == 0){
            this.removeOldFooter();
            this.nodes.each(function(node, index){
                this.targets[index % this.targets.length].adopt(node);
            }.bind(this));
            this.nodes = [];
            this.complete(this.options.page);
            if(this.totalPages == this.options.page){
                this.finished();
            }else{
                if(this.showFooter) this.renderFooter();
            }
        }else this.checkComplete.delay(100, this);
    },
    getPageData : function(number, callback){
        var pageURL = this.dataURL+this.options.page;
        var submission = new Request.JSON({
            url : pageURL,
            onSuccess : function(json, text) {
                callback(json);
                this.offset = this.offset + this.options.itemsPerPage;
                this.fetching = false;
                if(window.woopraTracker && this.options.woopra) window.woopraTracker.pushEvent({ name:'scroll', page: this.options.page.toString() });
            }.bind(this)
        }).send();
    },
    complete:function(pageNumber){
        if(this.options.onpage) this.options.onpage(pageNumber);
        document.id(document).fireEvent('paginate', { page : pageNumber });
    },
    stop :function(){
        window.removeEvent('scroll', this.scrollFunction);
    },
    start :function(){
        window.addEvent('scroll', this.scrollFunction);
    },
    finished:function(){
        this.stop();
        if(this.options.onend) this.options.onend();
    }
});


Protolus.InfiniteScrollJS = new Class({
    Extends : Protolus.InfiniteScroll,
    initialize : function(element, options){
        // options: itemCount, template, controlsIdentifier
        this.parent(element, options);
        if(options.dataURL) this.dataURL = options.dataURL;
        if(!this.options.itemCount) this.options.itemCount = 0;
        if(!this.options.threshold) this.options.threshold = 0.75;
        if(this.options.threshold.isFloat()){
            this.scrollFunction = function(){
                if(!this.fetching && (window.getScroll().y/ window.getScrollSize().y) > this.options.threshold ){
                    this.fetching = true;
                    this.trigger();
                }
            }.bind(this);
        }else{
            this.scrollFunction = function(){
                if(
                    !this.fetching && 
                    (window.getScrollSize().y < (this.options.threshold + window.getScroll().y) )
                ){
                    this.fetching = true;
                    this.trigger();
                }
            }.bind(this);
        }
        window.addEvent('domready', function(){
            this.start();
        }.bind(this));
        if(this.options.preloadData){
            var usersData = this.options.payload;

            var renderData = usersData.splice(0,20);    
            var payload = {};
            payload['users'] = renderData;

            Protolus.Page.render(this.options.template, payload, function(str){
                var newComment = str.toDOM();

                newComment.inject(this.scrollContainer, 'bottom');

            }.bind(this));       
        }
    },
    trigger : function(){

        var usersData = this.options.payload;
        var renderData = usersData.splice(0,20);
        var payload = {};
        payload['users'] = renderData;

        Protolus.Page.render(this.options.template, payload, function(str){
            var newComment = str.toDOM();
            
            newComment.inject(this.scrollContainer, 'bottom');
            this.fetching = false;
        }.bind(this));    
    }
    
    
});