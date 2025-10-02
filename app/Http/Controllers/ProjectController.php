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
        $sections = Section::with('blocks')->orderBy('order')->get();

        // hent eksisterende valg for rask lookup
        $existing = $project->projectBlocks()->get()->keyBy('block_id');

        return view('projects.findings', [
            'project'  => $project,
            'sections' => $sections,
            'existing' => $existing,
        ]);
    }

    public function saveFindings(Project $project, Request $r)
    {
        $input = $r->input('blocks', []); // forventer blocks[ID][selected|override_text]

        // Hent alle block-id'er som finnes i systemet (de som vises i skjemaet)
        $allIds = Block::query()->pluck('id')->all();

        // Eksisterende pivot-rader for prosjektet (for rask oppslag)
        $existing = $project->projectBlocks()->get()->keyBy('block_id');

        foreach ($allIds as $bid) {
            $rowIn = $input[$bid] ?? null;

            // Hvis ikke postet fra skjema -> behandle som ikke valgt og uten override
            $selected = isset($rowIn['selected']) && (int)$rowIn['selected'] === 1;
            $text     = isset($rowIn['override_text']) ? trim($rowIn['override_text']) : null;
            if ($text === '') { $text = null; }

            if ($existing->has($bid)) {
                // oppdater
                $pb = $existing[$bid];
                $pb->selected      = $selected;
                $pb->override_text = $text;
                $pb->save();
            } else {
                // opprett bare dersom noe er satt
                if ($selected || $text !== null) {
                    $project->projectBlocks()->create([
                        'block_id'       => $bid,
                        'selected'       => $selected,
                        'override_text'  => $text,
                    ]);
                }
            }
        }

        return redirect()
            ->route('projects.findings', $project)
            ->with('ok', 'Funn lagret.');
    }





    private function buildReportSections(): array
    {
        $sections = \App\Models\Section::with('blocks')->orderBy('order')->get();
        return [$sections];
    }

    public function reportPreview(Project $project)
    {
        // 1) Seksjoner + blokker i stabil rekkefølge
        $sections = Section::with(['blocks' => function ($q) {
            $q->orderBy('order')->orderBy('id');
        }])->orderBy('order')->get();

        // 2) Firma-info
        $company = $this->companyInfo();

        // 3) Prosjektets valg (pivot)
        $pb = $project->projectBlocks()->get()->keyBy('block_id');

        // 4) Bygg rapportseksjoner kun fra valgte blokker
        $reportSections = [];
        foreach ($sections as $s) {
            $chosen = [];
            foreach ($s->blocks as $b) {
                $row = $pb->get($b->id);
                if (!$row || !$row->selected) {
                    continue; // ikke valgt i prosjektet
                }

                $chosen[] = [
                    'icon'     => $b->icon,
                    'label'    => $b->label,
                    'severity' => $b->severity,
                    'text'     => $row->override_text ?: $b->default_text,
                    'tips'     => $b->tips ?? null,
                    'refs'     => $b->references ?? null,
                    'tags'     => $b->tags ?? null,
                    '_order'   => (int)($b->order ?? 0),
                ];
            }

            if ($chosen) {
                usort($chosen, fn($a,$b) => $a['_order'] <=> $b['_order'] ?: strcmp($a['label'],$b['label']));
                $reportSections[] = [
                    'title'  => $s->label,
                    'blocks' => $chosen,
                    '_order' => (int)($s->order ?? 0),
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
        // 1) Seksjoner + blokker i stabil rekkefølge
        $sections = Section::with(['blocks' => function ($q) {
            $q->orderBy('order')->orderBy('id');
        }])->orderBy('order')->get();

        // 2) Firma-info
        $company = $this->companyInfo();

        // 3) Prosjektets valg (pivot)
        $pb = $project->projectBlocks()->get()->keyBy('block_id');

        // 4) Bygg rapportseksjoner kun fra valgte blokker
        $reportSections = [];
        foreach ($sections as $s) {
            $chosen = [];
            foreach ($s->blocks as $b) {
                $row = $pb->get($b->id);
                if (!$row || !$row->selected) {
                    continue; // ikke valgt i prosjektet
                }

                $chosen[] = [
                    'icon'     => $b->icon,
                    'label'    => $b->label,
                    'severity' => $b->severity,
                    'text'     => $row->override_text ?: $b->default_text,
                    'tips'     => $b->tips ?? null,
                    'refs'     => $b->references ?? null,
                    'tags'     => $b->tags ?? null,
                    '_order'   => (int)($b->order ?? 0),
                ];
            }

            if ($chosen) {
                usort($chosen, fn($a,$b) => $a['_order'] <=> $b['_order'] ?: strcmp($a['label'],$b['label']));
                $reportSections[] = [
                    'title'  => $s->label,
                    'blocks' => $chosen,
                    '_order' => (int)($s->order ?? 0),
                ];
            }
        }

        usort($reportSections, fn($a,$b) => $a['_order'] <=> $b['_order'] ?: strcmp($a['title'],$b['title']));

        // 5) Render HTML -> bytes
        $html = view('reports.pdf', [
            'project'        => $project,
            'reportSections' => $reportSections,
            'company'        => $company,
        ])->render();

        $out = $pdf->renderBytes($html, public_path(), []);

        // 6) Filnavn
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

