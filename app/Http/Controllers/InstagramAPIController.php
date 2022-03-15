<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InstagramAPIController extends Controller
{


    public function ua_lastest ($sign_version = '204.0.0.30.119'){
        $resolusi = array('1080x1776','1080x1920','720x1280', '320x480', '480x800', '1024x768', '1280x720', '768x1024', '480x320');
        $versi = array('SM-A102U', 'SM-G955U', 'SM-G960U', 'SM-N975U');
        $dpi = array('420','380','480');
        $ver = $versi[array_rand($versi)];
        return 'Instagram '.$sign_version.' Android ('.mt_rand(10,11).'/'.mt_rand(1,3).'.'.mt_rand(3,5).'.'.mt_rand(0,5).'; '.$dpi[array_rand($dpi)].'; '.$resolusi[array_rand($resolusi)].'; samsung; '.$ver.'; '.$ver.'; smdkc210; en_US)';
    }

    public function request($ighost, $useragent, $url, $cookie = 0, $data = 0, $httpheader = array(), $proxy = 0){
        $url = $ighost ? 'https://i.instagram.com/api/v1/' . $url : $url;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL , 1);
        //Set the proxy IP.
        if($proxy) curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if($httpheader)curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        if($cookie) curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        if ($data):
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        endif;
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch);
        if(!$httpcode) return false; else{
            $header = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
            $body = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
            curl_close($ch);
            return array($header, $body);
        }
    }

    public function uamulti($sign_version = '6.22.0'){
        $resolusi = array('1080x1776','1080x1920','720x1280', '320x480', '480x800', '1024x768', '1280x720', '768x1024', '480x320');
           $versi = array('GT-N7000', 'SM-N9000', 'GT-I9220', 'GT-I9100');		$dpi = array('120', '160', '320', '240');
           $ver = $versi[array_rand($versi)];
           return 'Instagram '.$sign_version.' Android ('.mt_rand(10,11).'/'.mt_rand(1,3).'.'.mt_rand(3,5).'.'.mt_rand(0,5).'; '.$dpi[array_rand($dpi)].'; '.$resolusi[array_rand($resolusi)].'; samsung; '.$ver.'; '.$ver.'; smdkc210; pt_BR)';
           }

    public function generateDeviceId($seed){
        $volatile_seed = filemtime(__DIR__);
        return 'android-'.substr(md5($seed.$volatile_seed), 16);
    }
    public function hook($data){
        $hash = hash_hmac('sha256', $data, '673581b0ddb792bf47da5f9ca816b613d7996f342723aa06993a3f0552311c7d');
        return 'ig_sig_key_version=4&signed_body='.$hash.'.'.urlencode($data);
    }
    public function generate_useragent($sign_version = '42.0.0.19.95'){
        $resolusi = array('1080x1776','1080x1920','720x1280', '320x480', '480x800', '1024x768', '1280x720', '768x1024', '480x320');
        $versi = array('GT-N7000', 'SM-N9000', 'GT-I9220', 'GT-I9100');
        $dpi = array('120', '160', '320', '240');
        $ver = $versi[array_rand($versi)];
        return 'Instagram '.$sign_version.' Android ('.mt_rand(10,11).'/'.mt_rand(1,3).'.'.mt_rand(3,5).'.'.mt_rand(0,5).'; '.$dpi[array_rand($dpi)].'; '.$resolusi[array_rand($resolusi)].'; samsung; '.$ver.'; '.$ver.'; smdkc210; en_US)';
    }
    public function get_csrftoken(){
        $fetch = $this->request('si/fetch_headers/', null, null);
        $header = $fetch[0];
        if (!preg_match('#Set-Cookie: csrftoken=([^;]+)#', $header, $match)) {
            return json_encode(array('result' => false, 'content' => 'Missing csrftoken'));
        } else {
            return $match[0];
        }
    }
    public function generateUUID($type){
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        return $type ? $uuid : str_replace('-', '', $uuid);
    }


    public function instagram_login($post_username, $post_password,$proxy){
        $postq = json_encode([
            'phone_id' => $this->generateUUID(true),
            '_csrftoken' => $this->get_csrftoken(),
            'username' => $post_username,
            'guid' => $this->generateUUID(true),
            'device_id' => $this->generateUUID(true),
            'password' => $post_password,
            'login_attempt_count' => 0
        ]);
        $a = $this->request(1, $this->generate_useragent(), 'accounts/login/', 0, $this->hook($postq),array(),$proxy);
        $header = $a[0];
        $a = json_decode($a[1]);
        if($a->status == 'ok'){
            preg_match('#set-cookie: csrftoken=([^;]+)#', $header, $match);
            $csrftoken = $match[1];
            preg_match_all('%set-cookie: (.*?);%',$header,$d);$cookies = '';
            for($o=0;$o<count($d[0]);$o++)$cookies.=$d[1][$o].";";
            $id = $a->logged_in_user->pk;
            $user = $this->request(1, $this->generate_useragent(), 'users/'.$id.'/info', $cookies);
            $datas = json_decode($user[1]);
           // die(json_encode($datas));
            $name = $datas->user->full_name;
            $username = $datas->user->username;
            $followers = $datas->user->follower_count;
            $following = $datas->user->following_count;
            $biography = base64_encode($datas->user->biography);
            $profile_pic_url = $datas->user->profile_pic_url;





            $array = json_encode(['result' => true, 'cookies' => $cookies, 'useragent' => $this->generate_useragent() , 'name' => $name, 'username' => $username, 'followers' => $followers, 'following' => $following, 'biography' => $biography  , 'photo' => $profile_pic_url  ,'id' => $id, 'token' => $csrftoken]);
            $msg =json_encode([
                'info' => 'Cookie Saved Successfully Restart Script',
                'status' => 'ok'
            ]);

            $fp = fopen(__DIR__."/sessions/$username-data.json", 'w');
            fwrite($fp, $array);
            fclose($fp);

           echo $msg;


        } else {
            $msg = $a->message;
            die(json_encode(['result' => false, 'msg' => $msg]));
            exit();

        }
        return $array;
    }

    public function gettoken($proxy = null){

		// create curl resource
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, "https://www.instagram.com/data/shared_data/?__a=1");
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36 OPR/79.0.4143.50 (Edition utorrent)");
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($proxy)
		{
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}

        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

        $json = json_decode($output, true);
        $csrftoken = $json['config']['csrf_token'];
        $ig_did    = $json['device_id'];

        $return = array("csrftoken"=>$csrftoken, "ig_did"=>$ig_did);

        return json_encode($return);
		}

    public function getClientid ($proxy = null)
        {
		$strUrl = 'https://www.instagram.com/web/__mid/';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $strUrl);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36 OPR/79.0.4143.50 (Edition utorrent)");
		if ($proxy)
		{
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$client_id = curl_exec($ch);
		curl_close($ch);
		//echo $client_id;
		return $client_id;

        }

        public function loginWeb ($username,$password,$proxy){
            $token = $this->gettoken($proxy);
            $client =  $this->getClientid($proxy);
            $csrftoken = json_decode($token)->csrftoken;
            $ig_did = json_decode($token)->ig_did;
            $sessions = 'ig_cb=1; mid='.$client.'; csrftoken='.$csrftoken.'; ig_did='.$ig_did.'; rur=FRC';

            $enc_password = '#PWD_INSTAGRAM_BROWSER:0:' . time() . ':' . $password;
            $curl = curl_init();
            curl_setopt_array($curl, [
            CURLOPT_URL            => "https://www.instagram.com/accounts/login/ajax/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPPROXYTUNNEL => 1,
            CURLOPT_PROXY => $proxy,
            CURLOPT_ENCODING       => "gzip, deflate",
            CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36 OPR/79.0.4143.50 (Edition utorrent)",
            //CURLOPT_USERAGENT      => $useragent,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => [
            "Host: www.instagram.com",
           // "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.52 Safari/537.36 OPR/15.0.1147.100",
            "Accept: /",
            "Accept-Language: en-US,en;q=0.5",
            "Accept-Encoding: gzip, deflate, br",
            "X-Instagram-AJAX: cc6f59f85f33",
            "X-IG-App-ID: 936619743392459",
            "X-IG-WWW-Claim: 0",
            "X-asbd-id: 198387",
            "Content-Type: application/x-www-form-urlencoded",
            "X-Requested-With: XMLHttpRequest",
            "Origin: https://www.instagram.com",
            "Connection: keep-alive",
            "Referer: https://www.instagram.com/accounts/emailsignup/",
            "Pragma: no-cache",
            "Cache-Control: no-cache",
            "X-CSRFToken: ".$csrftoken,
            "Cookie: ".$sessions,
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-site",
            "TE: trailers"

            ],
            CURLOPT_HEADER  => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS  => 'enc_password='.$enc_password.'&username='.$username.'&queryParams=%7B%22oneTapUsers%22%3A%22%5B%5C%2249017715316%5C%22%2C%5C%2250802385173%5C%22%5D%22%7D&optIntoOneTap=false&stopDeletionNonce=&trustedDeviceRecords=%7B%7D',
         ]);
           // CURLOPT_CUSTOMREQUEST  =>


        $response = curl_exec($curl);
        $header = substr($response, 0, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
        $body = substr($response, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
        curl_close($curl);
        $a = array($header,$body);


        $header = $a[0];
        $a = json_decode($a[1]);
        //die(json_encode($a));

        if($a->authenticated == true){
        preg_match('#Set-Cookie: csrftoken=([^;]+)#', $header, $match);
        $csrftoken = $match[1];
        preg_match_all('%Set-Cookie: (.*?);%',$header,$d);$cookies = '';
        for($o=0;$o<count($d[0]);$o++)$cookies.=$d[1][$o].";";
        // $id = $a->userId;
        // $user = $this->request(1, $this->generate_useragent(), 'users/'.$id.'/info', $cookies);
        // $datas = json_decode($user[1]);
        // $name = $datas->user->full_name;
        //         $username = $datas->user->username;
        //         $followers = $datas->user->follower_count;
        //         $following = $datas->user->following_count;
        //         $biography = base64_encode($datas->user->biography);
        //         $profile_pic_url = $datas->user->profile_pic_url;

                $array = json_encode(['result' => true, 'cookies' => $cookies, 'csrftoken' => $csrftoken]);
                $msg =json_encode([
                    'info' => 'Cookie Saved Successfully Restart Script',
                    'status' => 'ok'
                ]);

                $fp = fopen(public_path("/sessions/cookie.json"), 'w');
                fwrite($fp, $array);
                fclose($fp);
                echo $msg;
            } else {
                $msg = $a->message;
                die(json_encode(['result' => false, 'msg' => $msg == !null ? $msg : 'error occured Please try again later']));
                exit();

            }
            return $array;

        }

        public function run(){
            $this->loginWeb(env('INSTAGRAM_USERNAME'), env('INSTAGRAM_PASSWORD'),'');
        }

        public function fas(){
            $token = $this->gettoken();
            $client =  $this->getClientid();
            $csrftoken = json_decode($token)->csrftoken;
            $ig_did = json_decode($token)->ig_did;
            $sessions = 'ig_cb=1; mid='.$client.'; csrftoken='.$csrftoken.'; ig_did='.$ig_did.'; rur=FRC';
            return response()->json(['result' => true,
            'cookies' => $sessions]);
        }
}
