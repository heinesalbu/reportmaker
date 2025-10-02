<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function company()
    {
        return view('settings.company', [
            'company_name'   => Setting::get('company_name', 'Ditt Firmanavn'),
            'company_footer' => Setting::get('company_footer', 'Generert av Reportmaker'),
            'logo_path'      => Setting::get('company_logo_path', null), // lagres på 'public' disk
        ]);
    }

    public function companySave(Request $r)
    {
        $data = $r->validate([
            'company_name'   => 'required|string|max:255',
            'company_footer' => 'nullable|string|max:1000',
            'logo'           => 'nullable|image|max:2048', // 2MB
        ]);

        Setting::set('company_name',   $data['company_name']);
        Setting::set('company_footer', $data['company_footer'] ?? '');

        if ($r->hasFile('logo')) {
            // lagres på public disk → tilgjengelig via /storage/…
            $path = $r->file('logo')->store('logos', 'public');
            Setting::set('company_logo_path', $path);
        }

        return back()->with('ok','Firmaopplysninger lagret.');
    }
}
