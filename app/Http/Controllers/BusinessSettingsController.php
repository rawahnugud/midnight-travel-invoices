<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BusinessSettingsController extends Controller
{
    public function edit()
    {
        $business = BusinessSetting::get();
        return view('settings.business', ['business' => $business]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'default_currency' => 'nullable|string|max:10',
            'tax_id' => 'nullable|string|max:64',
            'logo' => 'nullable|image|mimes:jpeg,png,gif,webp,svg|max:2048',
        ]);

        $business = BusinessSetting::get();

        if ($request->hasFile('logo')) {
            if ($business->logo_path) {
                Storage::disk('public')->delete($business->logo_path);
            }
            $path = $request->file('logo')->store('business', 'public');
            $validated['logo_path'] = $path;
        }

        unset($validated['logo']);
        $business->update($validated);

        return redirect()->route('settings.business.edit')
            ->with('success', 'Business settings saved.');
    }
}
