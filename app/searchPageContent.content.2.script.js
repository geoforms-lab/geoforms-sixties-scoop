return new Element('button', {'class':"inline-edit options-btn", html:"Advanced", "events":{
    "click":function(){
        
        if(this.hasClass('enabled')){
                
            this.removeClass('enabled');
            $$('.ui-view.search-content')[0].removeClass('enabled');
            this.innerHTML='Advanced';
            return;
        }
        
        
        this.addClass('enabled');
        $$('.ui-view.search-content')[0].addClass('enabled');
        this.innerHTML='Hide advanced';
        
    }
}});