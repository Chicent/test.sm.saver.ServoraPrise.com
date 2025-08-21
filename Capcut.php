<?php

class Capcut extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        preg_match_all('/<script id="RENDER_DATA" type="application\/json">(.*?)<\/script>/', $http->response, $matches);
        if (!empty($matches[1][0])) {
            $data = urldecode($matches[1][0]);
            $data = json_decode($data, true);
            if (!empty($data['ac9cb00e6f46231d4bf1560940374f29']['_fd']['data'])) {
                $data = $data['ac9cb00e6f46231d4bf1560940374f29']['_fd']['data'];
                $this->title = $data['seo']['title'];
                $this->source = 'capcut';
                $this->thumbnail = $data['template']['video']['cover_url'];
                $this->medias[] = new Media($data['template']['video']['video_url'], $data['template']['video']['video_width'] . 'p', 'mp4', true, true);
            }
        }
    }
}