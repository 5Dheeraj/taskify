<?php
use App\Models\User;
use App\Models\Subscription;
use App\Models\Admin;
use App\Models\Ticket;
use App\Models\Workspace;
use App\Models\LeaveRequest;
use Chatify\ChatifyMessenger;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
$user = getAuthenticatedUser();
$adminId = getAdminIdByUserRole();
if (isAdminOrHasAllDataAccess()) {
    $workspaces = Workspace::where('admin_id', $adminId)->skip(0)->take(5)->get();
    $total_workspaces = Workspace::where('admin_id', $adminId)->count();
} else {
    $workspaces = $user->workspaces;
    $total_workspaces = count($workspaces);
    $workspaces = $user->workspaces()->skip(0)->take(5)->get();
}
$current_workspace = Workspace::find(session()->get('workspace_id'));
$current_workspace_title = $current_workspace->title ?? 'No workspace(s) found';
$messenger = new ChatifyMessenger();
$unread = $messenger->totalUnseenMessages();
$pending_todos_count = $user->todos(0)->count();
$ongoing_meetings_count = $user->meetings('ongoing')->count();
$query = LeaveRequest::where('status', 'pending')->where('workspace_id', session()->get('workspace_id'));
if (!is_admin_or_leave_editor()) {
    $query->where('user_id', $user->id);
}
$pendingLeaveRequestsCount = $query->count();
$prefix = null;
$openTicketsCount = Ticket::where('status', 'open')->count();
$currentRoute = Route::current();
if ($currentRoute) {
    $uriSegments = explode('/', $currentRoute->uri());
    $prefix = count($uriSegments) > 1 ? $uriSegments[0] : '';
}
?>
@if ($user->hasrole('superadmin') || $user->hasrole('manager'))
    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme menu-container">
        <div class="app-brand demo">
            <a href="{{ route('home.index') }}" class="app-brand-link">
                <span class="app-brand-logo demo">
                    <img src="{{ asset($general_settings['full_logo']) }}" width="200px" alt="" />
                </span>
            </a>
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large d-block d-xl-none ms-auto">
                <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
        </div>
        <div class="menu-inner-shadow"></div>
        <ul class="menu-inner py-1">
            <hr class="dropdown-divider" />
            <!-- Dashboard -->
            <li class="menu-item {{ Request::is($prefix . '/home') ? 'active' : '' }}">
                <a href="{{ route('superadmin.panel') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle "></i>
                    <div><?= get_label('dashboard', 'Dashboard') ?></div>
                </a>
            </li>
            <li class="menu-item {{ Request::is($prefix . '/customers') || Request::is($prefix . '/customers/*') ? 'active' : '' }}">


                <a href="{{ route('customers.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user-circle "></i>
                    <div><?= get_label('customers', 'Customers') ?></div>
                </a>
            </li>
            <li class="menu-item {{ Request::is($prefix . '/plans') || Request::is($prefix . '/plans/*') ? 'active' : '' }}">

                <a href="{{ route('plans.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-task "></i>
                    <div><?= get_label('plans', 'Plans') ?></div>
                </a>
            </li>
            <li class="menu-item {{ Request::is($prefix . '/subscriptions') || Request::is($prefix . '/subscriptions/*') ? 'active' : '' }}">

                <a href="{{ route('subscriptions.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-spreadsheet "></i>
                    <div><?= get_label('subscriptions', 'Subscriptions') ?></div>
                </a>
            </li>
            <li class="menu-item {{ Request::is($prefix . '/transactions') || Request::is($prefix . '/transactions/*') ? 'active' : '' }}">

                <a href="{{ route('transactions.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money "></i>
                    <div><?= get_label('transactions', 'Transactions') ?></div>
                </a>
            </li>

            @hasRole('superadmin')
                <li
                    class="menu-item {{ Request::is($prefix . '/managers') || Request::is($prefix . '/managers/*') ? 'active' : '' }}">
                    <a href="{{ route('managers.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-user "></i>
                        <div>
                            {{ get_label('managers', 'Managers') }}
                        </div>
                    </a>
                </li>
            @endhasRole

            <li class="menu-item {{ Request::is('support') || Request::is('support/*') ? 'active' : '' }}">
                <a href="{{ route('support.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-support "></i>
                    <div>
                        {{ get_label('support', 'Support') }}
                        @if ($openTicketsCount > 0)
                            <span
                                class="badge badge-center bg-danger w-px-20 h-px-20 rounded-circle flex-shrink-0">{{ $openTicketsCount }}</span>
                        @endif
                    </div>
                </a>
            </li>
            @if ($user->hasRole('admin') || $user->hasRole('superadmin'))
    <li class="menu-item {{ Request::is('master-panel/training*') ? 'active' : '' }}">
        <a href="{{ route('training.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-slideshow"></i>
            <div>{{ get_label('training_module', 'Training') }}</div>
        </a>
    </li>
