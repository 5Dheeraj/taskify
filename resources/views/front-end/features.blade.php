@extends('front-end.layout')
@section('title')
    <?= get_label('features', 'Features') ?>
@endsection
@section('content')
    <section class="section" id="features">
        <div class="bg-gradient-primary position-relative border-radius-xl w-100">
            <img src="/assets/front-end/img/gallery/waves-white.svg" alt="pattern-lines"
                class="position-absolute start-0 top-md-0 w-100 opacity-7">
            <div class="container pb-lg-9 pb-7 pt-7 postion-relative z-index-2">
                <div class="row">
                    <div class="col-md-8 mx-auto text-center">
                        <span class="badge bg-gradient-dark mb-2">{{ get_label('features', 'Features') }}</span>
                        <h3 class="text-white">
                            {{ get_label('taskify_features_heading', $general_settings['company_title'] . ' Powerful Features for Efficient Project Management') }}
                        </h3>
                        <p class="text-center text-white fs-0 fs-md-1 mt-3 mb-3">
                            {{ get_label('taskify_features_subheading', 'Streamline your team\'s workflow and boost productivity with our comprehensive set of features.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-lg-n8 mt-n7">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body text-center">

                                <div class="row mb-4 mt-6">
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="{{ asset('assets/front-end/img/icons/project-management.png') }}"
                                            alt="{{ get_label('project_management', 'Project Management') }}"
                                            class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('project_management', 'Project Management') }}
                                        </h4>
                                        <p class="text-muted">
                                            {{ get_label('project_management_desc', 'Create and manage multiple projects with ease, ensuring seamless collaboration and organization.') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="{{ asset('assets/front-end/img/icons/task-tracking.png') }}"
                                            alt="{{ get_label('task_tracking', 'Task Tracking') }}" class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('task_tracking', 'Task Tracking') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('task_tracking_desc', 'Assign, prioritize, and track tasks efficiently, keeping your team on top of their workload.') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="assets/front-end/img/icons/7.png"
                                            alt="{{ get_label('user_management', 'User Management') }}"
                                            class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('user_management', 'User Management') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('user_management_desc', 'Manage user roles, permissions, and access levels, ensuring secure collaboration and data privacy.') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="assets/front-end/img/icons/12.png"
                                            alt="{{ get_label('client_management', 'Client Management') }}"
                                            class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('client_management', 'Client Management') }}
                                        </h4>
                                        <p class="text-muted">
                                            {{ get_label('client_management_desc', 'Streamline communication and manage client relationships with dedicated client portals.') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="assets/front-end/img/icons/4.png"
                                            alt="{{ get_label('contract_management', 'Contract Management') }}"
                                            class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('contract_management', 'Contract Management') }}
                                        </h4>
                                        <p class="text-muted">
                                            {{ get_label('contract_management_desc', 'Create, store, and manage contracts seamlessly, ensuring compliance and transparency.') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="{{ asset('assets/front-end/img/icons/6.png') }}"
                                            alt="{{ get_label('reporting', 'Reporting') }}" class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('reporting', 'Reporting and Analytics') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('reporting_desc', 'Gain insights into project performance with comprehensive reporting and analytics features.') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="{{ asset('assets/front-end/img/icons/collaboration.png') }}"
                                            alt="{{ get_label('collaboration', 'Collaboration') }}" class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('collaboration', 'Collaboration Tools') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('collaboration_desc', 'Foster seamless communication and collaboration with built-in chat, file sharing, and documentation features.') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="assets/front-end/img/icons/10.png"
                                            alt="{{ get_label('time_tracking', 'Time Tracking') }}" class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('time_tracking', 'Time Tracking') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('time_tracking_desc', 'Monitor time spent on tasks and projects, enabling accurate billing and productivity analysis.') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="assets/front-end/img/icons/integration_8020781.png"
                                            alt="{{ get_label('integrations', 'Integrations') }}" class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('integrations', 'Integrations') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('integrations_desc', 'Connect Taskify with your favorite tools and services for a seamless workflow experience.') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="{{ asset('assets/front-end/img/icons/payment-gateway.png') }}"
                                            alt="{{ get_label('payment_gateways', 'Payment Gateways') }}"
                                            class="icon-size" />
                                        <h4 class="mt-3 mb-2">
                                            {{ get_label('payment_gateways', 'Multiple Payment Gateways') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('payment_gateways_desc', 'Accept payments securely through integrated gateways like Stripe, PayPal, Paystack, and PhonePe.') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="assets/front-end/img/icons/11.png"
                                            alt="{{ get_label('security', 'Security') }}" class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('security', 'Security and Compliance') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('security_desc', 'Enjoy peace of mind with robust security measures and compliance with industry standards.') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="assets/front-end/img/icons/13.png"
                                            alt="{{ get_label('customization', 'Customization') }}" class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('customization', 'Customization') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('customization_desc', 'Tailor Taskify to your specific needs with our flexible customization options and integrations.') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <!-- Chat Messages -->
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="/assets/front-end/img/icons/message_6307263.png" alt="Chat Messages"
                                            class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('chat_messages', 'Chat Messages') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('chat_messagesDesc', 'Enable real-time communication among team members with built-in chat messaging.') }}
                                        </p>
                                    </div>
                                    <!-- Virtual Meetings -->
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="{{ asset('/assets/front-end/img/icons/virtual-meeting.png') }}"
                                            alt="Virtual Meetings" class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('virtual_meetings', 'Virtual Meetings') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('virtual_meetingsDesc', 'Organize virtual meetings and video conferences to facilitate remote collaboration.') }}
                                        </p>
                                    </div>
                                    <!-- Payslips -->
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="{{ asset('/assets/front-end/img/icons/payslip.png') }}" alt="Payslips"
                                            class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('payslips', 'Payslips') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('payslipsDesc', 'Generate and distribute payslips to employees securely,ensuring transparency in payroll management.') }}
                                        </p>
                                    </div>
                                    <!-- Finance Management -->
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="{{ asset('/assets/front-end/img/icons/finance-management.png') }}"
                                            alt="Finance Management" class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('finance_management', 'Finance Management') }}
                                        </h4>
                                        <p class="text-muted">
                                            {{ get_label(
                                                'finance_managementDesc',
                                                'Track expenses, manage budgets, and calculate taxes to maintain financial stability and compliance.',
                                            ) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <!-- Team Engagement -->
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="/assets/front-end/img/icons/trading_3790963.png" alt="Team Engagement"
                                            class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('team_engagement', 'Team Engagement') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('team_engagementDesc', 'Celebrate upcoming birthdays and work anniversaries, and stay updated on team members leave status to foster a positive work environment.') }}
                                        </p>
                                    </div>
                                    <!-- Elegant and Informative Dashboard -->
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="{{ asset('/assets/front-end/img/icons/dashboard.png') }}"
                                            alt="Elegant and Informative Dashboard" class="icon-size" />
                                        <h4 class="mt-3 mb-2">{{ get_label('elegant_dashboard', 'Elegant Dashboard') }}
                                        </h4>
                                        <p class="text-muted">
                                            {{ get_label('elegant_dashboardDesc', 'Access a visually appealing and comprehensive dashboard that provides key insights and metrics about your projects and tasks.') }}
                                        </p>
                                    </div>
                                    <!-- Multi-Language Support -->
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="{{ asset('/assets/front-end/img/icons/multi-language.png') }}"
                                            alt="Multi-Language Support" class="icon-size" />
                                        <h4 class="mt-3 mb-2">
                                            {{ get_label('multi_language_support', 'Multi-Language Support') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('multi_language_supportDesc', 'Enable users to switch between multiple languages to accommodate diverse teams and clients.') }}
                                        </p>
                                    </div>
                                    <!-- Workspace Management -->
                                    <div class="col-md-6 col-lg-3 text-center">
                                        <img src="{{ asset('/assets/front-end/img/icons/workspace.png') }}"
                                            alt="Workspace Management" class="icon-size" />
                                        <h4 class="mt-3 mb-2">
                                            {{ get_label('workspace_management', 'Workspace Management') }}</h4>
                                        <p class="text-muted">
                                            {{ get_label('workspace_managementDesc', 'Organize projects, tasks, and team members into separate workspaces for better organization and efficiency.') }}
                                        </p>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
