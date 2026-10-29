<?php

namespace App\Http\Controllers;

use App\Ai\Audit;
use App\Ai\Budget;
use App\Ai\HelpAgent;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function __invoke(Request $request, Budget $budget)
    {
        abort_unless(config('assistant.enabled') && config('ai.providers.xai.key'), 503, 'AI help is unavailable.');
        $data = $request->validate(['message' => 'required|string|max:2000', 'allow_changes' => 'sometimes|boolean']);
        $budget->reserve($request->user()->id);
        try {
            $response = (new HelpAgent($request->user(), $request->boolean('allow_changes')))
                ->prompt($data['message'], provider: 'xai', model: config('assistant.model'), timeout: 20);
            $id = Audit::record($request->user()->id, 'response', ['text' => $response->text, 'usage' => $response->usage, 'model' => config('assistant.model')]);
            Audit::finish($id, 'success');

            return back()->with('assistant_answer', $response->text);
        } catch (\Throwable $e) {
            // Do not expose provider exceptions (which can contain credentials or payloads).
            abort(502, 'AI help failed. This attempt counts toward your limit.');
        }
    }
}
