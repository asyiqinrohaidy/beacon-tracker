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

    // Predict location using Weighted KNN algorithm
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

        // Sort by distance and take K=5 nearest neighbors
        $k = 5;
        $nearest = $distances->sortBy('distance')->take($k);

        // Weighted KNN — closer neighbors get more weight (1/distance)
        $weightedVotes = [];
        foreach ($nearest as $neighbor) {
            $location = $neighbor['location_name'];
            $weight = $neighbor['distance'] > 0 ? 1 / $neighbor['distance'] : 100;

            if (!isset($weightedVotes[$location])) {
                $weightedVotes[$location] = 0;
            }
            $weightedVotes[$location] += $weight;
        }

        // Sort by highest weight
        arsort($weightedVotes);
        $predictedLocation = array_key_first($weightedVotes);
        $nearestSpot = $nearest->first()['spot_name'];

        return response()->json([
            'predicted_location' => $predictedLocation,
            'nearest_spot'       => $nearestSpot,
            'gateway_1_rssi'     => $liveG1,
            'gateway_2_rssi'     => $liveG2,
            'neighbors'          => $nearest->values(),
            'weighted_votes'     => $weightedVotes,
        ]);
    }

    // Delete a single fingerprint
    public function destroy($id)
    {
        Fingerprint::findOrFail($id)->delete();
        return response()->json(['message' => 'Fingerprint deleted']);
    }

    // Delete all fingerprints (reset training)
    public function reset()
    {
        Fingerprint::truncate();
        return response()->json(['message' => 'Fingerprint data reset successfully']);
    }
}