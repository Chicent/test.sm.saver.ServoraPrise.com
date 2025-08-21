<?php

class Snapchat extends Downloader
{
    private $cookies = '';

    public function fetch($videoUrl)
    {
        $this->cookies = get_option('aiodl_snapchat_cookies');
        $http = new Http($videoUrl);
        $http->addHeader('cookie', $this->cookies);
        $http->run();
        preg_match_all('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/', $http->response, $matches);
        //print_r($matches[1][0]);
        if (!empty($matches[1][0])) {
            $data = json_decode($matches[1][0], true);
            //print_r($matches[1][0]);
            if (!empty($data['props']['pageProps']['preselectedStory']['premiumStory']['playerStory']['snapList'])) {
                $data = $data['props']['pageProps']['preselectedStory']['premiumStory']['playerStory'];
                $this->title = $data['storyTitle']['value'];
                $this->source = 'snapchat';
                $this->thumbnail = $data['thumbnailUrl']['value'];
                $this->medias[] = new Media($data['snapList'][0]['snapUrls']['mediaUrl'], 'hd', 'mp4', true, true);
            } else if (!empty($data['props']['pageProps']['spotlightFeed']['spotlightStories'])) {
                $story = $data['props']['pageProps']['spotlightFeed']['spotlightStories'][0];
                $this->title = $story['metadata']['videoMetadata']['name'];
                $this->source = 'snapchat';
                $this->thumbnail = $story['metadata']['videoMetadata']['thumbnailUrl'];
                $this->medias[] = new Media($story['story']['snapList'][0]['snapUrls']['mediaUrl'], 'hd', 'mp4', true, true);
            } else if (!empty($data['props']['pageProps']['story']['snapList'])) {
                $stories = $data['props']['pageProps']['story']['snapList'];
                $this->title = $data['props']['pageProps']['pageMetadata']['pageTitle'];
                $this->source = 'snapchat';
                $this->thumbnail = $data['props']['pageProps']['linkPreview']['twitterImage']['url'];
                foreach ($stories as $story) {
                    $type = $story['snapMediaType'] === 0 ? 'jpg' : 'mp4';
                    $this->medias[] = new Media($story['snapUrls']['mediaUrl'], 'hd', $type, true, true);
                }
            }
        }
    }
}