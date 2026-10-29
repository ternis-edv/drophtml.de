<?php

namespace App\Http\Controllers;

use App\Ai\Audit;
use App\Ai\Budget;
use App\Ai\HelpAgent;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AssistantController extends Controller
{
    public function show(Request $request, Budget $budget)
    {
        return view('dashboard.help', [
            'available' => (bool) (config('assistant.enabled') && config('ai.providers.xai.key') && $budget->isReady()),
            'usage' => $budget->status($request->user()->id),
        ]);
    }

    public function __invoke(Request $request, Budget $budget)
    {
        abort_unless(config('assistant.enabled') && config('ai.providers.xai.key') && $budget->isReady(), 503, 'AI help is unavailable.');
        $data = $request->validate(['message' => 'required|string|max:2000', 'allow_changes' => 'sometimes|boolean']);
        try {
            $budget->reserve($request->user()->id);
        } catch (HttpException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'usage' => $budget->status($request->user()->id)], $e->getStatusCode());
            }
            throw $e;
        }
        try {
            $response = (new HelpAgent($request->user(), $request->boolean('allow_changes')))
                ->prompt($data['message'], provider: 'xai', model: config('assistant.model'), timeout: 20);
            $id = Audit::record($request->user()->id, 'response', ['text' => $response->text, 'usage' => $response->usage, 'model' => config('assistant.model')]);
            Audit::finish($id, 'success');

            if ($request->expectsJson()) {
                return response()->json(['answer' => $response->text, 'usage' => $budget->status($request->user()->id)]);
            }

            return back()->with('assistant_answer', $response->text);
        } catch (\Throwable $e) {
            // Do not expose provider exceptions (which can contain credentials or payloads).
            if ($request->expectsJson()) {
                return response()->json(['message' => 'AI help failed. This attempt counts toward your limit.', 'usage' => $budget->status($request->user()->id)], 502);
            }
            abort(502, 'AI help failed. This attempt counts toward your limit.');
        }
    }
}
