//Creates a modal box that pops up on screen and does stuff

Protolus.Modal = new Class({
    Implements: Options,
    options:{
        autoOpen : false,
        title : '',
        html : '',
        fadeIn : true,
        modalWidth : null,
        modalId : 'popup_modal_wrapper',
        modalContainer : 'modal_container',
        //Possible modalType are 'bool', 'html', 'panel' for legacy support (totally deprecated, do not use)
        closeButton : 'on',
        modalAlign : 'center',
        buttonsOn : true,
        buttonAlign : 'right',
        buttonClass : 'callout_button',
        boolSubmitAction : null,
        submitActionId : null,
        submitActionIndex : null,
        data : {},
        draggable : false,
        confirmBeforeClose : false,
        close : true,
        topMargin : 0,
        wrapperClass:'modal_wrapper',
    },
    modalContent : null,
    initialize : function(incoming){
        if(this.options.buttonsOn){ //legacy support
            this.options.buttons = this.options.buttonsOn;
            delete this.options.buttonsOn;
        }
        if(this.options.closeButton == 'on'){
            if(this.options.closeButton == 'on') this.options.close = true;
            if(this.options.closeButton == 'off') this.options.close = false;
            delete this.options.closeButton;
        }
        //console.log(incoming);
        for(key in this.options) if(!incoming[key] && incoming[key] !== false) incoming[key] = this.options[key];
        this.options = incoming;
        //console.log(['class', this.options.wrapperClass]);
        if(!document.id(this.options.modalId)){
            var modalWrap = new Element('div', {
                'id' : this.options.modalId, 
                'class': this.options.wrapperClass,
                'styles':{'display':'none'} 
            });
            var modalContainer = new Element('div', { 
                'id': 'modal_container',
                'class': 'modal_container'
            });
            var modalInner = new Element('div', { 
                'id': 'modal_box',
                'class': 'modal_box'
            });
            var modalTop = new Element('div', { 'id' : 'modal_box_top' });
            var modalTopText = new Element('div', { 'id' : 'modal_box_top_text' });
            this.modalContent = new Element('div', { 'id' : 'modal_box_content' });
            modalContainer.adopt(modalInner);
            modalTop.adopt(modalTopText);

            if(this.options.close){
                if(this.options.actions){
                    modal = this;
                    this.options.actions.push({
                        target : 'modal_box_top',
                        name : 'close',
                        label : 'X',
                        'class' : 'close_button',
                        action : function(){
                            this.hide();
                            if(this.options.onClose) this.options.onClose();
                        }.bind(this)
                    });
                }else{
                    var modalCloseButton = new Element('div', { 'class': 'close_button', 'text':'X'});
                    modalCloseButton.addEvent('click', function(){
                        this.hide();
                        if(this.options.onClose) this.options.onClose();
                    }.bind(this));
                    modalTop.adopt(modalCloseButton);
                }
            }
            modalInner.adopt(modalTop);
            modalInner.adopt(this.modalContent);
            modalWrap.adopt(modalContainer);
            document.id(document.body).adopt(modalWrap);
            //Set modal into place wherever we are on the page
            modalContainer.setStyle('margin-top', parseInt(this.options.topMargin)); //this is a browser specific hack: better...get Dimensions from Window and Element
            //drop it in
            //document.id(document.body).adopt(modalContainer);
            
            //console.log(['MC2', this.modalContent]);
            if(this.options.modalWidth) document.id('modal_box').setStyle('width',this.options.modalWidth+'px');
            if(this.options.draggable){
                modalTop.setStyle('cursor', 'move');
                var myDragScroller = new Drag(modalContainer,{
                    onBeforeStart : function(){
                        modalContainer.setStyle('margin-left','0px');
                        modalContainer.setStyle('margin-right','0px');
                    }
                }).detach();
                modalTop.addEvent('mousedown', function(event){ myDragScroller.attach(); });
                modalTop.addEvent('mouseup', function(event){ myDragScroller.detach(); });
            }
        }else{
            this.modalContent = document.id('modal_box_content');
        }
        if(this.options.modalType) switch(this.options.modalType){
            //for backwards compatibility (modalType is deprecated)
            case 'bool':
                this.content = '';
                if(document.id('modal_button_confirm')) document.id('modal_button_confirm').set('text', this.options.buttonYesText);
                if(document.id('modal_button_cancel')) document.id('modal_button_cancel').set('text', this.options.buttonNoText);
                break;
            case 'html':
                this.content = this.options.html;
                break;
            case 'panel':
                Protolus.Page.render(this.options.html, this.options.data, function(renderedHTML){
                    this.content = renderedHTML;
                }.bind(this));
                break;
        }else{
            if(this.options.panel){
                Protolus.Page.render(this.options.panel, this.options.data, function(html){
                    this.content = html;
                    this.show();
                }.bind(this));
            }
            if(this.options.html){
                this.content = this.options.html;
            }
        }
        if(this.options.title) document.id('modal_box_top_text').set('text', this.options.title);
        if(this.options.topMargin) modalInner.setStyle('margin-top',this.options.topMargin);
        if(this.options.modalWidth) document.id('modal_box').setStyle('width',this.options.modalWidth+'px');
        if(this.options.modalAlign == 'center'){
             //var centerPointPerc = ((document.body.offsetWidth/2)-(this.options.modalWidth/2))/document.body.offsetWidth*100;
             //console.log(centerPointPerc);
             document.id('modal_box').setStyle('left',(document.body.getSize().x/2)-(this.options.modalWidth/2)+'px');
         }
        if(this.options.autoOpen == true) this.show();
    },
    actions : function(actionOptions){
        this.options.actions = actionOptions;
    },
    render : function(panel, data, callback){
      if(data){
        Protolus.Page.render(panel, data, function(html){
            this.set(html);
            if(callback) callback(html);
        }.bind(this));
      }else{
          Protolus.Page.render(panel, function(html){
              this.set(html);
              if(callback) callback(html);
          }.bind(this));
      }
        
    },
    set :function(content){
        this.modalContent.empty();
        this.buildComponents();
        this.add(content);
		this.content = content;
		if(typeOf(this.options.actions) == 'array') this.buildActions();
        if(this.options.onLoad) this.options.onLoad();
    },
    actionTargets : [],
    buildActions : function(){
        //clean
        this.actionTargets.each(function(target){
            target.empty();
        });
        //render
        var modal = this;
        this.options.actions.each(function(control){
            
            if(!control.label) control.label = control.name;
            var button;
            if(!('class' in control))control['class'] = '';
            if(!control.target) control.target = this.options.name+'_modal_buttons';
            var el = document.id(control.target);
            //console.log(el);
            if(!el){
                el = new Element('div', {
                    'class' : 'modal_buttons',
                    'id' : control.target,
                    'align': this.options.buttonAlign
                });
                this.modalContent.adopt(el);
                this.actionTargets.push(el);
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
        this.modalContentText = new Element('div', {
            'id' : 'modal_box_content_text'
        });
        //if(!this.modalContent) console.log(['modal',this ]);
        this.modalContent.adopt(this.modalContentText);
        if(typeOf(this.options.actions) == 'array'){
            // do nothing, actions are built after content
        }else if(this.options.buttons == true){ //this is the old, crappy way.
            var modalButtonDiv = new Element('div', {
                'class' : 'modal_buttons',
                'align': this.options.buttonAlign
            });
            var modalYesButton = new Element('button', {
                'id' : 'modal_button_confirm',
                'class': this.options.buttonClass,
                'text' : this.options.confirmText
            });
            var modalNoButton = new Element('a', {
                'id' : 'modal_button_cancel',
                'class' : 'modal_cancel_button',
                'href' : 'javascript:void(0)',
                'text' : this.options.cancelText
            });
            if(this.options.cancelText) modalButtonDiv.adopt(modalNoButton);
            if(this.options.confirmText) modalButtonDiv.adopt(modalYesButton);
            this.modalContent.adopt(modalButtonDiv);
            modalNoButton.addEvent('click', function(){
                this.hide();
                if(this.options.onCancel) this.options.onCancel();
            }.bind(this));
            modalYesButton.addEvent('click', function(){
                if(this.options.confirmBeforeClose){ //legacy syntax support
                    this.options.onConfirm(this.fields(), function(){ this.hide(); }.bind(this));
                    return;
                }
                if(this.options.onConfirm){
                    var result = this.options.onConfirm(this.fields());
                    if(typeOf(result) == 'function') result();
                    this.hide();
                }
            }.bind(this));
        }
    },
    add :function(content){
        this.modalContentText.set('html', content);
		this.content +=content;
    },
    show : function(){
        //console.log(['MC3', this.modalContent]);
        this.set(this.content);
        if(this.options.fadeIn == true){ //this should be a settable effect, rather than a hardcoded one
            document.id(this.options.modalId).set('tween', {duration: 150});
            document.id(this.options.modalId).tween('opacity',1);
            document.id(this.options.modalId).show();
        }else{
            document.id(this.options.modalId).setStyle('opacity','1');
            document.id(this.options.modalId).show();
        }
    },
    hide : function(){
        //console.log(['options', this.options]);
        if(document.id(this.options.modalId)){
			document.id(this.options.modalId).hide();
	/*
            document.id(this.options.modalId).set('tween', {duration: 150});
            document.id(this.options.modalId).tween('opacity',0);
*/
            //document.id(this.options.modalId).dispose();
            
            //This is confusing. This had to change... modalContainer is actually the modal itelf and modalId is the wrapper.
            //They are separate elements! The modalID should be the modal itself. The Ids should switch option names...
            
            //ModalID should *always* refer to the root element to which all modal elements are attached... anything's id should always be on it's root DOM element
            //document.id(this.options.modalContainer).dispose();
            
            if(this.options.onHide) this.options.onHide();
        }
    },
    fields : function(){
        var formValues = {};
        var inputs = $$(this.modalContent.getElementsByTagName('input'));
        inputs.each(function(input){
            if(typeOf(input) != 'element') return;
            if(input.getAttribute('type').toLowerCase() == "checkbox") formValues[input.getAttribute('name')] = input.get('checked');
            else if(input.getAttribute('type').toLowerCase() == "radio"){
               if(input.get('checked')) formValues[input.getAttribute('name')] = input.getAttribute('id');
            }else formValues[input.getAttribute('name')] = ( ('value' in input) ?input.value:input.innerHTML);
        });
        var areas = $$(this.modalContent.getElementsByTagName('textarea'));
        areas.each(function(input){
            if(typeOf(input) != 'element') return;   
            formValues[input.getAttribute('name')] = input.value;
         
        });
        return formValues;
    },
	dispose : function(){ document.id(this.options.modalId).dispose(); },
    showModal : function(){ this.show(); }, //backwards compatibility
    closeModal : function(){ this.hide(); }
});