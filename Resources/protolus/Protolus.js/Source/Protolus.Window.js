Protolus.Window = new Class({
    Implements: Options,
    options:{
        data : {}, //available in both wrapper and panel renders
        modalsDirectory : 'modals',
        buttonClass : 'callout_button',
        dragHandle : false,
        standardControls : false,
        screen : false,
        name : 'default',
        position : 'centerTop',
        wrapper : 'modal',
        actions : [], //wiped on show with new actions or on set
        globalActions : [], //never wiped
    },
    actionsBuilt : false,
    modalContent : null,
    initialize : function(name, options){
        if(name && !options && typeOf(name) == 'object') throw('This modal call has been deprecated, please update the call!');
        if(options.actions){
            options.globalActions = options.actions;
            options.actions = [];
        }
        for(key in this.options) if(!options[key] && options[key] !== false) options[key] = this.options[key];
        this.options = options;
        this.options.name = name;
        if(!this.options.title) this.options.title = this.options.name;
        if(document.id('modal_'+name)){
            this.modal = document.id('window_'+name);
            this.wrapper = document.id('window_wrapper_'+name);
        }else{
            this.modal = new Element('div', {
                'id' : 'window_'+name,
                'class': 'window',
                //'styles':{'display':'none'}
            });
            this.modal.setStyle('position', 'relative');
            this.wrapper = new Element('div', {
                'id' : 'window_wrapper_'+name, 
                'class': 'window_wrapper',
                'styles':{'display':'none'} 
            });
            this.wrapper.setStyle('position', 'fixed');
            this.wrapper.setStyle('top', '0px');
            this.wrapper.setStyle('left', '0px');
            this.wrapper.setStyle('overflow', 'hidden');
            this.wrapper.adopt(this.modal);
            document.id(document.body).adopt(this.wrapper);
        }
        if(this.options.standardControls){
            var controls = this.options.standardControls.split(',');
            controls = controls.map(function(item){
                return item.toLowerCase();
            });
            if(controls.contains('close')){
                this.options.globalActions.push({
                    target : '*.window_controls',
                    name : 'close',
                    label : 'X',
                    'class' : 'close_button',
                    type:'a',
                    action : function(){
                        this.hide();
                        if(this.options.onClose) this.options.onClose();
                    }.bind(this)
                });
            }
            //todo: more standard controls
            if(controls.contains('minimize')){ }
            if(controls.contains('maximize')){ }
        }
        if(this.options.dragHandle){
            if(this.modal.dragger){
                this.modal.dragger.detach();
            }
            this.options.dragHandle.whenIn(this.wrapper, function(element){ //once we have a wrapper, set draggable
                this.modal.dragger = new Drag(this.modal,{
                    handle : this.wrapper.getElements(this.options.dragHandle)[0],
                    onDrag : function(){
                        this.handleWindowBounds();
                    }.bind(this)
                });
            }.bind(this));
        }
        //todo: fixed or scrolling mode
        //todo: set current top
        this.buildComponents();
        if(this.options.panel){
            this.render(this.options.panel, this.options.data);
        }
        if(this.options.html){
            this.content = this.options.html;
        }
        if(this.options.resizable) this.modal.makeResizable();
        if(!window.protolusWindows) window.protolusWindows = {};
        window.protolusWindows[name] = this;
        window.addEvent('resize', this.handleWindowBounds.bind(this));
    },
    actions : function(actionOptions){
        this.options.actions = actionOptions;
    },
    snap : function(){
        if(typeOf(this.options.position) == 'array'){ //ex: ['top', 'left']
            this.options.position = {x: this.options.position[1], y: this.options.position[0]};
        }
        if(this.options.position.indexOf && this.options.position.indexOf('-') != -1){ //ex: 'top-left'
            var parts = this.options.position.split('-');
            this.options.position = {x: parts[1], y: parts[0]};
        }
        var posOptions = {
            relativeTo: this.wrapper, 
            position: this.options.position,
            returnPos : true,
            edge: 'center'
        }
        if(!this.options.offset) this.options.offset = {};
        if(!this.options.offset.x) this.options.offset.x = 0;
        if(!this.options.offset.y) this.options.offset.y = 0;
        var pos = this.modal.position(posOptions);
        this.modal.setStyle('left', pos.left);
        this.modal.setStyle('top', this.options.offset.y + pos.top - window.getScroll().y);
        this.handleWindowBounds();
        
    },
    render : function(panel, data, callback){
        if(this.options.wrapper && !this.wrapperPanel){
            this.wrapperPanel = new Protolus.Panel(this.options.modalsDirectory+'/'+this.options.wrapper);
            this.wrapperPanel.wrapper = true;
            this.wrapperPanel.render({
                content : ('<div class="window_content"></div>'),
                name : this.options.name,
                title : this.options.title,
            }, function(text){
                this.modal.set('html', text);
            }.bind(this));
        }
        this.panel = new Protolus.Panel(panel);
        if(typeOf(data) == "object"){
            this.panel.render(data, function(text){ 
                this.set(text, callback);
                //this.buildActions();
                if(this.options.onRender)this.options.onRender(panel);
            }.bind(this));
        }else{
            this.panel.render(function(text){ 
                this.set(text, callback);
                this.buildActions();
                 if(this.options.onRender)this.options.onRender(panel);
            }.bind(this));
        }
    },
    set :function(content, callback){
        this.content = content;
        '*.window_content'.whenIn(this.wrapper, function(element){ //if we're rendering the wrapper we need to wait a sec
            element.empty();
            //element.adopt()
            element.set('html', this.content);
            this.buildActions(!this.actionsBuilt);
            if(!this.snapped){ //snap the first time we see content in the frame
                this.snap();
                this.snapped = true;
            }
            if(callback) callback(content);
        if(this.options.onLoad) this.options.onLoad();
        }.bind(this));
    },
    actionTargets : [],
    buildActions : function(includeGlobals){
        //clean
        this.actionsBuilt = true;
        /*this.actionTargets.each(function(target){
            target.empty();
        });*/
        var initialized = {};
        //render
        var actions;
        if(includeGlobals) actions = Object.merge(this.options.actions.clone(), this.options.globalActions.clone());
        else actions = this.options.actions;
        actions.each(function(control){
            if(!control.label) control.label = control.name;
            var button;
            if(!('class' in control)) control['class'] = '';
            if(!control.target) control.target = this.options.name+'_modal_buttons';
            var el = this.wrapper.getElements(control.target)[0];
            if(!el){
                return;
                el = new Element('div', {
                    'class' : 'modal_buttons',
                    'id' : control.target,
                    'align': this.options.buttonAlign
                });
                this.modal.adopt(el);
                this.actionTargets.push(el);
            }else{
                if(!this.actionTargets.contains(control.target)) this.actionTargets.push(control.target);
            }
            
            switch(control.type){
                case 'a':
                default:
                    button = new Element('a', {
                        'id' : this.options.name+'_'+control.name+'_button',
                        'class' : 'modal_action '+control.name+'_button '+control['class'],
                        'href' : 'javascript:void(0)',
                        'text' : control.label
                    });
                    target = button;
                    break;
                case 'button':
                    button = new Element('button', {
                        'id' : this.options.name+'_'+control.name+'_button',
                        'class' : 'modal_action '+control.name+'_button '+control['class'],
                        'text' : control.label
                    });
                    target = button;
                    break;
                case 'file':
                    if(!control.src) control.src = '/core/upload_button';
                    button = new Element('iframe', {
                        'id' : this.options.name+'_'+control.name+'_upload_frame',
                        'src' : control.src+'?name='+control.name+'&label='+control.label+'&callback='+this.options.name+'_'+control.name+'&width='+control.width+'&height='+control.height+'&background='+control.background+'&style='+this.options.style+'&url='+control.endpoint,
                        'height' : Number.from(control.height) + 15,
                        'width' : Number.from(control.width) + 15,
                        'scrolling' : 'no',
                        events:{
                            'load' : function(){
                                el.show();
                            }.bind(this)
                        },
                        styles : {
                            'display' : 'inline',
                            'padding' : '0px',
                            'margin' : '0px',
                            'vertical-align' : 'bottom',
                            'margin-bottom' : '-10px',
                            'background-image' : '/images/loading.gif',
                        }
                    });
                    el.hide();
                    target = button;
                    break;
                case 'div':
                    button = new Element('div', {
                        'id' : this.options.name+'_'+control.name+'_button',
                        'class' : 'modal_action '+control.name+'_button '+control['class'],
                        'align': control.align,
                        'text' : control.label
                    });
                    target = button;
                    break;
            }
            if(this.actionTargets.contains(control.target) && !initialized[control.target]){
                el.empty();
                initialized[control.target] = true;
            }
            el.appendChild(button);
            control.action.bind(this);
            switch(control.type){
                case 'file':
                    if(!Protolus.Modal.uploadCallbacks) Protolus.Modal.uploadCallbacks = {};
                    if(!Protolus.Modal.uploadInits) Protolus.Modal.uploadInits = {};
                    Protolus.Modal.uploadCallbacks[this.options.name+'_'+control.name] = function(event){
                        if(control.hide) modal.hide();
                        if(event) event.stop();
                        control.action.bind(this);
                        return control.action(this, event);
                    }.bind(this);
                    break;
                case 'a':
                case 'div':
                case 'button':
                default :
                    if(control.action) target.addEvent('click', function(event){
                        if(control.hide) modal.hide();
                        if(event) event.stop();
                        control.action(this, event);
                    }.bind(this));
                    break;
            }
        }.bind(this));
    },
    buildComponents :function(){
        this.set('<div style="height:200px;width:100%; text-align:center;margin-top: 150px;"><img src="/images/loading.gif"/></div>')
    },
    add :function(content){
        this.content += content;
        this.modal.set('html', content);
    },
    show : function(){
        //this.set(this.content);
        this.wrapper.show();
        if(!this.visible) this.snap();
        this.visible = true;
        if(this.options.onShow) this.options.onShow();
    },
    handleWindowBounds : function(){
        if(this.options.contentFrame){
            var content = this.modal.getElements(this.options.contentFrame)[0];
            if(content){
                var yDelta = content.getPosition(this.modal).y; //the height of the area above the content
                var offset = yDelta + this.modal.getPosition().y;
                var trailing = this.modal.getSize().y - (yDelta + content.getSize().y) //the hieght of the area below the content
                content.setStyle('overflowY', 'auto');
                content.setStyle('maxHeight', (window.getSize().y + window.getScroll().y) - (offset + trailing + Number.from(content.getStyle('marginBottom')) ) );
            }
        }
    },
    hide : function(){
        this.wrapper.hide();
        this.visible = false;
        if(this.options.onHide) this.options.onHide();
    },
    fields : function(){
        return this.modal.formValues();
    },
	dispose : function(){
	   this.modal.dispose();
	   this.wrapper.dispose();
    }
});

