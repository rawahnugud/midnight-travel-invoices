<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BusinessSettingsController extends Controller
{
    /**
     * Store logo in public/business so no storage:link (symlink) is needed on shared hosts.
     */
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
            'logo' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
        ]);

        $business = BusinessSetting::get();

        if ($request->hasFile('logo')) {
            $dir = public_path('business');
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            if ($business->logo_path) {
                $oldPath = public_path($business->logo_path);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
            $file = $request->file('logo');
            $name = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $name);
            $validated['logo_path'] = 'business/' . $name;
        }

        unset($validated['logo']);
        $business->update($validated);

        return redirect()->route('settings.business.edit')
            ->with('success', 'Business settings saved.');
    }

    public function editDesign()
    {
        $business = BusinessSetting::get();
        return view('settings.design', ['business' => $business]);
    }

    public function updateDesign(Request $request)
    {
        $request->merge([
            'primary_color' => trim($request->input('primary_color', '')) ?: null,
            'accent_color' => trim($request->input('accent_color', '')) ?: null,
            'invoice_header_color' => trim($request->input('invoice_header_color', '')) ?: null,
        ]);
        $validated = $request->validate([
            'primary_color' => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{3,6}$/',
            'accent_color' => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{3,6}$/',
            'invoice_header_color' => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{3,6}$/',
            'login_logo' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
        ]);

        $business = BusinessSetting::get();

        if ($request->hasFile('login_logo')) {
            $dir = public_path('business');
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            if ($business->login_logo_path) {
                $oldPath = public_path($business->login_logo_path);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
            $file = $request->file('login_logo');
            $name = 'login_logo_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $name);
            $validated['login_logo_path'] = 'business/' . $name;
        }

        unset($validated['login_logo']);
        $business->update($validated);

        return redirect()->route('settings.design.edit')
            ->with('success', 'Design settings saved.');
    }
}
