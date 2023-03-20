<?php

namespace Plugin;
include_once __DIR__ . '/vendor/autoload.php';

class MapStory extends \core\extensions\Plugin implements
\core\ViewController, \core\AjaxControllerProvider, \core\EventListener, \core\DataTypeProvider {

	use \core\AjaxControllerProviderTrait;
	use \core\EventListenerTrait;
	use \core\DataTypeProviderTrait;

	protected $name = 'Create Map Stories';
	protected $description = 'Allows users to create stories by connecting map items';

	protected $cacheUserMeta=array();

	// protected function onFacebookLogin($params){

	// }
	protected function onFacebookRegister($params) {

		$photoUrl = 'https://graph.facebook.com/' . $params->fbuser->id . '/picture?type=large';
		error_log($photoUrl);

		GetPlugin('Attributes');

		$icon = '<img src="' . $photoUrl . '" />';

		(new \attributes\Record('profileAttributes'))->setValues($params->user, "user", array(
			"icon" => $icon,
			"name" => $params->fbuser->name,
		));

	}

	public function includeScripts() {

		Behavior('aggregator');

		IncludeJS($this->getPath() . '/js/Stories.js');
		IncludeJS($this->getPath() . '/js/ClusterBehavior.js');
		IncludeJS($this->getPath() . '/js/StoryGroup.js');
		IncludeJS($this->getPath() . '/js/StoryCard.js');
		IncludeJS($this->getPath() . '/js/StoryUser.js');
		IncludeJS($this->getPath() . '/js/StorySearch.js');
		IncludeJS($this->getPath() . '/js/AdvancedStorySearch.js');
		IncludeJS($this->getPath() . '/js/UIDispersionGraph.js');
		IncludeJS($this->getPath() . '/js/MapStyle.js');
		IncludeJS($this->getPath() . '/js/StoryFilter.js');
	}

	public function getStoryLayerId() {
		return 1;
	}

	public function getUsersStoryMarker($itemId) {

		GetPlugin('Maps');

		$itemId = (int) $itemId;
		if ($itemId > 0) {
			$feature = (new \spatial\FeatureLoader())->fromId($itemId);
			return $feature;
		}

		$feature = new \Marker();
		$feature->setUserId(GetClient()->getUserId());

		return $feature;

	}

	public function formatFeatureMetadata($feature, $attributes = null) {
		return (new \MapStory\CardFormatter())->format($feature, $attributes);
	}

	

	public function getUsersMetadata($userId = -1) {

		if ($userId == -1) {
			$userId = GetClient()->getUserId();
		}

		if(!key_exists($userId, $this->cacheUserMeta)){
			GetPlugin('Attributes');
			$userData=(new \attributes\Record('profileAttributes'))->getValues($userId, "user");

			if(($userData['allowContact']==="true"||$userData['allowContact']===true)&&($userData['shareEmail']==="true"||$userData['shareEmail']===true)){
				$userData["email"]=GetClient()->userMetadataFor($userId)['email'];
			}
			
			$userData['id']=$userId;

			$this->cacheUserMeta[$userId]=$userData;


		}

		return $this->cacheUserMeta[$userId];

	}

	public function getFeaturesMetadata($featureIds) {

		GetPlugin('Maps');
		$list = array();
		(new \spatial\Features())
			->listLayerFeatures($this->getStoryLayerId())
			->withFeatures($featureIds)
			->iterate(function ($feature) use (&$list) {

				$attributes = $this->getCachedStoryAttribute($feature['id']);

				$list[] = $this->formatFeatureMetadata($feature, $attributes);

			});

		return $list;

	}

	public function getUsersStoryMetadata($userId = -1) {

		if ($userId == -1) {
			$userId = GetClient()->getUserId();
		}

		GetPlugin('Maps');
		$list = array();

		(new \spatial\Features())
			->listLayerFeatures($this->getStoryLayerId())
			->withOwner($userId)
			->iterate(function ($feature) use (&$list) {

				$attributes = $this->getCachedStoryAttribute($feature['id']);
				$list[] = $this->formatFeatureMetadata($feature, $attributes);

			});

		return (new \MapStory\StoryFormatter())
			->setCommitChanges(true)
			->forUser($userId)
			->format($list);

	}


	protected $_cachedStoryAttributes=array();
	protected $_cachedStoryAttr=null;
	protected  function getCachedStoryAttribute($id){

		if(!array_key_exists($id, $this->_cachedStoryAttributes)){
			
			if(!$this->_cachedStoryAttr){
				GetPlugin('Attributes');
				$this->_cachedStoryAttr = (new \attributes\Record('storyAttributes'));
			}

			$attr = $this->_cachedStoryAttr;
			$attributes = $attr->getValues($id, "MapStory.card");
			$this->_cachedStoryAttributes[$id]=$attributes;

		}

		return $this->_cachedStoryAttributes[$id];


	}


	/**
	 * these are only birth stories;
	 */
	public function listStories($limit=false){

		GetPlugin('Maps');

		$list=array();
		$prefix='attribute_';


		$filterBirthStories='{ 
				"filters":[{
					"field":"isBirthStory",
					"value":true
				}]
			}';

		$index=0;
		

		(new \spatial\AttributeFeatures('storyAttributes'))
			->withType('MapStory.card') //becuase attribute type is overriden
			->withAllAttributes($prefix)
			->withFilter($filterBirthStories)->iterate(function($result)use(&$list, $prefix, &$index, $limit){

				//result is not a metadata object

				if((!$limit)||$index>=$limit[0]&&$index<$limit[1]){
					$list[]=array('id'=>$result->id, 'features'=>array( $this->formatFeatureMetadata($result, $prefix)));
				}else{
					$list[]=array('id'=>$result->id,);
				}


				$index++;

			});

		return $list;

	}


	public function searchStories($keyword) {

		return (new \MapStory\CardSearch())->searchStories($keyword);


	}
	public function searchStoriesAdvanced($fields) {

		return (new \MapStory\CardSearch())->searchStoriesAdvanced($fields);

	}

	public function getFeatureListMetadata($featureIds) {

		GetPlugin('Maps');
		$list = array();

		$users = array();

		(new \spatial\Features())
			->listLayerFeatures($this->getStoryLayerId())
			->withFeatures($featureIds)
			->iterate(function ($feature) use (&$list, &$users) {

				$attributes = $this->getCachedStoryAttribute($feature['id']);

				$result = $this->formatFeatureMetadata($feature, $attributes);

				if (!key_exists($result['uid'], $users)) {
					$users[$result['uid']] = $this->getUsersMetadata($result['uid']);
				}

				$result['userData'] = $users[$result['uid']];

				$list[] = $result;

			});

		return $list;

	}

}