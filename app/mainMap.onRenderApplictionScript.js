IncludeJSBlock('
    TemplateModule.SetTemplate(\'default\',\'<div data-template="content" class="template-content"></div>\');
');

GetWidget("homeStyle")->display($targetInstance);
GetWidget("mainStyle")->display($targetInstance);

GetPlugin('MapStory')->includeScripts();