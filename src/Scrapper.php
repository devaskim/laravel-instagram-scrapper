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
    
    private function extract_instagram_images($media) {
        $images = [];
        if ($media["imageLowResolutionUrl"] !== "") { $images["imageLowResolutionUrl"] = $media["imageLowResolutionUrl"]; }
        if ($media["imageThumbnailUrl"] !== "") { $images["imageThumbnailUrl"] = $media["imageThumbnailUrl"]; }
        if ($media["imageStandardResolutionUrl"] !== "") { $images["imageStandardResolutionUrl"] = $media["imageStandardResolutionUrl"]; }
        if ($media["imageHighResolutionUrl"] !== "") { $images["imageHighResolutionUrl"] = $media["imageHighResolutionUrl"]; }
        return $images;
    }
    
    private function extract_instagram_videos($media, $with_stats = true) {
        $videos = [];
        if ($media["videoLowResolutionUrl"] !== "") { $videos["videoLowResolutionUrl"] = $media["videoLowResolutionUrl"]; }
        if ($media["videoStandardResolutionUrl"] !== "") { $videos["videoStandardResolutionUrl"] = $media["videoStandardResolutionUrl"]; }
        if ($media["videoLowBandwidthUrl"] !== "") { $videos["videoLowBandwidthUrl"] = $media["videoLowBandwidthUrl"]; }
        if ($with_stats && $media["videoDuration"] !== "") { $videos["videoDuration"] = $media["videoDuration"]; }
        if ($with_stats && $media["videoViews"] !== "") { $videos["videoViews"] = $media["videoViews"]; }
        return $videos;
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
                    $data = ['type' => $post['type'],
                             "id" => $post["id"],
                             "shortCode" => $post["shortCode"],
                             "link" => $post["link"],
                             "likesCount" => $post["likesCount"],
                             "commentsCount" => $post["commentsCount"]];
                             
                    if ($post["caption"] !== "") { $data["caption"] = $post["caption"]; }
                    if ($post["altText"] !== "") { $data["altText"] = $post["altText"]; }

                    if ($post["locationId"] !== "") { $data["locationId"] = $post["locationId"]; }
                    if ($post["locationName"] !== "") { $data["locationName"] = $post["locationName"]; }
                    if ($post["locationSlug"] !== "") { $data["locationSlug"] = $post["locationSlug"]; }

                    $data = array_merge($data, $this->extract_instagram_images($post));
                             
                    switch ($data['type']) {
                    case "image":
                    break;
                    case "video":
                        $data = array_merge($data, $this->extract_instagram_videos($post));
                    break;
                    case "sidecar":
                        foreach ($post["sidecarMedias"] as &$media) {
                            switch ($media['type']) {
                            case "image":
                                $data["sidecar_images"] = $this->extract_instagram_images($media);
                            break;
                            case "video":
                                $data["sidecar_videos"] = $this->extract_instagram_videos($media);
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
                                      "data" => json_encode($this->extract_instagram_videos($story, false)),
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
