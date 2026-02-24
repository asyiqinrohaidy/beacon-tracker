<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fingerprint;
use Illuminate\Http\Request;

class FingerprintController extends Controller
{
    // Save a fingerprint reading during training
    public function train(Request $request)
    {
        $fingerprint = Fingerprint::create([
            'spot_name'      => $request->spot_name,
            'location_name'  => $request->location_name,
            'gateway_1_rssi' => $request->gateway_1_rssi,
            'gateway_2_rssi' => $request->gateway_2_rssi,
        ]);

        return response()->json(['message' => 'Fingerprint saved', 'data' => $fingerprint]);
    }

    // Get all saved fingerprints
    public function index()
    {
        return response()->json(Fingerprint::all());
    }

    // Predict location using KNN algorithm
    public function predict(Request $request)
    {
        $liveG1 = $request->gateway_1_rssi;
        $liveG2 = $request->gateway_2_rssi;

        $fingerprints = Fingerprint::all();

        if ($fingerprints->isEmpty()) {
            return response()->json(['message' => 'No fingerprint data available'], 404);
        }

        // Calculate Euclidean distance from live RSSI to each fingerprint
        $distances = $fingerprints->map(function ($fp) use ($liveG1, $liveG2) {
            $distance = sqrt(
                pow($liveG1 - $fp->gateway_1_rssi, 2) +
                pow($liveG2 - $fp->gateway_2_rssi, 2)
            );
            return [
                'spot_name'     => $fp->spot_name,
                'location_name' => $fp->location_name,
                'distance'      => $distance,
            ];
        });

        // Sort by distance and take K=3 nearest neighbors
        $k = 3;
        $nearest = $distances->sortBy('distance')->take($k);

        // Vote for the most common location among neighbors
        $votes = $nearest->groupBy('location_name')
            ->map(fn($group) => $group->count())
            ->sortDesc();

        $predictedLocation = $votes->keys()->first();
        $nearestSpot = $nearest->first()['spot_name'];

        return response()->json([
            'predicted_location' => $predictedLocation,
            'nearest_spot'       => $nearestSpot,
            'gateway_1_rssi'     => $liveG1,
            'gateway_2_rssi'     => $liveG2,
            'neighbors'          => $nearest->values(),
        ]);
    }

    // Delete all fingerprints (reset training)
    public function reset()
    {
        Fingerprint::truncate();
        return response()->json(['message' => 'Fingerprint data reset successfully']);
    }
}