<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Template;
use App\Models\Section;
use App\Models\Block;
use App\Models\TemplateSection;
use App\Models\TemplateBlock;
use Illuminate\Http\Request;

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
        $data = $r->validate([
            'key' => 'required|string|max:100|unique:templates,key',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $template = Template::create($data);
        return redirect()->route('templates.edit',$template)->with('ok','Mal opprettet – nå kan du velge seksjoner/blokker.');
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
        // Forventet input-struktur:
        // sections[SECTION_ID][included|title_override|order_override]
        // blocks[BLOCK_ID][included|icon_override|label_override|severity_override|order_override|visible_by_default_override|default_text_override|tips_csv|references_csv|tags_csv]

        $sections = $request->input('sections', []);
        $blocks   = $request->input('blocks', []);

        // Hjelpere
        $toBool = fn($v) => filter_var($v, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
        $toInt  = fn($v, $default=0) => is_numeric($v) ? (int)$v : $default;
        $csv    = function ($s) {
            if ($s === null) return null;
            $s = trim($s);
            if ($s === '') return null;
            // Splitt på komma, trim hver, fjern tomme
            $arr = array_values(array_filter(array_map('trim', explode(',', $s)), fn($x) => $x !== ''));
            return $arr ?: null;
        };

        DB::beginTransaction();
        try {
            // Upsert SEKSJONER
            if (!empty($sections)) {
                $now = now();
                $rows = [];
                foreach ($sections as $sectionId => $payload) {
                    $rows[] = [
                        'template_id'    => $template->id,
                        'section_id'     => (int)$sectionId,
                        'included'       => $toBool($payload['included'] ?? true),
                        'title_override' => $payload['title_override'] ?? null,
                        'order_override' => $toInt($payload['order_override'] ?? 0),
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }

                // konflikt på (template_id, section_id)
                DB::table('template_sections')->upsert(
                    $rows,
                    ['template_id','section_id'],
                    ['included','title_override','order_override','updated_at']
                );
            }

            // Upsert BLOKKER
            if (!empty($blocks)) {
                $now  = now();
                $rows = [];
                foreach ($blocks as $blockId => $payload) {
                    $rows[] = [
                        'template_id'                   => $template->id,
                        'block_id'                      => (int)$blockId,
                        'included'                      => $toBool($payload['included'] ?? false),
                        'icon_override'                 => $payload['icon_override']     ?? null,
                        'label_override'                => $payload['label_override']    ?? null,
                        'severity_override'             => $payload['severity_override']  ?? null, // "info|warn|crit" eller null
                        'default_text_override'         => $payload['default_text_override'] ?? null,
                        'tips_override'                 => $csv($payload['tips_csv']        ?? null),
                        'references_override'           => $csv($payload['references_csv']  ?? null),
                        'tags_override'                 => $csv($payload['tags_csv']        ?? null),
                        'order_override'                => $toInt($payload['order_override'] ?? 0),
                        'visible_by_default_override'   => array_key_exists('visible_by_default_override', $payload)
                                                            ? $toBool($payload['visible_by_default_override'])
                                                            : null, // lar NULL bety "arv"
                        'created_at'                    => $now,
                        'updated_at'                    => $now,
                    ];
                }

                // konflikt på (template_id, block_id)
                DB::table('template_blocks')->upsert(
                    $rows,
                    ['template_id','block_id'],
                    [
                        'included','icon_override','label_override','severity_override',
                        'default_text_override','tips_override','references_override','tags_override',
                        'order_override','visible_by_default_override','updated_at'
                    ]
                );
            }

            DB::commit();
            return back()->with('ok', 'Malen er oppdatert.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Kunne ikke lagre mal: '.$e->getMessage());
        }
    }

}
