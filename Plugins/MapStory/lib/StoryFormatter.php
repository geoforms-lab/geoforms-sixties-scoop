<?php

namespace MapStory;

class StoryFormatter {

	protected $makeChanges = false;

	protected $hasBirthStory = false;
	protected $indexOfBirthStory = -1;

	protected $hasAdoptionStory = false;
	protected $userId=-1;

	protected $userProfile;


	protected $enableAdoptionStories=false;

	public function setCommitChanges($bool) {
		$this->makeChanges = true;
		return $this;
	}
	public function forUser($id) {
		$this->userId = $id;

		$this->userProfile = (new \attributes\Record('profileAttributes'))->getValues($this->userId, "user");

		return $this;
	}

	protected function hasAnyYoutubeVideo($list, $profile){

		$media=array_map(function($item, $list){
			return $item[$feature['attributes']['locationImages']];
		});
		$media[]=$profile['birthFamilyImages'];

		$media=implode(' ', $media);

		return strpos($media, 'youtube.com')!==false||strpos($media, 'youtu.be')!==false;

	}

	public function format($list) {

		$hasRepatriationStory = false;

		$indexOfBirth = -1;

		$list = $this->sort($list);


		$hasAnyVideo=$this->hasAnyYoutubeVideo($list, $this->userProfile);

		foreach ($list as $index => &$feature) {

			$attributes = $feature['attributes'];
			$attributesOriginal=$attributes;

			$attributes['hasStoryVideos']=$hasAnyVideo;


			if ($this->shouldBeBirthStory($attributes, $index)) {
				$attributes = $this->setAsBirthStory($attributes, $index);
			}else{
				$attributes = $this->clearBirthStory($attributes);
			}

			if ($this->shouldBeAdoptionStory($attributes, $index)) {
				$attributes = $this->setAsAdoptionStory($attributes, $index);			
			}else{
				$attributes = $this->clearAdoptionStory($attributes);
			}


			if ($this->isRepatriationStory($attributes)) {

				if (!$hasRepatriationStory) {
					$hasRepatriationStory = true;
				
				}else{
					$attributes['isRepatriationStory'] = false;
				}

			}

			if($this->userId>0){
				if(intval($attributes['storyUser'])!=$this->userId){
					
					$attributes['storyUser']=$this->userId;
				}
				
			}
			
			if($this->makeChanges){
				$updates=array();
				foreach($attributes as $key=>$value){
					if((!is_bool($value))&&$value!==$attributesOriginal[$key]){
						$updates[$key]=$value;
					}

					if(is_bool($value)&&($value?"true":"false")!==$attributesOriginal[$key]){
						$updates[$key]=$value;
					}
				}
				if(!empty($updates)){
					//error_log(json_encode($updates));
					//error_log(json_encode($attributesOriginal));
					\core\DataStorage::LogQuery("Update Story: ".json_encode($updates));
					(new \attributes\Record('storyAttributes'))->setValues($feature['id'], "MapStory.card", $updates);
				}
			}


			$countryA=$this->getCountry($attributes['locationData']);
			$provinceA=$this->getProvince($attributes['locationData']);
			$attributes['country']=$countryA;
			$attributes['province']=$provinceA;


			$feature['attributes'] = $attributes;
		}

		for ($i = 0; $i < count($list) - 1; $i++) {

			$currentNext=$list[$i]['attributes']['nextLocationData'];
			$currentNextJson=gettype($currentNext)=='string'?$currentNext:json_encode($currentNext);
			
			$nextValue=$list[$i + 1]['attributes']['locationData'];
			$nextValueJson=json_encode($nextValue);


			if($currentNextJson !== $nextValueJson) {

				
				//apply locally
				$list[$i]['attributes']['nextLocationData'] = $nextValue;


				\core\DataStorage::LogQuery("Update nextLocationData ".gettype($currentNext).md5($currentNextJson).' <- '.gettype($nextValue).md5($nextValueJson));

				(new \attributes\Record('storyAttributes'))->setValues($list[$i]['id'], "MapStory.card", array(
					"nextLocationData" => json_encode($nextValue),
				));

			}



			//if ($list[$i]['attributes']['country'] !== $list[$i + 1]['attributes']['country']) {
			$movesOutOfCountryValue = ($list[$i]['attributes']['country'] !== $list[$i + 1]['attributes']['country']);
			$movesOutOfCountryCurrent = $list[$i]['attributes']['movesOutOfCountry'];
			if ($movesOutOfCountryCurrent!=$movesOutOfCountryValue&&$movesOutOfCountryCurrent!=($movesOutOfCountryValue?'true':'false')) {

				\core\DataStorage::LogQuery("Update movesOutOfCountry ".$movesOutOfCountryCurrent."!=".$movesOutOfCountryValue);

				(new \attributes\Record('storyAttributes'))->setValues($list[$i]['id'], "MapStory.card", array(
					"movesOutOfCountry" => $movesOutOfCountryValue
				));
			}


			$movesOutOfProvinceValue = ($list[$i]['attributes']['country']==="CA"&&$list[$i]['attributes']['province'] !== $list[$i + 1]['attributes']['province']);
			$movesOutOfProvinceCurrent = $list[$i]['attributes']['movesOutOfProvince'];
			if ($movesOutOfProvinceCurrent!=$movesOutOfProvinceValue&&$movesOutOfProvinceCurrent!=($movesOutOfProvinceValue?'true':'false')) {

				\core\DataStorage::LogQuery("Update movesOutOfProvince ".$movesOutOfProvinceCurrent."!=".$movesOutOfProvinceValue);

				(new \attributes\Record('storyAttributes'))->setValues($list[$i]['id'], "MapStory.card", array(
					"movesOutOfProvince" => $movesOutOfProvinceValue
				));
			}
			
		}






		return $list;

	}


