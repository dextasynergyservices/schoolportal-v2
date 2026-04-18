<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustCreditsRequest;
use App\Models\School;
use App\Services\SchoolSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditController extends Controller
{
    public function __construct(private readonly SchoolSetupService $setup) {}

    public function index(Request $request): View
    {
        $query = School::tenants()->orderBy('name');

        if ($search = trim((string) $request->string('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $schools = $query->paginate(20)->withQueryString();

        return view('super-admin.credits.index', compact('schools'));
    }

    public function adjust(AdjustCreditsRequest $request, School $school): RedirectResponse
    {
        $data = $request->validated();

        $this->setup->adjustCredits(
            $school,
            (int) ($data['free_delta'] ?? 0),
            (int) ($data['purchased_delta'] ?? 0),
        );

        return back()->with('success', __('Credit balance updated for :name.', ['name' => $school->name]));
    }
}
