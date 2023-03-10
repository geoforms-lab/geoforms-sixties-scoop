///return ScoopStories.getSearchAggregators(search);



var SearchAggregator = new Class({
    Extends: UISearchListAggregator,
    initialize: function(search, options) {
        var me = this;
        this.parent(search, Object.append({
            maxResults: 10,
            PreviousTemplate: UIListAggregator.PreviousTemplate,
            MoreTemplate: UIListAggregator.MoreTemplate,
            ResultTemplate: UIListAggregator.NamedViewTemplate(application, {
                namedView: "sidePanelInfoDetail",
                formatResult: function(data) {

                    return new StoryGroup({
                               type:data.name,
                               description:"",
                               cards:[],
                               id:data.id
                               
                           });
                   
                   
                },
                events: {
                    click: function(result, e) {
            
                        window.location.href="/story/"+result.id

                    }
                }
            })

        }, options));
    },
    getLastResponse:function(){
        return this._results;
    },
    _getRequest: function(filters) {
        var me = this;
        var string = me.currentSearchString;


        return (new AjaxControlQuery(CoreAjaxUrlRoot, 'advanced_search', {
                'plugin': 'MapStory',
                'search': {name:string},
                 searchOptions: filters
            })).addEvent('success',function(results){

            me._results=results;

        });
    }
});


return [
        new SearchAggregator(search)
    ]