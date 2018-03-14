<?php
namespace App\Http\Controllers\Push;

use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Response;

use App\Http\Controllers\Push\Repositories\CloudMessagesRepository;
use App\Http\Controllers\Push\Services\FcmService;


class PushFcmController extends Controller
{

    protected $fcmSvc;
    protected $cloudMessagesRepo;

    function __construct(FcmService $fcmSvc, CloudMessagesRepository $cloudMessagesRepo)
    {
        $this->fcmSvc   = $fcmSvc;
        $this->cloudMessagesRepo   = $cloudMessagesRepo;
    }


    /**
     * /push/register_graphgame_test
     */
    public function test(Request $request){

        $param["title"] = "테스트";
        $param["body"]  = "오픈 하였습니다.";
        $param["uuid"]  = 84;
        $param["tokens"]  = 'aaaaaaaaaaaa';
        
        $this->fcmSvc->messageToDevice($param);


        return Response::json(['result'=>true, "code"=>"000", 'message' => ''], 200);

    }

    /**
     * 개별로 푸쉬 보내기
     */
    public function sendMessage(Request $request){

        $param["title"] = $request["title"];
        $param["body"]  = $request["body"];
        $param["uuid"]  = $request["uuid"];
        $param["tokens"]  = $request["tokens"];
        
        $this->fcmSvc->messageToDevice($param);
        return Response::json(['result'=>true, "code"=>"000", 'message' => ''], 200);
    }

    /**
     * 전체 사용자에게 푸쉬 보내기
     */
    public function sendMessages(Request $request){

        $param["title"] = $request["title"];
        $param["body"]  = $request["body"];

        $this->fcmSvc->messageToDevices($params);
        return Response::json(['result'=>true, "code"=>"000", 'message' => ''], 200);
    }

    /**
     * push key register
     * @param String $push_token : 토큰 값
     * @param String $device_id : 디바이스 아이디
     * @param String $push_type : gcm or apns
     */
     public function register(Request $request){

        $user       = Auth::user();

        $params["uuid"]         = $user->id;

        $params["push_token"]   = $request->push_token;
        $params["device_id"]    = $request->device_id;
        $params["push_type"]    = $request->push_type;

        $this->cloudMessagesRepo->store($params);
        return Response::json(['result'=>true, 'code'=>'000'], 200);
     }

    /**
     * 푸시 토큰 삭제
     * @param POST device_id
     */
    public function delete_token(Request $request){
            $result = $this->fcmSvc->delete_token($params);
    }

    /**
     * 푸시 토큰 조회
     * /get-token?uuid=84
     */
    public function get_token(Request $request){

    }

}
