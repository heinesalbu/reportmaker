<?php
// app/Http/Controllers/ProjectController.php
namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Models\Customer;   
use App\Models\Section; 
use App\Models\ProjectBlock;
use App\Models\Block;
use App\Services\PdfRenderer;
use App\Models\Setting;
use App\Models\Template;
use App\Models\TemplateBlock;
use App\Models\TemplateSection;
use App\Models\ProjectCustomBlock;




class ProjectController extends Controller
{
    public function index(Request $request)
    {
        // Hent sorterings- og pagineringsverdier fra URL, med standardverdier
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');
        $perPage = $request->query('per_page', 20);

        // En liste over kolonner vi tillater sortering på for sikkerhet
        $allowedSortBy = ['title', 'customer_id', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'created_at'; // Gå tilbake til standard hvis ugyldig kolonne
        }
        
        // Bygg opp spørringen
        $query = \App\Models\Project::with('customer')
            ->orderBy($sortBy, $sortDirection);

        // Håndter paginering. Hvis 'all' er valgt, vis alt.
        if ($perPage == 'all') {
            $projects = $query->get();
            // Vi lager en "manuell" paginator for å unngå feil i viewet
            $projects = new \Illuminate\Pagination\LengthAwarePaginator($projects, $projects->count(), -1);
        } else {
            $projects = $query->paginate($perPage);
        }
        
        // Returner viewet med prosjektene og de aktive sorterings/paginerings-verdiene
        return view('projects.index', [
            'projects' => $projects,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'perPage' => $perPage,
        ]);
    }

    public function create()
    {   
        $project   = new Project(); // ← FIX
        $templates = Template::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        return view('projects.form', [
            'project' => $project, 
            'customers'=>$customers, 
            'templates'=>$templates]);

    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'customer_id' => 'required|exists:customers,id',
            'title'       => 'required|string|max:255',
            'template_id' => 'nullable|string|max:255',
            'status'      => 'nullable|in:draft,ready,exported',
            'tags'        => 'nullable|string', // comma-separated
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:templates,id',
        ]);
        $data['tags'] = isset($data['tags']) && $data['tags'] !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $data['tags']))))
            : null;

        $project = Project::create($data);
        return redirect()->route('projects.edit', $project)->with('ok','Prosjekt opprettet');
    }

    public function edit(Project $project)
    {
        $templates = Template::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        return view('projects.form', ['project' => $project, 'customers'=>$customers, 'templates'=>$templates]);

    }

    public function update(Request $r, Project $project)
    {
        $data = $r->validate([
            'customer_id' => 'required|exists:customers,id',
            'title'       => 'required|string|max:255',
            'template_id' => 'nullable|string|max:255',
            'status'      => 'nullable|in:draft,ready,exported',
            'tags'        => 'nullable|string',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:templates,id',
        ]);
        $data['tags'] = isset($data['tags']) && $data['tags'] !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $data['tags']))))
            : null;

        $project->update($data);
        return back()->with('ok','Prosjekt oppdatert');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('ok','Prosjekt slettet');
    }



    public function findings(Project $project)
    {
        if (!$project->template_id) {
            return back()->withErrors(['msg' => 'Prosjektet har ingen mal tilknyttet og kan ikke vise blokker.']);
        }

        $project->load('projectBlocks', 'customBlocks', 'projectSections');

        $sections = Section::whereHas('templates', function ($query) use ($project) {
                $query->where('template_id', $project->template_id);
            })
            ->orderBy('order')
            ->with(['blocks' => function ($query) {
                $query->orderBy('order');
            }])
            ->get();

        [$templateSections, $templateBlocks] = $this->templateMaps($project);

        $groupedBlocks = collect();
        foreach ($sections as $section) {
            $items = collect();

            foreach ($section->blocks as $block) {
                $items->push($block);

                $customsAfter = $project->customBlocks
                    ->where('after_block_id', $block->id)
                    ->sortBy('order');
                foreach ($customsAfter as $custom) {
                    $items->push($custom);
                }
            }

            $sectionTitle = optional($templateSections->get($section->id))->title_override ?: $section->label;
            $groupedBlocks->put($sectionTitle, $items);
        }

        $projectSections = $project->projectSections->keyBy('section_id');

        return view('projects.findings', [
            'project'          => $project,
            'groupedBlocks'    => $groupedBlocks,
            'templateBlocks'   => $templateBlocks,
            'templateSections' => $templateSections,
            'projectSections'  => $projectSections,
            'sections'         => $sections, // Legg til dette
        ]);
    }