@endif
            @hasRole('superadmin')
                <li
                    class="menu-item {{ Request::is($prefix . '/settings') || Request::is($prefix . '/roles/*') || Request::is($prefix . '/settings/*') ? 'active open' : '' }}">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bx-cog "></i>
                        <div data-i18n="User interface"><?= get_label('settings', 'Settings') ?></div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ Request::is($prefix . '/settings/general') ? 'active' : '' }}">
                            <a href="{{ route('settings.index') }}" class="menu-link">
                                <div><?= get_label('general', 'General') ?></div>
                            </a>
                        </li>
                        <li class="menu-item {{ Request::is($prefix . '/settings/security') ? 'active' : '' }}">
                            <a href="{{ route('security.index') }}" class="menu-link">
                                <div><?= get_label('security_settings', 'Security Settings') ?></div>
                            </a>
                        </li>
                        <li
                            class="menu-item {{ Request::is($prefix . '/settings/permission') || Request::is('roles/*') ? 'active' : '' }}">
                            <a href="{{ route('roles.index') }}" class="menu-link">
                                <div><?= get_label('permissions', 'Permissions') ?></div>
                            </a>
                        </li>
                        <li
                            class="menu-item {{ Request::is($prefix . '/settings/languages') || Request::is($prefix . '/settings/languages/create') ? 'active' : '' }}">
                            <a href="{{ route('languages.index') }}" class="menu-link">
                                <div><?= get_label('languages', 'Languages') ?></div>
                            </a>
                        </li>
                        <li class="menu-item {{ Request::is($prefix . '/settings/email') ? 'active' : '' }}">
                            <a href="{{ route('settings.email') }}" class="menu-link">
                                <div><?= get_label('email', 'Email') ?></div>
                            </a>
                        </li>
                        <li class="menu-item {{ Request::is($prefix . '/settings/sms-gateway') ? 'active' : '' }}">


                            <a href="{{ route('sms_gateway.index') }}" class="menu-link">
                                <div><?= get_label('notifications_settings', 'Notifications Settings') ?></div>
                            </a>
                        </li>
                        <li class="menu-item {{ Request::is($prefix . '/settings/pusher') ? 'active' : '' }}">
                            <a href="{{ route('settings.pusher') }}" class="menu-link">
                                <div><?= get_label('pusher', 'Pusher') ?></div>
                            </a>
                        </li>
                        <li class="menu-item {{ Request::is($prefix . '/settings/media-storage') ? 'active' : '' }}">
                            <a href="{{ route('settings.media_storage') }}" class="menu-link">
                                <div><?= get_label('media_storage', 'Media storage') ?></div>
                            </a>
                        </li>
                        <li class="menu-item {{ Request::is($prefix . '/settings/payment-methods') ? 'active' : '' }}">
                            <a href="{{ route('payment_method.index') }}" class="menu-link">
                                <div><?= get_label('payment_methods', 'Payment Methods') ?></div>
                            </a>
                        </li>
                        <li class="menu-item {{ Request::is($prefix . '/settings/privacy-policy') ? 'active' : '' }}">
                            <a href="{{ route('privacy_policy.index') }}" class="menu-link">
                                <div><?= get_label('privacy_policy', 'Privacy Policy') ?></div>
                            </a>
                        </li>
                        <li
                            class="menu-item {{ Request::is($prefix . '/settings/terms-and-conditions') ? 'active' : '' }}">
                            <a href="{{ route('terms_and_conditions.index') }}" class="menu-link">
                                <div><?= get_label('terms_and_conditions', 'Terms And Conditions') ?></div>
                            </a>
                        </li>
                        <li class="menu-item {{ Request::is($prefix . '/settings/refund-policy') ? 'active' : '' }}">
                            <a href="{{ route('refund_policy.index') }}" class="menu-link">
                                <div><?= get_label('refund_policy', 'Refund Policy') ?></div>
                            </a>
                        </li>
                        <li class="menu-item {{ Request::is($prefix . '/settings/templates') ? 'active' : '' }}">
                            <a href="{{ route('templates.index') }}" class="menu-link">
                                <div><?= get_label('notification_templates', 'Notification Templates') ?></div>
                            </a>
                        </li>
                        <li class="menu-item {{ Request::is($prefix . '/settings/system-updater') ? 'active' : '' }}">
                            <a href="{{ route('update.index') }}" class="menu-link">
                                <div><?= get_label('system_updater', 'System updater') ?></div>
                            </a>
                        </li>

                    </ul>
                </li>
            @endhasRole
        </ul>
    </aside>
