<?php

namespace InstagramScrapper;

use DB;
use Config;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use InstagramScraper\Exception\InstagramAuthException;
use InstagramScraper\Exception\InstagramChallengeRecaptchaException;
use InstagramScraper\Exception\InstagramChallengeSubmitPhoneNumberException;
use InstagramScraper\Exception\InstagramException;
use Phpfastcache\Helper\Psr16Adapter;

use InstagramScrapper\Models\Post;
use InstagramScrapper\Models\Story;

class Scrapper
{
    const COMMON_FIELDS = [ "id",
                            "type",
                            "shortCode",
                            "link",
                            "likesCount",
                            "commentsCount",
                            "caption",
                            "altText" ];

    const LOCATION_FIELDS = [ "locationId",
                              "locationName",
                              "locationSlug" ];

    const IMAGE_FIELDS = [ "imageLowResolutionUrl",
                           "imageThumbnailUrl",
                           "imageStandardResolutionUrl",
                           "imageHighResolutionUrl" ];

    const VIDEO_FIELDS = [ "videoLowResolutionUrl",
                           "videoStandardResolutionUrl",
                           "videoLowBandwidthUrl",
                           "videoDuration",
                           "videoViews" ];

    const SIDECAR_IMAGES_KEY = "sidecar_images";
    const SIDECAR_VIDEOS_KEY = "sidecar_videos";

    private $mediaType;
    private $instagram;
    private $httpClient;
    private $cache;

    public function __construct($mediaType = "all") {
        $this->setMediaType($mediaType);

        $this->cache = new Psr16Adapter('Files');
        $this->instagram = \InstagramScraper\Instagram::withCredentials(new \GuzzleHttp\Client,
                                                                        Config::get('instagram-scrapper.username'),
                                                                        Config::get('instagram-scrapper.password'),
                                                                        $this->cache);
        $this->instagram->setUserAgent(Config::get('instagram-scrapper.user_agent'));
        $this->instagram->setCustomCookies(Config::get('instagram-scrapper.cookies'));
    }

    public function getMediaType() {
        return $this->mediaType;
    }

    public function setMediaType($mediaType) {
        if ($this->validateMediaType($mediaType)) {
            $this->mediaType = $mediaType;
            Log::info("Instagram scrapping media type set to {$this->mediaType}");
        } else {
            throw new InvalidArgumentException("Unexpected media type for Instagram scrapping: {$mediaType}");
        }
    }

    private function validateMediaType($mediaType) {
        return $mediaType === "all" ||
               $mediaType === "story" ||
               $mediaType === "post";

    }

    private function isLoggedIn() {
        return $this->instagram->isLoggedIn($this->cache->get(md5(Config::get('instagram-scrapper.username'))));
    }

    private function login() {
        if ($this->isLoggedIn()) {
            return true;
        }

        try {
            $this->instagram->login();
            $this->instagram->saveSession();

            Log::info("Successfully login to Instagram");
        } catch (InstagramAuthException|
                 InstagramChallengeRecaptchaException|
                 InstagramChallengeSubmitPhoneNumberException|
                 InstagramException $e) {
            Log::error("Instagram login failure: {$e->getMessage()}");
        }
        return $this->isLoggedIn();
    }

    public function __invoke() {
        switch ($this->mediaType) {
            case "story":
                $this->scrapeStories();
                break;
            case "post":
                $this->scrapePosts();
                break;
            case "all":
                $this->scrapePosts();
                $this->scrapeStories();
                break;
        }
    }

    private function extract_fields($media, $fields) {
        $data = [];
        foreach ($fields as &$field) {
            if (!is_null($media[$field]) && $media[$field] !== "") {
                $data[$field] = $media[$field];
            }
        }
        return $data;
    }

