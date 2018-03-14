<?php
//namespace Pondol\Push;
namespace App\Http\Controllers\Push;
use App\Http\Controllers\Controller;

use Validator;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use App\Service\CryptoService;

//use App\Service\FcmService;
use App\Service\PushKaKaoService;
use App\Repositories\UserRepository;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

//use DB;
use Response;
//use Carbon\Carbon;

//use App\Repositories\PushRepository;


/**
 * 기존 kakao만을 pushServer로 사용하던 방식은
 * 1차적으로 fcm과 혼용으로 사용하고
 * 추후는 fcm 단독으로 구축한다.
 */

class PushController extends Controller
{
    protected $pushRepo;
    protected $userRepo;
    protected $pushSvc;

    function __construct(UserRepository $userRepo, PushRepository $pushRepo, PushKaKaoService $pushSvc)
    {
        //parent::__construct();
        $this->userRepo   = $userRepo;
        $this->pushRepo   = $pushRepo;
        $this->pushSvc    = $pushSvc;
    }


    /**
     * push key register
     * @param String $push_token : 토큰 값
     * @param String $device_id : 디바이스 아이디
     * @param String $push_type : gcm or apns
     */
     public function register(Request $request){

        $user       = Auth::user();

        $params["uuid"]         = $user->mb_no;

        $params["push_token"]   = $request->push_token;
        $params["device_id"]    = $request->device_id;
        $params["push_type"]    = $request->push_type;


        $result = $this->pushSvc->register($params);


        return Response::json($result, 200);
     }

    /**
     * 푸시 토큰 삭제(추후 삭제는 push_token 단일 키로 처리한다.)
     * @param POST device_id
     */
    public function delete_token(Request $request){
        $user       = Auth::user();

        $params["uuid"]         = $user->mb_no;
        $params["device_id"]    = $request->device_id;
        $params["push_token"]  = $request->push_token;

        $row = $this->pushRepo->get_model()->where('device_id',$params["device_id"])->where('uuid',$params["uuid"])->select('push_type')->first();

        if(!$row)
            return array('success'=>false);

        $params["push_type"]  = $row->push_type;



        $result = $this->pushSvc->delete_token($params);



        return Response::json($result, 200);
    }

    /**
     * 푸시 토큰 조회
     * /push/get-token?uuid=84
     */
    public function get_token(Request $request){
        //$user     = Auth::user();
        //$params["uuid"]       = $user->mb_no;
        $params["uuid"] = $request->uuid;
        $result = $this->pushSvc->get_token($params);
        return Response::json($result, 200);
    }


    /**
     * 푸시 알림 보내기(특정 회원 및 전체에게 보내기)
     *
     * https://developers.google.com/cloud-messaging/http-server-ref
     *
     */
    public function send_push_admin(Request $request){

        $crypto = new CryptoService();

        $enc_data   = $request->enc_data;
        $requested  = json_decode($crypto->decrypt($enc_data));

        $params["msgType"]      = $requested->article_type;
        $params["uuid"]         = $requested->uuid;
        $params["msg"]          = $requested->message;
        $params["title"]        = $requested->title;//for gcm


        $result = $this->pre_send_push($params);
        //$result = $this->pushSvc->send_push($params);
        return Response::json($result, 200);
    }

    /**
     * @param
     */
    public function send_push(Request $request){

        Log::info('send_push request.', ['request' => $request->all()]);

        $params["msgType"]      = $request->msgType;
        $params["uuid"]         = $request->input('uuid', 'all');
        $params["msg"]          = $request->input('msg', '');
        $params["title"]        = $request->input('title', '');//다리다리 용
        $params["pick"]         = $request->input('pick');
        $params["mb_id"]        = $request->input('mb_id', '');

        $result = $this->pre_send_push($params);
        //$result = $this->pushSvc->send_push($params, $request);
        return Response::json($result, 200);

    }

    /**
     * send push 전처리
     *
     */
    private function pre_send_push($params){
        //Log::info('kakao send_push request.', ['request' => $request->all()]);
        Log::info('pre_send_push.', ['params' => $params]);


        $msgType        = $params["msgType"];
        $uuid           = $params["uuid"];
        $msg            = $params["msg"];
        $mb_id          = isset($params["mb_id"]) ? $params["mb_id"]:'';//feeder, patternmatch
        $f_mb_nick      = isset($params["f_mb_nick"]) ? $params["f_mb_nick"]:'';//feeder, patternmatch
        $pick           = isset($params["pick"]) ? $params["pick"]:'';//

        $collapse_key   = $msgType;



        $title      = isset($params["title"]) ? $params["title"]:'';//관리자, patternmatch 일경우
        $uuids      = [];
        $push_tokens    = [];

        $message  = $msg;



       return $rtn;
    }

/**
     * 전송 허용 가능한 시간인지를 체크
     */
    private function enable_push_time($set){
        $time = date("H:i");
        $set->enableTime = isset($set->enableTime) ? $set->enableTime : "off";
        if($set->enableTime == 'on'){
            if($time < $set->Stimer)
                return false;
            if($time > $set->Etimer)
                return false;

            return true;
        }else{
            return true;
        }

    }
    /**
     *  카카오에서 전송실패인경우 리턴 받음
     */
    public function fail(Request $request){

        $userId     = $request->userId;
        $pushToken  = $request->pushToken;
        $date       = $request->date;

        //현재 database에서 삭제
        $this->pushRepo->get_model()->where('uuid', $userId)->where('push_token', $pushToken)->delete();

        return;
    }
}
