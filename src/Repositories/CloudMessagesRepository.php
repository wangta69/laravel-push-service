<?php
namespace App\Http\Controllers\Push\Repositories;

use App\Http\Controllers\Push\Models\CloudMessages;
use DB;
class CloudMessagesRepository {
	protected $model;
	public function __construct(CloudMessages $messages)
	{
		$this->model = $messages;
	}

	/**
	 * @param
	 */
	public function get_model(){
		return $this->model;
	}

	/**
     * 신규 생성(sample)
     * @param Array $params array(uuid, device_id, push_type, push_token)
     */
    public function store($params){

        $push               = $this->model->firstOrNew(['uuid' => $params["uuid"]]); //,'device_id'=> $params["device_id"]   사용자별 하나의 device만 세팅한다.
        $push->uuid         = $params["uuid"];
        $push->device_id    = $params["device_id"];
        $push->push_type    = $params["push_type"];
        $push->push_token   = $params["push_token"];
        
        $push->save();

        return ;
    }
    
    public function retriveAlltokens(){
        return $this->model->pluck('push_token');
    }
}
