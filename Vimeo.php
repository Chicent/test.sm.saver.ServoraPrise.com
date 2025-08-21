<?php

class Vimeo extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        if (preg_match_all('/window.vimeo.clip_page_config.player\s*=\s*({.+?})\s*;\s*\n/', $http->response, $match)) {
            $configUrl = json_decode($match[1][0], true)["config_url"];
            $data = new Http($configUrl);
            $data->run();
            $data = json_decode($data->response, true);
        } else {
            $data = json_decode(Helpers::getStringBetween($http->response, "var config = ", "; if (!config.request)"), true);
        }
        if ($data['video']['title'] != '') {
            $this->title = $data['video']['title'];
            $this->source = 'vimeo';
            $this->thumbnail = reset($data['video']['thumbs']);
            $this->duration = $data['video']['duration'];
            if (!empty($data['request']['files']['progressive'])) {
                foreach ($data['request']['files']['progressive'] as $video) {
                    $this->medias[] = new Media($video['url'], $video['quality'], 'mp4', true, true);
                }
            } else {
                $hls = array_key_first($data['request']['files']['dash']['cdns']);
                $hls = $data['request']['files']['dash']['cdns'][$hls]['url'];
                $prefix = null;
                preg_match('/(.*?)sep\/video/', $hls, $matches);
                if (!empty($matches[1])) {
                    $prefix = $matches[1];
                }
                if (!empty($prefix)) {
                    $http = new Http($hls);
                    $http->run();
                    $data = json_decode($http->response, true);
                    $mediaTypes = ['video', 'audio'];
                    foreach ($mediaTypes as $type) {
                        if (!empty($data[$type])) {
                            foreach ($data[$type] as $media) {
                                preg_match('/(.*?)&range=/', $media['index_segment'], $matches);
                                if (!empty($matches[1])) {
                                    if ($type === 'video') {
                                        $this->medias[] = new Media($prefix . 'parcel/video/' . $matches[1], $media['height'] . 'p', 'mp4', true, false);
                                    } else {
                                        $this->medias[] = new Media($prefix . 'parcel/audio/' . $matches[1], $media['bitrate'] / 1000 . 'kbps', 'm4a', false, true);
                                    }
                                }

                            }
                        }
                    }

                }
            }
            usort($this->medias, array('Helpers', 'sortByQuality'));
        }
    }
}