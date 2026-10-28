<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AgentSkillController extends Controller
{
    /**
     * Serve the SKILL.md specification for AI Agents.
     */
    public function show(): Response
    {
        $path = base_path('.agents/skills/drophtml/SKILL.md');

        if (! File::exists($path)) {
            abort(404, 'Agent Skill specification not found.');
        }

        $content = File::get($path);

        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Display the human- and agent-friendly documentation page for DropHTML Agent Skill.
     */
    public function docs(): View
    {
        $path = base_path('.agents/skills/drophtml/SKILL.md');
        $rawMarkdown = File::exists($path) ? File::get($path) : '';

        return view('pages.docs-agents', [
            'rawMarkdown' => $rawMarkdown,
        ]);
    }
}
