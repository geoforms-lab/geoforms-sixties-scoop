<?php

class CardDataType extends \core\extensions\plugin\PluginDataType {
    
    protected $authtasks = array(
        'read',
        'write',
        'extend'
    );

    public function getParentTypes() {

        return array(
            'marker'
        ); 
    }

    /**
     * @SuppressWarnings("unused")
     */
    public function authorize($task, $item) {
        if (GetClient()->isAdmin()){
            return true;
        }


       
       return  Auth($task, $item, "marker");
        
    }
}