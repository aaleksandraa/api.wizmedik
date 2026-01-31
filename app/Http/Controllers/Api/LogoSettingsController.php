<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class LogoSettingsController extends Controller
{
    /**
     * Get logo settings
     */
    public function index()
    {
        $logoUrl = SiteSetting::where('key', 'logo_url')->value('value');
        $logoEnabled = SiteSetting::where('key', 'logo_enabled')->value('value');
        $logoType = SiteSetting::where('key', 'logo_type')->value('value');

        // Footer logo settings
        $footerLogoUrl = SiteSetting::where('key', 'footer_logo_url')->value('value');
        $footerLogoEnabled = SiteSetting::where('key', 'footer_logo_enabled')->value('value');
        $footerLogoType = SiteSetting::where('key', 'footer_logo_type')->value('value');
        $showHeartIcon = SiteSetting::where('key', 'show_heart_icon')->value('value');
        $showHeartIconHeader = SiteSetting::where('key', 'show_heart_icon_header')->value('value');

        // Logo height settings
        $logoHeightDesktop = SiteSetting::where('key', 'logo_height_desktop')->value('value') ?: 70;
        $logoHeightMobile = SiteSetting::where('key', 'logo_height_mobile')->value('value') ?: 50;
        $footerLogoHeightDesktop = SiteSetting::where('key', 'footer_logo_height_desktop')->value('value') ?: 70;
        $footerLogoHeightMobile = SiteSetting::where('key', 'footer_logo_height_mobile')->value('value') ?: 50;

        // Convert string to boolean properly
        $isEnabled = false;
        if ($logoEnabled === '1' || $logoEnabled === 'true' || $logoEnabled === true || $logoEnabled === 1) {
            $isEnabled = true;
        }

        $isFooterEnabled = false;
        if ($footerLogoEnabled === '1' || $footerLogoEnabled === 'true' || $footerLogoEnabled === true || $footerLogoEnabled === 1) {
            $isFooterEnabled = true;
        }

        $isHeartIconEnabled = true; // default true
        if ($showHeartIcon === '0' || $showHeartIcon === 'false' || $showHeartIcon === false || $showHeartIcon === 0) {
            $isHeartIconEnabled = false;
        }

        $isHeartIconHeaderEnabled = true; // default true
        if ($showHeartIconHeader === '0' || $showHeartIconHeader === 'false' || $showHeartIconHeader === false || $showHeartIconHeader === 0) {
            $isHeartIconHeaderEnabled = false;
        }

        return response()->json([
            'logo_url' => $logoUrl ?: null,
            'logo_enabled' => $isEnabled,
            'logo_type' => $logoType ?: 'text',
            'footer_logo_url' => $footerLogoUrl ?: null,
            'footer_logo_enabled' => $isFooterEnabled,
            'footer_logo_type' => $footerLogoType ?: 'text',
            'show_heart_icon' => $isHeartIconEnabled,
            'show_heart_icon_header' => $isHeartIconHeaderEnabled,
            'logo_height_desktop' => (int)$logoHeightDesktop,
            'logo_height_mobile' => (int)$logoHeightMobile,
            'footer_logo_height_desktop' => (int)$footerLogoHeightDesktop,
            'footer_logo_height_mobile' => (int)$footerLogoHeightMobile,
        ]);
    }

    /**
     * Update logo settings (Admin only)
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'logo_url' => 'nullable|string|max:500',
            'logo_enabled' => 'required|boolean',
            'logo_type' => 'required|in:image,text',
            'footer_logo_url' => 'nullable|string|max:500',
            'footer_logo_enabled' => 'required|boolean',
            'footer_logo_type' => 'required|in:image,text',
            'show_heart_icon' => 'required|boolean',
            'show_heart_icon_header' => 'required|boolean',
            'logo_height_desktop' => 'required|integer|min:20|max:200',
            'logo_height_mobile' => 'required|integer|min:20|max:200',
            'footer_logo_height_desktop' => 'required|integer|min:20|max:200',
            'footer_logo_height_mobile' => 'required|integer|min:20|max:200',
        ]);

        // Update or create logo_url
        $logoUrl = SiteSetting::where('key', 'logo_url')->first();
        if ($logoUrl) {
            $logoUrl->value = $validated['logo_url'] ?? '';
            $logoUrl->save();
        } else {
            SiteSetting::create([
                'key' => 'logo_url',
                'value' => $validated['logo_url'] ?? ''
            ]);
        }

        // Update or create logo_enabled
        $logoEnabled = SiteSetting::where('key', 'logo_enabled')->first();
        if ($logoEnabled) {
            $logoEnabled->value = $validated['logo_enabled'] ? '1' : '0';
            $logoEnabled->save();
        } else {
            SiteSetting::create([
                'key' => 'logo_enabled',
                'value' => $validated['logo_enabled'] ? '1' : '0'
            ]);
        }

        // Update or create logo_type
        $logoType = SiteSetting::where('key', 'logo_type')->first();
        if ($logoType) {
            $logoType->value = $validated['logo_type'];
            $logoType->save();
        } else {
            SiteSetting::create([
                'key' => 'logo_type',
                'value' => $validated['logo_type']
            ]);
        }

        // Update or create footer_logo_url
        $footerLogoUrl = SiteSetting::where('key', 'footer_logo_url')->first();
        if ($footerLogoUrl) {
            $footerLogoUrl->value = $validated['footer_logo_url'] ?? '';
            $footerLogoUrl->save();
        } else {
            SiteSetting::create([
                'key' => 'footer_logo_url',
                'value' => $validated['footer_logo_url'] ?? ''
            ]);
        }

        // Update or create footer_logo_enabled
        $footerLogoEnabled = SiteSetting::where('key', 'footer_logo_enabled')->first();
        if ($footerLogoEnabled) {
            $footerLogoEnabled->value = $validated['footer_logo_enabled'] ? '1' : '0';
            $footerLogoEnabled->save();
        } else {
            SiteSetting::create([
                'key' => 'footer_logo_enabled',
                'value' => $validated['footer_logo_enabled'] ? '1' : '0'
            ]);
        }

        // Update or create footer_logo_type
        $footerLogoType = SiteSetting::where('key', 'footer_logo_type')->first();
        if ($footerLogoType) {
            $footerLogoType->value = $validated['footer_logo_type'];
            $footerLogoType->save();
        } else {
            SiteSetting::create([
                'key' => 'footer_logo_type',
                'value' => $validated['footer_logo_type']
            ]);
        }

        // Update or create show_heart_icon
        $showHeartIcon = SiteSetting::where('key', 'show_heart_icon')->first();
        if ($showHeartIcon) {
            $showHeartIcon->value = $validated['show_heart_icon'] ? '1' : '0';
            $showHeartIcon->save();
        } else {
            SiteSetting::create([
                'key' => 'show_heart_icon',
                'value' => $validated['show_heart_icon'] ? '1' : '0'
            ]);
        }

        // Update or create show_heart_icon_header
        $showHeartIconHeader = SiteSetting::where('key', 'show_heart_icon_header')->first();
        if ($showHeartIconHeader) {
            $showHeartIconHeader->value = $validated['show_heart_icon_header'] ? '1' : '0';
            $showHeartIconHeader->save();
        } else {
            SiteSetting::create([
                'key' => 'show_heart_icon_header',
                'value' => $validated['show_heart_icon_header'] ? '1' : '0'
            ]);
        }

        // Update or create logo_height_desktop
        $logoHeightDesktop = SiteSetting::where('key', 'logo_height_desktop')->first();
        if ($logoHeightDesktop) {
            $logoHeightDesktop->value = (string)$validated['logo_height_desktop'];
            $logoHeightDesktop->save();
        } else {
            SiteSetting::create([
                'key' => 'logo_height_desktop',
                'value' => (string)$validated['logo_height_desktop']
            ]);
        }

        // Update or create logo_height_mobile
        $logoHeightMobile = SiteSetting::where('key', 'logo_height_mobile')->first();
        if ($logoHeightMobile) {
            $logoHeightMobile->value = (string)$validated['logo_height_mobile'];
            $logoHeightMobile->save();
        } else {
            SiteSetting::create([
                'key' => 'logo_height_mobile',
                'value' => (string)$validated['logo_height_mobile']
            ]);
        }

        // Update or create footer_logo_height_desktop
        $footerLogoHeightDesktop = SiteSetting::where('key', 'footer_logo_height_desktop')->first();
        if ($footerLogoHeightDesktop) {
            $footerLogoHeightDesktop->value = (string)$validated['footer_logo_height_desktop'];
            $footerLogoHeightDesktop->save();
        } else {
            SiteSetting::create([
                'key' => 'footer_logo_height_desktop',
                'value' => (string)$validated['footer_logo_height_desktop']
            ]);
        }

        // Update or create footer_logo_height_mobile
        $footerLogoHeightMobile = SiteSetting::where('key', 'footer_logo_height_mobile')->first();
        if ($footerLogoHeightMobile) {
            $footerLogoHeightMobile->value = (string)$validated['footer_logo_height_mobile'];
            $footerLogoHeightMobile->save();
        } else {
            SiteSetting::create([
                'key' => 'footer_logo_height_mobile',
                'value' => (string)$validated['footer_logo_height_mobile']
            ]);
        }

        return response()->json([
            'message' => 'Logo postavke ažurirane',
            'settings' => [
                'logo_url' => $validated['logo_url'] ?? '',
                'logo_enabled' => $validated['logo_enabled'],
                'logo_type' => $validated['logo_type'],
                'footer_logo_url' => $validated['footer_logo_url'] ?? '',
                'footer_logo_enabled' => $validated['footer_logo_enabled'],
                'footer_logo_type' => $validated['footer_logo_type'],
                'show_heart_icon' => $validated['show_heart_icon'],
                'show_heart_icon_header' => $validated['show_heart_icon_header'],
                'logo_height_desktop' => $validated['logo_height_desktop'],
                'logo_height_mobile' => $validated['logo_height_mobile'],
                'footer_logo_height_desktop' => $validated['footer_logo_height_desktop'],
                'footer_logo_height_mobile' => $validated['footer_logo_height_mobile'],
            ],
        ]);
    }
}
