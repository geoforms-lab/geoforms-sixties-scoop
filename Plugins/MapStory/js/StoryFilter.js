var StoryFilter=(function(){


	var StoryFilter=new Class_({

		initialize:function(){

			this._filter=function(){
				return true;
			}

		},

		fromUrl:function(){

			var filter=document.location.pathname.split('/filter').pop();
			var stubs=filter.split('/');

			if(stubs.length<2){
				return this;
			}


			var union=stubs.shift().indexOf('any')>=0;

			var sources=[];
			var dests=[];

			stubs.forEach(function(filter){

				if(filter.indexOf('source-')===0){
					sources=sources.concat(filter.split('source-').slice(1).split('-'));
				}

				if(filter.indexOf('dest-')===0){
					dests=dests.concat(filter.split('dest-').slice(1).split('-'));
				}


			});


			if(sources.length>0||dests.length>0){
				this._filter=function(result){
					return true;
				}
			}
			return this;
		},
		filterList:function(list, cb){


			cb(list);
		}


	});


	return StoryFilter;Editor.RenderStaticPreview(assetPath : string, subAssets : Object[], width : int, height : int)



})();