//for push
Route::post('/push/register', array('uses'=>'Push\PushKakaoController@register'))->middleware('jwt.auth');//푸쉬 토큰 등록
Route::post('/push/register', array('uses'=>'Push\PushFcmController@register'))->middleware('jwt.auth');//푸쉬 토큰 등록

Route::post('/push/send-push', array('uses'=>'Push\PushKakaoController@send_push'));//일반적인 푸쉬 알림
Route::post('/push/fail', array('uses'=>'Push\PushKakaoController@fail'));//카카오에서 전송 실패시
Route::post('/push/delete-token', array('uses'=>'Push\PushKakaoController@delete_token'))->middleware('jwt.auth');//
Route::get('/push/delete-token', array('uses'=>'Push\PushKakaoController@delete_token'))->middleware('jwt.auth');//for test
Route::get('/push/get-token', array('uses'=>'Push\PushKakaoController@get_token'));
