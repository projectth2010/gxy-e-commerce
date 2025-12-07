<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CheckTableStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:table-structure {table=subscriptions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the structure of a database table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table = $this->argument('table');
        
        if (!Schema::hasTable($table)) {
            $this->error("Table [{$table}] does not exist!");
            return 1;
        }
        
        $columns = Schema::getColumnListing($table);
        
        $this->info("Table: {$table}");
        $this->info(str_repeat('-', 80));
        
        $rows = [];
        foreach ($columns as $column) {
            $type = DB::getSchemaBuilder()->getColumnType($table, $column) ?: 'unknown';
            $rows[] = [
                'column' => $column,
                'type' => $type
            ];
        }
        
        $this->table(['Column', 'Type'], $rows);
        
        // Show sample data
        $this->info("\nSample data (first 5 rows):");
        $this->info(str_repeat('-', 80));
        
        $data = DB::table($table)->take(5)->get();
        $this->line($data->toJson(JSON_PRETTY_PRINT));
        
        return 0;
    }
}
