<?php
namespace sanex\filter\components;

use Yii;
use yii\base\Exception;
use yii\data\ActiveDataProvider;

abstract class FilterData
{
	//init properties
	protected $filter,
			$model,
			$query,
			$setDataProvider;

	//class properties
	protected $data,
			$getParams = [],
			$limit,
			$offset,
			$orderBy,
			$sort,
			$where = [];

	//default limit value for custom or ActiveDataProvider pagination
	const QUERY_LIMIT = 50;		

	public function __construct(Array $properties = [])
	{
		foreach($properties as $key => $value){
			if (property_exists($this, $key)) {
				$this->{$key} = $value;	
			} else {
				throw new Exception("Invalid filter object property", 1);
			}
		}

		if (!$this->model) 
			throw new Exception("Missing model property", 1);

		//set data
		$this->setWhereArray()->setQuery()->setData();
	}

	public function getData()
	{
		return $this->data;
	}

	abstract protected function setWhereArray();

	private function setQuery()
	{
		$query = $this->query ? clone $this->query : $this->model->find();
		if ($query->where)
		   $this->where = array_merge_recursive($query->where, $this->where); 

		$this->limit = $query->limit ? $query->limit : self::QUERY_LIMIT;
		$this->offset = $this->setDataProvider ? null : 
							($query->offset ? $query->offset :
								(Yii::$app->request->get('page') <= 1 ? 0 : 
									(Yii::$app->request->get('page') - 1) * $this->limit));				
		$this->orderBy = $this->setDataProvider ? null : ($query->orderBy ? $query->orderBy : null); 
		$this->sort = $query->orderBy; //set $this->sort property for dataProvider sorting													

		//build final query
		$this->query = $query->where($this->where)->limit($this->limit)->offset($this->offset)->orderBy($this->orderBy);
		return $this;
	}

	private function setData()
	{
		//set properties array for dataProvider
		$dpProps = ['query' => $this->query, 'pagination' => ['pageSize' => $this->limit]];

		//set dataProvider sorting based on ActiveQuery orderBy() method.
		//sorting based only on first orderBy() parameter
		if ($this->sort)
			$dpProps['sort'] = ['defaultOrder' => [array_keys($this->sort)[0] => SORT_ASC]];

		//set data
		$this->data = $this->setDataProvider ? new ActiveDataProvider($dpProps) : $this->query->all();
	}
}	