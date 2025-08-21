<?php

class Threads extends Downloader
{
    public function fetch($videoUrl)
    {
        $apiQuery = http_build_query([
            'url' => $videoUrl,
            'purchaseCode' => get_option('aiodl_license_code')
        ]);
        $http = new Http('https://aiovideodl.ml/assets/get_thread_id.php?' . $apiQuery);
        $http->run();
        $postId = $http->response;
        if (!empty($postId)) {
            $data = $this->getPostDetailFromApi($postId);
            if (!empty($data['data']['data']['containing_thread']['thread_items'])) {
                $data = $data['data']['data']['containing_thread']['thread_items'][0]['post'];
                $this->title = $data['caption']['text'];
                $this->source = 'threads';
                $this->thumbnail = $data['image_versions2']['candidates'][0]['url'];
                $this->thumbnailHotlinkProtection = true;
                $this->saveThumbnail();
                $this->medias[] = new Media($this->thumbnail, 'hd', 'jpg', false, false);
                if (!empty($data['video_versions'])) {
                    foreach ($data['video_versions'] as $video) {
                        $this->medias[] = new Media($video['url'], 'hd', 'mp4', true, true);
                        break;
                    }
                }
            }
        }
    }

    private function saveThumbnail()
    {
        if ($this->thumbnailHotlinkProtection) {
            $id = sha1($this->thumbnail);
            $cache = new Cache('threads-' . $id, 'jpg', file_get_contents($this->thumbnail));
            $this->thumbnail = $cache->url;
        }
    }

    private function getPostDetailFromApi($postId)
    {
        $http = new Http('https://threadsapi-1-c7077117.deta.app/?' . http_build_query(['post_id' => $postId, 'purchaseCode' => get_option('aiodl_license_code')]));
        $http->addHeader('Cookie', base64_decode('ZGV0YV9hcHBfdG9rZW49YXU5RUF5Qll2YUVEemhyb1dQaWpYaG80dm1CRHlhUlNnQVFZM29iUnJyeGdlNjhw'));
        $http->run();
        return json_decode($http->response, true);
    }
}