<?php
namespace InstagramScrapper\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * Post model
 *
 * @property integer $id
 * @property integer $owner_id
 * @property json  $data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Post extends Model {

    protected $guarded = array('id');
    
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        $this->table = Config::get('instagram-scrapper.table_posts');
    }
}
