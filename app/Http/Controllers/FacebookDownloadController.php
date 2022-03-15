<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FacebookDownloadController extends Controller
{
    public $data;

    public  function getLongUrl($url, $maxRedirs = 3)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $maxRedirs);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'authority: www.facebook.com',
            'cache-control: max-age=0',
            'sec-ch-ua: "Google Chrome";v="89", "Chromium";v="89", ";Not A Brand";v="99"',
            'sec-ch-ua-mobile: ?0',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'sec-fetch-site: none',
            'sec-fetch-mode: navigate',
            'sec-fetch-user: ?1',
            'sec-fetch-dest: document',
            'accept-language: en-GB,en;q=0.9,tr-TR;q=0.8,tr;q=0.7,en-US;q=0.6',
            'cookie: dpr=1.25; datr=WcctYtDbV2gYOTGUY6ZFqAwZ; fr=0J3CbxexbulYNv43O.AWUq95c6gh8nUOBcc2Nb76LaPtQ.BiLzxB.mp.AAA.0.0.BiMDpO.AWX04vJ-4CA; wd=1519x311; sb=LSsvYqmS9krJVMbADiDZ-Uf5; locale=en_US; m_pixel_ratio=1',

        ));
        curl_exec($ch);
        $longUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        parse_str(parse_url($longUrl, PHP_URL_QUERY), $query);
        if (!empty($query['next'])) {
            return $query['next'];
        } else {
            return $longUrl;
        }
    }

    public function urlGetContents($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'authority: www.facebook.com',
                'cache-control: max-age=0',
                'sec-ch-ua: "Google Chrome";v="89", "Chromium";v="89", ";Not A Brand";v="99"',
                'sec-ch-ua-mobile: ?0',
                'upgrade-insecure-requests: 1',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36',
                'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'sec-fetch-site: none',
                'sec-fetch-mode: navigate',
                'sec-fetch-user: ?1',
                'sec-fetch-dest: document',
                'accept-language: en-GB,en;q=0.9,tr-TR;q=0.8,tr;q=0.7,en-US;q=0.6',
                'cookie: dpr=1.25; datr=WcctYtDbV2gYOTGUY6ZFqAwZ; fr=0J3CbxexbulYNv43O.AWUq95c6gh8nUOBcc2Nb76LaPtQ.BiLzxB.mp.AAA.0.0.BiMDpO.AWX04vJ-4CA; wd=1519x311; sb=LSsvYqmS9krJVMbADiDZ-Uf5; locale=en_US; m_pixel_ratio=1',
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }


    public function getVideoInfo($url){
        $context = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.47 Safari/537.36",
            ],
        ];
        $context = stream_context_create($context);
        $this->data = file_get_contents($url, false, $context);
        if ($this->sd_finallink($this->data) == ''){
            return false;
        }
        return array(
            'type' => 'success',
            'title' => $this->getTitle($this->data),
            'hd_download_url' => $this->hd_finallink($this->data),
            'sd_download_url' => $this->sd_finallink($this->data)
        );
    }

    public function cleanStr($str)
    {
        return html_entity_decode(strip_tags($str), ENT_QUOTES, 'UTF-8');
    }

    public function hd_finallink($curl_content)
    {
        $regex = '/hd_src_no_ratelimit:"([^"]+)"/';
        if (preg_match($regex, $curl_content, $match)) {
            return $match[1];
        } else {return;}
    }

    public function sd_finallink($curl_content)
    {
        $regex = '/sd_src_no_ratelimit:"([^"]+)"/';
        if (preg_match($regex, $curl_content, $match1)) {
            return $match1[1];
        } else {return;}
    }

    public function getTitle($curl_content)
    {
        $title = null;
        if (preg_match('/h2 class="uiHeaderTitle"?[^>]+>(.+?)<\/h2>/', $curl_content, $matches)) {
            $title = $matches[1];
        } elseif (preg_match('/title id="pageTitle">(.+?)<\/title>/', $curl_content, $matches)) {
            $title = $matches[1];
        }
        return $this->cleanStr($title);
    }


    public function generateUrl($url)
    {
        $id = '';
        if (is_int($url)) {
            $id = $url;
        } elseif (preg_match('/(?:\.?\d+)(?:\/videos)?\/?(\d+)?(?:[v]\=)?(\d+)?/i', $url, $matches)) {
            $id = $matches[1];
        }

        return 'https://www.facebook.com/video.php?v='.$id;
    }

}
