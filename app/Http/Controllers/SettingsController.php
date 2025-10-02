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
// NY METODE for å vise PDF-innstillingssiden
    public function pdf()
    {
        $settings = Setting::where('key', 'pdf_styles')->value('value') ?? [];
        return view('settings.pdf', compact('settings'));
    }

    // NY METODE for å lagre PDF-innstillingene
    public function savePdf(Request $request)
    {
        $validated = $request->validate([
            'font_family' => 'required|string',
            'font_size' => 'required|integer|min:8|max:16',
            'separator_style' => 'required|in:solid,dashed,none',
            'separator_thickness' => 'required|integer|min:1|max:10',
            'separator_color' => 'required|string',
        ]);

        Setting::updateOrCreate(
            ['key' => 'pdf_styles'],
            ['value' => $validated]
        );

        return back()->with('ok', 'PDF-innstillinger er lagret.');
    }
    
}
