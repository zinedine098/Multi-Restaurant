<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $restaurantId = $request->restaurant_id ?? $user->restaurant_id;

        if (!$user->hasRole(['owner', 'admin'])) {
            $restaurantId = $user->restaurant_id;
        }

        $settings = Setting::where('restaurant_id', $restaurantId)->orderBy('key')->get();

        return $this->sendResponse($settings);
    }

    public function upsert(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'sometimes|required|exists:restaurants,id',
            'key' => 'required|string|max:100',
            'value' => 'nullable|string',
            'type' => 'nullable|in:string,number,boolean,json',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $restaurantId = $request->restaurant_id ?? $user->restaurant_id;
        if (!$user->hasRole(['owner', 'admin'])) {
            $restaurantId = $user->restaurant_id;
        }

        $setting = Setting::updateOrCreate(
            ['restaurant_id' => $restaurantId, 'key' => $request->key],
            ['value' => $request->value, 'type' => $request->type ?? 'string', 'description' => $request->description]
        );

        return $this->sendResponse($setting, 'Setting berhasil disimpan.');
    }

    public function destroy(Setting $setting): JsonResponse
    {
        $setting->delete();
        return $this->sendResponse(null, 'Setting berhasil dihapus.');
    }
}
