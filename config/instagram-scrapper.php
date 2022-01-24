<?php
return [
    /*
     * Instagram credentials.
     */    
    'username' => '',
    'password' => '',

    /*
     * Cookies fetched from one of browser cookies viewer plugin.
     */ 
    'cookies'  => [
        'ig_did'     =>	'',
        'mid'        =>	'',
        'sessionid'  =>	'',
        'csrftoken'  =>	'',
        'ds_user_id' =>	''
    ],
    
    /*
     * Database source/destination tables.
     */
    'table_stories' => 'instagram_stories',
    'table_posts' => 'instagram_posts',
    'table_pages' => [
        'name' => 'companies',
        'url_field' => 'instagram',
        'id_field' => 'id'
    ],

    /*
     * Indicates how many last posts to scrape each time.
     */    
    'max_post_count' => 1,

    /*
     * Fake user agent.
     */
    'user_agent' => 'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:96.0) Gecko/20100101 Firefox/96.0',
    
    /*
     * Instagram API to resolve user id.
     */
    'user_id_endpoint' => 'https://www.instagram.com/%s/?__a=1',
];