    public function scrapePosts() {
        $USERNAME_FIELD = Config::get('instagram-scrapper.table_pages.url_field');

        if (!$this->login()) {
            Log::error("No login success so skip Instagram scrapping");
            return;
        }

        $result = [];
        foreach ($this->getUsernames() as &$user) {
            try {
                $posts  = $this->instagram->getMediasFromFeed($user->$USERNAME_FIELD, intval(Config::get('instagram-scrapper.max_post_count')));
                foreach ($posts as &$post){
                    $data = $this->extract_fields($post, self::COMMON_FIELDS + self::LOCATION_FIELDS + self::IMAGE_FIELDS);

                    switch ($data['type']) {
                        case "image":
                            break;
                        case "video":
                            $data = $data + $this->extract_fields($post, self::VIDEO_FIELDS);
                            break;
                        case "sidecar":
                            foreach ($post["sidecarMedias"] as &$media) {
                                switch ($media['type']) {
                                    case "image":
                                        $data[self::SIDECAR_IMAGES_KEY] = $this->extract_fields($media, self::IMAGE_FIELDS);
                                        break;
                                    case "video":
                                        $data[self::SIDECAR_VIDEOS_KEY] = $this->extract_fields($media, self::VIDEO_FIELDS);
                                        break;
                                    default:
                                        Log::warning("No mapping for sidecar Instagram media type {$media['type']}");
                                }
                            }
                        default:
                            Log::warning("No mapping for Instagram media type {$data['type']}");
                    }

                    $result[] = [ "owner_id" => $user->id,
                                  "data" => json_encode($data),
                                  "created_at" => gmdate("Y-m-d H:i:s", $post['createdTime']),
                                  "updated_at" => gmdate("Y-m-d H:i:s", $post['modified'])];
                }
            } catch (InstagramException $e) {
                Log::error("Instagram post fetch failure for user '{$user->$USERNAME_FIELD}': {$e->getMessage()}");
            }
        }

        Log::debug("Truncating Instagram posts table");

        // FIXME: Do we need some kind of locking?
        Post::truncate();
        Post::insert($result);

        Log::info("Added ".count($result)." Instagram posts to database");
    }

    public function scrapeStories() {
        $USERNAME_FIELD = Config::get('instagram-scrapper.table_pages.url_field');

        if (!$this->login()) {
            Log::error("No login success so skip Instagram scrapping");
            return;
        }

        $result = [];
        foreach ($this->getUsernames() as &$user) {
            try {
                Log::debug("Requesting stories for Instagram user '{$user->$USERNAME_FIELD}'");

                $userId = $this->instagram->getAccount($user->$USERNAME_FIELD)->getId();
                if (is_null($userId)) {
                    Log::error("No id resolved for Instagram user '{$user->$USERNAME_FIELD}'");
                    continue;
                }

                Log::debug("Resolved id for Instagram user '{$user->$USERNAME_FIELD}' to {$userId}");

                $user_stories = $this->instagram->getStories($userId);
                foreach ($user_stories as &$user_story) {
                    $stories = $user_story->getStories();
                    Log::debug("Processing ".count($stories)." stories for Instagram user '{$user->$USERNAME_FIELD}'");
                    foreach ($stories as &$story) {
                        $result[] = [ "owner_id" => $user->id,
                                      "data" => json_encode($this->extract_fields($story,
                                                                                  $story['type'] === "video" ? self::VIDEO_FIELDS : self::IMAGE_FIELDS)),
                                      "created_at" => gmdate("Y-m-d H:i:s", $story['createdTime']),
                                      "updated_at" => gmdate("Y-m-d H:i:s", $story['modified'])];
                    }
                }
            } catch (InstagramException $e) {
                Log::error("Instagram story fetch failure for user '{$user->$USERNAME_FIELD}':{$e->getMessage()}");
            }
        }

        Log::debug("Truncating Instagram stories table");

        // FIXME: Do we need some kind of locking?
        Story::truncate();
        Story::insert($result);

        Log::info("Added ".count($result)." Instagram stories to database");
    }

    private function getUsernames() {
        return array_map(function($row) {
            $pos = strrpos($row->instagram, "/");
            if ($pos === false) {
                return $row;
            }

            $len = strlen($row->instagram);
            if ($pos === ($len - 1)) {
                $pos2 = strrpos($row->instagram, "/", -($len - $pos + 1));
                $row->instagram = substr($row->instagram, $pos2 + 1, $pos - $pos2 - 1);
            } else {
                $row->instagram = substr($row->instagram, $pos + 1);
            }
            return $row;
        }, DB::table(Config::get('instagram-scrapper.table_pages.name'))->
               select(Config::get('instagram-scrapper.table_pages.id_field'), Config::get('instagram-scrapper.table_pages.url_field'))->
               whereNotNull(Config::get('instagram-scrapper.table_pages.url_field'))->
               get()->
               toArray());
    }
}
