<?php

namespace Increment\Common\Notification\Http;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Increment\Account\Models\Account;
use Increment\Common\Notification\Models\Notification;
use App\Http\Controllers\APIController;
use App\Jobs\Notifications;
class NotificationController extends APIController
{
    function __construct(){
      $this->model = new Notification();
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $result = Notification::where('to', '=', $data['account_id'])->orderBy('created_at', 'desc')->get();
      $size = 0;
      $flag = false;
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          if($flag == false && $result[$i]['updated_at'] == null){
            $size++;
          }else if($flag == false && $result[$i]['updated_at'] != null){
            $flag = true;
          }
          $result[$i] = $this->manageResult($result[$i]);
          $i++;
        }
      }
      return response()->json(array(
        'data' => sizeof($result) > 0 ? $result : null,
        'size' => $size
      ));
    }

    public function manageResult($result){
        $result['account'] = $this->retrieveAccountDetails($result['from']);
        $result['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result['created_at'])->copy()->tz('Asia/Manila')->format('F j, Y h:i A');
        if($result['payload'] == 'guarantor'){
          $result['title'] = 'Guarantor Notification';
          $result['description'] = 'You have been assigned as guarantor by '.$result['account']['username'];
        }else if($result['payload'] == 'comaker'){
          $result['title'] = 'Comaker Notification';
          $result['description'] = 'You have been assigned as comaker by '.$result['account']['username'];
        }else if($result['payload'] == 'mail'){
          $result['title'] = 'Mail Notification';
          $result['description'] = 'An email has been sent to your email address';
        }else if($result['payload'] == 'invest'){
          $result['title'] = 'Investment Notification';
          $result['description'] = 'You have received a new investment from'.$result['account']['username'];
        }else{
          //
        }
        return $result;
    }

    public function update(Request $request){
      $data = $request->all();
      Notification::where('id', '=', $data['id'])->update(array(
        'updated_at' => Carbon::now()
      ));
      $this->response['data'] = true;
      return $this->response();
    }

    public function createByParams($parameter){
      $model = new Notification();
      $model->from = $data['from'];
      $model->to = $data['to'];
      $model->payload = $data['payload'];
      $model->payload_value = $data['payload_value'];
      $model->route = $data['route'];
      $model->created_at = $data['created_at'];
      $model->save();
      $result = Notification::where('id', '=', $model->id)->get();
      $result = $this->manageResult($result[0]);
      // Notifications::dispatch('notification', $result[0]);
      return true;
    }
}