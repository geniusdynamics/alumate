<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index()
    {
        return Inertia::render('Contact/Index', [
            'contact_info' => [
                'email' => 'support@alumni-platform.com',
                'phone' => '+1 (555) 123-4567',
                'address' => '123 Education Street, Learning City, LC 12345',
                'business_hours' => [
                    'monday_friday' => '9:00 AM - 6:00 PM',
                    'saturday' => '10:00 AM - 4:00 PM',
                    'sunday' => 'Closed',
                ],
            ],
            'departments' => [
                [
                    'name' => 'General Support',
                    'email' => 'support@alumni-platform.com',
                    'description' => 'General questions and technical support',
                ],
                [
                    'name' => 'Institution Partnerships',
                    'email' => 'partnerships@alumni-platform.com',
                    'description' => 'Institutional partnerships and onboarding',
                ],
                [
                    'name' => 'Employer Services',
                    'email' => 'employers@alumni-platform.com',
                    'description' => 'Employer registration and job posting support',
                ],
                [
                    'name' => 'Alumni Relations',
                    'email' => 'alumni@alumni-platform.com',
                    'description' => 'Alumni engagement and success stories',
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'department' => 'nullable|string|in:general,partnerships,employers,alumni',
            'phone' => 'nullable|string|max:20',
            'organization' => 'nullable|string|max:255',
        ]);

        // Determine recipient email based on department
        $recipientEmail = match ($request->department) {
            'partnerships' => 'partnerships@alumni-platform.com',
            'employers' => 'employers@alumni-platform.com',
            'alumni' => 'alumni@alumni-platform.com',
            default => 'support@alumni-platform.com'
        };

        try {
            // Send email to the appropriate department
            Mail::send('emails.contact', [
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'messageContent' => $request->message,
                'phone' => $request->phone,
                'organization' => $request->organization,
                'department' => $request->department,
            ], function ($message) use ($request, $recipientEmail) {
                $message->to($recipientEmail)
                    ->subject('Contact Form: '.$request->subject)
                    ->replyTo($request->email, $request->name);
            });

            // Send confirmation email to the user
            Mail::send('emails.contact-confirmation', [
                'name' => $request->name,
                'subject' => $request->subject,
            ], function ($message) use ($request) {
                $message->to($request->email, $request->name)
                    ->subject('Thank you for contacting Alumni Platform');
            });

            return back()->with('success', 'Thank you for your message! We\'ll get back to you within 24 hours.');

        } catch (\Exception $e) {
            return back()->with('error', 'Sorry, there was an error sending your message. Please try again or contact us directly at support@alumni-platform.com.');
        }
    }
}
