<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OperatingUnit;
use App\Models\UnitService;
use Illuminate\Support\Facades\DB;

class OperatingUnitController extends Controller
{
    public function index()
    {
        // Return units with their services
        return OperatingUnit::with('services')->get();
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        
        // Skip Header
        fgetcsv($handle); 

        DB::beginTransaction();
        
        try {
            // Optional: Reset tables to avoid duplicates
            // UnitService::truncate();
            // OperatingUnit::truncate();

            $currentOffice = null;

            while (($row = fgetcsv($handle)) !== false) {
                $officeName = trim($row[0]); // Column 0: Operating Units
                $serviceName = trim($row[1] ?? ''); // Column 1: Services Availed

                // 1. Handle "Fill Down" Logic
                if (!empty($officeName)) {
                    $currentOffice = $officeName;
                }

                // If we have a valid office (either current or from previous row)
                if ($currentOffice) {
                    // Create/Find Office
                    $unit = OperatingUnit::firstOrCreate(['name' => $currentOffice]);

                    // Create Service (if not empty)
                    if (!empty($serviceName)) {
                        $unit->services()->firstOrCreate(['name' => $serviceName]);
                    }
                }
            }

            DB::commit();
            fclose($handle);

            return response()->json(['message' => 'Operating Units updated successfully!']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}