	protected function getCountry($locationData){

		if(key_exists('geocode', $locationData)){
			$locationData=$locationData->geocode;
		}

		if(key_exists('address_components', $locationData)){
			foreach ($locationData->address_components as $addressPart) {
				if(key_exists('types', $addressPart)&&in_array('country',  $addressPart->types)){
					return $addressPart->short_name;
				}
			}
		}

		return '--';

	}

	protected function getProvince($locationData){


		if(key_exists('geocode', $locationData)){
			$locationData=$locationData->geocode;
		}

		if(key_exists('address_components', $locationData)){
			foreach ($locationData->address_components as $addressPart) {
				if(key_exists('types', $addressPart)&&in_array('administrative_area_level_1',  $addressPart->types)){
					return $addressPart->short_name;
				}
			}
		}

		return '--';

	}

	protected function clearBirthStory($attributes) {
		$attributes['isBirthStory'] = false;
		return $attributes;
	}
	protected function setAsBirthStory($attributes, $index) {

		if ($this->hasBirthStory) {
			throw new \Exception('Already has birth story');
		}

		$this->hasBirthStory = true;
		$this->indexOfBirth = $index;





		$attributes['isRepatriationStory'] = false;
		$attributes['isAdoptionStory'] = false;
		$attributes['isBirthStory'] = true;


	

		return $attributes;

	}

	protected function isRepatriationStory($attributes) {
		return $attributes['isRepatriationStory'] === "true" || $attributes['isRepatriationStory'] === true;
	}

	protected function shouldBeBirthStory($attributes, $index) {
		if($this->hasBirthStory){
			return false;
		}
		return $this->isBirthStory($attributes, $index);
	}
	protected function isBirthStory($attributes, $index) {
		return $attributes['isBirthStory'] === "true" || $attributes['isBirthStory'] === true;
	}

	protected function shouldBeAdoptionStory($attributes, $index) {


		if(!$this->enableAdoptionStories){
			return false;
		}

		if($this->hasAdoptionStory){
			return false;
		}
		if ($this->hasBirthStory && $this->indexOfBirth < $index&&(!$this->isRepatriationStory($attributes))) {
			return true;
		}
		return false;

		
	}

	protected function clearAdoptionStory($attributes) {
		$attributes['isAdoptionStory'] = false;
		return $attributes;

	}
	protected function setAsAdoptionStory($attributes, $index) {

		if($this->hasAdoptionStory){
			throw new \Exception('Already has adoption story');
		}
		
		$this->hasAdoptionStory = true;
		
		$attributes['isRepatriationStory'] = false;
		$attributes['isBirthStory'] = false;
		$attributes['isAdoptionStory'] = true;
		return $attributes;
		
	}

	protected function isAdoptionStory($attributes) {
		return $attributes['isAdoptionStory'] === "true" || $attributes['isAdoptionStory'] === true;
	}

	protected function sort($list) {

		usort($list, function ($a, $b) {

			return strtotime($a['attributes']['locationDate']) - strtotime($b['attributes']['locationDate']);
		});

		return $list;
	}
}