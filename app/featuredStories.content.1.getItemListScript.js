

    // var defaultList=[
    //     new MockDataTypeItem({name:"##Example 1"}), 
    //     new MockDataTypeItem({name:"##Example 2"}), 
    //     new MockDataTypeItem({name:"##Example 2"})
    // ];



	(new AjaxControlQuery(CoreAjaxUrlRoot, "get_configuration_field", {
		'widget': "featuredStoriesItems",
		'field': "featured"
	})).addEvent('success',function(response){
	    
	       var list=[];
	       var check=function(){
	           if(list.filter(function(item){
	               return !!item;
	           }).length==3){
	               callback(list);
	           }
	       };
	       
	    
	      response.value.forEach(function(featured, i){
	           
	           //if(data.name){
	           //    data.name='##'+data.name;
	           //}
	           
	           
	           
                (new AjaxControlQuery(CoreAjaxUrlRoot, "get_story_with_item", {
                    "plugin": "MapStory",
                    "item": featured.id
                })).addEvent("success", function(resp) {
                  
                    var item = new MockDataTypeItem(ObjectAppend_(
                         {mutable:true, name:"##Item "+i, story:null, cards:null}, 
                         featured,
                         {resp:resp}
                    ));
	           
    	             list.push(item);
    	             check();
                
                 
                    
                }).execute();
	           
	          
	       });
	       
           
	       
	    
        //callback(list.concat(defaultList).slice(0,3));
        
	}).execute();




