<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BusinessController extends Controller
{
    public function edit(Request $request)
    {
        $b = $request->user()->business;

        return Inertia::render('business/Profile', [
            'business' => $b ? $b->only('name', 'display_name', 'sector', 'district', 'description', 'years_operating', 'contact_phone') + ['has_registration_no' => (bool) $b->registration_no, 'verification_status' => $b->verification_status] : null,
            'sectors' => config('banyira.sectors'), 'districts' => config('banyira.districts'),
        ]);
    }

    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:160', 'display_name' => 'required|string|max:80', 'sector' => ['required', Rule::in(config('banyira.sectors'))],
            'district' => ['required', Rule::in(config('banyira.districts'))], 'description' => 'required|string|min:30|max:2000',
            'years_operating' => 'required|integer|min:0|max:80', 'contact_phone' => 'nullable|string|max:30', 'registration_no' => 'nullable|string|max:60',
        ];
    }

    public function save(Request $request, AuditLogger $audit)
    {
        $d = $request->validate(self::rules());
        $b = $request->user()->business;
        if (! $d['registration_no'] && $b) {
            unset($d['registration_no']);   // keep the stored value when the field is left blank
        }
        if ($b) {
            $b->update($d);
            $audit->record('business.updated', $b);
        } else {
            $b = Business::create($d + ['owner_id' => $request->user()->id, 'owner_consent_at' => now()]);
            $audit->record('business.created', $b);
        }

        return to_route('applications.index')->with('success', 'Business details saved.');
    }
}
