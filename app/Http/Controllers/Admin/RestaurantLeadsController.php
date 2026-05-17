<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\LeadDemoNotification;
use App\Models\LeadActivity;
use App\Models\RestaurantLead;
use App\Models\LeadEmailTrack;
use App\Models\RestaurantSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RestaurantLeadsController extends Controller
{
    public function index(Request $request)
    {
        $query = RestaurantLead::query();

        // Search
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Filters
        if ($status = $request->input('status')) {
            $query->status($status);
        }

        if ($city = $request->input('city')) {
            $query->city($city);
        }

        if ($priority = $request->input('priority')) {
            $query->priority($priority);
        }

        if ($websiteFilter = $request->input('website_filter')) {
            if ($websiteFilter === 'has') {
                $query->whereNotNull('website')->where('website', '!=', '');
            } elseif ($websiteFilter === 'none') {
                $query->where(function ($q) {
                    $q->whereNull('website')->orWhere('website', '');
                });
            }
        }

        if ($request->input('follow_up') === 'due') {
            $query->dueForFollowUp();
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['business_name', 'city', 'phone', 'website', 'status', 'priority', 'facility_type', 'last_activity_at', 'next_follow_up_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $leads = $query->paginate(25)->withQueryString();

        // Stats
        $stats = [
            'total' => RestaurantLead::count(),
            'new' => RestaurantLead::where('status', 'new')->count(),
            'contacted' => RestaurantLead::where('status', 'contacted')->count(),
            'interested' => RestaurantLead::where('status', 'interested')->count(),
            'quoted' => RestaurantLead::where('status', 'quoted')->count(),
            'converted' => RestaurantLead::where('status', 'converted')->count(),
            'due_follow_up' => RestaurantLead::dueForFollowUp()->count(),
            'has_website' => RestaurantLead::whereNotNull('website')->where('website', '!=', '')->count(),
        ];

        // Get distinct cities for filter
        $cities = RestaurantLead::whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('admin.leads.index', compact('leads', 'stats', 'cities'));
    }

    public function show(RestaurantLead $lead)
    {
        $lead->load(['activities.performer', 'convertedSite', 'emailTracks.site']);

        // Get demo/active sites for the "Send Demo Email" dropdown
        $demoSites = RestaurantSite::whereIn('status', ['demo', 'active'])
            ->orderBy('business_name')
            ->get();

        return view('admin.leads.show', compact('lead', 'demoSites'));
    }

    public function create()
    {
        return view('admin.leads.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:2',
            'postal_code' => 'nullable|string|max:7',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'operator_name' => 'nullable|string|max:255',
            'facility_type' => 'nullable|string|max:255',
            'status' => 'nullable|in:' . implode(',', array_keys(RestaurantLead::STATUSES)),
            'priority' => 'nullable|in:' . implode(',', array_keys(RestaurantLead::PRIORITIES)),
            'notes' => 'nullable|string',
            'next_follow_up_at' => 'nullable|date',
        ]);

        $data['source'] = RestaurantLead::SOURCE_MANUAL;
        $data['province'] = $data['province'] ?? 'NB';

        $lead = RestaurantLead::create($data);

        LeadActivity::create([
            'restaurant_lead_id' => $lead->id,
            'type' => LeadActivity::TYPE_NOTE,
            'description' => 'Lead created manually.',
            'performed_by' => auth()->id(),
        ]);

        return redirect()->route('admin.leads.show', $lead)
            ->with('status', "Lead '{$lead->business_name}' created successfully.");
    }

    public function edit(RestaurantLead $lead)
    {
        return view('admin.leads.edit', compact('lead'));
    }

    public function update(Request $request, RestaurantLead $lead)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:2',
            'postal_code' => 'nullable|string|max:7',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'operator_name' => 'nullable|string|max:255',
            'facility_type' => 'nullable|string|max:255',
            'status' => 'nullable|in:' . implode(',', array_keys(RestaurantLead::STATUSES)),
            'priority' => 'nullable|in:' . implode(',', array_keys(RestaurantLead::PRIORITIES)),
            'notes' => 'nullable|string',
            'next_follow_up_at' => 'nullable|date',
        ]);

        $oldStatus = $lead->status;
        $lead->update($data);

        // Log status change
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            LeadActivity::create([
                'restaurant_lead_id' => $lead->id,
                'type' => LeadActivity::TYPE_STATUS_CHANGE,
                'description' => "Status changed from " . (RestaurantLead::STATUSES[$oldStatus] ?? $oldStatus) . " to " . (RestaurantLead::STATUSES[$data['status']] ?? $data['status']),
                'metadata' => ['from' => $oldStatus, 'to' => $data['status']],
                'performed_by' => auth()->id(),
            ]);
            $lead->update(['last_activity_at' => now()]);
        }

        return redirect()->route('admin.leads.show', $lead)
            ->with('status', "Lead updated successfully.");
    }

    public function destroy(RestaurantLead $lead)
    {
        $name = $lead->business_name;
        $lead->delete();

        return redirect()->route('admin.leads.index')
            ->with('status', "Lead '{$name}' deleted.");
    }

    public function addActivity(Request $request, RestaurantLead $lead)
    {
        $data = $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(LeadActivity::TYPES)),
            'description' => 'required|string|max:5000',
        ]);

        LeadActivity::create([
            'restaurant_lead_id' => $lead->id,
            'type' => $data['type'],
            'description' => $data['description'],
            'performed_by' => auth()->id(),
        ]);

        // Update last_activity_at and contacted_at if applicable
        $updates = ['last_activity_at' => now()];
        if (in_array($data['type'], ['call', 'email', 'sms', 'visit'])) {
            $updates['contacted_at'] = now();
            if ($lead->status === RestaurantLead::STATUS_NEW) {
                $updates['status'] = RestaurantLead::STATUS_CONTACTED;
            }
        }
        $lead->update($updates);

        return redirect()->route('admin.leads.show', $lead)
            ->with('status', 'Activity logged.');
    }

    public function updateStatus(Request $request, RestaurantLead $lead)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(RestaurantLead::STATUSES)),
        ]);

        $oldStatus = $lead->status;
        if ($data['status'] === $oldStatus) {
            return back()->with('status', 'Status unchanged.');
        }

        $lead->update([
            'status' => $data['status'],
            'last_activity_at' => now(),
        ]);

        LeadActivity::create([
            'restaurant_lead_id' => $lead->id,
            'type' => LeadActivity::TYPE_STATUS_CHANGE,
            'description' => "Status changed from " . (RestaurantLead::STATUSES[$oldStatus] ?? $oldStatus) . " to " . (RestaurantLead::STATUSES[$data['status']] ?? $data['status']),
            'metadata' => ['from' => $oldStatus, 'to' => $data['status']],
            'performed_by' => auth()->id(),
        ]);

        return back()->with('status', "Status updated to {$lead->status_label}.");
    }

    public function bulkUpdateStatus(Request $request)
    {
        $data = $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'integer|exists:menudirect.restaurant_leads,id',
            'status' => 'required|in:' . implode(',', array_keys(RestaurantLead::STATUSES)),
        ]);

        $leads = RestaurantLead::whereIn('id', $data['lead_ids'])->get();
        $count = 0;

        foreach ($leads as $lead) {
            if ($lead->status !== $data['status']) {
                $oldStatus = $lead->status;
                $lead->update([
                    'status' => $data['status'],
                    'last_activity_at' => now(),
                ]);

                LeadActivity::create([
                    'restaurant_lead_id' => $lead->id,
                    'type' => LeadActivity::TYPE_STATUS_CHANGE,
                    'description' => "Bulk status change from " . (RestaurantLead::STATUSES[$oldStatus] ?? $oldStatus) . " to " . (RestaurantLead::STATUSES[$data['status']] ?? $data['status']),
                    'metadata' => ['from' => $oldStatus, 'to' => $data['status'], 'bulk' => true],
                    'performed_by' => auth()->id(),
                ]);
                $count++;
            }
        }

        return back()->with('status', "{$count} lead(s) updated to " . (RestaurantLead::STATUSES[$data['status']] ?? $data['status']) . ".");
    }

    public function sendDemoEmail(Request $request, RestaurantLead $lead)
    {
        $data = $request->validate([
            'restaurant_site_id' => 'required|exists:menudirect.restaurant_sites,id',
        ]);

        if (!$lead->email) {
            return back()->with('error', 'This lead has no email address.');
        }

        $site = RestaurantSite::with('categories.items')->findOrFail($data['restaurant_site_id']);
        $demoUrl = "https://{$site->slug}.menudirect.ca";

        // Create email tracking record
        $track = LeadEmailTrack::create([
            'restaurant_lead_id' => $lead->id,
            'restaurant_site_id' => $site->id,
            'recipient_email' => $lead->email,
            'email_type' => 'demo_notification',
            'sent_at' => now(),
        ]);

        Mail::to($lead->email)->send(new LeadDemoNotification($lead, $site, $demoUrl, $track->pixel_url));

        // Log the activity
        LeadActivity::create([
            'restaurant_lead_id' => $lead->id,
            'type' => LeadActivity::TYPE_EMAIL,
            'description' => "Sent demo site email — {$site->business_name} ({$demoUrl})",
            'metadata' => [
                'demo_url' => $demoUrl,
                'restaurant_site_id' => $site->id,
                'site_slug' => $site->slug,
            ],
            'performed_by' => auth()->id(),
        ]);

        // Update lead tracking
        $updates = [
            'last_activity_at' => now(),
            'contacted_at' => now(),
        ];
        if ($lead->status === RestaurantLead::STATUS_NEW) {
            $updates['status'] = RestaurantLead::STATUS_CONTACTED;
        }
        $lead->update($updates);

        return back()->with('status', "Demo email sent to {$lead->email}");
    }

    public function export(Request $request)
    {
        $query = RestaurantLead::query();

        if ($status = $request->input('status')) {
            $query->status($status);
        }
        if ($city = $request->input('city')) {
            $query->city($city);
        }
        if ($search = $request->input('search')) {
            $query->search($search);
        }
        if ($websiteFilter = $request->input('website_filter')) {
            if ($websiteFilter === 'has') {
                $query->whereNotNull('website')->where('website', '!=', '');
            } elseif ($websiteFilter === 'none') {
                $query->where(function ($q) {
                    $q->whereNull('website')->orWhere('website', '');
                });
            }
        }

        $leads = $query->orderBy('business_name')->get();

        $filename = 'restaurant-leads-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($leads) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Business Name', 'Address', 'City', 'Province', 'Postal Code',
                'Phone', 'Email', 'Website', 'Owner', 'Operator',
                'Facility Type', 'Inspection Rating', 'Last Inspection',
                'Status', 'Priority', 'Notes', 'Next Follow-Up',
                'Source', 'Created',
            ]);

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->business_name,
                    $lead->address,
                    $lead->city,
                    $lead->province,
                    $lead->postal_code,
                    $lead->phone,
                    $lead->email,
                    $lead->website,
                    $lead->owner_name,
                    $lead->operator_name,
                    $lead->facility_type,
                    $lead->inspection_rating,
                    $lead->last_inspection_date?->format('Y-m-d'),
                    $lead->status_label,
                    $lead->priority_label,
                    $lead->notes,
                    $lead->next_follow_up_at?->format('Y-m-d'),
                    $lead->source,
                    $lead->created_at->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