@else
    @php
        $modules = get_subscriptionModules();
    @endphp
     <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme menu-container">
        <div class="app-brand demo">
            <a href="{{ route('home.index') }}" class="app-brand-link">
                <span class="app-brand-logo demo">
                    <img src="{{ asset($general_settings['full_logo']) }}" width="200px" alt="" />
                </span>
                <!-- <span class="app-brand-text demo menu-text fw-bolder ms-2">Taskify</span> -->
            </a>
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large d-block d-xl-none ms-auto">
                <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
        </div>
        <div class="d-flex flex-column gap-2 px-3">
            <!-- Workspace Switch Button -->
            <div class="btn-group dropend w-100">
                <button type="button" class="btn btn-primary dropdown-toggle w-100 text-start"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <?= $current_workspace_title ?>
                </button>
                <ul class="dropdown-menu">
                    @foreach ($workspaces as $workspace)
                        <?php $checked = $workspace->id == session()->get('workspace_id') ? "<i class='menu-icon tf-icons bx bx-check-square text-primary'></i>" : "<i class='menu-icon tf-icons bx bx-square text-solid'></i>"; ?>
                        <li><a class="dropdown-item"
                                href="{{ route('workspaces.switch', ['id' => $workspace->id]) }}"><?= $checked ?>{{ $workspace->title }}
                                <?= $workspace->is_primary ? ' <span class="badge bg-success">' . get_label('primary', 'Primary') . '</span>' : '' ?></a>
                        </li>
                    @endforeach
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    @if ($user->can('manage_workspaces'))
                        <li>@if ($user->can('manage_workspaces'))
    <li><a class="dropdown-item" href="{{ route('workspaces.index') }}"><i
                class='menu-icon tf-icons bx bx-bar-chart-alt-2 text-success'></i>{{ get_label('manage_workspaces', 'Manage workspaces') }}
            {!! $total_workspaces > 5 ? '<span class="badge badge-center bg-primary"> + ' . ($total_workspaces - 5) . '</span>' : '' !!}
        </a>
    </li>
@endif

@if ($user->can('create_workspaces'))
    <li><a class="dropdown-item" href="{{ route('workspaces.create') }}"><i
                class='menu-icon tf-icons bx bx-plus text-warning'></i>{{ get_label('create_workspace', 'Create workspace') }}</a>
    </li>
@endif

@if ($user->hasRole('superadmin'))
    <li><a class="dropdown-item"
            href="{{ route('workspaces.edit', ['id' => session()->get('workspace_id')]) }}"><i
                class='menu-icon tf-icons bx bx-edit text-info'></i>{{ get_label('edit_workspace', 'Edit workspace') }}</a>
                        </li>
                        @endif
                    @endif
                    <li><a class="dropdown-item" href="#"
                            data-route-prefix="{{ Route::getCurrentRoute()->getPrefix() }}"
                            id="remove-participant"><i
                                class='menu-icon tf-icons bx bx-exit text-danger'></i><?= get_label('remove_me_from_workspace', 'Remove me from workspace') ?></a>
                    </li>
                </ul>
            </div>
            <!-- Search Menu -->
            <div class="w-100 position-relative">
                <input type="text" id="menu-search" class="form-control"
                    placeholder="{{ get_label('search_menu', 'Search Menu') }}...">
                <button type="button" id="clear-search"
                    class="btn btn-sm position-absolute top-50 translate-middle-y end-0 me-2" style="display: none;">
                    <i class="bx bx-x"></i>
                </button>
            </div>
        </div>
        <ul class="menu-inner pb-1">
        <hr class="dropdown-divider" />
        @php
        $menuOrder = json_decode(DB::table('menu_orders')->where(getGuardName() == 'web' ? 'user_id' : 'client_id', getAuthenticatedUser()->id)->value('menu_order'), true);
        $menus = getMenus();

        // Sort menus based on saved order
        $sortedMenus = [];
        if ($menuOrder) {
        foreach ($menuOrder as $order) {
        $menu = collect($menus)->firstWhere('id', $order['id']);
        if ($menu) {
        // Sort submenus if present
        if (!empty($order['submenus'])) {
        $submenuIds = collect($order['submenus'])->pluck('id')->toArray(); // Get the array of submenu IDs from saved order
        $menu['submenus'] = collect($menu['submenus'])->sortBy(function ($submenu) use ($submenuIds) {
        return array_search($submenu['id'], $submenuIds);
        })->toArray();
        }
        $sortedMenus[] = $menu;
        }
        }
        } else {
        // If no order is saved, return the default order of menus
        $sortedMenus = $menus;
        }
        @endphp
        @foreach ($sortedMenus as $menu)
        @if (!isset($menu['show']) || $menu['show'] === 1)
        <li class="{{ $menu['class'] }}">
            <a href="{{ $menu['url'] ?? 'javascript:void(0)' }}" class="menu-link {{ isset($menu['submenus']) ? 'menu-toggle' : '' }}">
                <i class="menu-icon tf-icons {{ $menu['icon'] }}"></i>
                <div>
                    {{ $menu['label'] }}
                    @if (isset($menu['badge']) && $menu['badge'])
                    {!! $menu['badge'] !!}
                    @endif
                </div>
            </a>

            @if (isset($menu['submenus']))
            <ul class="menu-sub">
                @foreach ($menu['submenus'] as $submenu)
                @if (!isset($submenu['show']) || $submenu['show'] === 1)
                <li class="{{ $submenu['class'] }}">
                    <a href="{{ $submenu['url'] }}" class="menu-link">
                        <div>{{ $submenu['label'] }}</div>
                    </a>
                </li>
                @endif
                @endforeach
            </ul>
            @endif
        </li>
        @endif
        @endforeach
    </ul>

    </aside>
@endif
