<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Models\Tag;
use App\Models\User;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Status;
use App\Models\Setting;
use App\Models\Language;
use App\Models\Priority;
use Faker\Extension\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\CustomPathGenerator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
// ✅ Spatie permission override line ke liye import
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PathGenerator::class, CustomPathGenerator::class);
    }

    public function boot()
    {
        Paginator::useBootstrapFive();

   // ✅ Manually force guard to 'admin' for permission checks
\Illuminate\Support\Facades\Auth::shouldUse('admin');

        try {
            DB::connection()->getPdo();
            $this->setupSettings();
            $this->setupViewComposer();
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
        }
    }

    private function setupSettings()
    {
        $general_settings = $this->getGeneralSettings();
        $pusher_settings = $this->getPusherSettings();
        $security_settings = $this->getSecuritySettings();
        $email_settings = $this->getEmailSettings();
        $media_storage_settings = $this->getMediaStorageSettings();
        $this->updateConfigs($general_settings, $pusher_settings, $email_settings, $media_storage_settings);

        $date_format = explode('|', $general_settings['date_format']);
        $php_date_format = $date_format[1];
        $this->app->instance('php_date_format', $php_date_format);
    }

    private function setupViewComposer()
    {
        View::composer('*', function ($view) {
            $languages = Language::all();
            $general_settings = $this->getGeneralSettings();
            $pusher_settings = $this->getPusherSettings();
            $email_settings = $this->getEmailSettings();
            $media_storage_settings = $this->getMediaStorageSettings();
            $security_settings = $this->getSecuritySettings();

            $date_format = explode('|', $general_settings['date_format']);
            $js_date_format = $date_format[0];
            $php_date_format = $date_format[1];

            $data = compact(
                'general_settings',
                'email_settings',
                'pusher_settings',
                'media_storage_settings',
                'languages',
                'js_date_format',
                'php_date_format',
                'security_settings'
            );

            $view->with($data);

            if (Auth::guard('web')->check() || Auth::guard('client')->check()) {
                $adminID = getAdminIdByUserRole();
                $statuses = Status::where('admin_id', $adminID)
                    ->orWhere(function ($query) {
                        $query->whereNull('admin_id')->where('is_default', 1);
                    })->get();
                $tags = Tag::where('admin_id', $adminID)->get();
                $priorities = Priority::where('admin_id', $adminID)->get();

                $view->with(compact('statuses', 'tags', 'priorities'));
            }
        });
    }

    private function getGeneralSettings()
    {
        $general_settings = get_settings('general_settings');
        $defaults = [
            'full_logo' => 'storage/logos/default_full_logo.png',
            'half_logo' => 'storage/logos/default_half_logo.png',
            'favicon' => 'storage/logos/default_favicon.png',
            'footer_logo' => 'storage/logos/footer_logo.png',
            'company_title' => 'Taskify - SaaS',
            'currency_symbol' => '₹',
            'currency_full_form' => 'Indian Rupee',
            'currency_code' => 'INR',
            'date_format' => 'DD-MM-YYYY|d-m-Y',
            'toast_time_out' => '5',
            'toast_position' => 'toast-top-right',
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($general_settings[$key]) || empty($general_settings[$key])) {
                $general_settings[$key] = $value;
            } elseif (in_array($key, ['full_logo', 'half_logo', 'favicon', 'footer_logo'])) {
                $general_settings[$key] = 'storage/' . $general_settings[$key];
            }
        }

        if (getAuthenticatedUser() && !(getAuthenticatedUser()->hasRole("superadmin") || getAuthenticatedUser()->hasRole("manager"))) {
            $adminSettings = Admin::findOrFail(getAdminIdByUserRole())->admin_settings;
            if (!empty($adminSettings)) {
                $adminSettings = json_decode($adminSettings, true);
                $general_settings['full_logo'] = empty($adminSettings['full_logo']) ? $general_settings['full_logo'] : 'storage/' . $adminSettings['full_logo'];
                $general_settings['half_logo'] = empty($adminSettings['half_logo']) ? $general_settings['half_logo'] : 'storage/' . $adminSettings['half_logo'];
                $general_settings['company_title'] = empty($adminSettings['company_title']) ? $general_settings['company_title'] : $adminSettings['company_title'];
            }
        }

        return $general_settings;
    }

    private function getPusherSettings()
    {
        return array_merge([
            'pusher_app_id' => '',
            'pusher_app_key' => '',
            'pusher_app_secret' => '',
            'pusher_app_cluster' => '',
        ], get_settings('pusher_settings'));
    }

    private function getSecuritySettings()
    {
        $settings = get_settings('security_settings') ?: [];
        return array_merge([
            'max_login_attempts' => 0,
            'time_decay' => 0,
        ], $settings);
    }

    private function getEmailSettings()
    {
        return array_merge([
            'email' => '',
            'password' => '',
            'smtp_host' => '',
            'smtp_port' => '',
            'email_content_type' => '',
            'smtp_encryption' => '',
        ], get_settings('email_settings'));
    }

    private function getMediaStorageSettings()
    {
        return array_merge([
            'media_storage_type' => '',
            's3_key' => '',
            's3_secret' => '',
            's3_region' => '',
            's3_bucket' => '',
        ], get_settings('media_storage_settings'));
    }

    private function updateConfigs($general_settings, $pusher_settings, $email_settings, $media_storage_settings)
    {
        Config::set([
            'app.timezone' => $general_settings['timezone'],
            'chatify.name' => $general_settings['company_title'],
            'chatify.pusher.key' => $pusher_settings['pusher_app_key'],
            'chatify.pusher.secret' => $pusher_settings['pusher_app_secret'],
            'chatify.pusher.app_id' => $pusher_settings['pusher_app_id'],
            'chatify.pusher.options.cluster' => $pusher_settings['pusher_app_cluster'],
            'mail.mailers.smtp.host' => $email_settings['smtp_host'],
            'mail.mailers.smtp.port' => $email_settings['smtp_port'],
            'mail.mailers.smtp.encryption' => $email_settings['smtp_encryption'],
            'mail.mailers.smtp.username' => $email_settings['email'],
            'mail.mailers.smtp.password' => $email_settings['password'],
            'mail.from.name' => $general_settings['company_title'],
            'mail.from.address' => $email_settings['email'],
            'filesystems.disks.s3.key' => $media_storage_settings['s3_key'],
            'filesystems.disks.s3.secret' => $media_storage_settings['s3_secret'],
            'filesystems.disks.s3.region' => $media_storage_settings['s3_region'],
            'filesystems.disks.s3.bucket' => $media_storage_settings['s3_bucket'],
        ]);
    }
}
