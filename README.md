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

# Configuration

1. Publish package resources:
```
php artisan vendor:publish --provider="InstagramScrapper\InstagramServiceProvider"
```

2. Edit [configuration file](https://github.com/denisdenisi4/laravel-instagram-scrapper/blob/main/config/instagram-scrapper.php).

3. Run database migration:
```
php artisan migrate
```

# Run

### Laravel command
```
cd <laravel_root_dir>
php artisan instagram:scrape [all|story|post]
```
### Scheduling
```
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Default mode (i.e. 'all')
    $schedule->command('instagram:scrape')->everyTwoHours();
    // or
    $schedule->command('instagram:scrape', ['all'])->everyTwoHours();
    
    // Story mode
    $schedule->command('instagram:scrape', ['story'])->daily();
    // Post mode
    $schedule->command('instagram:scrape', ['post'])->daily();
}
```

# Database data examples
See all available JSON fields [here](https://github.com/denisdenisi4/laravel-instagram-scrapper/blob/main/src/Scrapper.php#L20-L45).

### Story
```json
{
    "videoViews": 0,
    "videoDuration": 15,
    "videoLowBandwidthUrl": "https://instagram.frix7-1.fna.fbcdn.net/v/t50.12441-16/272477480_1682609335419630_7105022400587119230_n.mp4?_nc_ht=instagram.frix7-1.fna.fbcdn.net&_nc_cat=106&_nc_ohc=RrBSG2NJ_vAAX9rkia7&edm=AHlfZHwBAAAA&ccb=7-4&oe=61F24F07&oh=00_AT-1_2qN5Q52zN2LWpKANSe75I2clJ7sPWhhib6rtmhdOQ&_nc_sid=21929d",
    "videoLowResolutionUrl": "https://instagram.frix7-1.fna.fbcdn.net/v/t50.12441-16/272477480_1682609335419630_7105022400587119230_n.mp4?_nc_ht=instagram.frix7-1.fna.fbcdn.net&_nc_cat=106&_nc_ohc=RrBSG2NJ_vAAX9rkia7&edm=AHlfZHwBAAAA&ccb=7-4&oe=61F24F07&oh=00_AT-1_2qN5Q52zN2LWpKANSe75I2clJ7sPWhhib6rtmhdOQ&_nc_sid=21929d",
    "videoStandardResolutionUrl": "https://instagram.frix7-1.fna.fbcdn.net/v/t50.12441-16/272409638_674374773904609_4930979133200684146_n.mp4?_nc_ht=instagram.frix7-1.fna.fbcdn.net&_nc_cat=101&_nc_ohc=Kai5vl4pySgAX9hrbwr&edm=AHlfZHwBAAAA&ccb=7-4&oe=61F24936&oh=00_AT_7uRWg9etJZ_bPdAYWvSlNq_fkqhHMj7DeNEsvVLRzFQ&_nc_sid=21929d"
}
```

```json
{
    "imageThumbnailUrl": "https://instagram.frix7-1.fna.fbcdn.net/v/t51.2885-15/sh0.08/e35/p640x640/272702841_663137011393108_8591014142494865157_n.jpg?_nc_ht=instagram.frix7-1.fna.fbcdn.net&_nc_cat=1&_nc_ohc=-mholYSZN68AX-eXvK6&edm=AHlfZHwBAAAA&ccb=7-4&oh=00_AT_U3Xm4STsY-j-frmFVQCLSSlptCLNRsxIf9zYGsFeyfQ&oe=61F22423&_nc_sid=21929d",
    "imageLowResolutionUrl": "https://instagram.frix7-1.fna.fbcdn.net/v/t51.2885-15/sh0.08/e35/p750x750/272702841_663137011393108_8591014142494865157_n.jpg?_nc_ht=instagram.frix7-1.fna.fbcdn.net&_nc_cat=1&_nc_ohc=-mholYSZN68AX-eXvK6&edm=AHlfZHwBAAAA&ccb=7-4&oh=00_AT-YK4888gwOAqzSAhIsagXE3_UFFFald0fR6aVtSdxkmg&oe=61F22527&_nc_sid=21929d",
    "imageHighResolutionUrl": "https://instagram.frix7-1.fna.fbcdn.net/v/t51.2885-15/e35/272702841_663137011393108_8591014142494865157_n.jpg?_nc_ht=instagram.frix7-1.fna.fbcdn.net&_nc_cat=1&_nc_ohc=-mholYSZN68AX-eXvK6&edm=AHlfZHwBAAAA&ccb=7-4&oh=00_AT-0qhSMXS7lkBqrIFaxy-gGNR-WZP_8P7W8V0cfPRw07Q&oe=61F20249&_nc_sid=21929d",
    "imageStandardResolutionUrl": "https://instagram.frix7-1.fna.fbcdn.net/v/t51.2885-15/e35/272702841_663137011393108_8591014142494865157_n.jpg?_nc_ht=instagram.frix7-1.fna.fbcdn.net&_nc_cat=1&_nc_ohc=-mholYSZN68AX-eXvK6&edm=AHlfZHwBAAAA&ccb=7-4&oh=00_AT-0qhSMXS7lkBqrIFaxy-gGNR-WZP_8P7W8V0cfPRw07Q&oe=61F20249&_nc_sid=21929d"
}
```

### Post
```json
{
    "id": "2758855586746699748",
    "link": "https://www.instagram.com/p/CZJbIoCo4_k",
    "type": "image",
    "altText": "Photo by FC Barcelona in Ｈａｐｐｙ・Ｂｉｒｔｈｄａｙ. May be an image of one or more people and people standing.",
    "caption": "Per molts anys, míster! 🎉\nHB Legend 💙❤️\n¡Feliz cumpleaños, @xavi! 🎂\n\n🖌: @pkpcreative",
    "shortCode": "CZJbIoCo4_k",
    "likesCount": 180284,
    "locationId": "315264827",
    "locationName": "Ｈａｐｐｙ・Ｂｉｒｔｈｄａｙ",
    "locationSlug": "happybirthday",
    "commentsCount": 1003,
    "imageThumbnailUrl": "https://instagram.frix7-1.fna.fbcdn.net/v/t51.2885-15/sh0.08/e35/c0.135.1080.1080a/s640x640/272636043_881924435811887_6581655216661924651_n.jpg?_nc_ht=instagram.frix7-1.fna.fbcdn.net&_nc_cat=1&_nc_ohc=DIkJ3fPeZbgAX_dM7PD&tn=xy9het9lcq4ayvcI&edm=ABfd0MgBAAAA&ccb=7-4&oh=00_AT9UQ4HrVLyzHlV80B4OKqLa08NMb_0HrtYyqZ0R9pjUsA&oe=61F732E0&_nc_sid=7bff83",
    "imageHighResolutionUrl": "https://instagram.frix7-1.fna.fbcdn.net/v/t51.2885-15/fr/e15/p1080x1080/272636043_881924435811887_6581655216661924651_n.jpg?_nc_ht=instagram.frix7-1.fna.fbcdn.net&_nc_cat=1&_nc_ohc=DIkJ3fPeZbgAX_dM7PD&tn=xy9het9lcq4ayvcI&edm=ABfd0MgBAAAA&ccb=7-4&oh=00_AT8eBkg00qeVuAv0HoDzzYLfsXoWP0E0aY5CsoT484ipQw&oe=61F70FEB&_nc_sid=7bff83"
}
```
