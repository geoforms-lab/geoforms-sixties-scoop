

TemplateModule.SetTemplate('form',`<div>
    <div data-template="title" class="template-title"></div>
    <div data-template="content" class="template-content"></div>
    <div data-template="footer" class="template-footer"></div>
    </div>`);
    
    
if(AppClient.getUserType()==="guest"){
    new UIModalDialog(ScoopStories.getApp(), AppClient, {
             
        label: "Login",
        formName: "loginFormView",
        "class": "primary-btn login"
    
        
    }).show();
    return;
}


(new UIModalDialog(
			map, 
			new MockDataTypeItem({
			    mutable:true,
			    name:"",
			    description:"",
			    latLng:null
			}), 
			{
				"formName": 'supportGroupForm',
				"formOptions": {
					template: "form",
					labelForCancel:"Cancel",
					labelForSave:"Save",
					viewerOptions:{
				    	"className":"test"
					}
				}
			}
		)).show(function(){


		}).on('complete', function(item){
		    
		   
		    
		}).getWizard()
		    .setDataValue('latlng', latlng.lat+', '+latlng.lng)
		    .setDataValue('iconUrl', iconUrl)