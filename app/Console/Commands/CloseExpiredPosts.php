<?php

namespace App\Console\Commands;

use App\Enum\OpenCloseEnum;
use App\Models\RequestPost;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('app:close-expired-posts')]
#[Description('Command description')]
class CloseExpiredPosts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
         RequestPost::where('status', OpenCloseEnum::OPEN->value)
            ->where('published_until', '<', Carbon::now())
            ->update([
                'status' => OpenCloseEnum::CLOSED->value
            ]);

        $this->info('Expired posts closed successfully.');
    }
}
