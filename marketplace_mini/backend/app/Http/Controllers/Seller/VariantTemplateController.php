<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\VariantTemplate;
use Illuminate\Http\Request;

class VariantTemplateController extends Controller
{
    /**
     * Display a listing of seller's variant templates.
     */
    public function index()
    {
        $templates = VariantTemplate::where('seller_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'templates' => $templates,
        ]);
    }

    /**
     * Store a newly created variant template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'options' => ['required', 'array', 'min:1'],
            'options.*.name' => ['required', 'string'],
            'options.*.values' => ['required', 'array', 'min:1'],
        ]);

        $validated['seller_id'] = auth()->id();

        $template = VariantTemplate::create($validated);

        return response()->json([
            'message' => 'Variant template created successfully',
            'template' => $template,
        ], 201);
    }

    /**
     * Display the specified variant template.
     */
    public function show($id)
    {
        $template = VariantTemplate::where('seller_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'template' => $template,
        ]);
    }

    /**
     * Update the specified variant template.
     */
    public function update(Request $request, $id)
    {
        $template = VariantTemplate::where('seller_id', auth()->id())
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'options' => ['sometimes', 'required', 'array', 'min:1'],
            'options.*.name' => ['required_with:options', 'string'],
            'options.*.values' => ['required_with:options', 'array', 'min:1'],
        ]);

        $template->update($validated);

        return response()->json([
            'message' => 'Variant template updated successfully',
            'template' => $template,
        ]);
    }

    /**
     * Remove the specified variant template.
     */
    public function destroy($id)
    {
        $template = VariantTemplate::where('seller_id', auth()->id())
            ->findOrFail($id);

        $template->delete();

        return response()->json([
            'message' => 'Variant template deleted successfully',
        ]);
    }
}
