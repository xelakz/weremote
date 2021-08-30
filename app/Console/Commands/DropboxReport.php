<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Facades\Dropbox;

class DropboxReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dropbox {--report=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "Processing...Please wait." . PHP_EOL;
        $report = $this->option('report') ?? null;
        switch ($report) {
            case 4:
                Dropbox::report4();
                break;
            case 3:
                Dropbox::report3();
                break;
            case 2:
                Dropbox::report2();
                break;
            case 1:
                Dropbox::report1();
                break;
            default:

                break;
        }
        echo "Process Finished" . PHP_EOL;
    }
}
