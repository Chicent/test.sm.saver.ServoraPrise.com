<?php

class Chingari extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        preg_match_all('/<script type="application\/ld\+json" data-react-helmet="true" >(.*)<\/script><\/head>/', $http->response, $matches);
        if (!empty($matches[1][0])) {
            $data = json_decode($matches[1][0], true);
            $this->title = $data['name'];
            $this->thumbnail = $data['thumbnailUrl'];
            $this->duration = $data['duration'];
            $this->source = 'chingari';
            $this->medias[] = new Media($data['contentUrl'], $data['width'] . 'p', 'mp4', true, true);
        }
    }
}