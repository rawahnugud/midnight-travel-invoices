<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            'stamp' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
        ]);

        $business = BusinessSetting::get();

        if ($request->hasFile('stamp')) {
            $dir = public_path('business');
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            if ($business->stamp_path) {
                $oldPath = public_path($business->stamp_path);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
            $file = $request->file('stamp');
            $name = 'stamp_' . Str::uuid() . '.' . $file->guessExtension();
            $file->move($dir, $name);
            $validated['stamp_path'] = 'business/' . $name;
        }

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
            $name = 'logo_' . Str::uuid() . '.' . $file->guessExtension();
            $file->move($dir, $name);
            $validated['logo_path'] = 'business/' . $name;
        }

        unset($validated['logo'], $validated['stamp']);
        $business->update($validated);
        BusinessSetting::clearCache();

        return redirect()->route('settings.business.edit')
            ->with('success', __('messages.business_saved'));
    }

    public function updateBranch(Request $request, string $branch)
    {
        $imageRule = 'nullable|image|mimes:jpeg,png,gif,webp|max:2048';
        $data = $request->validate([
            'details' => 'sometimes|array:'.implode(',', BusinessSetting::PROFILE_FIELDS),
            'details.company_name' => 'nullable|string|max:255',
            'details.tagline' => 'nullable|string|max:255',
            'details.address' => 'nullable|string|max:500',
            'details.phone' => 'nullable|string|max:64',
            'details.email' => 'nullable|email|max:255',
            'details.website' => 'nullable|url|max:255',
            'details.tax_id' => 'nullable|string|max:64',
            'details.default_currency' => 'nullable|in:'.implode(',', BusinessSetting::CURRENCIES),
            'logo_mode' => 'required|in:shared,custom',
            'stamp_mode' => 'required|in:none,shared,custom',
            'logo' => $imageRule,
            'stamp' => $imageRule,
        ]);

        $id = BusinessSetting::get()->id;
        $added = [];
        $replaced = [];
        try {
            DB::transaction(function () use ($id, $branch, $data, &$added, &$replaced) {
                $business = BusinessSetting::lockForUpdate()->findOrFail($id);
                $profiles = $business->branch_branding ?? [];
                $profile = $profiles[$branch] ?? [];
                foreach (['logo', 'stamp'] as $type) {
                    $mode = $data[$type.'_mode'];
                    $pathKey = $type.'_path';
                    $file = $data[$type] ?? null;
                    if ($file && $mode !== 'custom') {
                        throw ValidationException::withMessages([$type => __('messages.select_custom_upload')]);
                    }
                    if ($mode === 'custom' && !$file && empty($profile[$pathKey])) {
                        throw ValidationException::withMessages([$type => __('messages.choose_branch_image')]);
                    }
                    if ($file) {
                        $dir = public_path('business');
                        File::ensureDirectoryExists($dir);
                        $name = $branch.'_'.$type.'_'.Str::uuid().'.'.$file->guessExtension();
                        $file->move($dir, $name);
                        $added[] = $dir.'/'.$name;
                        if (!empty($profile[$pathKey])) {
                            $replaced[] = public_path($profile[$pathKey]);
                        }
                        $profile[$pathKey] = 'business/'.$name;
                    }
                    $profile[$type.'_mode'] = $mode;
                }
                if (isset($data['details'])) {
                    $profile['details'] = array_replace($profile['details'] ?? [], $data['details']);
                }
                $profiles[$branch] = $profile;
                $business->branch_branding = $profiles;
                $business->save();
            });
        } catch (\Throwable $error) {
            File::delete($added);
            throw $error;
        }
        BusinessSetting::clearCache();
        File::delete($replaced);

        return redirect()->route('settings.business.edit')->with('success', __('messages.business_saved'));
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
            'browser_icon' => 'nullable|image|mimes:png|max:2048|dimensions:ratio=1/1',
            'login_logo' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
        ]);

        $id = BusinessSetting::get()->getKey();
        $added = [];
        $replaced = [];
        try {
            DB::transaction(function () use ($id, $validated, &$added, &$replaced) {
                $business = BusinessSetting::lockForUpdate()->findOrFail($id);
                foreach (['login_logo', 'browser_icon'] as $field) {
                    $file = $validated[$field] ?? null;
                    unset($validated[$field]);
                    if (!$file) {
                        continue;
                    }
                    $column = $field.'_path';
                    $name = $field.'_'.Str::uuid().'.'.$file->guessExtension();
                    File::ensureDirectoryExists(public_path('business'));
                    $file->move(public_path('business'), $name);
                    $added[] = public_path('business/'.$name);
                    if ($business->$column) {
                        $replaced[] = public_path($business->$column);
                    }
                    $validated[$column] = 'business/'.$name;
                }
                $business->update($validated);
            });
        } catch (\Throwable $error) {
            File::delete($added);
            throw $error;
        }
        File::delete($replaced);
        BusinessSetting::clearCache();

        return redirect()->route('settings.design.edit')
            ->with('success', __('messages.design_saved'));
    }
}