Protolus.TabbedWindow = new Class({
    Extends : Protolus.Window,
    currentTab : '',
    tabIndex : {},
    initialize : function(name, options){
        options.tabs.each(function(tab){
            this.tabIndex[tab.name] = tab;
            tab.target = options.tabContainer;
            tab['class'] = options.tabClass;
            tab.action = function(modal, event){
                modal.show(tab.name, tab.panel, function(){
                    if(tab.initialize) tab.initialize(tab);
                });
                event.target.getParent().getChildren().removeClass('selected');
                event.target.addClass('selected');
                if(tab.selected) tab.selected(tab);
                 //right now tabs always reinitialize, later we may buffer
            }.bind(this)
        }.bind(this));
        options.actions = options.tabs;
        this.parent(name, options);
        if(this.options.tab){
            this.show(this.options.tab);
            options.tabContainer.whenFullIn(this.modal, function(container){
                var results = container.getChildren().filter(function(child){
                    return child.get('html') == this.tabIndex[this.options.tab].label;
                }.bind(this));
                if(results.length == 1){
                    results[0].getParent().getChildren().removeClass('selected');
                    results[0].addClass('selected');
                }
            }.bind(this))
        }
    },
    show : function(tab, panel, callback){
        if(this.options.onTab) this.options.onTab(this.tabIndex[tab]);
        if(!panel){
            if(this.tabIndex[tab] && this.tabIndex[tab].panel) panel = this.tabIndex[tab].panel;
            else panel = tab;
        }
        if(tab && tab != this.currentTab){
            this.render(panel, null, function(text){
                this.currentTab = tab;
                if(this.tabIndex[tab] && this.tabIndex[tab].controls){
                    this.options.actions = this.tabIndex[tab].controls;
                }else{
                    this.options.actions = [];
                }
                this.show(); //cheap way to make a this.parent() call recursively
                if(callback) callback();
            }.bind(this));
        }else{
            this.parent();
        }
    }
});

