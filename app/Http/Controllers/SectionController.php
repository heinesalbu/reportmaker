<?php
namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class SectionController extends Controller
{
    public function index() {
        $sections = Section::orderBy('order')->paginate(20);
        return view('sections.index', compact('sections'));
    }
    public function create() {
        return view('sections.form', ['section' => new Section()]);
    }
    public function store(Request $r)
    {
        // Valider bare label og rekkefølge – nøkkelen genereres automatisk
        $data = $r->validate([
            'label' => 'required|string|max:100',
            'order' => 'nullable|integer|min:0',
        ]);

        // Sluggify label: små bokstaver, erstatt mellomrom med understrek, fjern spesialtegn
        $key = Str::slug($data['label'], '_');

        // Sørg for at nøkkelen er unik i sections-tabellen
        if (Section::where('key', $key)->exists()) {
            $key .= '_' . strtolower(Str::random(4)); // legg til kort suffix hvis nødvendig
        }

        // Opprett seksjonen med generert nøkkel
        Section::create([
            'key'   => $key,
            'label' => $data['label'],
            'order' => $data['order'] ?? 0,
        ]);

        return redirect()->route('sections.index')
            ->with('ok','Seksjon opprettet');
    }

    public function edit(Section $section) {
        return view('sections.form', compact('section'));
    }
    public function update(Request $r, Section $section) {
        $data = $r->validate([
            'key'   => 'required|string|max:100|unique:sections,key,'.$section->id,
            'label' => 'required|string|max:100',
            'order' => 'nullable|integer|min:0',
        ]);
        $section->update($data);
        return redirect()->route('sections.index')->with('ok','Seksjon oppdatert');
    }
    public function destroy(Section $section) {
        $section->delete();
        return redirect()->route('sections.index')->with('ok','Seksjon slettet');
    }
    public function reorder(Request $request)
    {
        $order = $request->input('order', []);
        
        foreach ($order as $item) {
            \App\Models\Section::where('id', $item['id'])->update(['order' => $item['order']]);
        }
        
        return response()->json(['success' => true]);
    }
}
