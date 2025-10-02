<?php
namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlockController extends Controller
{
    public function index() {
        $blocks = Block::with('section')->orderBy('section_id')->orderBy('order')->paginate(20);
        return view('blocks.index', compact('blocks'));
    }

    public function create() {
        $sections = Section::orderBy('order')->get();
        return view('blocks.form', ['block' => new Block(), 'sections' => $sections]);
    }

    public function store(Request $r) {
        $data = $this->validated($r);
        Block::create($data);
        return redirect()->route('blocks.index')->with('ok','Blokk opprettet');
    }

    public function edit(Block $block) {
        $sections = Section::orderBy('order')->get();
        return view('blocks.form', compact('block','sections'));
    }

    public function update(Request $r, Block $block) {
        $data = $this->validated($r, $block->id, $block->section_id);
        $block->update($data);
        return redirect()->route('blocks.index')->with('ok','Blokk oppdatert');
    }

    public function destroy(Block $block) {
        $block->delete();
        return redirect()->route('blocks.index')->with('ok','Blokk slettet');
    }

    private function OLD_validated(Request $r, $ignoreId = null, $sectionId = null): array {
        $sid = $r->input('section_id', $sectionId);
        return $r->validate([
            'section_id'         => 'required|exists:sections,id',
            'key'                => 'required|string|max:100|unique:blocks,key,'.($ignoreId ?? 'NULL').',id,section_id,'.$sid,
            'label'              => 'required|string|max:255',
            'order'              => 'nullable|integer|min:0',
            'icon'               => 'nullable|string|max:16',
            'severity'           => 'required|in:info,warn,crit',
            'default_text'       => 'nullable|string',
            'tips'               => 'nullable|string',   // CSV i UI → parses under
            'references'         => 'nullable|string',   // CSV i UI → parses under
            'tags'               => 'nullable|string',   // CSV i UI → parses under
            'visible_by_default' => 'nullable|boolean',
        ], [], [
            'section_id' => 'seksjon',
            'key'        => 'nøkkel',
        ]) + [
            // cast CSV → arrays
            'tips'       => self::csvToArray($r->input('tips')),
            'references' => self::csvToArray($r->input('references')),
            'tags'       => self::csvToArray($r->input('tags')),
            'visible_by_default' => (bool)$r->boolean('visible_by_default'),
            'order' => (int)$r->input('order', 0),
        ];
    }
    private function validated(Request $r, $ignoreId = null, $sectionId = null): array {
        // Valider alt unntatt nøkkel først
        $data = $r->validate([
            'section_id'         => 'required|exists:sections,id',
            'label'              => 'required|string|max:255',
            'order'              => 'nullable|integer|min:0',
            'icon'               => 'nullable|string|max:100', // Økt maks-lengde for Font Awesome
            'severity'           => 'required|in:info,warn,crit',
            'default_text'       => 'nullable|string',
            'tips'               => 'nullable|string',
            'references'         => 'nullable|string',
            'tags'               => 'nullable|string',
            'visible_by_default' => 'nullable|boolean',
        ], [], [
            'section_id' => 'seksjon',
            'label'      => 'tittel/label',
        ]);

        // Generer nøkkel automatisk fra label
        $key = Str::slug($data['label'], '_');
        
        // Sjekk for unikhet på nøkkel manuelt
        $sid = $r->input('section_id', $sectionId);
        $query = Block::where('section_id', $sid)->where('key', $key);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        if ($query->exists()) {
            // Hvis nøkkelen ikke er unik, legg til en tilfeldig streng for å unngå feil
            $key .= '_' . strtolower(Str::random(4));
        }
        
        // Kombiner all data for lagring
        return $data + [
            'key'        => $key,
            'tips'       => self::csvToArray($r->input('tips')),
            'references' => self::csvToArray($r->input('references')),
            'tags'       => self::csvToArray($r->input('tags')),
            'visible_by_default' => (bool)$r->boolean('visible_by_default'),
            'order'      => (int)$r->input('order', 0),
        ];
    }

    private static function csvToArray(?string $csv): ?array {
        if ($csv === null || trim($csv) === '') return null;
        return array_values(array_filter(array_map('trim', explode(',', $csv))));
    }
}
