<?php

class Instagram extends Downloader
{
    public function fetch($videoUrl)
    {
        $rapidApiKey = get_option('aiodl_rapid_api_key');
        $shortcode = $this->getPostShortcode($videoUrl);
        if (!empty($rapidApiKey) && !empty($shortcode)) {
            $hash = sha1($shortcode);
            $cachedData = get_transient($hash);
            if (!empty($cachedData)) {
                $data = $cachedData;
            } else {
                $http = new Http('https://rocketapi-for-instagram.p.rapidapi.com/instagram/media/get_info_by_shortcode');
                $http->addCurlOption(CURLOPT_CUSTOMREQUEST, 'POST');
                $http->addCurlOption(CURLOPT_POSTFIELDS, json_encode(['shortcode' => $shortcode]));
                $http->addHeader('X-RapidAPI-Host', 'rocketapi-for-instagram.p.rapidapi.com');
                $http->addHeader('X-RapidAPI-Key', $rapidApiKey);
                $http->addHeader('Content-Type', 'application/json');
                $http->run();
                $data = json_decode($http->response, true);
                if (!empty($data) && !empty($data['response']) && $data['response']['status_code'] === 200 && !empty($data['response']['body']['items'])) {
                    set_transient($hash, $data, 86400);
                }
            }
            if (!empty($data) && !empty($data['response']) && $data['response']['status_code'] === 200 && !empty($data['response']['body']['items'])) {
                $data = $data['response']['body']['items'][0];
                $this->title = $data['caption']['text'] ?? 'Instagram Post';
                if (!empty($data['image_versions2'])) {
                    $this->thumbnail = $data['image_versions2']['candidates'][0]['url'];
                } else if (!empty($data['carousel_media'])) {
                    $this->thumbnail = $data['carousel_media'][0]['image_versions2']['candidates'][0]['url'];
                }
                $this->thumbnailHotlinkProtection = true;
                $this->saveThumbnail();
                $this->duration = $data['video_duration'] ?? null;
                $this->source = 'instagram';
                if (!empty($data['video_versions'])) {
                    foreach ($data['video_versions'] as $video) {
                        $this->medias[] = new Media($video['url'], $video['width'] . 'p', 'mp4', true, true);
                    }
                    usort($this->medias, array('Helpers', 'sortByQuality'));
                } else if ($data['media_type'] === 1) {
                    $this->medias[] = new Media($data['image_versions2']['candidates'][0]['url'], 'hd', 'jpg', false, false);
                } else if ($data['media_type'] == 8 && !empty($data['carousel_media'])) {
                    foreach ($data['carousel_media'] as $media) {
                        $this->medias[] = new Media($media['image_versions2']['candidates'][0]['url'], 'hd', 'jpg', false, false);
                    }
                }
            }
        }
    }

    public function fetch2($videoUrl)
    {
        $rapidApiKey = get_option('aiodl_rapid_api_key');
        if (!empty($rapidApiKey)) {
            $hash = sha1($videoUrl);
            $cachedData = get_transient($hash);
            if (!empty($cachedData)) {
                $data = $cachedData;
            } else {
                $http = new Http('https://instagram-downloader-download-instagram-videos-stories.p.rapidapi.com/index?url=' . urlencode($videoUrl));
                $http->addHeader('X-RapidAPI-Host', 'instagram-downloader-download-instagram-videos-stories.p.rapidapi.com');
                $http->addHeader('X-RapidAPI-Key', $rapidApiKey);
                $http->run();
                $data = json_decode($http->response, true);
                if (!empty($data) && !empty($data['response']) && $data['response']['status_code'] === 200 && !empty($data['response']['body']['items'])) {
                    set_transient($hash, $data, 86400);
                }
            }
            if (!empty($data) && !empty($data['media'])) {
                $this->title = $data['title'] ?? 'Instagram Post';
                if (!empty($data['carousel_thumb'])) {
                    $this->thumbnail = $data['carousel_thumb'];
                } elseif (!empty($data['thumbnail'])) {
                    $this->thumbnail = $data['thumbnail'];
                }
                if (!empty($this->thumbnail)) {
                    $this->thumbnailHotlinkProtection = true;
                    $this->saveThumbnail();
                }
                $this->source = 'instagram';
                if (!empty($data['media_with_thumb'])) {
                    foreach ($data['media_with_thumb'] as $media) {
                        if ($media['Type'] == 'Video') {
                            $this->medias[] = new Media($media['media'], 'hd', 'mp4', true, true);
                        } else {
                            $this->medias[] = new Media($media['media'], 'hd', 'jpg', false, false);
                        }
                    }
                } else {
                    if ($data['Type'] == 'Post-Image') {
                        $this->medias[] = new Media($data['media'], 'hd', 'jpg', true, true);
                    } else {
                        $this->medias[] = new Media($data['media'], 'hd', 'mp4', true, true);
                    }
                }
            }
        }

    }

    /**
     * @param string $url
     * @return mixed
     */
    private function getPostShortcode($url)
    {
        if (substr($url, -1) != '/') {
            $url .= '/';
        }
        preg_match('/\/(p|tv|reel)\/(.*?)\//', $url, $output);
        return ($output['2'] ?? '');
    }

    private function saveThumbnail()
    {
        if ($this->thumbnailHotlinkProtection) {
            $id = sha1($this->thumbnail);
            $context_options = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                )
            );
            $context = stream_context_create($context_options);
            $cache = new Cache('ig-' . $id, 'jpg', file_get_contents($this->thumbnail, false, $context));
            $this->thumbnail = $cache->url;
        }
    }
}