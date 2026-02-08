<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SentimentResult;
use Illuminate\Support\Facades\DB;

class ImportThesisData extends Command
{
    protected $signature = 'thesis:import';
    protected $description = 'Import analyzed CSV results into MySQL';

    public function handle()
    {
        $file = storage_path('app/thesis_data.csv');

        if (!file_exists($file)) {
            $this->error("❌ File not found at: $file");
            return;
        }

        $this->info("🚀 Starting Import... This might take a few seconds.");
        
        // Open file
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle); // Get header row to find column indices

        // 1. Map Columns Dynamically (Find index of each column)
        $officeIdx    = array_search('Office/Unit Visited', $header);
        $commentIdx   = array_search('cleaned_text', $header);
        $sentimentIdx = array_search('predicted_sentiment', $header);
        $aspectIdx    = array_search('predicted_category', $header);
        $scoreIdx     = array_search('confidence_score', $header);

        // 2. Identify all "Service/s availed" column indices
        $serviceIndices = [];
        foreach ($header as $index => $colName) {
            if (str_starts_with($colName, 'Service/s availed')) {
                $serviceIndices[] = $index;
            }
        }

        SentimentResult::truncate(); // Clear old data

        $count = 0;
        DB::beginTransaction(); // Faster insert

        while (($row = fgetcsv($handle)) !== false) {
            // Merge Services
            $services = [];
            foreach ($serviceIndices as $idx) {
                if (!empty($row[$idx])) {
                    $services[] = trim($row[$idx]);
                }
            }
            $servicesStr = !empty($services) ? implode(', ', $services) : 'Unspecified';

            SentimentResult::create([
                'office_name'      => $row[$officeIdx] ?? 'Unknown',
                'services_availed' => $servicesStr,
                'comment'          => $row[$commentIdx] ?? '',
                'sentiment'        => ucfirst($row[$sentimentIdx] ?? 'Neutral'),
                'aspect'           => ucfirst($row[$aspectIdx] ?? 'General'),
                'confidence_score' => (float) ($row[$scoreIdx] ?? 0.0),
            ]);
            $count++;
        }

        DB::commit();
        fclose($handle);
        $this->info("🎉 DONE! Imported $count rows successfully.");
    }
}