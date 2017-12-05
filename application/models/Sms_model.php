<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sms_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    //COMMON FUNCTION FOR SENDING SMS
    function send_sms($message = '' , $reciever_phone = '')
    {
        $active_sms_service = $this->db->get_where('settings' , array(
            'type' => 'active_sms_service'
        ))->row()->description;
        if ($active_sms_service == '' || $active_sms_service == 'disabled')
            return;

        if ($active_sms_service == 'kavenegar'){
            $this->send_sms_via_kavenegar($message, $reciever_phone);
        }
        if ($active_sms_service == 'clickatell') {
            $this->send_sms_via_clickatell($message , $reciever_phone );
        }
        if ($active_sms_service == 'twilio') {
            $this->send_sms_via_twilio($message , $reciever_phone );
        }
        if ($active_sms_service == 'msg91') {
            $this->send_sms_via_msg91($message , $reciever_phone );
        }
    }

    // SEND SMS VIA KAVENEGAR API
    function send_sms_via_kavenegar($message = '', $reciever_phone = '') {
        log_message('error', 'into sms function');
        require '../../vendor/autoload.php';
        show_error('into sms function');
        try{
            show_error('into try exception function');
            $api = new \Kavenegar\KavenegarApi( "744B4F6A776D6254576C323434344A304661775334773D3D" );
            show_error('after create class');
            $sender = "100065995";
            $message = "خدمات پیام کوتاه کاوه نگار";
            $receptor = "09023206232";
            $result = $api->Send($sender,$receptor,$message) or die('send sms error');
            show_error('after send sms api');
            if($result){
                foreach($result as $r){
                    echo "messageid = $r->messageid";
                    echo "message = $r->message";
                    echo "status = $r->status";
                    echo "statustext = $r->statustext";
                    echo "sender = $r->sender";
                    echo "receptor = $r->receptor";
                    echo "date = $r->date";
                    echo "cost = $r->cost";

                }
            }
        }
        catch(\Kavenegar\Exceptions\ApiException $e){
            // در صورتی که خروجی وب سرویس 200 نباشد این خطا رخ می دهد
            echo $e->errorMessage();
        }
        catch(\Kavenegar\Exceptions\HttpException $e){
            // در زمانی که مشکلی در برقرای ارتباط با وب سرویس وجود داشته باشد این خطا رخ می دهد
            echo $e->errorMessage();
        }
    }

    // SEND SMS VIA CLICKATELL API
    function send_sms_via_clickatell($message = '' , $reciever_phone = '') {

        $clickatell_user       = $this->db->get_where('settings', array('type' => 'clickatell_user'))->row()->description;
        $clickatell_password   = $this->db->get_where('settings', array('type' => 'clickatell_password'))->row()->description;
        $clickatell_api_id     = $this->db->get_where('settings', array('type' => 'clickatell_api_id'))->row()->description;
        $clickatell_baseurl    = "http://api.clickatell.com";

        $text   = urlencode($message);
        $to     = $reciever_phone;

        // auth call
        $url = "$clickatell_baseurl/http/auth?user=$clickatell_user&password=$clickatell_password&api_id=$clickatell_api_id";

        // do auth call
        $ret = file($url);

        // explode our response. return string is on first line of the data returned
        $sess = explode(":",$ret[0]);
        print_r($sess);echo '<br>';
        if ($sess[0] == "OK") {

            $sess_id = trim($sess[1]); // remove any whitespace
            $url = "$clickatell_baseurl/http/sendmsg?session_id=$sess_id&to=$to&text=$text";

            // do sendmsg call
            $ret = file($url);
            $send = explode(":",$ret[0]);
            print_r($send);echo '<br>';
            if ($send[0] == "ID") {
                echo "successnmessage ID: ". $send[1];
            } else {
                echo "send message failed";
            }
        } else {
            echo "Authentication failure: ". $ret[0];
        }
    }


    // SEND SMS VIA TWILIO API
    function send_sms_via_twilio($message = '' , $reciever_phone = '') {

        // LOAD TWILIO LIBRARY
        require_once(APPPATH . 'libraries/twilio_library/Twilio.php');


        $account_sid    = $this->db->get_where('settings', array('type' => 'twilio_account_sid'))->row()->description;
        $auth_token     = $this->db->get_where('settings', array('type' => 'twilio_auth_token'))->row()->description;
        $client         = new Services_Twilio($account_sid, $auth_token);

        $client->account->messages->create(array(
            'To'        => $reciever_phone,
            'From'      => $this->db->get_where('settings', array('type' => 'twilio_sender_phone_number'))->row()->description,
            'Body'      => $message
        ));

    }

    //SMS via msg91
    function send_sms_via_msg91($message = '' , $reciever_phone = '') {

        $authKey       = $this->db->get_where('settings', array('type' => 'msg91_authentication_key'))->row()->description;
        $senderId      = $this->db->get_where('settings', array('type' => 'msg91_sender_ID'))->row()->description;
        $country_code  = $this->db->get_where('settings', array('type' => 'msg91_country_code'))->row()->description;
        $route         = $this->db->get_where('settings', array('type' => 'msg91_route'))->row()->description;

        //Multiple mobiles numbers separated by comma
        $mobileNumber = $reciever_phone;

        //Your message to send, Add URL encoding here.
        $message = urlencode($message);

        //Prepare you post parameters
        $postData = array(
            'authkey' => $authKey,
            'mobiles' => $mobileNumber,
            'message' => $message,
            'sender' => $senderId,
            'route' => $route,
            'country' => $country_code
        );
        //API URL
        $url="http://api.msg91.com/api/sendhttp.php";

        // init the resource
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData
            //,CURLOPT_FOLLOWLOCATION => true
        ));


        //Ignore SSL certificate verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);


        //get response
        $output = curl_exec($ch);

        //Print error if any
        if(curl_errno($ch))
        {
            echo 'error:' . curl_error($ch);
        }

        curl_close($ch);
    }
}
