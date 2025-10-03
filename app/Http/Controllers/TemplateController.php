<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Template;
use App\Models\Section;
use App\Models\Block;
use App\Models\TemplateSection;
use App\Models\TemplateBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::latest()->paginate(20);
        return view('templates.index', compact('templates'));
    }

    public function create()
    {
        return view('templates.form', ['template' => new Template()]);
    }

    public function store(Request $r)
    {
        // Valider bare navn og beskrivelse – nøkkel genereres automatisk
        $data = $r->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Generer key fra navn (bruk underscores i stedet for bindestrek)
        $key = Str::slug($data['name'], '_');

        // Kontroller at nøkkelen er unik i templates-tabellen
        $query = Template::where('key', $key);
        if ($query->exists()) {
            // Hvis den allerede finnes, legg til et kort random-suffiks
            $key .= '_' . strtolower(Str::random(4));
        }

        // Opprett malen med generert nøkkel
        $template = Template::create([
            'key'         => $key,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('templates.edit', $template)
            ->with('ok', 'Mal opprettet – nå kan du velge seksjoner/blokker.');
    }

    public function edit(Template $template)
    {
        $sections = Section::with('blocks')->orderBy('order')->get();

        // Map nåværende overrides for rask lookup i view
        $sec = $template->sections()->get()->keyBy('section_id');
        $blk = $template->blocks()->get()->keyBy('block_id');

        return view('templates.form', compact('template','sections','sec','blk'));
    }

    public function update(Request $r, Template $template)
    {
        $data = $r->validate([
            'key' => 'required|string|max:100|unique:templates,key,'.$template->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $template->update($data);
        return back()->with('ok','Mal lagret.');
    }

    // Lagrer inkludering + overrides for seksjoner/blokker:
    public function sync(Request $r, Template $template)
    {
        $payload = $r->validate([
            'sections' => 'array',
            'sections.*.included' => 'nullable|boolean',
            'sections.*.title' => 'nullable|string',
            'sections.*.order' => 'nullable|integer',

            'blocks' => 'array',
            'blocks.*.included' => 'nullable|boolean',
            'blocks.*.icon' => 'nullable|string|max:32',
            'blocks.*.label' => 'nullable|string|max:255',
            'blocks.*.severity' => 'nullable|in:info,warn,crit',
            'blocks.*.text' => 'nullable|string',
            'blocks.*.tips' => 'nullable|string',       // CSV i UI
            'blocks.*.refs' => 'nullable|string',       // CSV i UI
            'blocks.*.tags' => 'nullable|string',       // CSV i UI
            'blocks.*.order' => 'nullable|integer',
            'blocks.*.visible' => 'nullable|boolean',
        ]);

        $sections = $payload['sections'] ?? [];
        foreach ($sections as $sectionId => $s) {
            TemplateSection::updateOrCreate(
                ['template_id'=>$template->id, 'section_id'=>$sectionId],
                [
                    'included' => isset($s['included']) && (int)$s['included']===1,
                    'title_override' => $s['title'] ?? null,
                    'order_override' => (int)($s['order'] ?? 0),
                ]
            );
        }

        $blocks = $payload['blocks'] ?? [];
        foreach ($blocks as $blockId => $b) {
            TemplateBlock::updateOrCreate(
                ['template_id'=>$template->id, 'block_id'=>$blockId],
                [
                    'included' => isset($b['included']) && (int)$b['included']===1,
                    'icon_override' => $b['icon'] ?? null,
                    'label_override' => $b['label'] ?? null,
                    'severity_override' => $b['severity'] ?? null,
                    'default_text_override' => $b['text'] ?? null,
                    'tips_override' => self::csvToArray($b['tips'] ?? null),
                    'references_override' => self::csvToArray($b['refs'] ?? null),
                    'tags_override' => self::csvToArray($b['tags'] ?? null),
                    'order_override' => (int)($b['order'] ?? 0),
                    'visible_by_default_override' => isset($b['visible']) ? (bool)$b['visible'] : null,
                ]
            );
        }

        return back()->with('ok','Seksjoner/blokker synkronisert.');
    }

    private static function csvToArray(?string $csv): ?array {
        if ($csv === null) return null;
        $csv = trim($csv);
        if ($csv === '') return null;
        return array_values(array_filter(array_map('trim', explode(',', $csv))));
    }

    public function destroy(Template $template)
    {
        $template->delete();
        return redirect()->route('templates.index')->with('ok','Mal slettet.');
    }



    public function saveStructure(Request $request, Template $template)
    {
        // Valider input - inkluderer nå synlighetsfelter
        $request->validate([
            'template_name' => 'required|string|max:255',
            'template_description' => 'nullable|string',
            
            'sections' => 'nullable|array',
            'sections.*.included' => 'nullable|boolean',
            'sections.*.title_override' => 'nullable|string|max:255',
            'sections.*.order_override' => 'nullable|integer',
            'sections.*.show_title' => 'nullable|boolean',
            
            'blocks' => 'nullable|array',
            'blocks.*.included' => 'nullable|boolean',
            'blocks.*.label_override' => 'nullable|string|max:255',
            'blocks.*.icon_override' => 'nullable|string|max:100',
            'blocks.*.severity_override' => 'nullable|in:info,warn,crit',
            'blocks.*.default_text_override' => 'nullable|string',
            'blocks.*.tips_csv' => 'nullable|string',
            'blocks.*.order_override' => 'nullable|integer',
            'blocks.*.visible_by_default_override' => 'nullable|boolean',
            // NYE SYNLIGHETSFELTER
            'blocks.*.show_icon' => 'nullable|boolean',
            'blocks.*.show_label' => 'nullable|boolean',
            'blocks.*.show_text' => 'nullable|boolean',
            'blocks.*.show_tips' => 'nullable|boolean',
            'blocks.*.show_severity' => 'nullable|boolean',
        ]);

        $sections = $request->input('sections', []);
        $blocks = $request->input('blocks', []);

        DB::beginTransaction();
        try {
            // Oppdater malens navn og beskrivelse
            $template->update([
                'name' => $request->input('template_name'),
                'description' => $request->input('template_description'),
            ]);

            // Lagre SEKSJONER
            foreach ($sections as $sectionId => $data) {
                TemplateSection::updateOrCreate(
                    [
                        'template_id' => $template->id,
                        'section_id' => (int)$sectionId,
                    ],
                    [
                        'included' => isset($data['included']) && $data['included'] == '1',
                        'title_override' => $data['title_override'] ?? null,
                        'order_override' => isset($data['order_override']) ? (int)$data['order_override'] : 0,
                        'show_title' => isset($data['show_title']) && $data['show_title'] == '1',
                    ]
                );
            }

            // Lagre BLOKKER
            foreach ($blocks as $blockId => $data) {
                // Konverter tips fra CSV til array
                $tipsArray = null;
                if (!empty($data['tips_csv'])) {
                    $tipsArray = array_values(array_filter(
                        array_map('trim', explode(',', $data['tips_csv'])),
                        function($v) { return $v !== ''; }
                    ));
                    if (empty($tipsArray)) {
                        $tipsArray = null;
                    }
                }

                TemplateBlock::updateOrCreate(
                    [
                        'template_id' => $template->id,
                        'block_id' => (int)$blockId,
                    ],
                    [
                        'included' => isset($data['included']) && $data['included'] == '1',
                        'label_override' => $data['label_override'] ?? null,
                        'icon_override' => $data['icon_override'] ?? null,
                        'severity_override' => $data['severity_override'] ?? null,
                        'default_text_override' => $data['default_text_override'] ?? null,
                        'tips_override' => $tipsArray,
                        'references_override' => null,
                        'tags_override' => null,
                        'order_override' => isset($data['order_override']) ? (int)$data['order_override'] : 0,
                        'visible_by_default_override' => isset($data['visible_by_default_override']) && $data['visible_by_default_override'] == '1' ? true : null,
                        // NYE SYNLIGHETSFELTER
                        'show_icon' => isset($data['show_icon']) && $data['show_icon'] == '1',
                        'show_label' => isset($data['show_label']) && $data['show_label'] == '1',
                        'show_text' => isset($data['show_text']) && $data['show_text'] == '1',
                        'show_tips' => isset($data['show_tips']) && $data['show_tips'] == '1',
                        'show_severity' => isset($data['show_severity']) && $data['show_severity'] == '1',
                    ]
                );
            }

            DB::commit();
            return back()->with('ok', 'Mal lagret!');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Template saveStructure failed: ' . $e->getMessage());
            return back()->with('error', 'Kunne ikke lagre: ' . $e->getMessage());
        }
    }


}