public function saveFindings(Request $request, Project $project)
{
    // DEBUG: Se hva som faktisk kommer inn
    \Log::info('=== SAVE FINDINGS DEBUG ===');
    \Log::info('Project ID: ' . $project->id);
    \Log::info('Raw input blocks:', $request->input('blocks'));
    \Log::info('Raw input sections:', $request->input('sections'));
    
    $validated = $request->validate([
        'blocks.*.selected'       => 'nullable|boolean',
        'blocks.*.override_label' => 'nullable|string|max:255',
        'blocks.*.override_icon'  => 'nullable|string|max:100',
        'blocks.*.override_text'  => 'nullable|string',
        'blocks.*.override_tips'  => 'nullable|string',
        'blocks.*.show_icon'      => 'nullable',
        'blocks.*.show_label'     => 'nullable',
        'blocks.*.show_text'      => 'nullable',
        'blocks.*.show_tips'      => 'nullable',
        'blocks.*.show_severity'  => 'nullable',
        
        'custom_blocks.*.label'           => 'nullable|string|max:255',
        'custom_blocks.*.icon'            => 'nullable|string|max:100',
        'custom_blocks.*.text'            => 'nullable|string',
        'custom_blocks.*.tips'            => 'nullable|string',
        'custom_blocks.*.severity'        => 'nullable|in:info,warn,crit',
        'custom_blocks.*.after_block_id'  => 'nullable|exists:blocks,id',
        
        'sections.*.section_id'  => 'nullable|integer|exists:sections,id',  // nullable, ikke required
        'sections.*.show_title'  => 'nullable',
    ]);

    \Log::info('Validated blocks:', $validated['blocks'] ?? []);

    \DB::beginTransaction();
    try {
        $savedCount = 0;
        foreach ($validated['blocks'] ?? [] as $blockId => $blockData) {
            $isSelected = isset($blockData['selected']) && $blockData['selected'] == '1';
            
            \Log::info("Block $blockId: selected = " . ($isSelected ? 'TRUE' : 'FALSE'));
            
            $updateData = [
                'selected'       => $isSelected,
                'override_label' => $blockData['override_label'] ?? null,
                'override_icon'  => $blockData['override_icon'] ?? null,
                'override_text'  => $blockData['override_text'] ?? null,
                'override_tips'  => isset($blockData['override_tips']) && $blockData['override_tips']
                    ? array_values(array_filter(array_map('trim', explode(',', $blockData['override_tips']))))
                    : null,
            ];

            foreach (['show_icon', 'show_label', 'show_text', 'show_tips', 'show_severity'] as $field) {
                if (isset($blockData[$field])) {
                    $updateData[$field] = $blockData[$field] === '1' || $blockData[$field] === 1 || $blockData[$field] === true;
                } else {
                    $updateData[$field] = null;
                }
            }

            $pb = $project->projectBlocks()->updateOrCreate(
                ['block_id' => $blockId],
                $updateData
            );
            
            \Log::info("Saved block $blockId, selected in DB: " . ($pb->selected ? 'TRUE' : 'FALSE'));
            $savedCount++;
        }

        \Log::info("Total blocks saved: $savedCount");

        // Custom blocks (samme som før)
        $keepCustomIds = [];
        foreach ($validated['custom_blocks'] ?? [] as $customKey => $customData) {
            $tipsArr = null;
            if (!empty($customData['tips'])) {
                $tipsArr = array_values(array_filter(array_map('trim', explode(',', $customData['tips']))));
            }
            
            $attrs = [
                'label'          => $customData['label'] ?? null,
                'icon'           => $customData['icon'] ?? null,
                'text'           => $customData['text'] ?? null,
                'tips'           => $tipsArr,
                'severity'       => $customData['severity'] ?? 'info',
                'after_block_id' => $customData['after_block_id'] ?? null,
            ];

            if (str_starts_with((string)$customKey, 'new_')) {
                $attrs['project_id'] = $project->id;
                if (!empty($customData['after_block_id'])) {
                    $blockObj = \App\Models\Block::find($customData['after_block_id']);
                    if ($blockObj) {
                        $attrs['section_id'] = $blockObj->section_id;
                    }
                }
                $newBlock = \App\Models\ProjectCustomBlock::create($attrs);
                $keepCustomIds[] = $newBlock->id;
            } else {
                $cb = \App\Models\ProjectCustomBlock::find($customKey);
                if ($cb && $cb->project_id == $project->id) {
                    $cb->update($attrs);
                    $keepCustomIds[] = $cb->id;
                }
            }
        }

        \App\Models\ProjectCustomBlock::where('project_id', $project->id)
            ->whereNotIn('id', $keepCustomIds)
            ->delete();

        foreach ($validated['sections'] ?? [] as $index => $sectionData) {
            // Hopp over hvis section_id mangler ELLER er null
            if (!isset($sectionData['section_id']) || $sectionData['section_id'] === null) {
                \Log::info("Skipping section at index $index - no valid section_id");
                continue;
            }
            
            $sectionId = (int)$sectionData['section_id'];
            
            // Dobbeltsjekk at seksjonen eksisterer
            if (!\App\Models\Section::where('id', $sectionId)->exists()) {
                \Log::warning("Section $sectionId does not exist, skipping");
                continue;
            }
            
            \Log::info("Saving section $sectionId with show_title = " . ($sectionData['show_title'] ?? 'null'));
            
            \App\Models\ProjectSection::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'section_id' => $sectionId,
                ],
                [
                    'show_title' => isset($sectionData['show_title']) && ($sectionData['show_title'] === '1'),
                ]
            );
        }

        \DB::commit();
        \Log::info('=== SAVE COMPLETE ===');
        return back()->with('ok', 'Endringer er lagret (se laravel.log for detaljer)');
        
    } catch (\Throwable $e) {
        \DB::rollBack();
        \Log::error('saveFindings failed: ' . $e->getMessage());
        return back()->with('error', 'Kunne ikke lagre: ' . $e->getMessage());
    }
}
    private function buildReportSections(): array
    {
        $sections = \App\Models\Section::with('blocks')->orderBy('order')->get();
        return [$sections];
    }
    public function reportPreview(Project $project)
    {
        $sections = Section::with(['blocks' => function ($q) {
            $q->orderBy('order')->orderBy('id');
        }])->orderBy('order')->get();

        $company = $this->companyInfo();
        $project->load('projectBlocks', 'customBlocks', 'projectSections');
        $pb = $project->projectBlocks->keyBy('block_id');
        $projectSections = $project->projectSections->keyBy('section_id');

        $templateSections = collect();
        $templateBlocks = collect();
        if ($project->template_id) {
            $templateSections = \App\Models\TemplateSection::where('template_id', $project->template_id)
                ->get()->keyBy('section_id');
            $templateBlocks = \App\Models\TemplateBlock::where('template_id', $project->template_id)
                ->get()->keyBy('block_id');
        }

        $reportSections = [];
        foreach ($sections as $s) {
            $chosen = [];
            
            foreach ($s->blocks as $b) {
                $row = $pb->get($b->id);
                if (!$row || !$row->selected) continue;

                $tb = $templateBlocks->get($b->id);

                $showIcon     = ($row->show_icon !== null)     ? (bool)$row->show_icon     : (($tb?->show_icon !== null)     ? (bool)$tb->show_icon     : true);
                $showLabel    = ($row->show_label !== null)    ? (bool)$row->show_label    : (($tb?->show_label !== null)    ? (bool)$tb->show_label    : true);
                $showText     = ($row->show_text !== null)     ? (bool)$row->show_text     : (($tb?->show_text !== null)     ? (bool)$tb->show_text     : true);
                $showTips     = ($row->show_tips !== null)     ? (bool)$row->show_tips     : (($tb?->show_tips !== null)     ? (bool)$tb->show_tips     : true);
                $showSeverity = ($row->show_severity !== null) ? (bool)$row->show_severity : (($tb?->show_severity !== null) ? (bool)$tb->show_severity : false);

                $tips = $row->override_tips ?? ($tb && $tb->tips_override ? $tb->tips_override : $b->tips);
                if (is_string($tips)) {
                    $tips = array_filter(array_map('trim', explode(',', $tips)));
                }
                $tips = $tips ?: [];

                $chosen[] = [
                    'icon'          => $row->override_icon ?: ($tb && $tb->icon_override ? $tb->icon_override : $b->icon),
                    'label'         => $row->override_label ?: ($tb && $tb->label_override ? $tb->label_override : $b->label),
                    'severity'      => $b->severity,
                    'text'          => $row->override_text ?: ($tb && $tb->default_text_override ? $tb->default_text_override : $b->default_text),
                    'tips'          => $tips,
                    'refs'          => $b->references ?? null,
                    'tags'          => $b->tags ?? null,
                    '_order'        => (int)($b->order ?? 0),
                    'show_icon'     => $showIcon,
                    'show_label'    => $showLabel,
                    'show_text'     => $showText,
                    'show_tips'     => $showTips,
                    'show_severity' => $showSeverity,
                ];

                $customsAfter = $project->customBlocks->where('after_block_id', $b->id)->sortBy('order');
                foreach ($customsAfter as $custom) {
                    $customTips = $custom->tips;
                    if (is_string($customTips)) {
                        $customTips = array_filter(array_map('trim', explode(',', $customTips)));
                    }
                    $customTips = $customTips ?: [];

                    $chosen[] = [
                        'icon'          => $custom->icon,
                        'label'         => $custom->label,
                        'severity'      => $custom->severity ?? 'info',
                        'text'          => $custom->text,
                        'tips'          => $customTips,
                        'refs'          => null,
                        'tags'          => null,
                        '_order'        => (int)($b->order ?? 0) + 0.5,
                        'show_icon'     => true,
                        'show_label'    => true,
                        'show_text'     => true,
                        'show_tips'     => true,
                        'show_severity' => true,
                    ];
                }
            }

            if ($chosen) {
                usort($chosen, fn($a,$b) => $a['_order'] <=> $b['_order'] ?: strcmp($a['label'],$b['label']));
                
                $ts = $templateSections->get($s->id);
                $ps = $projectSections->get($s->id);
                
                $sectionTitle = $ts && $ts->title_override ? $ts->title_override : $s->label;
                $showSectionTitle = ($ps && $ps->show_title !== null) ? (bool)$ps->show_title : (($ts && $ts->show_title !== null) ? (bool)$ts->show_title : true);
                
                $reportSections[] = [
                    'title'      => $sectionTitle,
                    'show_title' => $showSectionTitle,
                    'blocks'     => $chosen,
                    '_order'     => (int)($s->order ?? 0),
                ];
            }
        }

        usort($reportSections, fn($a,$b) => $a['_order'] <=> $b['_order'] ?: strcmp($a['title'],$b['title']));

        return view('reports.preview', [
            'project'        => $project,
            'reportSections' => $reportSections,
            'company'        => $company,
        ]);
    }
    public function reportPdf(Project $project, \App\Services\PdfRenderer $pdf)
    {
        $sections = Section::with(['blocks' => function ($q) {
            $q->orderBy('order')->orderBy('id');
        }])->orderBy('order')->get();

        $company = $this->companyInfo();
        $project->load('projectBlocks', 'customBlocks', 'projectSections');
        $pb = $project->projectBlocks->keyBy('block_id');
        $projectSections = $project->projectSections->keyBy('section_id');

        $templateSections = collect();
        $templateBlocks = collect();
        if ($project->template_id) {
            $templateSections = \App\Models\TemplateSection::where('template_id', $project->template_id)
                ->get()->keyBy('section_id');
            $templateBlocks = \App\Models\TemplateBlock::where('template_id', $project->template_id)
                ->get()->keyBy('block_id');
        }

        $reportSections = [];
        foreach ($sections as $s) {
            $chosen = [];
            
            foreach ($s->blocks as $b) {
                $row = $pb->get($b->id);
                if (!$row || !$row->selected) continue;

                $tb = $templateBlocks->get($b->id);

                $showIcon     = ($row->show_icon !== null)     ? (bool)$row->show_icon     : (($tb?->show_icon !== null)     ? (bool)$tb->show_icon     : true);
                $showLabel    = ($row->show_label !== null)    ? (bool)$row->show_label    : (($tb?->show_label !== null)    ? (bool)$tb->show_label    : true);
                $showText     = ($row->show_text !== null)     ? (bool)$row->show_text     : (($tb?->show_text !== null)     ? (bool)$tb->show_text     : true);
                $showTips     = ($row->show_tips !== null)     ? (bool)$row->show_tips     : (($tb?->show_tips !== null)     ? (bool)$tb->show_tips     : true);
                $showSeverity = ($row->show_severity !== null) ? (bool)$row->show_severity : (($tb?->show_severity !== null) ? (bool)$tb->show_severity : false);

                $tips = $row->override_tips ?? ($tb && $tb->tips_override ? $tb->tips_override : $b->tips);
                if (is_string($tips)) {
                    $tips = array_filter(array_map('trim', explode(',', $tips)));
                }
                $tips = $tips ?: [];

                $chosen[] = [
                    'icon'          => $row->override_icon ?: ($tb && $tb->icon_override ? $tb->icon_override : $b->icon),
                    'label'         => $row->override_label ?: ($tb && $tb->label_override ? $tb->label_override : $b->label),
                    'severity'      => $b->severity,
                    'text'          => $row->override_text ?: ($tb && $tb->default_text_override ? $tb->default_text_override : $b->default_text),
                    'tips'          => $tips,
                    'refs'          => $b->references ?? null,
                    'tags'          => $b->tags ?? null,
                    '_order'        => (int)($b->order ?? 0),
                    'show_icon'     => $showIcon,
                    'show_label'    => $showLabel,
                    'show_text'     => $showText,
                    'show_tips'     => $showTips,
                    'show_severity' => $showSeverity,
                ];

                $customsAfter = $project->customBlocks->where('after_block_id', $b->id)->sortBy('order');
                foreach ($customsAfter as $custom) {
                    $customTips = $custom->tips;
                    if (is_string($customTips)) {
                        $customTips = array_filter(array_map('trim', explode(',', $customTips)));
                    }
                    $customTips = $customTips ?: [];

                    $chosen[] = [
                        'icon'          => $custom->icon,
                        'label'         => $custom->label,
                        'severity'      => $custom->severity ?? 'info',
                        'text'          => $custom->text,
                        'tips'          => $customTips,
                        'refs'          => null,
                        'tags'          => null,
                        '_order'        => (int)($b->order ?? 0) + 0.5,
                        'show_icon'     => true,
                        'show_label'    => true,
                        'show_text'     => true,
                        'show_tips'     => true,
                        'show_severity' => true,
                    ];
                }
            }

            if ($chosen) {
                usort($chosen, fn($a,$b) => $a['_order'] <=> $b['_order'] ?: strcmp($a['label'],$b['label']));
                
                $ts = $templateSections->get($s->id);
                $ps = $projectSections->get($s->id);
                
                $sectionTitle = $ts && $ts->title_override ? $ts->title_override : $s->label;
                $showSectionTitle = ($ps && $ps->show_title !== null) ? (bool)$ps->show_title : (($ts && $ts->show_title !== null) ? (bool)$ts->show_title : true);
                
                $reportSections[] = [
                    'title'      => $sectionTitle,
                    'show_title' => $showSectionTitle,
                    'blocks'     => $chosen,
                    '_order'     => (int)($s->order ?? 0),
                ];
            }
        }

        usort($reportSections, fn($a,$b) => $a['_order'] <=> $b['_order'] ?: strcmp($a['title'],$b['title']));

        $pdfStyles = \App\Models\Setting::where('key', 'pdf_styles')->value('value') ?? [];

        $html = view('reports.pdf', compact(
            'project', 
            'reportSections', 
            'company', 
            'pdfStyles'))->render();

        $out = $pdf->renderBytes($html, public_path(), []);

        $customer = optional($project->customer)->name ?: 'kunde';
        $basename = $this->safeFilename($customer.' - '.$project->title.' - '.now()->format('Y-m-d'));
        $ext = $out['mime'] === 'application/pdf' ? 'pdf' : 'html';

        return response($out['bytes'], 200, [
            'Content-Type'        => $out['mime'],
            'Content-Disposition' => 'attachment; filename="'.$basename.'.'.$ext.'"',
        ]);
    }
    public function applyTemplate(Request $request, Project $project)
    {
        $templateId = $request->input('template_id') ?: $project->template_id;
        if (!$templateId) {
            return back()->with('error', 'Ingen mal valgt.');
        }

        // Oppdater prosjektets template-tilknytning om den har endret seg
        if ((int)$project->template_id !== (int)$templateId) {
            $project->template_id = $templateId;
            $project->save();
        }

        /** @var Template|null $template */
        $template = Template::with(['blocks', 'sections'])->find($templateId);
        if (!$template) {
            return back()->with('error', 'Kunne ikke finne valgt mal.');
        }

        $mode = $request->input('mode', 'merge'); // merge|replace

        DB::beginTransaction();
        try {
            // Gjeldende prosjektvalg
            $existing = $project->projectBlocks()->get()->keyBy('block_id');

            if ($mode === 'replace') {
                // Nullstill alle valg i prosjektet (behold rader for diff/redo, men sett selected=false)
                DB::table('project_blocks')->where('project_id', $project->id)->update(['selected' => false]);
                $existing = $project->projectBlocks()->get()->keyBy('block_id'); // refetch
            }

            // Blokker som malen sier "included = true"
            $included = $template->blocks->where('included', true)->keyBy('block_id');

            // Blokker som malen sier "included = false" (for replace, skal deaktiveres)
            $excludedIds = $template->blocks->where('included', false)->pluck('block_id')->all();

            $added = 0;
            $updated = 0;

            foreach ($included as $blockId => $tb) {
                /** @var TemplateBlock $tb */
                /** @var ProjectBlock|null $pb */
                $pb = $existing->get($blockId);

                if (!$pb) {
                    // Finnes ikke i prosjektet → opprett og aktiver
                    $pb = new ProjectBlock([
                        'project_id' => $project->id,
                        'block_id'   => $blockId,
                    ]);
                    $pb->selected = true;

                    // Starttekst: bruk malens override hvis den finnes
                    if (!empty($tb->default_text_override)) {
                        $pb->override_text = $tb->default_text_override;
                    }

                    $pb->save();
                    $added++;
                    continue;
                }

                // Finnes i prosjektet
                $changed = false;

                // Sett selected=true (uansett modus) for å sikre at malen aktiverer blokken
                if (!$pb->selected) {
                    $pb->selected = true;
                    $changed = true;
                }

                // Fyll inn tekst fra mal hvis prosjektet ikke har egen tekst
                if (empty($pb->override_text) && !empty($tb->default_text_override)) {
                    $pb->override_text = $tb->default_text_override;
                    $changed = true;
                }

                if ($changed) {
                    $pb->save();
                    $updated++;
                }
            }

            if ($mode === 'replace' && !empty($excludedIds)) {
                // Deaktiver eksplisitt ekskluderte blokker i malen
                DB::table('project_blocks')
                    ->where('project_id', $project->id)
                    ->whereIn('block_id', $excludedIds)
                    ->update(['selected' => false]);
            }

            DB::commit();

            $msg = "Mal aktivert: $added lagt til, $updated oppdatert";
            $msg .= $mode === 'replace' ? " (modus: replace)" : " (modus: merge)";

            // Send gjerne brukeren til Funn & blokker for å se effekten
            return redirect()->route('projects.findings', $project)->with('ok', $msg);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Aktivering feilet: '.$e->getMessage());
        }
    }
    private function companyInfo(): array
    {
        return [
            'name'   => Setting::get('company_name','Ditt Firmanavn'),
            'footer' => Setting::get('company_footer','Generert av Reportmaker'),
            'logo'   => Setting::get('company_logo_path', null),
        ];
    }

    /**
     * Returnerer [TemplateSection-map, TemplateBlock-map] for prosjektets mal.
     * Begge som Collection keyet på hhv. section_id og block_id.
     *
     * @return array{0:Collection,1:Collection}
     */
    private function templateMaps($project): array
    {
        if (!$project->template_id) {
            return [collect(), collect()];
        }
        // Vi antar at projects.template_id peker til templates.id
        $template = Template::with(['sections','blocks'])->find($project->template_id);
        if (!$template) {
            return [collect(), collect()];
        }
        return [$template->sections->keyBy('section_id'), $template->blocks->keyBy('block_id')];
    }

    private function safeFilename(string $name): string
    {
        $file = preg_replace('/[^\p{L}\p{N}\-_\. ]+/u', '', $name) ?? 'rapport';
        $file = trim($file);
        return $file === '' ? 'rapport' : $file;
    }

    public function duplicate(Project $project, Request $request)
    {
        DB::beginTransaction();
        try {
            // 1) Lag nytt prosjekt (klon felt)
            $copy = new Project();
            $copy->customer_id = $project->customer_id;
            $copy->title       = $project->title.' (kopi)';
            $copy->template_id = $project->template_id;   // behold referanse til mal om ønskelig
            $copy->owner_id    = $project->owner_id;
            $copy->status      = 'draft';
            $copy->tags        = $project->tags;
            $copy->description = $project->description;
            $copy->save();

            // 2) Klon project_blocks
            $rows = $project->projectBlocks()->get();
            foreach ($rows as $row) {
                ProjectBlock::create([
                    'project_id'     => $copy->id,
                    'block_id'       => $row->block_id,
                    'selected'       => $row->selected,
                    'override_text'  => $row->override_text,
                ]);
            }

            DB::commit();
            return redirect()->route('projects.edit', $copy)
                ->with('ok', 'Prosjekt duplisert.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Kunne ikke duplisere: '.$e->getMessage());
        }
    }


}

