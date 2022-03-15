<?php

namespace App\Http\Controllers;


use YouTube\Utils\Utils;
use Illuminate\Http\Request;
use YouTube\YouTubeDownloader;
use YouTube\Exception\YouTubeException;
use App\Http\Controllers\FacebookDownloadController;

class MainController extends Controller
{



    public function instagram(Request $request){
        if($request->get('query')){
        $url = $request->get('query');
        $implode = explode('/',$url);
        if(empty($implode[4])){
            //session()->flash('error', 'No video found');
            return redirect()->back()->with('error','Invalid URL');
        }
        $id = $implode[4];




        $get_cookie = public_path('/sessions/cookie.json');
        $cookie = json_decode(file_get_contents($get_cookie),true);
        $cookie = $cookie['cookies'];
        //dd($cookie);
        $setUrl = 'https://www.instagram.com/p/'.$id.'/?__a=1';

        //echo $setUrl;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $setUrl);
        //curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36 OPR/79.0.4143.50 (Edition utorrent)");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        $output = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($output, true);
        //dd($json);

        if(!empty($json['items'][0]['carousel_media'][0]['image_versions2']['candidates'][0]['url'])){
            $video = $json['items'][0]['carousel_media'][0]['image_versions2']['candidates'][0]['url'];
            return view('instagram', [
                'query' => $request->get('query'),
                'data' => $video
            ]);
        }

        if(empty($json['items'][0]['video_versions'][0]['url'])){
            //session()->flash('error', 'No video found');
            return redirect()->back()->with('error','No video found');

        }else{
        $video = $json['items'][0]['video_versions'][0]['url'];
        }





        return view('instagram', [
            'query' => $request->get('query'),
            'data' => $video
        ]);

        }else{
            return view('instagram', ['query' => '']);
        }
    }


