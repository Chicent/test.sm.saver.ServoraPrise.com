<?php

class Douyin extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http('https://api.douyin.wtf/douyin_video_data/?douyin_video_url=' . urlencode($videoUrl));
        $http->run();
        $data = json_decode($http->response, true);
        if (!empty($data['aweme_list'])) {
            $this->parseApiData($data['aweme_list'][0]);
        }
    }

    public function fetch2($videoUrl)
    {
        $videoId = $this->extractVideoId($videoUrl);
        $data = $this->getVideoInfo($videoId);
        if (!empty($data['aweme_detail'])) {
            $this->parseApiData($data['aweme_detail']);
        }
    }

    public function fetch_legacy($videoUrl)
    {
        $videoId = $this->extractVideoId($videoUrl);
        $http = new Http('https://www.douyin.com/video/' . $videoId);
        $http->run();
        preg_match_all('/<script id="RENDER_DATA" type="application\/json">(.*?)<\/script>/', $http->response, $matches);
        if (!empty($matches[1][0])) {
            $data = urldecode($matches[1][0]);
            $data = json_decode($data, true);
            if (!empty($data['44']) && !empty($data['44']['aweme']['detail']['video'])) {
                $data = $data['44']['aweme']['detail'];
                $this->title = !empty($data['desc']) ? $data['desc'] : 'Douyin Video' . rand(1, 100);
                $this->source = 'douyin';
                $this->thumbnail = $data['video']['cover'];
                $this->duration = $data['video']['duration'] / 1000;
                if (!empty($data['video'])) {
                    $media = new Media('https:' . $data['video']['play_addr']['url_list'][0], 'watermark', 'mp4', true, true);
                    $media->size = $data['video']['play_addr']['data_size'];
                    $this->medias[] = $media;
                    $nwm = str_replace('playwm', 'play', 'https:' . $data['video']['play_addr']['url_list'][0]);
                    $media = new Media($nwm, 'hd', 'mp4', true, true);
                    $media->size = $data['video']['play_addr']['data_size'];
                    $this->medias[] = $media;
                }

                if (!empty($data['music'])) {
                    $this->medias[] = new Media($data['music']['play_url']['url_list'][0], '128kbps', 'mp3', false, true);
                }
            }
        }
    }

    private function parseApiData($data)
    {
        if (!empty($data['video'])) {
            $this->title = !empty($data['desc']) ? $data['desc'] : 'Douyin Video' . rand(1, 100);
            $this->source = 'douyin';
            $this->thumbnail = $data['video']['cover']['url_list'][0];
            $this->duration = $data['video']['duration'] / 1000;
            $media = new Media($data['video']['play_addr']['url_list'][0], 'watermark', 'mp4', true, true);
            $media->size = $data['video']['play_addr']['data_size'];
            $this->medias[] = $media;
            $nwm = str_replace('playwm', 'play', $data['video']['play_addr']['url_list'][0]);
            $media = new Media($nwm, 'hd', 'mp4', true, true);
            $media->size = $data['video']['play_addr']['data_size'];
            $this->medias[] = $media;
        }
        if (!empty($data['music'])) {
            $this->medias[] = new Media($data['music']['play_url']['url_list'][0], '128kbps', 'mp3', false, true);
        }
    }

    private function extractVideoId($videoUrl)
    {
        $http = new Http($videoUrl);
        $url = $http->getLongUrl();
        $url = strtok($url, '?');
        $last_char = substr($url, -1);
        if ($last_char == "/") {
            $url = substr($url, 0, -1);
        }
        $arr = explode('/', $url);
        return end($arr);
    }

    private function getVideoInfo($videoId)
    {
        $http = new Http('https://www.iesdouyin.com/aweme/v1/web/aweme/detail/?aweme_id=' . $videoId);
        $http->run();
        return json_decode($http->response, true);
    }
}