if(!HTML5) var HTML5 = {};
if(!HTML5.Forms) HTML5.Forms = {};
HTML5.Forms.range = function(){
    //todo: use duck typing instead of recognizing the browser
    if(!(Browser.safari || Browser.chrome)) document.addEvent('domready', function() {
        var sliders = $$("input[type='range']");
        sliders.each(function(input, index){
            var id = input.parentNode.getAttribute('id');
            if(!input.parentNode.getAttribute('id')){
                id = 'range_input_'+index;
                input.parentNode.set('id', id);
            }
            var knob = new Element('div', {
                width : '20px',
                height : '40px',
                id : id+'_knob'
            });
            var slide = new Element('div', {
                id : id+'_slide'
            });
            //todo: support CSS in addition to hardcoded styles
            /*
            
            todo: support aqua appearance (exact safari dupe), when CSS is not used
            
            background: #cde; 
			border: 2px solid #ccc; 
			border-color: #8ba2c1 #5890bf #4f93ca #768fa5; 
			font: 600 16px/1 Lucida Sans, Verdana, sans-serif; 
			color: #fff; 
			text-shadow: rgba(10, 10, 10, 0.5) 1px 2px 2px;
			text-align: center; 
			vertical-align: middle; 
			white-space: nowrap; 
			text-overflow: ellipsis; 
			overflow: hidden;
			border-radius: 16px; -moz-border-radius: 16px; -webkit-border-radius: 16px;
			box-shadow: 0 10px 16px rgba(66, 140, 240, 0.5), inset 0 -8px 12px 0 #6bf, inset 0 -8px 0 8px #48c, inset 0 -35px 15px -10px #7ad;
			-moz-box-shadow: 0 10px 16px rgba(66, 140, 240, 0.5), inset 0 -8px 12px 0 #6bf, inset 0 -8px 0 8px #48c, inset 0 -35px 15px -10px #7ad;
			-webkit-box-shadow: 0 10px 16px rgba(66, 140, 240, 0.5), inset 0 -8px 12px 0 #6bf , inset 0 -8px 0 8px #48c, inset 0 -35px 15px -10px #7ad;
			*/
            slide.setStyle('width', input.getStyle('width'));
            slide.setStyle('height', '6px');
            slide.setStyle('background-color', '#444444');
            slide.setStyle('-moz-border-radius', '3px 3px 3px 3px');
            slide.setStyle('margin-top', '-12px');
            slide.setStyle('-moz-box-shadow', 'inset 1px 1px 3px #000000');
            knob.setStyle('margin-top', '-20px');
            knob.setStyle('height', '14px');
            knob.setStyle('width', '14px');
            knob.setStyle('background-color', '#555555');
            knob.setStyle('border', '2px solid #777777');
            knob.setStyle('-moz-border-radius', '10px 10px 10px 10px');
            input.parentNode.adopt(knob);
            input.parentNode.adopt(slide);
            input.setStyle('opacity', .001);
            var mySlider = new Slider(id, id+'_knob', {
                range: [Number.from(input.getAttribute('min')), Number.from(input.getAttribute('max'))],
                wheel: true,
                snap: true,
                onChange: function(pos){
                    input.value = pos;
                    input.fireEvent('change', {event:{srcElement:input}});
                }.bind(this)
            });
        });
    });
};
