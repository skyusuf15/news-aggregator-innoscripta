<?php

namespace App\Console\Commands;

use App\Services\News\NewsFetcher;
use Illuminate\Console\Command;
use Log;
use Illuminate\Support\Facades\Schedule;

#[Schedule('hourly')]
class FetchArticlesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:fetch-articles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store articles from external news APIs';

    /**
     * Execute the console command.
     */
    public function handle(NewsFetcher $fetcher)
    {
        $this->info('Fetching latest articles...');
        $fetcher->fetchAndStore();
        $this->info('Articles updated successfully.');

        return self::SUCCESS;
    }
}
