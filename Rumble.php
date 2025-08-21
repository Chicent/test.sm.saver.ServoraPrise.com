<?php

class Rumble extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $videoId = Helpers::getStringBetween($http->response, '"video":"', '"');
        if ($videoId != '') {
            $data = $this->getVideoData($videoId);
            $this->title = $data['title'];
            $this->source = 'rumble';
            $this->thumbnail = $data['i'];
            $this->duration = $data['duration'];
            $videoFormats = ['mp4', 'webm'];
            foreach ($videoFormats as $format) {
                if (!empty($data['ua'][$format])) {
                    foreach ($data['ua'][$format] as $quality => $info) {
                        $media = new Media($info['url'], $quality . 'p', $format, true, true);
                        $media->size = $info['meta']['size'];
                        $this->medias[] = $media;
                    }
                }
            }
            usort($this->medias, array('Helpers', 'sortByQuality'));
        }
    }

    public function fetch_legacy($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $videoId = Helpers::getStringBetween($http->response, '"video":"', '"');
        if ($videoId != '') {
            $data = $this->getVideoData($videoId);
            $this->title = $data['title'];
            $this->source = 'rumble';
            $this->thumbnail = $data['i'];
            $this->duration = $data['duration'];
            foreach ($data["ua"] as $quality => $info) {
                $this->medias[] = new Media($info[0], $quality . 'p', 'mp4', true, true);
            }
            usort($this->medias, array('Helpers', 'sortByQuality'));
        }
    }

    private function getVideoData($videoId)
    {
        $http = new Http('https://rumble.com/embedJS/u3/?request=video&ver=2&v=' . $videoId);
        $http->run();
        return json_decode($http->response, true);
    }
}