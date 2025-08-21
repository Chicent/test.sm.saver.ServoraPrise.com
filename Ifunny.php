<?php

class Ifunny extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $this->title = Helpers::getStringBetween($http->response, '"seo":{"title":"', ',"');
        $this->source = 'ifunny';
        //preg_match_all('/window.__INITIAL_STATE__=(.*?);/', $http->response, $matches);
        $matches = Helpers::getStringBetween($http->response, '<script>window.__INITIAL_STATE__=', ';(function(){var s;');
        $matches = str_replace('"<br />', '', $matches);
        if (!empty($matches)) {
            $data = json_decode($matches, true);
            if (!empty($data['seo']['video'])) {
                $this->thumbnail = $data['seo']['image'];
                $videoUrl = html_entity_decode($data['seo']['video']);
                $this->medias[] = new Media($videoUrl, $data['seo']['videoWidth'] . 'p', 'mp4', true, true);
            }
        }
    }

    private function cleanUrl($url)
    {
        return str_replace('////', 'https://', $url);
    }
}