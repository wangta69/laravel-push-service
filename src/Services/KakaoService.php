<?php
//namespace Pondol\Push\Service;
namespace App\Http\Controllers\Push;

use App\Service\CryptoService;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

use DB;
//use App\Repositories\PushRepository;


class KaKaoService{

    protected $kakao_admin_key = "xxxxxxxxxxxxxxxxxxxxxxxx";
    protected $pushRepo;
    protected $userRepo;

    public function __construct(PushRepository $pushRepo, UserRepository $userRepo) {
        $this->pushRepo = $pushRepo;
        $this->userRepo = $userRepo;
    }


    /**
     * @param Array $params array(uuid,push_token,device_id,push_type)
     */
    public function register($params){

        //내부 데이타 베이스에 저장
        $this->pushRepo->store($params);

        //카톡에 날린다.
        $url    = "https://kapi.kakao.com/v1/push/register";
        $data   = array("uuid" => $params["uuid"], "device_id"=>$params["device_id"], "push_type"=>$params["push_type"], "push_token"=>$params["push_token"]);
        $res    = $this->post_request($url, $data);

        if($res["body"] == 30) return array('success'=>true, 'res'=>$res);
        else return array('success'=>false, 'res'=>$res);
     }

    /**
     * 푸시 토큰 삭제
     * @param Array $params array(uuid,device_id, push_type)
     */
    public function delete_token($params){
        //현재 데이타 베이스는 긴급 푸시를 위해 그대로 놓아 둔다.
        $url    =  "https://kapi.kakao.com/v1/push/deregister";
        $data   = array("uuid" => $params["uuid"], "device_id"=>$params["device_id"], "push_type"=>$params["push_type"]);
        $res    = $this->post_request($url, $data);
        return array('success'=>true, 'res'=>$res);
    }

    /**
     * 푸시 토큰 조회
     * @param Array $params array(uuid,device_id)
     */
    public function get_token($params){
        $url        = "https://kapi.kakao.com/v1/push/tokens?uuid=".$params["uuid"];
        $data       = array();
        $res        = $this->post_request($url, $data, "GET");

        //print_r($res["body"]);
        $header = substr($res["header"], 1).'"}';

        if($res["http_code"] == 200) return array('success'=>true, 'message'=>json_decode($res["body"]));
        else return array('success'=>false, 'message'=>json_decode($res["header"]));
    }


public function send_push($params){

        $uuids        = isset($params["uuids"]) ? $params["uuids"]:"";
        $msgType        = isset($params["msgType"]) ? $params["msgType"]:"";
        $title          = isset($params["title"]) ? $params["title"]:"";
        $message        = isset($params["message"]) ? $params["message"]:"";
        $pick           = isset($params["pick"]) ? $params["pick"]:"";
        $collapse_key    = isset($params["collapse_key"]) ? $params["collapse_key"]:"";


            //$final_params = array("msgType"=>$msgType, "title"=>$title, "message"=>$message, "pick"=>$pick, "collapse_key"=>$collapse_key);
        if(!is_array($uuids) || count($uuids) == 0)
            return false;

        Log::info('send_push finale uuid.', ['uuids' => $uuids]);
        //https://developers.kakao.com/docs/restapi#푸시-알림-푸시-토큰-등록
        //https://developers.google.com/cloud-messaging/concept-options
        $url        = "https://kapi.kakao.com/v1/push/send";


        $push_message =
            array(
                "for_apns"  =>
                    array(
                        "badge"                 => 1
                        ,"sound"                => "default"
                        ,"push_alert"           => true //음소거
                        ,"content-available"    => 1
                        ,"category"             => $msgType
                        ,"message"              => $message
                        ,"custom_field"         => array(
                                                    "msgType"       => $msgType
                                                    ,"title"        => $title
                                                    ,"message"      => $message
                                                    ,"pick"         => $pick
                                                    ,"notId"        => mt_rand()//collapse가 되지 않게 하기 위해 이 부분을 처리해 주어야 한다.
                                                    //,"msgcnt"     =>
                                                    //,"article_id" => $article_id
                                                    )
                    )
                ,"for_gcm"  =>
                    array(
                        "collapse"          => $collapse_key,
                        //"collapse"            => date("YmdHis")
                        //
                        "delay_while_idle"  => false
                        ,"time_to_live"     =>17200
                        ,"dry_run"          =>false
                        ,"priority"         =>"high"
                        ,"return_url"       =>"http://domain/api/v1/push/fail"
                        ,"custom_field"     => array(
                                                    "msgType"       => $msgType
                                                    ,"title"        => $title
                                                    ,"message"      => $message
                                                    ,"pick"         => $pick
                                                    ,"notId"        => mt_rand()//collapse가 되지 않게 하기 위해 이 부분을 처리해 주어야 한다.
                                                    //,"msgcnt"     =>
                                                    //,"article_id" => $article_id
                                                    )
                    )
            );

        $chunk  = array_chunk($uuids, 90);//uuid는 100까지 동시에 날릴 수 있다.
        $res                = "";
        $data               = "";
        $res["http_code"]   = "";
        $res["body"]        = "";
        foreach($chunk as $c_key=>$c_val){

            $data   = array("uuids" => '['.implode(",", $c_val).']', "push_message"=>json_encode($push_message));
            Log::info('send_push to kakao.', ['data' => $data]);
            $res    = $this->post_request($url, $data);

        }

        if($res["http_code"] == 200) return array('success'=>true);
        else return array('success'=>false, 'message'=>json_decode($res["body"]), 'data'=>$data);

    }



    private function post_request($url, $data=array(), $method="POST"){
        $this->headers = array(
            'Content-type: application/x-www-form-urlencoded;charset=UTF-8',
        //  'Cache-Control: no-cache',
        //  'Pragma: no-cache',
            'Authorization: KakaoAK '.$this->kakao_admin_key
        );

        $this->ch           = curl_init($url);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($this->ch, CURLOPT_HEADER, true);//헤더를 정상적으로 사용한다.
        //curl_setopt($this->ch, CURLOPT_HEADER, $CURLOPT_HEADER);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);

        if($method=="POST") {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($this->ch, CURLOPT_POST, 1);
        }

        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);//리턴 정보를 받기 위해 처리

        $response               = curl_exec($this->ch);

        $error = curl_error($this->ch);
        $result = array( 'header'       => '',
                        'body'          => '',
                        'curl_error'    => '',
                        'http_code'     => '',
                        'last_url'      => '');
        if ( $error != "" )
        {
            $result['curl_error'] = $error;
            return $result;
        }

        //Log::info('kakao send_push request.', ['params' => $params]);
        Log::info('result for send_push to kakao.', ['response' => $response]);
        $header_size            = curl_getinfo($this->ch,CURLINFO_HEADER_SIZE);

        $result['header']       = substr($response, 0, $header_size);
        $result['body']         = substr( $response, $header_size );
        $result['http_code']    = curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
        $result['last_url']     = curl_getinfo($this->ch,CURLINFO_EFFECTIVE_URL);
        return $result;

    }


    private function get_request($url, $data, $method="POST"){
        $options = array(
            'ssl'=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: KakaoAK ".$this->kakao_admin_key."\r\n",
                'method'  => $method,
                'content' => http_build_query($data),
            ),
        );
        $context  = stream_context_create($options);

        var_dump($context);
        $result = file_get_contents($url, false, $context);

        var_dump($result);
        if ($result === FALSE) { // Handle error
            }
        return $result;

    }


}
