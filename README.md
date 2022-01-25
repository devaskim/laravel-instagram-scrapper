# Installation
1. Add package repository to composer.json:
```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/denisdenisi4/laravel-instagram-scrapper"
    }
]
```

2. Install package:
```
composer require denisdenisi4/laravel-instagram-scrapper:*
```

3. Publish package resources:
```
php artisan vendor:publish --provider="InstagramScrapper\InstagramServiceProvider"
```

3. Run database migration:
```
php artisan migrate
```

# Configuration

See [this file](https://github.com/denisdenisi4/laravel-instagram-scrapper/blob/main/config/instagram-scrapper.php).

# Run

### Laravel command
```
cd <laravel_root_dir>
php artisan instagram:scrape [all|story|post]
```
### Scheduling in Kernel.php
```
protected function schedule(Schedule $schedule)
{
    $schedule->command('instagram:scrape')->everyFiveMinutes();
}
```
