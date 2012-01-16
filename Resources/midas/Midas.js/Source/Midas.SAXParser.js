/*
---
description: An extensible Mootools object container bridging to a pureJS SAX parser

license: [MIT-style, LGPL]

authors:
- Abbey Hawk Sparrow

requires:
    core/1.2.4: '*'

provides: [Midas.XMLParser]
...
*/
if(!Midas) var Midas = {};
Midas.SAXParser = new Class({
    initialize: function(){
        this.buildXMLcomponents();
    },
    buildXMLcomponents: function(){
        this.handler = new MidasSAXEventHandler();
        this.parser = new SAXDriver();
    },
    open: function(tagName, attributes){
        var node = document.id('widget_panel');
        //var pro = profile(attributes);
        var pro = attributes["d"];
        node.innerHTML = node.innerHTML + pro +"<br/><br/>";
    },
    content: function(text){
    },
    close: function(tagName){
    },
    error: function(exception){
        //console.log(['SAX Parse Error!', exception, exception.m_parser.m_xml.charAt(exception.m_parser.iP)+exception.m_parser.m_xml.charAt(exception.m_parser.iP+1)+exception.m_parser.m_xml.charAt(exception.m_parser.iP+2), exception.m_parser.m_xml]);
        throw('SAX Parse Error('+exception.m_strErrMsg+')');
    },
    parse: function(xml){
        if(!this.handler) this.buildXMLcomponents();
        this.handler.startTag = this.open.bind(this);
        this.handler.createError = this.error.bind(this);
        this.handler.endTag = this.close.bind(this);
        this.handler.charData = this.content.bind(this);
        this.parser.setDocumentHandler(this.handler);
        this.parser.setLexicalHandler(this.handler);
        this.parser.setErrorHandler(this.handler);
        this.parser.parse(xml);
    }
});
// SAX constructor
MidasSAXEventHandler = function() {
    this.characterData = "";
};
// My Non-exposed hooks (all existing hooks require fucking with char data internally, 
// so we call cleanly out to here to make truly pluggable functions)
MidasSAXEventHandler.prototype.startTag = function(name, attrs){};
MidasSAXEventHandler.prototype.charData = function(chars){};
MidasSAXEventHandler.prototype.endTag = function(name){};
//MidasSAXEventHandler Object SAX INTERFACES
MidasSAXEventHandler.prototype.characters = function(data, start, length) {
    this.characterData += data.substr(start, length);
}
MidasSAXEventHandler.prototype.endDocument = function() {
    this._handleCharacterData();
    //place endDocument event handling code below this line
}
MidasSAXEventHandler.prototype.endElement = function(name) {
    this._handleCharacterData();
    //place endElement event handling code below this line
    this.endTag(name);
}
MidasSAXEventHandler.prototype.processingInstruction = function(target, data) {
    this._handleCharacterData();
    //place processingInstruction event handling code below this line
}
MidasSAXEventHandler.prototype.setDocumentLocator = function(locator) {
    this._handleCharacterData();
    //place setDocumentLocator event handling code below this line
}
MidasSAXEventHandler.prototype.startElement = function(name, atts) {
    this._handleCharacterData();
    var attrs = {};
    //place startElement event handling code below this line
    for(var lcv =0; lcv < atts.getLength(); lcv++){
        attrs[atts.getName(lcv)] = atts.getValue(lcv);
    }
    this.startTag(name, attrs);
}
MidasSAXEventHandler.prototype.startDocument = function() {
    this._handleCharacterData();
    //place startDocument event handling code below this line
}
//MidasSAXEventHandler Object Lexical Handlers
MidasSAXEventHandler.prototype.comment = function(data, start, length) {
    this._handleCharacterData();
    //place comment event handling code below this line
}
MidasSAXEventHandler.prototype.endCDATA = function() {
    this._handleCharacterData();
    //place endCDATA event handling code below this line
}
MidasSAXEventHandler.prototype.startCDATA = function() {
    this._handleCharacterData();
    //place startCDATA event handling code below this line
}
// MidasSAXEventHandler Object Error Interface
MidasSAXEventHandler.prototype.error = function(exception) {
    this._handleCharacterData();
    //place error event handling code below this line
    this.createError(exception);
}
MidasSAXEventHandler.prototype.fatalError = function(exception) {
    this._handleCharacterData();
    //place fatalError event handling code below this line
    this.createError(exception);
}
MidasSAXEventHandler.prototype.warning = function(exception) {
    this._handleCharacterData();
    //place warning event handling code below this line
}
//MidasSAXEventHandler Object Internal Functions
MidasSAXEventHandler.prototype._fullCharacterDataReceived = function(fullCharacterData) {
    //place character (text) event handling code below this line
    this.charData(fullCharacterData);
}
MidasSAXEventHandler.prototype._handleCharacterData = function()  {
    if (this.characterData != ""){
        this._fullCharacterDataReceived(this.characterData);
    }
    this.characterData = "";
}