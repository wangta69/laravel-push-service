<?php
//namespace Pondol\Push\Service;
namespace App\Http\Controllers\Push\Services;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;

use App\Http\Controllers\Push\Repositories\CloudMessagesRepository;


use DB;


class FcmService{

    protected $cloudMessagesRepo;

    public function __construct(CloudMessagesRepository $cloudMessagesRepo) {
        $this->cloudMessagesRepo       = $cloudMessagesRepo;
    }




    /**
     * @param Array $params array(uuid,push_token,device_id,push_type)
     * $fcm_params = array("app" => "aa", "push_tokens"=>$push_tokens, "msgType"=>$msgType, "title"=>$title, "body"=>$message, "pick"=>$pick, "collapse_key"=>$collapse_key);
     */
    public function messageToDevice($params){

        $title  = $params["title"];
        $body   = $params["body"];
        $uuid   = isset($params["uuid"]) ? $params["uuid"]:"";
        $tokens = $params["tokens"];
        
        $add_data   = isset($params["add_data"]) ? $params["add_data"]:[];

        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);
        $option = $optionBuilder->build();
        
        $notificationBuilder = new PayloadNotificationBuilder($title);
        $notificationBuilder->setBody($body)
                            ->setSound('default');
        $notification = $notificationBuilder->build();
        
        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData($add_data);
        $data = $dataBuilder->build();

        $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);//if you want differnt key config
        //($to, Options $options = null, PayloadNotification $notification = null, PayloadData $data = null, $server_key = null, $sender_id = null)

        $downstreamResponse->numberSuccess();
        $downstreamResponse->numberFailure();
        $downstreamResponse->numberModification();

        //return Array - you must remove all this tokens in your database
        $this->cloudMessagesRepo->tokensToDelete($downstreamResponse->tokensToDelete());

        //return Array (key : oldToken, value : new token - you must change the token in your database )
        $this->cloudMessagesRepo->tokensToModify($downstreamResponse->tokensToModify());

        //return Array - you should try to resend the message to the tokens in the array
//      $downstreamResponse->tokensToRetry();

        // return Array (key:token, value:errror) - in production you should remove from your database the tokens
    }

    public function messageToDevices($params){
        $title  = $params["title"];
        $body   = $params["body"];

        //$server_key   = isset($params["server_key"]) ? $params["server_key"]:null;
        //$sender_id   = isset($params["sender_id"]) ? $params["sender_id"]:null;

        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);
        $option = $optionBuilder->build();
        
        $notificationBuilder = new PayloadNotificationBuilder($title);
        $notificationBuilder->setBody($body)
                            ->setSound('default');
        $notification = $notificationBuilder->build();
        
        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['a_data' => 'my_data']);
        $data = $dataBuilder->build();

        $tokens = $this->cloudMessagesRepo->retriveAlltokens();


        $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);//, $server_key, $sender_id

        //return Array - you must remove all this tokens in your database
        $this->cloudMessagesRepo->tokensToDelete($downstreamResponse->tokensToDelete());

        //return Array (key : oldToken, value : new token - you must change the token in your database )
        $this->cloudMessagesRepo->tokensToModify($downstreamResponse->tokensToModify());

        //return Array - you should try to resend the message to the tokens in the array
        $downstreamResponse->tokensToRetry();

        // return Array (key:token, value:errror) - in production you should remove from your database the tokens present in this array
        $downstreamResponse->tokensWithError();
    }

    /**
     * 사용자가 push 서비스를 해제하는 경우 강제 삭제
     * @param Array $params array(push_token, app)
     */
    public function delete_token($params){
        $this->cloudMessagesRepo->tokensToDelete([$params["push_token"]]);
        return array('success'=>true);
    }

}
