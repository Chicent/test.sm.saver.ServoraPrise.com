<?php

class Telegram extends Downloader
{
    public function fetch($videoUrl)
    {
        $videoUrl = strtok($videoUrl, '?') . '?embed=1&mode=tme';
        $http = new Http($videoUrl);
        $http->run();
        preg_match_all('/<video src="(.*?)"/', $http->response, $matches);
        if (!empty($matches[1][0])) {
            $this->medias[] = new Media($matches[1][0], 'hd', 'mp4', true, true);
        }
        preg_match_all('/<div class="tgme_widget_message_text js-message_text" dir="auto">(.*?)<\/div>/', $http->response, $matches);
        if (!empty($matches[1][0])) {
            $this->title = strip_tags($matches[1][0]);
        } else {
            $this->title = 'Telegram Video';
        }
        preg_match_all('/<img src="(.*?)"/', $http->response, $matches);
        if (!empty($matches[1][0])) {
            $this->thumbnail = $matches[1][0];
        } else {
            $this->thumbnail = 'https://play-lh.googleusercontent.com/ZU9cSsyIJZo6Oy7HTHiEPwZg0m2Crep-d5ZrfajqtsH-qgUXSqKpNA2FpPDTn-7qA5Q';
        }
        $this->source = 'telegram';
    }
}