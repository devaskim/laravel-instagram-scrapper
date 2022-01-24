<?php

namespace InstagramScrapper\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ScrapeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = "instagram:scrape {media_type=all}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Scrape Instagram media\'s urls.";

    /** @var \InstagramScrapper\Scrapper */
    protected $scrapper;

    public function __construct($scrapper)
    {
        $this->scrapper = $scrapper;
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $media_type = $this->argument('media_type');
        
        if (is_null($media_type) || $media_type === "") {
            $media_type = "all";
        }
        
        switch ($media_type) {
        case "story":
            $this->scrapper->scrapeStories();
            break;
        case "post":
            $this->scrapper->scrapePosts();
            break;
        case "all":
            $this->scrapper->scrapePosts();
            $this->scrapper->scrapeStories();
            break;
        }

        $this->info("Done Instagram scrapping, mode={$media_type}");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ["media_type", InputArgument::OPTIONAL, "The Instagram media type to scrape from user page (all|story|post)."],
        ];
    }
}
