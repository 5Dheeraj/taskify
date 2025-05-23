<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FrontEndController extends Controller
{
    public function index()
    {
        error_reporting(E_ALL);
        // Display errors
        ini_set('display_errors', 1);
        $plans = Plan::where('status', 'active')->orderBy('monthly_price', 'asc')->take(3)->get();
        $currency_symbol = (get_settings('general_settings')['currency_symbol']);

        return view('front-end.index', ['plans' => $plans, 'currency_symbol' => $currency_symbol]);
    }
    public function features()
    {
        return view('front-end.features');
    }
    public function about_us()
    {
        return view('front-end.about_us');
    }
    public function contact_us()
    {
        return view('front-end.contact_us');
    }
    public function send_mail(Request $request)
    {
        // Validate form data (optional but recommended)
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string'
        ]);

        // Prepare email content
        $senderName = $request->input('name');
        $senderEmail = $request->input('email');
        $messageContent = $request->input('message');

        $emailBody = [
            'name' => $senderName,
            'email' => $senderEmail,
            'message' => $messageContent
        ];

        try {
            // Send the email using the globally configured settings
            Mail::send('emails.contact', ['content' => $emailBody], function ($message) use ($senderEmail, $senderName) {
                // Use the globally configured "from" and set reply-to as sender's email
                $message->to(config('mail.from.address'))->subject("[Contact Form] Inquiry from $senderName");
                $message->replyTo($senderEmail, $senderName);
            });

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully!'
            ]);
        } catch (Exception $e) {
            // Error response with exception message
            return response()->json([
                'success' => false,
                'message' => "Message could not be sent. Mailer Error: {$e->getMessage()}"
            ], 500); // Set appropriate status code for internal server error
        }
    }

    public function pricing()
    {
        $plans = Plan::where('status', 'active')->orderBy('monthly_price', 'asc')->get();
        $currency_symbol = (get_settings('general_settings')['currency_symbol']);
        return view('front-end.pricing', ['currency_symbol' => $currency_symbol, 'plans' => $plans]);
    }
    public function faqs()
    {
        return view('front-end.faqs');
    }
    public function terms_and_condition()
    {
        $terms_and_conditions = get_settings('terms_and_conditions');
        return view('front-end.terms_and_conditions', ['terms_and_conditions' => $terms_and_conditions]);
    }
    public function refund_policy()
    {
        $refund_policy = get_settings('refund_policy');
        return view('front-end.refund_policy', ['refund_policy' => $refund_policy]);
    }
    public function privacy_policy()
    {
        $privacy_policy = get_settings('privacy_policy');
        return view('front-end.privacy_policy', ['privacy_policy' => $privacy_policy]);
    }
}