Protolus.ModalWindow = new Class({
    Extends : Protolus.Window,
    initialize : function(name, options){
        this.parent(name, options);
        if(this.options.screen){ //todo: preserve existing screen state
            if(!document.id('modal_screen')){
                var screen = new Element('div', { 
                    'id': 'modal_screen',
                    'class': 'modal_screen'
                });
                document.id(document.body).adopt(screen);
                this.screen = screen;
            }else this.screen = document.id('window_'+name);
        }
    },
    dispose : function(){
        this.parent();
    },
    show : function(){
        this.parent();
        if(this.options.screen) this.screen.show();
    },
    hide : function(){
        this.parent();
        if(this.options.screen) this.screen.hide();
    }
});

if(!Element.formValues){
    Element.implement({
        formValues: function(mode) {
            var formValues = {};
            var inputs = $$(this.getElementsByTagName('input'));
            inputs.each(function(input){
                if(typeOf(input) != 'element') return;
                if(input.getAttribute('type') == "checkbox") formValues[input.getAttribute('name')] = input.get('checked');
                else if(input.getAttribute('type') == "radio"){
                   if(input.get('checked')) formValues[input.getAttribute('name')] = input.getAttribute('id');
                }else formValues[input.getAttribute('name')] = ( ('value' in input) ?input.value:input.innerHTML);
            });
            var areas = $$(this.getElementsByTagName('textarea'));
            areas.each(function(input){
                if(typeOf(input) != 'element') return;   
                formValues[input.getAttribute('name')] = input.value;
            });
            return formValues;
        }
    });
}