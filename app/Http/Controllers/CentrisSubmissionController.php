<?php

namespace App\Http\Controllers;

use App\Models\CentrisSubmission;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CentrisSubmissionController extends Controller
{
    public function storeWebhook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'MLS' => 'required|string',
            'id_location' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $idLocation = $request->input('id_location');
        $mls = $request->input('MLS');
        $externalContactId = $this->normalizeNullableString($request->input('id'));

        $user = User::where('id_location', $idLocation)->first();

        if (!$user) {
            Log::warning('Centris submission rejected: location not found', [
                'id_location' => $idLocation,
                'mls' => $mls,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Location introuvable',
            ], 404);
        }

        $submission = CentrisSubmission::updateOrCreate(
            [
                'id_location' => $idLocation,
                'mls' => $mls,
                'external_contact_id' => $externalContactId,
            ],
            [
                'user_id' => $user->id,
                'first_name' => $this->normalizeNullableString($request->input('first_name')),
                'last_name' => $this->normalizeNullableString($request->input('last_name')),
                'email' => $this->normalizeNullableString($request->input('email')),
                'phone' => $this->normalizeNullableString($request->input('phone')),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Soumission Centris enregistrée',
            'submission' => $submission,
        ]);
    }

    private function normalizeNullableString($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || strtolower($value) === 'null') {
            return null;
        }

        return $value;
    }
}