    public function facebook(Request $request){
        if($request->get('query')){

            $facebook = new FacebookDownloadController();
            //$video = $facebook->getVideoInfo($request->get('query'));
            $webPage = $facebook->urlGetContents($request->get('query'));
            preg_match_all('/<script type="application\/ld\+json" nonce="\w{3,10}">(.*?)<\/script><link rel="canonical"/', $webPage, $matches);
            preg_match_all('/"video":{(.*?)},"video_home_www_injection_related_chaining_section"/', $webPage, $matches2);
            preg_match_all('/"playable_url":"(.*?)"/', $webPage, $matches3);
            preg_match_all('/<script type="application\/ld\+json" nonce=".*?">(.*?)<\/script>/', $webPage, $matches4);
            preg_match_all('/RelayPrefetchedStreamCache","next",\[\],(.*)],\["VideoPlayerSpinner\.react"]/', $webPage, $matches5);

            switch (true) {
                case(!empty($matches5[1][0])):
                    $data = json_decode($matches5[1][0], true)[1];
                    $postData = $data['__bbox']['result']['data']['node']['comet_sections']['content']['story']['comet_sections']['message']['story']['message']['text'] ?? null;
                    $videoData = $data['__bbox']['result']['data']['node']['comet_sections']['content']['story']['attachments'][0]['styles']['attachment']['media'];
                    $this->title = !empty($postData) ? $postData : 'Facebook Video';
                    $this->thumbnail = $videoData['thumbnailImage']['uri'];
                    if (!empty($videoData['playable_url'])) {

                        // $this->medias[] = new Media($videoData['playable_url'], 'sd', 'mp4', true, true);
                    }
                    if (!empty($videoData['playable_url_quality_hd'])) {
                        // $this->medias[] = new Media($videoData['playable_url_quality_hd'], 'sd', 'mp4', true, true);
                    }
                    break;
                case (!empty($matches4[1][0]) && empty($matches3[1][0])):
                    $data = json_decode($matches4[1][0], true);
                    if(!empty($data['video'])){
                        $this->title = $data['video']['name'];
                        $this->thumbnail = $data['video']['thumbnailUrl'];
                        // $this->medias[] = new Media($data['video']['embedUrl'], $data['video']['videoQuality'], 'mp4', true, true);
                    }
                    break;
                case (!empty($matches[1][0])):
                    $data = json_decode($matches[1][0], true);
                    if (!empty($data['@type']) && $data['@type'] == 'VideoObject') {
                        $this->title = $data['name'];
                        $this->thumbnail = $data['thumbnailUrl'];
                        if (isset($data['contentUrl']) != "") {
                            // $this->medias[] = new Media($data['contentUrl'], 'sd', 'mp4', true, true);
                        }
                        // $hdLink = Helpers::getStringBetween($webPage, 'hd_src:"', '"');
                        if (!empty($hdLink)) {
                            // $this->medias[] = new Media($hdLink, 'hd', 'mp4', true, true);
                        }
                    }
                    break;
                case (!empty($matches2[1][0])):
                    $json = '{' . $matches2[1][0] . '}';
                    $data = json_decode($json, true);
                    if (isset($data['story']['attachments'][0]['media']['__typename']) != '' && $data['story']['attachments'][0]['media']['__typename'] == 'Video') {
                        $this->title = $data['story']['message']['text'];
                        $this->thumbnail = $data['story']['attachments'][0]['media']['thumbnailImage']['uri'];
                        if (isset($data['story']['attachments'][0]['media']['playable_url']) != '') {
                            // $this->medias[] = new Media($data['story']['attachments'][0]['media']['playable_url'], 'sd', 'mp4', true, true);
                        }
                        if (isset($data['story']['attachments'][0]['media']['playable_url_quality_hd']) != '') {
                            // $this->medias[] = new Media($data['story']['attachments'][0]['media']['playable_url_quality_hd'], 'hd', 'mp4', true, true);
                        }
                    }
                    break;
                case (!empty($matches3[1][0])):
                    preg_match('/"preferred_thumbnail":{"image":{"uri":"(.*?)"/', $webPage, $thumbnail);
                    preg_match_all('/"playable_url_quality_hd":"(.*?)"/', $webPage, $hdLink);
                    $this->title = 'Facebook Video';
                    $this->thumbnail = isset($thumbnail[1]) ? $this->decodeJsonText($thumbnail[1]) : '';
                    $sdLink = $this->decodeJsonText($matches3[1][0]);
                    if (filter_var($sdLink, FILTER_VALIDATE_URL)) {
                       // $sdLink
                        // $this->medias[] = new Media($sdLink, 'sd', 'mp4', true, true);
                        if (isset($hdLink[1][0]) != "") {
                            $hdLink = $this->decodeJsonText($hdLink[1][0]);
                            //dd($hdLink);
                            // $this->medias[] = new Media($hdLink, 'hd', 'mp4', true, true);
                        }
                    }
                    break;

                }
         //  dd($matches3);

            return view('facebook', [
                'query' => $request->get('query'),
                'data' =>  [
                    'sd' => $sdLink,
                    'hd' => $hdLink,
                ]
        ]);
        }else{
            return view('facebook',['query'=>'']);
        }
    }

    public function youtube(Request $request){
        if($request->get('query')){
        $youtube = new YouTubeDownloader();
        try {
        $downloadOptions = $youtube->getDownloadLinks($request->get('query'));



        if ($downloadOptions->getAllFormats()) {

            return view('youtube', [
                'query' => $request->get('query'),
                'data' => $downloadOptions->getAllFormats(),
                'video_id' => Utils::extractVideoId($request->get('query'))
        ]);
        } else {
            session()->flash('error', 'No video found');
        }

    } catch (YouTubeException $e) {
        session()->flash('error', 'Something went wrong'.  $e->getMessage());

    }

        return view('youtube', ['query' => $request->get('query')]);
        }else{
        return view('youtube', ['query' => '']);

        }

    }

    public function search (Request $request){
      return redirect()->route('youtube',['query'=>$request->searchQueryInput]);
    }

    public function facebookSearch (Request $request){
        return redirect()->route('facebook',['query'=>$request->searchQueryInput]);
      }

      public function instagramSearch (Request $request){
        return redirect()->route('instagram',['query'=>$request->searchQueryInput]);
      }


    public function download(Request $request)
    {



    }

    public  function decodeJsonText($text)
    {
        $json = '{"text":"' . $text . '"}';
        $json = json_decode($json, 1);
        return $json["text"];
    }

    function removeM($url)
    {
        $url = str_replace('m.facebook.com', 'www.facebook.com', $url);
        return $url;
    }
}
