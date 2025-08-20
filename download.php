<?php
set_time_limit(0);
ini_set("zlib.output_compression", "Off");
require_once __DIR__ . '/../../../wp-load.php';
$siteUrl = get_site_url();
$countdown = (int)get_option('aiodl_download_timer') - 1;
$bandwidthSaving = get_option('aiodl_bandwidth_saving_mode') == 'on';
$monetization = get_option('aiodl_url_monetization') == 'on';
$suffix = get_option('aiodl_filename_suffix');
$shortenedUrl = 'null';
if (empty($_GET['start']) && $monetization) {
    $shortenerService = get_option('aiodl_url_shortener');
    $shortener = null;
    switch ($shortenerService) {
        case 'shortest':
            require_once __DIR__ . '/includes/shorteners/Shortest.php';
            $shortener = new Shortest(get_option('aiodl_shortest_api_key'));
            break;
        case 'bcvc':
            require_once __DIR__ . '/includes/shorteners/Bcvc.php';
            $shortener = new Bcvc(get_option('aiodl_bcvc_api_key'));
            break;
    }
    $pageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $shortenedUrl = $shortener->shorten($pageUrl . '&start=1');
    if (!$shortenedUrl) {
        $shortenedUrl = 'null';
    }
}
if ((!empty($_GET['start']) || $countdown === -1) && !empty($_GET['media']) && (!empty($_SESSION['result']) || !empty($_GET['sid']))) {
    $id = (int)base64_decode($_GET['media']);
    if (!empty($_GET['sid'])) {
        require_once __DIR__ . '/includes/Cache.php';
        $data = json_decode(Cache::getContent($_GET['sid'], 'json'), true);
    } else {
        $data = $_SESSION['result'];
    }
    if (is_numeric($id) && !empty($data['medias'][$id])) {
        $media = $data['medias'][$id];
        $name = substr($data['title'], 0, 48);
        if ($bandwidthSaving) {
            Helpers::redirect($media['url']);
        }
        if ($suffix != '') {
            $name = $data['title'] . ' ' . $suffix;
        }
        $parsedRemoteUrl = parse_url($media['url']);
        $remoteDomain = str_ireplace('www.', '', $parsedRemoteUrl['host'] ?? '');
        $localDomain = str_ireplace('www.', '', parse_url($siteUrl, PHP_URL_HOST));
        require_once __DIR__ . '/includes/Helpers.php';
        require_once __DIR__ . '/includes/Http.php';
        session_write_close();
        error_reporting(0);
        if ($media['chunked']) {
            $paths = explode('/', $parsedRemoteUrl['path']);
            $fileName = end($paths);
            $chunks = json_decode(file_get_contents(__DIR__ . '/cache/' . $fileName), true);
            Http::forceDownloadChunks($chunks, $name, $media['extension']);
        } else if ($media['cached']) {
            Http::forceDownloadLegacy(__DIR__ . $parsedRemoteUrl['path'], $name, $media['extension'], $media['size']);
        } else if ($remoteDomain == 'dailymotion.aiovideodl.ml') {
            Helpers::redirect($media['url']);
        } else if ($data['source'] == 'bilibili') {
            Http::forceDownloadLegacy($media['url'], $name, $media['extension'], $media['size'], false);
        } else if ($data['source'] == 'youtube2') {
            require_once __DIR__ . '/includes/Stream.php';
            $stream = new Stream();
            $stream->forceDownload($media['url'], $name, $media['extension'], $media['size'], $media['url']);
        } else {
            $referer = '';
            if ($data['source'] == 'mxtakatak') {
                $referer = 'https://www.mxtakatak.com/';
                $media['size'] = 0;
            }
            Http::forceDownload($media['url'], $name, $media['extension'], $media['size'], $referer);
        }
    }
}

if ($countdown >= 0) {
    get_header();
    ?>
    <script>
        const urlSearchParams = new URLSearchParams(window.location.search);
        const params = Object.fromEntries(urlSearchParams.entries());
        let redirectUrl = window.location.href;
        let countdown = <?php echo $countdown; ?>;
        let timeLeft = countdown;
        let monetization = <?php echo $monetization ? 'true' : 'false'; ?>;
        let shortenedUrl = "<?php echo $shortenedUrl; ?>";

        function isValidHttpUrl(string) {
            let url;

            try {
                url = new URL(string);
            } catch (_) {
                return false;
            }

            return url.protocol === "http:" || url.protocol === "https:";
        }

        function redirect() {
            if (!params.start) {
                if (monetization && isValidHttpUrl(shortenedUrl)) {
                    window.location.href = shortenedUrl;
                } else {
                    window.location.href = redirectUrl + "&start=1";
                }
            }
        }

        var downloadTimer = setInterval(function () {
            if (timeLeft <= 0) {
                clearInterval(downloadTimer);
                redirect();
                document.getElementById("text").innerHTML = "<?php pll_e('Download has started.'); ?>";
                document.getElementById("loader").src = "<?php echo $siteUrl; ?>/wp-content/themes/aiodl-default/assets/icons/check-mark.svg";
            } else {
                document.getElementById("countdown").innerHTML = timeLeft + "";
            }
            timeLeft -= 1;
        }, 1000);
    </script>
    <?php echo get_option('aiodl_ad_area_3'); ?>
    <main class="container mt-8 mb-12">
        <div class="row align-items-center">
            <div class="col-12 mb-5 mb-lg-0" id="main">
                <div class="mt-8 mb-8 mb-lg-12 text-center"><img
                            src="<?php echo $siteUrl; ?>/wp-content/themes/aiodl-default/assets/loader.svg"
                            class="img-fluid w-25 mx-auto" id="loader"></div>
                <div class="text-center"><strong id="text"><?php pll_e('Your download will start within'); ?> <span
                                id="countdown"><?php echo $countdown + 1; ?></span> <?php pll_e('seconds.'); ?></strong>
                </div>
            </div>
        </div>
    </main>
    <?php echo get_option('aiodl_ad_area_4'); ?>
    <?php
    get_footer();
}